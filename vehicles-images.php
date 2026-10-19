<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.connect.php';
require_once __DIR__ . '/includes/userTracking.php';
require_login();

const MAX_SIZE_KB = 250;          // μέγιστο μέγεθος αρχείου
const MAX_IMAGES_PER_CAR = 10;    // μέγιστος αριθμός φωτογραφιών ανά αγγελία
const MAX_DIMENSION = 6000;       // μέγιστο πλάτος/ύψος σε pixels

$user_id = current_user_id();
$uploadD = __DIR__ . '/uploads/';
$errors = [];
$successMessage = take_flash();
$page_title = 'Φωτογραφίες οχήματος';

// Τυχαίο όνομα αρχείου (UUID v4), ώστε να μην μπορεί κανείς να μαντέψει ή να αντικαταστήσει αρχεία
function generate_uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Αποθηκεύει ξανά τη φωτογραφία με τη GD. Έτσι σβήνονται τα μεταδεδομένα EXIF
// (π.χ. η τοποθεσία GPS όπου τραβήχτηκε η φωτογραφία με το κινητό).
// Πριν από αυτό γυρνάμε την εικόνα σωστά, γιατί ο προσανατολισμός ζει κι αυτός στα EXIF.
function save_jpeg_without_metadata(string $source, string $target): bool
{
    if (!function_exists('imagecreatefromjpeg')) {
        // Χωρίς την επέκταση GD απλώς μετακινούμε το αρχείο (τα EXIF μένουν)
        return move_uploaded_file($source, $target);
    }

    $image = @imagecreatefromjpeg($source);
    if ($image === false) {
        return false;
    }

    if (function_exists('exif_read_data')) {
        $exif = @exif_read_data($source);
        $rotation = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($rotation !== 0) {
            $rotated = imagerotate($image, $rotation, 0);
            if ($rotated !== false) {
                imagedestroy($image);
                $image = $rotated;
            }
        }
    }

    $saved = imagejpeg($image, $target, 85);
    imagedestroy($image);
    return $saved;
}

if (!is_dir($uploadD)) {
    mkdir($uploadD, 0755, true);
}

$stmt = $pdo->prepare('SELECT id FROM cars WHERE user_id = :uid LIMIT 1');
$stmt->execute([':uid' => $user_id]);
$hasCar = $stmt->fetch();
if (!$hasCar) {
    redirect('vehicles.php');
}
$car_id = (int) $hasCar['id'];
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_valid()) {
    $errors[] = 'Η φόρμα έληξε. Δοκιμάστε ξανά.';
    $action = '';
}

// Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload') {
    $file = $_FILES['car_image'] ?? null;
    $caption = trim((string) ($_POST['caption'] ?? ''));

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM images WHERE car_id = :cid');
    $countStmt->execute([':cid' => $car_id]);

    if ((int) $countStmt->fetchColumn() >= MAX_IMAGES_PER_CAR) {
        $errors[] = 'Έχετε φτάσει το όριο των ' . MAX_IMAGES_PER_CAR . ' φωτογραφιών. Διαγράψτε κάποια για να ανεβάσετε νέα.';
    } elseif (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Επιλέξτε ένα αρχείο εικόνας.';
    } elseif (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $file['size'] > MAX_SIZE_KB * 1024) {
        $errors[] = 'Το αρχείο είναι υπερβολικά μεγάλο. Μέγιστο όριο: ' . MAX_SIZE_KB . ' KB.';
    } elseif ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'Η μεταφόρτωση απέτυχε. Δοκιμάστε ξανά.';
    } elseif (mb_strlen($caption) > 255) {
        $errors[] = 'Η λεζάντα μπορεί να έχει έως 255 χαρακτήρες.';
    } else {
        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        // Έλεγχος του πραγματικού περιεχομένου, όχι μόνο της κατάληξης
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $info = @getimagesize($file['tmp_name']);

        if (!in_array($extension, ['jpg', 'jpeg'], true) || $mime !== 'image/jpeg' || $info === false) {
            $errors[] = 'Μη αποδεκτός τύπος αρχείου. Επιτρέπονται μόνο εικόνες JPG.';
        } elseif ($info[0] > MAX_DIMENSION || $info[1] > MAX_DIMENSION) {
            $errors[] = 'Η εικόνα είναι πολύ μεγάλη σε διαστάσεις (μέγιστο ' . MAX_DIMENSION . ' pixels).';
        }
    }

    if (!$errors) {
        $new_filename = generate_uuid() . '.jpg';
        $target_file = $uploadD . $new_filename;

        if (save_jpeg_without_metadata($file['tmp_name'], $target_file)) {
            try {
                $stmt = $pdo->prepare('INSERT INTO images (car_id, file_name, caption) VALUES (:cid, :fname, :cap)');
                $stmt->execute([':cid' => $car_id, ':fname' => $new_filename, ':cap' => $caption]);
                flash('Η φωτογραφία αναρτήθηκε επιτυχώς!');
                redirect('vehicles-images.php');
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $errors[] = 'Σφάλμα βάσης δεδομένων. Δοκιμάστε ξανά.';
                if (is_file($target_file)) {
                    unlink($target_file);
                }
            }
        } else {
            $errors[] = 'Η αποθήκευση της εικόνας απέτυχε. Παρακαλώ ξαναπροσπαθήστε.';
        }
    }
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    $img_id = (int) ($_POST['image_id'] ?? 0);
    // Μόνο εικόνες της δικής μας αγγελίας
    $stmt = $pdo->prepare('SELECT file_name FROM images WHERE id = :id AND car_id = :cid LIMIT 1');
    $stmt->execute([':id' => $img_id, ':cid' => $car_id]);
    $arrivedImage = $stmt->fetch();

    if (!$arrivedImage) {
        $errors[] = 'Δεν βρέθηκε η εικόνα.';
    } else {
        $filePath = $uploadD . basename($arrivedImage['file_name']);

        $pdo->prepare('DELETE FROM images WHERE id = :id AND car_id = :cid')
            ->execute([':id' => $img_id, ':cid' => $car_id]);

        if (is_file($filePath) && !unlink($filePath)) {
            error_log('Could not delete ' . $filePath);
        }
        flash('Η φωτογραφία διαγράφηκε.');
        redirect('vehicles-images.php');
    }
}

