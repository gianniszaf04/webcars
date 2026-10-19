<?php
// Πλαϊνή στήλη φίλτρων του search.php (χρησιμοποιεί τις μεταβλητές φίλτρων του search.php)
?>
<aside class="search-sidebar">
    <h3>Φίλτρα</h3>
    <form action="search.php" method="GET">

        <label for="car_type">Τύπος Αμαξιού</label>
        <select name="car_type" id="car_type">
            <option value="">Όλοι οι τύποι</option>
            <?php foreach (BODY_TYPES as $value => $label): ?>
                <option value="<?= h($value) ?>" <?= $car_type === $value ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="fuel_type">Καύσιμο</label>
        <select name="fuel_type" id="fuel_type">
            <option value="">Όλα τα καύσιμα</option>
            <?php foreach (FUEL_TYPES as $value => $label): ?>
                <option value="<?= h($value) ?>" <?= $fuel_type === $value ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="brand">Μάρκα</label>
        <input type="text" id="brand" name="brand" placeholder="Μάρκα (π.χ. Toyota)" maxlength="50" value="<?= h($brand) ?>">

        <label for="min_cc">Κυβικά (από)</label>
        <input type="number" id="min_cc" name="min_cc" placeholder="Κυβικά (από)" min="0" value="<?= h($min_cc) ?>">

        <label for="max_price">Τιμή (έως)</label>
        <input type="number" id="max_price" name="max_price" placeholder="Τιμή (έως)" min="0" value="<?= h($max_price) ?>">

        <label for="max_km">Χιλιόμετρα (έως)</label>
        <input type="number" id="max_km" name="max_km" placeholder="Χιλιόμετρα (έως)" min="0" value="<?= h($max_km) ?>">

        <label for="min_year">Χρονολογία (από)</label>
        <input type="number" id="min_year" name="min_year" placeholder="π.χ. 2010" min="1900" max="<?= date('Y') ?>" value="<?= h($min_year) ?>">

        <label for="sort">Ταξινόμηση</label>
        <select name="sort" id="sort">
            <?php foreach (SORT_OPTIONS as $value => [$label]): ?>
                <option value="<?= h($value) ?>" <?= $sort === $value ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Αναζήτηση</button>

        <div class="header-backlink sidebar-reset">
            <a href="search.php" class="back-link">Καθαρισμός</a>
        </div>
    </form>
</aside>
