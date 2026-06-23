<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('cart.php'));
    exit;
}

if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
    flash('error', 'Invalid request. Please try again.');
    header('Location: ' . url('cart.php'));
    exit;
}

$cartItems = getCartItems($pdo);
if (empty($cartItems)) {
    flash('error', 'Your cart is empty.');
    header('Location: ' . url('cart.php'));
    exit;
}

$customerName = trim($_POST['customer_name'] ?? '');
$customerPhone = trim($_POST['customer_phone'] ?? '');
$loggedInCustomer = getLoggedInCustomer($pdo);
if ($loggedInCustomer) {
    if ($customerName === '') {
        $customerName = $loggedInCustomer['name'];
    }
    if ($customerPhone === '') {
        $customerPhone = $loggedInCustomer['phone'];
    }
}
$notes = trim($_POST['notes'] ?? '');
$subtotal = array_sum(array_column($cartItems, 'line_total'));
$orderRef = generateOrderRef();

$itemsForDb = array_map(fn($i) => [
    'id' => $i['id'],
    'name' => $i['name'],
    'sku' => $i['sku'],
    'qty' => $i['qty'],
    'price' => $i['price'],
    'line_total' => $i['line_total'],
], $cartItems);

$stmt = $pdo->prepare(
    'INSERT INTO orders (order_ref, customer_name, customer_phone, items_json, subtotal, notes, source) 
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $orderRef,
    $customerName,
    $customerPhone,
    json_encode($itemsForDb),
    $subtotal,
    $notes,
    'whatsapp',
]);

$message = buildWhatsAppMessage($cartItems, $subtotal, $config, $customerName, $notes);
$message = "Order Ref: *{$orderRef}*\n\n" . $message;

$_SESSION['cart'] = [];

$waUrl = whatsappLink($config['whatsapp'], $message);
header('Location: ' . $waUrl);
exit;
