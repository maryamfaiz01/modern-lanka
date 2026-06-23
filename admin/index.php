<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
requireAdmin();

$stats = dashboardStats($pdo);

$recentOrders = $pdo->query(
    'SELECT * FROM orders ORDER BY created_at DESC LIMIT 8'
)->fetchAll();

$lowStock = $pdo->query(
    'SELECT id, name, sku, stock FROM products WHERE stock <= 5 AND is_active = 1 ORDER BY stock ASC LIMIT 6'
)->fetchAll();

$adminTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
$flash = getFlash();
?>

<div class="admin-page-header">
    <h1>Dashboard</h1>
    <p>Welcome back, <?= e($_SESSION['admin_name'] ?? 'Admin') ?></p>
</div>

<?php if ($flash): ?>
<div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card glass-card">
        <span class="stat-card__label">Total Products</span>
        <span class="stat-card__value"><?= $stats['products'] ?></span>
        <span class="stat-card__meta"><?= $stats['active_products'] ?> active</span>
    </div>
    <div class="stat-card glass-card">
        <span class="stat-card__label">Orders</span>
        <span class="stat-card__value"><?= $stats['orders'] ?></span>
        <span class="stat-card__meta"><?= $stats['pending_orders'] ?> pending</span>
    </div>
    <div class="stat-card glass-card stat-card--warning">
        <span class="stat-card__label">Low Stock</span>
        <span class="stat-card__value"><?= $stats['low_stock'] ?></span>
        <span class="stat-card__meta">≤ 5 units</span>
    </div>
    <div class="stat-card glass-card stat-card--gold">
        <span class="stat-card__label">Revenue</span>
        <span class="stat-card__value"><?= formatPrice($stats['revenue'], $config) ?></span>
        <span class="stat-card__meta">All non-cancelled</span>
    </div>
</div>

<div class="admin-grid-2">
    <section class="admin-panel glass-card">
        <div class="admin-panel__header">
            <h2>Recent Orders</h2>
            <a href="<?= url('admin/orders.php') ?>">View all →</a>
        </div>
        <?php if (empty($recentOrders)): ?>
        <p class="text-muted">No orders yet.</p>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr><th>Ref</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php foreach ($recentOrders as $order): ?>
                <tr>
                    <td><strong><?= e($order['order_ref']) ?></strong></td>
                    <td><?= e($order['customer_name'] ?? '—') ?></td>
                    <td><?= formatPrice((float) $order['subtotal'], $config) ?></td>
                    <td><span class="status-badge status-badge--<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span></td>
                    <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>

    <section class="admin-panel glass-card">
        <div class="admin-panel__header">
            <h2>Low Stock Alert</h2>
            <a href="<?= url('admin/products.php') ?>">Manage →</a>
        </div>
        <?php if (empty($lowStock)): ?>
        <p class="text-muted">All products well stocked.</p>
        <?php else: ?>
        <ul class="low-stock-list">
            <?php foreach ($lowStock as $p): ?>
            <li>
                <span><?= e($p['name']) ?></span>
                <span class="stock-pill stock-pill--low"><?= (int) $p['stock'] ?> left</span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>
</div>

<div class="admin-quick-actions">
    <a href="<?= url('admin/product-form.php') ?>" class="btn btn--primary">+ Add Product</a>
    <a href="<?= url('admin/products.php') ?>" class="btn btn--outline">Manage Products</a>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
