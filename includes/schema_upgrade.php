<?php
declare(strict_types=1);

function ensureOrdersSchema(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        $pdo->query('SELECT invoice_number FROM orders LIMIT 1');
        return;
    } catch (PDOException) {
        // Column missing — apply upgrades below.
    }

    $columns = [
        'invoice_number VARCHAR(30) NULL UNIQUE',
        'invoice_date DATE NULL',
        'delivery_charge DECIMAL(12, 2) NOT NULL DEFAULT 0.00',
        'discount DECIMAL(12, 2) NOT NULL DEFAULT 0.00',
        'tax DECIMAL(12, 2) NOT NULL DEFAULT 0.00',
    ];

    foreach ($columns as $columnDef) {
        try {
            $pdo->exec('ALTER TABLE orders ADD COLUMN ' . $columnDef);
        } catch (PDOException $e) {
            if (!str_contains($e->getMessage(), 'Duplicate column')) {
                error_log('Schema upgrade failed: ' . $e->getMessage());
            }
        }
    }
}
