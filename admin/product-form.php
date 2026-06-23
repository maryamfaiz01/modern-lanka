<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
$product = null;
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) {
        flash('error', 'Product not found.');
        header('Location: ' . url('admin/products.php'));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request.';
    } else {
        $data = [
            'sku' => trim($_POST['sku'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'slug' => slugify(trim($_POST['slug'] ?? $_POST['name'] ?? '')),
            'description' => trim($_POST['description'] ?? ''),
            'short_description' => trim($_POST['short_description'] ?? ''),
            'price' => (float) ($_POST['price'] ?? 0),
            'compare_price' => $_POST['compare_price'] !== '' ? (float) $_POST['compare_price'] : null,
            'category' => $_POST['category'] ?? 'spare-parts',
            'vehicle_model' => $_POST['vehicle_model'] ?? 'universal',
            'stock' => (int) ($_POST['stock'] ?? 0),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($data['sku'] === '' || $data['name'] === '') {
            $errors[] = 'SKU and name are required.';
        }

        $imageName = $product['image'] ?? null;
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $config['allowed_image_types'], true)) {
                $errors[] = 'Invalid image type. Use JPG, PNG, or WebP.';
            } elseif ($file['size'] > $config['max_upload_size']) {
                $errors[] = 'Image too large. Max 5MB.';
            } else {
                if (!is_dir($config['upload_path'])) {
                    mkdir($config['upload_path'], 0755, true);
                }
                $ext = match ($mime) {
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    default => 'jpg',
                };
                $imageName = $data['slug'] . '-' . time() . '.' . $ext;
                move_uploaded_file($file['tmp_name'], $config['upload_path'] . $imageName);
                if ($product && $product['image'] && file_exists($config['upload_path'] . $product['image'])) {
                    @unlink($config['upload_path'] . $product['image']);
                }
            }
        }

        if (empty($errors)) {
            if ($id) {
                $sql = 'UPDATE products SET sku=?, name=?, slug=?, description=?, short_description=?, price=?, compare_price=?, category=?, vehicle_model=?, stock=?, is_featured=?, is_active=?';
                $params = [
                    $data['sku'], $data['name'], $data['slug'], $data['description'], $data['short_description'],
                    $data['price'], $data['compare_price'], $data['category'], $data['vehicle_model'],
                    $data['stock'], $data['is_featured'], $data['is_active'],
                ];
                if ($imageName) {
                    $sql .= ', image=?';
                    $params[] = $imageName;
                }
                $sql .= ' WHERE id=?';
                $params[] = $id;
                $pdo->prepare($sql)->execute($params);
                flash('success', 'Product updated successfully.');
            } else {
                $pdo->prepare(
                    'INSERT INTO products (sku, name, slug, description, short_description, price, compare_price, category, vehicle_model, image, stock, is_featured, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
                )->execute([
                    $data['sku'], $data['name'], $data['slug'], $data['description'], $data['short_description'],
                    $data['price'], $data['compare_price'], $data['category'], $data['vehicle_model'],
                    $imageName, $data['stock'], $data['is_featured'], $data['is_active'],
                ]);
                flash('success', 'Product created successfully.');
            }
            header('Location: ' . url('admin/products.php'));
            exit;
        }
        $product = array_merge($product ?? [], $data, ['image' => $imageName]);
    }
}

$adminTitle = $id ? 'Edit Product' : 'Add Product';
require __DIR__ . '/includes/header.php';
$p = $product ?? $_POST;
?>

<div class="admin-page-header">
    <h1><?= $id ? 'Edit Product' : 'Add Product' ?></h1>
    <a href="<?= url('admin/products.php') ?>" class="btn btn--ghost">← Back</a>
</div>

<?php foreach ($errors as $err): ?>
<div class="alert alert--error"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="admin-form glass-card">
    <?= csrfField() ?>

    <div class="form-grid">
        <div class="form-group">
            <label for="name">Product Name *</label>
            <input type="text" id="name" name="name" class="input" required value="<?= e($p['name'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="sku">SKU *</label>
            <input type="text" id="sku" name="sku" class="input" required value="<?= e($p['sku'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="slug">URL Slug</label>
            <input type="text" id="slug" name="slug" class="input" value="<?= e($p['slug'] ?? '') ?>" placeholder="auto-generated from name">
        </div>
        <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category" class="select">
                <?php foreach ($config['categories'] as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= ($p['category'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="vehicle_model">Vehicle Model</label>
            <select id="vehicle_model" name="vehicle_model" class="select">
                <?php foreach ($config['vehicle_models'] as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= ($p['vehicle_model'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="price">Price (LKR) *</label>
            <input type="number" id="price" name="price" class="input" step="0.01" min="0" required value="<?= e((string) ($p['price'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="compare_price">Compare Price (optional)</label>
            <input type="number" id="compare_price" name="compare_price" class="input" step="0.01" min="0" value="<?= e((string) ($p['compare_price'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="stock">Stock Quantity</label>
            <input type="number" id="stock" name="stock" class="input" min="0" value="<?= e((string) ($p['stock'] ?? '0')) ?>">
        </div>
        <div class="form-group form-group--full">
            <label for="short_description">Short Description</label>
            <input type="text" id="short_description" name="short_description" class="input" maxlength="500" value="<?= e($p['short_description'] ?? '') ?>">
        </div>
        <div class="form-group form-group--full">
            <label for="description">Full Description</label>
            <textarea id="description" name="description" class="input textarea" rows="6"><?= e($p['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label for="image">Product Image</label>
            <input type="file" id="image" name="image" class="input" accept="image/jpeg,image/png,image/webp">
            <?php if (!empty($p['image'])): ?>
            <img src="<?= productImageUrl($p['image'], $config) ?>" alt="" class="form-preview-img">
            <?php endif; ?>
        </div>
        <div class="form-group form-checks">
            <label class="checkbox-label">
                <input type="checkbox" name="is_featured" <?= !empty($p['is_featured']) ? 'checked' : '' ?>>
                Featured product
            </label>
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" <?= ($p['is_active'] ?? 1) ? 'checked' : '' ?>>
                Visible on website
            </label>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary btn--lg"><?= $id ? 'Update Product' : 'Create Product' ?></button>
        <a href="<?= url('admin/products.php') ?>" class="btn btn--outline">Cancel</a>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
