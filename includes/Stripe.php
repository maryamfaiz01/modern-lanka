<?php
declare(strict_types=1);

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

class StripeGateway
{
    private array $config;
    private \Stripe\StripeClient $stripe;

    public function __construct(?array $config = null)
    {
        if ($config === null) {
            $configFile = __DIR__ . '/../config/stripe.php';
            if (!file_exists($configFile)) {
                throw new RuntimeException('Stripe configuration file (config/stripe.php) not found.');
            }
            $loaded = require $configFile;
            if (!is_array($loaded)) {
                throw new RuntimeException('Invalid configuration returned by config/stripe.php.');
            }
            $config = $loaded;
        }

        $this->config = $config;
        $secretKey = trim((string) ($this->config['secret_key'] ?? ''));

        if ($secretKey === '') {
            throw new InvalidArgumentException('Stripe secret_key is not configured.');
        }

        // On local development, disable strict peer verification if local cURL CA bundle is missing
        if (defined('LOCAL_DEV') && LOCAL_DEV) {
            \Stripe\Stripe::setVerifySslCerts(false);
        } else {
            $caBundle = realpath(__DIR__ . '/../vendor/stripe/stripe-php/data/ca-certificates.crt');
            if ($caBundle && file_exists($caBundle)) {
                \Stripe\Stripe::setCABundlePath($caBundle);
            }
            \Stripe\Stripe::setVerifySslCerts(true);
        }

        $this->stripe = new \Stripe\StripeClient($secretKey);
    }

    public function createPaymentIntent(float $amount, string $currency, array $metadata = []): \Stripe\PaymentIntent
    {
        return $this->stripe->paymentIntents->create([
            'amount' => (int) round($amount * 100), // Convert to cents
            'currency' => strtolower($currency),
            'metadata' => $metadata,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);
    }

    public function retrievePaymentIntent(string $paymentIntentId): \Stripe\PaymentIntent
    {
        return $this->stripe->paymentIntents->retrieve($paymentIntentId);
    }

    public function constructWebhookEvent(string $payload, string $sigHeader): \Stripe\Event
    {
        $webhookSecret = trim((string) ($this->config['webhook_secret'] ?? ''));
        if ($webhookSecret === '') {
            throw new RuntimeException('Stripe webhook_secret is not configured.');
        }

        return \Stripe\Webhook::constructEvent(
            $payload,
            $sigHeader,
            $webhookSecret
        );
    }

    public function createCheckoutSession(
        array $lineItems,
        string $successUrl,
        string $cancelUrl,
        array $metadata = [],
        ?string $customerEmail = null
    ): \Stripe\Checkout\Session {
        $params = [
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
        ];

        if ($customerEmail !== null && trim($customerEmail) !== '' && filter_var(trim($customerEmail), FILTER_VALIDATE_EMAIL)) {
            $params['customer_email'] = trim($customerEmail);
        }

        return $this->stripe->checkout->sessions->create($params);
    }

    public function retrieveCheckoutSession(string $sessionId): \Stripe\Checkout\Session
    {
        return $this->stripe->checkout->sessions->retrieve($sessionId);
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
