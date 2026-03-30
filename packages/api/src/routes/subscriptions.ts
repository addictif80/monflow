import { FastifyPluginAsync } from 'fastify'
import { SubscriptionStatus } from '@prisma/client'
import { createStripeCustomer, createCheckoutSession, createBillingPortalSession, cancelSubscription, reactivateSubscription, getInvoices, PLAN_PRICES, PLAN_AMOUNTS, constructWebhookEvent } from '../services/stripe.js'
import { sendSubscriptionConfirmationEmail, sendPaymentFailedEmail } from '../services/email.js'
import { config } from '../config/index.js'
import Stripe from 'stripe'

export const subscriptionRoutes: FastifyPluginAsync = async (fastify) => {
  fastify.get('/plans', async () => ({
    plans: [
      { id: 'MONTHLY', name: 'Mensuel', amount: PLAN_AMOUNTS.MONTHLY.amount, currency: PLAN_AMOUNTS.MONTHLY.currency, interval: PLAN_AMOUNTS.MONTHLY.interval, features: ['Écoute illimitée', 'Qualité haute fidélité', 'Mode radio Flow', 'Lyrics synchronisées', 'Application mobile'] },
      { id: 'YEARLY', name: 'Annuel', amount: PLAN_AMOUNTS.YEARLY.amount, currency: PLAN_AMOUNTS.YEARLY.currency, interval: PLAN_AMOUNTS.YEARLY.interval, savings: Math.round((PLAN_AMOUNTS.MONTHLY.amount * 12 - PLAN_AMOUNTS.YEARLY.amount) / 100), features: ['Tout du plan mensuel', '2 mois offerts', 'Priorité support'] },
    ],
  }))

  fastify.get('/me', { preHandler: [fastify.authenticate] }, async (req) => {
    const subscription = await fastify.prisma.subscription.findUnique({ where: { userId: req.user.sub } })
    return { subscription: subscription || null }
  })

  fastify.post('/checkout', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const { plan } = req.body as { plan: 'MONTHLY' | 'YEARLY' }
    if (!plan || !['MONTHLY', 'YEARLY'].includes(plan)) return reply.status(400).send({ error: 'Plan invalide' })
    const user = await fastify.prisma.user.findUnique({ where: { id: req.user.sub }, include: { subscription: true } })
    if (!user) return reply.status(404).send({ error: 'Utilisateur non trouvé' })
    if (user.subscription?.status === SubscriptionStatus.ACTIVE) return reply.status(409).send({ error: 'Vous avez déjà un abonnement actif' })
    let stripeCustomerId = user.subscription?.stripeCustomerId
    if (!stripeCustomerId) stripeCustomerId = await createStripeCustomer(user.email, user.username)
    const checkoutUrl = await createCheckoutSession({ customerId: stripeCustomerId, priceId: PLAN_PRICES[plan], successUrl: `${config.NEXT_PUBLIC_APP_URL}/dashboard/subscription?success=1`, cancelUrl: `${config.NEXT_PUBLIC_APP_URL}/dashboard/subscription?cancelled=1`, userId: user.id })
    return { url: checkoutUrl }
  })

  fastify.post('/portal', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const subscription = await fastify.prisma.subscription.findUnique({ where: { userId: req.user.sub } })
    if (!subscription?.stripeCustomerId) return reply.status(404).send({ error: 'Aucun abonnement trouvé' })
    return { url: await createBillingPortalSession(subscription.stripeCustomerId, `${config.NEXT_PUBLIC_APP_URL}/dashboard/subscription`) }
  })

  fastify.post('/cancel', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const subscription = await fastify.prisma.subscription.findUnique({ where: { userId: req.user.sub } })
    if (!subscription?.stripeSubscriptionId) return reply.status(404).send({ error: 'Aucun abonnement trouvé' })
    await cancelSubscription(subscription.stripeSubscriptionId)
    await fastify.prisma.subscription.update({ where: { userId: req.user.sub }, data: { cancelAtPeriodEnd: true } })
    return { message: 'Abonnement annulé.' }
  })

  fastify.post('/reactivate', { preHandler: [fastify.authenticate] }, async (req, reply) => {
    const subscription = await fastify.prisma.subscription.findUnique({ where: { userId: req.user.sub } })
    if (!subscription?.stripeSubscriptionId) return reply.status(404).send({ error: 'Aucun abonnement trouvé' })
    if (!subscription.cancelAtPeriodEnd) return reply.status(400).send({ error: "L'abonnement n'est pas en cours d'annulation" })
    await reactivateSubscription(subscription.stripeSubscriptionId)
    await fastify.prisma.subscription.update({ where: { userId: req.user.sub }, data: { cancelAtPeriodEnd: false } })
    return { message: 'Abonnement réactivé' }
  })

  fastify.get('/invoices', { preHandler: [fastify.authenticate] }, async (req) => {
    const subscription = await fastify.prisma.subscription.findUnique({ where: { userId: req.user.sub } })
    if (!subscription?.stripeCustomerId) return { invoices: [] }
    const invoices = await getInvoices(subscription.stripeCustomerId)
    return { invoices: invoices.map(inv => ({ id: inv.id, amount: inv.amount_paid, currency: inv.currency, status: inv.status, date: new Date((inv.created || 0) * 1000), pdfUrl: inv.invoice_pdf, hostedUrl: inv.hosted_invoice_url })) }
  })
}

