-- Adds a published flag to news.
-- Existing articles stay published. Importing again does not add a second column
-- and does not change a later unpublished article.

SET @news_published := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'news'
    AND COLUMN_NAME = 'is_published'
);
SET @sql := IF(@news_published = 0,
  'ALTER TABLE `news` ADD COLUMN `is_published` tinyint(1) NOT NULL DEFAULT 1 AFTER `publish_date`',
  'SELECT 1');
PREPARE news_published_column FROM @sql;
EXECUTE news_published_column;
DEALLOCATE PREPARE news_published_column;
