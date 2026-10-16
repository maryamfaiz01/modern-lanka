<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$productId = (int) ($_GET['product'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$reviewsPerPage = 10;

if (!$productId) {
    header('Location: ' . url('products.php'));
    exit;
}

// Fetch product
$stmt = $pdo->prepare('SELECT id, name, slug, image FROM products WHERE id = ? AND is_active = 1');
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Reviews Not Found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="empty-state glass-card"><h3>Product not found</h3><a href="' . url('products.php') . '" class="btn btn--primary">Back to Catalog</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch total reviews
$totalStmt = $pdo->prepare('SELECT COUNT(*) FROM reviews WHERE product_id = ? AND is_approved = 1');
$totalStmt->execute([$productId]);
$totalReviews = (int) $totalStmt->fetchColumn();

$totalPages = ceil($totalReviews / $reviewsPerPage);
$offset = ($page - 1) * $reviewsPerPage;

// Fetch paginated reviews
$stmt = $pdo->prepare(
    'SELECT * FROM reviews WHERE product_id = ? AND is_approved = 1 
     ORDER BY created_at DESC LIMIT ? OFFSET ?'
);
$stmt->execute([$productId, $reviewsPerPage, $offset]);
$reviews = $stmt->fetchAll();

// Get average rating
$avgStmt = $pdo->prepare(
    'SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = ? AND is_approved = 1'
);
$avgStmt->execute([$productId]);
$ratingStats = $avgStmt->fetch();
$averageRating = $ratingStats['avg_rating'] ? round($ratingStats['avg_rating'], 1) : 0;

// Rating distribution
$distStmt = $pdo->prepare(
    'SELECT rating, COUNT(*) as count FROM reviews WHERE product_id = ? AND is_approved = 1 
     GROUP BY rating ORDER BY rating DESC'
);
$distStmt->execute([$productId]);
$ratingDistribution = $distStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'All Reviews - ' . $product['name'];
$pageDescription = 'Read all customer reviews for ' . $product['name'];

require __DIR__ . '/includes/header.php';

function renderStars(int $rating): string {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<span class="star star--filled">★</span>';
        } else {
            $stars .= '<span class="star star--empty">☆</span>';
        }
    }
    return $stars;
}

function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $now = time();
    $ago = $now - $time;

    if ($ago < 60) {
        return 'just now';
    } elseif ($ago < 3600) {
        return (int)($ago / 60) . ' minutes ago';
    } elseif ($ago < 86400) {
        return (int)($ago / 3600) . ' hours ago';
    } elseif ($ago < 604800) {
        return (int)($ago / 86400) . ' days ago';
    } elseif ($ago < 2592000) {
        return (int)($ago / 604800) . ' weeks ago';
    } elseif ($ago < 31536000) {
        return (int)($ago / 2592000) . ' months ago';
    } else {
        return (int)($ago / 31536000) . ' years ago';
    }
}
?>

