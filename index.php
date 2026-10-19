<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/userTracking.php';
require_once __DIR__ . '/includes/catalog.php';
$no_container = true;

require_once __DIR__ . '/includes/header.php';
?>
<div class="index">
    <h1>U S E D &nbsp; C A R S</h1>
    <p class="index-subtitle">Βρείτε το επόμενο σας αυτοκίνητο εύκολα και γρήγορα</p>
    <div class="search">

        <form action="search.php" method="GET" class="main-search">
            <select name="car_type" aria-label="Τύπος αμαξιού">
                <option value="">Τύποι Αμαξιού</option>
                <?php foreach (BODY_TYPES as $value => $label): ?>
                    <option value="<?= h($value) ?>"><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="brand" placeholder="Μάρκα (π.χ. Toyota)" aria-label="Μάρκα" maxlength="50">
            <input type="number" name="min_cc" placeholder="Κυβικά (από)" aria-label="Κυβικά από" min="0">
            <input type="number" name="max_price" placeholder="Τιμή (έως)" aria-label="Τιμή έως" min="0">
            <button type="submit">Αναζήτηση</button>
        </form>
    </div>

    <div class="pr-cars">
        <a href="search.php?max_price=5000">Οχήματα έως 5.000€</a>
        <a href="search.php?car_type=suv">SUV Οχήματα</a>
        <a href="search.php?sort=newest">Όλες οι αγγελίες</a>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
