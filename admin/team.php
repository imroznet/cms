<?php
require_once __DIR__ . '/../includes/security.php';
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Check if user has permission to manage team
if (!in_array('manage_team', $_SESSION['permissions'])) {
    header("Location: dashboard.php");
    exit;
}

// Set page-specific variables
define('INCLUDED', true);
$currentPage = 'team';
$pageTitle = 'Team Members';

// Initialize variables
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$messageType = '';

// Handle team member deletion
if ($action === 'delete' && $id > 0) {
    // Get image filename before deletion
    $stmt = $conn->prepare("SELECT image FROM team_members WHERE id = ?");
    $stmt->execute([$id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    $image = $member['image'] ?? null;
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM team_members WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        // Delete associated image if exists
        if ($image && file_exists('../assets/images/team/' . $image)) {
            unlink('../assets/images/team/' . $image);
        }
        
        $message = "Team member deleted successfully";
        $messageType = "success";
    } else {
        $message = "Error deleting team member";
        $messageType = "error";
    }
    
    $action = 'list';
}

// Toggle active status
if ($action === 'toggle' && $id > 0) {
    $stmt = $conn->prepare("UPDATE team_members SET is_active = NOT is_active WHERE id = ?");
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

// Get team member for editing
$editMember = null;
if (($action === 'edit' || $action === 'view') && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM team_members WHERE id = ?");
    $stmt->execute([$id]);
    $editMember = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$editMember) {
        $message = "Team member not found";
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
    $bio = trim($_POST['bio'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $social_linkedin = trim($_POST['social_linkedin'] ?? '');
    $social_twitter = trim($_POST['social_twitter'] ?? '');
    $social_facebook = trim($_POST['social_facebook'] ?? '');
    $social_instagram = trim($_POST['social_instagram'] ?? '');
    $display_order = (int)($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate form
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($position)) {
        $errors[] = "Position is required";
    }
    
    // Handle image upload
    $image = null;
    if ($id > 0) {
        // Keep existing image for edit mode
        $image = $editMember['image'];
    }
    
    if (!empty($_FILES['image']['name'])) {
        $upload_dir = '../assets/images/team/';
        
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
            
            // Delete old image if exists and not default
            if ($id > 0 && $editMember['image'] && file_exists($upload_dir . $editMember['image'])) {
                unlink($upload_dir . $editMember['image']);
            }
        } else {
            $errors[] = "Error uploading image";
        }
    } elseif ($id === 0) {
        // If it's a new member, require an image
        $errors[] = "Team member image is required";
    }
    
    if (empty($errors)) {
        try {
            if ($id > 0) {
                // Update existing member
                $stmt = $conn->prepare("
                    UPDATE team_members SET 
                    name = ?, 
                    position = ?, 
                    bio = ?, 
                    image = ?,
                    email = ?,
                    phone = ?,
                    social_linkedin = ?,
                    social_twitter = ?,
                    social_facebook = ?,
                    social_instagram = ?,
                    display_order = ?,
                    is_active = ?
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([
                    $name,
                    $position,
                    $bio,
                    $image,
                    $email,
                    $phone,
                    $social_linkedin,
                    $social_twitter,
                    $social_facebook,
                    $social_instagram,
                    $display_order,
                    $is_active,
                    $id
                ]);
                
                if ($result) {
                    $message = "Team member updated successfully";
                    $messageType = "success";
                    $action = 'list';
                } else {
                    $message = "Error updating team member";
                    $messageType = "error";
                }
            } else {
                // Add new member
                $stmt = $conn->prepare("
                    INSERT INTO team_members (
                        name, position, bio, image, email, phone, 
                        social_linkedin, social_twitter, social_facebook, social_instagram,
                        display_order, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $result = $stmt->execute([
                    $name,
                    $position,
                    $bio,
                    $image,
                    $email,
                    $phone,
                    $social_linkedin,
                    $social_twitter,
                    $social_facebook,
                    $social_instagram,
                    $display_order,
                    $is_active
                ]);
                
                if ($result) {
                    $message = "Team member added successfully";
                    $messageType = "success";
                    $action = 'list';
                } else {
                    $message = "Error adding team member";
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

// Get team members for list view
$teamMembers = [];
if ($action === 'list') {
    $stmt = $conn->query("SELECT * FROM team_members ORDER BY display_order ASC, name ASC");
    $teamMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        
        <h1>Team Members</h1>
        
        <?php if ($action === 'list'): ?>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Member</a>
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
            <!-- Add/Edit Team Member Form -->
            <div class="panel">
                <h3><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Team Member</h3>
                
                <form action="" method="post" enctype="multipart/form-data">
                    <?php if ($action === 'edit' && $editMember): ?>
                        <input type="hidden" name="id" value="<?php echo $editMember['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo $action === 'edit' ? htmlspecialchars($editMember['name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="position">Position/Title *</label>
                        <input type="text" id="position" name="position" value="<?php echo $action === 'edit' ? htmlspecialchars($editMember['position']) : ''; ?>" required>
                        <p class="note">e.g., CEO, Marketing Director, Software Developer</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Profile Image <?php echo $action === 'add' ? '*' : ''; ?></label>
                        <input type="file" id="image" name="image" accept="image/*" <?php echo $action === 'add' ? 'required' : ''; ?>>
                        <p class="note">
                            Recommended size: 400x400 pixels (square). Max file size: 2MB.<br>
                            Supported formats: JPG, JPEG, PNG, GIF
                        </p>
                        
                        <?php if ($action === 'edit' && $editMember['image']): ?>
                            <div style="margin-top: 10px;">
                                <p>Current image:</p>
                                <img src="../assets/images/team/<?php echo htmlspecialchars($editMember['image']); ?>" alt="Team Member" style="max-width: 100px; max-height: 100px; margin-top: 10px; border-radius: 50%; border: 1px solid #ddd;">
                                <p class="note">Leave empty to keep current image</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Biography/Description</label>
                        <textarea id="bio" name="bio" rows="5"><?php echo $action === 'edit' ? htmlspecialchars($editMember['bio']) : ''; ?></textarea>
                        <p class="note">Brief description about this team member</p>
                    </div>
                    
                    <div class="form-section" style="margin-top: 30px;">
                        <h4>Contact Information</h4>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo $action === 'edit' ? htmlspecialchars($editMember['email']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone" value="<?php echo $action === 'edit' ? htmlspecialchars($editMember['phone']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-section" style="margin-top: 30px;">
                        <h4>Social Media</h4>
                        
                        <div class="form-group">
                            <label for="social_linkedin">LinkedIn URL</label>
                            <input type="url" id="social_linkedin" name="social_linkedin" value="<?php echo $action === 'edit' ? htmlspecialchars($editMember['social_linkedin']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="social_twitter">Twitter URL</label>
                            <input type="url" id="social_twitter" name="social_twitter" value="<?php echo $action === 'edit' ? htmlspecialchars($editMember['social_twitter']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="social_facebook">Facebook URL</label>
                            <input type="url" id="social_facebook" name="social_facebook" value="<?php echo $action === 'edit' ? htmlspecialchars($editMember['social_facebook']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="social_instagram">Instagram URL</label>
                            <input type="url" id="social_instagram" name="social_instagram" value="<?php echo $action === 'edit' ? htmlspecialchars($editMember['social_instagram']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-section" style="margin-top: 30px;">
                        <h4>Display Settings</h4>
                        
                        <div class="form-group">
                            <label for="display_order">Display Order</label>
                            <input type="number" id="display_order" name="display_order" value="<?php echo $action === 'edit' ? (int)$editMember['display_order'] : 0; ?>" min="0">
                            <p class="note">Team members are displayed in ascending order (0 first)</p>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="is_active" name="is_active" <?php echo ($action === 'edit' && $editMember['is_active']) || $action === 'add' ? 'checked' : ''; ?>>
                                <label for="is_active">Active</label>
                            </div>
                            <p class="note">Inactive team members will not be displayed on the website</p>
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Add' : 'Update'; ?> Team Member</button>
                        <a href="?action=list" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php elseif ($action === 'view'): ?>
            <!-- View Team Member Details -->
            <div class="panel">
                <h3>Team Member Details</h3>
                
                <div class="member-details" style="display: flex; flex-wrap: wrap; gap: 30px; margin-bottom: 30px;">
                    <div class="member-image" style="flex: 0 0 200px;">
                        <?php if ($editMember['image'] && file_exists('../assets/images/team/' . $editMember['image'])): ?>
                            <img src="../assets/images/team/<?php echo htmlspecialchars($editMember['image']); ?>" alt="<?php echo htmlspecialchars($editMember['name']); ?>" style="max-width: 100%; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <?php else: ?>
                            <div style="width: 200px; height: 200px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 5px;">
                                <i class="fas fa-user" style="font-size: 48px; color: #adb5bd;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="member-info" style="flex: 1; min-width: 300px;">
                        <h2 style="margin-bottom: 5px;"><?php echo htmlspecialchars($editMember['name']); ?></h2>
                        <p style="color: #007bff; margin-bottom: 15px;"><?php echo htmlspecialchars($editMember['position']); ?></p>
                        
                        <div class="status-badge <?php echo $editMember['is_active'] ? 'status-active' : 'status-inactive'; ?>" style="display: inline-block; padding: 3px 10px; border-radius: 3px; font-size: 12px; margin-bottom: 15px;">
                            <?php echo $editMember['is_active'] ? 'Active' : 'Inactive'; ?>
                        </div>
                        
                        <?php if (!empty($editMember['bio'])): ?>
                            <div class="bio" style="margin-bottom: 20px;">
                                <h4>Biography</h4>
                                <p><?php echo nl2br(htmlspecialchars($editMember['bio'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($editMember['email']) || !empty($editMember['phone'])): ?>
                            <div class="contact-info" style="margin-bottom: 20px;">
                                <h4>Contact Information</h4>
                                
                                <?php if (!empty($editMember['email'])): ?>
                                    <p><i class="fas fa-envelope" style="width: 20px;"></i> <a href="mailto:<?php echo htmlspecialchars($editMember['email']); ?>"><?php echo htmlspecialchars($editMember['email']); ?></a></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($editMember['phone'])): ?>
                                    <p><i class="fas fa-phone" style="width: 20px;"></i> <a href="tel:<?php echo htmlspecialchars($editMember['phone']); ?>"><?php echo htmlspecialchars($editMember['phone']); ?></a></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php
                        $hasSocial = !empty($editMember['social_linkedin']) || !empty($editMember['social_twitter']) || 
                                     !empty($editMember['social_facebook']) || !empty($editMember['social_instagram']);
                        if ($hasSocial):
                        ?>
                            <div class="social-links" style="margin-bottom: 20px;">
                                <h4>Social Media</h4>
                                <div style="display: flex; gap: 10px; margin-top: 10px;">
                                    <?php if (!empty($editMember['social_linkedin'])): ?>
                                        <a href="<?php echo htmlspecialchars($editMember['social_linkedin']); ?>" target="_blank" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background-color: #0077b5; color: white; border-radius: 50%;">
                                            <i class="fab fa-linkedin-in"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($editMember['social_twitter'])): ?>
                                        <a href="<?php echo htmlspecialchars($editMember['social_twitter']); ?>" target="_blank" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background-color: #1da1f2; color: white; border-radius: 50%;">
                                            <i class="fab fa-twitter"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($editMember['social_facebook'])): ?>
                                        <a href="<?php echo htmlspecialchars($editMember['social_facebook']); ?>" target="_blank" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background-color: #3b5998; color: white; border-radius: 50%;">
                                            <i class="fab fa-facebook-f"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($editMember['social_instagram'])): ?>
                                        <a href="<?php echo htmlspecialchars($editMember['social_instagram']); ?>" target="_blank" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; background-color: #e1306c; color: white; border-radius: 50%;">
                                            <i class="fab fa-instagram"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="action-buttons" style="margin-top: 20px;">
                    <a href="?action=edit&id=<?php echo $editMember['id']; ?>" class="btn btn-primary">Edit</a>
                    <a href="?action=list" class="btn btn-secondary">Back to List</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Team Members List -->
            <div class="panel">
                <h3>Team Members</h3>
                
                <?php if (count($teamMembers) > 0): ?>
                    <div class="team-preview-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        <?php foreach ($teamMembers as $member): ?>
                            <div class="team-preview-card" style="text-align: center; background-color: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: relative;">
                                <?php if (!$member['is_active']): ?>
                                    <div style="position: absolute; top: 10px; right: 10px; background-color: rgba(220, 53, 69, 0.8); color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px;">
                                        Inactive
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($member['image'] && file_exists('../assets/images/team/' . $member['image'])): ?>
                                    <div style="height: 200px; overflow: hidden;">
                                        <img src="../assets/images/team/<?php echo htmlspecialchars($member['image']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                <?php else: ?>
                                    <div style="height: 200px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-user" style="font-size: 48px; color: #adb5bd;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div style="padding: 15px;">
                                    <h4 style="margin: 0; font-size: 18px;"><?php echo htmlspecialchars($member['name']); ?></h4>
                                    <p style="color: #6c757d; margin: 5px 0;"><?php echo htmlspecialchars($member['position']); ?></p>
                                    
                                    <div style="margin-top: 10px;">
                                        <a href="?action=view&id=<?php echo $member['id']; ?>" class="btn btn-primary btn-sm" style="padding: 3px 10px; font-size: 14px;">View</a>
                                        <a href="?action=edit&id=<?php echo $member['id']; ?>" class="btn btn-secondary btn-sm" style="padding: 3px 10px; font-size: 14px;">Edit</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teamMembers as $member): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <?php if ($member['image'] && file_exists('../assets/images/team/' . $member['image'])): ?>
                                                    <img src="../assets/images/team/<?php echo htmlspecialchars($member['image']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                                <?php else: ?>
                                                    <div style="width: 40px; height: 40px; border-radius: 50%; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-user" style="color: #adb5bd;"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($member['name']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($member['position']); ?></td>
                                        <td><?php echo (int)$member['display_order']; ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $member['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="?action=view&id=<?php echo $member['id']; ?>" title="View">
                                                <i class="fas fa-eye" style="color: #28a745;"></i>
                                            </a>
                                            <a href="?action=edit&id=<?php echo $member['id']; ?>" title="Edit">
                                                <i class="fas fa-edit" style="color: #007bff;"></i>
                                            </a>
                                            <a href="?action=toggle&id=<?php echo $member['id']; ?>" title="<?php echo $member['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="fas <?php echo $member['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>" style="color: <?php echo $member['is_active'] ? '#28a745' : '#6c757d'; ?>;"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $member['id']; ?>" onclick="return confirm('Are you sure you want to delete this team member?');" title="Delete">
                                                <i class="fas fa-trash-alt" style="color: #dc3545;"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No team members found. <a href="?action=add">Add your first team member</a>.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>