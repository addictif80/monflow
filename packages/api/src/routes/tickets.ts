import { FastifyPluginAsync } from 'fastify'
import { z } from 'zod'
import { TicketStatus } from '@prisma/client'
import { sendTicketResponseEmail } from '../services/email.js'

export const ticketRoutes: FastifyPluginAsync = async (fastify) => {
  fastify.get('/', { preHandler: [fastify.authenticate] }, async (req) => {
    const tickets = await fastify.prisma.ticket.findMany({
      where: { userId: req.user.sub },
      include: { _count: { select: { messages: true } }, messages: { orderBy: { createdAt: 'desc' }, take: 1 } },
      orderBy: { updatedAt: 'desc' },
    })
    return { tickets }
  })

  fastify.post('/', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const schema = z.object({
      subject: z.string().min(5).max(150),
      message: z.string().min(20).max(5000),
      priority: z.enum(['LOW', 'MEDIUM', 'HIGH']).default('MEDIUM'),
    })
    const parsed = schema.safeParse(req.body)
    if (!parsed.success) return reply.status(400).send({ error: parsed.error.errors[0].message })
    const ticket = await fastify.prisma.ticket.create({
      data: {
        userId: req.user.sub,
        subject: parsed.data.subject,
        priority: parsed.data.priority,
        messages: { create: { senderId: req.user.sub, message: parsed.data.message, isAdmin: false } },
      },
      include: { messages: true },
    })
    return reply.status(201).send({ ticket })
  })

  fastify.get('/:id', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const ticket = await fastify.prisma.ticket.findUnique({
      where: { id },
      include: { messages: { orderBy: { createdAt: 'asc' }, include: { sender: { select: { username: true, avatarUrl: true, role: true } } } } },
    })
    if (!ticket) return reply.status(404).send({ error: 'Ticket non trouvé' })
    if (ticket.userId !== req.user.sub) return reply.status(403).send({ error: 'Accès refusé' })
    return { ticket }
  })

  fastify.post('/:id/messages', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const { message } = req.body as { message: string }
    if (!message || message.trim().length < 5) return reply.status(400).send({ error: 'Message trop court' })
    const ticket = await fastify.prisma.ticket.findUnique({ where: { id } })
    if (!ticket) return reply.status(404).send({ error: 'Ticket non trouvé' })
    if (ticket.userId !== req.user.sub) return reply.status(403).send({ error: 'Accès refusé' })
    if (ticket.status === TicketStatus.CLOSED) return reply.status(400).send({ error: 'Ce ticket est fermé' })
    const msg = await fastify.prisma.ticketMessage.create({
      data: { ticketId: id, senderId: req.user.sub, message: message.trim(), isAdmin: false },
    })
    await fastify.prisma.ticket.update({ where: { id }, data: { status: TicketStatus.PENDING_ADMIN, updatedAt: new Date() } })
    return reply.status(201).send({ message: msg })
  })

  fastify.patch('/:id/close', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const { id } = req.params as { id: string }
    const ticket = await fastify.prisma.ticket.findUnique({ where: { id } })
    if (!ticket) return reply.status(404).send({ error: 'Ticket non trouvé' })
    if (ticket.userId !== req.user.sub) return reply.status(403).send({ error: 'Accès refusé' })
    await fastify.prisma.ticket.update({ where: { id }, data: { status: TicketStatus.CLOSED, closedAt: new Date() } })
    return { message: 'Ticket fermé' }
  })
}
