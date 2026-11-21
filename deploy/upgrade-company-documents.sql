-- Company documents: incorporation, trade licence, BIN, TIN, and other certificates.
-- No rows are inserted. Importing again does not create a second table.

SET @documents_table := (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'company_documents'
);
SET @sql := IF(@documents_table = 0,
  'CREATE TABLE `company_documents` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(160) NOT NULL,
    `document_type` varchar(32) NOT NULL,
    `document_number` varchar(80) DEFAULT NULL,
    `issuing_authority` varchar(160) DEFAULT NULL,
    `issue_date` date DEFAULT NULL,
    `expiry_date` date DEFAULT NULL,
    `certificate_file` varchar(255) DEFAULT NULL,
    `display_order` int NOT NULL DEFAULT 0,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
  'SELECT 1');
PREPARE company_documents_table FROM @sql;
EXECUTE company_documents_table;
DEALLOCATE PREPARE company_documents_table;
