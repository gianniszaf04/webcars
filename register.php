<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.connect.php';
require_once __DIR__ . '/includes/userTracking.php';

if (is_logged_in()) {
    redirect('index.php');
}

$page_title = 'Εγγραφή';
$errors = [];
$input = ['username' => '', 'password' => '', 'first_name' => '', 'last_name' => '', 'email' => '', 'phone' => ''];
$passwordPattern = '/^[A-Za-z0-9!@_\-]{10,}$/';

// Καθαρισμός input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($input as $key => $value) {
        $input[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    // Το τηλέφωνο αποθηκεύεται χωρίς κενά / παύλες
    $phone = preg_replace('/[\s\-().]/', '', $input['phone']);

    if (!csrf_valid()) {
        $errors[] = 'Η φόρμα έληξε. Δοκιμάστε ξανά.';
    }
    if ($input['username'] === '') {
        $errors[] = 'Το username είναι υποχρεωτικό.';
    } elseif (mb_strlen($input['username']) < 3 || mb_strlen($input['username']) > 50 || preg_match('/\s/u', $input['username'])) {
        $errors[] = 'Το username πρέπει να έχει 3 έως 50 χαρακτήρες, χωρίς κενά.';
    }
    if ($input['first_name'] === '') {
        $errors[] = 'Το όνομα είναι υποχρεωτικό.';
    } elseif (mb_strlen($input['first_name']) > 50) {
        $errors[] = 'Το όνομα μπορεί να έχει έως 50 χαρακτήρες.';
    }
    if ($input['last_name'] === '') {
        $errors[] = 'Το επώνυμο είναι υποχρεωτικό.';
    } elseif (mb_strlen($input['last_name']) > 50) {
        $errors[] = 'Το επώνυμο μπορεί να έχει έως 50 χαρακτήρες.';
    }
    if ($input['email'] === '' || !filter_var($input['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($input['email']) > 100) {
        $errors[] = 'Το e-mail δεν είναι έγκυρο.';
    }
    if ($input['phone'] === '') {
        $errors[] = 'Το τηλέφωνο είναι υποχρεωτικό.';
    } elseif (!preg_match('/^\+?[0-9]{10,15}$/', $phone)) {
        $errors[] = 'Το τηλέφωνο πρέπει να έχει 10 έως 15 ψηφία (π.χ. 6912345678 ή +306912345678).';
    }
    // Κανόνας των κωδικών
    if (!preg_match($passwordPattern, $input['password'])) {
        $errors[] = 'Το password πρέπει να έχει τουλάχιστον 10 χαρακτήρες και να περιέχει μόνο λατινικούς χαρακτήρες, αριθμούς και τα σύμβολα !, -, _, @.';
    }

    // Έλεγχος για μοναδικότητα (η σύγκριση στη βάση δεν κάνει διάκριση πεζών/κεφαλαίων)
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT username, email FROM users WHERE username = :username OR email = :email');
        $stmt->execute([':username' => $input['username'], ':email' => $input['email']]);
        foreach ($stmt->fetchAll() as $existing) {
            if (mb_strtolower($existing['username']) === mb_strtolower($input['username'])) {
                $errors[] = 'Το username χρησιμοποιείται ήδη.';
            }
            if (mb_strtolower($existing['email']) === mb_strtolower($input['email'])) {
                $errors[] = 'Το email χρησιμοποιείται ήδη.';
            }
        }
        $errors = array_values(array_unique($errors));
    }

    if (!$errors) {
        $activationCode = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $passwordHash = password_hash($input['password'], PASSWORD_DEFAULT); // bcrypt hash με salt

        try {
            $sql = 'INSERT INTO users (username, password, first_name, last_name, email, phone, activation_code, is_active)
                    VALUES (:username, :password, :first_name, :last_name, :email, :phone, :code, 0)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':username'   => $input['username'],
                ':password'   => $passwordHash,
                ':first_name' => $input['first_name'],
                ':last_name'  => $input['last_name'],
                ':email'      => $input['email'],
                ':phone'      => $phone,
                ':code'       => $activationCode,
            ]);

            // Δεν υπάρχει mail server, οπότε ο κωδικός εμφανίζεται στη σελίδα ενεργοποίησης.
            // Τον κρατάμε στη session (όχι στο URL) ώστε να μη μένει στο ιστορικό του browser.
            $_SESSION['pending_activation'] = ['username' => $input['username'], 'code' => $activationCode];
            redirect('activate.php');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'Σφάλμα κατά την εγγραφή. Δοκιμάστε ξανά.';
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<h1>Δημιουργία Λογαριασμού</h1>

<?= render_messages($errors) ?>

<form id="registerForm" method="post" action="register.php" novalidate>
    <?= csrf_field() ?>

    <label for="username">Username</label>
    <input type="text" id="username" name="username" required minlength="3" maxlength="50" autocomplete="username"
        value="<?= h($input['username']) ?>">

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required minlength="10" pattern="[A-Za-z0-9!@_\-]{10,}"
        autocomplete="new-password">
    <div class="hint">Τουλάχιστον 10 χαρακτήρες. Επιτρέπονται: A-Z, a-z, 0-9, !, -, _, @.</div>

    <label for="first_name">Όνομα</label>
    <input type="text" id="first_name" name="first_name" required maxlength="50" autocomplete="given-name"
        value="<?= h($input['first_name']) ?>">

    <label for="last_name">Επώνυμο</label>
    <input type="text" id="last_name" name="last_name" required maxlength="50" autocomplete="family-name"
        value="<?= h($input['last_name']) ?>">

    <label for="email">Email</label>
    <input type="email" id="email" name="email" required maxlength="100" autocomplete="email"
        value="<?= h($input['email']) ?>">

    <label for="phone">Τηλέφωνο</label>
    <input type="tel" id="phone" name="phone" required maxlength="20" autocomplete="tel"
        value="<?= h($input['phone']) ?>">

    <button type="submit">Δημιουργία Λογαριασμού</button>
</form>

<p class="form-footer">Έχεις ήδη λογαριασμό; <a href="login.php">Σύνδεση</a></p>

<script>
    const form = document.getElementById('registerForm');
    const passwordInput = document.getElementById('password');

    form.addEventListener('submit', function (event) {
        passwordInput.setCustomValidity('');

        if (passwordInput.validity.patternMismatch || passwordInput.validity.tooShort) {
            passwordInput.setCustomValidity('Το password πρέπει να έχει 10+ χαρακτήρες: γράμματα A-Z, αριθμούς και τα !, -, _, @.');
        }

        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
        }
    });

    passwordInput.addEventListener('input', function () {
        passwordInput.setCustomValidity('');
    });
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
