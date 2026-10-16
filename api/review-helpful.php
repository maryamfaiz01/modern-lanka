<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

$reviewId = (int) ($_POST['review_id'] ?? 0);

if (!$reviewId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid review ID']);
    exit;
}

try {
    // Verify review exists
    $stmt = $pdo->prepare('SELECT helpful_count FROM reviews WHERE id = ?');
    $stmt->execute([$reviewId]);
    $review = $stmt->fetch();

    if (!$review) {
        http_response_code(404);
        echo json_encode(['error' => 'Review not found']);
        exit;
    }

    // Check if user already marked as helpful (using session)
    $sessionKey = 'review_helpful_' . $reviewId;
    
    if (isset($_SESSION[$sessionKey])) {
        http_response_code(400);
        echo json_encode(['error' => 'You already marked this review as helpful']);
        exit;
    }

    // Increment helpful count
    $updateStmt = $pdo->prepare('UPDATE reviews SET helpful_count = helpful_count + 1 WHERE id = ?');
    $updateStmt->execute([$reviewId]);

    // Mark in session
    $_SESSION[$sessionKey] = true;

    echo json_encode([
        'success' => true,
        'helpful_count' => (int)$review['helpful_count'] + 1
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    exit;
}
