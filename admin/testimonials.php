<?php
require_once __DIR__ . '/../includes/security.php';
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Check if user has permission to manage testimonials
if (!in_array('manage_testimonials', $_SESSION['permissions'])) {
    header("Location: dashboard.php");
    exit;
}

// Set page-specific variables
define('INCLUDED', true);
$currentPage = 'testimonials';
$pageTitle = 'Testimonials';

// Initialize variables
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$messageType = '';

// Handle testimonial deletion
if ($action === 'delete' && $id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get image filename before deletion
    $stmt = $conn->prepare("SELECT image FROM testimonials WHERE id = ?");
    $stmt->execute([$id]);
    $testimonial = $stmt->fetch(PDO::FETCH_ASSOC);
    $image = $testimonial['image'] ?? null;
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM testimonials WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        // Delete associated image if exists
        if ($image && file_exists('../assets/images/testimonials/' . $image)) {
            unlink('../assets/images/testimonials/' . $image);
        }
        
        $message = "Testimonial deleted successfully";
        $messageType = "success";
    } else {
        $message = "Error deleting testimonial";
        $messageType = "error";
    }
    
    $action = 'list';
}

// Toggle active status
if ($action === 'toggle' && $id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("UPDATE testimonials SET is_active = NOT is_active WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        $message = "Status updated successfully";
        $messageType = "success";
    } else {
        $message = "Error updating status";
        $messageType = "error";
    }
    
    $action = 'list';
}

