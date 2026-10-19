<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.connect.php';
require_once __DIR__ . '/includes/userTracking.php';
require_once __DIR__ . '/includes/catalog.php';
$no_container = true;
$page_title = 'Αναζήτηση';

// Φίλτρα από το URL. Τιμές εκτός λίστας / μη αριθμητικές αγνοούνται.
$brand = trim((string) ($_GET['brand'] ?? ''));
$car_type = (string) ($_GET['car_type'] ?? '');
$fuel_type = (string) ($_GET['fuel_type'] ?? '');
$min_cc = trim((string) ($_GET['min_cc'] ?? ''));
$max_price = trim((string) ($_GET['max_price'] ?? ''));
$max_km = trim((string) ($_GET['max_km'] ?? ''));
$min_year = trim((string) ($_GET['min_year'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'newest');

if (!array_key_exists($car_type, BODY_TYPES)) $car_type = '';
if (!array_key_exists($fuel_type, FUEL_TYPES)) $fuel_type = '';
if (!array_key_exists($sort, SORT_OPTIONS)) $sort = 'newest';
if (!ctype_digit($min_cc)) $min_cc = '';
if (!ctype_digit($max_km)) $max_km = '';
if (!ctype_digit($min_year)) $min_year = '';
if (!is_numeric($max_price) || (float) $max_price < 0) $max_price = '';

// Δυναμική κατασκευή του WHERE: ετοιμάζουμε τα φίλτρα και τα ζητάμε μετά από την SQL
$filters = [];
$params = [];
if ($brand !== '') {
    $filters[] = 'c.brand LIKE :brand';
    $params[':brand'] = '%' . addcslashes($brand, '%_\\') . '%'; // να περιέχει το όνομα
}
if ($car_type !== '') {
    $filters[] = 'c.car_type = :car_type';
    $params[':car_type'] = $car_type;
}
if ($fuel_type !== '') {
    $filters[] = 'c.fuel_type = :fuel_type';
    $params[':fuel_type'] = $fuel_type;
}
if ($min_cc !== '') {
    $filters[] = 'c.engine_cc >= :min_cc';
    $params[':min_cc'] = (int) $min_cc;
}
if ($max_price !== '') {
    $filters[] = 'c.price <= :max_price';
    $params[':max_price'] = (float) $max_price;
}
if ($max_km !== '') {
    $filters[] = 'c.mileage <= :max_km';
    $params[':max_km'] = (int) $max_km;
}
if ($min_year !== '') {
    $filters[] = 'c.reg_date >= :min_date';
    $params[':min_date'] = sprintf('%04d-01-01', (int) $min_year);
}
// Προσέχουμε την περίπτωση της αναζήτησης χωρίς φίλτρα
$sql_filters = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM cars c $sql_filters");
$stmt->execute($params);
$totalCars = (int) $stmt->fetchColumn();

// Σελιδοποίηση
$per_page = 4;
$totalPages = max(1, (int) ceil($totalCars / $per_page));
$page = min(max(1, (int) ($_GET['page'] ?? 1)), $totalPages);
$start = ($page - 1) * $per_page;

// Μία εικόνα ανά αγγελία (η πρώτη που ανέβηκε) μαζί με τη δική της λεζάντα
$orderBy = SORT_OPTIONS[$sort][1];
$sql = "SELECT c.id, c.brand, c.model, c.car_type, c.fuel_type, c.engine_cc, c.mileage, c.reg_date, c.price,
               u.username AS seller_name,
               i.file_name AS thumb, i.caption AS thumb_caption
        FROM cars c
        JOIN users u ON u.id = c.user_id
        LEFT JOIN images i ON i.id = (SELECT MIN(i2.id) FROM images i2 WHERE i2.car_id = c.id)
        $sql_filters
        ORDER BY $orderBy
        LIMIT $per_page OFFSET $start";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cars = $stmt->fetchAll();

// Ποιοι αριθμοί σελίδων εμφανίζονται: η πρώτη, η τελευταία και 2 γύρω από την τρέχουσα
$pageLinks = [];
for ($i = 1; $i <= $totalPages; $i++) {
    if ($i === 1 || $i === $totalPages || abs($i - $page) <= 2) {
        $pageLinks[] = $i;
    } elseif (end($pageLinks) !== '…') {
        $pageLinks[] = '…';
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="search-layout">

    <?php require __DIR__ . '/includes/search-sidebar.php'; ?>

    <section class="search-results">

        <h2>Αποτελέσματα: <?= $totalCars ?></h2>

        <?php if (!$cars): ?>
            <p class="no-results">Δεν βρέθηκαν αγγελίες με αυτά τα φίλτρα.
                <a href="search.php" class="back-link">Δείτε όλες τις αγγελίες</a>
            </p>
        <?php endif; ?>

        <?php foreach ($cars as $car): ?>
            <?php $url = 'vehicles-info.php?id=' . (int) $car['id']; ?>
            <article class="car-item">

                <a class="car-thumb" href="<?= h($url) ?>">
                    <?php if (empty($car['thumb'])): ?>
                        <img src="img/no-image.jpg" alt="Χωρίς φωτογραφία" loading="lazy">
                    <?php else: ?>
                        <img src="<?= h('uploads/' . basename($car['thumb'])) ?>"
                            alt="<?= h($car['brand'] . ' ' . $car['model']) ?>" loading="lazy">

                        <?php if (!empty($car['thumb_caption'])): ?>
                            <div class="car_thumb_caption"><?= h($car['thumb_caption']) ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </a>

                <div class="car-info">
                    <h3>
                        <a href="<?= h($url) ?>"><?= h($car['brand'] . ' ' . $car['model']) ?></a>
                    </h3>
                    <h4 class="car-info-subtitle">Βασικά Στοιχεία Οχήματος</h4>
                    <ul>
                        <li>Πωλητής: <?= h($car['seller_name']) ?></li>
                        <li>Κυβικά: <?= h(format_number($car['engine_cc'])) ?> cc</li>
                        <li>Τύπος Αμαξιού: <?= h(body_type_label($car['car_type'])) ?></li>
                        <li>Χιλιόμετρα: <?= h(format_number($car['mileage'])) ?> km</li>
                        <li>Τιμή: <?= h(format_price($car['price'])) ?></li>
                    </ul>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if ($totalPages > 1): ?>
            <nav class="pagination" aria-label="Σελίδες αποτελεσμάτων">

                <?php if ($page > 1): ?>
                    <a href="<?= h(page_url($page - 1)) ?>">&laquo; Προηγούμενη</a>
                <?php endif; ?>

                <?php foreach ($pageLinks as $link): ?>
                    <?php if ($link === '…'): ?>
                        <span class="pagination-dots">…</span>
                    <?php else: ?>
                        <a href="<?= h(page_url($link)) ?>" class="<?= $link === $page ? 'active' : '' ?>"
                            <?= $link === $page ? 'aria-current="page"' : '' ?>><?= $link ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= h(page_url($page + 1)) ?>">Επόμενη &raquo;</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
