<?php
declare(strict_types=1);

require_once __DIR__ . '/config_loader.php';

define('RecaptchaSiteKey', (string) app_config()['recaptcha']['site_key']);
define('RecaptchaSecretKey', (string) app_config()['recaptcha']['secret_key']);

// Ελέγχει την απάντηση του reCAPTCHA στους servers της Google.
// Το secret στέλνεται με POST (όχι μέσα στο URL) και με όριο χρόνου 5 δευτερολέπτων.
function recaptcha_verify(string $token, ?string $remoteIp = null): bool
{
    if ($token === '') {
        return false;
    }

    $fields = ['secret' => RecaptchaSecretKey, 'response' => $token];
    if ($remoteIp) {
        $fields['remoteip'] = $remoteIp;
    }
    $url = 'https://www.google.com/recaptcha/api/siteverify';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
    } else {
        $context = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($fields),
            'timeout' => 5,
        ]]);
        $response = @file_get_contents($url, false, $context);
    }

    if (!is_string($response) || $response === '') {
        error_log('reCAPTCHA: no response from Google');
        return false;
    }

    $data = json_decode($response, true);
    return !empty($data['success']);
}
