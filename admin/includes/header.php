<?php
/** @var array $config */
/** @var string $adminTitle */
$adminPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminTitle ?? 'Dashboard') ?> | Admin — <?= e($config['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Syne:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin-body">
    <div class="admin-layout">
        <aside class="admin-sidebar glass-panel">
            <a href="<?= url('admin/index.php') ?>" class="admin-brand">
                <span class="brand__name">MLM Admin</span>
            </a>
            <nav class="admin-nav">
                <a href="<?= url('admin/index.php') ?>" class="admin-nav__link <?= $adminPage === 'index' ? 'active' : '' ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                    Dashboard
                </a>
                <a href="<?= url('admin/products.php') ?>" class="admin-nav__link <?= in_array($adminPage, ['products', 'product-form']) ? 'active' : '' ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                    Products
                </a>
                <a href="<?= url('admin/orders.php') ?>" class="admin-nav__link <?= $adminPage === 'orders' ? 'active' : '' ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><path d="M3 6h18M16 10a4 4 0 01-8 0"/></svg>
                    Orders
                </a>
                <a href="<?= url('admin/change-password.php') ?>" class="admin-nav__link <?= $adminPage === 'change-password' ? 'active' : '' ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    Change Password
                </a>
                <a href="<?= url('index.php') ?>" class="admin-nav__link" target="_blank">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg>
                    View Site
                </a>
            </nav>
            <div class="admin-sidebar__footer">
                <span class="admin-user"><?= e($_SESSION['admin_name'] ?? 'Admin') ?></span>
                <a href="<?= url('admin/logout.php') ?>" class="admin-logout">Sign Out</a>
            </div>
        </aside>
        <main class="admin-main">
