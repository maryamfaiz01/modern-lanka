<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = defined('BASE_URL') ? BASE_URL : '';
    $path = ltrim($path, '/');
    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }
    return ($base === '' ? '' : $base) . '/' . $path;
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function formatPrice(float $amount, array $config): string
{
    return $config['currency_symbol'] . ' ' . number_format($amount, 0, '.', ',');
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'product';
}

function cartCount(): int
{
    $count = 0;
    foreach ($_SESSION['cart'] ?? [] as $item) {
        $count += (int) ($item['qty'] ?? 0);
    }
    return $count;
}

function cartSubtotal(PDO $pdo): float
{
    $total = 0.0;
    foreach ($_SESSION['cart'] ?? [] as $id => $item) {
        $stmt = $pdo->prepare('SELECT price FROM products WHERE id = ? AND is_active = 1');
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch();
        if ($row) {
            $total += (float) $row['price'] * (int) $item['qty'];
        }
    }
    return $total;
}

function getCartItems(PDO $pdo): array
{
    $items = [];
    foreach ($_SESSION['cart'] ?? [] as $id => $cartItem) {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND is_active = 1');
        $stmt->execute([(int) $id]);
        $product = $stmt->fetch();
        if ($product) {
            $qty = (int) $cartItem['qty'];
            $items[] = array_merge($product, [
                'qty' => $qty,
                'line_total' => (float) $product['price'] * $qty,
            ]);
        }
    }
    return $items;
}

function generateOrderRef(): string
{
    return 'ML' . strtoupper(substr(uniqid(), -8));
}

function productImageUrl(?string $image, array $config): string
{
    if ($image && file_exists(ROOT_PATH . '/uploads/products/' . $image)) {
        return url('uploads/products/' . $image);
    }
    return asset('images/product-placeholder.svg');
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function requireAdmin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . url('admin/login.php'));
        exit;
    }
}

function getLoggedInCustomer(PDO $pdo): ?array
{
    if (empty($_SESSION['customer_id'])) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT id, name, phone FROM customers WHERE id = ?');
    $stmt->execute([(int) $_SESSION['customer_id']]);
    $customer = $stmt->fetch();
    return $customer ?: null;
}

function requireCustomer(): void
{
    if (empty($_SESSION['customer_id'])) {
        header('Location: ' . url('login.php'));
        exit;
    }
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(?string $token): bool
{
    return $token !== null && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function buildWhatsAppMessage(array $items, float $subtotal, array $config, ?string $customerName = null, ?string $notes = null): string
{
    $lines = ["*Order — {$config['name']}*", ''];
    if ($customerName) {
        $lines[] = "Customer: {$customerName}";
    }
    $lines[] = '---';
    foreach ($items as $item) {
        $lines[] = sprintf(
            '• %s x%d — %s',
            $item['name'],
            $item['qty'],
            formatPrice($item['line_total'], $config)
        );
    }
    $lines[] = '---';
    $lines[] = '*Total: ' . formatPrice($subtotal, $config) . '*';
    if ($notes) {
        $lines[] = '';
        $lines[] = 'Notes: ' . $notes;
    }
    $lines[] = '';
    $lines[] = '_Sent via modernlankamoters.lk_';
    return implode("\n", $lines);
}

function whatsappLink(string $phone, string $message): string
{
    return 'https://wa.me/' . preg_replace('/\D/', '', $phone) . '?text=' . rawurlencode($message);
}

function paginate(int $total, int $perPage, int $currentPage): array
{
    $totalPages = max(1, (int) ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
    ];
}

function categoryLabel(string $key, array $config): string
{
    return $config['categories'][$key] ?? ucfirst(str_replace('-', ' ', $key));
}

function vehicleLabel(string $key, array $config): string
{
    return $config['vehicle_models'][$key] ?? ucfirst($key);
}

function dashboardStats(PDO $pdo): array
{
    $stats = [];
    $stats['products'] = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $stats['active_products'] = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();
    $stats['low_stock'] = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE stock <= 5 AND is_active = 1')->fetchColumn();
    $stats['orders'] = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $stats['pending_orders'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
    $stats['revenue'] = (float) $pdo->query("SELECT COALESCE(SUM(subtotal), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
    return $stats;
}
