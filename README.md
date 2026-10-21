# Modern Lanka Motors

Premium E-commerce store for Toyota **Hiace, Dolphin and HiRoof** van parts and modifications in Sri Lanka. Customers browse and review products, then pay by card (Stripe) or order through WhatsApp. Admins manage stock, orders and invoices.

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)
![Stripe](https://img.shields.io/badge/Stripe-payments-635BFF?logo=stripe&logoColor=white)

**Live demo:** _add your site URL here_

## Features

**Storefront:** search, category / vehicle-model filters, pagination, session cart, product reviews (star ratings, verified badges, "helpful" votes), customer accounts and order history.
**Checkout:** Stripe Checkout (cards) or one-tap WhatsApp order.
**Admin panel:** dashboard, product CRUD with image upload, order management, printable invoices, sales reports, review moderation, stock tracking ,review moderation (Stripe refunds via Stripe Dashboard).
**Reviews** — star ratings, verified badges, helpful votes, full reviews page
**Customers** — register, login, **My Orders**
**Cart** — session-based shopping car

## Tech stack

PHP 8 (PDO, sessions) · MySQL / MariaDB · Stripe PHP SDK · Apache `.htaccess` · vanilla JS / CSS

## Security

- Prepared statements (PDO) and password hashing for admin and customer accounts
- Sensitive folders (`config/`, `includes/`, `vendor/`, `database/`) blocked at the web-server level
- `install.php` and `migrate-*.php` only run from the local machine, never from the internet
- PHP execution disabled inside `uploads/`
- Secrets live in git-ignored `config/stripe.php` and `config/database.php`

## Run locally (XAMPP)

1. Copy the project to `htdocs/modern-lanka`, start **Apache** and **MySQL**.
2. `cp config/database.example.php config/database.php` and set your credentials.
3. _(Optional)_ `cp config/stripe.example.php config/stripe.php` and add Stripe **test** keys. Without them, WhatsApp checkout still works.
4. Visit `http://localhost/modern-lanka/install.php` → **Install Database**.
# Modern Lanka Motors

Premium e-commerce for Toyota Hiace, Dolphin, and HiRoof van parts and modifications (Sri Lanka).



## Requirements

- PHP 8.1+ (PDO MySQL, sessions, fileinfo)
- MySQL 5.7+ or MariaDB 10.3+
- [Composer](https://getcomposer.org/) (Stripe PHP SDK)
- Apache with `mod_rewrite` (XAMPP or similar)


## Stripe setup

1. Copy `config/stripe.example.php` to `config/stripe.php`.
2. Add **publishable**, **secret**, and **webhook** keys from the [Stripe Dashboard](https://dashboard.stripe.com/apikeys).
3. Point a webhook to `https://your-domain/stripe-webhook.php` (events used by this app as configured in code).

## Project structure

```
modern-lanka/
├── admin/                 Admin dashboard (products, orders, reports, invoices)
├── api/                   JSON APIs (cart, reviews)
├── assets/                CSS, JS, images
├── config/                app.php, database.php, stripe.php (examples provided)
├── database/              schema.sql, schema.hosting.sql
├── includes/              bootstrap, DB, shared UI, Stripe gateway
├── logs/                  PHP error log (production)
├── uploads/products/      Product images (writable)
├── vendor/                Composer dependencies (Stripe SDK)
├── index.php              Homepage
├── products.php           Catalog
├── product.php            Product detail + reviews
├── reviews.php            All reviews for a product
├── cart.php               Cart & checkout
├── checkout.php           WhatsApp order handler
├── stripe-checkout.php    Stripe Checkout
├── stripe-success.php     Payment success
├── stripe-webhook.php     Stripe webhooks
├── login.php / register.php / my-orders.php
└── install.php            One-time DB setup (remove after use)
```

## Security notes

- Never commit `config/database.php` or `config/stripe.php`.
- Delete `install.php` after setup.
- Use HTTPS in production; set strong admin and customer passwords.
- `.htaccess` blocks direct access to `config/`, `includes/`, `database/`, `logs/`, `vendor/`, and sensitive file types.
- Production hides PHP errors (`display_errors` off when not on localhost).



Product pages show recent reviews and ratings; `reviews.php` lists all reviews with pagination. Customers can mark reviews helpful via `api/review-helpful.php`. Sample review data is included in the schema seed.

## License

Proprietary — Modern Lanka Motors. All rights reserved.
