<?php
declare(strict_types=1);

require_once __DIR__ . '/config_loader.php';

// Αν κάτι πάει στραβά (π.χ. σφάλμα βάσης), ο χρήστης βλέπει ένα απλό μήνυμα
// και οι λεπτομέρειες γράφονται μόνο στο error log — όχι στη σελίδα.
set_exception_handler(function (Throwable $e): void {
    error_log((string) $e);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo 'Κάτι πήγε στραβά. Δοκιμάστε ξανά σε λίγο.';
});

$db = app_config()['db'];

$dsn = "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Σφάλμα σύνδεσης με τη βάση δεδομένων.');
}
