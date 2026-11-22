<?php
/**
 * Common functions for the website
 */

/**
 * Get site settings from database
 * @param PDO $conn Database connection
 * @return array Site settings
 */
function getSiteSettings($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM site_settings WHERE id = 1");
        $stmt->execute();
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        return $settings ? $settings : [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get services from database
 * @param PDO $conn Database connection
 * @param int $limit Limit the number of services returned (0 for all)
 * @return array Services
 */
function getServices($conn, $limit = 0) {
    try {
        $query = "SELECT * FROM services ORDER BY display_order ASC, title ASC";
        if ($limit > 0) {
            $query .= " LIMIT " . (int)$limit;
        }
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get statistics from database
 * @param PDO $conn Database connection
 * @return array Statistics
 */
function getStatistics($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM statistics ORDER BY display_order ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get social links from database
 * @param PDO $conn Database connection
 * @return array Social links
 */
function getSocialLinks($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM social_links");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Sanitize user input
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function publicAsset(string $path): string
{
    return '/' . ltrim($path, '/');
}

function newsHasPublishedFlag(PDO $conn): bool
{
    static $known = null;

    if ($known !== null) {
        return $known;
    }

    try {
        $column = $conn->query("SHOW COLUMNS FROM news LIKE 'is_published'");
        $known = $column !== false && $column->fetch() !== false;
    } catch (PDOException $e) {
        $known = false;
    }

    return $known;
}

function siteHasColumn(PDO $conn, string $column): bool
{
    static $columns = null;

    if (!preg_match('/^[a-z_]+$/', $column)) {
        return false;
    }

    if ($columns === null) {
        $columns = [];

        try {
            foreach ($conn->query('SHOW COLUMNS FROM site_settings') as $row) {
                if (isset($row['Field']) && is_string($row['Field'])) {
                    $columns[$row['Field']] = true;
                }
            }
        } catch (PDOException $e) {
            $columns = [];
        }
    }

    return isset($columns[$column]);
}

/**
 * Registered and corporate offices, or the older single address when neither is set.
 *
 * @param array<string, mixed> $settings
 * @return list<array{label: string, text: string}>
 */
function officeAddressBlocks(array $settings): array
{
    $registered = trim((string) ($settings['registered_office_address'] ?? ''));
    $corporate = trim((string) ($settings['corporate_office_address'] ?? ''));

    if ($registered !== '' || $corporate !== '') {
        $blocks = [];

        if ($registered !== '') {
            $blocks[] = ['label' => 'Registered Office:', 'text' => $registered];
        }

        if ($corporate !== '') {
            $blocks[] = ['label' => 'Corporate Office:', 'text' => $corporate];
        }

        return $blocks;
    }

    $fallback = trim((string) ($settings['address'] ?? ''));

    if ($fallback === '') {
        return [];
    }

    return [['label' => '', 'text' => $fallback]];
}

function storedUploadName(mixed $name): ?string
{
    if (!is_string($name) || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,180}$/', $name) || str_contains($name, '..')) {
        return null;
    }

    return $name;
}

function removeStoredFile(string $directory, mixed $name): void
{
    $safe = storedUploadName($name);
    $root = realpath($directory);

    if ($safe === null || $root === false) {
        return;
    }

    $path = $root . DIRECTORY_SEPARATOR . $safe;
    $real = realpath($path);

    if ($real === false || !is_file($real) || !str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
        return;
    }

    unlink($real);
}

function contentTableExists(PDO $conn, string $table): bool
{
    if (!in_array($table, ['company_documents', 'achievements'], true)) {
        return false;
    }

    try {
        $stmt = $conn->query('SHOW TABLES LIKE ' . $conn->quote($table));

        return $stmt !== false && $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

function documentTypeLabel(string $type): string
{
    return match ($type) {
        'incorporation' => 'Incorporation',
        'trade_license' => 'Trade License',
        'bin' => 'BIN',
        'tin' => 'TIN',
        default => 'Other',
    };
}

function formatStoredDate(mixed $value): string
{
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) || $value === '0000-00-00') {
        return '';
    }

    $time = strtotime($value);

    return $time === false ? '' : date('j F Y', $time);
}

/**
 * @return list<array<string, mixed>>
 */
function getCompanyDocuments(PDO $conn, bool $activeOnly = true): array
{
    if (!contentTableExists($conn, 'company_documents')) {
        return [];
    }

    try {
        $sql = 'SELECT * FROM company_documents';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY display_order ASC, id ASC';
        $rows = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * @return list<array<string, mixed>>
 */
function getAchievements(PDO $conn, bool $activeOnly = true): array
{
    if (!contentTableExists($conn, 'achievements')) {
        return [];
    }

    try {
        $sql = 'SELECT * FROM achievements';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY display_order ASC, id ASC';
        $rows = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    } catch (PDOException $e) {
        return [];
    }
}

function moveDisplayOrder(PDO $conn, string $table, int $id, string $direction): void
{
    if (!in_array($table, ['company_documents', 'achievements'], true) || $id < 1 || !in_array($direction, ['up', 'down'], true)) {
        return;
    }

    $rows = $conn->query('SELECT id FROM ' . $table . ' ORDER BY display_order ASC, id ASC')->fetchAll(PDO::FETCH_COLUMN);
    $ids = array_map(static fn (mixed $rowId): int => (int) $rowId, is_array($rows) ? $rows : []);
    $index = array_search($id, $ids, true);

    if ($index === false) {
        return;
    }

    $swap = $direction === 'up' ? $index - 1 : $index + 1;

    if (!isset($ids[$swap])) {
        return;
    }

    $current = $ids[$index];
    $ids[$index] = $ids[$swap];
    $ids[$swap] = $current;
    $stmt = $conn->prepare('UPDATE ' . $table . ' SET display_order = ? WHERE id = ?');

    foreach ($ids as $position => $rowId) {
        $stmt->execute([$position + 1, $rowId]);
    }
}

function publishedNewsSql(PDO $conn, string $alias = ''): string
{
    if (!newsHasPublishedFlag($conn)) {
        return '';
    }

    $column = $alias === '' ? 'is_published' : $alias . '.is_published';

    return ' AND ' . $column . ' = 1';
}


/**
 * Get team members
 * 
 * @param PDO $conn Database connection
 * @param bool $activeOnly Get only active members
 * @param int $limit Maximum number of members to return (0 for all)
 * @return array Array of team members
 */
function getTeamMembers($conn, $activeOnly = true, $limit = 0) {
    try {
        $query = "SELECT * FROM team_members";
        
        if ($activeOnly) {
            $query .= " WHERE is_active = 1";
        }
        
        $query .= " ORDER BY display_order ASC, name ASC";
        
        if ($limit > 0) {
            $query .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log error
        error_log("Error fetching team members: " . $e->getMessage());
        return [];
    }
}

/**
 * Get testimonials
 * 
 * @param PDO $conn Database connection
 * @param bool $activeOnly Get only active testimonials
 * @param int $limit Maximum number of testimonials to return (0 for all)
 * @return array Array of testimonials
 */
function getTestimonials($conn, $activeOnly = true, $limit = 0) {
    try {
        $query = "SELECT * FROM testimonials";
        
        if ($activeOnly) {
            $query .= " WHERE is_active = 1";
        }
        
        $query .= " ORDER BY display_order ASC, created_at DESC";
        
        if ($limit > 0) {
            $query .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching testimonials: " . $e->getMessage());
        return [];
    }
}