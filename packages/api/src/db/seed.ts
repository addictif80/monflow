/**
 * Seed initial : crée le compte administrateur par défaut
 */
import { PrismaClient, UserRole, UserStatus } from '@prisma/client'
import { hashPassword } from '../utils/auth.js'

const prisma = new PrismaClient()

async function main() {
  const adminEmail = process.env.ADMIN_EMAIL || 'admin@monflow.fr'
  const adminPassword = process.env.ADMIN_PASSWORD || 'AdminMonFlow2024!'
  const adminUsername = 'admin'

  const existing = await prisma.user.findUnique({ where: { email: adminEmail } })

  if (existing) {
    console.log('✅ Admin déjà existant:', adminEmail)
    return
  }

  const passwordHash = await hashPassword(adminPassword)

  await prisma.user.create({
    data: {
      email: adminEmail,
      username: adminUsername,
      passwordHash,
      role: UserRole.ADMIN,
      status: UserStatus.ACTIVE,
      emailVerified: true,
      settings: { create: {} },
    },
  })

  console.log('✅ Compte admin créé:', adminEmail)
  console.log('⚠️  Changez le mot de passe après la première connexion!')
}

main()
  .catch(console.error)
  .finally(() => prisma.$disconnect())
