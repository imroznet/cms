<?php
if (!defined('INCLUDED')) {
    exit('Direct access not permitted');
}

// Get site settings
$settings = getSiteSettings($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="/">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) && !empty($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo htmlspecialchars($settings['company_name'] ?? 'Company Website'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header Styles */
        header {
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: relative;
            z-index: 100;
        }
        
        .header-top {
            background-color: #007bff;
            color: white;
            padding: 10px 0;
            font-size: 14px;
        }
        
        .header-top .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .contact-info {
            display: flex;
            gap: 20px;
        }
        
        .contact-info a {
            color: white;
            text-decoration: none;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
        }
        
        .social-links a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .social-links a i {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            line-height: 1;
        }
        
        .main-header {
            padding: 20px 0;
        }
        
        .main-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            max-height: 50px;
            margin-right: 10px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        /* Navigation */
        .main-nav ul {
            list-style: none;
            display: flex;
            gap: 20px;
        }
        
        .main-nav a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 5px 0;
            position: relative;
            transition: color 0.3s;
        }
        
        .main-nav a:hover, .main-nav a.active {
            color: #007bff;
        }
        
        .main-nav a.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #007bff;
        }
        
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .header-top .container {
                flex-direction: column;
                gap: 10px;
            }
            
            .main-nav {
                display: none;
                width: 100%;
            }
            
            .main-nav.active {
                display: block;
            }
            
            .main-nav ul {
                flex-direction: column;
                gap: 10px;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            .main-header .container {
                flex-wrap: wrap;
            }
        }
        
        /* Sections */
        .section {
            padding: 60px 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .section-title h2 {
            font-size: 32px;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }
        
        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 2px;
            background-color: #007bff;
        }
        
        /* Services Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .service-card {
            text-align: center;
            padding: 30px 20px;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            background: white;
        }
        
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .service-card i {
            font-size: 48px;
            color: #007bff;
            margin-bottom: 20px;
        }
        
        .service-card h3 {
            margin-bottom: 15px;
        }
        
        /* News Grid */
        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .news-card {
            background: white;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .news-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .news-card-content {
            padding: 20px;
        }
        
        .news-date {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        /* Statistics */
        .statistics {
            background-color: #f8f9fa;
            padding: 60px 0;
        }
        
        .statistics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 30px;
            text-align: center;
        }
        
        .statistic {
            padding: 20px;
        }
        
        .statistic i {
            font-size: 36px;
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .statistic .number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .statistic .title {
            color: #6c757d;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: inherit;
        }
        
        .form-group textarea {
            height: 150px;
        }
        
        .btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        /* Contact Section */
        .contact-section {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
        }
        
        .contact-info-item {
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
        }
        
        .contact-info-item i {
            font-size: 20px;
            color: #007bff;
            margin-right: 15px;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .contact-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <?php if (!empty($settings['contact_email']) || !empty($settings['contact_phone'])): ?>
        <div class="header-top">
            <div class="container">
                <div class="contact-info">
                    <?php if (!empty($settings['contact_email'])): ?>
                    <a href="mailto:<?php echo htmlspecialchars($settings['contact_email']); ?>">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($settings['contact_email']); ?>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($settings['contact_phone'])): ?>
                    <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $settings['contact_phone']); ?>">
                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($settings['contact_phone']); ?>
                    </a>
                    <?php endif; ?>
                </div>
                
                <?php
                $socialLinks = getSocialLinks($conn);
                if (count($socialLinks) > 0):
                ?>
                <div class="social-links">
                    <?php foreach ($socialLinks as $social): ?>
                    <a href="<?php echo htmlspecialchars($social['url']); ?>" target="_blank">
                        <i class="fab fa-<?php echo htmlspecialchars($social['icon']); ?>"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="main-header">
            <div class="container">
                <div class="logo">
                    <?php if (!empty($settings['logo_image'])): ?>
                    <img src="assets/images/uploads/<?php echo htmlspecialchars($settings['logo_image']); ?>" alt="<?php echo htmlspecialchars($settings['company_name'] ?? 'Company Logo'); ?>">
                    <?php endif; ?>
                    <div class="company-name"><?php echo htmlspecialchars($settings['company_name'] ?? 'Company Name'); ?></div>
                </div>
                
                <button class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                
<nav class="main-nav">
    <ul>
        <li><a href="." class="<?php echo $currentPage === 'home' ? 'active' : ''; ?>">Home</a></li>
        <li><a href="about" class="<?php echo $currentPage === 'about' ? 'active' : ''; ?>">About</a></li>
        <li><a href="services" class="<?php echo $currentPage === 'services' ? 'active' : ''; ?>">Services</a></li>
        <li><a href="team" class="<?php echo $currentPage === 'team' ? 'active' : ''; ?>">Our Team</a></li>
        <li><a href="news" class="<?php echo $currentPage === 'news' ? 'active' : ''; ?>">News</a></li>
        <li><a href="careers" class="<?php echo $currentPage === 'careers' ? 'active' : ''; ?>">Careers</a></li>
        <li><a href="https://blog.ahmad.com.bd" target="_blank">Blog</a></li>
        <li><a href="contact" class="<?php echo $currentPage === 'contact' ? 'active' : ''; ?>">Contact</a></li>
    </ul>
</nav>
            </div>
        </div>
    </header>