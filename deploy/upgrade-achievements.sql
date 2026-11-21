-- Achievements: awards and milestones with an optional photo.
-- No rows are inserted. Importing again does not create a second table.

SET @achievements_table := (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'achievements'
);
SET @sql := IF(@achievements_table = 0,
  'CREATE TABLE `achievements` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(160) NOT NULL,
    `year` int unsigned DEFAULT NULL,
    `description` text DEFAULT NULL,
    `photo` varchar(255) DEFAULT NULL,
    `display_order` int NOT NULL DEFAULT 0,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
  'SELECT 1');
PREPARE achievements_table FROM @sql;
EXECUTE achievements_table;
DEALLOCATE PREPARE achievements_table;
