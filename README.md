# Refugio del Mar

**A hotel management system for the Andalusian coast**, from Huelva to Almería.

Refugio del Mar started as my final-degree project (TFG). This repository is a full rebuild of it: same idea, rewritten from scratch now that I have more time and more experience, with a cleaner architecture, tests for every feature, a bilingual interface and a design of its own.

Travellers search and compare hotels in the five coastal provinces, hotel owners manage their hotels, rooms, photos and offers, and administrators keep the platform in order.

## Demo

<!-- demo video -->

> 🎬 Demo video coming soon.

## Features

### For travellers

- **Search** by destination (town, province or hotel name, accent-insensitive, with suggestions as you type), dates, adults and children.
- **Filters** by price per night, stars, travel style (beach, family-friendly, luxury…) and amenities; sort by recommended, guest rating or price.
- **List and map side by side**: hovering a hotel highlights its pin on the map.
- **Compare** up to three hotels side by side: price for your dates, amenities, travel style and cancellation policy.
- **Hotel page** with photo gallery, rooms grouped by type with the total price of the stay (night-by-night breakdown, offers already applied) and how many are still free for your dates, amenities, activities, location map with directions and the cancellation policy in plain words.
- **Book and pay online** with Stripe Checkout. The room is held for 30 minutes while you pay, and two people can never book the same room for the same nights.
- **My reservations**: upcoming, past and cancelled stays, each with its booking code and price breakdown.
- **Cancel up to the check-in day** and get the refund back on your card automatically, following the hotel's cancellation tiers. The cancel dialog tells you the exact amount before you confirm.
- **Review your stay** from the check-out day: a 1–5 rating and a comment, which you can edit or delete later. Each hotel shows its average guest rating, how many reviews gave each score and the hotel's replies.

### For hotel owners

- Create hotels: description, province, address, stars, amenities, categories and a **tiered cancellation policy** (e.g. full refund up to 7 days before check-in, 50 % up to 3 days before).
- Place the hotel on the map by **searching its address** and dragging the pin to the exact spot.
- Add **rooms one by one or in bulk** ("10 doubles numbered from 101").
- Upload **photos** for the hotel and each room; they are resized and converted to WebP automatically, and can be reordered or set as cover.
- Informative **activities** (yoga, boat trips, tastings…).
- **Offers**: a percentage discount for a range of nights, on the whole hotel or one room type. If several offers cover the same night, the best one applies; discounts never stack.
- Publish or hide the hotel at any time (hiding never cancels existing bookings) and **preview** its public page before publishing.
- **Reservations** of their hotels, with filters, the guest's contact details and requests. If the hotel cannot honour a booking, the owner cancels it with a reason and the guest gets a full refund.
- **Reviews** of their hotels: reply publicly to guests, and report offensive reviews to the administrators (owners cannot delete reviews, so fair criticism stays).

### For administrators

- **User management**: search, filter, create users with any role, edit, deactivate and reactivate. Users are never deleted, only deactivated, and only when they have no hotels or active bookings.
- **Hotel moderation**: see every hotel, edit any of them and block those that break the rules, with a reason the owner can read.
- **Catalogues**: amenities (with icon), categories and room types, each with a name in Spanish and English.
- **Every reservation** on the platform, with a filter for refunds that failed and a button to retry them safely.
- **Review moderation**: reports from owners first; removing a review asks for a reason, takes it out of the hotel's average and emails its author why.

### Across the platform

- Interface in **Spanish and English**, with the language remembered per user.
- **Emails in each recipient's language**: booking confirmed (with the price breakdown and cancellation policy) and cancellations with the exact refund for travellers; new bookings and guest cancellations for hotel owners; a notice with the reason when a review is removed. New accounts confirm their email address before booking.
- **Light and dark mode** with a coastal colour palette.
- Three roles (admin, owner, customer) with access rules enforced on the server; trying to open a page you can't use takes you back with a message instead of an error page.
- One single place calculates prices, so the price shown is always the price charged.

## Roadmap

