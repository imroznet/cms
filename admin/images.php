<?php
require_once __DIR__ . '/../includes/security.php';
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Check if user has permission to manage settings
if (!in_array('manage_settings', $_SESSION['permissions'])) {
    header("Location: dashboard.php");
    exit;
}

// Set page-specific variables
define('INCLUDED', true);
$currentPage = 'images';
$pageTitle = 'Image Management';

// Initialize variables
$message = '';
$messageType = '';
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'favicon';

// Define image categories and their properties
$imageCategories = [
    'favicon' => [
        'title' => 'Favicon',
        'description' => 'The small icon shown in browser tabs (recommended size: 32x32px or 64x64px)',
        'path' => '../favicon.ico',
        'allowed_types' => ['ico', 'png'],
        'max_size' => 1048576, // 1MB
        'recommended_size' => '32x32 or 64x64 pixels'
    ],
    'home' => [
        'title' => 'Homepage Images',
        'description' => 'Background images used on the homepage',
        'subcategories' => [
            'hero' => [
                'title' => 'Hero Background',
                'path' => '../assets/images/hero-bg.jpg',
                'allowed_types' => ['jpg', 'jpeg', 'png'],
                'max_size' => 5242880, // 5MB
                'recommended_size' => '1920x1080 pixels'
            ],
            'cta' => [
                'title' => 'Call-to-Action Background',
                'path' => '../assets/images/cta-bg.jpg',
                'allowed_types' => ['jpg', 'jpeg', 'png'],
                'max_size' => 5242880, // 5MB
                'recommended_size' => '1920x800 pixels'
            ],
            'about_home' => [
                'title' => 'About Section Image',
                'path' => '../assets/images/about-home.jpg',
                'allowed_types' => ['jpg', 'jpeg', 'png'],
                'max_size' => 5242880, // 5MB
                'recommended_size' => '600x400 pixels'
            ]
        ]
    ],
    'pages' => [
        'title' => 'Page Header Images',
        'description' => 'Background images for page headers',
        'subcategories' => [
            'about' => [
                'title' => 'About Page Header',
                'path' => '../assets/images/about-header.jpg',
                'allowed_types' => ['jpg', 'jpeg', 'png'],
                'max_size' => 5242880, // 5MB
                'recommended_size' => '1920x400 pixels'
            ],
            'services' => [
                'title' => 'Services Page Header',
                'path' => '../assets/images/services-header.jpg',
                'allowed_types' => ['jpg', 'jpeg', 'png'],
                'max_size' => 5242880, // 5MB
                'recommended_size' => '1920x400 pixels'
            ],
            'contact' => [
                'title' => 'Contact Page Header',
                'path' => '../assets/images/contact-header.jpg',
                'allowed_types' => ['jpg', 'jpeg', 'png'],
                'max_size' => 5242880, // 5MB
                'recommended_size' => '1920x400 pixels'
            ],
            'news' => [
                'title' => 'News Page Header',
                'path' => '../assets/images/news-header.jpg',
                'allowed_types' => ['jpg', 'jpeg', 'png'],
                'max_size' => 5242880, // 5MB
                'recommended_size' => '1920x400 pixels'
            ],
            'careers' => [
                'title' => 'Careers Page Header',
                'path' => '../assets/images/careers-header.jpg',
                'allowed_types' => ['jpg', 'jpeg', 'png'],
                'max_size' => 5242880, // 5MB
                'recommended_size' => '1920x400 pixels'
            ]
        ]
    ],
    'placeholders' => [
        'title' => 'Placeholder Images',
        'description' => 'Default images used when content images are not available',
        'subcategories' => [
            'team' => [
                'title' => 'Team Member Placeholder',
                'path' => '../assets/images/team-placeholder.jpg',
                'allowed_types' => ['jpg', 'jpeg', 'png'],
                'max_size' => 2097152, // 2MB
                'recommended_size' => '300x300 pixels'
            ],
            'news' => [
                'title' => 'News Placeholder',
                'path' => '../assets/images/news-placeholder.jpg',
                'allowed_types' => ['jpg', 'jpeg', 'png'],
                'max_size' => 2097152, // 2MB
                'recommended_size' => '800x450 pixels'
            ],
            'testimonial' => [
                'title' => 'Testimonial Placeholder',
                'path' => '../assets/images/testimonial-placeholder.jpg',
                'allowed_types' => ['jpg', 'jpeg', 'png'],
                'max_size' => 2097152, // 2MB
                'recommended_size' => '100x100 pixels'
            ]
        ]
    ]
];

