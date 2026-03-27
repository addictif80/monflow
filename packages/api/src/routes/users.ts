import { FastifyPluginAsync } from 'fastify'
import { z } from 'zod'
import { hashPassword, verifyPassword, isPasswordStrong } from '../utils/auth.js'
import { navidromeService } from '../services/navidrome.js'

export const userRoutes: FastifyPluginAsync = async (fastify) => {
  // GET /api/users/me
  fastify.get('/me', { preHandler: [fastify.authenticate] }, async (req) => {
    const user = await fastify.prisma.user.findUnique({
      where: { id: req.user.sub },
      include: {
        subscription: true,
        settings: true,
      },
    })

    if (!user) throw new Error('Utilisateur non trouvé')

    return {
      id: user.id,
      email: user.email,
      username: user.username,
      role: user.role,
      status: user.status,
      avatarUrl: user.avatarUrl,
      emailVerified: user.emailVerified,
      createdAt: user.createdAt,
      lastLoginAt: user.lastLoginAt,
      subscription: user.subscription
        ? {
            plan: user.subscription.plan,
            status: user.subscription.status,
            currentPeriodEnd: user.subscription.currentPeriodEnd,
            cancelAtPeriodEnd: user.subscription.cancelAtPeriodEnd,
          }
        : null,
      settings: user.settings?.settings || {},
    }
  })

  // GET /api/users/me/credentials
  fastify.get('/me/credentials', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const user = await fastify.prisma.user.findUnique({
      where: { id: req.user.sub },
    })
    if (!user) return reply.status(404).send({ error: 'Utilisateur non trouvé' })

    return {
      navidromeUrl: process.env.NAVIDROME_URL?.replace('navidrome', 'monflow.fr') || '',
      username: user.username,
    }
  })

  // PATCH /api/users/me
  fastify.patch('/me', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const schema = z.object({
      username: z.string().min(3).max(30).regex(/^[a-zA-Z0-9_-]+$/).optional(),
      avatarUrl: z.string().url().optional(),
    })

    const parsed = schema.safeParse(req.body)
    if (!parsed.success) {
      return reply.status(400).send({ error: parsed.error.errors[0].message })
    }

    const { username } = parsed.data

    if (username) {
      const existing = await fastify.prisma.user.findUnique({ where: { username } })
      if (existing && existing.id !== req.user.sub) {
        return reply.status(409).send({ error: 'Nom d\'utilisateur déjà utilisé' })
      }
    }

    const updated = await fastify.prisma.user.update({
      where: { id: req.user.sub },
      data: parsed.data,
    })

    return {
      id: updated.id,
      email: updated.email,
      username: updated.username,
      avatarUrl: updated.avatarUrl,
    }
  })

  // PATCH /api/users/me/password
  fastify.patch('/me/password', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const schema = z.object({
      currentPassword: z.string(),
      newPassword: z.string().min(8),
    })

    const parsed = schema.safeParse(req.body)
    if (!parsed.success) {
      return reply.status(400).send({ error: parsed.error.errors[0].message })
    }

    const { currentPassword, newPassword } = parsed.data

    if (!isPasswordStrong(newPassword)) {
      return reply.status(400).send({
        error: 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre',
      })
    }

    const user = await fastify.prisma.user.findUnique({ where: { id: req.user.sub } })
    if (!user) return reply.status(404).send({ error: 'Utilisateur non trouvé' })

    const valid = await verifyPassword(currentPassword, user.passwordHash)
    if (!valid) {
      return reply.status(401).send({ error: 'Mot de passe actuel incorrect' })
    }

    const passwordHash = await hashPassword(newPassword)
    await fastify.prisma.user.update({
      where: { id: user.id },
      data: { passwordHash },
    })

    // Mettre à jour dans Navidrome
    try {
      await navidromeService.updateUserPassword(user.username, newPassword)
    } catch (err) {
      fastify.log.error('Navidrome password update failed:', err)
    }

    return { message: 'Mot de passe mis à jour' }
  })

  // PATCH /api/users/me/settings
  fastify.patch('/me/settings', { preHandler: [fastify.authenticate] }, async (req) => {
    const settings = req.body as Record<string, any>

    await fastify.prisma.userSettings.upsert({
      where: { userId: req.user.sub },
      update: { settings },
      create: { userId: req.user.sub, settings },
    })

    return { message: 'Paramètres mis à jour' }
  })

  // GET /api/users/me/stats
  fastify.get('/me/stats', { preHandler: [fastify.authenticate] }, async (req) => {
    const [playCount, favoriteCount, playlistCount, totalListeningTime] = await Promise.all([
      fastify.prisma.listeningHistory.count({ where: { userId: req.user.sub } }),
      fastify.prisma.favorite.count({ where: { userId: req.user.sub } }),
      fastify.prisma.playlist.count({ where: { userId: req.user.sub } }),
      fastify.prisma.listeningHistory.aggregate({
        where: { userId: req.user.sub },
        _sum: { duration: true },
      }),
    ])

    return {
      playCount,
      favoriteCount,
      playlistCount,
      totalListeningMinutes: Math.floor((totalListeningTime._sum.duration || 0) / 60),
    }
  })
}
