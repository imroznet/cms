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
$currentPage = 'dashboard';
$pageTitle = 'Dashboard';

// Get statistics for dashboard
$stats = [
    'messages' => 0,
    'news' => 0,
    'careers' => 0,
    'services' => 0
];

// Count unread contact messages
$stmt = $conn->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");
$stats['messages'] = $stmt->fetchColumn();

// Count news articles
$stmt = $conn->query("SELECT COUNT(*) FROM news");
$stats['news'] = $stmt->fetchColumn();

// Count active job listings
$stmt = $conn->query("SELECT COUNT(*) FROM careers WHERE is_active = 1");
$stats['careers'] = $stmt->fetchColumn();

// Count services
$stmt = $conn->query("SELECT COUNT(*) FROM services");
$stats['services'] = $stmt->fetchColumn();

// Get recent contact messages
$stmt = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5");
$recentMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        
        <h1>Dashboard</h1>
        
        <div></div> <!-- Empty div for flex spacing -->
    </div>
    
    <div class="container">
        <!-- Dashboard Stats -->
        <div class="dashboard-stats">
            <?php if (in_array('manage_contacts', $_SESSION['permissions'])): ?>
            <div class="stat-card">
                <i class="fas fa-envelope fa-2x"></i>
                <div class="number"><?php echo $stats['messages']; ?></div>
                <div class="stat-title">Unread Messages</div>
                <a href="contacts.php" class="btn btn-primary btn-sm">View All</a>
            </div>
            <?php endif; ?>
            
            <?php if (in_array('manage_news', $_SESSION['permissions'])): ?>
            <div class="stat-card">
                <i class="fas fa-newspaper fa-2x"></i>
                <div class="number"><?php echo $stats['news']; ?></div>
                <div class="stat-title">News Articles</div>
                <a href="news.php" class="btn btn-primary btn-sm">Manage</a>
            </div>
            <?php endif; ?>
            
            <?php if (in_array('manage_careers', $_SESSION['permissions'])): ?>
            <div class="stat-card">
                <i class="fas fa-briefcase fa-2x"></i>
                <div class="number"><?php echo $stats['careers']; ?></div>
                <div class="stat-title">Active Job Listings</div>
                <a href="careers.php" class="btn btn-primary btn-sm">Manage</a>
            </div>
            <?php endif; ?>
            
            <?php if (in_array('manage_services', $_SESSION['permissions'])): ?>
            <div class="stat-card">
                <i class="fas fa-cogs fa-2x"></i>
                <div class="number"><?php echo $stats['services']; ?></div>
                <div class="stat-title">Services</div>
                <a href="services.php" class="btn btn-primary btn-sm">Manage</a>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Messages -->
        <?php if (in_array('manage_contacts', $_SESSION['permissions']) && count($recentMessages) > 0): ?>
        <div class="panel">
            <h3>Recent Contact Messages</h3>
            
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentMessages as $message): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($message['name']); ?></td>
                                <td><?php echo htmlspecialchars($message['email']); ?></td>
                                <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($message['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $message['is_read'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $message['is_read'] ? 'Read' : 'Unread'; ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <a href="contacts.php?action=view&id=<?php echo $message['id']; ?>" title="View">
                                        <i class="fas fa-eye" style="color: #007bff;"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="text-align: right; margin-top: 20px;">
                <a href="contacts.php" class="btn btn-secondary">View All Messages</a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Welcome Panel -->
        <div class="panel">
            <h3>Welcome to the Admin Panel</h3>
            <p>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>! This is your dashboard for managing website content.</p>
            
            <p>From here you can:</p>
            <ul style="margin-left: 20px; margin-bottom: 20px;">
                <?php if (in_array('manage_settings', $_SESSION['permissions'])): ?>
                <li>Update site settings and contact information</li>
                <li>Manage social media links</li>
                <?php endif; ?>
                <?php if (in_array('manage_news', $_SESSION['permissions'])): ?>
                <li>Add and edit news articles</li>
                <?php endif; ?>
                <?php if (in_array('manage_services', $_SESSION['permissions'])): ?>
                <li>Manage service offerings</li>
                <?php endif; ?>
                <?php if (in_array('manage_careers', $_SESSION['permissions'])): ?>
                <li>Post job opportunities</li>
                <?php endif; ?>
                <?php if (in_array('manage_contacts', $_SESSION['permissions'])): ?>
                <li>View and respond to contact messages</li>
                <?php endif; ?>
                <?php if (in_array('manage_statistics', $_SESSION['permissions'])): ?>
                <li>Update company statistics</li>
                <?php endif; ?>
            </ul>
            
            <p>If you need any assistance, please contact the system administrator.</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>