<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$cartItems = getCartItems($pdo);
$subtotal = array_sum(array_column($cartItems, 'line_total'));
$loggedInCustomer = getLoggedInCustomer($pdo);

$pageTitle = 'Shopping Cart';
$extraScripts = ['cart.js'];
require __DIR__ . '/includes/header.php';
?>

<div class="page-header animate-in">
    <h1>Shopping Cart</h1>
    <p><?= count($cartItems) ?> item<?= count($cartItems) !== 1 ? 's' : '' ?> in your cart</p>
</div>

<?php if (empty($cartItems)): ?>
<div class="empty-state glass-card">
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M6 6h15l-1.5 9h-12z"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
    <h3>Your cart is empty</h3>
    <p>Browse our catalog and add premium van parts to your cart.</p>
    <a href="<?= url('products.php') ?>" class="btn btn--primary">Browse Catalog</a>
</div>
<?php else: ?>

<div class="cart-layout">
    <div class="cart-items">
        <?php foreach ($cartItems as $item): ?>
        <div class="cart-item glass-card" data-cart-item="<?= (int) $item['id'] ?>">
            <a href="<?= url('product.php?slug=' . e($item['slug'])) ?>" class="cart-item__image">
                <img src="<?= productImageUrl($item['image'], $config) ?>" alt="<?= e($item['name']) ?>">
            </a>
            <div class="cart-item__details">
                <h3><a href="<?= url('product.php?slug=' . e($item['slug'])) ?>"><?= e($item['name']) ?></a></h3>
                <p class="cart-item__sku">SKU: <?= e($item['sku']) ?></p>
                <p class="cart-item__price"><?= formatPrice((float) $item['price'], $config) ?> each</p>
            </div>
            <div class="cart-item__qty">
                <div class="qty-selector qty-selector--sm">
                    <button type="button" class="qty-btn cart-qty-btn" data-action="minus" data-id="<?= (int) $item['id'] ?>">−</button>
                    <input type="number" value="<?= (int) $item['qty'] ?>" min="1" max="<?= (int) $item['stock'] ?>" 
                           class="qty-input cart-qty-input" data-id="<?= (int) $item['id'] ?>">
                    <button type="button" class="qty-btn cart-qty-btn" data-action="plus" data-id="<?= (int) $item['id'] ?>">+</button>
                </div>
            </div>
            <div class="cart-item__total">
                <span class="line-total"><?= formatPrice($item['line_total'], $config) ?></span>
                <button type="button" class="btn-remove cart-remove" data-id="<?= (int) $item['id'] ?>" aria-label="Remove">×</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <aside class="cart-summary glass-card">
        <h3>Order Summary</h3>
        <div class="summary-row">
            <span>Subtotal</span>
            <span id="cartSubtotal"><?= formatPrice($subtotal, $config) ?></span>
        </div>
        <div class="summary-row summary-row--note">
            <span>Delivery</span>
            <span>Calculated on order</span>
        </div>
        <div class="summary-row summary-row--total">
            <span>Total</span>
            <span id="cartTotal"><?= formatPrice($subtotal, $config) ?></span>
        </div>

        <form action="<?= url('checkout.php') ?>" method="post" class="checkout-form">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="customer_name">Your Name</label>
                <input type="text" id="customer_name" name="customer_name" class="input" required placeholder="Full name"
                       value="<?= e($loggedInCustomer['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="customer_phone">Phone / WhatsApp</label>
                <input type="tel" id="customer_phone" name="customer_phone" class="input" required placeholder="07X XXX XXXX"
                       value="<?= e($loggedInCustomer['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="notes">Order Notes (optional)</label>
                <textarea id="notes" name="notes" class="input textarea" rows="3" placeholder="Delivery address, vehicle model, special requests..."></textarea>
            </div>
            <button type="submit" class="btn btn--whatsapp btn--block btn--lg">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                Place Order via WhatsApp
            </button>
            <p class="form-hint">You'll be redirected to WhatsApp with your order details pre-filled.</p>
        </form>

        <a href="<?= url('products.php') ?>" class="btn btn--ghost btn--block">Continue Shopping</a>
    </aside>
</div>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
