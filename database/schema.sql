-- Database Schema for Campus Events Management System

CREATE DATABASE IF NOT EXISTS `campus_events` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `campus_events`;

-- 1. Admins Table
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `fullname` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Students Table
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` VARCHAR(30) NOT NULL UNIQUE, -- e.g., Roll number
    `fullname` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(15) DEFAULT NULL,
    `branch` VARCHAR(50) DEFAULT NULL,
    `year` INT DEFAULT NULL, -- 1, 2, 3, 4
    `status` ENUM('active', 'blocked') DEFAULT 'active',
    `otp` VARCHAR(10) DEFAULT NULL,
    `otp_expiry` DATETIME DEFAULT NULL,
    `profile_pic` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_student_status` (`status`)
) ENGINE=InnoDB;

-- 3. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. Events Table
CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_name` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `category_id` INT NOT NULL,
    `venue` VARCHAR(100) NOT NULL,
    `building` VARCHAR(50) DEFAULT NULL,
    `room` VARCHAR(50) DEFAULT NULL,
    `date` DATE NOT NULL,
    `time` TIME NOT NULL,
    `organizer` VARCHAR(100) NOT NULL,
    `faculty_coordinator` VARCHAR(100) NOT NULL,
    `student_coordinator` VARCHAR(100) NOT NULL,
    `max_participants` INT NOT NULL,
    `registration_deadline` DATE NOT NULL,
    `event_banner` VARCHAR(255) DEFAULT NULL,
    `rules` TEXT DEFAULT NULL,
    `prizes` TEXT DEFAULT NULL,
    `contact_details` TEXT DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    `available_seats` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
    INDEX `idx_event_date` (`date`),
    INDEX `idx_event_status` (`status`),
    INDEX `idx_event_category` (`category_id`)
) ENGINE=InnoDB;

-- 5. Registrations Table
CREATE TABLE IF NOT EXISTS `registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `event_id` INT NOT NULL,
    `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `attendance` ENUM('present', 'absent', 'pending') DEFAULT 'pending',
    `ticket_code` VARCHAR(50) NOT NULL UNIQUE,
    `certificate_status` ENUM('none', 'generated') DEFAULT 'none',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
    INDEX `idx_reg_student` (`student_id`),
    INDEX `idx_reg_event` (`event_id`),
    INDEX `idx_reg_status` (`status`),
    UNIQUE KEY `unique_student_event` (`student_id`, `event_id`)
) ENGINE=InnoDB;

-- 6. Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT DEFAULT NULL, -- NULL means send to all
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('unread', 'read') DEFAULT 'unread',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    INDEX `idx_notification_student` (`student_id`)
) ENGINE=InnoDB;

-- 7. Feedback Table
CREATE TABLE IF NOT EXISTS `feedback` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `event_id` INT NOT NULL,
    `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
    `comment` TEXT DEFAULT NULL,
    `reply` TEXT DEFAULT NULL,
    `replied_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
    INDEX `idx_feedback_event` (`event_id`)
) ENGINE=InnoDB;

-- 8. Gallery Table
CREATE TABLE IF NOT EXISTS `gallery` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT DEFAULT NULL, -- Can be associated with an event or general homepage slider
    `image_path` VARCHAR(255) NOT NULL,
    `caption` VARCHAR(200) DEFAULT NULL,
    `is_slider` TINYINT(1) DEFAULT 0, -- 1 if used for homepage hero slider
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 9. Announcements Table
CREATE TABLE IF NOT EXISTS `announcements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `content` TEXT NOT NULL,
    `priority` ENUM('normal', 'high') DEFAULT 'normal',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 10. Certificates Table
CREATE TABLE IF NOT EXISTS `certificates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `registration_id` INT NOT NULL UNIQUE,
    `certificate_code` VARCHAR(50) NOT NULL UNIQUE,
    `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 11. Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `site_name` VARCHAR(100) NOT NULL DEFAULT 'Campus Guru',
    `site_email` VARCHAR(100) NOT NULL DEFAULT 'organizer@campus.edu',
    `site_phone` VARCHAR(20) DEFAULT '+1-234-567-890',
    `site_address` TEXT DEFAULT NULL,
    `logo` VARCHAR(255) DEFAULT NULL,
    `slider_settings` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 12. Activity Logs Table
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL, -- ID of admin or student
    `user_role` ENUM('admin', 'student', 'system') NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
