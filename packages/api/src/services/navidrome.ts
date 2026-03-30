import axios, { AxiosInstance } from 'axios'
import { config } from '../config/index.js'

export interface NavidromeTrack {
  id: string; title: string; album: string; albumId: string; artist: string; artistId: string
  duration: number; bitRate?: number; genre?: string; year?: number; coverArt?: string
  size?: number; contentType?: string; suffix?: string; path?: string; playCount?: number
}
export interface NavidromeAlbum {
  id: string; name: string; artist: string; artistId: string; coverArt?: string
  songCount: number; duration: number; year?: number; genre?: string
}
export interface NavidromeArtist { id: string; name: string; coverArt?: string; albumCount: number }
export interface NavidromeSearchResult { artists: NavidromeArtist[]; albums: NavidromeAlbum[]; tracks: NavidromeTrack[] }

class NavidromeService {
  private adminClient: AxiosInstance
  constructor() { this.adminClient = axios.create({ baseURL: config.NAVIDROME_URL, timeout: 30000 }) }

  private getAdminParams(extra: Record<string, string | number> = {}) {
    return { u: config.NAVIDROME_ADMIN_USER, p: config.NAVIDROME_ADMIN_PASSWORD, v: '1.16.1', c: 'monflow', f: 'json', ...extra }
  }

  async adminRequest<T = any>(endpoint: string, params: Record<string, any> = {}): Promise<T> {
    const response = await this.adminClient.get(`/rest/${endpoint}`, { params: { ...this.getAdminParams(), ...params } })
    const result = response.data['subsonic-response']
    if (result.status !== 'ok') throw new Error(`Navidrome error: ${result.error?.message || 'Unknown error'}`)
    return result
  }

  async createUser(username: string, password: string, email: string): Promise<void> {
    await this.adminRequest('createUser', { username, password, email, ldapAuthenticated: false, adminRole: false, settingsRole: true, downloadRole: false, uploadRole: false, playlistRole: true, coverArtRole: true, commentRole: true, podcastRole: false, streamRole: true, jukeboxRole: false, shareRole: false })
  }
  async updateUserPassword(username: string, newPassword: string): Promise<void> {
    await this.adminRequest('changePassword', { username, password: newPassword })
  }
  async deleteNavidromeUser(username: string): Promise<void> { await this.adminRequest('deleteUser', { username }) }
  async getNavidromeUserId(username: string): Promise<string | null> {
    try { const result = await this.adminRequest<any>('getUser', { username }); return result.user?.id || null } catch { return null }
  }
  async getRandomSongs(params: { size?: number; genre?: string; fromYear?: number; toYear?: number } = {}): Promise<NavidromeTrack[]> {
    const result = await this.adminRequest<any>('getRandomSongs', { size: params.size || 50, ...params })
    return result.randomSongs?.song || []
  }
  async getSongsByGenre(genre: string, count = 50, offset = 0): Promise<NavidromeTrack[]> {
    const result = await this.adminRequest<any>('getSongsByGenre', { genre, count, offset })
    return result.songsByGenre?.song || []
  }
  async getSimilarSongs(trackId: string, count = 10): Promise<NavidromeTrack[]> {
    try { const result = await this.adminRequest<any>('getSimilarSongs2', { id: trackId, count }); return result.similarSongs2?.song || [] } catch { return [] }
  }
  async getTopSongs(artist: string, count = 10): Promise<NavidromeTrack[]> {
    try { const result = await this.adminRequest<any>('getTopSongs', { artist, count }); return result.topSongs?.song || [] } catch { return [] }
  }
  async getSong(trackId: string): Promise<NavidromeTrack | null> {
    try { const result = await this.adminRequest<any>('getSong', { id: trackId }); return result.song || null } catch { return null }
  }
  async getAlbum(albumId: string): Promise<{ album: NavidromeAlbum; songs: NavidromeTrack[] } | null> {
    try { const result = await this.adminRequest<any>('getAlbum', { id: albumId }); const album = result.album; const songs = album?.song || []; delete album?.song; return { album, songs } } catch { return null }
  }
  async getArtist(artistId: string): Promise<{ artist: NavidromeArtist; albums: NavidromeAlbum[] } | null> {
    try { const result = await this.adminRequest<any>('getArtist', { id: artistId }); const artist = result.artist; const albums = artist?.album || []; delete artist?.album; return { artist, albums } } catch { return null }
  }
  async getSimilarArtists(artistId: string, count = 6): Promise<NavidromeArtist[]> {
    try { const result = await this.adminRequest<any>('getSimilarArtists', { id: artistId, count }); return result.similarArtists?.artist || [] } catch { return [] }
  }
  async search(query: string, options: { artistCount?: number; albumCount?: number; songCount?: number; offset?: number } = {}): Promise<NavidromeSearchResult> {
    const result = await this.adminRequest<any>('search3', { query, artistCount: options.artistCount || 5, albumCount: options.albumCount || 5, songCount: options.songCount || 20, artistOffset: options.offset || 0, albumOffset: options.offset || 0, songOffset: options.offset || 0 })
    return { artists: result.searchResult3?.artist || [], albums: result.searchResult3?.album || [], tracks: result.searchResult3?.song || [] }
  }
  async getGenres(): Promise<Array<{ name: string; songCount: number; albumCount: number }>> {
    const result = await this.adminRequest<any>('getGenres'); return result.genres?.genre || []
  }
  async getArtists(): Promise<NavidromeArtist[]> {
    const result = await this.adminRequest<any>('getArtists')
    const indexes = result.artists?.index || []
    const artists: NavidromeArtist[] = []
    for (const index of indexes) artists.push(...(index.artist || []))
    return artists
  }
  async getNewestAlbums(size = 20, offset = 0): Promise<NavidromeAlbum[]> {
    const result = await this.adminRequest<any>('getAlbumList2', { type: 'newest', size, offset }); return result.albumList2?.album || []
  }
  async getMostPlayedAlbums(size = 20): Promise<NavidromeAlbum[]> {
    const result = await this.adminRequest<any>('getAlbumList2', { type: 'frequent', size }); return result.albumList2?.album || []
  }
  async getRecentAlbums(size = 20): Promise<NavidromeAlbum[]> {
    const result = await this.adminRequest<any>('getAlbumList2', { type: 'recent', size }); return result.albumList2?.album || []
  }
  async scrobble(trackId: string, submission = true): Promise<void> {
    try { await this.adminRequest('scrobble', { id: trackId, submission, time: Date.now() }) } catch {}
  }
  getCoverArtUrl(coverArtId: string, size?: number): string {
    const params = new URLSearchParams({ id: coverArtId, v: '1.16.1', c: 'monflow', u: config.NAVIDROME_ADMIN_USER, p: config.NAVIDROME_ADMIN_PASSWORD, ...(size ? { size: String(size) } : {}) })
    return `${config.NAVIDROME_URL}/rest/getCoverArt?${params}`
  }
  async getMusicFolders(): Promise<any[]> { const result = await this.adminRequest<any>('getMusicFolders'); return result.musicFolders?.musicFolder || [] }
  async getNowPlaying(): Promise<any[]> { const result = await this.adminRequest<any>('getNowPlaying'); return result.nowPlaying?.entry || [] }
}

export const navidromeService = new NavidromeService()
