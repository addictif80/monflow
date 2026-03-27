/**
 * Worker de tâches planifiées
 * - Suspension automatique des comptes en retard de paiement
 * - Suppression définitive des comptes supprimés
 * - Nettoyage des sessions expirées
 */

import { PrismaClient, UserStatus, SubscriptionStatus } from '@prisma/client'
import { sendAccountSuspendedEmail } from './services/email.js'
import { config } from './config/index.js'

const prisma = new PrismaClient()

async function suspendOverdueAccounts() {
  const threshold = new Date(
    Date.now() - config.PAYMENT_GRACE_PERIOD_DAYS * 24 * 60 * 60 * 1000
  )

  // Trouver les abonnements PAST_DUE dépassant la période de grâce
  const overdueSubscriptions = await prisma.subscription.findMany({
    where: {
      status: SubscriptionStatus.PAST_DUE,
      pastDueAt: { lte: threshold },
    },
    include: { user: true },
  })

  for (const sub of overdueSubscriptions) {
    if (sub.user.status === UserStatus.ACTIVE) {
      console.log(`[Worker] Suspension du compte ${sub.user.email} (paiement en retard)`)

      await prisma.user.update({
        where: { id: sub.userId },
        data: { status: UserStatus.SUSPENDED, suspendedAt: new Date() },
      })

      await prisma.subscription.update({
        where: { id: sub.id },
        data: { status: SubscriptionStatus.SUSPENDED },
      })

      try {
        await sendAccountSuspendedEmail(sub.user.email, sub.user.username)
      } catch (err) {
        console.error(`[Worker] Erreur email suspension:`, err)
      }
    }
  }

  console.log(`[Worker] ${overdueSubscriptions.length} comptes vérifiés pour suspension`)
}

async function deleteExpiredAccounts() {
  const threshold = new Date(
    Date.now() - config.ACCOUNT_DELETION_DAYS * 24 * 60 * 60 * 1000
  )

  // Supprimer définitivement les comptes marqués DELETED depuis plus de X jours
  const result = await prisma.user.deleteMany({
    where: {
      status: UserStatus.DELETED,
      deletedAt: { lte: threshold },
    },
  })

  if (result.count > 0) {
    console.log(`[Worker] ${result.count} compte(s) supprimé(s) définitivement`)
  }
}

async function cleanExpiredSessions() {
  const result = await prisma.session.deleteMany({
    where: { expiresAt: { lte: new Date() } },
  })
  console.log(`[Worker] ${result.count} session(s) expirée(s) nettoyée(s)`)
}

async function runTasks() {
  console.log('[Worker] Démarrage des tâches planifiées...')

  try {
    await cleanExpiredSessions()
    await suspendOverdueAccounts()
    await deleteExpiredAccounts()
    console.log('[Worker] Toutes les tâches terminées')
  } catch (err) {
    console.error('[Worker] Erreur:', err)
  }
}

// Exécuter toutes les heures
const INTERVAL_MS = 60 * 60 * 1000

runTasks()
setInterval(runTasks, INTERVAL_MS)

process.on('SIGTERM', async () => {
  await prisma.$disconnect()
  process.exit(0)
})
