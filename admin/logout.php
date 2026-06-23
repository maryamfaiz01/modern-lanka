<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

unset($_SESSION['admin_id'], $_SESSION['admin_name']);
header('Location: ' . url('admin/login.php'));
exit;
