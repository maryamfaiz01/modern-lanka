<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

// Handle POST request if submitted to checkout.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = $_POST['payment_method'] ?? '';
    if ($paymentMethod === 'stripe' || isset($_POST['stripe_checkout'])) {
        require __DIR__ . '/stripe-checkout.php';
        exit;
    }
}

// Redirect to cart.php - shopping cart and checkout are unified on cart.php
header('Location: ' . url('cart.php'));
exit;
