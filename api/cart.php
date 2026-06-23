<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? '';
$productId = (int) ($input['product_id'] ?? 0);
$qty = max(1, (int) ($input['qty'] ?? 1));

switch ($action) {
    case 'add':
        $stmt = $pdo->prepare('SELECT id, name, price, stock FROM products WHERE id = ? AND is_active = 1');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }
        if (!isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] = ['qty' => 0];
        }
        $newQty = $_SESSION['cart'][$productId]['qty'] + $qty;
        if ($newQty > (int) $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
            exit;
        }
        $_SESSION['cart'][$productId]['qty'] = $newQty;
        echo json_encode([
            'success' => true,
            'message' => 'Added to cart',
            'cart_count' => cartCount(),
        ]);
        break;

    case 'update':
        if ($productId && isset($_SESSION['cart'][$productId])) {
            if ($qty <= 0) {
                unset($_SESSION['cart'][$productId]);
            } else {
                $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = ?');
                $stmt->execute([$productId]);
                $stock = (int) $stmt->fetchColumn();
                $_SESSION['cart'][$productId]['qty'] = min($qty, $stock);
            }
        }
        echo json_encode([
            'success' => true,
            'cart_count' => cartCount(),
            'subtotal' => cartSubtotal($pdo),
        ]);
        break;

    case 'remove':
        unset($_SESSION['cart'][$productId]);
        echo json_encode([
            'success' => true,
            'cart_count' => cartCount(),
            'subtotal' => cartSubtotal($pdo),
        ]);
        break;

    case 'clear':
        $_SESSION['cart'] = [];
        echo json_encode(['success' => true, 'cart_count' => 0]);
        break;

    case 'count':
        echo json_encode(['success' => true, 'cart_count' => cartCount()]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
