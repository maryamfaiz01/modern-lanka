<?php
/** @var array $product */
/** @var int $i */
$delay = isset($i) ? $i * 0.05 : 0;
?>
<article class="product-card glass-card animate-on-scroll" style="--delay: <?= $delay ?>s" data-product-id="<?= (int) $product['id'] ?>">
    <a href="<?= url('product.php?slug=' . e($product['slug'])) ?>" class="product-card__image-wrap">
        <?php if ($product['is_featured']): ?>
        <span class="product-card__badge">Featured</span>
        <?php endif; ?>
        <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
        <span class="product-card__sale">Sale</span>
        <?php endif; ?>
        <img src="<?= productImageUrl($product['image'], $config) ?>" 
             alt="<?= e($product['name']) ?>" 
             class="product-card__image" loading="lazy">
    </a>
    <div class="product-card__body">
        <div class="product-card__meta">
            <span class="product-card__vehicle"><?= e(vehicleLabel($product['vehicle_model'], $config)) ?></span>
            <span class="product-card__category"><?= e(categoryLabel($product['category'], $config)) ?></span>
        </div>
        <h3 class="product-card__title">
            <a href="<?= url('product.php?slug=' . e($product['slug'])) ?>"><?= e($product['name']) ?></a>
        </h3>
        <p class="product-card__sku">SKU: <?= e($product['sku']) ?></p>
        <p class="product-card__stock <?= (int) $product['stock'] <= 0 ? 'stock-out' : ((int) $product['stock'] <= 5 ? 'stock-low' : 'stock-ok') ?>">
            <?php if ((int) $product['stock'] <= 0): ?>
                Out of Stock
            <?php elseif ((int) $product['stock'] <= 5): ?>
                Only <?= (int) $product['stock'] ?> left
            <?php else: ?>
                In Stock: <?= (int) $product['stock'] ?>
            <?php endif; ?>
        </p>
        <div class="product-card__footer">
            <div class="product-card__price">
                <span class="price-current"><?= formatPrice((float) $product['price'], $config) ?></span>
                <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                <span class="price-compare"><?= formatPrice((float) $product['compare_price'], $config) ?></span>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn--primary btn--sm btn-add-cart" 
                    data-id="<?= (int) $product['id'] ?>"
                    <?= (int) $product['stock'] <= 0 ? 'disabled' : '' ?>>
                <?= (int) $product['stock'] <= 0 ? 'Out of Stock' : 'Add to Cart' ?>
            </button>
        </div>
    </div>
</article>
