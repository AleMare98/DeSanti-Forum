CREATE DATABASE IF NOT EXISTS `forum_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `forum_db`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `created_by` INT UNSIGNED NOT NULL,
    `source` ENUM('human', 'ai') NOT NULL DEFAULT 'human',
    `ai_prompt_hash` CHAR(64) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_categories_name` (`name`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `threads` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `source` ENUM('human', 'ai') NOT NULL DEFAULT 'human',
    `ai_prompt_hash` CHAR(64) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
    INDEX `idx_threads_category_created` (`category_id`, `created_at`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `comments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `content` TEXT NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `thread_id` INT UNSIGNED NOT NULL,
    `source` ENUM('human', 'ai') NOT NULL DEFAULT 'human',
    `ai_prompt_hash` CHAR(64) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`thread_id`) REFERENCES `threads`(`id`) ON DELETE CASCADE,
    INDEX `idx_comments_thread_created` (`thread_id`, `created_at`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `content` VARCHAR(500) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_chat_category_created` (`category_id`, `created_at`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `ai_generation_runs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `created_by` INT UNSIGNED NOT NULL,
    `provider` VARCHAR(30) NOT NULL,
    `model` VARCHAR(100) NOT NULL,
    `seed_prompt` TEXT NOT NULL,
    `language` VARCHAR(40) NOT NULL,
    `tone` VARCHAR(40) NOT NULL,
    `requested_categories` TINYINT UNSIGNED NOT NULL,
    `requested_threads_per_category` TINYINT UNSIGNED NOT NULL,
    `requested_comments_per_thread` TINYINT UNSIGNED NOT NULL,
    `created_categories` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_threads` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_comments` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('running', 'success', 'failed') NOT NULL DEFAULT 'running',
    `error_message` VARCHAR(500) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `forum_settings` (
    `id` TINYINT UNSIGNED PRIMARY KEY,
    `ai_followups_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `updated_by` INT UNSIGNED NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO `forum_settings` (`id`, `ai_followups_enabled`)
VALUES (1, 1)
ON DUPLICATE KEY UPDATE `ai_followups_enabled` = 1;

CREATE TABLE IF NOT EXISTS `ai_comment_followups` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trigger_comment_id` INT UNSIGNED NOT NULL UNIQUE,
    `thread_id` INT UNSIGNED NOT NULL,
    `status` ENUM('pending', 'replied', 'skipped', 'failed') NOT NULL DEFAULT 'pending',
    `should_reply` TINYINT(1) NULL,
    `response_content` TEXT NULL,
    `ai_comment_id` INT UNSIGNED NULL,
    `provider` VARCHAR(30) NOT NULL,
    `model` VARCHAR(100) NOT NULL,
    `error_message` VARCHAR(500) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME NULL,
    FOREIGN KEY (`trigger_comment_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`thread_id`) REFERENCES `threads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`ai_comment_id`) REFERENCES `comments`(`id`) ON DELETE SET NULL,
    INDEX `idx_ai_followups_thread_created` (`thread_id`, `created_at`)
) ENGINE=InnoDB;

INSERT INTO `users` (`username`, `password`, `role`) VALUES
('admin', '$2y$10$5IiAo1vHCsSRxlbDLEBl5.gveEXtzJ3a5UHzj6IRerDcPEjpfNacq', 'admin');
