# Getting Started with BookIt

You've decided to take control of your booking system. Great choice! This guide walks you through your first BookIt installation, from zero to your first live booking.

## 1. Check Your Prerequisites

Before you start, make sure you have:

- **PHP 8.1+** (with the extensions listed in the documentation)
- **Composer** for PHP dependencies
- **Node.js 16+** and npm for frontend assets
- **A database** — MySQL, PostgreSQL, or SQLite all work
- **Git** to clone the repository

> Running a shared host? Check with your host that PHP 8.1+ is available before you begin. If your host only offers older PHP, you have two options: upgrade your hosting plan, or ask us to host it for you (see the bottom of this article).

## 2. Install the Application

```bash
# Clone the repository
git clone https://github.com/tawandajosephmutsena/book-it.git
cd book-it

# Install PHP dependencies
composer install

# Install frontend dependencies
npm install

# Copy and configure your environment
cp .env.example .env
php artisan key:generate
```

## 3. Configure Your Database

Open `.env` and set your database details:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bookit
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Then run the migrations:

```bash
php artisan migrate
```

## 4. Build the Frontend

```bash
npm run build
```

For development, you can instead run `npm run dev` to get hot-reloading.

## 5. Create Your First Service

1. Start the app with `php artisan serve`
2. Visit `http://localhost:8000` and log in to the admin panel
3. Go to **Services → Add New Service**
4. Give it a name (e.g. "30-Minute Consultation"), set a duration and price
5. Save, and the service is immediately bookable

## 6. Go Live

For production, remember to:

- Set `APP_ENV=production` and disable debug mode
- Configure your mail driver so confirmations and reminders actually send
- Set up the scheduler cron entry (`php artisan schedule:run` every minute)
- Run `php artisan config:cache`, `route:cache`, and `view:cache`

## Prefer a Managed Setup?

Not everyone wants to touch a server — and that's fine. We host, secure, and maintain BookIt for you:

- **$6/month** (billed annually) or **$9/month** (billed monthly)
- We install, configure, and handle updates and backups
- You keep the dashboard, your data, and full control — we just keep the lights on

Email us to get started: [contact@ottomate.space](mailto:contact@ottomate.space)

---
*BookIt is released under the MIT License. Free to download, free to use, free to modify — forever.*