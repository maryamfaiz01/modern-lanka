<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (!empty($_SESSION['customer_id'])) {
    header('Location: ' . url('index.php'));
    exit;
}

$errors = [];
$form = [
    'name' => '',
    'phone' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['name'] = trim($_POST['name'] ?? '');
    $form['phone'] = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($form['name'] === '') {
        $errors[] = 'Full name is required.';
    }
    if ($form['phone'] === '') {
        $errors[] = 'Phone number is required.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM customers WHERE phone = ?');
        $stmt->execute([$form['phone']]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this phone number already exists.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare(
                'INSERT INTO customers (name, phone, password_hash) VALUES (?, ?, ?)'
            )->execute([$form['name'], $form['phone'], $passwordHash]);

            $_SESSION['customer_id'] = (int) $pdo->lastInsertId();
            header('Location: ' . url('index.php'));
            exit;
        }
    }
}

$pageTitle = 'Register';
$pageDescription = 'Create an account to track your orders.';
require __DIR__ . '/includes/header.php';
?>

<div class="page-header animate-in">
    <div class="page-header__breadcrumb">
        <a href="<?= url('index.php') ?>">Home</a>
        <span>/</span>
        <span>Register</span>
    </div>
    <h1>Create Account</h1>
    <p>Register to view your order history</p>
</div>

<?php foreach ($errors as $err): ?>
<div class="alert alert--error"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" class="auth-form glass-card">
    <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" class="input" required value="<?= e($form['name']) ?>">
    </div>
    <div class="form-group">
        <label for="phone">Phone Number</label>
        <input type="tel" id="phone" name="phone" class="input" required value="<?= e($form['phone']) ?>" placeholder="07X XXX XXXX">
    </div>
    <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" class="input" required minlength="6">
    </div>
    <div class="form-group">
        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" class="input" required minlength="6">
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn--primary btn--lg">Create Account</button>
    </div>
    <p class="auth-form__footer">Already have an account? <a href="<?= url('login.php') ?>">Sign in</a></p>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
