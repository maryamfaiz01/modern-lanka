<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$featured = $pdo->query(
    'SELECT * FROM products WHERE is_active = 1 AND is_featured = 1 ORDER BY created_at DESC LIMIT 6'
)->fetchAll();

$heroTitle = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'hero_title'")->fetchColumn()
    ?: 'Engineered for Sri Lanka\'s Finest Vans';
$heroSubtitle = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'hero_subtitle'")->fetchColumn()
    ?: $config['tagline'];
$announcement = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'announcement'")->fetchColumn();

$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
$flash = getFlash();
?>

<?php if ($flash): ?>
<div class="toast toast--<?= e($flash['type']) ?>" data-toast><?= e($flash['message']) ?></div>
<?php endif; ?>

<?php if ($announcement): ?>
<div class="announcement-bar">
    <span class="announcement-bar__pulse"></span>
    <?= e($announcement) ?>
</div>
<?php endif; ?>

<section class="hero">
    <div class="hero__bg">
        <div class="hero__grid"></div>
        <div class="hero__lines"></div>
    </div>
    <div class="hero__content animate-in">
        <span class="hero__badge">Toyota Van Specialists — Sri Lanka</span>
        <h1 class="hero__title"><?= e($heroTitle) ?></h1>
        <p class="hero__subtitle"><?= e($heroSubtitle) ?></p>
        <div class="hero__actions">
            <a href="<?= url('products.php') ?>" class="btn btn--primary btn--lg">
                Browse Catalog
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
            <a href="<?= whatsappLink($config['whatsapp'], 'Hi! I\'m interested in van parts and modifications.') ?>" 
               class="btn btn--outline btn--lg" target="_blank" rel="noopener">WhatsApp Us</a>
        </div>
        <div class="hero__stats">
            <div class="stat-item">
                <span class="stat-item__value">500+</span>
                <span class="stat-item__label">Parts In Stock</span>
            </div>
            <div class="stat-item">
                <span class="stat-item__value">3</span>
                <span class="stat-item__label">Vehicle Platforms</span>
            </div>
            <div class="stat-item">
                <span class="stat-item__value">10+</span>
                <span class="stat-item__label">Years Experience</span>
            </div>
        </div>
    </div>
    <div class="hero__visual animate-in delay-2">
        <div class="hero-card glass-card">
            <div class="hero-card__glow"></div>
            <div class="hero-card__vehicle">
                <svg viewBox="0 0 400 200" class="van-silhouette">
                    <defs>
                        <linearGradient id="vanGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" style="stop-color:#c9a227;stop-opacity:0.3"/>
                            <stop offset="100%" style="stop-color:#c9a227;stop-opacity:0.8"/>
                        </linearGradient>
                    </defs>
                    <path d="M40 140 Q40 100 80 90 L120 85 L140 60 L280 60 L300 85 L340 90 Q380 100 380 140 L380 155 L40 155 Z" fill="url(#vanGrad)" stroke="#c9a227" stroke-width="1"/>
                    <rect x="150" y="75" width="50" height="35" rx="4" fill="none" stroke="#c9a227" stroke-width="0.8" opacity="0.6"/>
                    <rect x="220" y="75" width="50" height="35" rx="4" fill="none" stroke="#c9a227" stroke-width="0.8" opacity="0.6"/>
                    <circle cx="100" cy="155" r="22" fill="#0a0a0b" stroke="#c9a227" stroke-width="2"/>
                    <circle cx="100" cy="155" r="10" fill="#1a1a1c"/>
                    <circle cx="320" cy="155" r="22" fill="#0a0a0b" stroke="#c9a227" stroke-width="2"/>
                    <circle cx="320" cy="155" r="10" fill="#1a1a1c"/>
                </svg>
            </div>
            <div class="hero-card__models">
                <span>Hiace</span>
                <span>Dolphin</span>
                <span>HiRoof</span>
            </div>
        </div>
    </div>
</section>

<section class="vehicles-section" id="vehicles">
    <div class="section-header animate-on-scroll">
        <span class="section-label">Our Expertise</span>
        <h2>Built for Your Van Platform</h2>
        <p>Precision-fit parts and modifications for Sri Lanka's most popular commercial vans.</p>
    </div>
    <div class="vehicle-cards">
        <?php
        $vehicles = [
            ['key' => 'hiace', 'name' => 'Toyota Hiace', 'desc' => 'KDH200 series body kits, lighting, suspension & interior upgrades.', 'icon' => 'M'],
            ['key' => 'dolphin', 'name' => 'Toyota Dolphin', 'desc' => 'Exterior styling, seat covers, spoilers & comfort modifications.', 'icon' => 'D'],
            ['key' => 'hiroof', 'name' => 'Toyota HiRoof', 'desc' => 'High-roof conversion kits, interior panels & window systems.', 'icon' => 'H'],
        ];
        foreach ($vehicles as $i => $v):
        ?>
        <a href="<?= url('products.php?vehicle=' . $v['key']) ?>" class="vehicle-card glass-card animate-on-scroll" style="--delay: <?= $i * 0.1 ?>s">
            <div class="vehicle-card__icon"><?= $v['icon'] ?></div>
            <h3><?= e($v['name']) ?></h3>
            <p><?= e($v['desc']) ?></p>
            <span class="vehicle-card__link">View Parts →</span>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="featured-section">
    <div class="section-header animate-on-scroll">
        <span class="section-label">Featured</span>
        <h2>Premium Selection</h2>
        <a href="<?= url('products.php') ?>" class="section-link">View all products →</a>
    </div>
    <div class="product-grid">
        <?php foreach ($featured as $i => $product): ?>
        <?php include __DIR__ . '/includes/product-card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="about-section" id="about">
    <div class="about-grid">
        <div class="about-content animate-on-scroll">
            <span class="section-label">About Us</span>
            <h2>Sri Lanka's Van Modification Authority</h2>
            <p>Modern Lanka Motors specialises in genuine-quality spare parts and bespoke modifications for Toyota Hiace, Dolphin, and HiRoof vans. From body kits to complete interior transformations, we deliver factory-grade fitment with premium materials.</p>
            <ul class="about-features">
                <li>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                    Factory-fit parts & professional installation support
                </li>
                <li>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                    Custom modification design & fabrication
                </li>
                <li>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                    Island-wide delivery & WhatsApp ordering
                </li>
            </ul>
        </div>
        <div class="about-visual glass-card animate-on-scroll">
            <div class="about-visual__inner">
                <div class="about-metric">
                    <span class="about-metric__num">98%</span>
                    <span class="about-metric__text">Customer Satisfaction</span>
                </div>
                <div class="about-metric">
                    <span class="about-metric__num">24h</span>
                    <span class="about-metric__text">Quote Response Time</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cta-section glass-panel animate-on-scroll">
    <h2>Ready to upgrade your van?</h2>
    <p>Browse our catalog or message us on WhatsApp for expert advice.</p>
    <div class="cta-actions">
        <a href="<?= url('products.php') ?>" class="btn btn--primary">Shop Now</a>
        <a href="<?= whatsappLink($config['whatsapp'], 'Hi! I need help choosing parts for my van.') ?>" 
           class="btn btn--whatsapp" target="_blank" rel="noopener">Chat on WhatsApp</a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
