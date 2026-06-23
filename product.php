<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

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
$extraScripts = ['cart.js'];

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
        </div>

        <div class="product-detail__description glass-card">
            <h3>Description</h3>
            <div class="prose"><?= nl2br(e($product['description'] ?? 'No description available.')) ?></div>
        </div>
    </div>
</div>

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

<?php require __DIR__ . '/includes/footer.php'; ?>
