<?php
declare(strict_types=1);

/**
 * Product Reviews Display Component
 * Shows customer reviews with ratings, avatars, and verification badges
 */

if (!isset($product['id'])) {
    return;
}

// Fetch reviews for this product
$stmt = $pdo->prepare(
    'SELECT * FROM reviews WHERE product_id = ? AND is_approved = 1 ORDER BY created_at DESC LIMIT 5'
);
$stmt->execute([$product['id']]);
$reviews = $stmt->fetchAll();

// Calculate average rating
$avgStmt = $pdo->prepare(
    'SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE product_id = ? AND is_approved = 1'
);
$avgStmt->execute([$product['id']]);
$ratingStats = $avgStmt->fetch();
$averageRating = $ratingStats['avg_rating'] ? round((float) $ratingStats['avg_rating'], 1) : 0;
$totalReviews = $ratingStats['total_reviews'] ?? 0;

if ($totalReviews === 0) {
    return;
}

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

<section class="reviews-section glass-card">
    <div class="reviews-header">
        <h2 class="reviews-title">Customer Reviews</h2>
        
        <div class="reviews-rating-summary">
            <div class="rating-display">
                <div class="rating-number"><?= number_format($averageRating, 1) ?></div>
                <div class="rating-stars">
                    <?= renderStars((int)ceil($averageRating)) ?>
                </div>
                <div class="rating-count">
                    <?= $totalReviews ?> review<?= $totalReviews !== 1 ? 's' : '' ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($reviews): ?>
    <div class="reviews-container">
        <?php foreach ($reviews as $review): ?>
        <article class="review-card">
            <div class="review-card__header">
                <div class="review-author">
                    <div class="review-avatar" style="background: var(--gold-dim); display: flex; align-items: center; justify-content: center; color: var(--gold); font-weight: 700; font-size: 1rem;">
                        <?= strtoupper(substr($review['customer_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    
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

            <p class="review-text"><?= nl2br(e($review['review_text'])) ?></p>

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
            <div class="review-images">
                <?php foreach (array_slice($reviewImages, 0, 3) as $image): ?>
                <img src="<?= e(url($image)) ?>" 
                     alt="Customer review photo" 
                     class="review-image"
                     loading="lazy">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
    </div>

    <?php if ($totalReviews > 5): ?>
    <div class="reviews-footer">
        <a href="<?= url('reviews.php?product=' . (int)$product['id']) ?>" class="btn btn--outline btn--sm">
            View All <?= (int)$totalReviews ?> Reviews
        </a>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="no-reviews">
        <p>No reviews yet. Be the first to review this product!</p>
    </div>
    <?php endif; ?>
</section>