- [x] Roles, bilingual interface and user management
- [x] Hotels, rooms, photos, activities and admin catalogues
- [x] Offers and stay pricing
- [x] Public catalogue: home page, search with map, comparison and hotel page
- [x] Reservations and payments with Stripe (with refunds following each hotel's cancellation policy)
- [x] Transactional emails in each user's language
- [x] Reviews and ratings
- [ ] Dashboards with statistics for owners and admins
- [ ] Demo data

## Tech stack

### This version

| Area              | Technology                                                                 |
| ----------------- | -------------------------------------------------------------------------- |
| Backend           | Laravel 13 (PHP 8.4), Laravel Fortify (authentication)                     |
| Frontend          | React 19, TypeScript, Inertia.js v3, Tailwind CSS v4, shadcn/ui components |
| Routing           | Laravel Wayfinder (typed routes shared with the frontend)                  |
| Maps              | Leaflet with OpenStreetMap tiles, Nominatim for address search             |
| Payments          | Stripe Checkout, webhooks and automatic refunds (stripe-php)               |
| Database          | SQLite                                                                     |
| Testing & quality | Pest, Larastan (PHPStan), Laravel Pint, TypeScript, Vite+ lint and format  |

### Original TFG stack (for reference)

The first version of the project used Laravel, Inertia, React, TypeScript, Tailwind CSS, Fortify, Wayfinder, Pest and Stripe, with **PostgreSQL** as the database, **Docker** for deployment and a link to **Google Maps** to show each hotel's location. This rebuild uses SQLite for development and replaces the Google Maps link with interactive OpenStreetMap maps built into the pages.

## Getting started

### Requirements

- PHP 8.4 with the GD, EXIF and fileinfo extensions
- Composer
- Node.js 22

### Installation

```bash
git clone https://github.com/alvarodawserver/hotel-management-system.git
cd hotel-management-system

# Installs dependencies, creates .env, generates the app key, migrates and builds the frontend
composer setup

# Public storage for uploaded photos, plus demo users and catalogues
php artisan storage:link
php artisan migrate:fresh --seed

# Server, queue worker, scheduler and Vite dev server
composer dev
```

Then open http://localhost:8000.

### Demo accounts

All of them use the password `password`.

| Role          | Email                |
| ------------- | -------------------- |
| Administrator | admin@example.com    |
| Hotel owner   | owner@example.com    |
| Customer      | customer@example.com |

### Payments with Stripe (test mode)

1. Copy your **test** keys from the Stripe dashboard (Developers → API keys) into `.env`:

    ```env
    STRIPE_KEY=pk_test_...
    STRIPE_SECRET=sk_test_...
    ```

2. Install the [Stripe CLI](https://docs.stripe.com/stripe-cli), log in once and forward webhooks to your local app:

    ```bash
    stripe login
    stripe listen \
      --events checkout.session.completed,checkout.session.async_payment_succeeded,checkout.session.expired,refund.updated \
      --forward-to localhost:8000/stripe/webhook
    ```

    The command prints a signing secret; put it in `.env` as `STRIPE_WEBHOOK_SECRET=whsec_...`. Without it, payments are never confirmed locally, because Stripe cannot reach `localhost` on its own.

3. Pay with the test card `4242 4242 4242 4242`, any future expiry date and any CVC.

`composer dev` also runs the scheduler (`php artisan schedule:work`), which expires unpaid bookings every five minutes in case a webhook is lost.

### Emails

By default emails are written to `storage/logs/laravel.log`. To see them as they would arrive, create a free [Mailtrap](https://mailtrap.io) sandbox inbox and put its SMTP credentials in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=...
MAIL_PASSWORD=...
```

Emails are sent through the queue, so keep `composer dev` (which runs the queue worker) open. The demo accounts are already verified; accounts you register yourself receive a verification email first.

### Photo uploads

Photos can be up to 10 MB each. If uploads fail, raise these values in your `php.ini`:

```ini
upload_max_filesize = 10M
post_max_size = 64M
```

On **Windows**, if every upload fails with "failed to upload", also set `upload_tmp_dir` to a writable folder (for example your `%TEMP%`). `php artisan serve` does not pass the `TEMP` variable to PHP's built-in server, so PHP otherwise tries to write uploads to `C:\Windows`.

## Tests and code quality

```bash
composer test          # Pint, PHPStan and the Pest test suite
npm run check          # Frontend lint and formatting
npm run types:check    # TypeScript
```

Run a single test file with `php artisan test --compact tests/Feature/Catalog/HotelSearchTest.php`.

## Project structure

| Path                           | What lives there                                                                                                                                  |
| ------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Actions`                  | Business operations shared by several endpoints: pricing (`CalculateStayPrice`), hotel search, visibility, image optimisation, user deactivation… |
| `app/Http/Controllers/Catalog` | Public pages: home, search, hotel page, comparison                                                                                                |
| `app/Http/Controllers/Manage`  | Hotel management and reservations for owners (and admins)                                                                                         |
| `app/Http/Controllers/Admin`   | Administration: users, hotels, catalogues                                                                                                         |
| `app/Policies`                 | Who can do what with each record                                                                                                                  |
| `resources/js/pages`           | One React page per screen (`catalog`, `manage`, `admin`, `settings`, `auth`)                                                                      |
| `resources/js/components`      | Shared UI, grouped by area                                                                                                                        |
| `lang/es.json`                 | Spanish translations (English is the source language)                                                                                             |
| `tests/Feature`                | Feature tests, one file per controller or action                                                                                                  |

A few design decisions:

- **Money is stored in cents** and every price goes through `CalculateStayPrice`.
- **Stripe sits behind a `PaymentGateway` interface**, so tests use a fake and never call Stripe. Refunds are made on the payment (PaymentIntent) saved by the webhook, with an idempotency key per reservation, so a retry can never refund twice.
- **Hotels and rooms are soft-deleted**, so past bookings always keep their data.
- **Layouts depend on the role**: travellers see a public header and footer; owners and admins get a management sidebar.

## Credits

Maps © [OpenStreetMap](https://www.openstreetmap.org/copyright) contributors.
