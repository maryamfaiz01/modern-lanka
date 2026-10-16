<?php
declare(strict_types=1);

return [
    'name' => 'Modern Lanka Motors',
    'tagline' => 'Premium Toyota Van Parts & Modifications',
    'whatsapp' => '94773721604',
    'whatsapp_display' => '+94 77 372 1604',
    'email' => 'info@modernlankamotors.lk',
    'phone' => '+94 77 372 1604',
    'address' => 'Gampola, Sri Lanka',
    'social' => [
        'facebook' => '',
        'instagram' => '',
        'tiktok' => 'https://www.tiktok.com/@modern.lanka.motors',
        'whatsapp' => '',
    ],
    'currency' => 'LKR',
    'currency_symbol' => 'Rs.',
    'items_per_page' => 12,
    'upload_path' => dirname(__DIR__) . '/uploads/products/',
    // Resolved at runtime in bootstrap.php (works at domain root or in a subfolder).
    'upload_url' => '',
    'allowed_image_types' => ['image/jpeg', 'image/png', 'image/webp'],
    'max_upload_size' => 5 * 1024 * 1024,
    'vehicle_models' => [
        'hiace' => 'Toyota Hiace',
        'dolphin' => 'Toyota Dolphin',
        'hiroof' => 'Toyota HiRoof',
        'universal' => 'Universal / All Models',
    ],
    'categories' => [
        'body-kits' => 'Body Kits & Exterior',
        'interior' => 'Interior & Trim',
        'lighting' => 'Lighting & LEDs',
        'suspension' => 'Suspension & Handling',
        'engine' => 'Engine & Performance',
        'electrical' => 'Electrical & Electronics',
        'wheels' => 'Wheels & Tyres',
        'accessories' => 'Accessories',
        'modifications' => 'Custom Modifications',
        'spare-parts' => 'OEM Spare Parts',
    ],
];
