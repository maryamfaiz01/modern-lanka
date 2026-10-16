-- Modern Lanka Motors — hosting import (InfinityFree / shared hosting)
-- Select your database in phpMyAdmin, then import this file.
-- Same as database/schema.sql without CREATE DATABASE / USE (migrations merged into main schema).

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
  payment_status ENUM('unpaid', 'paid', 'partial', 'cancelled', 'failed', 'chargedback') DEFAULT 'unpaid',
  stripe_payment_intent_id VARCHAR(255) NULL,
  stripe_customer_email VARCHAR(255) NULL,
  invoice_number VARCHAR(30) NULL UNIQUE,
  invoice_date DATE NULL,
  delivery_charge DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  discount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  tax DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
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

CREATE TABLE IF NOT EXISTS reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  customer_name VARCHAR(100) NOT NULL,
  customer_email VARCHAR(120),
  customer_phone VARCHAR(20),
  customer_avatar VARCHAR(255),
  rating TINYINT UNSIGNED NOT NULL DEFAULT 5 CHECK (rating >= 1 AND rating <= 5),
  review_title VARCHAR(150),
  review_text TEXT,
  review_images JSON,
  is_verified_purchase TINYINT(1) NOT NULL DEFAULT 1,
  is_approved TINYINT(1) NOT NULL DEFAULT 1,
  helpful_count INT UNSIGNED DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_product (product_id),
  INDEX idx_rating (rating),
  INDEX idx_created (created_at),
  INDEX idx_approved (is_approved)
) ENGINE=InnoDB;

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

INSERT IGNORE INTO reviews (product_id, customer_name, customer_email, customer_phone, customer_avatar, rating, review_title, review_text, is_verified_purchase, is_approved, created_at) VALUES
(1, 'Rajith Perera', 'rajith.perera@email.com', '+94771234567', 'https://i.pravatar.cc/150?img=1', 5, 'Excellent Quality & Perfect Fit!', 'The front bumper kit quality exceeded my expectations. Installation was straightforward and fits perfectly on my Hiace. The fog light housings look premium. Highly recommended!', 1, 1, DATE_SUB(NOW(), INTERVAL 15 DAY)),
(1, 'Chaminda Silva', 'chaminda.silva@email.com', '+94772345678', 'https://i.pravatar.cc/150?img=2', 5, 'Best Investment for My Van', 'Purchased this bumper kit 6 months ago. Still looks pristine with zero wear. UV resistance is genuine. Worth every rupee!', 1, 1, DATE_SUB(NOW(), INTERVAL 28 DAY)),
(1, 'Anura Fernando', 'anura.fernando@email.com', '+94773456789', 'https://i.pravatar.cc/150?img=3', 4, 'Great Product, Could Be Better', 'Good quality bumper. Installation took longer than expected but end result is professional looking. Minor scuff on one corner during delivery but nothing serious.', 1, 1, DATE_SUB(NOW(), INTERVAL 42 DAY)),
(1, 'Lakshan Weerasinghe', 'lakshan.w@email.com', '+94774567890', 'https://i.pravatar.cc/150?img=4', 5, 'Transformed My Hiace!', 'Complete transformation! The bumper gives my van an aggressive, modern look. Paint quality is excellent. Customer service was fantastic throughout the process.', 1, 1, DATE_SUB(NOW(), INTERVAL 55 DAY)),
(2, 'Dinesh Kumar', 'dinesh.kumar@email.com', '+94775678901', 'https://i.pravatar.cc/150?img=5', 5, 'Lighting Perfection', 'LED headlights are incredibly bright and well-designed. The DRL strip looks sophisticated. Installation instructions were clear. Night driving is now so much safer and stylish!', 1, 1, DATE_SUB(NOW(), INTERVAL 20 DAY)),
(2, 'Priya Wijesundara', 'priya.wijes@email.com', '+94776789012', 'https://i.pravatar.cc/150?img=6', 5, 'Professional Grade LEDs', 'These aren\'t cheap knock-offs. The build quality is genuinely professional. ECE compliance adds confidence. Been using for 3 months without issues. Excellent purchase!', 1, 1, DATE_SUB(NOW(), INTERVAL 35 DAY)),
(3, 'Mahinda Jayasinghe', 'mahinda.jay@email.com', '+94777890123', 'https://i.pravatar.cc/150?img=7', 4, 'Looks Amazing, Worth It', 'The roof spoiler with LED brake light looks premium on my Dolphin. The aerodynamic design is noticeable. Fitment was perfect. Highly satisfied overall.', 1, 1, DATE_SUB(NOW(), INTERVAL 12 DAY)),
(3, 'Sanjana Kumari', 'sanjana.k@email.com', '+94778901234', 'https://i.pravatar.cc/150?img=8', 5, 'Modern Transformation', 'My Dolphin looks like a different van now! The spoiler is beautifully integrated. LED brake light is bright and eye-catching. Friends keep asking where I got it!', 1, 1, DATE_SUB(NOW(), INTERVAL 48 DAY)),
(4, 'Harsha Mendis', 'harsha.mendis@email.com', '+94779012345', 'https://i.pravatar.cc/150?img=9', 5, 'Luxury Comfort for Family Trips', 'These seat covers completely changed the interior. Premium diamond-stitch leather look is stunning. Comfort has improved significantly. Installation was hassle-free.', 1, 1, DATE_SUB(NOW(), INTERVAL 25 DAY)),
(7, 'Geeth Rajapaksha', 'geeth.r@email.com', '+94770123456', 'https://i.pravatar.cc/150?img=10', 5, 'Amazing Ambiance!', 'The RGB ambient lighting kit is a game-changer. App control is intuitive. Can sync lighting with music. Quality is outstanding. Best interior upgrade ever!', 1, 1, DATE_SUB(NOW(), INTERVAL 18 DAY));
