<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/userTracking.php';
require_once __DIR__ . '/includes/theme_cookies.php';

set_theme_cookie(get_theme() === 'dark' ? 'default' : 'dark');

// Γυρνάει τον χρήστη στη σελίδα που ήταν, μαζί με τα φίλτρα / το id της αγγελίας
$referer = parse_url($_SERVER['HTTP_REFERER'] ?? '');
$back = basename($referer['path'] ?? '');
if (!empty($referer['query'])) {
    $back .= '?' . $referer['query'];
}
redirect(safe_local_target($back));
