# MonFlow

Portail de gestion pour une plateforme de streaming musical basée sur **Navidrome**.
Gère les abonnements, les paiements Stripe, les utilisateurs, le support, et synchronise
automatiquement avec Navidrome (création, suspension, réactivation, suppression).

Écrit en **Laravel 11 / PHP 8.2+ / MySQL** — compatible **CyberPanel / LiteSpeed / OpenLiteSpeed**.

## Fonctionnalités

### Cycle de vie utilisateur
- Inscription auto-créée sur Navidrome
- **Suspension automatique à J+7** d'impayé (mot de passe Navidrome randomisé, données conservées)
- **Suppression automatique à J+30** (utilisateur supprimé de Navidrome + abonnements Stripe annulés)
- **Réactivation automatique sur paiement** : le mot de passe original est restauré sur Navidrome (stocké chiffré AES-256 via `APP_KEY`)
- Synchronisation email / nom / mot de passe sur modification profil

### Portail client
- Tableau de bord (abonnement actif, portefeuille, paiements récents)
- Gestion profil + changement de mot de passe (sync Navidrome)
- Souscription via Stripe Checkout avec support codes promo
- Portefeuille interne rechargeable (Stripe top-up)
- Envoi d'abonnements en cadeau (crée le compte destinataire automatiquement)
- Gestion des appareils connectés
- Historique complet des paiements
- Système de tickets de support

### Panel admin
- Tableau de bord avec KPIs (utilisateurs, revenus mensuels, tickets ouverts)
- CRUD utilisateurs + suspension/réactivation/suppression manuelle
- CRUD formules d'abonnement + codes promo
- Gestion paiements + remboursements (moyen original OU crédit portefeuille)
- Gestion des abonnements et tickets
- **Configuration SMTP dynamique** (stockée en base, pas dans `.env`) avec bouton de test
- Éditeur de templates email HTML avec variables Mustache

### Paiements (Stripe)
- Abonnements récurrents
- Rechargements portefeuille
- Cadeaux
- Webhooks pour `checkout.session.completed`, `invoice.payment_succeeded`, `invoice.payment_failed`
- Remboursements totaux ou partiels

## Installation

### Prérequis

- PHP 8.2+
- MySQL 8.0+ / MariaDB 10.5+
- Composer 2+
- Navidrome déjà installé et accessible
- Un compte Stripe avec clés API

### Installation sur CyberPanel

1. **Créer un site dans CyberPanel**
   - Créer un site web en PHP 8.2+
   - Créer une base MySQL pour l'app

2. **Cloner le projet**
   ```bash
   cd /home/VOTRE-SITE.com
   git clone <repo> public_html
   cd public_html
   composer install --no-dev --optimize-autoloader
   ```

