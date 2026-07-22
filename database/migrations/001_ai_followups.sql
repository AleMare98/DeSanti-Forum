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
