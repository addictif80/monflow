import bcrypt from 'bcryptjs'
import crypto from 'crypto'

const SALT_ROUNDS = 12

export async function hashPassword(password: string): Promise<string> {
  return bcrypt.hash(password, SALT_ROUNDS)
}

export async function verifyPassword(password: string, hash: string): Promise<boolean> {
  return bcrypt.compare(password, hash)
}

export function generateToken(bytes = 32): string {
  return crypto.randomBytes(bytes).toString('hex')
}

export function generateTempPassword(length = 12): string {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789'
  let result = ''
  const bytes = crypto.randomBytes(length)
  for (let i = 0; i < length; i++) {
    result += chars[bytes[i] % chars.length]
  }
  return result
}

export function generateNavidromeToken(username: string, password: string): string {
  // Token de type "token auth" Subsonic
  const salt = crypto.randomBytes(8).toString('hex')
  const token = crypto.createHash('md5').update(password + salt).digest('hex')
  return `u=${username}&t=${token}&s=${salt}`
}

export function isPasswordStrong(password: string): boolean {
  // Au moins 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre
  return (
    password.length >= 8 &&
    /[A-Z]/.test(password) &&
    /[a-z]/.test(password) &&
    /[0-9]/.test(password)
  )
}