export const stripeWebhookRoute: FastifyPluginAsync = async (fastify) => {
  fastify.addContentTypeParser('application/json', { parseAs: 'buffer' }, (req, body, done) => { done(null, body) })

  fastify.post('/webhooks/stripe', async (req, reply) => {
    const signature = req.headers['stripe-signature'] as string
    let event: Stripe.Event
    try { event = constructWebhookEvent(req.body as Buffer, signature) }
    catch (err: any) { return reply.status(400).send({ error: 'Invalid signature' }) }
    try { await handleStripeEvent(event, fastify) }
    catch (err) { return reply.status(500).send({ error: 'Handler error' }) }
    return { received: true }
  })
}

async function handleStripeEvent(event: Stripe.Event, fastify: any) {
  const prisma = fastify.prisma
  switch (event.type) {
    case 'checkout.session.completed': {
      const session = event.data.object as Stripe.CheckoutSession
      const userId = session.metadata?.userId
      if (!userId || !session.subscription) break
      await prisma.subscription.upsert({
        where: { userId },
        update: { stripeSubscriptionId: session.subscription as string, stripeCustomerId: session.customer as string, status: 'ACTIVE', currentPeriodStart: new Date(), currentPeriodEnd: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000) },
        create: { userId, plan: 'MONTHLY', stripeSubscriptionId: session.subscription as string, stripeCustomerId: session.customer as string, status: 'ACTIVE', currentPeriodStart: new Date(), currentPeriodEnd: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000) },
      })
      await prisma.user.update({ where: { id: userId }, data: { status: 'ACTIVE', suspendedAt: null } })
      const user = await prisma.user.findUnique({ where: { id: userId } })
      if (user) await sendSubscriptionConfirmationEmail(user.email, user.username, 'MONTHLY', new Date(Date.now() + 30 * 24 * 60 * 60 * 1000))
      break
    }
    case 'invoice.payment_succeeded': {
      const invoice = event.data.object as Stripe.Invoice
      const subscription = await prisma.subscription.findFirst({ where: { stripeCustomerId: invoice.customer as string }, include: { user: true } })
      if (!subscription) break
      await prisma.payment.create({ data: { userId: subscription.userId, subscriptionId: subscription.id, amount: invoice.amount_paid, currency: invoice.currency, status: 'SUCCEEDED', description: `Abonnement ${subscription.plan.toLowerCase()}`, stripeInvoiceId: invoice.id, invoiceUrl: invoice.hosted_invoice_url || null } })
      await prisma.subscription.update({ where: { id: subscription.id }, data: { status: 'ACTIVE', pastDueAt: null, currentPeriodEnd: new Date((invoice.period_end || 0) * 1000) } })
      if (subscription.user.status === 'SUSPENDED') await prisma.user.update({ where: { id: subscription.userId }, data: { status: 'ACTIVE', suspendedAt: null } })
      break
    }
    case 'invoice.payment_failed': {
      const invoice = event.data.object as Stripe.Invoice
      const subscription = await prisma.subscription.findFirst({ where: { stripeCustomerId: invoice.customer as string }, include: { user: true } })
      if (!subscription) break
      await prisma.payment.create({ data: { userId: subscription.userId, subscriptionId: subscription.id, amount: invoice.amount_due, currency: invoice.currency, status: 'FAILED', description: `Échec abonnement`, stripeInvoiceId: invoice.id, failureReason: 'Paiement refusé' } })
      await prisma.subscription.update({ where: { id: subscription.id }, data: { status: 'PAST_DUE', pastDueAt: new Date() } })
      await sendPaymentFailedEmail(subscription.user.email, subscription.user.username, config.PAYMENT_GRACE_PERIOD_DAYS)
      break
    }
    case 'customer.subscription.deleted': {
      const sub = event.data.object as Stripe.Subscription
      await prisma.subscription.updateMany({ where: { stripeSubscriptionId: sub.id }, data: { status: 'CANCELLED', cancelledAt: new Date() } })
      break
    }
  }
}
