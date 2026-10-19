-- Demo data for Webcars (optional). Import AFTER schema.sql:
--   mysql -u root -p webcars < database/seed.sql
-- Every demo account: username USER1 … USER9, password: demo_password1

SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO `users` (`id`, `username`, `password`, `first_name`, `last_name`, `email`, `phone`, `activation_code`, `is_active`) VALUES
(5, 'USER1', '$2y$10$7aOtCZB801or91l6tMd5B.aKUJP4w8GUay2UtbAIPhJ2QzN51P/Te', 'Demo', 'User 1', 'user1@example.com', '6900000000', '', 1),
(7, 'USER2', '$2y$10$7aOtCZB801or91l6tMd5B.aKUJP4w8GUay2UtbAIPhJ2QzN51P/Te', 'Demo', 'User 2', 'user2@example.com', '6900000000', '', 1),
(8, 'USER3', '$2y$10$7aOtCZB801or91l6tMd5B.aKUJP4w8GUay2UtbAIPhJ2QzN51P/Te', 'Demo', 'User 3', 'user3@example.com', '6900000000', '', 1),
(9, 'USER4', '$2y$10$7aOtCZB801or91l6tMd5B.aKUJP4w8GUay2UtbAIPhJ2QzN51P/Te', 'Demo', 'User 4', 'user4@example.com', '6900000000', '', 1),
(10, 'USER5', '$2y$10$7aOtCZB801or91l6tMd5B.aKUJP4w8GUay2UtbAIPhJ2QzN51P/Te', 'Demo', 'User 5', 'user5@example.com', '6900000000', '', 1),
(11, 'USER6', '$2y$10$7aOtCZB801or91l6tMd5B.aKUJP4w8GUay2UtbAIPhJ2QzN51P/Te', 'Demo', 'User 6', 'user6@example.com', '6900000000', '', 1),
(12, 'USER7', '$2y$10$7aOtCZB801or91l6tMd5B.aKUJP4w8GUay2UtbAIPhJ2QzN51P/Te', 'Demo', 'User 7', 'user7@example.com', '6900000000', '', 1),
(13, 'USER8', '$2y$10$7aOtCZB801or91l6tMd5B.aKUJP4w8GUay2UtbAIPhJ2QzN51P/Te', 'Demo', 'User 8', 'user8@example.com', '6900000000', '', 1),
(14, 'USER9', '$2y$10$7aOtCZB801or91l6tMd5B.aKUJP4w8GUay2UtbAIPhJ2QzN51P/Te', 'Demo', 'User 9', 'user9@example.com', '6900000000', '', 1);

INSERT INTO `cars` (`id`, `user_id`, `brand`, `model`, `car_type`, `fuel_type`, `engine_cc`, `mileage`, `reg_date`, `has_turbo`, `is_hybrid`, `needs_repair`, `price`, `created_at`, `updated_at`) VALUES
(3, 5, 'TOYOTA', 'YARIS', 'hatchback', 'petrol', 1500, 150000, '2004-08-25', 0, 0, 1, 15000.00, '2026-01-15 22:03:03', NULL),
(4, 7, 'FIAT', 'PUNTO', 'other', 'diesel', 1200, 200000, '2005-05-30', 1, 1, 0, 15000.00, '2026-01-15 22:15:52', NULL),
(5, 8, 'BMW', '1 series', 'other', 'petrol', 1600, 250000, '2007-06-15', 1, 0, 0, 30000.00, '2026-01-15 22:20:01', NULL),
(6, 9, 'MERCEDES', 'CLA', 'hatchback', 'electric', 2000, 5000, '2010-05-31', 1, 0, 1, 5000.00, '2026-01-15 22:26:53', NULL),
(7, 10, 'MERCEDES', 'BENZ', 'other', 'petrol', 1450, 250000, '2015-04-23', 0, 1, 0, 100000.00, '2026-01-15 22:30:53', NULL),
(8, 11, 'FIAT', 'PANDA', 'mini', 'other', 600, 1000000, '1970-02-05', 1, 1, 1, 15.00, '2026-01-15 22:46:22', NULL),
(9, 12, 'JEEP', 'SUV', 'suv', 'lpg', 1900, 60000, '2016-07-24', 1, 1, 0, 20000.00, '2026-01-15 22:53:35', NULL),
(10, 13, 'MAZDA', 'RX7', 'other', 'diesel', 1150, 300000, '1990-05-23', 0, 0, 1, 15000.00, '2026-01-15 22:58:24', NULL),
(11, 14, 'ALFA ROMEO', 'ALFA 156', 'sedan', 'petrol', 1600, 299000, '1999-09-09', 0, 0, 1, 3000.00, '2026-01-15 23:02:24', NULL);

COMMIT;
