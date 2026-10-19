<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/userTracking.php';
require_once __DIR__ . '/includes/db.connect.php';
require_once __DIR__ . '/includes/recaptcha.php';

if (is_logged_in()) {
    redirect('index.php');
}

$page_title = 'Ενεργοποίηση λογαριασμού';
// Ο κωδικός που δημιουργήθηκε στην εγγραφή (κρατιέται στη session μέχρι την ενεργοποίηση)
$pending = $_SESSION['pending_activation'] ?? null;
$username = (string) ($pending['username'] ?? '');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; // χωρίς trim: ο χρήστης μπορεί να έχει βάλει κενά στον κωδικό του
    $code = trim($_POST['activation_code'] ?? '');
    $cap = (string) ($_POST['g-recaptcha-response'] ?? '');

    if (!csrf_valid()) {
        $error = 'Η φόρμα έληξε. Δοκιμάστε ξανά.';
    } elseif ($username === '' || $password === '' || $code === '') {
        $error = 'Συμπλήρωσε όλα τα πεδία.';
    } elseif (!preg_match('/^[0-9]{5}$/', $code)) {
        $error = 'Ο κωδικός ενεργοποίησης πρέπει να είναι 5 ψηφία.';
    } elseif ($cap === '') {
        $error = 'Ολοκλήρωσε το CAPTCHA.';
    } elseif (!recaptcha_verify($cap, $_SERVER['REMOTE_ADDR'] ?? null)) {
        $error = 'Αποτυχία CAPTCHA. Δοκιμάστε ξανά.';
    }

    if ($error === '') {
        $stmt = $pdo->prepare('SELECT id, username, password, activation_code, is_active FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        // Πρώτα ο κωδικός πρόσβασης, ώστε να μη φαίνεται αν ένα username υπάρχει
        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Λάθος username ή password.';
        } elseif ((int) $user['is_active'] === 1) {
            $error = 'Ο λογαριασμός είναι ήδη ενεργός. Μπορείς να συνδεθείς.';
        } elseif (!hash_equals((string) $user['activation_code'], $code)) {
            $error = 'Λάθος κωδικός ενεργοποίησης.';
        } else {
            // Ενεργοποίηση και διαγραφή του κωδικού (δεν χρειάζεται πια)
            $upd = $pdo->prepare("UPDATE users SET is_active = 1, activation_code = '' WHERE id = :id");
            $upd->execute([':id' => $user['id']]);
            unset($_SESSION['pending_activation']);

            // Αυτόματο login
            session_regenerate_id(true); // Νέο session id στον χρήστη
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = (string) $user['username'];

            redirect('index.php');
        }
    }
}
require_once __DIR__ . '/includes/header.php';
?>

<h1>Ενεργοποίηση λογαριασμού</h1>

<?php if ($pending): ?>
    <div id="ActivationCode" class="activ_code success">
        <strong>Η εγγραφή ολοκληρώθηκε! Ο κωδικός ενεργοποίησης είναι: <?= h($pending['code']) ?></strong>
    </div>
<?php endif; ?>

<?= render_messages($error !== '' ? [$error] : []) ?>

<form id="activateForm" method="post" action="activate.php" novalidate>
    <?= csrf_field() ?>

    <label for="username">Username</label>
    <input type="text" id="username" name="username" required maxlength="50" autocomplete="username" value="<?= h($username) ?>">

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required autocomplete="current-password">

    <label for="activation_code">Κωδικός ενεργοποίησης (5 ψηφία)</label>
    <input type="text" id="activation_code" name="activation_code" required pattern="[0-9]{5}" maxlength="5"
        inputmode="numeric" autocomplete="one-time-code">

    <div class="captcha-container">
        <div class="g-recaptcha" data-sitekey="<?= h(RecaptchaSiteKey) ?>"></div>
    </div>
    <button type="submit">Ενεργοποίηση</button>
</form>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
    const form = document.getElementById('activateForm');
    const codeInput = document.getElementById('activation_code');

    form.addEventListener('submit', function (event) {
        codeInput.setCustomValidity('');
        if (codeInput.validity.patternMismatch) {
            codeInput.setCustomValidity('Ο κωδικός πρέπει να είναι 5 ψηφία.');
        }

        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
            return;
        }

        if (typeof grecaptcha !== 'undefined' && !grecaptcha.getResponse()) {
            event.preventDefault();
            alert('Ολοκληρώστε το CAPTCHA.');
        }
    });

    codeInput.addEventListener('input', function () {
        codeInput.setCustomValidity('');
    });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
