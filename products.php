<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$category = $_GET['category'] ?? '';
$vehicle = $_GET['vehicle'] ?? '';
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = $config['items_per_page'];

$where = ['is_active = 1'];
$params = [];

if ($category && isset($config['categories'][$category])) {
    $where[] = 'category = ?';
    $params[] = $category;
}
if ($vehicle && isset($config['vehicle_models'][$vehicle])) {
    $where[] = 'vehicle_model = ?';
    $params[] = $vehicle;
}
if ($search !== '') {
    $where[] = '(name LIKE ? OR short_description LIKE ? OR sku LIKE ?)';
    $term = '%' . $search . '%';
    $params = array_merge($params, [$term, $term, $term]);
}

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$pagination = paginate($total, $perPage, $page);

$orderBy = match ($sort) {
    'price_asc' => 'price ASC',
    'price_desc' => 'price DESC',
    'name' => 'name ASC',
    default => 'created_at DESC',
};

$stmt = $pdo->prepare(
    "SELECT * FROM products WHERE {$whereSql} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$pagination['offset']}"
);
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Product Catalog';
if ($vehicle) {
    $pageTitle = vehicleLabel($vehicle, $config) . ' Parts';
} elseif ($category) {
    $pageTitle = categoryLabel($category, $config);
}
$pageDescription = 'Browse premium Toyota van spare parts and modifications.';

require __DIR__ . '/includes/header.php';
?>

<div class="page-header animate-in">
    <div class="page-header__breadcrumb">
        <a href="<?= url('index.php') ?>">Home</a>
        <span>/</span>
        <span>Catalog</span>
    </div>
    <h1><?= e($pageTitle) ?></h1>
    <p><?= $total ?> product<?= $total !== 1 ? 's' : '' ?> found</p>
</div>

<div class="catalog-toolbar glass-panel">
    <form class="catalog-search" method="get" action="">
        <?php if ($category): ?><input type="hidden" name="category" value="<?= e($category) ?>"><?php endif; ?>
        <?php if ($vehicle): ?><input type="hidden" name="vehicle" value="<?= e($vehicle) ?>"><?php endif; ?>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search parts, SKU..." class="input">
        <button type="submit" class="btn btn--primary btn--sm">Search</button>
    </form>
    <div class="catalog-filters">
        <select name="sort" class="select" id="sortSelect">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A-Z</option>
        </select>
    </div>
</div>

<?php if ($category || $vehicle || $search): ?>
<div class="active-filters">
    <?php if ($vehicle): ?>
    <a href="?<?= http_build_query(array_filter(['category' => $category, 'q' => $search ?: null])) ?>" class="filter-tag">
        <?= e(vehicleLabel($vehicle, $config)) ?> ×
    </a>
    <?php endif; ?>
    <?php if ($category): ?>
    <a href="?<?= http_build_query(array_filter(['vehicle' => $vehicle, 'q' => $search ?: null])) ?>" class="filter-tag">
        <?= e(categoryLabel($category, $config)) ?> ×
    </a>
    <?php endif; ?>
    <?php if ($search): ?>
    <a href="?<?= http_build_query(array_filter(['category' => $category, 'vehicle' => $vehicle])) ?>" class="filter-tag">
        "<?= e($search) ?>" ×
    </a>
    <?php endif; ?>
    <a href="<?= url('products.php') ?>" class="filter-clear">Clear all</a>
</div>
<?php endif; ?>

<?php if (empty($products)): ?>
<div class="empty-state glass-card">
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
    <h3>No products found</h3>
    <p>Try adjusting your filters or search terms.</p>
    <a href="<?= url('products.php') ?>" class="btn btn--outline">View All Products</a>
</div>
<?php else: ?>
<div class="product-grid">
    <?php foreach ($products as $i => $product): ?>
    <?php include __DIR__ . '/includes/product-card.php'; ?>
    <?php endforeach; ?>
</div>

<?php if ($pagination['total_pages'] > 1): ?>
<nav class="pagination">
    <?php if ($pagination['current_page'] > 1): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>" class="pagination__btn">← Prev</a>
    <?php endif; ?>
    <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
        <?php if ($p === 1 || $p === $pagination['total_pages'] || abs($p - $pagination['current_page']) <= 2): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>" 
           class="pagination__num <?= $p === $pagination['current_page'] ? 'active' : '' ?>"><?= $p ?></a>
        <?php elseif ($p === 2 || $p === $pagination['total_pages'] - 1): ?>
        <span class="pagination__dots">…</span>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>" class="pagination__btn">Next →</a>
    <?php endif; ?>
</nav>
<?php endif; ?>
<?php endif; ?>

<script>
document.getElementById('sortSelect')?.addEventListener('change', function() {
    const p = new URLSearchParams(location.search);
    p.set('sort', this.value);
    location.search = p.toString();
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
