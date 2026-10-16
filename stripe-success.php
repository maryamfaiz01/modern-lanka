<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/Stripe.php';

$sessionId = trim((string) ($_GET['session_id'] ?? ''));

if ($sessionId === '') {
    flash('error', 'Invalid or missing payment session ID.');
    header('Location: ' . url('cart.php'));
    exit;
}

// Retrieve checkout session from Stripe
try {
    $stripe = new StripeGateway();
    $session = $stripe->retrieveCheckoutSession($sessionId);
} catch (Throwable $e) {
    logStripeError('Failed to retrieve Stripe session ' . $sessionId, $e);
    flash('error', 'Payment session not found or expired.');
    header('Location: ' . url('cart.php'));
    exit;
}

// Get order from session metadata
$orderId = (int) ($session->metadata['order_id'] ?? 0);
$orderRef = (string) ($session->metadata['order_ref'] ?? '');

if ($orderId <= 0) {
    logStripeError('Order ID missing in Stripe session metadata: ' . $sessionId);
    flash('error', 'Order not found in payment session.');
    header('Location: ' . url('cart.php'));
    exit;
}

// Load order from database
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    logStripeError('Order not found in database for ID: ' . $orderId);
    flash('error', 'Order not found.');
    header('Location: ' . url('cart.php'));
    exit;
}

// Confirm payment when webhooks are not configured (typical on local XAMPP)
if (
    ($session->payment_status ?? '') === 'paid'
    && ($order['payment_status'] ?? 'unpaid') !== 'paid'
) {
    try {
        $pdo->beginTransaction();

        $pdo->prepare(
            'UPDATE orders SET payment_status = ?, status = ?, stripe_customer_email = COALESCE(?, stripe_customer_email) WHERE id = ?'
        )->execute([
            'paid',
            'confirmed',
            $session->customer_details?->email ?? null,
            (int) $orderId,
        ]);

        if ((int) $order['stock_deducted'] === 0) {
            $items = json_decode($order['items_json'], true) ?: [];
            $updateStock = $pdo->prepare('UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?');
            foreach ($items as $item) {
                $productId = (int) ($item['id'] ?? 0);
                $qty = (int) ($item['qty'] ?? 0);
                if ($productId > 0 && $qty > 0) {
                    $updateStock->execute([$qty, $productId]);
                }
            }
            $pdo->prepare('UPDATE orders SET stock_deducted = 1 WHERE id = ?')->execute([(int) $orderId]);
        }

        $pdo->commit();

        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([(int) $orderId]);
        $order = $stmt->fetch() ?: $order;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('stripe-success order finalize failed: ' . $e->getMessage());
    }
}

// Clear cart
$_SESSION['cart'] = [];

$pageTitle = 'Payment Successful';
require __DIR__ . '/includes/header.php';
?>

<div class="payment-result payment-success">
    <div class="result-container">
        <div class="result-icon success">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>
        
        <h1>Payment Successful!</h1>
        <p class="result-message">Thank you for your order. Your payment has been processed successfully.</p>
        
        <div class="order-details glass-card">
            <h2>Order Details</h2>
            <div class="detail-row">
                <span>Order Reference</span>
                <span class="order-ref"><?= e($orderRef ?: $order['order_ref']) ?></span>
            </div>
            <p class="order-note">You will receive an order confirmation email shortly with your order details.</p>
        </div>

        <div class="result-actions">
            <a href="<?= url('products.php') ?>" class="btn btn--primary btn--lg">Continue Shopping</a>
            <a href="<?= url('cart.php') ?>" class="btn btn--ghost btn--lg">View Cart</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
