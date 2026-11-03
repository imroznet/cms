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
$currentPage = 'social';
$pageTitle = 'Manage Social Links';

// Get existing social links
$stmt = $conn->query("SELECT * FROM social_links ORDER BY id ASC");
$socialLinks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables
$message = '';
$messageType = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$editLink = null;

// Handle social link deletion
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM social_links WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            $message = "Social link deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error deleting social link";
            $messageType = "error";
        }
    } catch (PDOException $e) {
        $message = "The operation failed. Please contact your administrator.";
        $messageType = "error";
    }
    
    // Refresh social links list
    $stmt = $conn->query("SELECT * FROM social_links ORDER BY id ASC");
    $socialLinks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $action = 'list'; // Return to list view
}

// Get social link for editing
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT * FROM social_links WHERE id = ?");
    $stmt->execute([$id]);
    $editLink = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$editLink) {
        $message = "Social link not found";
        $messageType = "error";
        $action = 'list';
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['_confirm_action'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $platform = trim($_POST['platform'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    
    // Validate form
    $errors = [];
    
    if (empty($platform)) {
        $errors[] = "Platform name is required";
    }
    
    if (empty($url)) {
        $errors[] = "URL is required";
    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
        $errors[] = "Please enter a valid URL";
    }
    
    if (empty($icon)) {
        $errors[] = "Icon class is required";
    }
    
    if (empty($errors)) {
        try {
            if ($id > 0) {
                // Update existing social link
                $stmt = $conn->prepare("UPDATE social_links SET platform = ?, url = ?, icon = ? WHERE id = ?");
                $result = $stmt->execute([$platform, $url, $icon, $id]);
                
                if ($result) {
                    $message = "Social link updated successfully!";
                    $messageType = "success";
                    $action = 'list';
                } else {
                    $message = "Error updating social link";
                    $messageType = "error";
                }
            } else {
                // Add new social link
                $stmt = $conn->prepare("INSERT INTO social_links (platform, url, icon) VALUES (?, ?, ?)");
                $result = $stmt->execute([$platform, $url, $icon]);
                
                if ($result) {
                    $message = "Social link added successfully!";
                    $messageType = "success";
                    $action = 'list';
                } else {
                    $message = "Error adding social link";
                    $messageType = "error";
                }
            }
            
            // Refresh social links list
            $stmt = $conn->query("SELECT * FROM social_links ORDER BY id ASC");
            $socialLinks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $message = "The operation failed. Please contact your administrator.";
            $messageType = "error";
        }
    } else {
        $message = "Please correct the following errors: <ul><li>" . implode("</li><li>", $errors) . "</li></ul>";
        $messageType = "error";
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
        
        <h1>Social Media Links</h1>
        
        <?php if ($action === 'list'): ?>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Social Link</a>
        <?php else: ?>
            <div></div> <!-- Empty div for flex spacing -->
        <?php endif; ?>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Social Link Form -->
            <div class="panel">
                <h3><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Social Link</h3>
                
                <form action="" method="post">
                    <?php if ($action === 'edit' && $editLink): ?>
                        <input type="hidden" name="id" value="<?php echo $editLink['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="platform">Platform *</label>
                        <input type="text" id="platform" name="platform" value="<?php echo $action === 'edit' ? htmlspecialchars($editLink['platform']) : ''; ?>" required>
                        <p class="note">e.g., Facebook, Twitter, LinkedIn, Instagram, etc.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="url">URL *</label>
                        <input type="url" id="url" name="url" value="<?php echo $action === 'edit' ? htmlspecialchars($editLink['url']) : ''; ?>" required>
                        <p class="note">Full URL to your social media profile</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="icon">Font Awesome Icon *</label>
                        <input type="text" id="icon" name="icon" value="<?php echo $action === 'edit' ? htmlspecialchars($editLink['icon']) : ''; ?>" required>
                        <p class="note">
                            Font Awesome icon class without "fa-" prefix. For example: "facebook-f", "twitter", "linkedin-in", etc.<br>
                            <a href="https://fontawesome.com/icons?d=gallery&s=brands" target="_blank">Find icons here</a>
                        </p>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Add' : 'Update'; ?> Social Link</button>
                        <a href="?action=list" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
            
            <!-- Icon Preview -->
            <div class="panel">
                <h3>Icon Preview</h3>
                
                <div id="icon-preview" style="font-size: 24px; margin: 20px 0; text-align: center;">
                    <i class="fab fa-facebook-f"></i>
                    <span style="margin-left: 10px;">facebook-f</span>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label for="icon-test">Test an icon:</label>
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <input type="text" id="icon-test" placeholder="Enter icon name (e.g., facebook-f)" style="flex-grow: 1;">
                        <button id="test-icon-btn" class="btn btn-secondary">Preview</button>
                    </div>
                </div>
                
                <div>
                    <p><strong>Popular social media icons:</strong></p>
                    <ul style="list-style-type: none; columns: 3; margin: 15px 0;">
                        <li><code>facebook-f</code> - <i class="fab fa-facebook-f"></i></li>
                        <li><code>twitter</code> - <i class="fab fa-twitter"></i></li>
                        <li><code>instagram</code> - <i class="fab fa-instagram"></i></li>
                        <li><code>linkedin-in</code> - <i class="fab fa-linkedin-in"></i></li>
                        <li><code>youtube</code> - <i class="fab fa-youtube"></i></li>
                        <li><code>pinterest</code> - <i class="fab fa-pinterest"></i></li>
                        <li><code>tiktok</code> - <i class="fab fa-tiktok"></i></li>
                        <li><code>whatsapp</code> - <i class="fab fa-whatsapp"></i></li>
                        <li><code>telegram</code> - <i class="fab fa-telegram-plane"></i></li>
                    </ul>
                </div>
            </div>
        <?php else: ?>
            <!-- Social Links List -->
            <div class="panel">
                <h3>Social Media Links</h3>
                
                <?php if (count($socialLinks) > 0): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Platform</th>
                                    <th>Icon</th>
                                    <th>URL</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($socialLinks as $link): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($link['platform']); ?></td>
                                        <td>
                                            <i class="fab fa-<?php echo htmlspecialchars($link['icon']); ?>"></i>
                                            <span style="margin-left: 5px; color: #6c757d; font-size: 0.9em;"><?php echo htmlspecialchars($link['icon']); ?></span>
                                        </td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank">
                                                <?php echo htmlspecialchars($link['url']); ?>
                                            </a>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="?action=edit&id=<?php echo $link['id']; ?>" title="Edit">
                                                <i class="fas fa-edit" style="color: #007bff;"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $link['id']; ?>" onclick="return confirm('Are you sure you want to delete this social link?');" title="Delete">
                                                <i class="fas fa-trash-alt" style="color: #dc3545;"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No social links found. <a href="?action=add">Add your first social link</a>.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Icon preview functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Function to update icon preview
        function updateIconPreview(iconName) {
            const previewDiv = document.getElementById('icon-preview');
            previewDiv.innerHTML = `<i class="fab fa-${iconName}"></i><span style="margin-left: 10px;">${iconName}</span>`;
        }
        
        // Icon test button click handler
        document.getElementById('test-icon-btn').addEventListener('click', function() {
            const iconName = document.getElementById('icon-test').value.trim();
            if (iconName) {
                updateIconPreview(iconName);
            }
        });
        
        // Update preview when typing in the icon field (if on add/edit form)
        const iconField = document.getElementById('icon');
        if (iconField) {
            iconField.addEventListener('input', function() {
                updateIconPreview(this.value.trim());
            });
            
            // Initial preview for edit mode
            if (iconField.value) {
                updateIconPreview(iconField.value);
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>