import { FastifyPluginAsync } from 'fastify'
import { PaymentStatus } from '@prisma/client'

export const adminPaymentsRoutes: FastifyPluginAsync = async (fastify) => {
  fastify.get('/', { preHandler: [fastify.authenticateAdmin] }, async (request) => {
    const { page = '1', limit = '20', status } = request.query as any
    const pageNum = parseInt(page)
    const limitNum = parseInt(limit)
    const skip = (pageNum - 1) * limitNum
    const where: any = {}
    if (status) where.status = status as PaymentStatus
    const [payments, total] = await Promise.all([
      fastify.prisma.payment.findMany({ where, include: { user: { select: { email: true, username: true } } }, orderBy: { createdAt: 'desc' }, skip, take: limitNum }),
      fastify.prisma.payment.count({ where }),
    ])
    return { payments, total, page: pageNum, limit: limitNum }
  })
}
