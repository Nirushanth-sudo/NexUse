-- NexUse Database Schema SQL
-- Use this file to import schema and seed data into MySQL database.

CREATE DATABASE IF NOT EXISTS `nexuse` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nexuse`;

-- Drop tables if they exist to start fresh
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `wishlist`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `chats`;
DROP TABLE IF EXISTS `disputes`;
DROP TABLE IF EXISTS `requests`;
DROP TABLE IF EXISTS `listings`;
DROP TABLE IF EXISTS `donation_requests`;
DROP TABLE IF EXISTS `users`;

-- 1. Create Users Table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('admin', 'buyer', 'seller', 'lender', 'renter', 'donor', 'receiver') NOT NULL DEFAULT 'buyer',
  `rating` DECIMAL(3,2) NOT NULL DEFAULT 5.00,
  `verified` TINYINT(1) NOT NULL DEFAULT 0,
  `phone` VARCHAR(20) DEFAULT NULL,
  `location` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `google_id` VARCHAR(255) DEFAULT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create Listings Table
CREATE TABLE `listings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `condition` VARCHAR(50) NOT NULL,
  `type` ENUM('buy', 'rent', 'share', 'donate', 'disposal') NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `availability` ENUM('available', 'requested', 'rented', 'completed') NOT NULL DEFAULT 'available',
  `owner_id` INT NOT NULL,
  `location` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `emoji` VARCHAR(10) DEFAULT '📦',
  FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create Requests Table
CREATE TABLE `requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `listing_id` INT NOT NULL,
  `requester_id` INT NOT NULL,
  `requester_role` VARCHAR(50) NOT NULL,
  `status` ENUM('pending', 'accepted', 'rejected', 'returned') NOT NULL DEFAULT 'pending',
  `start_date` DATE DEFAULT NULL,
  `return_date` DATE DEFAULT NULL,
  `actual_return` DATE DEFAULT NULL,
  `penalty` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `condition_on_return` VARCHAR(255) DEFAULT NULL,
  `note` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create Donation Requests Table
CREATE TABLE `donation_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `location` VARCHAR(100) NOT NULL,
  `item_type` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `requester_id` INT NOT NULL,
  `rating` DECIMAL(3,2) NOT NULL DEFAULT 5.00,
  FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create Disputes Table
CREATE TABLE `disputes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reporter_id` INT NOT NULL,
  `reported_user_id` INT NOT NULL,
  `listing_id` INT DEFAULT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `status` ENUM('pending', 'resolved') NOT NULL DEFAULT 'pending',
  `resolution` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Create Chats Table
CREATE TABLE `chats` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `listing_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `ts` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Create Notifications Table
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Create Wishlist Table
CREATE TABLE `wishlist` (
  `user_id` INT NOT NULL,
  `listing_id` INT NOT NULL,
  PRIMARY KEY (`user_id`, `listing_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Create Cart Table
