<?php
declare(strict_types=1);

// Κοινές λίστες επιλογών και συναρτήσεις μορφοποίησης για τα οχήματα.
// Οι τιμές (keys) ταιριάζουν με τα ENUM της βάσης δεδομένων.

const BODY_TYPES = [
    'mini'      => 'Mini',
    'hatchback' => 'Hatchback',
    'sedan'     => 'Sedan',
    'suv'       => 'SUV',
    'other'     => 'Άλλο',
];

const FUEL_TYPES = [
    'petrol'   => 'Βενζίνη',
    'diesel'   => 'Πετρέλαιο',
    'lpg'      => 'Υγραέριο (LPG)',
    'cng'      => 'Φυσικό Αέριο (CNG)',
    'electric' => 'Ηλεκτρικό',
    'other'    => 'Άλλο',
];

// Επιλογές ταξινόμησης για την αναζήτηση: key => [ετικέτα, ORDER BY]
const SORT_OPTIONS = [
    'newest'     => ['Νεότερες αγγελίες', 'c.id DESC'],
    'price_asc'  => ['Τιμή (αύξουσα)', 'c.price ASC, c.id DESC'],
    'price_desc' => ['Τιμή (φθίνουσα)', 'c.price DESC, c.id DESC'],
    'km_asc'     => ['Λιγότερα χιλιόμετρα', 'c.mileage ASC, c.id DESC'],
    'year_desc'  => ['Νεότερα οχήματα', 'c.reg_date DESC, c.id DESC'],
];

// Όρια πεδίων (ίδια με τη βάση δεδομένων)
const BRAND_MAX_LEN = 50;
const MODEL_MAX_LEN = 50;
const CC_MIN = 500;
const CC_MAX = 8000;
const KM_MAX = 2000000;
const PRICE_MAX = 99999999.99; // decimal(10,2)

function body_type_label(string $key): string
{
    return BODY_TYPES[$key] ?? $key;
}

function fuel_type_label(string $key): string
{
    return FUEL_TYPES[$key] ?? $key;
}

// 15000.00 -> "15.000 €", 1234.5 -> "1.234,50 €"
function format_price(mixed $price): string
{
    $value = (float) $price;
    $decimals = fmod($value, 1.0) === 0.0 ? 0 : 2;
    return number_format($value, $decimals, ',', '.') . ' €';
}

// 250000 -> "250.000"
function format_number(mixed $value): string
{
    return number_format((int) $value, 0, ',', '.');
}

// 2015-04-23 -> "23/04/2015"
function format_date(?string $date): string
{
    $d = $date ? DateTime::createFromFormat('Y-m-d', $date) : false;
    return $d ? $d->format('d/m/Y') : (string) $date;
}
