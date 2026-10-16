<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? null)) {
    $reviewId = (int) ($_POST['review_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($reviewId && in_array($action, ['approve', 'delete'], true)) {
        try {
            if ($action === 'approve') {
                $pdo->prepare('UPDATE reviews SET is_approved = 1 WHERE id = ?')->execute([$reviewId]);
                flash('success', 'Review approved successfully.');
            } elseif ($action === 'delete') {
                $pdo->prepare('DELETE FROM reviews WHERE id = ?')->execute([$reviewId]);
                flash('success', 'Review deleted successfully.');
            }
        } catch (Throwable) {
            flash('error', 'Could not update review. Please try again.');
        }
    }
    header('Location: ' . url('admin/reviews.php'));
    exit;
}

$statusFilter = $_GET['status'] ?? '';
$where = '1=1';
$params = [];
if ($statusFilter === 'pending') {
    $where = 'is_approved = 0';
} elseif ($statusFilter === 'approved') {
    $where = 'is_approved = 1';
}

$stmt = $pdo->prepare(
    "SELECT r.*, p.name as product_name 
     FROM reviews r 
     JOIN products p ON r.product_id = p.id 
     WHERE {$where} 
     ORDER BY r.created_at DESC"
);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

$adminTitle = 'Reviews';
require __DIR__ . '/includes/header.php';
$flash = getFlash();
?>

<div class="admin-page-header admin-page-header--flex">
    <div>
        <h1>Reviews</h1>
        <p><?= count($reviews) ?> reviews</p>
    </div>
    <div class="orders-toolbar">
        <div class="filter-tabs">
            <a href="?" class="filter-tab <?= !$statusFilter ? 'active' : '' ?>">All</a>
            <a href="?status=pending" class="filter-tab <?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending</a>
            <a href="?status=approved" class="filter-tab <?= $statusFilter === 'approved' ? 'active' : '' ?>">Approved</a>
        </div>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>

<div class="reviews-list">
    <?php foreach ($reviews as $review):
        $reviewImages = [];
        if ($review['review_images']) {
            try {
                $reviewImages = json_decode($review['review_images'], true) ?? [];
            } catch (Exception $e) {
                $reviewImages = [];
            }
        }
        $truncatedText = mb_strlen($review['review_text'] ?? '') > 200 
            ? mb_substr($review['review_text'], 0, 200) . '...' 
            : ($review['review_text'] ?? '');
    ?>
    <div class="review-card glass-card">
        <div class="review-card__header">
            <div>
                <strong><?= e($review['product_name']) ?></strong>
                <span class="review-date"><?= date('M j, Y g:i A', strtotime($review['created_at'])) ?></span>
            </div>
            <div class="review-actions">
                <?php if (!$review['is_approved']): ?>
                <form method="post" class="action-form" onsubmit="return confirm('Approve this review?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn--primary btn--sm">Approve</button>
                </form>
                <?php endif; ?>
                <form method="post" class="action-form" onsubmit="return confirm('Delete this review? This cannot be undone.')">
                    <?= csrfField() ?>
                    <input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                </form>
            </div>
        </div>
        <div class="review-card__customer">
            <span><?= e($review['customer_name']) ?></span>
            <?php if ($review['customer_phone']): ?>
            <a href="tel:<?= e($review['customer_phone']) ?>"><?= e($review['customer_phone']) ?></a>
            <?php endif; ?>
            <?php if ($review['is_verified_purchase']): ?>
            <span class="badge badge--verified">Verified Purchase</span>
            <?php endif; ?>
            <span class="status-badge status-badge--<?= $review['is_approved'] ? 'approved' : 'pending' ?>">
                <?= $review['is_approved'] ? 'Approved' : 'Pending' ?>
            </span>
        </div>
        <div class="review-rating">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <span class="star <?= $i <= $review['rating'] ? 'star--filled' : 'star--empty' ?>">★</span>
            <?php endfor; ?>
        </div>
        <?php if ($review['review_text']): ?>
        <p class="review-text">
            <?= e($truncatedText) ?>
            <?php if (mb_strlen($review['review_text'] ?? '') > 200): ?>
            <button type="button" class="btn-link" onclick="this.parentElement.innerHTML = '<?= e(nl2br($review['review_text'])) ?>'">View more</button>
            <?php endif; ?>
        </p>
        <?php endif; ?>
        <?php if ($reviewImages): ?>
        <div class="review-images">
            <?php foreach (array_slice($reviewImages, 0, 3) as $image): ?>
            <img src="<?= e($image) ?>" alt="Review photo" class="review-image-thumbnail">
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php if (empty($reviews)): ?>
    <div class="empty-state glass-card">
        <h3>No reviews found</h3>
        <p>Customer reviews will appear here once submitted.</p>
    </div>
    <?php endif; ?>
</div>

<style>
.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.review-card {
    padding: 20px;
}

.review-card__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.review-card__header strong {
    display: block;
    font-size: 1.1rem;
}

.review-date {
    font-size: 0.85rem;
    color: var(--text-secondary);
}

.review-actions {
    display: flex;
    gap: 8px;
}

.action-form {
    margin: 0;
}

.review-card__customer {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.review-card__customer span,
.review-card__customer a {
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.badge--verified {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-badge--approved {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
}

.status-badge--pending {
    background: rgba(234, 179, 8, 0.1);
    color: #eab308;
}

.review-rating {
    display: flex;
    gap: 4px;
    margin-bottom: 12px;
}

.star {
    color: var(--gold);
}

.star--empty {
    color: var(--text-muted);
}

.review-text {
    margin: 0 0 12px 0;
    line-height: 1.5;
    color: var(--text-secondary);
}

.btn-link {
    background: none;
    border: none;
    color: var(--gold);
    cursor: pointer;
    padding: 0;
    font-size: inherit;
    text-decoration: underline;
}

.review-images {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.review-image-thumbnail {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--glass-border);
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
