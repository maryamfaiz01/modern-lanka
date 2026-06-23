<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (!empty($_SESSION['customer_id'])) {
    header('Location: ' . url('index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM customers WHERE phone = ?');
    $stmt->execute([$phone]);
    $customer = $stmt->fetch();

    if ($customer && password_verify($password, $customer['password_hash'])) {
        $_SESSION['customer_id'] = (int) $customer['id'];
        header('Location: ' . url('index.php'));
        exit;
    }
    $error = 'Invalid phone number or password.';
}

$pageTitle = 'Login';
$pageDescription = 'Sign in to view your orders.';
require __DIR__ . '/includes/header.php';
?>

<div class="page-header animate-in">
    <div class="page-header__breadcrumb">
        <a href="<?= url('index.php') ?>">Home</a>
        <span>/</span>
        <span>Login</span>
    </div>
    <h1>Customer Login</h1>
    <p>Sign in to track your orders</p>
</div>

<?php if ($error): ?>
<div class="alert alert--error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" class="auth-form glass-card">
    <div class="form-group">
        <label for="phone">Phone Number</label>
        <input type="tel" id="phone" name="phone" class="input" required autofocus
               value="<?= e($_POST['phone'] ?? '') ?>" placeholder="07X XXX XXXX">
    </div>
    <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" class="input" required>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn--primary btn--lg">Sign In</button>
    </div>
    <p class="auth-form__footer">Don't have an account? <a href="<?= url('register.php') ?>">Register</a></p>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
