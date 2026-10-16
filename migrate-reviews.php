<?php
declare(strict_types=1);

/**
 * Database Migration: Add Reviews Table
 * Run this once, then delete it
 */

require_once __DIR__ . '/includes/bootstrap.php';

$migrationCompleted = false;
$message = '';
$error = '';

try {
    // Check if reviews table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'reviews'");
    if ($stmt->rowCount() > 0) {
        $message = '✓ Reviews table already exists. No action needed.';
        $migrationCompleted = true;
    } else {
        // Create reviews table
        $pdo->exec("
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
        ");

        // Insert sample reviews
        $pdo->exec("
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
        ");

        $message = '✓ Reviews table created and populated with 10 sample reviews!';
        $migrationCompleted = true;
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration | Modern Lanka Motors</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif; background: #060608; color: #f4f2ef; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .container { max-width: 500px; width: 100%; }
        .card { background: rgba(18,18,22,0.9); border: 1px solid rgba(255,255,255,0.06); border-radius: 20px; padding: 40px; }
        h1 { font-size: 1.8rem; margin-bottom: 24px; font-weight: 700; }
        .alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; }
        .alert-success { background: rgba(74, 222, 128, 0.15); border: 1px solid rgba(74, 222, 128, 0.3); color: #4ade80; }
        .alert-error { background: rgba(248, 113, 113, 0.15); border: 1px solid rgba(248, 113, 113, 0.3); color: #f87171; }
        .alert-info { background: rgba(201, 162, 39, 0.15); border: 1px solid rgba(201, 162, 39, 0.3); color: #e8c547; }
        .btn { display: inline-block; padding: 12px 24px; background: #c9a227; color: #060608; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.3s ease; }
        .btn:hover { background: #e8c547; }
        .code { background: #0c0c0f; border: 1px solid rgba(255,255,255,0.06); padding: 12px; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 0.85rem; margin: 16px 0; overflow-x: auto; }
        small { color: #9a9690; display: block; margin-top: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>📊 Database Migration</h1>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
            <?php elseif ($migrationCompleted): ?>
            <div class="alert alert-success">
                <strong>Success!</strong> <?= htmlspecialchars($message) ?>
            </div>

            <p style="margin-bottom: 16px; color: #9a9690; line-height: 1.6;">
                The reviews table has been created with 10 sample customer reviews. You can now view product reviews on the product detail pages.
            </p>

            <div style="background: rgba(201, 162, 39, 0.08); border: 1px solid rgba(201, 162, 39, 0.2); padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                <strong style="color: #e8c547;">Next Steps:</strong>
                <ul style="margin-top: 8px; margin-left: 20px; color: #9a9690;">
                    <li>Visit a product page to see the reviews section</li>
                    <li>Click "View All Reviews" to see the full reviews page</li>
                    <li>Delete this migration file when done</li>
                </ul>
            </div>

            <a href="<?= url('products.php') ?>" class="btn">View Products</a>
            <small>Or delete this file (migrate-reviews.php) once complete.</small>
            <?php else: ?>
            <div class="alert alert-info">
                Running migration...
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
