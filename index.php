<?php
// Define the site as included to prevent direct access to page files
define('INCLUDED', true);
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/maintenance.php';

if (publicSiteIsHidden()) {
    $settings = getSiteSettings($conn);
    http_response_code(503);
    header('Retry-After: 3600');
    include 'pages/maintenance.php';
    exit;
}

// Get the requested page from URL or default to home
$page = isset($_GET['page']) ? $_GET['page'] : '';

// Default to 'home' if the page is empty or 'index'
if (empty($page) || $page === 'index') {
    $page = 'home';
}

// Basic security check to prevent directory traversal
$page = str_replace(['.', '/', '\\'], '', $page);

// Handle page parameter for news and career detail pages
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Map of allowed pages with their titles
$allowed_pages = [
    'home' => ['file' => 'pages/home.php', 'title' => ''],
    'about' => ['file' => 'pages/about.php', 'title' => 'About Us'],
    'services' => ['file' => 'pages/services.php', 'title' => 'Our Services'],
    'team' => ['file' => 'pages/team.php', 'title' => 'Our Team'],
    'news' => ['file' => 'pages/news.php', 'title' => 'News & Updates'],
    'news-detail' => ['file' => 'pages/news-detail.php', 'title' => 'News Details'],
    'careers' => ['file' => 'pages/careers.php', 'title' => 'Careers'],
    'career-detail' => ['file' => 'pages/career-detail.php', 'title' => 'Career Details'],
    'contact' => ['file' => 'pages/contact.php', 'title' => 'Contact Us'],
    'documents' => ['file' => 'pages/documents.php', 'title' => 'Documents'],
];

// Check if requested page exists
if (!isset($allowed_pages[$page])) {
    // Page not found - show 404
    header("HTTP/1.0 404 Not Found");
    $page = '404';
    $pageTitle = 'Page Not Found';
    $currentPage = '404';
} else {
    // Set page variables
    $pageTitle = $allowed_pages[$page]['title'];
    $currentPage = $page;
    
    // Special handling for detail pages that need dynamic titles
    if ($page === 'news-detail' && $id > 0) {
        // Get the news article title
        $stmt = $conn->prepare("SELECT title FROM news WHERE id = ?");
        $stmt->execute([$id]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($article) {
            $pageTitle = $article['title'] . ' - News';
        }
    } elseif ($page === 'career-detail' && $id > 0) {
        // Get the career position title
        $stmt = $conn->prepare("SELECT position FROM careers WHERE id = ?");
        $stmt->execute([$id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($job) {
            $pageTitle = $job['position'] . ' - Careers';
        }
    }
}

// Get site settings
$settings = getSiteSettings($conn);

// Include the header
include 'includes/header.php';

// Include the page content
if ($page === '404') {
    include 'pages/404.php';
} else {
    include $allowed_pages[$page]['file'];
}

// Include the footer
include 'includes/footer.php';
?>