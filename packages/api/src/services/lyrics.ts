import axios from 'axios'
import { config } from '../config/index.js'

interface SyncedLyricLine { time: number; text: string }
interface LyricsResult { lyrics: string | null; synced: SyncedLyricLine[] | null; source: string }

async function fetchFromLrclib(trackName: string, artistName: string, albumName?: string, duration?: number): Promise<LyricsResult | null> {
  try {
    const params: Record<string, string | number> = { track_name: trackName, artist_name: artistName }
    if (albumName) params.album_name = albumName
    if (duration) params.duration = duration
    const response = await axios.get('https://lrclib.net/api/get', { params, timeout: 5000 })
    if (response.status === 200 && response.data) {
      return { lyrics: response.data.plainLyrics || null, synced: response.data.syncedLyrics ? parseLrc(response.data.syncedLyrics) : null, source: 'lrclib' }
    }
    return null
  } catch { return null }
}

async function fetchFromGenius(trackName: string, artistName: string): Promise<LyricsResult | null> {
  if (!config.GENIUS_ACCESS_TOKEN) return null
  try {
    const searchResponse = await axios.get('https://api.genius.com/search', {
      params: { q: `${trackName} ${artistName}` },
      headers: { Authorization: `Bearer ${config.GENIUS_ACCESS_TOKEN}` },
      timeout: 5000,
    })
    const hits = searchResponse.data?.response?.hits || []
    if (hits.length === 0) return null
    return { lyrics: null, synced: null, source: 'genius' }
  } catch { return null }
}

function parseLrc(lrcText: string): SyncedLyricLine[] {
  const lines = lrcText.split('\n')
  const result: SyncedLyricLine[] = []
  const timeRegex = /\[(\d{2}):(\d{2})\.(\d{2,3})\]/g
  for (const line of lines) {
    const matches = [...line.matchAll(timeRegex)]
    if (matches.length === 0) continue
    const text = line.replace(timeRegex, '').trim()
    if (!text) continue
    for (const match of matches) {
      const time = (parseInt(match[1]) * 60 + parseInt(match[2])) * 1000 + parseInt(match[3].padEnd(3, '0'))
      result.push({ time, text })
    }
  }
  return result.sort((a, b) => a.time - b.time)
}

export async function fetchLyrics(trackName: string, artistName: string, albumName?: string, duration?: number): Promise<LyricsResult> {
  const lrclib = await fetchFromLrclib(trackName, artistName, albumName, duration)
  if (lrclib && (lrclib.lyrics || lrclib.synced)) return lrclib
  const genius = await fetchFromGenius(trackName, artistName)
  if (genius) return genius
  return { lyrics: null, synced: null, source: 'none' }
}
