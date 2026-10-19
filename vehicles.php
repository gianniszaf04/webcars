<?php
// Καταχώριση ή επεξεργασία της αγγελίας του χρήστη (κάθε χρήστης έχει μία αγγελία).
// Το ίδιο αρχείο κάνει και διαγραφή της αγγελίας.
declare(strict_types=1);

require_once __DIR__ . '/includes/db.connect.php';
require_once __DIR__ . '/includes/userTracking.php';
require_once __DIR__ . '/includes/catalog.php';

require_login();
$user_id = current_user_id();
$errors = [];
$successMessage = take_flash();
$today = date('Y-m-d');

$input = [
    'brand' => '',
    'model' => '',
    'car_type' => '',
    'engine_cc' => '',
    'fuel_type' => '',
    'mileage' => '',
    'reg_date' => '',
    'has_turbo' => '0',
    'is_hybrid' => '0',
    'needs_repair' => '0',
    'price' => '',
];

$stmt = $pdo->prepare('
    SELECT id, brand, model, car_type, engine_cc, fuel_type, mileage, reg_date,
           has_turbo, is_hybrid, needs_repair, price
    FROM cars WHERE user_id = :user_id LIMIT 1');
$stmt->execute([':user_id' => $user_id]);
$existingVehicle = $stmt->fetch();
$editVehicle = (bool) $existingVehicle;

if ($existingVehicle) {
    foreach (array_keys($input) as $key) {
        $input[$key] = (string) $existingVehicle[$key];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_valid()) {
    $errors[] = 'Η φόρμα έληξε. Δοκιμάστε ξανά.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    // Διαγραφή αγγελίας: πρώτα τα αρχεία των φωτογραφιών, μετά η εγγραφή
    // (οι εγγραφές των εικόνων σβήνονται αυτόματα λόγω ON DELETE CASCADE)
    if ($existingVehicle) {
        $stmt = $pdo->prepare('SELECT file_name FROM images WHERE car_id = :cid');
        $stmt->execute([':cid' => $existingVehicle['id']]);
        $files = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $pdo->prepare('DELETE FROM cars WHERE id = :id AND user_id = :uid')
            ->execute([':id' => $existingVehicle['id'], ':uid' => $user_id]);

        foreach ($files as $file) {
            $path = __DIR__ . '/uploads/' . basename($file);
            if (is_file($path)) {
                unlink($path);
            }
        }
        flash('Η αγγελία διαγράφηκε.');
    }
    redirect('vehicles.php');
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['brand', 'model', 'car_type', 'engine_cc', 'fuel_type', 'mileage', 'reg_date', 'price'] as $key) {
        $input[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    $input['has_turbo'] = isset($_POST['has_turbo']) ? '1' : '0';
    $input['is_hybrid'] = isset($_POST['is_hybrid']) ? '1' : '0';
    $input['needs_repair'] = isset($_POST['needs_repair']) ? '1' : '0';

    // Ξεκινάει ο έλεγχος :) (server side)
    if ($input['brand'] === '') {
        $errors[] = 'Η μάρκα είναι υποχρεωτική.';
    } elseif (mb_strlen($input['brand']) > BRAND_MAX_LEN) {
        $errors[] = 'Η μάρκα μπορεί να έχει έως ' . BRAND_MAX_LEN . ' χαρακτήρες.';
    }
    if ($input['model'] === '') {
        $errors[] = 'Το μοντέλο είναι υποχρεωτικό.';
    } elseif (mb_strlen($input['model']) > MODEL_MAX_LEN) {
        $errors[] = 'Το μοντέλο μπορεί να έχει έως ' . MODEL_MAX_LEN . ' χαρακτήρες.';
    }

    if (!array_key_exists($input['car_type'], BODY_TYPES)) {
        $errors[] = 'Επιλέξτε έγκυρο τύπο αμαξιού.';
    }
    if (!array_key_exists($input['fuel_type'], FUEL_TYPES)) {
        $errors[] = 'Επιλέξτε έγκυρο τύπο καυσίμου.';
    }

    if (!ctype_digit($input['engine_cc']) || (int) $input['engine_cc'] < CC_MIN || (int) $input['engine_cc'] > CC_MAX) {
        $errors[] = 'Τα κυβικά πρέπει να είναι ακέραιος αριθμός από ' . CC_MIN . ' έως ' . CC_MAX . '.';
    }

    if (!ctype_digit($input['mileage']) || (int) $input['mileage'] > KM_MAX) {
        $errors[] = 'Τα χιλιόμετρα πρέπει να είναι ακέραιος αριθμός από 0 έως 2.000.000.';
    }

    // Τιμή: έως 2 δεκαδικά, με τελεία ή κόμμα (π.χ. 15000 ή 15000,50)
    $price = str_replace(',', '.', $input['price']);
    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $price) || (float) $price <= 0 || (float) $price > PRICE_MAX) {
        $errors[] = 'Η τιμή πρέπει να είναι θετικός αριθμός (π.χ. 15000 ή 15000,50).';
    }

    $date = DateTime::createFromFormat('Y-m-d', $input['reg_date']);
    if (!$date || $date->format('Y-m-d') !== $input['reg_date']) {
        $errors[] = 'Η ημερομηνία κυκλοφορίας δεν είναι έγκυρη.';
    } elseif ($input['reg_date'] < '1900-01-01' || $input['reg_date'] > $today) {
        $errors[] = 'Η ημερομηνία κυκλοφορίας δεν μπορεί να είναι στο μέλλον.';
    }

    // PHP ----> Βάση Δεδομένων
    if (!$errors) {
        if ($editVehicle) {
            $sql = 'UPDATE cars SET
                        brand = :brand,
                        model = :model,
                        car_type = :car_type,
                        engine_cc = :engine_cc,
                        fuel_type = :fuel_type,
                        mileage = :mileage,
                        reg_date = :reg_date,
                        has_turbo = :has_turbo,
                        is_hybrid = :is_hybrid,
                        needs_repair = :needs_repair,
                        price = :price
                    WHERE user_id = :user_id';
        } else {
            $sql = 'INSERT INTO cars (user_id, brand, model, car_type, engine_cc, fuel_type, mileage, reg_date,
                                      has_turbo, is_hybrid, needs_repair, price)
                    VALUES (:user_id, :brand, :model, :car_type, :engine_cc, :fuel_type, :mileage, :reg_date,
                            :has_turbo, :is_hybrid, :needs_repair, :price)';
        }
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $user_id,
                ':brand' => $input['brand'],
                ':model' => $input['model'],
                ':car_type' => $input['car_type'],
                ':engine_cc' => (int) $input['engine_cc'],
                ':fuel_type' => $input['fuel_type'],
                ':mileage' => (int) $input['mileage'],
                ':reg_date' => $input['reg_date'],
                ':has_turbo' => (int) $input['has_turbo'],
                ':is_hybrid' => (int) $input['is_hybrid'],
                ':needs_repair' => (int) $input['needs_repair'],
                ':price' => $price,
            ]);
            // Post/Redirect/Get: με ανανέωση της σελίδας δεν ξαναστέλνεται η φόρμα
            flash($editVehicle ? 'Τα στοιχεία ενημερώθηκαν.' : 'Η καταχώριση ολοκληρώθηκε. Μπορείτε τώρα να προσθέσετε φωτογραφίες.');
            redirect('vehicles.php');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = 'Σφάλμα κατά την αποθήκευση. Δοκιμάστε ξανά.';
        }
    }
}

