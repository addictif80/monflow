import Stripe from 'stripe'
import { config } from '../config/index.js'

export const stripe = new Stripe(config.STRIPE_SECRET_KEY, { apiVersion: '2024-06-20', typescript: true })
export type PlanType = 'MONTHLY' | 'YEARLY'
export const PLAN_PRICES: Record<PlanType, string> = { MONTHLY: config.STRIPE_PRICE_MONTHLY, YEARLY: config.STRIPE_PRICE_YEARLY }
export const PLAN_AMOUNTS: Record<PlanType, { amount: number; currency: string; interval: string }> = {
  MONTHLY: { amount: 999, currency: 'eur', interval: 'mois' },
  YEARLY: { amount: 9999, currency: 'eur', interval: 'an' },
}

export async function createStripeCustomer(email: string, name: string): Promise<string> {
  const customer = await stripe.customers.create({ email, name, metadata: { platform: 'monflow' } })
  return customer.id
}

export async function createCheckoutSession(params: { customerId: string; priceId: string; successUrl: string; cancelUrl: string; userId: string; trialDays?: number }): Promise<string> {
  const session = await stripe.checkout.sessions.create({
    customer: params.customerId,
    payment_method_types: ['card'],
    line_items: [{ price: params.priceId, quantity: 1 }],
    mode: 'subscription',
    success_url: params.successUrl,
    cancel_url: params.cancelUrl,
    allow_promotion_codes: true,
    billing_address_collection: 'required',
    subscription_data: { metadata: { userId: params.userId }, ...(params.trialDays ? { trial_period_days: params.trialDays } : {}) },
    metadata: { userId: params.userId },
  })
  return session.url!
}

export async function createBillingPortalSession(customerId: string, returnUrl: string): Promise<string> {
  const session = await stripe.billingPortal.sessions.create({ customer: customerId, return_url: returnUrl })
  return session.url
}

export async function cancelSubscription(subscriptionId: string): Promise<void> {
  await stripe.subscriptions.update(subscriptionId, { cancel_at_period_end: true })
}

export async function reactivateSubscription(subscriptionId: string): Promise<void> {
  await stripe.subscriptions.update(subscriptionId, { cancel_at_period_end: false })
}

export async function getSubscription(subscriptionId: string): Promise<Stripe.Subscription> {
  return stripe.subscriptions.retrieve(subscriptionId)
}

export async function getInvoices(customerId: string, limit = 10): Promise<Stripe.Invoice[]> {
  const invoices = await stripe.invoices.list({ customer: customerId, limit })
  return invoices.data
}

export function constructWebhookEvent(payload: Buffer, signature: string): Stripe.Event {
  return stripe.webhooks.constructEvent(payload, signature, config.STRIPE_WEBHOOK_SECRET)
}
