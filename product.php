<?php

declare(strict_types=1);



require_once __DIR__ . '/includes/bootstrap.php';

// Load Stripe config
$stripeConfig = file_exists(__DIR__ . '/config/stripe.php')
    ? require __DIR__ . '/config/stripe.php'
    : null;
$stripeEnabled = stripeIsConfigured($stripeConfig);
$loggedInCustomer = getLoggedInCustomer($pdo);

$slug = $_GET['slug'] ?? '';

if (!$slug) {

    header('Location: ' . url('products.php'));

    exit;

}



$stmt = $pdo->prepare('SELECT * FROM products WHERE slug = ? AND is_active = 1');

$stmt->execute([$slug]);

$product = $stmt->fetch();



if (!$product) {

    http_response_code(404);

    $pageTitle = 'Product Not Found';

    require __DIR__ . '/includes/header.php';

    echo '<div class="empty-state glass-card"><h3>Product not found</h3><a href="' . url('products.php') . '" class="btn btn--primary">Back to Catalog</a></div>';

    require __DIR__ . '/includes/footer.php';

    exit;

}



$pdo->prepare('UPDATE products SET views = views + 1 WHERE id = ?')->execute([$product['id']]);



$related = $pdo->prepare(

    'SELECT * FROM products WHERE is_active = 1 AND id != ? AND (category = ? OR vehicle_model = ?) ORDER BY RAND() LIMIT 4'

);

$related->execute([$product['id'], $product['category'], $product['vehicle_model']]);

$relatedProducts = $related->fetchAll();



$pageTitle = $product['name'];

$pageDescription = $product['short_description'] ?? substr(strip_tags($product['description'] ?? ''), 0, 160);

require __DIR__ . '/includes/header.php';

?>



<div class="product-detail animate-in">

    <div class="product-detail__gallery glass-card">

        <img src="<?= productImageUrl($product['image'], $config) ?>" 

             alt="<?= e($product['name']) ?>" class="product-detail__image">

        <?php if ($product['is_featured']): ?>

        <span class="product-card__badge">Featured</span>

        <?php endif; ?>

    </div>



    <div class="product-detail__info">

        <div class="page-header__breadcrumb">

            <a href="<?= url('index.php') ?>">Home</a>

            <span>/</span>

            <a href="<?= url('products.php') ?>">Catalog</a>

            <span>/</span>

            <span><?= e($product['name']) ?></span>

        </div>



        <div class="product-detail__tags">

            <span class="tag"><?= e(vehicleLabel($product['vehicle_model'], $config)) ?></span>

            <span class="tag"><?= e(categoryLabel($product['category'], $config)) ?></span>

        </div>



        <h1><?= e($product['name']) ?></h1>

        <p class="product-detail__sku">SKU: <?= e($product['sku']) ?></p>



        <div class="product-detail__price">

            <span class="price-current price-current--lg"><?= formatPrice((float) $product['price'], $config) ?></span>

            <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>

            <span class="price-compare"><?= formatPrice((float) $product['compare_price'], $config) ?></span>

            <span class="price-save">Save <?= formatPrice((float) $product['compare_price'] - (float) $product['price'], $config) ?></span>

            <?php endif; ?>

        </div>



        <?php if ($product['short_description']): ?>

        <p class="product-detail__short"><?= e($product['short_description']) ?></p>

        <?php endif; ?>



        <div class="stock-indicator <?= (int) $product['stock'] > 5 ? 'in-stock' : ((int) $product['stock'] > 0 ? 'low-stock' : 'out-stock') ?>">

            <?php if ((int) $product['stock'] > 5): ?>

            ✓ In Stock (<?= (int) $product['stock'] ?> available)

            <?php elseif ((int) $product['stock'] > 0): ?>

            ⚠ Low Stock — only <?= (int) $product['stock'] ?> left

            <?php else: ?>

            ✕ Out of Stock

            <?php endif; ?>

        </div>



        <div class="product-detail__actions">

            <div class="qty-selector">

                <button type="button" class="qty-btn" data-action="minus">−</button>

                <input type="number" id="productQty" value="1" min="1" max="<?= (int) $product['stock'] ?>" class="qty-input">

                <button type="button" class="qty-btn" data-action="plus">+</button>

            </div>

            <button type="button" class="btn btn--primary btn--lg btn-add-cart" 

                    data-id="<?= (int) $product['id'] ?>"

                    <?= (int) $product['stock'] <= 0 ? 'disabled' : '' ?>>

                Add to Cart

            </button>

            <a href="<?= whatsappLink(
                $config['whatsapp'],
                "Hi! I'm interested in:\n*{$product['name']}*\nSKU: {$product['sku']}\nPrice: " . formatPrice((float) $product['price'], $config)
            ) ?>" class="btn btn--whatsapp btn--lg" target="_blank" rel="noopener">
                Order via WhatsApp
            </a>

            <?php if ($stripeEnabled && (int) $product['stock'] > 0): ?>
            <form action="<?= url('stripe-checkout.php') ?>" method="POST" id="stripe-buynow-form" class="product-buynow-form" style="display:inline-block;">
                <input type="hidden" name="customer_name" value="<?= e($loggedInCustomer['name'] ?? '') ?>">
                <input type="hidden" name="customer_phone" value="<?= e($loggedInCustomer['phone'] ?? '') ?>">
                <input type="hidden" name="customer_email" value="">
                <input type="hidden" name="notes" value="">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <button type="button" class="btn btn--gold btn--lg btn-buy-now-stripe" id="btnBuyNowStripe" data-id="<?= (int) $product['id'] ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    Buy Now with Stripe
                </button>
            </form>
            <?php endif; ?>
        </div>



        <div class="product-detail__description glass-card">

            <h3>Description</h3>

            <div class="prose"><?= nl2br(e($product['description'] ?? 'No description available.')) ?></div>

        </div>

    </div>

