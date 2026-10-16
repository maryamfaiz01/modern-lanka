<?php
declare(strict_types=1);

/**
 * Copy to config/stripe.php and add your keys from:
 * https://dashboard.stripe.com/test/apikeys
 */
return [
    'publishable_key' => 'pk_test_YOUR_PUBLISHABLE_KEY',
    'secret_key' => 'sk_test_YOUR_SECRET_KEY',
    'webhook_secret' => 'whsec_YOUR_WEBHOOK_SECRET',
    'currency' => 'lkr',
];
