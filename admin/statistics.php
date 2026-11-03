<?php
require_once __DIR__ . '/../includes/security.php';
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Check if user has permission to manage statistics
if (!in_array('manage_statistics', $_SESSION['permissions'])) {
    header("Location: dashboard.php");
    exit;
}

// Set page-specific variables
define('INCLUDED', true);
$currentPage = 'statistics';
$pageTitle = 'Manage Statistics';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$messageType = '';

// Handle statistic deletion
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM statistics WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            $message = "Statistic deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error deleting statistic";
            $messageType = "error";
        }
    } catch (PDOException $e) {
        $message = "The operation failed. Please contact your administrator.";
        $messageType = "error";
    }
    
    $action = 'list'; // Return to list view after deletion
}

// Handle form submission for add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['_confirm_action'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title = trim($_POST['title'] ?? '');
    $value = trim($_POST['value'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $display_order = (int)($_POST['display_order'] ?? 0);
    
    // Validate form
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($value)) {
        $errors[] = "Value is required";
    }
    
    if (empty($icon)) {
        $errors[] = "Icon is required";
    }
    
    if (empty($errors)) {
        try {
            if ($id > 0) {
                // Update existing statistic
                $stmt = $conn->prepare("UPDATE statistics SET title = ?, value = ?, icon = ?, display_order = ? WHERE id = ?");
                $result = $stmt->execute([$title, $value, $icon, $display_order, $id]);
                
                if ($result) {
                    $message = "Statistic updated successfully!";
                    $messageType = "success";
                    $action = 'list'; // Return to list view after update
                } else {
                    $message = "Error updating statistic";
                    $messageType = "error";
                }
            } else {
                // Add new statistic
                $stmt = $conn->prepare("INSERT INTO statistics (title, value, icon, display_order) VALUES (?, ?, ?, ?)");
                $result = $stmt->execute([$title, $value, $icon, $display_order]);
                
                if ($result) {
                    $message = "Statistic added successfully!";
                    $messageType = "success";
                    $action = 'list'; // Return to list view after addition
                } else {
                    $message = "Error adding statistic";
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

// Get statistic for editing
$editStat = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT * FROM statistics WHERE id = ?");
    $stmt->execute([$id]);
    $editStat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$editStat) {
        $message = "Statistic not found";
        $messageType = "error";
        $action = 'list';
    }
}

// Get all statistics for listing
$statsList = [];
if ($action === 'list') {
    $stmt = $conn->prepare("SELECT * FROM statistics ORDER BY display_order ASC, title ASC");
    $stmt->execute();
    $statsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Include header with shared navigation
include 'includes/header.php';
?>

<!-- Main Content -->
<div class="main-content">
    <!-- Top Navigation -->
    <div class="top-nav">
        <button class="menu-toggle" id="menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <h1>Manage Statistics</h1>
        
        <?php if ($action === 'list'): ?>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Statistic</a>
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
        
        <!-- Statistics Preview Section -->
        <?php if ($action === 'list' && count($statsList) > 0): ?>
            <div class="panel">
                <h3>Statistics Preview</h3>
                <div class="statistics-preview-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">
                    <?php foreach ($statsList as $stat): ?>
                        <div class="statistic-preview" style="text-align: center; padding: 20px; background-color: #f8f9fa; border-radius: 5px;">
                            <i class="fas <?php echo htmlspecialchars($stat['icon']); ?>" style="font-size: 36px; color: #007bff; margin-bottom: 10px;"></i>
                            <div style="font-size: 36px; font-weight: bold; margin-bottom: 5px;"><?php echo htmlspecialchars($stat['value']); ?></div>
                            <div><?php echo htmlspecialchars($stat['title']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Statistic Form -->
            <div class="panel">
                <h3><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Statistic</h3>
                
                <form action="" method="post">
                    <?php if ($action === 'edit' && $editStat): ?>
                        <input type="hidden" name="id" value="<?php echo $editStat['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" value="<?php echo $action === 'edit' ? htmlspecialchars($editStat['title']) : ''; ?>" required>
                        <p class="note">Example: "Clients", "Projects", "Years Experience"</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="value">Value *</label>
                        <input type="text" id="value" name="value" value="<?php echo $action === 'edit' ? htmlspecialchars($editStat['value']) : ''; ?>" required>
                        <p class="note">Example: "150+", "500", "10+ Years"</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="icon">Icon *</label>
                        <input type="text" id="icon" name="icon" value="<?php echo $action === 'edit' ? htmlspecialchars($editStat['icon']) : ''; ?>" required>
                        <p class="note">Font Awesome icon class (e.g., fa-users, fa-chart-bar, fa-calendar-check)</p>
                        
                        <div class="icon-preview">
                            <i class="fas <?php echo $action === 'edit' ? htmlspecialchars($editStat['icon']) : 'fa-chart-bar'; ?>"></i>
                            <span>Icon preview</span>
                        </div>
                        
                        <!-- Common icons to choose from -->
                        <h4 style="margin-top: 20px; margin-bottom: 10px;">Common Icons:</h4>
                        <div class="icon-selector">
                            <div class="icon-option" data-icon="fa-users">
                                <i class="fas fa-users"></i>
                                <span>users</span>
                            </div>
                            <div class="icon-option" data-icon="fa-project-diagram">
                                <i class="fas fa-project-diagram"></i>
                                <span>project</span>
                            </div>
                            <div class="icon-option" data-icon="fa-calendar-check">
                                <i class="fas fa-calendar-check"></i>
                                <span>calendar</span>
                            </div>
                            <div class="icon-option" data-icon="fa-award">
                                <i class="fas fa-award"></i>
                                <span>award</span>
                            </div>
                            <div class="icon-option" data-icon="fa-globe">
                                <i class="fas fa-globe"></i>
                                <span>globe</span>
                            </div>
                            <div class="icon-option" data-icon="fa-handshake">
                                <i class="fas fa-handshake"></i>
                                <span>handshake</span>
                            </div>
                            <div class="icon-option" data-icon="fa-thumbs-up">
                                <i class="fas fa-thumbs-up"></i>
                                <span>thumbs-up</span>
                            </div>
                            <div class="icon-option" data-icon="fa-clock">
                                <i class="fas fa-clock"></i>
                                <span>clock</span>
                            </div>
                            <div class="icon-option" data-icon="fa-building">
                                <i class="fas fa-building"></i>
                                <span>building</span>
                            </div>
                            <div class="icon-option" data-icon="fa-chart-line">
                                <i class="fas fa-chart-line"></i>
                                <span>chart-line</span>
                            </div>
                            <div class="icon-option" data-icon="fa-certificate">
                                <i class="fas fa-certificate"></i>
                                <span>certificate</span>
                            </div>
                            <div class="icon-option" data-icon="fa-check-circle">
                                <i class="fas fa-check-circle"></i>
                                <span>check</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" min="0" value="<?php echo $action === 'edit' ? (int)$editStat['display_order'] : 0; ?>">
                        <p class="note">Statistics with lower numbers will be displayed first (0 = default)</p>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Add' : 'Update'; ?> Statistic</button>
                        <a href="?action=list" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Statistics List -->
            <div class="panel">
                <h3>Statistics</h3>
                
                <?php if (count($statsList) > 0): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Icon</th>
                                    <th>Title</th>
                                    <th>Value</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statsList as $stat): ?>
                                    <tr>
                                        <td><?php echo (int)$stat['display_order']; ?></td>
                                        <td><i class="fas <?php echo htmlspecialchars($stat['icon']); ?>"></i></td>
                                        <td><?php echo htmlspecialchars($stat['title']); ?></td>
                                        <td><?php echo htmlspecialchars($stat['value']); ?></td>
                                        <td class="action-buttons">
                                            <a href="?action=edit&id=<?php echo $stat['id']; ?>" title="Edit">
                                                <i class="fas fa-edit" style="color: #007bff;"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $stat['id']; ?>" onclick="return confirm('Are you sure you want to delete this statistic?');" title="Delete">
                                                <i class="fas fa-trash-alt" style="color: #dc3545;"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No statistics found. <a href="?action=add">Add your first statistic</a>.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Icon selector functionality
    document.addEventListener('DOMContentLoaded', function() {
        const iconInput = document.getElementById('icon');
        const iconPreview = document.querySelector('.icon-preview i');
        
        // Update icon preview when input changes
        if (iconInput) {
            iconInput.addEventListener('input', function() {
                iconPreview.className = 'fas ' + this.value;
            });
            
            // Set up icon selector options
            const iconOptions = document.querySelectorAll('.icon-option');
            iconOptions.forEach(function(option) {
                option.addEventListener('click', function() {
                    const iconClass = this.getAttribute('data-icon');
                    
                    // Update input and preview
                    iconInput.value = iconClass;
                    iconPreview.className = 'fas ' + iconClass;
                    
                    // Update selected state
                    iconOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                });
                
                // Set initial selected state
                if (iconInput.value === option.getAttribute('data-icon')) {
                    option.classList.add('selected');
                }
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>