<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
requireAdmin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid request.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE id = ?');
        $stmt->execute([(int) $_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if (!$admin) {
            $errors[] = 'Admin account not found.';
        } elseif (!password_verify($currentPassword, $admin['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirmation do not match.';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters long.';
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([
                $hash,
                (int) $_SESSION['admin_id'],
            ]);
            flash('success', 'Password changed successfully.');
            header('Location: ' . url('admin/index.php'));
            exit;
        }
    }
}

$adminTitle = 'Change Password';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <h1>Change Password</h1>
    <a href="<?= url('admin/index.php') ?>" class="btn btn--ghost">← Back</a>
</div>

<?php foreach ($errors as $err): ?>
<div class="alert alert--error"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" class="admin-form glass-card">
    <?= csrfField() ?>

    <div class="form-grid">
        <div class="form-group form-group--full">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" class="input" required autocomplete="current-password">
        </div>
        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" class="input" required minlength="8" autocomplete="new-password">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="input" required minlength="8" autocomplete="new-password">
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary btn--lg">Update Password</button>
        <a href="<?= url('admin/index.php') ?>" class="btn btn--outline">Cancel</a>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
