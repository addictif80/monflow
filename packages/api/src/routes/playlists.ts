import { FastifyPluginAsync } from 'fastify'
import { z } from 'zod'

export const playlistRoutes: FastifyPluginAsync = async (fastify) => {
  fastify.get('/', { preHandler: [fastify.authenticate] }, async (req) => {
    const { includePublic } = req.query as { includePublic?: string }
    const where = includePublic === 'true'
      ? { OR: [{ userId: req.user.sub }, { isPublic: true }] }
      : { userId: req.user.sub }
    const playlists = await fastify.prisma.playlist.findMany({
      where,
      include: { _count: { select: { tracks: true } }, user: { select: { username: true } } },
      orderBy: { updatedAt: 'desc' },
    })
    return { playlists }
  })

  fastify.post('/', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const schema = z.object({
      name: z.string().min(1).max(100),
      description: z.string().max(500).optional(),
      isPublic: z.boolean().default(false),
    })
    const parsed = schema.safeParse(req.body)
    if (!parsed.success) return reply.status(400).send({ error: parsed.error.errors[0].message })
    const playlist = await fastify.prisma.playlist.create({
      data: { userId: req.user.sub, ...parsed.data },
    })
    return reply.status(201).send({ playlist })
  })

  fastify.get('/:id', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const playlist = await fastify.prisma.playlist.findUnique({
      where: { id },
      include: { tracks: { orderBy: { position: 'asc' } }, user: { select: { username: true, avatarUrl: true } } },
    })
    if (!playlist) return reply.status(404).send({ error: 'Playlist non trouvée' })
    if (!playlist.isPublic && playlist.userId !== req.user.sub) return reply.status(403).send({ error: 'Accès refusé' })
    return { playlist }
  })

  fastify.patch('/:id', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const schema = z.object({
      name: z.string().min(1).max(100).optional(),
      description: z.string().max(500).nullable().optional(),
      isPublic: z.boolean().optional(),
      coverUrl: z.string().url().nullable().optional(),
    })
    const parsed = schema.safeParse(req.body)
    if (!parsed.success) return reply.status(400).send({ error: parsed.error.errors[0].message })
    const playlist = await fastify.prisma.playlist.findUnique({ where: { id } })
    if (!playlist) return reply.status(404).send({ error: 'Playlist non trouvée' })
    if (playlist.userId !== req.user.sub) return reply.status(403).send({ error: 'Accès refusé' })
    const updated = await fastify.prisma.playlist.update({ where: { id }, data: parsed.data })
    return { playlist: updated }
  })

  fastify.delete('/:id', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const playlist = await fastify.prisma.playlist.findUnique({ where: { id } })
    if (!playlist) return reply.status(404).send({ error: 'Playlist non trouvée' })
    if (playlist.userId !== req.user.sub) return reply.status(403).send({ error: 'Accès refusé' })
    await fastify.prisma.playlist.delete({ where: { id } })
    return { message: 'Playlist supprimée' }
  })

  fastify.post('/:id/tracks', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const { trackId } = req.body as { trackId: string }
    if (!trackId) return reply.status(400).send({ error: 'trackId requis' })
    const playlist = await fastify.prisma.playlist.findUnique({
      where: { id }, include: { _count: { select: { tracks: true } } },
    })
    if (!playlist) return reply.status(404).send({ error: 'Playlist non trouvée' })
    if (playlist.userId !== req.user.sub) return reply.status(403).send({ error: 'Accès refusé' })
    const existing = await fastify.prisma.playlistTrack.findUnique({
      where: { playlistId_trackId: { playlistId: id, trackId } },
    })
    if (existing) return reply.status(409).send({ error: 'Titre déjà dans la playlist' })
    await fastify.prisma.playlistTrack.create({
      data: { playlistId: id, trackId, position: playlist._count.tracks },
    })
    await fastify.prisma.playlist.update({ where: { id }, data: { updatedAt: new Date() } })
    return reply.status(201).send({ message: 'Titre ajouté' })
  })

  fastify.delete('/:id/tracks/:trackId', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const { id, trackId } = req.params as { id: string; trackId: string }
    const playlist = await fastify.prisma.playlist.findUnique({ where: { id } })
    if (!playlist) return reply.status(404).send({ error: 'Playlist non trouvée' })
    if (playlist.userId !== req.user.sub) return reply.status(403).send({ error: 'Accès refusé' })
    await fastify.prisma.playlistTrack.deleteMany({ where: { playlistId: id, trackId } })
    const remaining = await fastify.prisma.playlistTrack.findMany({
      where: { playlistId: id }, orderBy: { position: 'asc' },
    })
    await Promise.all(remaining.map((track, index) =>
      fastify.prisma.playlistTrack.update({ where: { id: track.id }, data: { position: index } })
    ))
    return { message: 'Titre supprimé de la playlist' }
  })

  fastify.put('/:id/tracks/reorder', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const { trackIds } = req.body as { trackIds: string[] }
    if (!Array.isArray(trackIds)) return reply.status(400).send({ error: 'trackIds doit être un tableau' })
    const playlist = await fastify.prisma.playlist.findUnique({ where: { id } })
    if (!playlist) return reply.status(404).send({ error: 'Playlist non trouvée' })
    if (playlist.userId !== req.user.sub) return reply.status(403).send({ error: 'Accès refusé' })
    await Promise.all(trackIds.map((trackId, index) =>
      fastify.prisma.playlistTrack.updateMany({ where: { playlistId: id, trackId }, data: { position: index } })
    ))
    return { message: 'Ordre mis à jour' }
  })
}