$page_title = $editVehicle ? 'Επεξεργασία οχήματος' : 'Καταχώριση οχήματος';
require __DIR__ . '/includes/header.php';
?>

<h1><?= $editVehicle ? 'Επεξεργασία Οχήματος' : 'Καταχώριση Οχήματος' ?></h1>

<?= render_messages($errors, $successMessage) ?>

<?php if ($existingVehicle): ?>
    <div class="header-backlink">
        <a href="vehicles-info.php?id=<?= (int) $existingVehicle['id'] ?>" class="back-link">Δείτε την αγγελία σας &rarr;</a>
    </div>
<?php endif; ?>

<form id="VehicleForm" method="post" action="vehicles.php" novalidate>
    <?= csrf_field() ?>
    <p>
        <label for="brand">Μάρκα</label>
        <input type="text" id="brand" name="brand" required maxlength="<?= BRAND_MAX_LEN ?>" value="<?= h($input['brand']) ?>">
    </p>
    <p>
        <label for="model">Μοντέλο</label>
        <input type="text" id="model" name="model" required maxlength="<?= MODEL_MAX_LEN ?>" value="<?= h($input['model']) ?>">
    </p>
    <p>
        <label for="car_type">Τύπος αμαξιού</label>
        <select id="car_type" name="car_type" required>
            <option value="">Επιλέξτε τύπο</option>
            <?php foreach (BODY_TYPES as $value => $label): ?>
                <option value="<?= h($value) ?>" <?= $input['car_type'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label for="engine_cc">Κυβικά</label>
        <input type="number" id="engine_cc" name="engine_cc" min="<?= CC_MIN ?>" max="<?= CC_MAX ?>" step="1" required
            value="<?= h($input['engine_cc']) ?>">
    </p>
    <p>
        <label for="fuel_type">Τύπος καυσίμου</label>
        <select id="fuel_type" name="fuel_type" required>
            <option value="">Επιλέξτε καύσιμο</option>
            <?php foreach (FUEL_TYPES as $value => $label): ?>
                <option value="<?= h($value) ?>" <?= $input['fuel_type'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label for="mileage">Χιλιόμετρα</label>
        <input type="number" id="mileage" name="mileage" min="0" max="<?= KM_MAX ?>" step="1" required
            value="<?= h($input['mileage']) ?>">
    </p>
    <p>
        <label for="reg_date">Ημερομηνία κυκλοφορίας</label>
        <input type="date" id="reg_date" name="reg_date" min="1900-01-01" max="<?= h($today) ?>" required
            value="<?= h($input['reg_date']) ?>">
    </p>
    <p>
        <label for="price">Τιμή (€)</label>
        <input type="text" id="price" name="price" required inputmode="decimal" placeholder="π.χ. 15000"
            value="<?= h($input['price']) ?>">
    </p>
    <fieldset class="checkbox-group">
        <legend>Επιπλέον χαρακτηριστικά</legend>
        <label>
            <input type="checkbox" name="has_turbo" <?= $input['has_turbo'] === '1' ? 'checked' : '' ?>>
            Turbo
        </label>
        <label>
            <input type="checkbox" name="is_hybrid" <?= $input['is_hybrid'] === '1' ? 'checked' : '' ?>>
            Υβριδικό
        </label>
        <label>
            <input type="checkbox" name="needs_repair" <?= $input['needs_repair'] === '1' ? 'checked' : '' ?>>
            Χρειάζεται επισκευή
        </label>
    </fieldset>
    <p>
        <button type="submit"><?= $editVehicle ? 'Ενημέρωση' : 'Καταχώριση' ?></button>
    </p>
</form>

<?php if ($editVehicle): ?>
    <div class="header-backlink">
        <a href="vehicles-images.php" class="back-link">Καταχώριση εικόνων &rarr;</a>
    </div>

    <form method="post" action="vehicles.php" class="danger-zone"
        onsubmit="return confirm('Σίγουρα θέλετε να διαγράψετε την αγγελία και όλες τις φωτογραφίες της;');">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <button type="submit" class="danger">Διαγραφή αγγελίας</button>
    </form>
<?php endif; ?>

<script>
    document.getElementById('VehicleForm').addEventListener('submit', function (e) {
        // Παίρνουμε τα στοιχεία του χρήστη από τη φόρμα
        const val = (id) => document.getElementById(id).value.trim(); // αν το έχει αφήσει κενό --> κενό string
        const brand = val('brand');
        const model = val('model');
        const car_type = val('car_type');
        const fuel_type = val('fuel_type');
        const reg_date = val('reg_date');
        // string -> number
        const cc = Number(val('engine_cc'));
        const km = Number(val('mileage'));
        const priceText = val('price').replace(',', '.'); // Περίπτωση που ο χρήστης γράψει 1000,1 και όχι 1000.1
        const price = Number(priceText);

        let message = '';
        if (brand === '' || model === '' || car_type === '' || fuel_type === '' || reg_date === '') {
            message = 'Παρακαλώ συμπληρώστε όλα τα υποχρεωτικά πεδία (Μάρκα, Μοντέλο, Τύπος αμαξιού, Καύσιμο, Ημερομηνία).';
        } else if (!Number.isInteger(cc) || cc < <?= CC_MIN ?> || cc > <?= CC_MAX ?>) {
            message = 'Τα κυβικά πρέπει να είναι μεταξύ <?= CC_MIN ?> και <?= CC_MAX ?>.';
        } else if (!Number.isInteger(km) || km < 0 || km > <?= KM_MAX ?>) {
            message = 'Τα χιλιόμετρα πρέπει να είναι από 0 έως 2.000.000.';
        } else if (!/^\d{1,8}(\.\d{1,2})?$/.test(priceText) || price <= 0) {
            message = 'Η τιμή πρέπει να είναι θετικός αριθμός (π.χ. 15000 ή 15000,50).';
        } else if (reg_date > '<?= $today ?>') {
            message = 'Η ημερομηνία κυκλοφορίας δεν μπορεί να είναι στο μέλλον.';
        }

        if (message !== '') {
            e.preventDefault();
            alert(message);
        }
    });
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
