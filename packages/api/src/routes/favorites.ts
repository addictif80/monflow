import { FastifyPluginAsync } from 'fastify'

export const favoriteRoutes: FastifyPluginAsync = async (fastify) => {
  // GET /api/favorites
  fastify.get('/', { preHandler: [fastify.authenticate] }, async (req) => {
    const { page, limit } = req.query as { page?: string; limit?: string }
    const take = parseInt(limit || '50')
    const skip = (parseInt(page || '1') - 1) * take

    const [favorites, total] = await Promise.all([
      fastify.prisma.favorite.findMany({
        where: { userId: req.user.sub },
        orderBy: { addedAt: 'desc' },
        take,
        skip,
      }),
      fastify.prisma.favorite.count({ where: { userId: req.user.sub } }),
    ])

    return {
      favorites,
      total,
      page: parseInt(page || '1'),
      totalPages: Math.ceil(total / take),
    }
  })

  // POST /api/favorites
  fastify.post('/', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const { trackId } = req.body as { trackId: string }
    if (!trackId) return reply.status(400).send({ error: 'trackId requis' })

    const existing = await fastify.prisma.favorite.findUnique({
      where: { userId_trackId: { userId: req.user.sub, trackId } },
    })

    if (existing) return reply.status(409).send({ error: 'Déjà dans les favoris' })

    await fastify.prisma.favorite.create({
      data: { userId: req.user.sub, trackId },
    })

    return reply.status(201).send({ message: 'Ajouté aux favoris' })
  })

  // DELETE /api/favorites/:trackId
  fastify.delete('/:trackId', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const { trackId } = req.params as { trackId: string }

    const deleted = await fastify.prisma.favorite.deleteMany({
      where: { userId: req.user.sub, trackId },
    })

    if (deleted.count === 0) return reply.status(404).send({ error: 'Non trouvé dans les favoris' })

    return { message: 'Retiré des favoris' }
  })

  // GET /api/favorites/check/:trackId
  fastify.get('/check/:trackId', { preHandler: [fastify.authenticate] }, async (req) => {
    const { trackId } = req.params as { trackId: string }
    const fav = await fastify.prisma.favorite.findUnique({
      where: { userId_trackId: { userId: req.user.sub, trackId } },
    })
    return { isFavorite: !!fav }
  })
}
