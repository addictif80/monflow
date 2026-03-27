import fp from 'fastify-plugin'
import { FastifyPluginAsync, FastifyRequest, FastifyReply } from 'fastify'
import fastifyJwt from '@fastify/jwt'
import { config } from '../config/index.js'
import { UserRole, UserStatus } from '@prisma/client'

export interface JWTPayload {
  sub: string
  email: string
  role: UserRole
  status: UserStatus
}

declare module 'fastify' {
  interface FastifyInstance {
    authenticate: (req: FastifyRequest, reply: FastifyReply) => Promise<void>
    authenticateAdmin: (req: FastifyRequest, reply: FastifyReply) => Promise<void>
  }
  interface FastifyRequest {
    user: JWTPayload
  }
}

const authPlugin: FastifyPluginAsync = async (fastify) => {
  fastify.register(fastifyJwt, {
    secret: config.JWT_SECRET,
    sign: { expiresIn: config.JWT_EXPIRES_IN },
  })

  fastify.decorate('authenticate', async (req: FastifyRequest, reply: FastifyReply) => {
    try {
      await req.jwtVerify()
      const payload = req.user as JWTPayload

      // Vérifier que le compte est actif
      if (payload.status === UserStatus.DELETED) {
        return reply.status(403).send({ error: 'Compte supprimé' })
      }
      if (payload.status === UserStatus.SUSPENDED) {
        return reply.status(403).send({
          error: 'Compte suspendu',
          code: 'ACCOUNT_SUSPENDED',
        })
      }
    } catch (err) {
      return reply.status(401).send({ error: 'Non authentifié' })
    }
  })

  fastify.decorate('authenticateAdmin', async (req: FastifyRequest, reply: FastifyReply) => {
    try {
      await req.jwtVerify()
      const payload = req.user as JWTPayload

      if (payload.role !== UserRole.ADMIN) {
        return reply.status(403).send({ error: 'Accès refusé' })
      }
    } catch (err) {
      return reply.status(401).send({ error: 'Non authentifié' })
    }
  })
}

export default fp(authPlugin, { name: 'auth' })
