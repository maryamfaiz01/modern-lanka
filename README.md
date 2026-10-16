# Modern Lanka Motors

Premium e-commerce for Toyota Hiace, Dolphin, and HiRoof van parts and modifications (Sri Lanka).

## Features

- **Catalog** — search, filters, categories, vehicle models, pagination
- **Cart** — session-based shopping cart
- **Checkout** — WhatsApp orders and **Stripe** card payments
- **Reviews** — star ratings, verified badges, helpful votes, full reviews page
- **Customers** — register, login, **My Orders**
- **Admin** — dashboard, product CRUD, orders, reports, invoices, stock, review moderation (Stripe refunds via Stripe Dashboard)

## Requirements

- PHP 8.1+ (PDO MySQL, sessions, fileinfo)
- MySQL 5.7+ or MariaDB 10.3+
- [Composer](https://getcomposer.org/) (Stripe PHP SDK)
- Apache with `mod_rewrite` (XAMPP or similar)

## Local setup (XAMPP)

1. Clone or copy the project to `C:\xampp\htdocs\modern-lanka`.
2. Start **Apache** and **MySQL** in XAMPP.
3. Copy `config/database.example.php` to `config/database.php` and set MySQL credentials.
4. Copy `config/stripe.example.php` to `config/stripe.php` and add your Stripe keys (optional for local WhatsApp-only testing).
5. Run `composer install` in the project root (if `vendor/` is missing).
6. Open `http://localhost/modern-lanka/install.php`, run **Install Database**, then **delete `install.php`**.
7. Storefront: `http://localhost/modern-lanka/`  
   Admin: `http://localhost/modern-lanka/admin/login.php` — change the default admin password after first login.

Edit `config/app.php` for WhatsApp number, contact details, categories, and vehicle models.

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

## Deploy on InfinityFree

1. Create a **MySQL database** in the InfinityFree control panel (note host, database name, username, password).
2. In **phpMyAdmin**, import `database/schema.hosting.sql` (no `CREATE DATABASE` / `USE` — select your database first).
3. Upload the full project to `htdocs/` (include the `vendor/` folder, or run `composer install` on a machine that can upload the result).
4. Copy `config/database.example.php` to `config/database.php` and set InfinityFree MySQL host and credentials.
5. Copy `config/stripe.example.php` to `config/stripe.php` with live or test keys as needed.
6. Ensure `uploads/products/` and `logs/` exist and are writable.
7. **Do not** upload `install.php` to production, or delete it immediately after a one-time local test import.
8. Open your site URL; admin at `/admin/login.php`.

Paths and asset URLs work at the domain root or in a subfolder (base URL is detected automatically).

## Reviews (summary)

Product pages show recent reviews and ratings; `reviews.php` lists all reviews with pagination. Customers can mark reviews helpful via `api/review-helpful.php`. Sample review data is included in the schema seed.

## License

Proprietary — Modern Lanka Motors. All rights reserved.
