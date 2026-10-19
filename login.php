<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.connect.php';
require_once __DIR__ . '/includes/userTracking.php';
require_once __DIR__ . '/includes/login_throttle.php';

$next = safe_local_target($_GET['next'] ?? $_POST['next'] ?? null);

if (is_logged_in()) {
    redirect($next);
}

$page_title = 'Σύνδεση';
$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_valid()) {
        $error = 'Η φόρμα έληξε. Δοκιμάστε ξανά.';
    } elseif ($username === '' || $password === '') {
        $error = 'Συμπλήρωσε username και password.';
    } elseif (login_is_throttled($pdo, $username)) {
        $error = 'Πάρα πολλές αποτυχημένες προσπάθειες. Δοκιμάστε ξανά σε ' . LOGIN_WINDOW_MINUTES . ' λεπτά.';
    }

    if ($error === '') {
        $stmt = $pdo->prepare('SELECT id, username, password, is_active FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        // Ελέγχουμε πρώτα τον κωδικό και δίνουμε το ίδιο μήνυμα είτε λείπει ο χρήστης είτε ο κωδικός είναι λάθος,
        // ώστε να μη φαίνεται ποια usernames υπάρχουν.
        // (Αν δεν υπάρχει ο χρήστης, κάνουμε ένα "ψεύτικο" password_verify για να διαρκεί το ίδιο.)
        $passwordOk = $user
            ? password_verify($password, $user['password'])
            : password_verify($password, '$2y$10$7aOtCZB801or91l6tMd5B.aKUJP4w8GUay2UtbAIPhJ2QzN51P/Te') && false;

        if (!$passwordOk) {
            login_record_failure($pdo, $username);
            $error = 'Λάθος username ή password.';
        } elseif ((int) $user['is_active'] !== 1) {
            $error = 'Ο λογαριασμός δεν έχει ενεργοποιηθεί ακόμα.';
        } else {
            login_clear_failures($pdo, $username);

            // Αν αλλάξει ο αλγόριθμος του password_hash, ανανεώνουμε το hash
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $pdo->prepare('UPDATE users SET password = :p WHERE id = :id')
                    ->execute([':p' => password_hash($password, PASSWORD_DEFAULT), ':id' => $user['id']]);
            }

            session_regenerate_id(true); // Νέο session id στον χρήστη
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = (string) $user['username'];

            redirect($next);
        }
    }
}
require __DIR__ . '/includes/header.php';
?>

<h1>Σύνδεση</h1>

<?= render_messages($error !== '' ? [$error] : []) ?>

<form method="post" action="login.php" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= h($next) ?>">

    <label for="username">Username</label>
    <input type="text" id="username" name="username" required maxlength="50" autocomplete="username" value="<?= h($username) ?>">

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required autocomplete="current-password">

    <button type="submit">Σύνδεση</button>
</form>

<p class="form-footer">
    Δεν έχεις λογαριασμό; <a href="register.php">Εγγραφή</a> ·
    <a href="activate.php">Ενεργοποίηση λογαριασμού</a>
</p>

<?php require __DIR__ . '/includes/footer.php'; ?>
