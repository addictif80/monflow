import { FastifyPluginAsync } from 'fastify'
import { navidromeService } from '../services/navidrome.js'
import { fetchLyrics } from '../services/lyrics.js'
import { RadioService } from '../services/radio.js'
import { config } from '../config/index.js'
import axios from 'axios'

export const playerRoutes: FastifyPluginAsync = async (fastify) => {
  const radioService = new RadioService(fastify.prisma)

  fastify.get('/stream/:trackId', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const { trackId } = req.params as { trackId: string }
    const { maxBitRate, format } = req.query as { maxBitRate?: string; format?: string }
    const subscription = await fastify.prisma.subscription.findUnique({ where: { userId: req.user.sub } })
    if (!subscription || subscription.status === 'SUSPENDED' || subscription.status === 'CANCELLED') {
      return reply.status(403).send({ error: 'Abonnement requis' })
    }
    fastify.prisma.listeningHistory.create({ data: { userId: req.user.sub, trackId, playedAt: new Date() } }).catch(() => {})
    const params = new URLSearchParams({ id: trackId, v: '1.16.1', c: 'monflow', u: config.NAVIDROME_ADMIN_USER, p: config.NAVIDROME_ADMIN_PASSWORD, ...(maxBitRate ? { maxBitRate } : {}), ...(format ? { format } : {}) })
    const streamUrl = `${config.NAVIDROME_URL}/rest/stream?${params}`
    try {
      const range = req.headers.range
      const headers: Record<string, string> = {}
      if (range) headers['Range'] = range
      const response = await axios({ method: 'GET', url: streamUrl, responseType: 'stream', headers, timeout: 60000 })
      reply.status(range ? 206 : 200)
      if (response.headers['content-type']) reply.header('Content-Type', response.headers['content-type'])
      if (response.headers['content-length']) reply.header('Content-Length', response.headers['content-length'])
      if (response.headers['content-range']) reply.header('Content-Range', response.headers['content-range'])
      if (response.headers['accept-ranges']) reply.header('Accept-Ranges', response.headers['accept-ranges'])
      reply.header('Cache-Control', 'no-cache')
      return reply.send(response.data)
    } catch (err: any) {
      return reply.status(502).send({ error: 'Erreur de streaming' })
    }
  })

  fastify.post('/scrobble', { preHandler: [fastify.authenticate] }, async (req) => {
    const { trackId, duration, completed } = req.body as { trackId: string; duration?: number; completed?: boolean }
    await fastify.prisma.listeningHistory.create({ data: { userId: req.user.sub, trackId, duration: duration || null, completed: completed || false, playedAt: new Date() } })
    navidromeService.scrobble(trackId, completed).catch(() => {})
    return { ok: true }
  })

  fastify.get('/lyrics/:trackId', { preHandler: [fastify.authenticate] }, async (req) => {
    const { trackId } = req.params as { trackId: string }
    const cached = await fastify.prisma.lyricsCache.findUnique({ where: { trackId } })
    if (cached) return { lyrics: cached.lyrics, synced: cached.synced, source: cached.source }
    const track = await navidromeService.getSong(trackId)
    if (!track) return { lyrics: null, synced: null }
    const result = await fetchLyrics(track.title, track.artist, track.album, track.duration)
    await fastify.prisma.lyricsCache.create({ data: { trackId, trackName: track.title, artist: track.artist, lyrics: result.lyrics, synced: result.synced as any, source: result.source } })
    return result
  })

  fastify.get('/similar/:trackId', { preHandler: [fastify.authenticate] }, async (req) => {
    const { trackId } = req.params as { trackId: string }
    const { count } = req.query as { count?: string }
    return { tracks: await navidromeService.getSimilarSongs(trackId, parseInt(count || '10')) }
  })

  fastify.get('/radio', { preHandler: [fastify.authenticate] }, async (req) => {
    const { seed, size } = req.query as { seed?: string; size?: string }
    return { tracks: await radioService.generateRadio(req.user.sub, seed || undefined, parseInt(size || '30')) }
  })

  fastify.get('/radio/artist/:artistId', { preHandler: [fastify.authenticate] }, async (req) => {
    const { artistId } = req.params as { artistId: string }
    return { tracks: await radioService.generateArtistRadio(artistId) }
  })

  fastify.get('/cover/:id', async (req, reply) => {
    const { id } = req.params as { id: string }
    const { size } = req.query as { size?: string }
    const coverUrl = navidromeService.getCoverArtUrl(id, size ? parseInt(size) : undefined)
    try {
      const response = await axios({ method: 'GET', url: coverUrl, responseType: 'stream', timeout: 10000 })
      reply.header('Content-Type', response.headers['content-type'] || 'image/jpeg')
      reply.header('Cache-Control', 'public, max-age=86400')
      return reply.send(response.data)
    } catch { return reply.status(404).send() }
  })

  fastify.get('/recent', { preHandler: [fastify.authenticate] }, async (req) => {
    const history = await fastify.prisma.listeningHistory.findMany({ where: { userId: req.user.sub }, orderBy: { playedAt: 'desc' }, take: 20, distinct: ['trackId'] })
    const tracks = await Promise.all(history.slice(0, 10).map(h => navidromeService.getSong(h.trackId)))
    return { tracks: tracks.filter(Boolean) }
  })

  fastify.get('/search', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const { q, artistCount, albumCount, songCount, offset } = req.query as { q?: string; artistCount?: string; albumCount?: string; songCount?: string; offset?: string }
    if (!q || q.trim().length < 1) return reply.status(400).send({ error: 'Requête de recherche requise' })
    return navidromeService.search(q, { artistCount: parseInt(artistCount || '5'), albumCount: parseInt(albumCount || '5'), songCount: parseInt(songCount || '20'), offset: parseInt(offset || '0') })
  })

  fastify.get('/home', { preHandler: [fastify.authenticate] }, async (req) => {
    const [newest, mostPlayed, randomSongs, genres] = await Promise.all([
      navidromeService.getNewestAlbums(10), navidromeService.getMostPlayedAlbums(10),
      navidromeService.getRandomSongs({ size: 20 }), navidromeService.getGenres(),
    ])
    const recentHistory = await fastify.prisma.listeningHistory.findMany({ where: { userId: req.user.sub }, orderBy: { playedAt: 'desc' }, take: 10, distinct: ['trackId'] })
    return { newestAlbums: newest, mostPlayedAlbums: mostPlayed, randomTracks: randomSongs, genres: genres.slice(0, 20), recentTrackIds: recentHistory.map(h => h.trackId) }
  })

  fastify.get('/artist/:id', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const result = await navidromeService.getArtist(id)
    if (!result) return reply.status(404).send({ error: 'Artiste non trouvé' })
    return { ...result, similar: await navidromeService.getSimilarArtists(id, 6) }
  })

  fastify.get('/album/:id', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const result = await navidromeService.getAlbum(id)
    if (!result) return reply.status(404).send({ error: 'Album non trouvé' })
    return result
  })

  fastify.get('/genres', { preHandler: [fastify.authenticate] }, async () => ({ genres: await navidromeService.getGenres() }))

  fastify.get('/genres/:genre/songs', { preHandler: [fastify.authenticate] }, async (req) => {
    const { genre } = req.params as { genre: string }
    const { count, offset } = req.query as { count?: string; offset?: string }
    return { tracks: await navidromeService.getSongsByGenre(genre, parseInt(count || '50'), parseInt(offset || '0')) }
  })
}
