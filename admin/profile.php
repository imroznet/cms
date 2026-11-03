<?php
require_once __DIR__ . '/../includes/security.php';
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Set page-specific variables
define('INCLUDED', true);
$currentPage = 'profile';
$pageTitle = 'My Profile';

// Initialize variables
$message = '';
$messageType = '';
$user = [];

// Get user details
$stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['_confirm_action'])) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        // Update profile info
        $email = trim($_POST['email'] ?? '');
        
        // Validate input
        $errors = [];
        
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                $result = $stmt->execute([$email, $_SESSION['user_id']]);
                
                if ($result) {
                    $_SESSION['email'] = $email; // Update session data
                    $message = "Profile updated successfully";
                    $messageType = "success";
                    
                    // Refresh user data
                    $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $message = "Error updating profile";
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
    } elseif ($action === 'change_password') {
        // Change password
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate input
        $errors = [];
        
        if (empty($current_password)) {
            $errors[] = "Current password is required";
        }
        
        if (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters long";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
        
        if (empty($errors)) {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData && password_verify($current_password, $userData['password'])) {
                // Hash new password
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password in database
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $result = $stmt->execute([$new_password_hash, $_SESSION['user_id']]);
                
                if ($result) {
                    $message = "Password changed successfully";
                    $messageType = "success";
                } else {
                    $message = "Error changing password";
                    $messageType = "error";
                }
            } else {
                $message = "Current password is incorrect";
                $messageType = "error";
            }
        } else {
            $message = "Please correct the following errors: <ul><li>" . implode("</li><li>", $errors) . "</li></ul>";
            $messageType = "error";
        }
    }
}

// Get recent activity
$stmt = $conn->prepare("SELECT * FROM user_activity_log WHERE user_id = ? ORDER BY timestamp DESC LIMIT 10");
$stmt->execute([$_SESSION['user_id']]);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        
        <h1>My Profile</h1>
        
        <div></div> <!-- Empty div for flex spacing -->
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="panel">
            <h3>Account Information</h3>
            
            <div class="profile-info" style="display: flex; align-items: center; margin-bottom: 20px;">
                <div style="font-size: 48px; color: #6c757d; margin-right: 20px;">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div>
                    <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($user['username']); ?></h3>
                    <p style="margin-bottom: 5px; color: #6c757d;"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p style="margin: 0;">Role: <?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
                </div>
            </div>
            
            <form action="" method="post">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    <p class="note">Username cannot be changed.</p>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </div>
            </form>
        </div>
        
        <div class="panel">
            <h3>Change Password</h3>
            
            <form action="" method="post">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <p class="note">Password must be at least 8 characters long.</p>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
        
        <?php if (count($activities) > 0): ?>
            <div class="panel">
                <h3>Recent Activity</h3>
                
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Action</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                                <?php 
                                $details = json_decode($activity['details'], true);
                                $browser = $details['browser'] ?? 'Unknown';
                                ?>
                                <tr>
                                    <td><?php echo date('M j, Y g:i a', strtotime($activity['timestamp'])); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($activity['action'])); ?></td>
                                    <td><?php echo htmlspecialchars($activity['ip_address']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>