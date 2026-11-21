-- Adds the registered and corporate office addresses to the existing settings row.
-- Nothing is filled in. Importing again does not add the columns a second time
-- and does not change an address that has already been entered.

SET @registered_office := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'site_settings'
    AND COLUMN_NAME = 'registered_office_address'
);
SET @sql := IF(@registered_office = 0,
  'ALTER TABLE `site_settings` ADD COLUMN `registered_office_address` text NULL AFTER `address`',
  'SELECT 1');
PREPARE registered_office_column FROM @sql;
EXECUTE registered_office_column;
DEALLOCATE PREPARE registered_office_column;

SET @corporate_office := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'site_settings'
    AND COLUMN_NAME = 'corporate_office_address'
);
SET @sql := IF(@corporate_office = 0,
  'ALTER TABLE `site_settings` ADD COLUMN `corporate_office_address` text NULL AFTER `registered_office_address`',
  'SELECT 1');
PREPARE corporate_office_column FROM @sql;
EXECUTE corporate_office_column;
DEALLOCATE PREPARE corporate_office_column;
