# Leads CRM

A real estate lead management system built with Laravel. It centralises leads from multiple sources (Facebook/Meta, Zapier, manual import), routes them to the right sales reps, and tracks every lead through the full sales pipeline to a closed deal.

## Features

- **Lead ingestion** — Facebook/Meta Leads API webhook, Zapier webhook, Excel bulk import, manual entry
- **Sales pipeline** — 14 configurable statuses (New → Follow-up → Meeting → Booking → Sold, and more)
- **Automated routing** — round-robin auto-assignment based on availability, campaign, and working hours
- **Booking & invoicing** — attach booking details, invoices, and customer documents (passport, reservation form) per lead
- **Role-based access** — 7 roles with 13 fine-grained permissions
- **Notifications** — in-app, Firebase FCM push, and email (follow-up reminders, status changes)
- **Scheduled tasks** — follow-up reminders every 5 min, Zapier auto-import every 30 min, auto-reassignment every 15 min
- **Reporting** — dashboard with per-status lead counts, activity log, lead path audit trail

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.2+ |
| Database | MySQL |
| Frontend | Bootstrap 5, jQuery 3.6, Laravel Mix |
| Auth & RBAC | Laravel Sanctum, Spatie/Permission v6 |
| Push notifications | Firebase FCM, Pusher |
| PDF generation | Laravel MPDF |
| Excel import | Maatwebsite/Excel |

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+ & npm
- MySQL 8+
- A Firebase project (for push notifications)
- A Pusher app (for real-time in-app notifications)

## Installation

```bash
# 1. Clone the repo
git clone <repo-url>
cd leads-crm

# 2. Install PHP dependencies
composer install

# 3. Install JS dependencies
npm install

# 4. Copy and configure environment
cp .env.example .env
php artisan key:generate

# 5. Run migrations and seed roles/permissions
php artisan migrate
php artisan db:seed

# 6. Build frontend assets
npm run production
```

## Environment Variables

Key variables to set in `.env`:

```env
APP_NAME="Leads CRM"
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=leads_crm
DB_USERNAME=root
DB_PASSWORD=

# Mail
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com

# Firebase (push notifications)
FIREBASE_PROJECT=your-project-id
FIREBASE_CREDENTIALS=/path/to/service-account.json

# Pusher (real-time notifications)
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=
BROADCAST_DRIVER=pusher

# Queue (use database or redis in production)
QUEUE_CONNECTION=database
```

## Scheduled Tasks

Add this cron entry to run Laravel's scheduler:

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler runs three automated tasks:

| Command | Frequency | Purpose |
|---|---|---|
| `tickets:follow_up-reminder` | Every 5 min | Sends email + push reminders for follow-up leads |
| `tickets:auto-import` | Every 30 min | Fetches new leads from Zapier-enabled sources |
| `tickets:auto-reassign` | Every 15 min | Reassigns stale leads; moves to Dead if no agent available |

All tasks respect configurable working hours set in General Settings.

## Queue Workers

If `QUEUE_CONNECTION` is set to `database` or `redis`, start a worker:

```bash
php artisan queue:work
```

## Roles & Permissions

| Role | Key Capabilities |
|---|---|
| `super-admin` | Full access — all permissions, user management, settings |
| `admin` | Manage leads and users; cannot approve, book, or invoice |
| `sales-manager` | Manage team leads; can pre-approve or reject |
| `sale` | View and update assigned leads; can book |
| `tele-sale` | View and update assigned leads; can book |
| `accountant` | View booked leads; can create invoices and mark as Sold |

Roles and permissions are seeded via `php artisan db:seed`.

## Webhook Endpoints

| Method | URL | Purpose |
|---|---|---|
| `GET/POST` | `/api/webhooks/meta` | Facebook/Meta Leads API (signature-verified) |
| `POST` | `/api/webhook/leads` | Zapier or any external lead source |

## Project Structure

```
app/
  Console/          # Scheduler and artisan commands
  Http/Controllers/ # 18 controllers (Ticket, User, Meta, Notification…)
  Imports/          # Excel import classes
  Models/           # 17 models (Ticket, Booking, Meeting, User…)
  Services/         # Business logic (LeadAutoAssign, Meta, Notification)
  Helpers/          # Lead distribution helpers
config/
  constants.php     # Status colours and icons
database/
  migrations/       # 25 migration files
  seeders/          # Role, Permission, Status seeders
resources/views/
  tickets/          # Lead list, detail, import, archive
  users/            # User management
  settings/         # General settings
  notifications/    # Notification inbox
routes/
  web.php           # Web routes
  api.php           # Webhook and API routes
```

## License

Private / proprietary.
