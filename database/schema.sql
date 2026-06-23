-- Modern Lanka Motors - Database Schema
-- Run: mysql -u root -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS modern_lanka_moters
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE modern_lanka_moters;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL UNIQUE,
  description TEXT,
  short_description VARCHAR(500),
  price DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  compare_price DECIMAL(12, 2) NULL,
  category VARCHAR(50) NOT NULL,
  vehicle_model VARCHAR(50) NOT NULL DEFAULT 'universal',
  image VARCHAR(255) DEFAULT NULL,
  gallery JSON DEFAULT NULL,
  stock INT NOT NULL DEFAULT 0,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  views INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_category (category),
  INDEX idx_vehicle (vehicle_model),
  INDEX idx_featured (is_featured),
  INDEX idx_active (is_active),
  FULLTEXT idx_search (name, short_description, description)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_ref VARCHAR(20) NOT NULL UNIQUE,
  customer_name VARCHAR(100),
  customer_phone VARCHAR(20),
  customer_email VARCHAR(120),
  items_json JSON NOT NULL,
  subtotal DECIMAL(12, 2) NOT NULL,
  notes TEXT,
  status ENUM('pending', 'confirmed', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
  stock_deducted TINYINT(1) NOT NULL DEFAULT 0,
  source ENUM('whatsapp', 'admin', 'website') DEFAULT 'whatsapp',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  INDEX idx_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS site_settings (
  setting_key VARCHAR(80) PRIMARY KEY,
  setting_value TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin: username admin / password password
-- Change immediately after first login
INSERT IGNORE INTO admins (username, email, password_hash, full_name) VALUES
('admin', 'admin@modernlankamoters.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator');

INSERT IGNORE INTO products (sku, name, slug, description, short_description, price, compare_price, category, vehicle_model, stock, is_featured, is_active) VALUES
('ML-HC-001', 'Hiace Premium Front Bumper Kit', 'hiace-premium-front-bumper-kit',
 'Full replacement front bumper assembly with integrated fog light housings. UV-resistant ABS construction, factory-fit mounting points for Toyota Hiace KDH200 series.',
 'Premium ABS front bumper with fog light cutouts for Hiace KDH200.', 89500.00, 98000.00, 'body-kits', 'hiace', 12, 1, 1),
('ML-HC-002', 'Hiace LED Headlight Assembly Pair', 'hiace-led-headlight-assembly-pair',
 'Projector-style LED headlight pair with DRL strip. Plug-and-play wiring harness included. ECE compliant beam pattern.',
 'Projector LED headlights with DRL for Toyota Hiace.', 125000.00, NULL, 'lighting', 'hiace', 8, 1, 1),
('ML-DP-001', 'Dolphin Roof Spoiler with Brake Light', 'dolphin-roof-spoiler-brake-light',
 'Aerodynamic roof spoiler with integrated LED third brake light. Painted-ready primer finish.',
 'Roof spoiler with integrated LED brake light for Dolphin.', 42000.00, 48000.00, 'body-kits', 'dolphin', 15, 1, 1),
('ML-DP-002', 'Dolphin Custom Seat Cover Set (7-Seater)', 'dolphin-custom-seat-cover-7-seater',
 'Premium diamond-stitch synthetic leather seat covers. Full 7-seater configuration with armrest and headrest covers.',
 'Diamond-stitch leather-look seat covers for 7-seater Dolphin.', 35000.00, NULL, 'interior', 'dolphin', 20, 0, 1),
('ML-HR-001', 'HiRoof High Roof Interior Panel Kit', 'hiroof-high-roof-interior-panel-kit',
 'Complete interior trim panel set for high-roof conversions. Sound-dampening backing included.',
 'Interior panel kit for HiRoof high-roof conversions.', 156000.00, 175000.00, 'interior', 'hiroof', 5, 1, 1),
('ML-HR-002', 'HiRoof Side Window Conversion Kit', 'hiroof-side-window-conversion-kit',
 'Sliding side window kit with tinted glass and rubber seals. Professional installation recommended.',
 'Sliding tinted side windows for HiRoof conversion.', 98000.00, NULL, 'modifications', 'hiroof', 6, 0, 1),
('ML-UN-001', 'Universal LED Interior Ambient Kit', 'universal-led-interior-ambient-kit',
 'App-controlled RGB ambient lighting kit. 12-piece strip set with Bluetooth controller.',
 'Bluetooth RGB ambient interior lighting — fits all models.', 18500.00, 22000.00, 'lighting', 'universal', 50, 1, 1),
('ML-HC-003', 'Hiace Lowering Spring Set (Front & Rear)', 'hiace-lowering-spring-set',
 'Progressive rate lowering springs. 35mm drop. Maintains load capacity for passenger variants.',
 '35mm progressive lowering springs for Hiace.', 28000.00, NULL, 'suspension', 'hiace', 18, 0, 1);

INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
('hero_title', 'Engineered for Sri Lanka\'s Finest Vans'),
('hero_subtitle', 'Genuine-quality spare parts and bespoke modifications for Toyota Hiace, Dolphin & HiRoof.'),
('announcement', 'Free delivery within Colombo for orders above Rs. 50,000');
