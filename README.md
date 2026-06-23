# Modern Lanka Moters

Premium automotive e-commerce website and admin system for Toyota Hiace, Dolphin, and HiRoof van spare parts and modifications — Sri Lanka.

## Features

- **Dark luxury UI** with glassmorphism, gold accents, and smooth animations
- **Top navbar + sidebar** for vehicle/category navigation
- **Product catalog** with search, filters, sorting, and pagination
- **Shopping cart** with session-based persistence
- **WhatsApp ordering** — checkout opens WhatsApp with pre-filled order details
- **Admin dashboard** — stats, orders, low-stock alerts
- **Product management** — full CRUD with image uploads
- **Responsive design** — mobile, tablet, and desktop

## Requirements

- PHP 8.1+ (with PDO MySQL, fileinfo, sessions)
- MySQL 5.7+ or MariaDB 10.3+
- Apache (XAMPP/WAMP) or nginx + PHP-FPM

## Quick Start (XAMPP on Windows)

1. Copy this folder to `C:\xampp\htdocs\modern-lanka` (recommended — no spaces in URL).

   Or use the folder as-is: `C:\xampp\htdocs\Modern lanka`  
   URL: `http://localhost/Modern%20lanka/` (note the `%20` for the space)

2. Start **Apache** and **MySQL** in XAMPP Control Panel.

3. Edit `config/database.php` if your MySQL credentials differ:
   ```php
   'username' => 'root',
   'password' => '',
   ```

4. Edit `config/app.php` — set your real **WhatsApp number** (country code, no +):
   ```php
   'whatsapp' => '94771234567',
   'whatsapp_display' => '+94 77 123 4567',
   ```

5. Open in browser:
   ```
   http://localhost/modern-lanka/install.php
   ```
   Click **Install Database**, then **delete `install.php`**.

6. Visit the site:
   - **Website:** `http://localhost/modern-lanka/`
   - **Admin:** `http://localhost/modern-lanka/admin/login.php`
     - Username: `admin`
     - Password: `password`  
     **Change this immediately** after first login.

## Manual Database Setup

```bash
mysql -u root -p < database/schema.sql
```

## Project Structure

```
├── admin/              Admin dashboard & product management
├── api/                Cart API (JSON)
├── assets/             CSS, JS, images
├── config/             App & database settings
├── database/           SQL schema + seed data
├── includes/           Shared PHP (header, functions, DB)
├── uploads/products/   Product images
├── index.php           Homepage
├── products.php        Catalog
├── product.php         Product detail
├── cart.php            Shopping cart
└── checkout.php        WhatsApp order handler
```

## Customization

| Setting | File |
|--------|------|
| Business name, WhatsApp, contact | `config/app.php` |
| Categories & vehicle models | `config/app.php` |
| Database credentials | `config/database.php` |
| Hero text & announcement | Admin → or `site_settings` table |

## Security Notes

- Delete `install.php` after setup
- Change default admin password
- Use HTTPS in production
- Keep `config/` and `includes/` outside web root if possible (via vhost)

## License

Proprietary — Modern Lanka Moters. All rights reserved.
