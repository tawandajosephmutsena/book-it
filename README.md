# BookIt — Open-Source Booking Software

**Free. Open source. Your data stays yours.**

BookIt is appointment scheduling software built with Laravel that helps entrepreneurs, freelancers, and service-based businesses run their bookings on their own server — no SaaS fees, no data mining, no lock-in.

![Booking flow — select time](docs/images/booking-select-time.webp)

## Why BookIt?

- 🔓 **Free & open source** (MIT) — download it, use it, modify it, forever
- 🗄️ **You own your data** — it lives on your server, not a third party's cloud
- 🚫 **No lock-in** — export anything, move hosts anytime, keep everything
- 💸 **No monthly SaaS fees** — pay for hosting, not for the software
- 📅 **Real-time availability** — no double bookings, no stale calendars
- 🔔 **Automated reminders** — fewer no-shows, fuller calendar
- 📊 **Reports & analytics** — see revenue, utilization, and peak hours
- 👥 **Team & multi-service** — staff schedules, custom fields, buffers

## Screenshots

### Customer Booking Flow

| | | |
|---|---|---|
| ![Select time](docs/images/booking-select-time.webp) | ![Add details](docs/images/booking-add-details.webp) | ![Confirmed](docs/images/booking-confirmed.webp) |

### Admin Dashboard

| | | |
|---|---|---|
| ![Landing](docs/images/dashboard-landing.webp) | ![Client list](docs/images/dashboard-client-list.webp) | ![Client card](docs/images/dashboard-client-card.webp) |
| ![Content management](docs/images/dashboard-content-management.webp) | ![Time blocking](docs/images/dashboard-time-blocking.webp) | ![Meeting card](docs/images/dashboard-meeting-card.webp) |

See all images in [docs/media.md](docs/media.md).

## Quick Start

```bash
git clone https://github.com/tawandajosephmutsena/book-it.git
cd book-it
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

Full walkthrough: [Getting Started](docs/posts/getting-started-with-bookit.md) · [Documentation](docs/docs/documentation.md)

## Articles

- [What is BookIt?](docs/posts/what-is-bookit.md)
- [How BookIt Works](docs/posts/how-bookit-works.md)
- [Getting Started with BookIt](docs/posts/getting-started-with-bookit.md)
- [Best Practices for Service-Based Businesses](docs/posts/best-practices-for-service-businesses.md)
- [Who Really Owns Your Customer Data?](docs/posts/own-your-data.md)

## Documentation

Full docs (installation, configuration, usage, API, deployment, troubleshooting, contributing):

[📖 Read the Documentation](docs/docs/documentation.md)

## Hosting Option — We Set It Up For You

Not technical? No problem. We host, secure, and maintain BookIt for you:

- **$6/month** (billed annually) or **$9/month** (billed monthly)
- We install, configure, update, and back up — you just run your business
- Your data stays yours; we're the caretaker, not the owner

**Email:** [contact@ottomate.space](mailto:contact@ottomate.space)

## License

MIT License. Copyright © 2026 Tawanda Joseph Mutsena.

---

*BookIt is released under the MIT License. Free to download, free to use, free to modify — forever.*
