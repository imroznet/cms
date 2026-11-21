<?php
require_once __DIR__ . '/../includes/security.php';
require_once '../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

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
$currentPage = 'settings';
$pageTitle = 'Site Settings';

// Initialize variables
$message = '';
$messageType = '';
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

// Get current settings
$settings = [];
$stmt = $conn->query("SELECT * FROM site_settings WHERE id = 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['_confirm_action'])) {
    $formType = $_POST['form_type'] ?? '';
    
    if ($formType === 'general_settings') {
        // Process general settings form
        $company_name = trim($_POST['company_name'] ?? '');
        $motto = trim($_POST['motto'] ?? '');
        $homepage_title = trim($_POST['homepage_title'] ?? '');
        $homepage_subtitle = trim($_POST['homepage_subtitle'] ?? '');
        $homepage_content = trim($_POST['homepage_content'] ?? '');
        
        // Validate form
        $errors = [];
        
        if (empty($company_name)) {
            $errors[] = "Company name is required";
        }
        
        // Handle logo upload
        $logo_image = $settings['logo_image'] ?? null; // Keep existing logo by default
        
        if (!empty($_FILES['logo_image']['name'])) {
            $upload_dir = '../assets/images/uploads/';
            $filename = time() . '_' . $_FILES['logo_image']['name'];
            $upload_path = $upload_dir . $filename;
            
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
            $ext = strtolower(pathinfo($_FILES['logo_image']['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                $errors[] = "Logo must be JPG, JPEG, PNG, GIF or SVG format";
            } elseif ($_FILES['logo_image']['size'] > 2000000) {
                $errors[] = "Logo size must be less than 2MB";
            } elseif (move_uploaded_file($_FILES['logo_image']['tmp_name'], $upload_path)) {
                // If upload successful, update logo name
                $logo_image = $filename;
                
                // Delete old logo if exists and it's not a default logo
                if ($settings['logo_image'] && file_exists($upload_dir . $settings['logo_image'])) {
                    unlink($upload_dir . $settings['logo_image']);
                }
            } else {
                $errors[] = "Error uploading logo";
            }
        }
        
        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("
                    UPDATE site_settings SET
                    company_name = ?,
                    motto = ?,
                    logo_image = ?,
                    homepage_title = ?,
                    homepage_subtitle = ?,
                    homepage_content = ?
                    WHERE id = 1
                ");
                
                $result = $stmt->execute([
                    $company_name,
                    $motto,
                    $logo_image,
                    $homepage_title,
                    $homepage_subtitle,
                    $homepage_content
                ]);
                
                if ($result) {
                    $message = "General settings updated successfully!";
                    $messageType = "success";
                    
                    // Refresh settings
                    $stmt = $conn->query("SELECT * FROM site_settings WHERE id = 1");
                    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $message = "Error updating general settings";
                    $messageType = "error";
                }
            } catch (PDOException $e) {
                $message = "The operation failed. Please contact your administrator.";
                $messageType = "error";
            }
        } else {
            $message = "Please correct the following errors: <ul><li>" . implode("</li><li>", $errors) . "</li></ul>";
            $messageType = "error";
        }
        
        $activeTab = 'general';
    } elseif ($formType === 'about_settings') {
        // Process about settings form
        $about_content = $_POST['about_content'] ?? '';
        
        try {
            $stmt = $conn->prepare("UPDATE site_settings SET about_content = ? WHERE id = 1");
            $result = $stmt->execute([$about_content]);
            
            if ($result) {
                $message = "About page content updated successfully!";
                $messageType = "success";
                
                // Refresh settings
                $stmt = $conn->query("SELECT * FROM site_settings WHERE id = 1");
                $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $message = "Error updating about page content";
                $messageType = "error";
            }
        } catch (PDOException $e) {
            $message = "The operation failed. Please contact your administrator.";
            $messageType = "error";
        }
        
        $activeTab = 'about';
    } elseif ($formType === 'contact_settings') {
        // Process contact settings form
        $address = trim($_POST['address'] ?? '');
        $registeredOffice = trim($_POST['registered_office_address'] ?? '');
        $corporateOffice = trim($_POST['corporate_office_address'] ?? '');
        $contact_email = trim($_POST['contact_email'] ?? '');
        $contact_phone = trim($_POST['contact_phone'] ?? '');
        $map_embed_url = trim($_POST['map_embed_url'] ?? '');
        $map_latitude = trim($_POST['map_latitude'] ?? '');
        $map_longitude = trim($_POST['map_longitude'] ?? '');
        
        // Validate form
        $errors = [];
        
        if (empty($contact_email)) {
            $errors[] = "Contact email is required";
        } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid contact email format";
        }
        
        if (empty($errors)) {
            try {
                $officeColumns = siteHasColumn($conn, 'registered_office_address') && siteHasColumn($conn, 'corporate_office_address');
                if ($officeColumns) {
                    $stmt = $conn->prepare("
                        UPDATE site_settings SET
                        address = ?,
                        registered_office_address = ?,
                        corporate_office_address = ?,
                        contact_email = ?,
                        contact_phone = ?,
                        map_embed_url = ?,
                        map_latitude = ?,
                        map_longitude = ?
                        WHERE id = 1
                    ");
                    $result = $stmt->execute([
                        $address,
                        $registeredOffice === '' ? null : $registeredOffice,
                        $corporateOffice === '' ? null : $corporateOffice,
                        $contact_email,
                        $contact_phone,
                        $map_embed_url,
                        $map_latitude,
                        $map_longitude
                    ]);
                } else {
                    $stmt = $conn->prepare("
                        UPDATE site_settings SET
                        address = ?,
                        contact_email = ?,
                        contact_phone = ?,
                        map_embed_url = ?,
                        map_latitude = ?,
                        map_longitude = ?
                        WHERE id = 1
                    ");
                    $result = $stmt->execute([
                        $address,
                        $contact_email,
                        $contact_phone,
                        $map_embed_url,
                        $map_latitude,
                        $map_longitude
                    ]);
                }
                
                if ($result) {
                    $message = "Contact information updated successfully!";
                    $messageType = "success";
                    
                    // Refresh settings
                    $stmt = $conn->query("SELECT * FROM site_settings WHERE id = 1");
                    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $message = "Error updating contact information";
                    $messageType = "error";
                }
            } catch (PDOException $e) {
                $message = "The operation failed. Please contact your administrator.";
                $messageType = "error";
            }
        } else {
            $message = "Please correct the following errors: <ul><li>" . implode("</li><li>", $errors) . "</li></ul>";
            $messageType = "error";
        }
        
        $activeTab = 'contact';
    } elseif ($formType === 'email_settings') {
        // Process email settings form
        $admin_email = trim($_POST['admin_email'] ?? '');
        $smtp_host = trim($_POST['smtp_host'] ?? '');
        $smtp_port = (int)($_POST['smtp_port'] ?? 0);
        $smtp_user = trim($_POST['smtp_user'] ?? '');
        $smtp_pass = trim($_POST['smtp_pass'] ?? '');
        
        // Validate form
        $errors = [];
        
        if (!empty($admin_email) && !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid admin email format";
        }
        
        if (!empty($smtp_host) && empty($smtp_port)) {
            $errors[] = "SMTP port is required when SMTP host is provided";
        }
        
        if (empty($errors)) {
            try {
                // Only update SMTP password if a new one is provided
                if (!empty($smtp_pass)) {
                    $stmt = $conn->prepare("
                        UPDATE site_settings SET
                        admin_email = ?,
                        smtp_host = ?,
                        smtp_port = ?,
                        smtp_user = ?,
                        smtp_pass = ?
                        WHERE id = 1
                    ");
                    
                    $result = $stmt->execute([
                        $admin_email,
                        $smtp_host,
                        $smtp_port,
                        $smtp_user,
                        $smtp_pass
                    ]);
                } else {
                    $stmt = $conn->prepare("
                        UPDATE site_settings SET
                        admin_email = ?,
                        smtp_host = ?,
                        smtp_port = ?,
                        smtp_user = ?
                        WHERE id = 1
                    ");
                    
                    $result = $stmt->execute([
                        $admin_email,
                        $smtp_host,
                        $smtp_port,
                        $smtp_user
                    ]);
                }
                
                if ($result) {
                    $message = "Email settings updated successfully!";
                    $messageType = "success";
                    
                    // Refresh settings
                    $stmt = $conn->query("SELECT * FROM site_settings WHERE id = 1");
                    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $message = "Error updating email settings";
                    $messageType = "error";
                }
            } catch (PDOException $e) {
                $message = "The operation failed. Please contact your administrator.";
                $messageType = "error";
            }
        } else {
            $message = "Please correct the following errors: <ul><li>" . implode("</li><li>", $errors) . "</li></ul>";
            $messageType = "error";
        }
        
        $activeTab = 'email';
    }
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
        
        <h1>Site Settings</h1>
        
        <div></div> <!-- Empty div for flex spacing -->
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Settings Tabs -->
        <div class="tabs" style="margin-bottom: 20px;">
            <ul class="tab-list" style="display: flex; list-style: none; padding: 0; margin: 0; border-bottom: 1px solid #dee2e6;">
                <li>
                    <a href="?tab=general" class="tab-link <?php echo $activeTab === 'general' ? 'active' : ''; ?>" style="display: block; padding: 10px 15px; text-decoration: none; color: <?php echo $activeTab === 'general' ? '#007bff' : '#6c757d'; ?>; font-weight: <?php echo $activeTab === 'general' ? 'bold' : 'normal'; ?>; border-bottom: <?php echo $activeTab === 'general' ? '2px solid #007bff' : 'none'; ?>;">
                        <i class="fas fa-cog"></i> General
                    </a>
                </li>
                <li>
                    <a href="?tab=about" class="tab-link <?php echo $activeTab === 'about' ? 'active' : ''; ?>" style="display: block; padding: 10px 15px; text-decoration: none; color: <?php echo $activeTab === 'about' ? '#007bff' : '#6c757d'; ?>; font-weight: <?php echo $activeTab === 'about' ? 'bold' : 'normal'; ?>; border-bottom: <?php echo $activeTab === 'about' ? '2px solid #007bff' : 'none'; ?>;">
                        <i class="fas fa-info-circle"></i> About Page
                    </a>
                </li>
                <li>
                    <a href="?tab=contact" class="tab-link <?php echo $activeTab === 'contact' ? 'active' : ''; ?>" style="display: block; padding: 10px 15px; text-decoration: none; color: <?php echo $activeTab === 'contact' ? '#007bff' : '#6c757d'; ?>; font-weight: <?php echo $activeTab === 'contact' ? 'bold' : 'normal'; ?>; border-bottom: <?php echo $activeTab === 'contact' ? '2px solid #007bff' : 'none'; ?>;">
                        <i class="fas fa-address-card"></i> Contact Info
                    </a>
                </li>
                <li>
                    <a href="?tab=email" class="tab-link <?php echo $activeTab === 'email' ? 'active' : ''; ?>" style="display: block; padding: 10px 15px; text-decoration: none; color: <?php echo $activeTab === 'email' ? '#007bff' : '#6c757d'; ?>; font-weight: <?php echo $activeTab === 'email' ? 'bold' : 'normal'; ?>; border-bottom: <?php echo $activeTab === 'email' ? '2px solid #007bff' : 'none'; ?>;">
                        <i class="fas fa-envelope"></i> Email Settings
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- General Settings Tab -->
        <?php if ($activeTab === 'general'): ?>
            <div class="panel">
                <h3>General Settings</h3>
                
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="form_type" value="general_settings">
                    
                    <div class="form-group">
                        <label for="company_name">Company Name *</label>
                        <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="motto">Company Motto</label>
                        <input type="text" id="motto" name="motto" value="<?php echo htmlspecialchars($settings['motto'] ?? ''); ?>">
                        <p class="note">A short tagline that appears below your company name</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="logo_image">Company Logo</label>
                        <input type="file" id="logo_image" name="logo_image" accept="image/*">
                        <p class="note">Recommended size: 200x50px. Supported formats: PNG, JPG, GIF, SVG</p>
                        
                        <?php if (!empty($settings['logo_image'])): ?>
                            <div style="margin-top: 10px;">
                                <p>Current logo:</p>
                                <img src="../assets/images/uploads/<?php echo htmlspecialchars($settings['logo_image']); ?>" alt="Current Logo" style="max-height: 50px; margin-top: 10px; border: 1px solid #ddd; padding: 5px;">
                                <p class="note">Leave the field empty to keep the current logo</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-section" style="margin-top: 30px;">
                        <h4>Homepage Content</h4>
                        
                        <div class="form-group">
                            <label for="homepage_title">Homepage Title</label>
                            <input type="text" id="homepage_title" name="homepage_title" value="<?php echo htmlspecialchars($settings['homepage_title'] ?? ''); ?>">
                            <p class="note">Main heading displayed on the homepage hero section</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="homepage_subtitle">Homepage Subtitle</label>
                            <input type="text" id="homepage_subtitle" name="homepage_subtitle" value="<?php echo htmlspecialchars($settings['homepage_subtitle'] ?? ''); ?>">
                            <p class="note">Subheading displayed below the main title on the homepage</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="homepage_content">Homepage About Section</label>
                            <textarea id="homepage_content" name="homepage_content" rows="6"><?php echo htmlspecialchars($settings['homepage_content'] ?? ''); ?></textarea>
                            <p class="note">Text displayed in the about section of the homepage</p>
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary">Save General Settings</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- About Page Tab -->
        <?php if ($activeTab === 'about'): ?>
            <div class="panel">
                <h3>About Page Content</h3>
                
                <form action="" method="post">
                    <input type="hidden" name="form_type" value="about_settings">
                    
                    <div class="form-group">
                        <label for="about_content">About Page Content</label>
                        <textarea id="about_content" name="about_content" rows="15"><?php echo htmlspecialchars($settings['about_content'] ?? ''); ?></textarea>
                        <p class="note">
                            This content will be displayed on the About page.<br>
                            You can use basic HTML tags for formatting.<br>
                            Use &lt;h2&gt; for headings, &lt;p&gt; for paragraphs, &lt;ul&gt; and &lt;li&gt; for lists.
                        </p>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary">Save About Content</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Contact Information Tab -->
        <?php if ($activeTab === 'contact'): ?>
            <div class="panel">
                <h3>Contact Information</h3>
                
                <form action="" method="post">
                    <input type="hidden" name="form_type" value="contact_settings">
                    
                    <div class="form-group">
                        <label for="address">Company Address</label>
                        <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($settings['address'] ?? ''); ?></textarea>
                        <p class="note">Used on the public site only when both offices below are blank.</p>
                    </div>

                    <?php if (siteHasColumn($conn, 'registered_office_address') && siteHasColumn($conn, 'corporate_office_address')): ?>
                    <div class="form-group">
                        <label for="registered_office_address">Registered Office</label>
                        <textarea id="registered_office_address" name="registered_office_address" rows="3"><?php echo htmlspecialchars($settings['registered_office_address'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="corporate_office_address">Corporate Office</label>
                        <textarea id="corporate_office_address" name="corporate_office_address" rows="3"><?php echo htmlspecialchars($settings['corporate_office_address'] ?? ''); ?></textarea>
                        <p class="note">Leave an office blank until its address is entered. Nothing is filled in for you.</p>
                    </div>
                    <?php else: ?>
                    <p class="note">Import deploy/upgrade-company-addresses.sql before editing the registered and corporate offices.</p>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="contact_email">Contact Email *</label>
                        <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>" required>
                        <p class="note">This email will be displayed on the website for public contact.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_phone">Contact Phone</label>
                        <input type="text" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>">
                    </div>
                    
                    <h4>Map Settings</h4>
                    
                    <div class="form-group">
                        <label for="map_embed_url">Google Maps Embed URL</label>
                        <input type="text" id="map_embed_url" name="map_embed_url" value="<?php echo htmlspecialchars($settings['map_embed_url'] ?? ''); ?>">
                        <p class="note">Enter the Google Maps embed URL (e.g., https://www.google.com/maps/embed?pb=...)</p>
                        <p class="note">To get this: Go to Google Maps, find your location, click "Share", select "Embed a map", copy the URL from the iframe src attribute</p>
                    </div>
                    
                    <div class="form-group" style="display: flex; gap: 20px;">
                        <div style="flex: 1;">
                            <label for="map_latitude">Latitude (optional)</label>
                            <input type="text" id="map_latitude" name="map_latitude" value="<?php echo htmlspecialchars($settings['map_latitude'] ?? ''); ?>">
                        </div>
                        <div style="flex: 1;">
                            <label for="map_longitude">Longitude (optional)</label>
                            <input type="text" id="map_longitude" name="map_longitude" value="<?php echo htmlspecialchars($settings['map_longitude'] ?? ''); ?>">
                        </div>
                    </div>
                    <p class="note">Latitude and longitude are only needed if you're not using the Google Maps Embed URL.</p>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary">Save Contact Information</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Email Settings Tab -->
        <?php if ($activeTab === 'email'): ?>
            <div class="panel">
                <h3>Email Settings</h3>
                
                <form action="" method="post">
                    <input type="hidden" name="form_type" value="email_settings">
                    
                    <div class="form-group">
                        <label for="admin_email">Admin Email</label>
                        <input type="email" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($settings['admin_email'] ?? ''); ?>">
                        <p class="note">Email address where contact form submissions and notifications will be sent.</p>
                    </div>
                    
                    <div class="form-section" style="margin-top: 30px;">
                        <h4>SMTP Configuration (Optional)</h4>
                        <p class="note">Configure SMTP to send emails through an external email service. Leave blank to use PHP's mail() function.</p>
                        
                        <div class="form-group">
                            <label for="smtp_host">SMTP Host</label>
                            <input type="text" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>">
                            <p class="note">e.g., smtp.gmail.com, smtp.office365.com</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_port">SMTP Port</label>
                            <input type="number" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? ''); ?>">
                            <p class="note">Common ports: 25, 465 (SSL), 587 (TLS)</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_user">SMTP Username</label>
                            <input type="text" id="smtp_user" name="smtp_user" value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>">
                            <p class="note">Usually your full email address</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="smtp_pass">SMTP Password</label>
                            <input type="password" id="smtp_pass" name="smtp_pass" value="">
                            <p class="note">Leave blank to keep current password</p>
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary">Save Email Settings</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>