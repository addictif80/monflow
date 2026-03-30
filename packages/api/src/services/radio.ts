import { PrismaClient } from '@prisma/client'
import { navidromeService, NavidromeTrack } from './navidrome.js'

export class RadioService {
  constructor(private prisma: PrismaClient) {}

  async generateRadio(userId: string, seedTrackId?: string, size = 30): Promise<NavidromeTrack[]> {
    const tracks: NavidromeTrack[] = []
    const addedIds = new Set<string>()

    const recentHistory = await this.prisma.listeningHistory.findMany({
      where: { userId, playedAt: { gte: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000) } },
      orderBy: { playedAt: 'desc' },
      take: 100,
    })
    const recentTrackIds = new Set(recentHistory.map(h => h.trackId))

    const artistCounts = new Map<string, number>()
    for (const entry of recentHistory) {
      if (entry.artistId) artistCounts.set(entry.artistId, (artistCounts.get(entry.artistId) || 0) + 1)
    }
    const topArtistIds = [...artistCounts.entries()].sort((a, b) => b[1] - a[1]).slice(0, 5).map(([id]) => id)

    if (seedTrackId) {
      const similar = await navidromeService.getSimilarSongs(seedTrackId, 15)
      for (const track of similar) {
        if (!recentTrackIds.has(track.id) && !addedIds.has(track.id)) { tracks.push(track); addedIds.add(track.id) }
      }
    }

    for (const artistId of topArtistIds) {
      if (tracks.length >= size) break
      try {
        const similarArtists = await navidromeService.getSimilarArtists(artistId, 3)
        for (const artist of similarArtists) {
          if (tracks.length >= size) break
          const topSongs = await navidromeService.getTopSongs(artist.name, 5)
          for (const track of topSongs) {
            if (!recentTrackIds.has(track.id) && !addedIds.has(track.id)) { tracks.push(track); addedIds.add(track.id) }
          }
        }
      } catch {}
    }

    if (tracks.length < size) {
      const randomSongs = await navidromeService.getRandomSongs({ size: size - tracks.length + 20 })
      for (const track of randomSongs) {
        if (tracks.length >= size) break
        if (!recentTrackIds.has(track.id) && !addedIds.has(track.id)) { tracks.push(track); addedIds.add(track.id) }
      }
    }

    return shuffleWithBias(tracks, 0.3).slice(0, size)
  }

  async generateArtistRadio(artistId: string, size = 20): Promise<NavidromeTrack[]> {
    const tracks: NavidromeTrack[] = []
    const addedIds = new Set<string>()
    const similarArtists = await navidromeService.getSimilarArtists(artistId, 5)
    for (const artist of [{ id: artistId, name: '' }, ...similarArtists]) {
      const result = await navidromeService.getArtist(artist.id)
      if (!result) continue
      for (const album of result.albums.slice(0, 2)) {
        const albumData = await navidromeService.getAlbum(album.id)
        if (!albumData) continue
        for (const track of albumData.songs.slice(0, 3)) {
          if (!addedIds.has(track.id) && tracks.length < size) { tracks.push(track); addedIds.add(track.id) }
        }
      }
    }
    return shuffleWithBias(tracks, 0.2)
  }
}

function shuffleWithBias<T>(array: T[], bias: number): T[] {
  const result = [...array]
  const splitPoint = Math.floor(result.length * bias)
  for (let i = result.length - 1; i > splitPoint; i--) {
    const j = splitPoint + Math.floor(Math.random() * (i - splitPoint + 1))
    ;[result[i], result[j]] = [result[j], result[i]]
  }
  return result
}
