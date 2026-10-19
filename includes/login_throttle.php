<?php
declare(strict_types=1);

// Περιορισμός αποτυχημένων συνδέσεων (brute force).
// Μετά από 5 λάθος κωδικούς για το ίδιο username (ή 20 από την ίδια IP) μέσα σε 15 λεπτά,
// το login μπλοκάρει προσωρινά. Χρησιμοποιεί τον πίνακα `login_attempts`.
// Αν ο πίνακας δεν υπάρχει ακόμα (παλιά βάση), το login συνεχίζει να δουλεύει κανονικά.

const LOGIN_MAX_PER_USER = 5;
const LOGIN_MAX_PER_IP = 20;
const LOGIN_WINDOW_MINUTES = 15;

function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
}

function login_is_throttled(PDO $pdo, string $username): bool
{
    try {
        $stmt = $pdo->prepare(
            'SELECT
                SUM(username = :u) AS by_user,
                SUM(ip = :ip)      AS by_ip
             FROM login_attempts
             WHERE attempted_at > NOW() - INTERVAL ' . LOGIN_WINDOW_MINUTES . ' MINUTE
               AND (username = :u2 OR ip = :ip2)'
        );
        $stmt->execute([':u' => $username, ':ip' => client_ip(), ':u2' => $username, ':ip2' => client_ip()]);
        $row = $stmt->fetch() ?: [];
        return (int) ($row['by_user'] ?? 0) >= LOGIN_MAX_PER_USER
            || (int) ($row['by_ip'] ?? 0) >= LOGIN_MAX_PER_IP;
    } catch (PDOException $e) {
        error_log('login_throttle: ' . $e->getMessage());
        return false;
    }
}

function login_record_failure(PDO $pdo, string $username): void
{
    try {
        $pdo->prepare('INSERT INTO login_attempts (username, ip) VALUES (:u, :ip)')
            ->execute([':u' => mb_substr($username, 0, 50), ':ip' => client_ip()]);
        // καθαρισμός παλιών εγγραφών
        $pdo->exec('DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY');
    } catch (PDOException $e) {
        error_log('login_throttle: ' . $e->getMessage());
    }
}

function login_clear_failures(PDO $pdo, string $username): void
{
    try {
        $pdo->prepare('DELETE FROM login_attempts WHERE username = :u')->execute([':u' => $username]);
    } catch (PDOException $e) {
        error_log('login_throttle: ' . $e->getMessage());
    }
}
