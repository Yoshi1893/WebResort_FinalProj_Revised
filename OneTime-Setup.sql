-- 9 Waves Events Place one-time database setup
-- Import this into MySQL/phpMyAdmin to create a clean working schema for the current codebase.

CREATE DATABASE IF NOT EXISTS `9waves_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `9waves_db`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `inquiry_amenities`;
DROP TABLE IF EXISTS `access_log`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `inquiries`;
DROP TABLE IF EXISTS `amenities`;
DROP TABLE IF EXISTS `venues`;
DROP TABLE IF EXISTS `packages`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin') NOT NULL DEFAULT 'customer',
  `profile_image` longtext DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','revoked','deleted') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(80) NOT NULL,
  `name` varchar(150) NOT NULL,
  `base_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `guest_capacity` int(11) NOT NULL DEFAULT 0,
  `tagline` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `max_private_rooms` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_packages_key` (`key`),
  UNIQUE KEY `uq_packages_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `venues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_venues_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venue_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_rooms_venue_id` (`venue_id`),
  CONSTRAINT `fk_rooms_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `amenities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_amenities_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `reference` varchar(40) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `preferred_date` date DEFAULT NULL,
  `backup_date` date DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `venue_id` int(11) DEFAULT NULL,
  `requested_rooms` int(11) NOT NULL DEFAULT 0,
  `estimated_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `status` enum('submitted','review','proposal','closed') NOT NULL DEFAULT 'submitted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inquiries_reference` (`reference`),
  KEY `idx_inquiries_user_id` (`user_id`),
  KEY `idx_inquiries_email` (`email`),
  KEY `idx_inquiries_package_id` (`package_id`),
  KEY `idx_inquiries_venue_id` (`venue_id`),
  CONSTRAINT `fk_inquiries_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inquiries_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inquiries_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `inquiry_amenities` (
  `inquiry_id` int(11) NOT NULL,
  `amenity_id` int(11) NOT NULL,
  PRIMARY KEY (`inquiry_id`, `amenity_id`),
  KEY `idx_inquiry_amenities_amenity_id` (`amenity_id`),
  CONSTRAINT `fk_inquiry_amenities_inquiry` FOREIGN KEY (`inquiry_id`) REFERENCES `inquiries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_inquiry_amenities_amenity` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `access_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `actioned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_access_log_user_id` (`user_id`),
  KEY `idx_access_log_actioned_by` (`actioned_by`),
  CONSTRAINT `fk_access_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_access_log_actioned_by` FOREIGN KEY (`actioned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password`, `role`, `profile_image`, `archived`, `status`, `created_at`) VALUES
(1, 'Admin', 'User', 'admin@9waves.com', '+63 917 111 0000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, 0, 'active', '2026-04-01 00:00:00'),
(2, 'Test', 'User', 'test@example.com', '+639171234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', NULL, 0, 'active', '2026-04-08 00:00:00');

INSERT INTO `packages` (`id`, `key`, `name`, `base_price`, `guest_capacity`, `tagline`, `active`, `max_private_rooms`) VALUES
(1, 'ripple', 'Ripple Pack', 45000.00, 100, 'Best for intimate celebrations', 1, 2),
(2, 'crest', 'Crest Pack', 85000.00, 200, 'Ideal for mid-size signature events', 1, 5),
(3, 'sovereign', 'Sovereign Wave', 150000.00, 500, 'Built for grand celebrations', 1, 8);

INSERT INTO `venues` (`id`, `name`, `description`, `active`) VALUES
(1, 'Pearl Ballroom', 'Grand indoor ballroom for large celebrations.', 1),
(2, 'Wavecrest Garden', 'Open-air garden venue with sunset appeal.', 1),
(3, 'Tidal Pool Terrace', 'Poolside venue for intimate gatherings.', 1);

INSERT INTO `rooms` (`id`, `venue_id`, `name`, `note`, `active`) VALUES
(1, 1, 'Bridal Suite', 'Private prep room near ballroom entrance.', 1),
(2, 1, 'VIP Lounge', 'Holding room for principal sponsors and family.', 1),
(3, 2, 'Garden Prep Room', 'Air-conditioned prep room for outdoor events.', 1),
(4, 3, 'Poolside Cabana', 'Small lounge for hosts and coordinators.', 0);

INSERT INTO `amenities` (`id`, `name`, `price`, `active`) VALUES
(1, 'Extra Event Hour', 5000.00, 1),
(2, '3-Tier Wedding Cake', 8000.00, 1),
(3, 'Full Event Coordination', 12000.00, 1),
(4, 'Pro A/V Upgrade', 15000.00, 1),
(5, 'Flower Wall Backdrop', 10000.00, 1),
(6, 'Overnight Accommodation', 25000.00, 0);

INSERT INTO `inquiries` (`id`, `user_id`, `reference`, `full_name`, `email`, `event_type`, `event_date`, `preferred_date`, `backup_date`, `package_id`, `venue_id`, `requested_rooms`, `estimated_total`, `notes`, `status`, `created_at`) VALUES
(1, 2, 'INQ-20260408-01201', 'Maria Santos', 'maria.santos@example.com', 'Wedding', '2026-06-12', '2026-06-12', '2026-06-19', 2, 1, 3, 107000.00, 'Would like a classic ballroom setup with ivory florals.', 'review', '2026-04-08 00:00:00'),
(2, 2, 'INQ-20260410-01202', 'Maria Santos', 'maria.santos@example.com', 'Debut', '2026-08-02', '2026-08-02', '2026-08-09', 1, 2, 1, 53000.00, 'Sunset timing preferred for photos.', 'submitted', '2026-04-10 00:00:00'),
(3, 2, 'INQ-20260412-01203', 'Paolo Reyes', 'paolo.reyes@example.com', 'Corporate Gala', '2026-09-18', '2026-09-18', '2026-09-25', 3, 1, 5, 190000.00, 'Needs stage projection and executive holding rooms.', 'proposal', '2026-04-12 00:00:00');

INSERT INTO `inquiry_amenities` (`inquiry_id`, `amenity_id`) VALUES
(1, 3),
(1, 5),
(2, 2),
(3, 4),
(3, 6);

INSERT INTO `access_log` (`id`, `user_id`, `action`, `reason`, `actioned_by`, `created_at`) VALUES
(1, 2, 'granted', 'Initial sample account', 1, '2026-04-08 00:00:00');

SET FOREIGN_KEY_CHECKS = 1;
