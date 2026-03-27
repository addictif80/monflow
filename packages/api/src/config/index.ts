import { z } from 'zod'

const envSchema = z.object({
  NODE_ENV: z.enum(['development', 'production', 'test']).default('development'),
  API_PORT: z.coerce.number().default(3001),
  API_SECRET: z.string().min(32),
  JWT_SECRET: z.string().min(32),
  JWT_REFRESH_SECRET: z.string().min(32),
  JWT_EXPIRES_IN: z.string().default('15m'),
  JWT_REFRESH_EXPIRES_IN: z.string().default('7d'),

  DATABASE_URL: z.string(),
  REDIS_URL: z.string().default('redis://localhost:6379'),

  NAVIDROME_URL: z.string().default('http://navidrome:4533'),
  NAVIDROME_ADMIN_USER: z.string(),
  NAVIDROME_ADMIN_PASSWORD: z.string(),

  DEEMIX_URL: z.string().default('http://deemix:6595'),

  STRIPE_SECRET_KEY: z.string(),
  STRIPE_WEBHOOK_SECRET: z.string(),
  STRIPE_PRICE_MONTHLY: z.string(),
  STRIPE_PRICE_YEARLY: z.string(),

  SMTP_HOST: z.string(),
  SMTP_PORT: z.coerce.number().default(587),
  SMTP_SECURE: z.string().transform(v => v === 'true').default('false'),
  SMTP_USER: z.string(),
  SMTP_PASSWORD: z.string(),
  SMTP_FROM: z.string(),

  GENIUS_ACCESS_TOKEN: z.string().optional(),

  NEXT_PUBLIC_APP_URL: z.string().default('https://monflow.fr'),
  ADMIN_EMAIL: z.string().email().default('admin@monflow.fr'),

  PAYMENT_GRACE_PERIOD_DAYS: z.coerce.number().default(7),
  ACCOUNT_DELETION_DAYS: z.coerce.number().default(30),
})

const parsed = envSchema.safeParse(process.env)
if (!parsed.success) {
  console.error('❌ Invalid environment variables:')
  console.error(parsed.error.flatten().fieldErrors)
  process.exit(1)
}

export const config = parsed.data
export type Config = typeof config
