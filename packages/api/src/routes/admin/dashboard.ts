import { FastifyPluginAsync } from 'fastify'
import { UserStatus, SubscriptionStatus, PaymentStatus } from '@prisma/client'

export const adminDashboardRoutes: FastifyPluginAsync = async (fastify) => {
  fastify.get('/', { preHandler: [fastify.authenticateAdmin] }, async () => {
    const now = new Date()
    const thirtyDaysAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000)
    const sevenDaysAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000)

    const [totalUsers, activeUsers, suspendedUsers, deletedUsers, pendingUsers, activeSubscriptions, pastDueSubscriptions, cancelledSubscriptions, monthlyRevenue, yearlyRevenue, recentPayments, failedPayments, openTickets, newUsersThisMonth, newUsersThisWeek] = await Promise.all([
      fastify.prisma.user.count(),
      fastify.prisma.user.count({ where: { status: UserStatus.ACTIVE } }),
      fastify.prisma.user.count({ where: { status: UserStatus.SUSPENDED } }),
      fastify.prisma.user.count({ where: { status: UserStatus.DELETED } }),
      fastify.prisma.user.count({ where: { status: UserStatus.PENDING } }),
      fastify.prisma.subscription.count({ where: { status: SubscriptionStatus.ACTIVE } }),
      fastify.prisma.subscription.count({ where: { status: SubscriptionStatus.PAST_DUE } }),
      fastify.prisma.subscription.count({ where: { status: SubscriptionStatus.CANCELLED } }),
      fastify.prisma.payment.aggregate({ where: { status: PaymentStatus.SUCCEEDED, createdAt: { gte: thirtyDaysAgo } }, _sum: { amount: true } }),
      fastify.prisma.payment.aggregate({ where: { status: PaymentStatus.SUCCEEDED, createdAt: { gte: new Date(now.getFullYear(), 0, 1) } }, _sum: { amount: true } }),
      fastify.prisma.payment.findMany({ where: { createdAt: { gte: sevenDaysAgo } }, include: { user: { select: { email: true, username: true } } }, orderBy: { createdAt: 'desc' }, take: 10 }),
      fastify.prisma.payment.count({ where: { status: PaymentStatus.FAILED, createdAt: { gte: thirtyDaysAgo } } }),
      fastify.prisma.ticket.count({ where: { status: { in: ['OPEN', 'PENDING_ADMIN'] } } }),
      fastify.prisma.user.count({ where: { createdAt: { gte: thirtyDaysAgo } } }),
      fastify.prisma.user.count({ where: { createdAt: { gte: sevenDaysAgo } } }),
    ])

    const gracePeriodDays = parseInt(process.env.PAYMENT_GRACE_PERIOD_DAYS || '7')
    const gracePeriodThreshold = new Date(now.getTime() - gracePeriodDays * 24 * 60 * 60 * 1000)
    const overdueAccounts = await fastify.prisma.subscription.findMany({
      where: { status: SubscriptionStatus.PAST_DUE, pastDueAt: { lte: gracePeriodThreshold } },
      include: { user: { select: { email: true, username: true, status: true } } },
      take: 10,
    })
    const upcomingExpirations = await fastify.prisma.subscription.findMany({
      where: { status: SubscriptionStatus.ACTIVE, cancelAtPeriodEnd: true, currentPeriodEnd: { gte: now, lte: new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000) } },
      include: { user: { select: { email: true, username: true } } },
    })

    return {
      stats: {
        users: { total: totalUsers, active: activeUsers, suspended: suspendedUsers, deleted: deletedUsers, pending: pendingUsers, newThisMonth: newUsersThisMonth, newThisWeek: newUsersThisWeek },
        subscriptions: { active: activeSubscriptions, pastDue: pastDueSubscriptions, cancelled: cancelledSubscriptions },
        revenue: { lastThirtyDays: (monthlyRevenue._sum.amount || 0) / 100, thisYear: (yearlyRevenue._sum.amount || 0) / 100 },
        payments: { recentCount: recentPayments.length, failedCount: failedPayments },
        support: { openTickets },
      },
      recentPayments,
      overdueAccounts,
      upcomingExpirations,
    }
  })
}
