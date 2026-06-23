<?php
declare(strict_types=1);

/**
 * One-time installer — delete this file after successful setup.
 */
$config = require __DIR__ . '/config/database.php';
$app = require __DIR__ . '/config/app.php';
$message = '';
$error = '';
$success = false;
$alreadyInstalled = false;

function connectServer(array $config): PDO
{
    $dsn = sprintf('mysql:host=%s;charset=%s', $config['host'], $config['charset']);
    return new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
}

function isDatabaseInstalled(PDO $pdo, string $dbname): bool
{
    try {
        $stmt = $pdo->query(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = " . $pdo->quote($dbname) . " AND table_name = 'admins'"
        );
        if ((int) $stmt->fetchColumn() === 0) {
            return false;
        }
        $pdo->exec('USE `' . str_replace('`', '``', $dbname) . '`');
        return (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
    } catch (PDOException) {
        return false;
    }
}

function runSchema(PDO $pdo, string $schemaPath): void
{
    $sql = file_get_contents($schemaPath);
    if ($sql === false) {
        throw new RuntimeException('Could not read database/schema.sql');
    }

    // Remove SQL comments
    $sql = preg_replace('/--.*$/m', '', $sql) ?? $sql;

    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
}

try {
    $pdo = connectServer($config);
    $alreadyInstalled = isDatabaseInstalled($pdo, $config['dbname']);
    if ($alreadyInstalled && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $success = true;
        $message = 'Database is already installed and ready to use.';
    }
} catch (PDOException $e) {
    $error = 'Could not connect to MySQL. Start MySQL in XAMPP and check config/database.php.<br><small>' . htmlspecialchars($e->getMessage()) . '</small>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    try {
        runSchema($pdo, __DIR__ . '/database/schema.sql');
        $success = true;
        $alreadyInstalled = isDatabaseInstalled($pdo, $config['dbname']);

        if ($alreadyInstalled) {
            $message = 'Database is ready! Default admin: username <strong>admin</strong>, password <strong>password</strong>. Delete install.php and change your password immediately.';
        } else {
            $message = 'Tables created, but admin account was not found. Please run install again or import database/schema.sql manually.';
        }
    } catch (PDOException $e) {
        if (isDatabaseInstalled($pdo, $config['dbname'])) {
            $success = true;
            $alreadyInstalled = true;
            $message = 'Database is already installed and ready to use.';
        } else {
            $error = 'Installation failed: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install | <?= htmlspecialchars($app['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Outfit', sans-serif; background: #060608; color: #f4f2ef; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { max-width: 480px; width: 100%; background: rgba(18,18,22,0.9); border: 1px solid rgba(255,255,255,0.06); border-radius: 20px; padding: 40px; }
        h1 { font-size: 1.5rem; margin-bottom: 8px; }
        p { color: #9a9690; margin-bottom: 24px; font-size: 0.9rem; line-height: 1.6; }
        .btn { display: inline-block; padding: 14px 28px; background: linear-gradient(135deg, #c9a227, #a88620); color: #060608; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; font-size: 1rem; width: 100%; text-align: center; }
        .alert { padding: 14px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; line-height: 1.5; }
        .alert-error { background: rgba(248,113,113,0.1); border: 1px solid #f87171; color: #f87171; }
        .alert-success { background: rgba(74,222,128,0.1); border: 1px solid #4ade80; color: #4ade80; }
        a { color: #c9a227; }
        code { background: rgba(255,255,255,0.06); padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Modern Lanka Motors</h1>
        <p>Database installer. Ensure MySQL is running and <code>config/database.php</code> credentials are correct.</p>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success"><?= $message ?></div>
        <p>
            <a href="index.php">→ Go to website</a> &nbsp;|&nbsp;
            <a href="admin/login.php">→ Admin login</a>
        </p>
        <?php if (!$alreadyInstalled || $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <p style="font-size: 0.8rem; margin-top: 16px;">You can safely delete <code>install.php</code> now.</p>
        <?php endif; ?>
        <?php else: ?>
        <form method="post">
            <button type="submit" class="btn">Install Database</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