// Process image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
    $category = $_POST['category'] ?? '';
    $subcategory = $_POST['subcategory'] ?? '';
    
    // Determine image properties based on category and subcategory
    $imageConfig = null;
    if (!empty($subcategory)) {
        $imageConfig = $imageCategories[$category]['subcategories'][$subcategory] ?? null;
    } else {
        $imageConfig = $imageCategories[$category] ?? null;
    }
    
    if ($imageConfig && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $fileName = $file['name'];
        $fileTmpPath = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileType = $file['type'];
        
        // Extract file extension
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Validate file type
        if (!in_array($fileExtension, $imageConfig['allowed_types'])) {
            $message = "Error: Only " . implode(', ', $imageConfig['allowed_types']) . " files are allowed.";
            $messageType = "error";
        } 
        // Validate file size
        elseif ($fileSize > $imageConfig['max_size']) {
            $message = "Error: File size is too large. Maximum size allowed is " . ($imageConfig['max_size'] / 1048576) . " MB.";
            $messageType = "error";
        } 
        else {
            // Get the destination path
            $uploadPath = $imageConfig['path'];
            $uploadDirectory = dirname($uploadPath);
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0755, true);
            }
            
            // Attempt to move the uploaded file
            if (move_uploaded_file($fileTmpPath, $uploadPath)) {
                $message = "Image uploaded successfully.";
                $messageType = "success";
            } else {
                $message = "Error: Failed to upload image. Please check directory permissions.";
                $messageType = "error";
            }
        }
    } else {
        $message = "Error: Please select a valid image to upload.";
        $messageType = "error";
    }
    
    // Maintain the current tab
    $activeTab = $category;
}

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<div class="main-content">
    <!-- Top Navigation -->
    <div class="top-nav">
        <button class="menu-toggle" id="menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <h1>Image Management</h1>
        
        <div></div> <!-- Empty div for flex spacing -->
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="panel">
            <h3>Manage Website Images</h3>
            <p>Upload and manage images used throughout your website. Select a category from the tabs below.</p>
        </div>
        
        <!-- Image Category Tabs -->
        <div class="tabs" style="margin-bottom: 20px;">
            <ul class="tab-list" style="display: flex; list-style: none; padding: 0; margin: 0; border-bottom: 1px solid #dee2e6; overflow-x: auto;">
                <?php foreach ($imageCategories as $categoryKey => $category): ?>
                    <li>
                        <a href="?tab=<?php echo $categoryKey; ?>" class="tab-link <?php echo $activeTab === $categoryKey ? 'active' : ''; ?>" style="display: block; padding: 10px 15px; text-decoration: none; color: <?php echo $activeTab === $categoryKey ? '#007bff' : '#6c757d'; ?>; font-weight: <?php echo $activeTab === $categoryKey ? 'bold' : 'normal'; ?>; border-bottom: <?php echo $activeTab === $categoryKey ? '2px solid #007bff' : 'none'; ?>; white-space: nowrap;">
                            <i class="fas fa-<?php echo ($categoryKey === 'favicon') ? 'bookmark' : (($categoryKey === 'home') ? 'home' : (($categoryKey === 'pages') ? 'file' : 'image')); ?>"></i> <?php echo $category['title']; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- Image Category Content -->
        <?php foreach ($imageCategories as $categoryKey => $category): ?>
            <?php if ($activeTab === $categoryKey): ?>
                <div class="panel">
                    <h3><?php echo $category['title']; ?></h3>
                    <?php if (isset($category['description'])): ?>
                        <p><?php echo $category['description']; ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($category['subcategories'])): ?>
                        <!-- Category has subcategories -->
                        <?php foreach ($category['subcategories'] as $subKey => $subcategory): ?>
                            <div class="image-item" style="margin-bottom: 40px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
                                <h4><?php echo $subcategory['title']; ?></h4>
                                
                                <div style="display: flex; flex-wrap: wrap; gap: 20px; margin: 20px 0;">
                                    <div style="flex: 1; min-width: 300px;">
                                        <!-- Current Image -->
                                        <div class="current-image">
                                            <h5>Current Image</h5>
                                            <?php if (file_exists($subcategory['path'])): ?>
                                                <img src="<?php echo str_replace('../', '../', $subcategory['path']) . '?v=' . time(); ?>" alt="<?php echo $subcategory['title']; ?>" style="max-width: 100%; max-height: 200px; border: 1px solid #ddd; margin-top: 10px;">
                                                <p style="margin-top: 5px; font-size: 0.9em; color: #6c757d;">
                                                    Last modified: <?php echo date("F j, Y, g:i a", filemtime($subcategory['path'])); ?>
                                                </p>
                                            <?php else: ?>
                                                <div style="width: 100%; height: 200px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd; margin-top: 10px;">
                                                    <span style="color: #6c757d;">No image uploaded</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div style="flex: 1; min-width: 300px;">
                                        <!-- Upload Form -->
                                        <form action="" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="category" value="<?php echo $categoryKey; ?>">
                                            <input type="hidden" name="subcategory" value="<?php echo $subKey; ?>">
                                            
                                            <div class="form-group">
                                                <label for="image_<?php echo $categoryKey; ?>_<?php echo $subKey; ?>">Upload New Image</label>
                                                <input type="file" id="image_<?php echo $categoryKey; ?>_<?php echo $subKey; ?>" name="image" accept="<?php echo '.'.implode(',.', $subcategory['allowed_types']); ?>" required>
                                                <p class="note">
                                                    Recommended size: <?php echo $subcategory['recommended_size']; ?><br>
                                                    Allowed formats: <?php echo strtoupper(implode(', ', $subcategory['allowed_types'])); ?><br>
                                                    Maximum size: <?php echo ($subcategory['max_size'] / 1048576); ?> MB
                                                </p>
                                            </div>
                                            
                                            <button type="submit" name="upload_image" class="btn btn-primary">Upload Image</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Category doesn't have subcategories (like favicon) -->
                        <div class="image-item" style="margin-bottom: 40px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
                            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin: 20px 0;">
                                <div style="flex: 1; min-width: 300px;">
                                    <!-- Current Image -->
                                    <div class="current-image">
                                        <h5>Current <?php echo $category['title']; ?></h5>
                                        <?php if (file_exists($category['path'])): ?>
                                            <img src="<?php echo str_replace('../', '../', $category['path']) . '?v=' . time(); ?>" alt="<?php echo $category['title']; ?>" style="max-width: 100px; max-height: 100px; border: 1px solid #ddd; margin-top: 10px;">
                                            <p style="margin-top: 5px; font-size: 0.9em; color: #6c757d;">
                                                Last modified: <?php echo date("F j, Y, g:i a", filemtime($category['path'])); ?>
                                            </p>
                                        <?php else: ?>
                                            <div style="width: 100px; height: 100px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd; margin-top: 10px;">
                                                <span style="color: #6c757d;">Not set</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div style="flex: 1; min-width: 300px;">
                                    <!-- Upload Form -->
                                    <form action="" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="category" value="<?php echo $categoryKey; ?>">
                                        
                                        <div class="form-group">
                                            <label for="image_<?php echo $categoryKey; ?>">Upload New <?php echo $category['title']; ?></label>
                                            <input type="file" id="image_<?php echo $categoryKey; ?>" name="image" accept="<?php echo '.'.implode(',.', $category['allowed_types']); ?>" required>
                                            <p class="note">
                                                Recommended size: <?php echo $category['recommended_size']; ?><br>
                                                Allowed formats: <?php echo strtoupper(implode(', ', $category['allowed_types'])); ?><br>
                                                Maximum size: <?php echo ($category['max_size'] / 1048576); ?> MB
                                            </p>
                                        </div>
                                        
                                        <button type="submit" name="upload_image" class="btn btn-primary">Upload <?php echo $category['title']; ?></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>