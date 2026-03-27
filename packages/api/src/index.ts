import Fastify from 'fastify'
import fastifyCors from '@fastify/cors'
import fastifyHelmet from '@fastify/helmet'

import { config } from './config/index.js'
import prismaPlugin from './plugins/prisma.js'
import authPlugin from './plugins/auth.js'

import { authRoutes } from './routes/auth.js'
import { userRoutes } from './routes/users.js'
import { subscriptionRoutes, stripeWebhookRoute } from './routes/subscriptions.js'
import { playerRoutes } from './routes/player.js'
import { playlistRoutes } from './routes/playlists.js'
import { favoriteRoutes } from './routes/favorites.js'
import { ticketRoutes } from './routes/tickets.js'

import { adminDashboardRoutes } from './routes/admin/dashboard.js'
import { adminUserRoutes } from './routes/admin/users.js'
import { adminTicketRoutes } from './routes/admin/tickets.js'
import { adminMediaRoutes } from './routes/admin/media.js'
import { adminPaymentsRoutes } from './routes/admin/payments.js'

// ─────────────────────────────────────────────────────────────
// Fastify instance
// ─────────────────────────────────────────────────────────────

const fastify = Fastify({
  logger: {
    level: config.NODE_ENV === 'production' ? 'info' : 'debug',
    transport:
      config.NODE_ENV !== 'production'
        ? { target: 'pino-pretty', options: { colorize: true } }
        : undefined,
  },
})

// ─────────────────────────────────────────────────────────────
// Plugins
// ─────────────────────────────────────────────────────────────

// Stripe webhook nécessite le body raw AVANT le parser JSON
fastify.register(stripeWebhookRoute)

fastify.register(fastifyCors, {
  origin: [
    config.NEXT_PUBLIC_APP_URL,
    'http://localhost:3000',
    'http://localhost:3001',
  ],
  credentials: true,
  methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
})

fastify.register(fastifyHelmet, {
  contentSecurityPolicy: false,
})

fastify.register(prismaPlugin)
fastify.register(authPlugin)

// ─────────────────────────────────────────────────────────────
// Routes publiques
// ─────────────────────────────────────────────────────────────

fastify.register(authRoutes, { prefix: '/api/auth' })

// ─────────────────────────────────────────────────────────────
// Routes utilisateurs (authentifiées)
// ─────────────────────────────────────────────────────────────

fastify.register(userRoutes, { prefix: '/api/users' })
fastify.register(subscriptionRoutes, { prefix: '/api/subscriptions' })
fastify.register(playerRoutes, { prefix: '/api/player' })
fastify.register(playlistRoutes, { prefix: '/api/playlists' })
fastify.register(favoriteRoutes, { prefix: '/api/favorites' })
fastify.register(ticketRoutes, { prefix: '/api/tickets' })

// ─────────────────────────────────────────────────────────────
// Routes admin
// ─────────────────────────────────────────────────────────────

fastify.register(adminDashboardRoutes, { prefix: '/api/admin' })
fastify.register(adminUserRoutes, { prefix: '/api/admin/users' })
fastify.register(adminTicketRoutes, { prefix: '/api/admin/tickets' })
fastify.register(adminMediaRoutes, { prefix: '/api/admin/media' })
fastify.register(adminPaymentsRoutes, { prefix: '/api/admin/payments' })

// ─────────────────────────────────────────────────────────────
// Health check
// ─────────────────────────────────────────────────────────────

fastify.get('/health', async () => ({
  status: 'ok',
  timestamp: new Date().toISOString(),
  version: '1.0.0',
}))

// ─────────────────────────────────────────────────────────────
// Démarrage
// ─────────────────────────────────────────────────────────────

const start = async () => {
  try {
    await fastify.listen({ port: config.API_PORT, host: '0.0.0.0' })
    fastify.log.info(`🎵 MonFlow API démarrée sur le port ${config.API_PORT}`)
  } catch (err) {
    fastify.log.error(err)
    process.exit(1)
  }
}

start()

export default fastify
