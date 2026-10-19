<?php
declare(strict_types=1);

// Session, έλεγχος σύνδεσης, escaping, CSRF και μηνύματα.

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

    ini_set('session.use_strict_mode', '1');   // δεν δεχόμαστε session id που δεν φτιάξαμε εμείς
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,  // μόνο μέσω HTTPS όταν υπάρχει
        'httponly' => true,      // η JavaScript δεν μπορεί να διαβάσει το cookie
        'samesite' => 'Lax',     // βασική προστασία από CSRF
    ]);
    session_start();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

// Επιστρέφει μια ασφαλή, τοπική διεύθυνση (π.χ. "vehicles-info.php?id=7") ή το $fallback.
// Έτσι αποφεύγουμε redirects προς εξωτερικά sites (open redirect).
function safe_local_target(?string $target, string $fallback = 'index.php'): string
{
    $target = (string) $target;
    if (preg_match('/^[a-z\-]+\.php(\?[A-Za-z0-9_\-=&%.+]*)?$/', $target)) {
        return $target;
    }
    return $fallback;
}

function require_login(): void
{
    if (!is_logged_in()) {
        $here = basename($_SERVER['SCRIPT_NAME'] ?? '');
        redirect('login.php?next=' . rawurlencode(safe_local_target($here)));
    }
}

// Συνάρτηση για τη σελιδοποίηση στο search.php: κρατάει τα φίλτρα και αλλάζει μόνο τη σελίδα
function page_url(int $page): string
{
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

// CSRF προστασία για τις φόρμες που αλλάζουν δεδομένα
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf_token']) && hash_equals(csrf_token(), (string) $_POST['csrf_token']);
}

// Μήνυμα που επιβιώνει ένα redirect (Post/Redirect/Get)
function flash(string $message): void
{
    $_SESSION['flash'] = $message;
}

function take_flash(): string
{
    $message = (string) ($_SESSION['flash'] ?? '');
    unset($_SESSION['flash']);
    return $message;
}

// Εμφανίζει όλα τα σφάλματα και το μήνυμα επιτυχίας με τις κλάσεις .error / .success
function render_messages(array $errors, string $success = ''): string
{
    $html = '';
    if (count($errors) === 1) {
        $html .= '<div class="error">' . h($errors[0]) . '</div>';
    } elseif ($errors) {
        $html .= '<div class="error"><ul class="message-list">';
        foreach ($errors as $error) {
            $html .= '<li>' . h($error) . '</li>';
        }
        $html .= '</ul></div>';
    }
    if ($success !== '') {
        $html .= '<div class="success">' . h($success) . '</div>';
    }
    return $html;
}
