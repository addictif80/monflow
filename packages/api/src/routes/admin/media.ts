import { FastifyPluginAsync } from 'fastify'
import { navidromeService } from '../../services/navidrome.js'
import { fetchLyrics } from '../../services/lyrics.js'
import axios from 'axios'
import { config } from '../../config/index.js'

export const adminMediaRoutes: FastifyPluginAsync = async (fastify) => {
  fastify.get('/search', { preHandler: [fastify.authenticateAdmin] }, async (req, reply) => {
    const { q } = req.query as { q?: string }
    if (!q) return reply.status(400).send({ error: 'Paramètre q requis' })
    return navidromeService.search(q, { artistCount: 5, albumCount: 5, songCount: 30 })
  })

  fastify.get('/track/:id', { preHandler: [fastify.authenticateAdmin] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const track = await navidromeService.getSong(id)
    if (!track) return reply.status(404).send({ error: 'Titre non trouvé' })
    const lyrics = await fastify.prisma.lyricsCache.findUnique({ where: { trackId: id } })
    return { track, lyrics }
  })

  fastify.patch('/lyrics/:trackId', { preHandler: [fastify.authenticateAdmin] }, async (req, reply) => {
    const { trackId } = req.params as { trackId: string }
    const { lyrics, synced, source } = req.body as { lyrics?: string; synced?: Array<{ time: number; text: string }>; source?: string }
    const track = await navidromeService.getSong(trackId)
    if (!track) return reply.status(404).send({ error: 'Titre non trouvé' })
    const updated = await fastify.prisma.lyricsCache.upsert({
      where: { trackId },
      update: { lyrics: lyrics || null, synced: synced as any || null, source: source || 'manual' },
      create: { trackId, trackName: track.title, artist: track.artist, lyrics: lyrics || null, synced: synced as any || null, source: source || 'manual' },
    })
    return { lyrics: updated }
  })

  fastify.post('/lyrics/:trackId/fetch', { preHandler: [fastify.authenticateAdmin] }, async (req, reply) => {
    const { trackId } = req.params as { trackId: string }
    const track = await navidromeService.getSong(trackId)
    if (!track) return reply.status(404).send({ error: 'Titre non trouvé' })
    const result = await fetchLyrics(track.title, track.artist, track.album, track.duration)
    if (!result.lyrics && !result.synced) return reply.status(404).send({ error: 'Lyrics non trouvées' })
    const cached = await fastify.prisma.lyricsCache.upsert({
      where: { trackId },
      update: { lyrics: result.lyrics, synced: result.synced as any, source: result.source },
      create: { trackId, trackName: track.title, artist: track.artist, lyrics: result.lyrics, synced: result.synced as any, source: result.source },
    })
    return { lyrics: cached }
  })

  fastify.get('/duplicates', { preHandler: [fastify.authenticateAdmin] }, async (req) => {
    const { genre } = req.query as { genre?: string }
    const songs = await navidromeService.getRandomSongs({ size: 500, genre })
    const groups = new Map<string, typeof songs>()
    for (const song of songs) {
      const key = `${song.title.toLowerCase().trim()}|${song.artist.toLowerCase().trim()}`
      const group = groups.get(key) || []
      group.push(song)
      groups.set(key, group)
    }
    const duplicates = [...groups.entries()].filter(([, tracks]) => tracks.length > 1).map(([key, tracks]) => ({ key, tracks }))
    return { duplicates, total: duplicates.length }
  })

  fastify.get('/stats', { preHandler: [fastify.authenticateAdmin] }, async () => {
    const [totalLyricsCached, syncedLyricsCount, mostPlayed] = await Promise.all([
      fastify.prisma.lyricsCache.count(),
      fastify.prisma.lyricsCache.count({ where: { synced: { not: null } } }),
      fastify.prisma.listeningHistory.groupBy({ by: ['trackId'], _count: { trackId: true }, orderBy: { _count: { trackId: 'desc' } }, take: 20 }),
    ])
    return { lyricsCached: totalLyricsCached, syncedLyrics: syncedLyricsCount, mostPlayedTrackIds: mostPlayed.map(m => ({ trackId: m.trackId, count: m._count.trackId })) }
  })

  fastify.post('/deemix/search', { preHandler: [fastify.authenticateAdmin] }, async (req, reply) => {
    const { query } = req.body as { query: string }
    if (!query) return reply.status(400).send({ error: 'Query requise' })
    try { return (await axios.get(`${config.DEEMIX_URL}/api/search`, { params: { term: query }, timeout: 10000 })).data }
    catch (err: any) { return reply.status(502).send({ error: 'Deemix non disponible', details: err.message }) }
  })

  fastify.post('/deemix/download', { preHandler: [fastify.authenticateAdmin] }, async (req, reply) => {
    const { url } = req.body as { url: string }
    if (!url) return reply.status(400).send({ error: 'URL Deezer requise' })
    try { return (await axios.post(`${config.DEEMIX_URL}/api/addToQueue`, { url, bitrate: '3' }, { timeout: 10000 })).data }
    catch (err: any) { return reply.status(502).send({ error: 'Deemix non disponible', details: err.message }) }
  })

  fastify.get('/deemix/queue', { preHandler: [fastify.authenticateAdmin] }, async (req, reply) => {
    try { return (await axios.get(`${config.DEEMIX_URL}/api/queue`, { timeout: 5000 })).data }
    catch { return reply.status(502).send({ error: 'Deemix non disponible' }) }
  })
}
