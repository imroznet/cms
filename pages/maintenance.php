<?php
if (!defined('INCLUDED')) {
    exit('Direct access not permitted');
}

$company = 'Ahmad Solution Ltd.';
if (isset($settings['company_name']) && is_string($settings['company_name']) && $settings['company_name'] !== '') {
    $company = $settings['company_name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($company); ?></title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; color: #333333; background: #f8f9fa; }
        main { max-width: 36rem; margin: 0 auto; padding: 4rem 1.25rem; }
        h1 { font-size: 1.75rem; margin: 0 0 1rem; }
        p { line-height: 1.6; margin: 0; }
    </style>
</head>
<body>
    <main>
        <h1><?php echo htmlspecialchars($company); ?></h1>
        <p>The site is unavailable right now because of maintenance. Please check back soon.</p>
    </main>
</body>
</html>