3. **Configurer `.env`**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Éditer `.env` et remplir :
   - `APP_URL` (https://votre-domaine.com)
   - `DB_*` (MySQL)
   - `NAVIDROME_URL`, `NAVIDROME_ADMIN_USER`, `NAVIDROME_ADMIN_PASSWORD`
   - `STRIPE_PUBLIC_KEY`, `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`
   - `SUSPEND_DELAY_DAYS` (défaut 7), `DELETE_DELAY_DAYS` (défaut 30)

   **⚠️ Important :** `APP_KEY` est utilisée pour chiffrer les mots de passe stockés
   pour la réactivation Navidrome. Si vous la changez, les mots de passe existants
   ne pourront plus être déchiffrés.

4. **Migrer la base et installer les templates email**
   ```bash
   php artisan migrate
   php artisan setup:email-templates
   ```

5. **Créer le premier admin**
   ```bash
   php artisan tinker
   >>> $u = App\Models\User::create(['username' => 'admin', 'email' => 'admin@example.com', 'password' => Hash::make('motdepasse'), 'is_admin' => true]);
   >>> $u->storeEncryptedPassword('motdepasse');
   ```

6. **Pointer le DocumentRoot sur `public/`**
   Dans CyberPanel > Site > vHost Conf, mettre `DocumentRoot` sur
   `/home/VOTRE-SITE.com/public_html/public`. Sinon, le `.htaccess` à la racine
   redirige automatiquement vers `public/`.

7. **Configurer le cron Laravel**
   Dans CyberPanel > Cron Jobs, ajouter :
   ```
   * * * * * cd /home/VOTRE-SITE.com/public_html && php artisan schedule:run >> /dev/null 2>&1
   ```
   Ceci déclenche :
   - `subscriptions:check-overdue` chaque heure (suspension J+7 / suppression J+30)
   - `subscriptions:send-payment-reminders` chaque jour à 9h (relances avant échéance + relances quotidiennes en retard)
   - `subscriptions:send-renewal-reminders` chaque jour à 9h15 (rappel de renouvellement à J-7)
   - `queue:work --stop-when-empty` chaque minute (traite la file d'emails ; à remplacer par un worker persistant `supervisor`/`systemd` en production si le volume augmente)

8. **Configurer le webhook Stripe**
   Dans Dashboard Stripe > Webhooks, créer un endpoint :
   - URL : `https://votre-domaine.com/stripe/webhook`
   - Événements : `checkout.session.completed`, `invoice.payment_succeeded`, `invoice.payment_failed`
   - Copier le `Signing secret` dans `STRIPE_WEBHOOK_SECRET` du `.env`

9. **Permissions**
   ```bash
   chown -R VOTRE-USER:VOTRE-USER storage bootstrap/cache
   chmod -R 775 storage bootstrap/cache
   ```

## Architecture

```
app/
  Console/Commands/     # Tâches planifiées (overdue, reminders, setup)
  Http/Controllers/
    Auth/               # Inscription, connexion, mot de passe
    Admin/              # Panel admin
    Portal/             # Portail client, tickets
    PaymentController   # Webhooks Stripe
  Http/Middleware/      # AdminMiddleware
  Models/               # 14 modèles Eloquent (UUID)
  Services/
    NavidromeService    # Client API Navidrome (/api/*, JWT)
    StripeService       # Checkout sessions, refunds, webhook
    EmailService        # SMTP dynamique + templates
database/migrations/    # 6 migrations MySQL
resources/views/        # Blade templates (Tailwind CDN)
routes/web.php          # Toutes les routes HTTP
```

## Suspension et réactivation Navidrome

Navidrome n'a **pas** de fonctionnalité native de suspension. MonFlow implémente un
contournement sécurisé :

1. **Suspension** : le mot de passe de l'utilisateur sur Navidrome est remplacé par
   une chaîne aléatoire (`bin2hex(random_bytes(32))`). L'utilisateur est bloqué de tous
   les clients (web, apps, CarPlay, Android Auto) mais ses playlists, historique et
   préférences sont conservés.

2. **Stockage du mot de passe original** : à la création du compte et à chaque
   changement de mot de passe, le mot de passe en clair est **chiffré avec AES-256-CBC**
   (via `Illuminate\Support\Facades\Crypt`, clé = `APP_KEY`) et stocké dans la colonne
   `users.encrypted_password`.

3. **Réactivation automatique** : lorsqu'un webhook Stripe `checkout.session.completed`
   arrive pour un utilisateur suspendu, `PaymentController::handleCheckout()` :
   - Remet le statut en `active`
   - Déchiffre le mot de passe original via `getDecryptedPassword()`
   - Appelle `NavidromeService::reactivateUser()` qui restaure le mot de passe
   - L'utilisateur retrouve l'accès **sans rien changer** sur ses appareils

4. **Avertissement avant suppression** : à J-7 avant la suppression définitive
   (donc J+23 par défaut, `delete_delay_days - 7`), `subscriptions:check-overdue`
   envoie un email `deletion_warning` à l'utilisateur suspendu pour l'informer
   que ses données seront supprimées le `{{ deletion_date }}` s'il ne régularise
   pas. L'envoi est tracé sur `subscriptions.deletion_warning_sent_at` pour ne
   partir qu'une seule fois ; ce champ est remis à zéro automatiquement dès que
   l'échéance de l'abonnement est repoussée dans le futur (renouvellement,
   prolongation manuelle). Aucun avertissement n'est envoyé lors d'un
   traitement manuel avec l'option `--keep-data` (aucune suppression n'aura
   lieu dans ce cas).

## Clients mobiles et Android Auto / CarPlay

MonFlow ne fournit pas d'app mobile propriétaire. Navidrome expose le **protocole
Subsonic**, supporté par de nombreux clients mobiles :

### Android (Android Auto compatible)
- **Symfonium** (payant, excellent) — Android Auto ✓
- **Ultrasonic** (gratuit, open source) — Android Auto ✓
- **Substreamer** (freemium)
- **DSub** (gratuit, open source)

### iOS (CarPlay compatible)
- **play:Sub** (payant) — CarPlay ✓
- **Amperfy** (gratuit, open source) — CarPlay ✓
- **iSub** (gratuit)

### Web
Navidrome fournit sa propre interface web. Un lecteur web intégré à MonFlow
(basé sur l'API Subsonic) est prévu dans une future version.

Les utilisateurs configurent ces apps avec :
- URL : `https://votre-serveur-navidrome`
- Nom d'utilisateur / mot de passe : identiques au portail MonFlow

## Sécurité

- Mots de passe utilisateurs hashés avec bcrypt (Laravel)
- Mot de passe original chiffré AES-256-CBC via `APP_KEY` pour la réactivation Navidrome
- Transactions DB avec verrous `lockForUpdate()` pour les opérations portefeuille
- CSRF tokens sur tous les formulaires
- Validation des webhooks Stripe via signature
- Middleware admin pour routes admin

## Développement local

```bash
composer install
cp .env.example .env
php artisan key:generate
# Configurer DB dans .env
php artisan migrate
php artisan setup:email-templates
php artisan serve
```

## Commandes Artisan utiles

```bash
# Vérifier les abonnements en retard (suspension J+7, suppression J+30)
php artisan subscriptions:check-overdue

# Envoyer les rappels de paiement (relance avant échéance + retards)
php artisan subscriptions:send-payment-reminders

# Envoyer les rappels de renouvellement (J-7)
php artisan subscriptions:send-renewal-reminders

# Installer/réinstaller les templates email par défaut
php artisan setup:email-templates
```

## License

Propriétaire.
