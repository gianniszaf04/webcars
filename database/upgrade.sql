-- Upgrade an existing Webcars database (created with an older schema.sql) to the current version.
-- Safe to run more than once.
--   mysql -u root -p webcars < database/upgrade.sql

SET NAMES utf8mb4;

-- 1. Table for failed login attempts (brute-force protection on login.php)
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `attempted_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_attempts_user` (`username`, `attempted_at`),
  KEY `idx_attempts_ip` (`ip`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. The theme is stored in a cookie, so this column was never used
--    (written this way so it works on both MySQL 8 and MariaDB)
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'css_preference');
SET @sql := IF(@has_col > 0, 'ALTER TABLE `users` DROP COLUMN `css_preference`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Activation codes are no longer needed once an account is active
UPDATE `users` SET `activation_code` = '' WHERE `is_active` = 1;