<div class="reviews-page animate-in">
    <div class="page-header__breadcrumb">
        <a href="<?= url('index.php') ?>">Home</a>
        <span>/</span>
        <a href="<?= url('products.php') ?>">Catalog</a>
        <span>/</span>
        <a href="<?= url('product.php?slug=' . e($product['slug'])) ?>"><?= e($product['name']) ?></a>
        <span>/</span>
        <span>All Reviews</span>
    </div>

    <div class="reviews-page__header">
        <div class="reviews-page__product">
            <img src="<?= productImageUrl($product['image'], $config) ?>" 
                 alt="<?= e($product['name']) ?>" class="reviews-page__product-img">
            <div>
                <h1><?= e($product['name']) ?></h1>
                <a href="<?= url('product.php?slug=' . e($product['slug'])) ?>" class="btn btn--sm btn--outline">
                    View Product
                </a>
            </div>
        </div>

        <div class="reviews-page__summary">
            <div class="rating-summary">
                <div class="rating-number"><?= number_format($averageRating, 1) ?></div>
                <div class="rating-stars"><?= renderStars((int)ceil($averageRating)) ?></div>
                <div class="rating-text">Based on <?= $totalReviews ?> review<?= $totalReviews !== 1 ? 's' : '' ?></div>
            </div>

            <div class="rating-distribution">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <div class="distribution-row">
                    <span class="distribution-label"><?= $i ?> <span class="star">★</span></span>
                    <div class="distribution-bar">
                        <div class="distribution-fill" style="width: <?= $totalReviews > 0 ? (($ratingDistribution[$i] ?? 0) / $totalReviews * 100) : 0 ?>%"></div>
                    </div>
                    <span class="distribution-count"><?= (int)($ratingDistribution[$i] ?? 0) ?></span>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <div class="reviews-page__content">
        <?php if ($reviews): ?>
        <div class="reviews-list">
            <?php foreach ($reviews as $review): ?>
            <article class="review-card review-card--large">
                <div class="review-card__header">
                    <div class="review-author">
                        <img src="<?= e($review['customer_avatar']) ?>" 
                             alt="<?= e($review['customer_name']) ?>" 
                             class="review-avatar"
                             onerror="this.src='https://i.pravatar.cc/150?u=<?= urlencode($review['customer_email']) ?>'">
                        
                        <div class="review-author__info">
                            <div class="review-author__name">
                                <?= e($review['customer_name']) ?>
                                <?php if ($review['is_verified_purchase']): ?>
                                <span class="badge badge--verified" title="Verified Purchase">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    Verified
                                </span>
                                <?php endif; ?>
                            </div>
                            <time class="review-date"><?= timeAgo($review['created_at']) ?></time>
                        </div>
                    </div>

                    <div class="review-rating">
                        <?= renderStars($review['rating']) ?>
                    </div>
                </div>

                <?php if ($review['review_title']): ?>
                <h3 class="review-title"><?= e($review['review_title']) ?></h3>
                <?php endif; ?>

                <?php if ($review['review_text']): ?>
                <p class="review-text"><?= nl2br(e($review['review_text'])) ?></p>
                <?php endif; ?>

                <?php 
                $reviewImages = [];
                if ($review['review_images']) {
                    try {
                        $reviewImages = json_decode($review['review_images'], true) ?? [];
                    } catch (Exception $e) {
                        $reviewImages = [];
                    }
                }
                ?>

                <?php if ($reviewImages): ?>
                <div class="review-images review-images--gallery">
                    <?php foreach ($reviewImages as $image): ?>
                    <img src="<?= e($image) ?>" 
                         alt="Customer review photo" 
                         class="review-image"
                         loading="lazy">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="review-footer">
                    <button class="review-helpful" type="button" data-review-id="<?= (int)$review['id'] ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 9V5a3 3 0 0 0-3-3v0a3 3 0 0 0-3 3v4"></path>
                            <path d="M15 20H4a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2h2.5a1 1 0 0 0 .8-.4l3.7-4.4a1 1 0 0 1 1.6.2l.4 1.6"></path>
                        </svg>
                        Helpful (<?= (int)$review['helpful_count'] ?>)
                    </button>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?product=<?= (int)$productId ?>&page=<?= $page - 1 ?>" class="pagination__btn">← Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page): ?>
                <span class="pagination__num active"><?= $i ?></span>
                <?php elseif ($i <= 3 || $i > $totalPages - 3 || abs($i - $page) <= 1): ?>
                <a href="?product=<?= (int)$productId ?>&page=<?= $i ?>" class="pagination__num"><?= $i ?></a>
                <?php elseif ($i == 4 || $i == $totalPages - 2): ?>
                <span class="pagination__dots">…</span>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
            <a href="?product=<?= (int)$productId ?>&page=<?= $page + 1 ?>" class="pagination__btn">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="no-reviews">
            <p>No reviews yet. Be the first to review this product!</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.reviews-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px;
}

.reviews-page__header {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 48px;
    margin-bottom: 48px;
    padding-bottom: 32px;
    border-bottom: 1px solid var(--glass-border);
}

.reviews-page__product {
    display: flex;
    gap: 24px;
    align-items: flex-start;
}

.reviews-page__product-img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: var(--radius-md);
    border: 1px solid var(--glass-border);
}

.reviews-page__product h1 {
    font-size: 1.5rem;
    margin: 0 0 16px 0;
}

.reviews-page__summary {
    display: flex;
    flex-direction: column;
    gap: 32px;
}

.rating-summary {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    text-align: center;
}

.rating-number {
    font-family: var(--font-display);
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--gold);
    line-height: 1;
}

.rating-stars {
    display: flex;
    gap: 4px;
    font-size: 1.2rem;
    justify-content: center;
}

.rating-text {
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.rating-distribution {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.distribution-row {
    display: grid;
    grid-template-columns: 30px 1fr 40px;
    align-items: center;
    gap: 12px;
}

.distribution-label {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.85rem;
    color: var(--text-secondary);
    text-align: right;
}

.distribution-label .star {
    font-size: 0.9rem;
    color: var(--gold);
}

.distribution-bar {
    height: 6px;
    background: var(--bg-elevated);
    border-radius: 999px;
    overflow: hidden;
    border: 1px solid var(--glass-border);
}

.distribution-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--gold) 0%, var(--gold-light) 100%);
    transition: width 0.3s ease;
}

.distribution-count {
    font-size: 0.8rem;
    color: var(--text-muted);
    text-align: right;
}

.reviews-page__content {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.review-card--large {
    padding: 24px;
}

@media (max-width: 768px) {
    .reviews-page__header {
        grid-template-columns: 1fr;
        gap: 32px;
    }

    .reviews-page {
        padding: 16px;
    }

    .reviews-page__product {
        flex-direction: column;
    }

    .reviews-page__product-img {
        width: 100px;
        height: 100px;
    }

    .distribution-row {
        grid-template-columns: 25px 1fr 30px;
    }

    .review-card--large {
        padding: 16px;
    }
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
