import { FastifyPluginAsync } from 'fastify'
import { UserStatus } from '@prisma/client'
import { hashPassword, generateTempPassword } from '../../utils/auth.js'
import { navidromeService } from '../../services/navidrome.js'
import { sendPasswordResetByAdminEmail, sendAccountSuspendedEmail } from '../../services/email.js'

export const adminUserRoutes: FastifyPluginAsync = async (fastify) => {
  fastify.get('/', { preHandler: [fastify.authenticateAdmin] }, async (req) => {
    const { status, search, page, limit, sort } = req.query as { status?: string; search?: string; page?: string; limit?: string; sort?: string }
    const take = Math.min(parseInt(limit || '50'), 100)
    const skip = (parseInt(page || '1') - 1) * take
    const where: any = {}
    if (status && status !== 'ALL') where.status = status as UserStatus
    if (search) where.OR = [{ email: { contains: search, mode: 'insensitive' } }, { username: { contains: search, mode: 'insensitive' } }]
    const orderBy: any = sort === 'oldest' ? { createdAt: 'asc' } : sort === 'email' ? { email: 'asc' } : { createdAt: 'desc' }
    const [users, total] = await Promise.all([
      fastify.prisma.user.findMany({
        where,
        include: { subscription: { select: { plan: true, status: true, currentPeriodEnd: true, pastDueAt: true } }, _count: { select: { payments: true, tickets: true } } },
        orderBy, take, skip,
      }),
      fastify.prisma.user.count({ where }),
    ])
    return { users: users.map(u => ({ id: u.id, email: u.email, username: u.username, role: u.role, status: u.status, createdAt: u.createdAt, lastLoginAt: u.lastLoginAt, suspendedAt: u.suspendedAt, deletedAt: u.deletedAt, subscription: u.subscription, paymentCount: u._count.payments, ticketCount: u._count.tickets })), total, page: parseInt(page || '1'), totalPages: Math.ceil(total / take) }
  })

  fastify.get('/:id', { preHandler: [fastify.authenticateAdmin] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const user = await fastify.prisma.user.findUnique({ where: { id }, include: { subscription: true, payments: { orderBy: { createdAt: 'desc' }, take: 10 }, tickets: { orderBy: { createdAt: 'desc' }, take: 5, include: { _count: { select: { messages: true } } } }, _count: { select: { favorites: true, playlists: true, listeningHistory: true } } } })
    if (!user) return reply.status(404).send({ error: 'Utilisateur non trouvé' })
    return { user }
  })

  fastify.post('/:id/suspend', { preHandler: [fastify.authenticateAdmin] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const { reason } = req.body as { reason?: string }
    const user = await fastify.prisma.user.findUnique({ where: { id } })
    if (!user) return reply.status(404).send({ error: 'Utilisateur non trouvé' })
    if (user.role === 'ADMIN') return reply.status(403).send({ error: 'Impossible de suspendre un admin' })
    await fastify.prisma.user.update({ where: { id }, data: { status: UserStatus.SUSPENDED, suspendedAt: new Date() } })
    await fastify.prisma.auditLog.create({ data: { adminId: req.user.sub, action: 'USER_SUSPENDED', targetUserId: id, details: { reason: reason || 'Suspension manuelle', userEmail: user.email }, ipAddress: req.ip } })
    try { await sendAccountSuspendedEmail(user.email, user.username) } catch {}
    return { message: 'Compte suspendu' }
  })

  fastify.post('/:id/reactivate', { preHandler: [fastify.authenticateAdmin] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const user = await fastify.prisma.user.findUnique({ where: { id } })
    if (!user) return reply.status(404).send({ error: 'Utilisateur non trouvé' })
    await fastify.prisma.user.update({ where: { id }, data: { status: UserStatus.ACTIVE, suspendedAt: null } })
    await fastify.prisma.auditLog.create({ data: { adminId: req.user.sub, action: 'USER_REACTIVATED', targetUserId: id, details: { userEmail: user.email }, ipAddress: req.ip } })
    return { message: 'Compte réactivé' }
  })

  fastify.delete('/:id', { preHandler: [fastify.authenticateAdmin] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const { permanent } = req.query as { permanent?: string }
    const user = await fastify.prisma.user.findUnique({ where: { id } })
    if (!user) return reply.status(404).send({ error: 'Utilisateur non trouvé' })
    if (user.role === 'ADMIN') return reply.status(403).send({ error: 'Impossible de supprimer un admin' })
    if (permanent === 'true') {
      await fastify.prisma.user.delete({ where: { id } })
      try { await navidromeService.deleteNavidromeUser(user.username) } catch {}
      await fastify.prisma.auditLog.create({ data: { adminId: req.user.sub, action: 'USER_DELETED_PERMANENT', targetUserId: null, details: { userEmail: user.email, username: user.username }, ipAddress: req.ip } })
      return { message: 'Compte supprimé définitivement' }
    } else {
      await fastify.prisma.user.update({ where: { id }, data: { status: UserStatus.DELETED, deletedAt: new Date(), email: `deleted_${Date.now()}_${user.email}` } })
      await fastify.prisma.auditLog.create({ data: { adminId: req.user.sub, action: 'USER_DELETED', targetUserId: id, details: { userEmail: user.email }, ipAddress: req.ip } })
      return { message: 'Compte supprimé' }
    }
  })

  fastify.post('/:id/reset-password', { preHandler: [fastify.authenticateAdmin] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const user = await fastify.prisma.user.findUnique({ where: { id } })
    if (!user) return reply.status(404).send({ error: 'Utilisateur non trouvé' })
    const tempPassword = generateTempPassword()
    const passwordHash = await hashPassword(tempPassword)
    await fastify.prisma.user.update({ where: { id }, data: { passwordHash } })
    try { await navidromeService.updateUserPassword(user.username, tempPassword) } catch {}
    await fastify.prisma.session.deleteMany({ where: { userId: id } })
    try { await sendPasswordResetByAdminEmail(user.email, user.username, tempPassword) } catch {}
    await fastify.prisma.auditLog.create({ data: { adminId: req.user.sub, action: 'USER_PASSWORD_RESET', targetUserId: id, details: { userEmail: user.email }, ipAddress: req.ip } })
    return { message: 'Mot de passe réinitialisé et envoyé par email' }
  })

  fastify.get('/audit-logs', { preHandler: [fastify.authenticateAdmin] }, async (req) => {
    const { page, limit, userId } = req.query as { page?: string; limit?: string; userId?: string }
    const take = parseInt(limit || '50')
    const skip = (parseInt(page || '1') - 1) * take
    const logs = await fastify.prisma.auditLog.findMany({
      where: userId ? { targetUserId: userId } : {},
      include: { admin: { select: { username: true } }, targetUser: { select: { username: true, email: true } } },
      orderBy: { createdAt: 'desc' }, take, skip,
    })
    return { logs }
  })
}
