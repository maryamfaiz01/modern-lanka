<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$customerId = $_SESSION['customer_id'] ?? null;
if (!$customerId) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to submit a review']);
    exit;
}

$productId = (int) ($_POST['product_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);
$text = trim($_POST['review_text'] ?? '');

// Handle photo uploads
$photoUrls = [];
if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
    $uploadDir = dirname(__DIR__) . '/uploads/reviews/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    foreach ($_FILES['photos']['name'] as $key => $name) {
        if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array(strtolower($ext), $allowed)) {
                $filename = uniqid('review_', true) . '.' . $ext;
                $filepath = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['photos']['tmp_name'][$key], $filepath)) {
                    $photoUrls[] = '/uploads/reviews/' . $filename;
                }
            }
        }
    }
}

// Validate
if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Rating must be between 1 and 5']);
    exit;
}

if (strlen($text) < 10) {
    http_response_code(400);
    echo json_encode(['error' => 'Review text must be at least 10 characters']);
    exit;
}

// Check if product exists
$stmt = $pdo->prepare('SELECT id FROM products WHERE id = ? AND is_active = 1');
$stmt->execute([$productId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}

// Check if customer already reviewed this product
$stmt = $pdo->prepare('SELECT id FROM reviews WHERE product_id = ? AND customer_id = ?');
$stmt->execute([$productId, $customerId]);
if ($stmt->fetch()) {
    http_response_code(400);
    echo json_encode(['error' => 'You have already reviewed this product']);
    exit;
}

// Get customer info
$stmt = $pdo->prepare('SELECT name, phone FROM customers WHERE id = ?');
$stmt->execute([$customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    http_response_code(400);
    echo json_encode(['error' => 'Customer not found']);
    exit;
}

// Check if customer has purchased this product (for verified badge)
$stmt = $pdo->prepare(
    'SELECT id FROM orders WHERE customer_phone = ? AND items_json LIKE ? AND status != "cancelled"'
);
$stmt->execute([$customer['phone'], '%"id":' . $productId . '%']);
$isVerified = $stmt->fetch() ? 1 : 0;

// Insert review
try {
    $pdo->prepare(
        'INSERT INTO reviews
         (product_id, customer_id, customer_name, customer_phone, customer_email, 
          rating, review_text, review_images, is_verified_purchase, is_approved)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $productId,
        $customerId,
        $customer['name'],
        $customer['phone'],
        '', // email not stored in customers table
        $rating,
        $text,
        json_encode($photoUrls),
        $isVerified,
        1, // auto-approve
    ]);

    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit review']);
}
