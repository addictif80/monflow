import { FastifyPluginAsync } from 'fastify'
import { z } from 'zod'
import { UserStatus } from '@prisma/client'
import { hashPassword, verifyPassword, generateToken, isPasswordStrong } from '../utils/auth.js'
import { navidromeService } from '../services/navidrome.js'
import { sendWelcomeEmail, sendPasswordResetEmail } from '../services/email.js'
import { config } from '../config/index.js'

const registerSchema = z.object({
  email: z.string().email('Email invalide'),
  username: z.string().min(3, 'Minimum 3 caractères').max(30).regex(/^[a-zA-Z0-9_-]+$/, 'Caractères invalides'),
  password: z.string().min(8, 'Minimum 8 caractères'),
})

const loginSchema = z.object({
  email: z.string().email(),
  password: z.string(),
})

export const authRoutes: FastifyPluginAsync = async (fastify) => {
  // POST /api/auth/register
  fastify.post('/register', async (req, reply) => {
    const parsed = registerSchema.safeParse(req.body)
    if (!parsed.success) {
      return reply.status(400).send({ error: parsed.error.errors[0].message })
    }

    const { email, username, password } = parsed.data

    if (!isPasswordStrong(password)) {
      return reply.status(400).send({
        error: 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre',
      })
    }

    // Vérifier si l'email ou username existe déjà
    const existing = await fastify.prisma.user.findFirst({
      where: { OR: [{ email }, { username }] },
    })
    if (existing) {
      const field = existing.email === email ? 'Email' : 'Nom d\'utilisateur'
      return reply.status(409).send({ error: `${field} déjà utilisé` })
    }

    const passwordHash = await hashPassword(password)
    const verifyToken = generateToken()

    // Créer l'utilisateur en DB
    const user = await fastify.prisma.user.create({
      data: {
        email,
        username,
        passwordHash,
        emailVerifyToken: verifyToken,
        status: UserStatus.PENDING,
        settings: { create: {} },
      },
    })

    // Créer l'utilisateur dans Navidrome
    try {
      await navidromeService.createUser(username, password, email)
      const navidromeUserId = await navidromeService.getNavidromeUserId(username)
      if (navidromeUserId) {
        await fastify.prisma.user.update({
          where: { id: user.id },
          data: { navidromeUserId },
        })
      }
    } catch (err) {
      fastify.log.error('Navidrome user creation failed:', err)
    }

    // Envoyer email de bienvenue
    try {
      await sendWelcomeEmail(email, username, verifyToken)
    } catch (err) {
      fastify.log.error('Welcome email failed:', err)
    }

    return reply.status(201).send({
      message: 'Compte créé. Vérifiez votre email pour activer votre compte.',
    })
  })

  // POST /api/auth/verify-email
  fastify.post('/verify-email', async (req, reply) => {
    const { token } = req.body as { token: string }
    if (!token) return reply.status(400).send({ error: 'Token manquant' })

    const user = await fastify.prisma.user.findFirst({
      where: { emailVerifyToken: token },
    })
    if (!user) return reply.status(400).send({ error: 'Token invalide' })

    await fastify.prisma.user.update({
      where: { id: user.id },
      data: {
        emailVerified: true,
        emailVerifyToken: null,
        status: UserStatus.ACTIVE,
      },
    })

    return { message: 'Email confirmé. Vous pouvez vous connecter.' }
  })

  // POST /api/auth/login
  fastify.post('/login', async (req, reply) => {
    const parsed = loginSchema.safeParse(req.body)
    if (!parsed.success) {
      return reply.status(400).send({ error: 'Données invalides' })
    }

    const { email, password } = parsed.data

    const user = await fastify.prisma.user.findUnique({ where: { email } })
    if (!user) {
      return reply.status(401).send({ error: 'Email ou mot de passe incorrect' })
    }

    const valid = await verifyPassword(password, user.passwordHash)
    if (!valid) {
      return reply.status(401).send({ error: 'Email ou mot de passe incorrect' })
    }

    if (user.status === UserStatus.PENDING) {
      return reply.status(403).send({ error: 'Vérifiez votre email avant de vous connecter' })
    }
    if (user.status === UserStatus.DELETED) {
      return reply.status(403).send({ error: 'Ce compte a été supprimé' })
    }

    const payload = {
      sub: user.id,
      email: user.email,
      role: user.role,
      status: user.status,
    }

    const accessToken = fastify.jwt.sign(payload, { expiresIn: config.JWT_EXPIRES_IN })
    const refreshToken = generateToken()

    // Sauvegarder le refresh token
    await fastify.prisma.session.create({
      data: {
        userId: user.id,
        token: refreshToken,
        userAgent: req.headers['user-agent'] || null,
        ipAddress: req.ip,
        expiresAt: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000),
      },
    })

    // Mettre à jour la dernière connexion
    await fastify.prisma.user.update({
      where: { id: user.id },
      data: { lastLoginAt: new Date() },
    })

    return {
      accessToken,
      refreshToken,
      user: {
        id: user.id,
        email: user.email,
        username: user.username,
        role: user.role,
        status: user.status,
        avatarUrl: user.avatarUrl,
      },
    }
  })

  // POST /api/auth/refresh
  fastify.post('/refresh', async (req, reply) => {
    const { refreshToken } = req.body as { refreshToken: string }
    if (!refreshToken) return reply.status(400).send({ error: 'Refresh token manquant' })

    const session = await fastify.prisma.session.findUnique({
      where: { token: refreshToken },
      include: { user: true },
    })

    if (!session || session.expiresAt < new Date()) {
      await fastify.prisma.session.deleteMany({ where: { token: refreshToken } })
      return reply.status(401).send({ error: 'Session expirée' })
    }

    const payload = {
      sub: session.user.id,
      email: session.user.email,
      role: session.user.role,
      status: session.user.status,
    }

    const accessToken = fastify.jwt.sign(payload, { expiresIn: config.JWT_EXPIRES_IN })

    return { accessToken }
  })

  // POST /api/auth/logout
  fastify.post('/logout', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const { refreshToken } = req.body as { refreshToken?: string }
    if (refreshToken) {
      await fastify.prisma.session.deleteMany({ where: { token: refreshToken } })
    }
    return { message: 'Déconnecté' }
  })

  // POST /api/auth/forgot-password
  fastify.post('/forgot-password', async (req, reply) => {
    const { email } = req.body as { email: string }
    if (!email) return reply.status(400).send({ error: 'Email requis' })

    const user = await fastify.prisma.user.findUnique({ where: { email } })

    // Toujours répondre 200 pour ne pas révéler si l'email existe
    if (user) {
      const resetToken = generateToken()
      const expiry = new Date(Date.now() + 60 * 60 * 1000) // 1h

      await fastify.prisma.user.update({
        where: { id: user.id },
        data: {
          resetPasswordToken: resetToken,
          resetPasswordExpiry: expiry,
        },
      })

      try {
        await sendPasswordResetEmail(email, user.username, resetToken)
      } catch (err) {
        fastify.log.error('Password reset email failed:', err)
      }
    }

    return { message: 'Si un compte existe avec cet email, vous recevrez un lien de réinitialisation.' }
  })

  // POST /api/auth/reset-password
  fastify.post('/reset-password', async (req, reply) => {
    const { token, password } = req.body as { token: string; password: string }
    if (!token || !password) {
      return reply.status(400).send({ error: 'Token et mot de passe requis' })
    }

    if (!isPasswordStrong(password)) {
      return reply.status(400).send({
        error: 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre',
      })
    }

    const user = await fastify.prisma.user.findFirst({
      where: {
        resetPasswordToken: token,
        resetPasswordExpiry: { gte: new Date() },
      },
    })

    if (!user) {
      return reply.status(400).send({ error: 'Token invalide ou expiré' })
    }

    const passwordHash = await hashPassword(password)
    await fastify.prisma.user.update({
      where: { id: user.id },
      data: {
        passwordHash,
        resetPasswordToken: null,
        resetPasswordExpiry: null,
      },
    })

    // Mettre à jour le mot de passe dans Navidrome
    try {
      await navidromeService.updateUserPassword(user.username, password)
    } catch (err) {
      fastify.log.error('Navidrome password update failed:', err)
    }

    // Révoquer toutes les sessions
    await fastify.prisma.session.deleteMany({ where: { userId: user.id } })

    return { message: 'Mot de passe réinitialisé avec succès' }
  })
}
