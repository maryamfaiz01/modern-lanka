<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? null)) {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? 'pending';
    $allowed = ['pending', 'confirmed', 'processing', 'completed', 'cancelled'];
    if ($orderId && in_array($status, $allowed, true)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT items_json, status, stock_deducted FROM orders WHERE id = ?');
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new RuntimeException('Order not found.');
            }

            $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $orderId]);

            $stockAdjusted = false;
            if ($status === 'confirmed' && (int) $order['stock_deducted'] === 0) {
                $items = json_decode($order['items_json'], true) ?: [];
                $updateStock = $pdo->prepare('UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?');

                foreach ($items as $item) {
                    $productId = (int) ($item['id'] ?? 0);
                    $qty = (int) ($item['qty'] ?? 0);
                    if ($productId > 0 && $qty > 0) {
                        $updateStock->execute([$qty, $productId]);
                    }
                }

                $pdo->prepare('UPDATE orders SET stock_deducted = 1 WHERE id = ?')->execute([$orderId]);
                $stockAdjusted = true;
            }

            $pdo->commit();
            flash('success', $stockAdjusted ? 'Order status updated and stock adjusted.' : 'Order status updated.');
        } catch (Throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', 'Could not update order status. Please try again.');
        }
    }
    header('Location: ' . url('admin/orders.php'));
    exit;
}

$statusFilter = $_GET['status'] ?? '';
$where = '1=1';
$params = [];
if ($statusFilter && in_array($statusFilter, ['pending', 'confirmed', 'processing', 'completed', 'cancelled'], true)) {
    $where = 'status = ?';
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE {$where} ORDER BY created_at DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$adminTitle = 'Orders';
require __DIR__ . '/includes/header.php';
$flash = getFlash();
?>

<div class="admin-page-header admin-page-header--flex">
    <div>
        <h1>Orders</h1>
        <p><?= count($orders) ?> orders</p>
    </div>
    <div class="orders-toolbar">
        <div class="filter-tabs">
            <a href="?" class="filter-tab <?= !$statusFilter ? 'active' : '' ?>">All</a>
            <?php foreach (['pending', 'confirmed', 'processing', 'completed'] as $s): ?>
            <a href="?status=<?= $s ?>" class="filter-tab <?= $statusFilter === $s ? 'active' : '' ?>"><?= ucfirst($s) ?></a>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn--outline btn--sm orders-print-btn" onclick="window.print()">Print Orders</button>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>

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
            <form method="post" class="status-form">
                <?= csrfField() ?>
                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                <select name="status" class="select select--sm" onchange="this.form.submit()">
                    <?php foreach (['pending', 'confirmed', 'processing', 'completed', 'cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="order-card__customer">
            <?php if ($order['customer_name']): ?>
            <span><?= e($order['customer_name']) ?></span>
            <?php endif; ?>
            <?php if ($order['customer_phone']): ?>
            <a href="tel:<?= e($order['customer_phone']) ?>"><?= e($order['customer_phone']) ?></a>
            <?php endif; ?>
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
        <h3>No orders found</h3>
        <p>Orders placed via WhatsApp checkout will appear here.</p>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
