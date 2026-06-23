<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
requireAdmin();

$search = trim($_GET['q'] ?? '');
$where = '1=1';
$params = [];

if ($search) {
    $where .= ' AND (name LIKE ? OR sku LIKE ?)';
    $term = '%' . $search . '%';
    $params = [$term, $term];
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE {$where} ORDER BY created_at DESC");
$stmt->execute($params);
$products = $stmt->fetchAll();

if (isset($_GET['delete']) && verifyCsrf($_GET['token'] ?? null)) {
    $id = (int) $_GET['delete'];
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    flash('success', 'Product deleted.');
    header('Location: ' . url('admin/products.php'));
    exit;
}

$adminTitle = 'Products';
require __DIR__ . '/includes/header.php';
$flash = getFlash();
?>

<div class="admin-page-header admin-page-header--flex">
    <div>
        <h1>Products</h1>
        <p><?= count($products) ?> products</p>
    </div>
    <a href="<?= url('admin/product-form.php') ?>" class="btn btn--primary">+ Add Product</a>
</div>

<?php if ($flash): ?>
<div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>

<form class="admin-search glass-panel" method="get">
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search by name or SKU..." class="input">
    <button type="submit" class="btn btn--outline">Search</button>
</form>

<div class="admin-panel glass-card" style="margin-top: 24px; padding: 0; overflow: hidden;">
    <table class="admin-table admin-table--full">
        <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Category</th>
                <th>Vehicle</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td>
                    <div class="table-product">
                        <img src="<?= productImageUrl($p['image'], $config) ?>" alt="" width="48" height="48">
                        <span><?= e($p['name']) ?></span>
                    </div>
                </td>
                <td><?= e($p['sku']) ?></td>
                <td><?= e(categoryLabel($p['category'], $config)) ?></td>
                <td><?= e(vehicleLabel($p['vehicle_model'], $config)) ?></td>
                <td><?= formatPrice((float) $p['price'], $config) ?></td>
                <td>
                    <span class="stock-pill <?= (int) $p['stock'] <= 5 ? 'stock-pill--low' : '' ?>">
                        <?= (int) $p['stock'] ?>
                    </span>
                </td>
                <td>
                    <?php if ($p['is_active']): ?>
                    <span class="status-badge status-badge--completed">Active</span>
                    <?php else: ?>
                    <span class="status-badge status-badge--cancelled">Hidden</span>
                    <?php endif; ?>
                </td>
                <td class="table-actions">
                    <a href="<?= url('admin/product-form.php?id=' . $p['id']) ?>" class="btn btn--sm btn--outline">Edit</a>
                    <a href="?delete=<?= $p['id'] ?>&token=<?= e(csrfToken()) ?>" 
                       class="btn btn--sm btn--danger"
                       onclick="return confirm('Delete this product?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
