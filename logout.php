<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

unset($_SESSION['customer_id']);
header('Location: ' . url('index.php'));
exit;
