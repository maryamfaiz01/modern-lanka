<?php
/** @var array $config */
/** @var string $pageTitle */
/** @var string $pageDescription */
/** @var PDO $pdo */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$pageTitle = $pageTitle ?? $config['name'];
$pageDescription = $pageDescription ?? $config['tagline'];
$loggedInCustomer = getLoggedInCustomer($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <title><?= e($pageTitle) ?> | <?= e($config['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/main.css') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= asset('images/favicon.svg') ?>">
</head>
<body>
    <div class="grain-overlay" aria-hidden="true"></div>
    <div class="ambient-glow ambient-glow--gold" aria-hidden="true"></div>
    <div class="ambient-glow ambient-glow--blue" aria-hidden="true"></div>

    <header class="site-header" id="siteHeader">
        <nav class="top-nav glass-panel">
            <a href="<?= url('index.php') ?>" class="brand">
                <span class="brand__icon">
                    <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 28L20 8L32 28H8Z" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M12 28H28" stroke="currentColor" stroke-width="1.5"/>
                        <circle cx="20" cy="22" r="3" fill="currentColor"/>
                    </svg>
                </span>
                <span class="brand__text">
                    <span class="brand__name">Modern Lanka</span>
                    <span class="brand__sub">Motors</span>
                </span>
            </a>

            <div class="top-nav__links" id="topNavLinks">
                <a href="<?= url('index.php') ?>" class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>">Home</a>
                <a href="<?= url('products.php') ?>" class="nav-link <?= in_array($currentPage, ['products', 'product']) ? 'active' : '' ?>">Catalog</a>
                <a href="<?= url('index.php#vehicles') ?>" class="nav-link">Vehicles</a>
                <a href="<?= url('index.php#about') ?>" class="nav-link">About</a>
                <a href="<?= url('index.php#contact') ?>" class="nav-link">Contact</a>
                <?php if ($loggedInCustomer): ?>
                <a href="<?= url('my-orders.php') ?>" class="nav-link <?= $currentPage === 'my-orders' ? 'active' : '' ?>">My Orders</a>
                <a href="<?= url('logout.php') ?>" class="nav-link">Logout</a>
                <?php else: ?>
                <a href="<?= url('login.php') ?>" class="nav-link <?= $currentPage === 'login' ? 'active' : '' ?>">Login</a>
                <a href="<?= url('register.php') ?>" class="nav-link <?= $currentPage === 'register' ? 'active' : '' ?>">Register</a>
                <?php endif; ?>
            </div>

            <div class="top-nav__actions">
                <a href="<?= url('cart.php') ?>" class="btn-icon cart-btn" aria-label="Shopping cart">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M6 6h15l-1.5 9h-12z"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/>
                        <path d="M6 6L5 3H2"/>
                    </svg>
                    <span class="cart-badge" id="cartBadge"><?= cartCount() ?></span>
                </a>
                <a href="<?= whatsappLink($config['whatsapp'], 'Hello! I need assistance with van parts.') ?>" 
                   class="btn btn--whatsapp btn--sm" target="_blank" rel="noopener">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.75.75 0 00.918.918l4.458-1.495A11.95 11.95 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75a9.714 9.714 0 01-4.89-1.312l-.35-.208-3.263 1.096 1.096-3.182-.227-.363A9.712 9.712 0 012.25 12C2.25 6.615 6.615 2.25 12 2.25S21.75 6.615 21.75 12 17.385 21.75 12 21.75z"/></svg>
                    WhatsApp
                </a>
                <button class="btn-icon mobile-toggle" id="mobileToggle" aria-label="Menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </nav>
    </header>

    <div class="layout-wrapper">
        <aside class="sidebar glass-panel" id="sidebar">
            <div class="sidebar__section">
                <h3 class="sidebar__title">Shop by Vehicle</h3>
                <ul class="sidebar__list">
                    <?php foreach ($config['vehicle_models'] as $key => $label): ?>
                    <li>
                        <a href="<?= url('products.php?vehicle=' . $key) ?>" 
                           class="sidebar__link <?= ($_GET['vehicle'] ?? '') === $key ? 'active' : '' ?>">
                            <span class="sidebar__dot"></span>
                            <?= e($label) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="sidebar__section">
                <h3 class="sidebar__title">Categories</h3>
                <ul class="sidebar__list">
                    <?php foreach ($config['categories'] as $key => $label): ?>
                    <li>
                        <a href="<?= url('products.php?category=' . $key) ?>" 
                           class="sidebar__link <?= ($_GET['category'] ?? '') === $key ? 'active' : '' ?>">
                            <?= e($label) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="sidebar__cta glass-card">
                <p>Need a custom modification?</p>
                <a href="<?= whatsappLink($config['whatsapp'], 'Hi, I need a custom modification quote for my van.') ?>" 
                   class="btn btn--outline btn--block" target="_blank" rel="noopener">Get a Quote</a>
            </div>
        </aside>

        <main class="main-content">
