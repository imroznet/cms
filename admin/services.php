<?php
require_once __DIR__ . '/../includes/security.php';
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Check if user has permission to manage services
if (!in_array('manage_services', $_SESSION['permissions'])) {
    header("Location: dashboard.php");
    exit;
}

// Set page-specific variables
define('INCLUDED', true);
$currentPage = 'services';
$pageTitle = 'Manage Services';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$messageType = '';

// Handle service deletion
if ($action === 'delete' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_GET['id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            $message = "Service deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error deleting service";
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
    $description = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $display_order = (int)($_POST['display_order'] ?? 0);
    
    // Validate form
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    if (empty($icon)) {
        $errors[] = "Icon is required";
    }
    
    if (empty($errors)) {
        try {
            if ($id > 0) {
                // Update existing service
                $stmt = $conn->prepare("UPDATE services SET title = ?, description = ?, icon = ?, display_order = ? WHERE id = ?");
                $result = $stmt->execute([$title, $description, $icon, $display_order, $id]);
                
                if ($result) {
                    $message = "Service updated successfully!";
                    $messageType = "success";
                    $action = 'list'; // Return to list view after update
                } else {
                    $message = "Error updating service";
                    $messageType = "error";
                }
            } else {
                // Add new service
                $stmt = $conn->prepare("INSERT INTO services (title, description, icon, display_order) VALUES (?, ?, ?, ?)");
                $result = $stmt->execute([$title, $description, $icon, $display_order]);
                
                if ($result) {
                    $message = "Service added successfully!";
                    $messageType = "success";
                    $action = 'list'; // Return to list view after addition
                } else {
                    $message = "Error adding service";
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

// Get service for editing
$editService = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$id]);
    $editService = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$editService) {
        $message = "Service not found";
        $messageType = "error";
        $action = 'list';
    }
}

// Get all services for listing
$servicesList = [];
if ($action === 'list') {
    $stmt = $conn->prepare("SELECT * FROM services ORDER BY display_order ASC, title ASC");
    $stmt->execute();
    $servicesList = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        
        <h1>Manage Services</h1>
        
        <?php if ($action === 'list'): ?>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Service</a>
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
            <!-- Add/Edit Service Form -->
            <div class="panel">
                <h3><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Service</h3>
                
                <form action="" method="post">
                    <?php if ($action === 'edit' && $editService): ?>
                        <input type="hidden" name="id" value="<?php echo $editService['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">Service Title *</label>
                        <input type="text" id="title" name="title" value="<?php echo $action === 'edit' ? htmlspecialchars($editService['title']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Service Description *</label>
                        <textarea id="description" name="description" required><?php echo $action === 'edit' ? htmlspecialchars($editService['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="icon">Service Icon *</label>
                        <input type="text" id="icon" name="icon" value="<?php echo $action === 'edit' ? htmlspecialchars($editService['icon']) : ''; ?>" required>
                        <p class="note">Font Awesome icon class (e.g., fa-cog, fa-robot, fa-chart-line)</p>
                        
                        <div class="icon-preview">
                            <i class="fas <?php echo $action === 'edit' ? htmlspecialchars($editService['icon']) : 'fa-cog'; ?>"></i>
                            <span>Icon preview</span>
                        </div>
                        
                        <!-- Common icons to choose from -->
                        <h4 style="margin-top: 20px; margin-bottom: 10px;">Common Icons:</h4>
                        <div class="icon-selector">
                            <div class="icon-option" data-icon="fa-cog">
                                <i class="fas fa-cog"></i>
                                <span>cog</span>
                            </div>
                            <div class="icon-option" data-icon="fa-robot">
                                <i class="fas fa-robot"></i>
                                <span>robot</span>
                            </div>
                            <div class="icon-option" data-icon="fa-chart-line">
                                <i class="fas fa-chart-line"></i>
                                <span>chart-line</span>
                            </div>
                            <div class="icon-option" data-icon="fa-code">
                                <i class="fas fa-code"></i>
                                <span>code</span>
                            </div>
                            <div class="icon-option" data-icon="fa-desktop">
                                <i class="fas fa-desktop"></i>
                                <span>desktop</span>
                            </div>
                            <div class="icon-option" data-icon="fa-mobile-alt">
                                <i class="fas fa-mobile-alt"></i>
                                <span>mobile-alt</span>
                            </div>
                            <div class="icon-option" data-icon="fa-database">
                                <i class="fas fa-database"></i>
                                <span>database</span>
                            </div>
                            <div class="icon-option" data-icon="fa-server">
                                <i class="fas fa-server"></i>
                                <span>server</span>
                            </div>
                            <div class="icon-option" data-icon="fa-cloud">
                                <i class="fas fa-cloud"></i>
                                <span>cloud</span>
                            </div>
                            <div class="icon-option" data-icon="fa-lightbulb">
                                <i class="fas fa-lightbulb"></i>
                                <span>lightbulb</span>
                            </div>
                            <div class="icon-option" data-icon="fa-tools">
                                <i class="fas fa-tools"></i>
                                <span>tools</span>
                            </div>
                            <div class="icon-option" data-icon="fa-seedling">
                                <i class="fas fa-seedling"></i>
                                <span>seedling</span>
                            </div>
                            <div class="icon-option" data-icon="fa-utensils">
                                <i class="fas fa-utensils"></i>
                                <span>utensils</span>
                            </div>
                            <div class="icon-option" data-icon="fa-industry">
                                <i class="fas fa-industry"></i>
                                <span>industry</span>
                            </div>
                            <div class="icon-option" data-icon="fa-tractor">
                                <i class="fas fa-tractor"></i>
                                <span>tractor</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" min="0" value="<?php echo $action === 'edit' ? (int)$editService['display_order'] : 0; ?>">
                        <p class="note">Services with lower numbers will be displayed first (0 = default)</p>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Add' : 'Update'; ?> Service</button>
                        <a href="?action=list" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Services List -->
            <div class="panel">
                <h3>Services</h3>
                
                <?php if (count($servicesList) > 0): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Icon</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($servicesList as $service): ?>
                                    <tr>
                                        <td><?php echo (int)$service['display_order']; ?></td>
                                        <td><i class="fas <?php echo htmlspecialchars($service['icon']); ?>"></i></td>
                                        <td><?php echo htmlspecialchars($service['title']); ?></td>
                                        <td><?php echo mb_substr(htmlspecialchars($service['description']), 0, 100) . (mb_strlen($service['description']) > 100 ? '...' : ''); ?></td>
                                        <td class="action-buttons">
                                            <a href="?action=edit&id=<?php echo $service['id']; ?>" title="Edit">
                                                <i class="fas fa-edit" style="color: #007bff;"></i>
                                            </a>
                                            <form method="post" action="?action=delete&id=<?php echo $service['id']; ?>" style="display:inline;margin-right:10px" onsubmit="return confirm('Are you sure you want to delete this service?');">
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
                    <p>No services found. <a href="?action=add">Add your first service</a>.</p>
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