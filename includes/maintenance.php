<?php

if (!defined('INCLUDED')) {
    exit('Direct access not permitted');
}

function publicMaintenanceFile(): string
{
    $configured = getenv('AHMAD_MAINTENANCE_FILE');

    if (is_string($configured) && $configured !== '') {
        return $configured;
    }

    return dirname(__DIR__, 2) . '/ahmad-private/public-site-maintenance';
}

function publicSiteIsHidden(): bool
{
    $path = publicMaintenanceFile();

    if (!is_file($path)) {
        return false;
    }

    $value = file_get_contents($path);

    return is_string($value) && trim($value) === '1';
}
