# BookIt Documentation

## Overview

BookIt is an open-source appointment scheduling and booking system built with Laravel. This documentation covers installation, configuration, usage, and development guidelines.

## Table of Contents

1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Usage Guide](#usage-guide)
4. [API Reference](#api-reference)
5. [Customization](#customization)
6. [Deployment](#deployment)
7. [Troubleshooting](#troubleshooting)
8. [Contributing](#contributing)

## Installation

### Prerequisites

- PHP 8.1 or higher
- Composer
- Node.js & npm (for frontend assets)
- MySQL, PostgreSQL, or SQLite
- Git

### Step-by-Step Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/tawandajosephmutsena/book-it.git
   cd book-it
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

5. **Generate application key**
   ```bash
   php artisan key:generate
   ```

6. **Configure database**
   Edit `.env` file with your database credentials:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=bookit
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

7. **Run migrations**
   ```bash
   php artisan migrate
   ```

8. **Seed initial data (optional)**
   ```bash
   php artisan db:seed
   ```

9. **Build frontend assets**
   ```bash
   npm run build
   # or for development
   npm run dev
   ```

10. **Start the application**
    ```bash
    php artisan serve
    ```
    Visit `http://localhost:8000` in your browser.

## Configuration

### Environment Variables

Key environment variables in `.env`:

- `APP_NAME`: Application name
- `APP_ENV`: Environment (local, production, etc.)
- `APP_KEY`: Application encryption key
- `DB_*`: Database connection settings
- `MAIL_*`: Email configuration for notifications
- `QUEUE_CONNECTION`: Queue driver (sync, database, redis, etc.)
- `FILESYSTEM_DRIVER`: Storage driver (local, s3, etc.)

### Calendar Settings

Configure booking intervals, slot durations, and buffer times in:
- `config/booking.php`
- `config/services.php`

### Payment Gateways

BookIt supports Stripe and PayPal out of the box. Configure in:
- `config/services.php` (for API keys)
- `.env` file:
  ```
  STRIPE_KEY=your_stripe_key
  STRIPE_SECRET=your_stripe_secret
  PAYPAL_CLIENT_ID=your_paypal_client_id
  PAYPAL_CLIENT_SECRET=your_paypal_secret
  ```

### Email Notifications

Configure mail settings in `.env`:
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hello@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Usage Guide

### Admin Dashboard

Access the admin panel at `/admin` (default credentials: admin/admin - change immediately after first login!)

#### Managing Services

1. Navigate to **Services** in the sidebar
2. Click **"Add New Service"**
3. Fill in:
   - Service name
   - Description
   - Duration (in minutes)
   - Price
   - Buffer time before/after
   - Maximum participants
   - Associated staff/resources

#### Managing Staff

1. Go to **Staff** section
2. Add team members with:
   - Name
   - Email
   - Role/permissions
   - Availability schedule
   - Services they can provide

#### Managing Bookings

- View all bookings in the **Bookings** section
- Filter by date, status, service, or staff
- Actions: confirm, reschedule, cancel, mark as completed
- Export bookings to CSV

### Customer Booking Flow

1. Visit your booking page (typically `/book` or a custom subdomain)
2. Select a service from the list
3. Choose an available date from the calendar
4. Select an available time slot
5. Fill in customer information form
6. (Optional) Complete payment if required
7. Receive confirmation via email/SMS

## API Reference

BookIt provides a RESTful API for integration with other systems.

### Authentication

Most endpoints require API token authentication:
```
Authorization: Bearer YOUR_API_TOKEN
```

Generate API tokens in the admin panel under **API Tokens**.

### Endpoints

#### Services
- `GET /api/services` - List all services
- `GET /api/services/{id}` - Get service details
- `POST /api/services` - Create a new service (admin only)
- `PUT /api/services/{id}` - Update service (admin only)
- `DELETE /api/services/{id}` - Delete service (admin only)

#### Availability
- `GET /api/services/{service_id}/available-dates` - Get available dates for a service
- `GET /api/services/{service_id}/available-times?date=YYYY-MM-DD` - Get available times for a specific date

#### Bookings
- `GET /api/bookings` - List bookings (with filters)
- `GET /api/bookings/{id}` - Get booking details
- `POST /api/bookings` - Create a new booking
- `PUT /api/bookings/{id}` - Update booking
- `DELETE /api/bookings/{id}` - Cancel booking

#### Customers
- `GET /api/customers` - List customers
- `GET /api/customers/{id}` - Get customer details
- `POST /api/customers` - Create new customer
- `PUT /api/customers/{id}` - Update customer

### Webhooks

BookIt can send HTTP POST requests to configured URLs for events:
- `booking.created`
- `booking.updated`
- `booking.cancelled`
- `booking.completed`
- `customer.created`

Configure webhooks in the admin panel under **Integrations → Webhooks**.

## Customization

### Theming

Override Blade views by creating files in:
- `resources/views/vendor/bookit/` 
  (Laravel's view publishing system)

### Custom Fields

Add custom fields to services, customers, or bookings via:
- Admin panel → **Custom Fields**
- Or publish and modify migrations

### Styling

Modify the frontend appearance by:
1. Editing CSS in `resources/css/app.css`
2. Or overriding Tailwind CSS configuration in `tailwind.config.js`
3. Rebuild assets with `npm run build`

### Language & Localization

BookIt supports multiple languages:
- Language files in `resources/lang/`
- To add a new language, copy `resources/lang/en` to `resources/lang/your-lang-code`
- Translate the PHP arrays
- Set `APP_LOCALE` in `.env` or allow users to select language

## Deployment

### Production Checklist

1. Set `APP_ENV=production` in `.env`
2. Generate a new application key: `php artisan key:generate`
3. Configure proper database connection
4. Set up mail driver for production (SMTP, Mailgun, etc.)
5. Configure queue worker (Supervisor, systemd, etc.)
6. Set up scheduled tasks (cron entry for `php artisan schedule:run`)
7. Optimize configuration: `php artisan config:cache`
8. Optimize routes: `php artisan route:cache`
9. Optimize views: `php artisan view:cache`
10. Set proper file permissions:
    ```bash
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
    ```

### Server Requirements

- PHP 8.1+ with extensions: bcmath, ctype, fileinfo, json, mbstring, openssl, pdo, tokenizer, xml
- Composer 2.0+
- Node.js 16+ & npm
- MySQL 5.7+ / PostgreSQL 10+ / SQLite 3.8.8+
- Redis (recommended for queue and cache)
- Nginx or Apache web server

### Docker Deployment

A basic Docker setup is available in the `docker/` directory:
```bash
docker-compose up -d
```

### Cloud Providers

BookIt deploys easily to:
- Laravel Forge
- Laravel Vapor
- AWS Elastic Beanstalk
- DigitalOcean App Platform
- Heroku (with ClearDB MySQL addon)
- Render.com
- Fly.io

## Troubleshooting

### Common Issues

#### Installation Problems

- **"Could not open input file: artisan"**: Make sure you're in the book-it root directory
- **Composer memory errors**: Run `composer install --no-dev --prefer-dist`
- **Missing PHP extensions**: Check required extensions and install via your package manager

#### Database Issues

- **Connection refused**: Verify database server is running and credentials in `.env` are correct
- **Access denied**: Ensure database user has proper privileges
- **Migration failures**: Check database encoding (should be utf8mb4) and collation

#### Email Problems

- **Emails not sending**: Check mail credentials and connection to SMTP server
- **Failed to authenticate**: Verify username/password for mail service
- **Slow email sending**: Consider using a queue for mail delivery

#### Performance Issues

- **Slow page loads**: Enable route and config caching in production
- **High database usage**: Add indexes to frequently queried columns
- **Queue backlog**: Monitor and scale queue workers as needed

### Debugging

Enable debug mode temporarily in `.env`:
```
APP_DEBUG=true
```

View logs:
- Laravel logs: `storage/logs/laravel.log`
- Web server logs: Check your nginx/apache error logs
- Queue logs: `storage/logs/queue.log`

Use Laravel Telescope for advanced debugging (install via composer in development only).

## Contributing

We welcome contributions from the community! Here's how you can help:

### Reporting Issues

1. Check if the issue already exists in [GitHub Issues](https://github.com/tawandajosephmutsena/book-it/issues)
2. Create a new issue with:
   - Clear title and description
   - Steps to reproduce
   - Expected vs actual behavior
   - Screenshots if applicable
   - Environment details (PHP version, Laravel version, etc.)

### Pull Requests

1. Fork the repository
2. Create a new branch: `git checkout -b feature/your-feature-name`
3. Make your changes
4. Ensure your code follows PSR-12 coding standards
5. Add tests for new functionality
6. Run existing tests: `php artisan test`
7. Commit your changes: `git commit -m "Add: your feature description"`
8. Push to your branch: `git push origin feature/your-feature-name`
9. Open a Pull Request against the `main` branch

### Development Setup

For contributors, we recommend:
- Using Laravel Sail for local development: `./vendor/bin/sail up`
- Running tests with: `./vendor/bin/sail test`
- Using PHPStan for static analysis: `./vendor/bin/sail phpstan`
- Using Pint for code styling: `./vendor/bin/sail pint`

### Code Style

- Follow PSR-12 coding standards
- Use type declarations for function parameters and return types
- Write descriptive commit messages
- Keep pull requests focused on a single feature or bug fix
- Update documentation when adding/changing features

### License

By contributing to BookIt, you agree that your contributions will be licensed under the MIT License.

---

*Last updated: August 2026*
*BookIt is released under the MIT License. Copyright © 2026 Tawanda Joseph Mutsena*