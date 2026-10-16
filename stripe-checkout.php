<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/Stripe.php';

// Check if Stripe configuration is available
$stripeConfig = file_exists(__DIR__ . '/config/stripe.php')
    ? require __DIR__ . '/config/stripe.php'
    : null;

if (!stripeIsConfigured($stripeConfig)) {
    logStripeError('Stripe checkout accessed but Stripe keys are missing, incomplete, or invalid in config/stripe.php.');
    flash('error', 'Online card payment is currently unavailable. Please place your order via WhatsApp or contact support.');
    header('Location: ' . url('cart.php'));
    exit;
}

// ── HANDLE POST: Checkout from Cart ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Invalid request session. Please try again.');
        header('Location: ' . url('cart.php'));
        exit;
    }

    $cartItems = getCartItems($pdo);
    if (empty($cartItems)) {
        flash('error', 'Your cart is empty.');
        header('Location: ' . url('cart.php'));
        exit;
    }

    $customerName  = trim($_POST['customer_name'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');
    $notes         = trim($_POST['notes'] ?? '');
    $loggedInCustomer = getLoggedInCustomer($pdo);

    if ($loggedInCustomer) {
        if ($customerName === '') {
            $customerName = (string) ($loggedInCustomer['name'] ?? '');
        }
        if ($customerPhone === '') {
            $customerPhone = (string) ($loggedInCustomer['phone'] ?? '');
        }
    }

    $subtotal = array_sum(array_column($cartItems, 'line_total'));
    $orderRef = generateOrderRef();

    $itemsForDb = array_map(fn($i) => [
        'id'         => (int) $i['id'],
        'name'       => $i['name'],
        'sku'        => $i['sku'],
        'qty'        => (int) $i['qty'],
        'price'      => (float) $i['price'],
        'line_total' => (float) $i['line_total'],
    ], $cartItems);

    try {
        // Insert order with pending status
        $pdo->prepare(
            'INSERT INTO orders
             (order_ref, customer_name, customer_phone, customer_email, items_json, subtotal, notes, status, payment_status, source)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $orderRef,
            $customerName,
            $customerPhone,
            $customerEmail !== '' ? $customerEmail : null,
            json_encode($itemsForDb),
            $subtotal,
            $notes,
            'pending',
            'unpaid',
            'website',
        ]);

        $orderId = (int) $pdo->lastInsertId();

        // Build line items for Stripe Checkout
        $currency = strtolower($stripeConfig['currency'] ?? 'lkr');
        $lineItems = [];
        foreach ($itemsForDb as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => $item['name'],
                    ],
                    'unit_amount' => (int) round($item['price'] * 100), // Convert to cents
                ],
                'quantity' => $item['qty'],
            ];
        }

        // Create Stripe Checkout Session
        $stripe = new StripeGateway($stripeConfig);
        $successUrl = url('stripe-success.php?session_id={CHECKOUT_SESSION_ID}');
        $cancelUrl = url('stripe-cancel.php?order_id=' . $orderId);

        $session = $stripe->createCheckoutSession(
            $lineItems,
            $successUrl,
            $cancelUrl,
            [
                'order_id' => (string) $orderId,
                'order_ref' => $orderRef,
            ],
            $customerEmail !== '' ? $customerEmail : null
        );

        // Update order with checkout session ID
        $pdo->prepare('UPDATE orders SET stripe_payment_intent_id = ? WHERE id = ?')
            ->execute([$session->id, $orderId]);

        // Redirect to Stripe's hosted checkout page
        header('Location: ' . $session->url);
        exit;
    } catch (Throwable $e) {
        logStripeError('Stripe Checkout Session creation failed for order ' . $orderRef, $e);
        flash('error', 'Unable to open Stripe checkout: ' . $e->getMessage());
        header('Location: ' . url('cart.php'));
        exit;
    }
}

// ── HANDLE GET: Retry / Resume Payment for existing Order ────────
$orderId = (int) ($_GET['order_id'] ?? 0);

if ($orderId <= 0) {
    header('Location: ' . url('cart.php'));
    exit;
}

try {
    // Load order from database
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        flash('error', 'Order not found.');
        header('Location: ' . url('cart.php'));
        exit;
    }

    if (($order['payment_status'] ?? '') === 'paid') {
        flash('info', 'This order has already been paid.');
        header('Location: ' . url('cart.php'));
        exit;
    }

    // Parse items from JSON
    $items = json_decode($order['items_json'] ?? '[]', true) ?: [];
    if (empty($items)) {
        flash('error', 'Order has no items.');
        header('Location: ' . url('cart.php'));
        exit;
    }

    // Build line items for Stripe Checkout
    $currency = strtolower($stripeConfig['currency'] ?? 'lkr');
    $lineItems = [];
    foreach ($items as $item) {
        $unitAmount = (int) round(((float) ($item['price'] ?? 0)) * 100);
        $qty = max(1, (int) ($item['qty'] ?? 1));
        $lineItems[] = [
            'price_data' => [
                'currency' => $currency,
                'product_data' => [
                    'name' => (string) ($item['name'] ?? 'Product'),
                ],
                'unit_amount' => $unitAmount,
            ],
            'quantity' => $qty,
        ];
    }

    // Create Stripe Checkout Session
    $stripe = new StripeGateway($stripeConfig);
    $successUrl = url('stripe-success.php?session_id={CHECKOUT_SESSION_ID}');
    $cancelUrl = url('stripe-cancel.php?order_id=' . $orderId);

    $session = $stripe->createCheckoutSession(
        $lineItems,
        $successUrl,
        $cancelUrl,
        [
            'order_id' => (string) $orderId,
            'order_ref' => (string) ($order['order_ref'] ?? ''),
        ],
        !empty($order['customer_email']) ? (string) $order['customer_email'] : null
    );

    // Update order with checkout session ID
    $pdo->prepare('UPDATE orders SET stripe_payment_intent_id = ? WHERE id = ?')
        ->execute([$session->id, $orderId]);

    // Redirect to Stripe's hosted checkout page
    header('Location: ' . $session->url);
    exit;
} catch (Throwable $e) {
    logStripeError('Stripe Checkout retry failed for order ID ' . $orderId, $e);
    flash('error', 'Unable to resume Stripe payment: ' . $e->getMessage());
    header('Location: ' . url('cart.php'));
    exit;
}