// Get testimonial for editing
$editTestimonial = null;
if (($action === 'edit' || $action === 'view') && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM testimonials WHERE id = ?");
    $stmt->execute([$id]);
    $editTestimonial = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$editTestimonial) {
        $message = "Testimonial not found";
        $messageType = "error";
        $action = 'list';
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['_confirm_action'])) {
    // Extract form data
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $testimonial_message = trim($_POST['message'] ?? '');
    $rating = (int)($_POST['rating'] ?? 5);
    $display_order = (int)($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate form
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($testimonial_message)) {
        $errors[] = "Testimonial message is required";
    }
    
    if ($rating < 1 || $rating > 5) {
        $errors[] = "Rating must be between 1 and 5";
    }
    
    // Handle image upload
    $image = null;
    if ($id > 0) {
        // Keep existing image for edit mode
        $image = $editTestimonial['image'];
    }
    
    if (!empty($_FILES['image']['name'])) {
        $upload_dir = '../assets/images/testimonials/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $filename = time() . '_' . $_FILES['image']['name'];
        $upload_path = $upload_dir . $filename;
        
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $errors[] = "Image must be JPG, JPEG, PNG or GIF format";
        } elseif ($_FILES['image']['size'] > 2000000) {
            $errors[] = "Image size must be less than 2MB";
        } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            // If upload successful, update image name
            $image = $filename;
            
            // Delete old image if exists
            if ($id > 0 && $editTestimonial['image'] && file_exists($upload_dir . $editTestimonial['image'])) {
                unlink($upload_dir . $editTestimonial['image']);
            }
        } else {
            $errors[] = "Error uploading image";
        }
    }
    
    if (empty($errors)) {
        try {
            if ($id > 0) {
                // Update existing testimonial
                $stmt = $conn->prepare("
                    UPDATE testimonials SET 
                    name = ?, 
                    position = ?, 
                    company = ?, 
                    message = ?,
                    image = ?,
                    rating = ?,
                    display_order = ?,
                    is_active = ?
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([
                    $name,
                    $position,
                    $company,
                    $testimonial_message,
                    $image,
                    $rating,
                    $display_order,
                    $is_active,
                    $id
                ]);
                
                if ($result) {
                    $message = "Testimonial updated successfully";
                    $messageType = "success";
                    $action = 'list';
                } else {
                    $message = "Error updating testimonial";
                    $messageType = "error";
                }
            } else {
                // Add new testimonial
                $stmt = $conn->prepare("
                    INSERT INTO testimonials (
                        name, position, company, message, image, rating, display_order, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $result = $stmt->execute([
                    $name,
                    $position,
                    $company,
                    $testimonial_message,
                    $image,
                    $rating,
                    $display_order,
                    $is_active
                ]);
                
                if ($result) {
                    $message = "Testimonial added successfully";
                    $messageType = "success";
                    $action = 'list';
                } else {
                    $message = "Error adding testimonial";
                    $messageType = "error";
                }
            }
        } catch (PDOException $e) {
            $message = "The operation failed. Please contact your administrator.";
            $messageType = "error";
        }
    } else {
        $message = "Please correct the following errors: <ul><li>" . implode("</li><li>", $errors) . "</li></ul>";
        $messageType = "error";
    }
}

// Get testimonials for list view
$testimonials = [];
if ($action === 'list') {
    $stmt = $conn->query("SELECT * FROM testimonials ORDER BY display_order ASC, created_at DESC");
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        
        <h1>Testimonials</h1>
        
        <?php if ($action === 'list'): ?>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Testimonial</a>
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
            <!-- Add/Edit Testimonial Form -->
            <div class="panel">
                <h3><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Testimonial</h3>
                
                <form action="" method="post" enctype="multipart/form-data">
                    <?php if ($action === 'edit' && $editTestimonial): ?>
                        <input type="hidden" name="id" value="<?php echo $editTestimonial['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="name">Client Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo $action === 'edit' ? htmlspecialchars($editTestimonial['name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="position">Position</label>
                        <input type="text" id="position" name="position" value="<?php echo $action === 'edit' ? htmlspecialchars($editTestimonial['position']) : ''; ?>">
                        <p class="note">e.g., CEO, Marketing Director, etc.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="company">Company</label>
                        <input type="text" id="company" name="company" value="<?php echo $action === 'edit' ? htmlspecialchars($editTestimonial['company']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Testimonial *</label>
                        <textarea id="message" name="message" rows="5" required><?php echo $action === 'edit' ? htmlspecialchars($editTestimonial['message']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Client Photo</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <p class="note">
                            Recommended size: 150x150 pixels (square). Max file size: 2MB.<br>
                            Supported formats: JPG, JPEG, PNG, GIF
                        </p>
                        
                        <?php if ($action === 'edit' && $editTestimonial['image']): ?>
                            <div style="margin-top: 10px;">
                                <p>Current image:</p>
                                <img src="../assets/images/testimonials/<?php echo htmlspecialchars($editTestimonial['image']); ?>" alt="Client" style="max-width: 100px; max-height: 100px; margin-top: 10px; border-radius: 50%;">
                                <p class="note">Leave empty to keep current image</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="rating">Rating</label>
                        <select id="rating" name="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($action === 'edit' && $editTestimonial['rating'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-section" style="margin-top: 30px;">
                        <h4>Display Settings</h4>
                        
                        <div class="form-group">
                            <label for="display_order">Display Order</label>
                            <input type="number" id="display_order" name="display_order" value="<?php echo $action === 'edit' ? (int)$editTestimonial['display_order'] : 0; ?>" min="0">
                            <p class="note">Lower numbers will display first (0 = default)</p>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="is_active" name="is_active" <?php echo ($action === 'edit' && $editTestimonial['is_active']) || $action === 'add' ? 'checked' : ''; ?>>
                                <label for="is_active">Active</label>
                            </div>
                            <p class="note">Inactive testimonials will not be displayed on the website</p>
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Add' : 'Update'; ?> Testimonial</button>
                        <a href="?action=list" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php elseif ($action === 'view'): ?>
            <!-- View Testimonial Details -->
            <div class="panel">
                <h3>Testimonial Details</h3>
                
                <div class="testimonial-view" style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
                    <?php if ($editTestimonial['image'] && file_exists('../assets/images/testimonials/' . $editTestimonial['image'])): ?>
                    <div style="flex: 0 0 150px;">
                        <img src="../assets/images/testimonials/<?php echo htmlspecialchars($editTestimonial['image']); ?>" alt="<?php echo htmlspecialchars($editTestimonial['name']); ?>" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%;">
                    </div>
                    <?php endif; ?>
                    
                    <div style="flex: 1; min-width: 300px;">
                        <div style="margin-bottom: 20px;">
                            <h4 style="margin-bottom: 5px;"><?php echo htmlspecialchars($editTestimonial['name']); ?></h4>
                            <?php if (!empty($editTestimonial['position']) || !empty($editTestimonial['company'])): ?>
                            <p style="color: #6c757d; margin-bottom: 10px;">
                                <?php 
                                echo !empty($editTestimonial['position']) ? htmlspecialchars($editTestimonial['position']) : '';
                                echo !empty($editTestimonial['position']) && !empty($editTestimonial['company']) ? ', ' : '';
                                echo !empty($editTestimonial['company']) ? htmlspecialchars($editTestimonial['company']) : '';
                                ?>
                            </p>
                            <?php endif; ?>
                            
                            <div style="margin-bottom: 15px;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star" style="color: <?php echo $i <= $editTestimonial['rating'] ? '#ffc107' : '#e9ecef'; ?>; font-size: 18px;"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                            <p style="font-style: italic; color: #343a40;">
                                "<?php echo nl2br(htmlspecialchars($editTestimonial['message'])); ?>"
                            </p>
                        </div>
                        
                        <p>
                            <span class="status-badge <?php echo $editTestimonial['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $editTestimonial['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                            <span style="margin-left: 10px; color: #6c757d;">Display Order: <?php echo (int)$editTestimonial['display_order']; ?></span>
                        </p>
                    </div>
                </div>
                
                <div class="form-buttons">
                    <a href="?action=edit&id=<?php echo $editTestimonial['id']; ?>" class="btn btn-primary">Edit</a>
                    <a href="?action=list" class="btn btn-secondary">Back to List</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Testimonials List -->
            <div class="panel">
                <h3>Testimonials List</h3>
                
                <?php if (count($testimonials) > 0): ?>
                    <div class="testimonials-preview-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        <?php foreach ($testimonials as $testimonial): ?>
                            <div class="testimonial-card" style="background-color: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; position: relative;">
                                <?php if (!$testimonial['is_active']): ?>
                                    <div style="position: absolute; top: 10px; right: 10px; background-color: rgba(220, 53, 69, 0.8); color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">
                                        Inactive
                                    </div>
                                <?php endif; ?>
                                
                                <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                    <div style="width: 60px; height: 60px; overflow: hidden; border-radius: 50%; margin-right: 15px;">
                                        <?php if ($testimonial['image'] && file_exists('../assets/images/testimonials/' . $testimonial['image'])): ?>
                                            <img src="../assets/images/testimonials/<?php echo htmlspecialchars($testimonial['image']); ?>" alt="<?php echo htmlspecialchars($testimonial['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 100%; background-color: #e9ecef; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-user" style="color: #adb5bd;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div>
                                        <h4 style="margin: 0; font-size: 16px;"><?php echo htmlspecialchars($testimonial['name']); ?></h4>
                                        <?php if (!empty($testimonial['position']) || !empty($testimonial['company'])): ?>
                                            <p style="margin: 0; font-size: 14px; color: #6c757d;">
                                                <?php 
                                                echo !empty($testimonial['position']) ? htmlspecialchars($testimonial['position']) : '';
                                                echo !empty($testimonial['position']) && !empty($testimonial['company']) ? ', ' : '';
                                                echo !empty($testimonial['company']) ? htmlspecialchars($testimonial['company']) : '';
                                                ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div style="margin-bottom: 15px;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star" style="color: <?php echo $i <= $testimonial['rating'] ? '#ffc107' : '#e9ecef'; ?>; font-size: 14px;"></i>
                                    <?php endfor; ?>
                                </div>
                                
                                <p style="font-style: italic; color: #6c757d; font-size: 14px; margin-bottom: 15px; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis;">
                                    "<?php echo htmlspecialchars($testimonial['message']); ?>"
                                </p>
                                
                                <div style="display: flex; gap: 10px;">
                                    <a href="?action=view&id=<?php echo $testimonial['id']; ?>" class="btn btn-primary btn-sm" style="padding: 3px 10px; font-size: 14px;">View</a>
                                    <a href="?action=edit&id=<?php echo $testimonial['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 3px 10px; font-size: 14px;">Edit</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Position / Company</th>
                                    <th>Rating</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($testimonials as $testimonial): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($testimonial['name']); ?></td>
                                        <td>
                                            <?php 
                                            echo !empty($testimonial['position']) ? htmlspecialchars($testimonial['position']) : '';
                                            echo !empty($testimonial['position']) && !empty($testimonial['company']) ? ', ' : '';
                                            echo !empty($testimonial['company']) ? htmlspecialchars($testimonial['company']) : '';
                                            echo empty($testimonial['position']) && empty($testimonial['company']) ? '—' : '';
                                            ?>
                                        </td>
                                        <td>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star" style="color: <?php echo $i <= $testimonial['rating'] ? '#ffc107' : '#e9ecef'; ?>; font-size: 12px;"></i>
                                            <?php endfor; ?>
                                        </td>
                                        <td><?php echo (int)$testimonial['display_order']; ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $testimonial['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $testimonial['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="?action=view&id=<?php echo $testimonial['id']; ?>" title="View">
                                                <i class="fas fa-eye" style="color: #28a745;"></i>
                                            </a>
                                            <a href="?action=edit&id=<?php echo $testimonial['id']; ?>" title="Edit">
                                                <i class="fas fa-edit" style="color: #007bff;"></i>
                                            </a>
                                            <form method="post" action="?action=toggle&id=<?php echo $testimonial['id']; ?>" style="display:inline;margin-right:10px">
                                                <input type="hidden" name="_confirm_action" value="1">
                                                <button type="submit" title="<?php echo $testimonial['is_active'] ? 'Deactivate' : 'Activate'; ?>" style="background:none;border:0;padding:0;cursor:pointer;">
                                                    <i class="fas <?php echo $testimonial['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>" style="color: <?php echo $testimonial['is_active'] ? '#28a745' : '#6c757d'; ?>;"></i>
                                                </button>
                                            </form>
                                            <form method="post" action="?action=delete&id=<?php echo $testimonial['id']; ?>" style="display:inline;margin-right:10px" onsubmit="return confirm('Are you sure you want to delete this testimonial?');">
                                                <input type="hidden" name="_confirm_action" value="1">
                                                <button type="submit" title="Delete" style="background:none;border:0;padding:0;cursor:pointer;">
                                                    <i class="fas fa-trash-alt" style="color: #dc3545;"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No testimonials found. <a href="?action=add">Add your first testimonial</a>.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>