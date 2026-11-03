<?php
require_once __DIR__ . '/../includes/security.php';
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Check if user has permission to manage users
if (!in_array('manage_users', $_SESSION['permissions']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Set page-specific variables
define('INCLUDED', true);
$currentPage = 'users';
$pageTitle = 'Manage Users';

// Process actions
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$messageType = '';

// Delete user
if ($action === 'delete' && $id > 0) {
    // Prevent self-deletion
    if ($id === $_SESSION['user_id']) {
        $message = "You cannot delete your own account";
        $messageType = "error";
        $action = 'list';
    } else {
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // Delete user permissions
            $stmt = $conn->prepare("DELETE FROM user_permissions WHERE user_id = ?");
            $stmt->execute([$id]);
            
            // Delete user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            // Commit transaction
            $conn->commit();
            
            $message = "User deleted successfully";
            $messageType = "success";
        } catch (PDOException $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $message = "The operation failed. Please contact your administrator.";
            $messageType = "error";
        }
        
        $action = 'list';
    }
}

// Get user for editing
$editUser = null;
$userPermissions = [];
if ($action === 'edit' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$editUser) {
        $message = "User not found";
        $messageType = "error";
        $action = 'list';
    } else {
        // Get user permissions
        $stmt = $conn->prepare("SELECT permission_name FROM user_permissions WHERE user_id = ?");
        $stmt->execute([$id]);
        $userPermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['_confirm_action'])) {
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'user_form') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
        
        // Validate form
        $errors = [];
        
        if (empty($username)) {
            $errors[] = "Username is required";
        }
        
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        if (empty($role)) {
            $errors[] = "Role is required";
        } elseif (!in_array($role, ['admin', 'editor', 'contributor'])) {
            $errors[] = "Invalid role selected";
        }
        
        // Check if username is taken (for new users or when changing username)
        if ($id === 0 || ($editUser && $username !== $editUser['username'])) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Username is already taken";
            }
        }
        
        // Check if email is taken (for new users or when changing email)
        if ($id === 0 || ($editUser && $email !== $editUser['email'])) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Email is already registered";
            }
        }
        
        // Password validation for new users or password changes
        if ($id === 0 || !empty($newPassword)) {
            if (empty($newPassword)) {
                $errors[] = "Password is required for new users";
            } elseif (strlen($newPassword) < 8) {
                $errors[] = "Password must be at least 8 characters long";
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = "Passwords do not match";
            }
        }
        
        if (empty($errors)) {
            try {
                // Start transaction
                $conn->beginTransaction();
                
                if ($id > 0) {
                    // Update existing user
                    if (!empty($newPassword)) {
                        // Update with new password
                        $password_hash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
                        $stmt->execute([$username, $email, $password_hash, $role, $id]);
                    } else {
                        // Update without changing password
                        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
                        $stmt->execute([$username, $email, $role, $id]);
                    }
                } else {
                    // Add new user
                    $password_hash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $password_hash, $role]);
                    $id = $conn->lastInsertId();
                }
                
                // Update permissions
                // First delete existing permissions
                $stmt = $conn->prepare("DELETE FROM user_permissions WHERE user_id = ?");
                $stmt->execute([$id]);
                
                // Then add new permissions
                if (!empty($permissions)) {
                    $stmt = $conn->prepare("INSERT INTO user_permissions (user_id, permission_name) VALUES (?, ?)");
                    foreach ($permissions as $permission) {
                        $stmt->execute([$id, $permission]);
                    }
                }
                
                // Commit transaction
                $conn->commit();
                
                $message = $id > 0 ? "User updated successfully" : "User added successfully";
                $messageType = "success";
                $action = 'list';
            } catch (PDOException $e) {
                // Rollback transaction on error
                $conn->rollBack();
                $message = "The operation failed. Please contact your administrator.";
                $messageType = "error";
            }
        } else {
            $message = "Please correct the following errors: <ul><li>" . implode("</li><li>", $errors) . "</li></ul>";
            $messageType = "error";
        }
    }
}

// Get users for list view
$users = [];
if ($action === 'list') {
    $stmt = $conn->query("SELECT * FROM users ORDER BY username ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Define available permissions
$availablePermissions = [
    'manage_settings' => 'Manage Site Settings',
    'manage_news' => 'Manage News Articles',
    'manage_services' => 'Manage Services',
    'manage_testimonials' => 'Manage Testimonials',
    'manage_careers' => 'Manage Career Listings',
    'manage_contacts' => 'Manage Contact Messages',
    'manage_statistics' => 'Manage Statistics',
    'manage_images' => 'Manage Website Images',
    'manage_team' => 'Manage Team Members',
    'manage_users' => 'Manage Users'
];

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
        
        <h1>Manage Users</h1>
        
        <?php if ($action === 'list'): ?>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add User</a>
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
            <!-- Add/Edit User Form -->
            <div class="panel">
                <h3><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> User</h3>
                
                <form action="" method="post">
                    <input type="hidden" name="form_type" value="user_form">
                    <?php if ($action === 'edit' && $editUser): ?>
                        <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" value="<?php echo $action === 'edit' ? htmlspecialchars($editUser['username']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" value="<?php echo $action === 'edit' ? htmlspecialchars($editUser['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin" <?php echo ($action === 'edit' && $editUser['role'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                            <option value="editor" <?php echo ($action === 'edit' && $editUser['role'] === 'editor') ? 'selected' : ''; ?>>Editor</option>
                            <option value="contributor" <?php echo ($action === 'edit' && $editUser['role'] === 'contributor') ? 'selected' : ''; ?>>Contributor</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password"><?php echo $action === 'add' ? 'Password *' : 'New Password'; ?></label>
                        <input type="password" id="new_password" name="new_password" <?php echo $action === 'add' ? 'required' : ''; ?>>
                        <?php if ($action === 'edit'): ?>
                            <p class="note">Leave blank to keep current password</p>
                        <?php else: ?>
                            <p class="note">Password must be at least 8 characters long</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password"><?php echo $action === 'add' ? 'Confirm Password *' : 'Confirm New Password'; ?></label>
                        <input type="password" id="confirm_password" name="confirm_password" <?php echo $action === 'add' ? 'required' : ''; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label>Permissions</label>
                        <div class="permissions-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px; margin-top: 10px;">
                            <?php foreach ($availablePermissions as $permKey => $permLabel): ?>
                                <div class="checkbox-wrapper" style="display: flex; align-items: center;">
                                    <input type="checkbox" id="perm_<?php echo $permKey; ?>" name="permissions[]" value="<?php echo $permKey; ?>" <?php echo ($action === 'edit' && in_array($permKey, $userPermissions)) ? 'checked' : ''; ?>>
                                    <label for="perm_<?php echo $permKey; ?>" style="margin-left: 8px; font-weight: normal;"><?php echo $permLabel; ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Add' : 'Update'; ?> User</button>
                        <a href="?action=list" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Users List -->
            <div class="panel">
                <h3>Users</h3>
                
                <?php if (count($users) > 0): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($user['username']); ?>
                                            <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                                <span style="margin-left: 5px; font-size: 0.8em; color: #6c757d;">(You)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                                        <td class="action-buttons">
                                            <a href="?action=edit&id=<?php echo $user['id']; ?>" title="Edit">
                                                <i class="fas fa-edit" style="color: #007bff;"></i>
                                            </a>
                                            
                                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                <a href="?action=delete&id=<?php echo $user['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?');" title="Delete">
                                                    <i class="fas fa-trash-alt" style="color: #dc3545;"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No users found. <a href="?action=add">Add your first user</a>.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>