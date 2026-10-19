<?php
declare(strict_types=1);

// Φορτώνει τις ρυθμίσεις από το includes/config.php (δεν ανεβαίνει στο GitHub).
// Αν δεν υπάρχει config.php αλλά υπάρχουν μεταβλητές περιβάλλοντος (π.χ. στο Docker),
// χρησιμοποιούνται αυτές.
function app_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $file = __DIR__ . '/config.php';
    if (is_file($file)) {
        $config = require $file;
    } elseif (getenv('DB_HOST') !== false) {
        $config = [
            'db' => [
                'host' => (string) getenv('DB_HOST'),
                'name' => (string) (getenv('DB_NAME') ?: 'webcars'),
                'user' => (string) getenv('DB_USER'),
                'pass' => (string) getenv('DB_PASS'),
            ],
            'recaptcha' => [
                'site_key'   => (string) getenv('RECAPTCHA_SITE_KEY'),
                'secret_key' => (string) getenv('RECAPTCHA_SECRET_KEY'),
            ],
        ];
    } else {
        http_response_code(500);
        die('Λείπει το includes/config.php — αντέγραψε το includes/config.example.php σε config.php.');
    }

    return $config;
}
