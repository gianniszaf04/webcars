<?php
declare(strict_types=1);

// Συνάρτηση για να βρούμε το θέμα ('default' ή 'dark')
function get_theme(): string
{
    if (isset($_COOKIE['site_theme']) && $_COOKIE['site_theme'] === 'dark') {
        return 'dark';
    }
    return 'default';
}

// Αποθήκευση της επιλογής για 30 ημέρες
function set_theme_cookie(string $theme): void
{
    setcookie('site_theme', $theme, [
        'expires'  => time() + 86400 * 30,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
