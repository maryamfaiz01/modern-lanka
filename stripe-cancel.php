<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$orderId = (int) ($_GET['order_id'] ?? 0);

if ($orderId <= 0) {
    flash('error', 'Invalid order ID.');
    header('Location: ' . url('cart.php'));
    exit;
}

// Load order from database
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    flash('error', 'Order not found.');
    header('Location: ' . url('cart.php'));
    exit;
}

$pageTitle = 'Payment Cancelled';
require __DIR__ . '/includes/header.php';
?>

<div class="payment-result payment-cancelled">
    <div class="result-container">
        <div class="result-icon cancelled">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
        </div>
        
        <h1>Payment Cancelled</h1>
        <p class="result-message">Your payment was cancelled. No charges were made to your card.</p>
        
        <div class="order-details glass-card">
            <h2>Order Details</h2>
            <div class="detail-row">
                <span>Order Reference</span>
                <span class="order-ref"><?= e($order['order_ref']) ?></span>
            </div>
            <p class="order-note">Your order has been saved in your cart. You can complete the payment later or modify your order.</p>
        </div>

        <div class="result-actions">
            <a href="<?= url('cart.php') ?>" class="btn btn--primary btn--lg">Return to Cart</a>
            <a href="<?= url('products.php') ?>" class="btn btn--ghost btn--lg">Continue Shopping</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
