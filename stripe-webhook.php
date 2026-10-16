<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/Stripe.php';

// Stripe sends webhook events as POST requests
$input = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (!$input) {
    http_response_code(400);
    echo 'Missing payload';
    exit;
}

$stripeConfig = file_exists(__DIR__ . '/config/stripe.php')
    ? require __DIR__ . '/config/stripe.php'
    : null;
$webhookSecret = trim((string) ($stripeConfig['webhook_secret'] ?? ''));

try {
    if ($webhookSecret !== '') {
        if (!$sigHeader) {
            http_response_code(400);
            echo 'Missing signature header';
            exit;
        }
        $stripe = new StripeGateway($stripeConfig);
        $event = $stripe->constructWebhookEvent($input, $sigHeader);
    } else {
        if (!LOCAL_DEV) {
            throw new RuntimeException('Stripe webhook_secret is not configured.');
        }
        $payload = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
        $event = \Stripe\Event::constructFrom($payload);
    }
} catch (Throwable $e) {
    logStripeError('Webhook signature verification failed: ' . $e->getMessage(), $e);
    http_response_code(400);
    echo 'Invalid signature';
    exit;
}

// Handle the event
switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;
        $sessionId = $session->id;
        $orderId = $session->metadata['order_id'] ?? null;
        $orderRef = $session->metadata['order_ref'] ?? null;
        $customerEmail = isset($session->customer_details) && isset($session->customer_details->email) ? $session->customer_details->email : null;

        error_log("Checkout session completed: {$sessionId} for order {$orderRef}");

        if ($orderId) {
            // Update order payment status
            $stmt = $pdo->prepare('SELECT id, items_json, stock_deducted FROM orders WHERE id = ?');
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if ($order) {
                $pdo->prepare('UPDATE orders SET payment_status = "paid", status = "confirmed", stripe_customer_email = ? WHERE id = ?')
                    ->execute([$customerEmail, $orderId]);

                // Deduct stock if not already done
                if ((int) $order['stock_deducted'] === 0) {
                    $items = json_decode($order['items_json'], true) ?: [];
                    foreach ($items as $item) {
                        $pdo->prepare('UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?')
                            ->execute([$item['qty'], $item['id']]);
                    }
                    $pdo->prepare('UPDATE orders SET stock_deducted = 1 WHERE id = ?')
                        ->execute([$order['id']]);
                }

                error_log("Order {$orderRef} (ID: {$orderId}) confirmed and payment status updated to paid");
            } else {
                error_log("Order not found for ID: {$orderId}");
            }
        }
        break;

    case 'checkout.session.expired':
        $session = $event->data->object;
        $sessionId = $session->id;
        $orderId = $session->metadata['order_id'] ?? null;
        $orderRef = $session->metadata['order_ref'] ?? null;

        error_log("Checkout session expired: {$sessionId} for order {$orderRef}");

        if ($orderId) {
            $pdo->prepare('UPDATE orders SET payment_status = "cancelled", status = "cancelled" WHERE id = ?')
                ->execute([$orderId]);
            error_log("Order {$orderRef} (ID: {$orderId}) marked as cancelled due to session expiry");
        }
        break;

    case 'payment_intent.succeeded':
        $paymentIntent = $event->data->object;
        $paymentIntentId = $paymentIntent->id;
        $orderRef = $paymentIntent->metadata['order_ref'] ?? null;
        $customerEmail = $paymentIntent->metadata['customer_email'] ?? null;

        error_log("Payment succeeded: {$paymentIntentId} for order {$orderRef}");

        if ($orderRef) {
            // Update order payment status
            $stmt = $pdo->prepare('SELECT id, items_json, stock_deducted FROM orders WHERE order_ref = ?');
            $stmt->execute([$orderRef]);
            $order = $stmt->fetch();

            if ($order) {
                $pdo->prepare('UPDATE orders SET payment_status = "paid", status = "confirmed", stripe_customer_email = ? WHERE order_ref = ?')
                    ->execute([$customerEmail, $orderRef]);

                // Deduct stock if not already done
                if ((int) $order['stock_deducted'] === 0) {
                    $items = json_decode($order['items_json'], true) ?: [];
                    foreach ($items as $item) {
                        $pdo->prepare('UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?')
                            ->execute([$item['qty'], $item['id']]);
                    }
                    $pdo->prepare('UPDATE orders SET stock_deducted = 1 WHERE id = ?')
                        ->execute([$order['id']]);
                }

                error_log("Order {$orderRef} confirmed and payment status updated to paid");
            } else {
                error_log("Order not found for reference: {$orderRef}");
            }
        }
        break;

    case 'payment_intent.payment_failed':
        $paymentIntent = $event->data->object;
        $paymentIntentId = $paymentIntent->id;
        $orderRef = $paymentIntent->metadata['order_ref'] ?? null;

        error_log("Payment failed: {$paymentIntentId} for order {$orderRef}");

        if ($orderRef) {
            $pdo->prepare('UPDATE orders SET payment_status = "failed", status = "cancelled" WHERE order_ref = ?')
                ->execute([$orderRef]);
            error_log("Order {$orderRef} marked as failed/cancelled");
        }
        break;

    case 'payment_intent.canceled':
        $paymentIntent = $event->data->object;
        $paymentIntentId = $paymentIntent->id;
        $orderRef = $paymentIntent->metadata['order_ref'] ?? null;

        error_log("Payment canceled: {$paymentIntentId} for order {$orderRef}");

        if ($orderRef) {
            $pdo->prepare('UPDATE orders SET payment_status = "cancelled", status = "cancelled" WHERE order_ref = ?')
                ->execute([$orderRef]);
        }
        break;

    default:
        error_log('Unhandled event type: ' . $event->type);
}

http_response_code(200);
echo 'Webhook received';
