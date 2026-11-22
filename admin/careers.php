<?php
require_once __DIR__ . '/../includes/security.php';
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Check if user has permission to manage careers
if (!in_array('manage_careers', $_SESSION['permissions'])) {
    header("Location: dashboard.php");
    exit;
}

// Set page-specific variables
define('INCLUDED', true);
$currentPage = 'careers';
$pageTitle = 'Manage Careers';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$messageType = '';

// Handle career deletion
if ($action === 'delete' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_GET['id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM careers WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            $message = "Career listing deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error deleting career listing";
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
    $position = trim($_POST['position'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $posted_date = trim($_POST['posted_date'] ?? '');
    $closing_date = trim($_POST['closing_date'] ?? '');
    
    // Validate form
    $errors = [];
    
    if (empty($position)) {
        $errors[] = "Position title is required";
    }
    
    if (empty($description)) {
        $errors[] = "Job description is required";
    }
    
    if (empty($location)) {
        $errors[] = "Job location is required";
    }
    
    if (empty($posted_date)) {
        $errors[] = "Posted date is required";
    }
    
    if (empty($errors)) {
        try {
            if ($id > 0) {
                // Update existing career listing
                if (!empty($closing_date)) {
                    $stmt = $conn->prepare("UPDATE careers SET position = ?, description = ?, requirements = ?, location = ?, is_active = ?, posted_date = ?, closing_date = ? WHERE id = ?");
                    $result = $stmt->execute([$position, $description, $requirements, $location, $is_active, $posted_date, $closing_date, $id]);
                } else {
                    $stmt = $conn->prepare("UPDATE careers SET position = ?, description = ?, requirements = ?, location = ?, is_active = ?, posted_date = ?, closing_date = NULL WHERE id = ?");
                    $result = $stmt->execute([$position, $description, $requirements, $location, $is_active, $posted_date, $id]);
                }
                
                if ($result) {
                    $message = "Career listing updated successfully!";
                    $messageType = "success";
                    $action = 'list'; // Return to list view after update
                } else {
                    $message = "Error updating career listing";
                    $messageType = "error";
                }
            } else {
                // Add new career listing
                if (!empty($closing_date)) {
                    $stmt = $conn->prepare("INSERT INTO careers (position, description, requirements, location, is_active, posted_date, closing_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $result = $stmt->execute([$position, $description, $requirements, $location, $is_active, $posted_date, $closing_date]);
                } else {
                    $stmt = $conn->prepare("INSERT INTO careers (position, description, requirements, location, is_active, posted_date) VALUES (?, ?, ?, ?, ?, ?)");
                    $result = $stmt->execute([$position, $description, $requirements, $location, $is_active, $posted_date]);
                }
                
                if ($result) {
                    $message = "Career listing added successfully!";
                    $messageType = "success";
                    $action = 'list'; // Return to list view after addition
                } else {
                    $message = "Error adding career listing";
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

// Get career listing for editing
$editCareer = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT * FROM careers WHERE id = ?");
    $stmt->execute([$id]);
    $editCareer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$editCareer) {
        $message = "Career listing not found";
        $messageType = "error";
        $action = 'list';
    }
}

// Get all career listings for listing
$careersList = [];
if ($action === 'list') {
    $stmt = $conn->prepare("SELECT * FROM careers ORDER BY is_active DESC, posted_date DESC");
    $stmt->execute();
    $careersList = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        
        <h1>Manage Careers</h1>
        
        <?php if ($action === 'list'): ?>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Job</a>
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
            <!-- Add/Edit Career Form -->
            <div class="panel">
                <h3><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Job Listing</h3>
                
                <form action="" method="post">
                    <?php if ($action === 'edit' && $editCareer): ?>
                        <input type="hidden" name="id" value="<?php echo $editCareer['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="position">Job Position *</label>
                        <input type="text" id="position" name="position" value="<?php echo $action === 'edit' ? htmlspecialchars($editCareer['position']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Job Location *</label>
                        <input type="text" id="location" name="location" value="<?php echo $action === 'edit' ? htmlspecialchars($editCareer['location']) : ''; ?>" required>
                        <p class="note">Example: "New York, NY" or "Remote"</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="posted_date">Posted Date *</label>
                        <input type="date" id="posted_date" name="posted_date" value="<?php echo $action === 'edit' ? date('Y-m-d', strtotime($editCareer['posted_date'])) : date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="closing_date">Closing Date</label>
                        <input type="date" id="closing_date" name="closing_date" value="<?php echo ($action === 'edit' && !empty($editCareer['closing_date'])) ? date('Y-m-d', strtotime($editCareer['closing_date'])) : ''; ?>">
                        <p class="note">Leave blank if there is no closing date</p>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="is_active" name="is_active" <?php echo ($action === 'edit' && $editCareer['is_active']) ? 'checked' : ''; ?> <?php echo $action === 'add' ? 'checked' : ''; ?>>
                            <label for="is_active">Active Listing</label>
                        </div>
                        <p class="note">Uncheck to hide this job from the website</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Job Description *</label>
                        <textarea id="description" name="description" required><?php echo $action === 'edit' ? htmlspecialchars($editCareer['description']) : ''; ?></textarea>
                        <p class="note">Describe the job role, responsibilities, etc.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="requirements">Job Requirements</label>
                        <textarea id="requirements" name="requirements"><?php echo $action === 'edit' ? htmlspecialchars($editCareer['requirements']) : ''; ?></textarea>
                        <p class="note">Skills, qualifications, experience required, etc.</p>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Add' : 'Update'; ?> Job</button>
                        <a href="?action=list" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Careers List -->
            <div class="panel">
                <h3>Job Listings</h3>
                
                <?php if (count($careersList) > 0): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Position</th>
                                    <th>Location</th>
                                    <th>Posted Date</th>
                                    <th>Closing Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($careersList as $job): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($job['position']); ?></td>
                                        <td><?php echo htmlspecialchars($job['location']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($job['posted_date'])); ?></td>
                                        <td>
                                            <?php echo !empty($job['closing_date']) ? date('Y-m-d', strtotime($job['closing_date'])) : 'No closing date'; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $job['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $job['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="?action=edit&id=<?php echo $job['id']; ?>" title="Edit">
                                                <i class="fas fa-edit" style="color: #007bff;"></i>
                                            </a>
                                            <form method="post" action="?action=delete&id=<?php echo $job['id']; ?>" style="display:inline;margin-right:10px" onsubmit="return confirm('Are you sure you want to delete this job listing?');">
                                                <input type="hidden" name="_confirm_action" value="1">
                                                <button type="submit" title="Delete" style="background:none;border:0;padding:0;cursor:pointer;">
                                                    <i class="fas fa-trash-alt" style="color: #dc3545;"></i>
                                                </button>
                                            </form>
                                            <a href="../career-detail/<?php echo $job['id']; ?>" target="_blank" title="View">
                                                <i class="fas fa-eye" style="color: #28a745;"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No job listings found. <a href="?action=add">Add your first job listing</a>.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>