$stmt = $pdo->prepare('SELECT id, file_name, caption FROM images WHERE car_id = :cid ORDER BY id');
$stmt->execute([':cid' => $car_id]);
$images = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="header-backlink">
    <a href="vehicles.php" class="back-link">&larr; Πίσω στα στοιχεία του οχήματος</a>
</div>
<br>
<h1>Φωτογραφίες Οχήματος</h1>

<?= render_messages($errors, $successMessage) ?>

<div class="upload-box">
    <h3>Προσθήκη νέας εικόνας</h3>
    <hr>
    <form method="post" action="vehicles-images.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        <?= csrf_field() ?>

        <label for="car_image">Επιλογή αρχείου (JPG, έως <?= MAX_SIZE_KB ?> KB):</label>
        <input type="file" id="car_image" name="car_image" accept=".jpg,.jpeg,image/jpeg" required>

        <label for="caption">Λεζάντα:</label>
        <input type="text" id="caption" name="caption" placeholder="π.χ. Μπροστινή μεριά οχήματος" maxlength="255">
        <button type="submit">Ανάρτηση</button>
    </form>
</div>

<hr>
<h3>Οι φωτογραφίες μου (<?= count($images) ?>/<?= MAX_IMAGES_PER_CAR ?>)</h3>
<?php if (count($images) === 0): ?>
    <p>Δεν υπάρχουν φωτογραφίες.</p>
<?php else: ?>
    <div class="gallery-container">
        <?php foreach ($images as $img): ?>
            <div class="gallery-card">
                <img class="gallery-img"
                    src="uploads/<?= h(basename($img['file_name'])) ?>"
                    alt="<?= h($img['caption'] ?: 'Φωτογραφία οχήματος') ?>">

                <?php if ((string) $img['caption'] !== ''): ?>
                    <p class="gallery-caption"><?= h($img['caption']) ?></p>
                <?php endif; ?>

                <form method="post" action="vehicles-images.php" onsubmit="return confirm('Είστε σίγουροι;');">
                    <input type="hidden" name="action" value="delete">
                    <?= csrf_field() ?>
                    <input type="hidden" name="image_id" value="<?= (int) $img['id'] ?>">
                    <button type="submit" class="delete">Διαγραφή</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
    document.getElementById('car_image').addEventListener('change', function () {
        const f = this.files && this.files[0];
        if (!f) return;

        if (f.size > <?= MAX_SIZE_KB ?> * 1024) {
            alert('Το αρχείο πρέπει να είναι κάτω από <?= MAX_SIZE_KB ?> KB.');
            this.value = '';
            return;
        }

        if (f.type !== 'image/jpeg') {
            alert('Επιτρέπονται μόνο εικόνες JPG.');
            this.value = '';
        }
    });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
