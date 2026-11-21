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
    return publicFlagIsOne(publicMaintenanceFile(), false);
}

function publicFlagDirectory(): string
{
    $configured = getenv('AHMAD_MAINTENANCE_FILE');

    if (is_string($configured) && $configured !== '') {
        return dirname($configured);
    }

    return dirname(__DIR__, 2) . '/ahmad-private';
}

function contactFormEnabled(): bool
{
    return publicFlagIsOne(publicFlagDirectory() . '/public-site-contact-form', true);
}

function recaptchaRequired(): bool
{
    return publicFlagIsOne(publicFlagDirectory() . '/public-site-recaptcha', false);
}

/**
 * @return array{site: string, secret: string}
 */
function recaptchaKeys(): array
{
    $site = getenv('AHMAD_RECAPTCHA_SITE_KEY');
    $secret = getenv('AHMAD_RECAPTCHA_SECRET_KEY');

    if (is_string($site) && $site !== '' && is_string($secret) && $secret !== '') {
        return ['site' => $site, 'secret' => $secret];
    }

    $path = publicFlagDirectory() . '/public-site-recaptcha-keys';

    if (!is_file($path)) {
        return ['site' => '', 'secret' => ''];
    }

    $raw = file_get_contents($path);
    $decoded = is_string($raw) ? json_decode($raw, true) : null;

    if (!is_array($decoded)) {
        return ['site' => '', 'secret' => ''];
    }

    $fileSite = $decoded['site'] ?? '';
    $fileSecret = $decoded['secret'] ?? '';

    return [
        'site' => is_string($fileSite) ? $fileSite : '',
        'secret' => is_string($fileSecret) ? $fileSecret : '',
    ];
}

function recaptchaVerified(string $token, string $secret): bool
{
    if ($token === '' || $secret === '' || !function_exists('curl_init')) {
        return false;
    }

    $handle = curl_init('https://www.google.com/recaptcha/api/siteverify');

    if ($handle === false) {
        return false;
    }

    curl_setopt_array($handle, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'secret' => $secret,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
    ]);
    $body = curl_exec($handle);
    curl_close($handle);

    if (!is_string($body) || $body === '') {
        return false;
    }

    $decoded = json_decode($body, true);

    return is_array($decoded) && ($decoded['success'] ?? false) === true;
}

function publicFlagIsOne(string $path, bool $whenMissing): bool
{
    if (!is_file($path)) {
        return $whenMissing;
    }

    $value = file_get_contents($path);

    if (!is_string($value)) {
        return $whenMissing;
    }

    $value = trim($value);

    if ($value === '1') {
        return true;
    }

    if ($value === '0') {
        return false;
    }

    return $whenMissing;
}