CREATE TABLE `cart` (
  `user_id` INT NOT NULL,
  `listing_id` INT NOT NULL,
  PRIMARY KEY (`user_id`, `listing_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`listing_id`) REFERENCES `listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ──────────────────────────────────────────────────────────────
-- SEED DATA
-- ──────────────────────────────────────────────────────────────

-- Users
-- Password for admin: admin123
-- Password for everyone else: password123
INSERT INTO `users` (`id`, `username`, `name`, `email`, `password`, `role`, `rating`, `verified`, `phone`, `location`, `address`) VALUES
(1, 'admin', 'System Administrator', 'admin@nexuse.com', '$2y$12$WOlbeaUOi5SmAEhaMhRABewMpoyBELhtLm/tUvnEP6wKObPmnLZ8.', 'admin', 5.00, 1, '', 'Global', ''),
(2, 'bob', 'Bob Martin', 'bob@gmail.com', '$2y$12$YFw/QiDPxqCUtAeX6aVkOeaf0GkoqRNGmeA5a/RHlva3oL6uAg/NW', 'buyer', 4.80, 1, '+15551001', 'New York, NY', '12 Oak Ave'),
(3, 'sam', 'Sam Chen', 'sam@resell.io', '$2y$12$YFw/QiDPxqCUtAeX6aVkOeaf0GkoqRNGmeA5a/RHlva3oL6uAg/NW', 'seller', 4.60, 1, '+15551002', 'San Francisco, CA', '88 Market St'),
(4, 'lucy', 'Lucy Harte', 'lucy@share.net', '$2y$12$YFw/QiDPxqCUtAeX6aVkOeaf0GkoqRNGmeA5a/RHlva3oL6uAg/NW', 'lender', 4.90, 1, '+15551003', 'New York, NY', '5 Elm St'),
(5, 'ryan', 'Ryan Patel', 'ryan@rent.com', '$2y$12$YFw/QiDPxqCUtAeX6aVkOeaf0GkoqRNGmeA5a/RHlva3oL6uAg/NW', 'renter', 4.20, 0, '+15551004', 'Newark, NJ', ''),
(6, 'dan', 'Dan Foster', 'dan@give.org', '$2y$12$YFw/QiDPxqCUtAeX6aVkOeaf0GkoqRNGmeA5a/RHlva3oL6uAg/NW', 'donor', 5.00, 1, '+15551005', 'Chicago, IL', ''),
(7, 'rachel', 'Elena Rostova', 'rachel@need.org', '$2y$12$YFw/QiDPxqCUtAeX6aVkOeaf0GkoqRNGmeA5a/RHlva3oL6uAg/NW', 'receiver', 4.70, 0, '+15551006', 'Chicago, IL', ''),
(8, 'marcus', 'Marcus Aurelius', 'marcus@help.org', '$2y$12$YFw/QiDPxqCUtAeX6aVkOeaf0GkoqRNGmeA5a/RHlva3oL6uAg/NW', 'receiver', 4.70, 0, '+15551007', 'Chicago, IL', '');

-- Listings
INSERT INTO `listings` (`id`, `title`, `category`, `condition`, `type`, `price`, `availability`, `owner_id`, `location`, `description`, `emoji`) VALUES
(1, 'MacBook Pro 15-inch (2018)', 'Electronics', 'Like New', 'buy', 650.00, 'available', 3, 'San Francisco', 'Well-maintained MacBook Pro, 16GB RAM, 512GB SSD. Minor scratches on the bottom. Charger included. Perfect for developers and students.', '💻'),
(2, 'DeWalt Cordless Drill 20V', 'Tools', 'Good', 'rent', 10.00, 'available', 4, 'New York', 'Powerful brushless cordless drill, includes battery, charger, and carrying bag. Perfect for home renovation and professional projects.', '🔧'),
(3, 'Canon EOS Rebel T7 DSLR', 'Electronics', 'Good', 'buy', 380.00, 'available', 3, 'San Francisco', 'Canon camera with 18-55mm lens. Great starter camera for photography enthusiasts. Comes with extra battery and 32GB SD card.', '📷'),
(4, 'Solid Oak Coffee Table', 'Furniture', 'Good', 'donate', 0.00, 'available', 3, 'San Francisco', 'Beautiful rustic solid oak coffee table. Dimensions: 120cm x 60cm. Quite heavy, will need two people to carry. Self-collection only.', '🛋️'),
(5, 'Introduction to Algorithms...', 'Books', 'Good', 'donate', 0.00, 'requested', 7, 'Chicago', 'Classic computer science textbook, 3rd edition. Perfect for CS students. All pages intact, some highlighting.', '📚'),
(6, 'Pressure Washer 2000 PSI', 'Tools', 'Good', 'rent', 25.00, 'available', 4, 'New York', 'High pressure washer for cleaning driveways, patios, cars. Includes 3 nozzle attachments and 20ft hose.', '🚿'),
(7, 'Old CRT Monitor for Parts', 'Electronics', 'Fair', 'buy', 15.00, 'available', 3, 'San Francisco', '15-inch CRT monitor, turns on but screen flickers. Useful for parts or retro computing enthusiasts.', '🖥️'),
(8, 'Coleman 6-Person Camping Tent', 'Sports', 'Good', 'share', 0.00, 'available', 4, 'New York', 'Waterproof dome tent with easy 10-minute setup. Includes stakes and rainfly. Please return clean and dry.', '⛺'),
(9, 'Warm Winter Coats (Size M/L)', 'Clothing', 'Good', 'donate', 0.00, 'available', 3, 'San Francisco', 'Collection of 3 winter jackets, all size M or L. Donated for community shelter collection.', '🧥'),
(10, 'Honda Petrol Lawn Mower', 'Tools', 'Good', 'rent', 15.00, 'rented', 4, 'New York', 'Self-propelled mower with grass catcher. Ideal for large yards. Currently rented out.', '🌿');

-- Requests
INSERT INTO `requests` (`id`, `listing_id`, `requester_id`, `requester_role`, `status`, `start_date`, `return_date`, `actual_return`, `penalty`, `condition_on_return`, `note`, `created_at`) VALUES
(1, 2, 5, 'renter', 'pending', '2026-06-16', '2026-06-21', NULL, 0.00, NULL, NULL, '2026-06-14 10:00:00'),
(2, 10, 5, 'renter', 'accepted', '2026-06-05', '2026-06-10', NULL, 0.00, NULL, NULL, '2026-06-04 09:00:00'),
(3, 5, 6, 'donor', 'pending', NULL, NULL, NULL, 0.00, NULL, 'I have the 3rd edition. Can drop off.', '2026-06-13 14:00:00'),
(4, 1, 2, 'buyer', 'pending', NULL, NULL, NULL, 0.00, NULL, NULL, '2026-06-12 09:00:00');

-- Donation Requests
INSERT INTO `donation_requests` (`id`, `title`, `category`, `location`, `item_type`, `description`, `requester_id`, `rating`) VALUES
(1, 'Laptops for Kids Charity Program', 'Electronics', 'Chicago', 'LAPTOPS / TABLETS', 'Seeking working laptops or tablets for underprivileged school children in the Chicago area to support remote learning.', 7, 4.30),
(2, 'Reference Books for Local Community Center', 'Books', 'Chicago', 'EDUCATIONAL BOOKS', 'Looking for dictionaries, encyclopedias, and educational books for a community library project.', 8, 4.70);

-- Disputes
INSERT INTO `disputes` (`id`, `reporter_id`, `reported_user_id`, `listing_id`, `reason`, `description`, `status`, `resolution`, `created_at`) VALUES
(1, 4, 5, 10, 'Non-Return', 'Ryan has had the lawn mower 5 days past the return date (June 10) and is unresponsive to messages.', 'pending', NULL, '2026-06-13 17:00:00');

-- Chats
INSERT INTO `chats` (`id`, `sender_id`, `receiver_id`, `listing_id`, `message`, `ts`) VALUES
(1, 2, 3, 1, 'Hi Sam, is the MacBook still available?', '2026-06-14 09:30:00'),
(2, 3, 2, 1, 'Yes it is! The battery holds 80% charge, very solid.', '2026-06-14 09:45:00'),
(3, 5, 4, 10, 'Hi Lucy, the mower is a bit late — I\'ll return it this weekend.', '2026-06-11 12:00:00'),
(4, 4, 5, 10, 'Ryan, it\'s already 5 days late. Please return it today or I\'ll have to file a report.', '2026-06-13 10:00:00');

-- Notifications
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 4, 'New Rental Request', 'Renter Ryan wants to borrow your DeWalt Drill from Jun 16–21.', 0, '2026-06-14 10:00:00'),
(2, 5, '⚠️ Return Overdue', 'Your Honda Lawn Mower rental was due Jun 10. Penalties accumulating.', 0, '2026-06-12 08:00:00'),
(3, 7, 'Donation Pledge Received', 'Donor Dan pledged books for your request \'Introduction to Algorithms\'.', 0, '2026-06-13 14:00:00'),
(4, 3, 'Purchase Request', 'Buyer Bob sent a purchase request for your MacBook Pro listing.', 0, '2026-06-12 09:00:00');
