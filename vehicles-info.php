<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.connect.php';
require_once __DIR__ . '/includes/userTracking.php';
require_once __DIR__ . '/includes/catalog.php';
$no_container = true;

// Βρίσκουμε το id της αγγελίας
$car_id = (int) ($_GET['id'] ?? 0);

$car = false;
if ($car_id > 0) {
    $sql = 'SELECT cars.*, users.username AS seller_name, users.first_name AS seller_first_name,
                   users.last_name AS seller_last_name, users.phone AS seller_phone, users.email AS seller_email
            FROM cars
            JOIN users ON cars.user_id = users.id
            WHERE cars.id = :id
            LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $car_id]);
    $car = $stmt->fetch(); // περιμένουμε μία γραμμή
}

if (!$car) {
    http_response_code(404);
    $page_title = 'Η αγγελία δεν βρέθηκε';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="main-container not-found">
        <h1>Η αγγελία δεν βρέθηκε</h1>
        <p>Μπορεί να έχει διαγραφεί από τον πωλητή.</p>
        <div class="header-backlink">
            <a href="search.php" class="back-link">&larr; Πίσω στην αναζήτηση</a>
        </div>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$stmtImg = $pdo->prepare('SELECT file_name, caption FROM images WHERE car_id = :id ORDER BY id');
$stmtImg->execute([':id' => $car_id]);
$images = $stmtImg->fetchAll(); // ένα αυτοκίνητο μπορεί να έχει 2+ εικόνες

$isOwner = current_user_id() === (int) $car['user_id'];
$title = $car['brand'] . ' ' . $car['model'];
$page_title = $title;
$yesNo = fn($flag): string => $flag ? 'Ναι' : 'Όχι';

require __DIR__ . '/includes/header.php';
?>
<div class="main-container">
    <h1><?= h($title) ?>
        <br>
        <?= h(format_price($car['price'])) ?>
    </h1>

    <?php if ($isOwner): ?>
        <div class="header-backlink owner-actions">
            <a href="vehicles.php" class="back-link">Επεξεργασία αγγελίας</a>
            <a href="vehicles-images.php" class="back-link">Φωτογραφίες</a>
        </div>
    <?php endif; ?>

    <div>
        <?php if (empty($images)): ?>
            <img src="img/no-image.jpg" alt="Χωρίς φωτογραφία" class="main-image">
        <?php else: ?>
            <?php foreach ($images as $img): ?>
                <figure class="image-result">
                    <img src="<?= h('uploads/' . basename($img['file_name'])) ?>"
                        alt="<?= h($img['caption'] ?: $title) ?>" class="detail-image" loading="lazy">

                    <?php if ((string) $img['caption'] !== ''): ?>
                        <figcaption class="image-caption"><?= h($img['caption']) ?></figcaption>
                    <?php endif; ?>
                </figure>
            <?php endforeach; ?>
        <?php endif; ?>

        <hr>

        <div class="details">
            <h2>Χαρακτηριστικά</h2>
            <ul class="details-list">
                <li><strong>Πωλητής: </strong><?= h($car['seller_name']) ?></li>
                <li><strong>Τύπος Αμαξιού: </strong><?= h(body_type_label($car['car_type'])) ?></li>
                <li><strong>Κυβικά: </strong><?= h(format_number($car['engine_cc'])) ?> cc</li>
                <li><strong>Καύσιμο: </strong><?= h(fuel_type_label($car['fuel_type'])) ?></li>
                <li><strong>Χιλιόμετρα: </strong><?= h(format_number($car['mileage'])) ?> km</li>
                <li><strong>Ημερομηνία Κυκλοφορίας: </strong><?= h(format_date($car['reg_date'])) ?></li>
            </ul>

            <h2>Επιπλέον Εξοπλισμός και Κατάσταση</h2>
            <ul class="details-list">
                <li><strong>Turbo: </strong><?= $yesNo($car['has_turbo']) ?></li>
                <li><strong>Υβριδικό: </strong><?= $yesNo($car['is_hybrid']) ?></li>
                <li><strong>Χρειάζεται Επισκευή: </strong><?= $yesNo($car['needs_repair']) ?></li>
            </ul>

            <h2>Επικοινωνία με τον πωλητή</h2>
            <?php if (is_logged_in()): ?>
                <ul class="details-list">
                    <li><strong>Όνομα: </strong><?= h($car['seller_first_name'] . ' ' . $car['seller_last_name']) ?></li>
                    <li><strong>Τηλέφωνο: </strong>
                        <a href="tel:<?= h($car['seller_phone']) ?>"><?= h($car['seller_phone']) ?></a>
                    </li>
                    <li><strong>Email: </strong>
                        <a href="mailto:<?= h($car['seller_email']) ?>?subject=<?= rawurlencode('Αγγελία: ' . $title) ?>"><?= h($car['seller_email']) ?></a>
                    </li>
                </ul>
            <?php else: ?>
                <p class="details-note">
                    <a href="login.php?next=<?= h(rawurlencode('vehicles-info.php?id=' . $car_id)) ?>">Συνδεθείτε</a>
                    για να δείτε το τηλέφωνο και το email του πωλητή.
                </p>
            <?php endif; ?>
        </div>

        <div class="header-backlink">
            <a href="search.php" class="back-link">&larr; Πίσω στην αναζήτηση</a>
        </div>
        <br>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
