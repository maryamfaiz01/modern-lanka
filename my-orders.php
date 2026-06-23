<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
requireCustomer();

$customer = getLoggedInCustomer($pdo);
if (!$customer) {
    unset($_SESSION['customer_id']);
    header('Location: ' . url('login.php'));
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM orders WHERE customer_phone = ? ORDER BY created_at DESC');
$stmt->execute([$customer['phone']]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Orders';
$pageDescription = 'View your past orders.';
require __DIR__ . '/includes/header.php';
?>

<div class="page-header animate-in">
    <div class="page-header__breadcrumb">
        <a href="<?= url('index.php') ?>">Home</a>
        <span>/</span>
        <span>My Orders</span>
    </div>
    <h1>My Orders</h1>
    <p><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?> for <?= e($customer['phone']) ?></p>
</div>

<div class="orders-list">
    <?php foreach ($orders as $order):
        $items = json_decode($order['items_json'], true) ?: [];
    ?>
    <div class="order-card glass-card">
        <div class="order-card__header">
            <div>
                <strong><?= e($order['order_ref']) ?></strong>
                <span class="order-date"><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></span>
            </div>
        </div>
        <ul class="order-items">
            <?php foreach ($items as $item): ?>
            <li>
                <span><?= e($item['name']) ?> × <?= (int) $item['qty'] ?></span>
                <span><?= formatPrice((float) ($item['line_total'] ?? 0), $config) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($order['notes']): ?>
        <p class="order-notes"><strong>Notes:</strong> <?= e($order['notes']) ?></p>
        <?php endif; ?>
        <div class="order-card__footer">
            <span class="order-total"><?= formatPrice((float) $order['subtotal'], $config) ?></span>
            <span class="status-badge status-badge--<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($orders)): ?>
    <div class="empty-state glass-card">
        <h3>No orders yet</h3>
        <p>Orders placed with your phone number will appear here.</p>
        <a href="<?= url('products.php') ?>" class="btn btn--primary">Browse Catalog</a>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
