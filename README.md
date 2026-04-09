# MonFlow — Portail de gestion streaming musical Navidrome

Plateforme de gestion d'abonnements pour un service de streaming musical basé sur [Navidrome](https://www.navidrome.org/).

## Fonctionnalités

### Portail client
- Inscription / connexion avec création automatique du compte Navidrome
- Gestion du profil et changement de mot de passe (synchronisé avec Navidrome)
- Souscription aux formules d'abonnement via Stripe
- Portefeuille rechargeable (wallet)
- Offrir un abonnement à un proche
- Gestion des appareils connectés avec révocation
- Historique des paiements
- Support par tickets

### Administration
- Tableau de bord avec statistiques
- Gestion manuelle des utilisateurs (CRUD, suspension, réactivation, suppression)
- Gestion du catalogue (formules d'abonnement)
- Gestion des codes promo
- Gestion des paiements et remboursements (Stripe ou wallet)
- Gestion des wallets clients
- Système de support par tickets
- Configuration SMTP avec test intégré
- Templates d'emails HTML personnalisables

### Automatisations (Celery)
- Suspension automatique des comptes à J+7 de retard de paiement
- Suppression automatique des comptes à J+30 de retard de paiement
- Envoi automatique d'emails (bienvenue, relance, suspension, suppression, etc.)
- Rappels de paiement avant échéance (J-3)

## Stack technique

- **Backend:** Django 5.1, Python 3.12
- **Base de données:** PostgreSQL 16
- **Tâches asynchrones:** Celery + Redis
- **Paiements:** Stripe (Checkout, Subscriptions, Refunds)
- **Streaming:** Navidrome (API REST native)
- **Frontend:** Bootstrap 5, htmx
- **Déploiement:** Docker Compose

## Démarrage rapide

### 1. Cloner et configurer

```bash
git clone <repo-url> monflow
cd monflow
cp .env.example .env
# Éditer .env avec vos clés Stripe, paramètres DB, etc.
```

### 2. Lancer avec Docker Compose

```bash
docker compose up -d
```

Cela lance : PostgreSQL, Redis, Navidrome, l'application Django, Celery worker et Celery beat.

### 3. Créer un superutilisateur

```bash
docker compose exec web python manage.py createsuperuser
```

### 4. Accéder

- **Portail client :** http://localhost:8000/portal/
- **Administration :** http://localhost:8000/admin/
- **Navidrome :** http://localhost:4533/
- **Django Admin :** http://localhost:8000/django-admin/

## Développement local (sans Docker)

```bash
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt

# Démarrer PostgreSQL et Redis localement
python manage.py migrate
python manage.py setup_default_templates
python manage.py createsuperuser
python manage.py runserver

# Dans un autre terminal :
celery -A monflow worker -l info
celery -A monflow beat -l info
```

## Configuration Stripe

1. Créer un compte Stripe et obtenir les clés API
2. Configurer les clés dans `.env`
3. Créer des produits et prix dans Stripe Dashboard
4. Copier les `price_id` dans les formules du portail admin
5. Configurer le webhook Stripe vers `https://votre-domaine.com/payments/webhook/stripe/`
6. Événements webhook à écouter :
   - `checkout.session.completed`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
   - `customer.subscription.deleted`

## Configuration des tâches planifiées

Via Django Admin > Periodic Tasks ou Celery Beat :

- `subscriptions.tasks.check_overdue_subscriptions` : toutes les heures
- `subscriptions.tasks.send_payment_reminders` : tous les jours à 9h

## Structure du projet

```
monflow/
├── apps/
│   ├── accounts/        # Modèle User, Wallet, SMTP, templates email
│   ├── subscriptions/   # Plans, codes promo, abonnements, tâches Celery
│   ├── payments/        # Paiements, remboursements, webhook Stripe
│   ├── support/         # Tickets de support
│   ├── admin_portal/    # Vues d'administration
│   └── customer_portal/ # Portail client
├── services/
│   ├── navidrome.py     # Client API Navidrome
│   ├── stripe_service.py# Service Stripe
│   └── email_service.py # Service d'envoi d'emails
├── templates/           # Templates HTML (Django)
├── static/              # Fichiers statiques
├── monflow/             # Configuration Django
└── docker-compose.yml
```
