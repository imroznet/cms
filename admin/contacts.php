<?php
require_once __DIR__ . '/../includes/security.php';
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Check if user has permission to manage contacts
if (!in_array('manage_contacts', $_SESSION['permissions'])) {
    header("Location: dashboard.php");
    exit;
}

// Set page-specific variables
define('INCLUDED', true);
$currentPage = 'contacts';
$pageTitle = 'Contact Messages';

// Default values
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$messageType = '';
$contactMessage = null;

// Mark message as read
if ($action === 'view' && $id > 0) {
    $stmt = $conn->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
    $stmt->execute([$id]);
    
    $stmt = $conn->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->execute([$id]);
    $contactMessage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contactMessage) {
        $message = "Message not found";
        $messageType = "error";
        $action = 'list';
    }
}

// Delete message
if ($action === 'delete' && $id > 0) {
    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        $message = "Message deleted successfully";
        $messageType = "success";
    } else {
        $message = "Error deleting message";
        $messageType = "error";
    }
    
    $action = 'list';
}

// Get messages for list view
$messages = [];
if ($action === 'list') {
    // Pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    
    // Count total messages
    $stmt = $conn->query("SELECT COUNT(*) FROM contact_messages");
    $totalMessages = $stmt->fetchColumn();
    $totalPages = ceil($totalMessages / $perPage);
    
    // Get messages for current page
    $stmt = $conn->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        
        <h1>Contact Messages</h1>
        
        <div></div> <!-- Empty div for flex spacing -->
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'view' && $contactMessage): ?>
            <!-- View Single Message -->
            <div class="panel">
                <h3>Message Details</h3>
                
                <div class="message-details">
                    <div class="message-info">
                        <p><strong>From:</strong> <?php echo htmlspecialchars($contactMessage['name']); ?> (<?php echo htmlspecialchars($contactMessage['email']); ?>)</p>
                        <p><strong>Subject:</strong> <?php echo htmlspecialchars($contactMessage['subject']); ?></p>
                        <p><strong>Date:</strong> <?php echo date('F j, Y g:i a', strtotime($contactMessage['created_at'])); ?></p>
                    </div>
                    
                    <div class="message-content" style="margin-top: 20px; padding: 20px; background-color: #f8f9fa; border-radius: 5px;">
                        <?php echo nl2br(htmlspecialchars($contactMessage['message'])); ?>
                    </div>
                    
                    <div class="message-actions" style="margin-top: 20px; display: flex; gap: 10px;">
                        <a href="?action=list" class="btn btn-secondary">Back to List</a>
                        <a href="mailto:<?php echo htmlspecialchars($contactMessage['email']); ?>?subject=Re: <?php echo htmlspecialchars($contactMessage['subject']); ?>" class="btn btn-primary">Reply via Email</a>
                        <a href="?action=delete&id=<?php echo $contactMessage['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this message?');">Delete Message</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Messages List -->
            <div class="panel">
                <h3>Contact Messages</h3>
                
                <?php if (count($messages) > 0): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Subject</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $msg): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($msg['name']); ?></td>
                                        <td><a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>"><?php echo htmlspecialchars($msg['email']); ?></a></td>
                                        <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($msg['created_at'])); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $msg['is_read'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $msg['is_read'] ? 'Read' : 'Unread'; ?>
                                            </span>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="?action=view&id=<?php echo $msg['id']; ?>" title="View">
                                                <i class="fas fa-eye" style="color: #007bff;"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $msg['id']; ?>" onclick="return confirm('Are you sure you want to delete this message?');" title="Delete">
                                                <i class="fas fa-trash-alt" style="color: #dc3545;"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination" style="margin-top: 20px; text-align: center;">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">&laquo; Previous</a>
                            <?php endif; ?>
                            
                            <span style="margin: 0 15px;">
                                Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                            </span>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p>No contact messages found.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>