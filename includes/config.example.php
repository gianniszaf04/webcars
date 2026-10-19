<?php
// Αντέγραψε αυτό το αρχείο σε config.php και συμπλήρωσε τις τιμές.
// Copy this file to config.php and fill in your own values.
// config.php is listed in .gitignore and must never be committed.
declare(strict_types=1);

return [
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'webcars',
        'user' => 'webcarsuser',
        'pass' => 'CHANGE_ME',
    ],
    // Get keys (reCAPTCHA v2 "I'm not a robot") at https://www.google.com/recaptcha/admin
    'recaptcha' => [
        'site_key'   => 'YOUR_RECAPTCHA_SITE_KEY',
        'secret_key' => 'YOUR_RECAPTCHA_SECRET_KEY',
    ],
];
