<?php
if (!defined('INCLUDED')) {
    exit('Direct access not permitted');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Admin Panel</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Include Sidebar -->
        <?php include 'includes/sidebar.php'; ?>