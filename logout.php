<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/userTracking.php';

// Αποσύνδεση μόνο με POST + CSRF token, ώστε ένα link από άλλο site να μην μπορεί να αποσυνδέσει τον χρήστη
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    redirect('index.php');
}

$_SESSION = [];
// Ολοκληρωμένο logout: σβήνουμε και το cookie της session από τον browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?: 'Lax',
    ]);
}

session_destroy();
redirect('index.php');
