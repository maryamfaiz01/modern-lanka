<?php
declare(strict_types=1);

/**
 * One-time migration — delete this file after running once.
 */
$config = require __DIR__ . '/config/database.php';

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    $config['host'],
    $config['dbname'],
    $config['charset']
);

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = ? AND table_name = ? AND column_name = ?'
    );
    $stmt->execute([$config['dbname'], 'orders', 'stock_deducted']);
    $exists = (int) $stmt->fetchColumn() > 0;

    if ($exists) {
        $message = 'Column stock_deducted already exists on orders — nothing to do.';
    } else {
        $pdo->exec('ALTER TABLE orders ADD COLUMN stock_deducted TINYINT(1) NOT NULL DEFAULT 0');
        $message = 'Added stock_deducted column to orders successfully.';
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Migration failed: ' . htmlspecialchars($e->getMessage());
    exit(1);
}

header('Content-Type: text/plain; charset=utf-8');
echo $message . PHP_EOL;
echo 'Delete migrate-stock.php from the project root now that migration is complete.' . PHP_EOL;
