import nodemailer from 'nodemailer'
import { config } from '../config/index.js'

const transporter = nodemailer.createTransport({
  host: config.SMTP_HOST,
  port: config.SMTP_PORT,
  secure: config.SMTP_SECURE,
  auth: { user: config.SMTP_USER, pass: config.SMTP_PASSWORD },
})

const appName = 'MonFlow'
const appUrl = config.NEXT_PUBLIC_APP_URL
const primaryColor = '#6366f1'

function htmlTemplate(title: string, body: string): string {
  return `<!DOCTYPE html><html><head><meta charset="utf-8"><title>${title}</title></head>
  <body style="margin:0;padding:0;background-color:#0f0f0f;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#0f0f0f;padding:40px 0;">
  <tr><td align="center"><table width="600" cellpadding="0" cellspacing="0" style="background-color:#1a1a1a;border-radius:12px;overflow:hidden;">
  <tr><td style="background-color:${primaryColor};padding:30px;text-align:center;"><h1 style="color:#fff;margin:0;font-size:28px;">${appName}</h1></td></tr>
  <tr><td style="padding:40px;color:#e5e5e5;">${body}</td></tr>
  <tr><td style="padding:20px;text-align:center;background-color:#111;color:#666;font-size:12px;">
  <p style="margin:0;">&copy; ${new Date().getFullYear()} ${appName}</p>
  <p style="margin:8px 0 0;"><a href="${appUrl}" style="color:${primaryColor};">${appUrl}</a></p>
  </td></tr></table></td></tr></table></body></html>`
}

function button(text: string, url: string): string {
  return `<a href="${url}" style="display:inline-block;background-color:${primaryColor};color:#fff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:600;margin:20px 0;">${text}</a>`
}

export async function sendWelcomeEmail(email: string, username: string, verifyToken: string): Promise<void> {
  const verifyUrl = `${appUrl}/verify-email?token=${verifyToken}`
  const body = `<h2 style="color:#fff;margin-top:0;">Bienvenue sur ${appName}, ${username} !</h2>
  <p>Confirmez votre adresse email pour activer votre compte.</p>
  <div style="text-align:center;">${button('Confirmer mon email', verifyUrl)}</div>
  <p style="color:#888;font-size:14px;">Ce lien expire dans 24 heures.</p>`
  await transporter.sendMail({ from: config.SMTP_FROM, to: email, subject: `Bienvenue sur ${appName}`, html: htmlTemplate(`Bienvenue sur ${appName}`, body) })
}

export async function sendPasswordResetEmail(email: string, username: string, resetToken: string): Promise<void> {
  const resetUrl = `${appUrl}/reset-password?token=${resetToken}`
  const body = `<h2 style="color:#fff;margin-top:0;">Réinitialisation de mot de passe</h2>
  <p>Bonjour ${username}, une demande de réinitialisation a été effectuée.</p>
  <div style="text-align:center;">${button('Réinitialiser mon mot de passe', resetUrl)}</div>
  <p style="color:#888;font-size:14px;">Ce lien expire dans 1 heure.</p>`
  await transporter.sendMail({ from: config.SMTP_FROM, to: email, subject: `${appName} - Réinitialisation de mot de passe`, html: htmlTemplate('Réinitialisation', body) })
}

export async function sendPasswordResetByAdminEmail(email: string, username: string, tempPassword: string): Promise<void> {
  const body = `<h2 style="color:#fff;margin-top:0;">Votre mot de passe a été réinitialisé</h2>
  <p>Bonjour ${username}, voici vos nouvelles informations :</p>
  <div style="background:#111;border:1px solid #333;border-radius:8px;padding:20px;margin:20px 0;">
  <p><strong>Mot de passe temporaire :</strong> <code style="color:${primaryColor};font-size:18px;">${tempPassword}</code></p></div>
  <div style="text-align:center;">${button('Se connecter', `${appUrl}/login`)}</div>`
  await transporter.sendMail({ from: config.SMTP_FROM, to: email, subject: `${appName} - Mot de passe réinitialisé`, html: htmlTemplate('Réinitialisation', body) })
}

export async function sendSubscriptionConfirmationEmail(email: string, username: string, plan: string, endDate: Date): Promise<void> {
  const planLabel = plan === 'MONTHLY' ? 'mensuel' : 'annuel'
  const body = `<h2 style="color:#fff;margin-top:0;">Abonnement activé !</h2>
  <p>Bonjour ${username}, votre abonnement <strong>${planLabel}</strong> est actif.</p>
  <p>Prochaine facturation : ${endDate.toLocaleDateString('fr-FR')}</p>
  <div style="text-align:center;">${button('Commencer à écouter', appUrl)}</div>`
  await transporter.sendMail({ from: config.SMTP_FROM, to: email, subject: `${appName} - Abonnement activé`, html: htmlTemplate('Abonnement activé', body) })
}

export async function sendPaymentFailedEmail(email: string, username: string, gracePeriodDays: number): Promise<void> {
  const body = `<h2 style="color:#f87171;margin-top:0;">Problème de paiement</h2>
  <p>Bonjour ${username}, le renouvellement a échoué. Vous avez <strong>${gracePeriodDays} jours</strong> pour mettre à jour votre moyen de paiement.</p>
  <div style="text-align:center;">${button('Mettre à jour', `${appUrl}/dashboard/billing`)}</div>`
  await transporter.sendMail({ from: config.SMTP_FROM, to: email, subject: `${appName} - Problème de paiement`, html: htmlTemplate('Problème de paiement', body) })
}

export async function sendAccountSuspendedEmail(email: string, username: string): Promise<void> {
  const body = `<h2 style="color:#f87171;margin-top:0;">Compte suspendu</h2>
  <p>Bonjour ${username}, votre compte a été suspendu pour défaut de paiement.</p>
  <div style="text-align:center;">${button('Régulariser mon compte', `${appUrl}/dashboard/billing`)}</div>`
  await transporter.sendMail({ from: config.SMTP_FROM, to: email, subject: `${appName} - Compte suspendu`, html: htmlTemplate('Compte suspendu', body) })
}

export async function sendTicketResponseEmail(email: string, username: string, ticketId: string, subject: string): Promise<void> {
  const body = `<h2 style="color:#fff;margin-top:0;">Nouvelle réponse à votre ticket</h2>
  <p>Bonjour ${username}, l'équipe support a répondu à : <strong>${subject}</strong></p>
  <div style="text-align:center;">${button('Voir la réponse', `${appUrl}/dashboard/support/${ticketId}`)}</div>`
  await transporter.sendMail({ from: config.SMTP_FROM, to: email, subject: `${appName} - Réponse à votre ticket`, html: htmlTemplate('Support', body) })
}
