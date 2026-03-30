import { FastifyPluginAsync } from 'fastify'
import { TicketStatus, TicketPriority } from '@prisma/client'
import { sendTicketResponseEmail } from '../../services/email.js'

export const adminTicketRoutes: FastifyPluginAsync = async (fastify) => {
  fastify.get('/', { preHandler: [fastify.authenticateAdmin] }, async (req) => {
    const { status, priority, page, limit } = req.query as { status?: string; priority?: string; page?: string; limit?: string }
    const take = parseInt(limit || '50')
    const skip = (parseInt(page || '1') - 1) * take
    const where: any = {}
    if (status && status !== 'ALL') where.status = status as TicketStatus
    if (priority && priority !== 'ALL') where.priority = priority as TicketPriority
    const [tickets, total] = await Promise.all([
      fastify.prisma.ticket.findMany({
        where,
        include: { user: { select: { email: true, username: true, avatarUrl: true } }, _count: { select: { messages: true } }, messages: { orderBy: { createdAt: 'desc' }, take: 1 } },
        orderBy: [{ priority: 'desc' }, { updatedAt: 'desc' }],
        take, skip,
      }),
      fastify.prisma.ticket.count({ where }),
    ])
    return { tickets, total, page: parseInt(page || '1'), totalPages: Math.ceil(total / take) }
  })

  fastify.get('/:id', { preHandler: [fastify.authenticateAdmin] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const ticket = await fastify.prisma.ticket.findUnique({
      where: { id },
      include: { user: { select: { email: true, username: true, avatarUrl: true, role: true } }, messages: { orderBy: { createdAt: 'asc' }, include: { sender: { select: { username: true, avatarUrl: true, role: true } } } } },
    })
    if (!ticket) return reply.status(404).send({ error: 'Ticket non trouvé' })
    return { ticket }
  })

  fastify.post('/:id/messages', { preHandler: [fastify.authenticateAdmin] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const { message } = req.body as { message: string }
    if (!message?.trim()) return reply.status(400).send({ error: 'Message requis' })
    const ticket = await fastify.prisma.ticket.findUnique({ where: { id }, include: { user: true } })
    if (!ticket) return reply.status(404).send({ error: 'Ticket non trouvé' })
    const msg = await fastify.prisma.ticketMessage.create({ data: { ticketId: id, senderId: req.user.sub, message: message.trim(), isAdmin: true } })
    await fastify.prisma.ticket.update({ where: { id }, data: { status: TicketStatus.PENDING_USER, updatedAt: new Date() } })
    try { await sendTicketResponseEmail(ticket.user.email, ticket.user.username, id, ticket.subject) } catch {}
    return reply.status(201).send({ message: msg })
  })

  fastify.patch('/:id', { preHandler: [fastify.authenticateAdmin] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const { status, priority } = req.body as { status?: TicketStatus; priority?: TicketPriority }
    const data: any = {}
    if (status) data.status = status
    if (priority) data.priority = priority
    if (status === TicketStatus.CLOSED || status === TicketStatus.RESOLVED) data.closedAt = new Date()
    return { ticket: await fastify.prisma.ticket.update({ where: { id }, data }) }
  })
}