</div>

<?php include __DIR__ . '/includes/reviews.php'; ?>

<!-- Review Submission Form -->
<?php if (isset($_SESSION['customer_id'])): ?>
<section class="review-submit-section glass-card animate-in">
    <div class="review-submit-header">
        <h3>Write a Review</h3>
        <p class="review-submit-sub">Share your experience with this product</p>
    </div>
    
    <form id="reviewForm" class="review-submit-form">
        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        
        <div class="form-group">
            <label>Your Rating *</label>
            <div class="rating-input">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <button type="button" class="rating-star" data-rating="<?= $i ?>" aria-label="<?= $i ?> stars">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                </button>
                <?php endfor; ?>
            </div>
            <input type="hidden" name="rating" id="ratingInput" required>
        </div>
        
        <div class="form-group">
            <label for="review_text">Your Review *</label>
            <textarea id="review_text" name="review_text" class="input textarea" rows="4" required 
                      placeholder="Tell us about your experience with this product..." maxlength="2000"></textarea>
        </div>
        
        <div class="form-group">
            <label>Add Photos (Optional)</label>
            <div class="photo-upload">
                <input type="file" id="reviewPhotos" name="photos[]" accept="image/*" multiple class="photo-input">
                <label for="reviewPhotos" class="photo-upload-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                    <span>Upload Photos</span>
                </label>
                <div id="photoPreview" class="photo-preview"></div>
            </div>
        </div>
        
        <button type="submit" class="btn btn--primary btn--lg">Submit Review</button>
    </form>
</section>
<?php else: ?>
<section class="review-login-prompt glass-card animate-in">
    <p>Please <a href="<?= url('login.php') ?>">log in</a> to write a review.</p>
</section>
<?php endif; ?>

<?php if ($relatedProducts): ?>

<section class="related-section">

    <h2>Related Products</h2>

    <div class="product-grid product-grid--4">

        <?php foreach ($relatedProducts as $i => $product): ?>

        <?php include __DIR__ . '/includes/product-card.php'; ?>

        <?php endforeach; ?>

    </div>

</section>

<?php endif; ?>

<script>
document.getElementById('btnBuyNowStripe')?.addEventListener('click', async function () {
    const btn = this;
    if (btn.disabled) return;

    const productId = btn.dataset.id;
    const qtyEl = document.getElementById('productQty');
    let qty = 1;
    if (qtyEl) {
        const parsedQty = parseInt(qtyEl.value, 10);
        const maxQty = parseInt(qtyEl.max, 10) || 99;
        qty = Number.isNaN(parsedQty) ? 1 : Math.max(1, Math.min(parsedQty, maxQty));
        qtyEl.value = qty;
    }

    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.textContent = 'Redirecting to Stripe...';

    const baseUrl = window.MLM?.baseUrl || '';
    const cartApiUrl = (baseUrl || '') + 'api/cart.php';

    try {
        const res = await fetch(cartApiUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', product_id: productId, qty: qty }),
        });

        if (!res.ok) {
            throw new Error(`Request failed with status ${res.status}`);
        }

        const data = await res.json();
        if (data.success) {
            if (window.MLM?.updateCartBadge) {
                window.MLM.updateCartBadge(data.cart_count);
            }
            document.getElementById('stripe-buynow-form')?.submit();
        } else {
            const errorMsg = data.message || 'Could not add product to cart';
            if (window.MLM?.showToast) {
                window.MLM.showToast(errorMsg, 'error');
            } else {
                alert(errorMsg);
            }
            btn.innerHTML = originalContent;
            btn.disabled = false;
        }
    } catch (err) {
        const errorMsg = 'Unable to proceed to checkout. Please try again.';
        if (window.MLM?.showToast) {
            window.MLM.showToast(errorMsg, 'error');
        } else {
            alert(errorMsg);
        }
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

