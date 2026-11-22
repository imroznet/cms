<?php
if (!defined('INCLUDED')) {
    exit('Direct access not permitted');
}
?>
<!-- Mobile toggle button (outside the sidebar) -->
<div class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>Admin Panel</h2>
        <!-- Only show the close button on mobile -->
        <button class="close-sidebar" id="closeSidebar" style="display: none;">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <!-- Added this sidebar-menu-container to enable scrolling -->
    <div class="sidebar-menu-container">
        <div class="sidebar-menu">
            <ul>
                <li><a href="dashboard.php" <?php echo $currentPage === 'dashboard' ? 'class="active"' : ''; ?>><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                
                <?php if (in_array('manage_settings', $_SESSION['permissions'])): ?>
                <li><a href="settings.php" <?php echo $currentPage === 'settings' ? 'class="active"' : ''; ?>><i class="fas fa-cog"></i> Site Settings</a></li>
                <li><a href="documents.php" <?php echo $currentPage === 'documents' ? 'class="active"' : ''; ?>><i class="fas fa-file-alt"></i> Documents</a></li>
                <li><a href="achievements.php" <?php echo $currentPage === 'achievements' ? 'class="active"' : ''; ?>><i class="fas fa-trophy"></i> Achievements</a></li>
                <li><a href="social.php" <?php echo $currentPage === 'social' ? 'class="active"' : ''; ?>><i class="fas fa-share-alt"></i> Social Links</a></li>
                <li><a href="images.php" <?php echo $currentPage === 'images' ? 'class="active"' : ''; ?>><i class="fas fa-images"></i> Image Management</a></li>
                <?php endif; ?>
                
                <?php if (in_array('manage_news', $_SESSION['permissions'])): ?>
                <li><a href="news.php" <?php echo $currentPage === 'news' ? 'class="active"' : ''; ?>><i class="fas fa-newspaper"></i> News</a></li>
                <?php endif; ?>
                
                <?php if (in_array('manage_services', $_SESSION['permissions'])): ?>
                <li><a href="services.php" <?php echo $currentPage === 'services' ? 'class="active"' : ''; ?>><i class="fas fa-cogs"></i> Services</a></li>
                <?php endif; ?>
                
                <?php if (in_array('manage_testimonials', $_SESSION['permissions'])): ?>
                <li><a href="testimonials.php" <?php echo $currentPage === 'testimonials' ? 'class="active"' : ''; ?>><i class="fas fa-quote-right"></i> Testimonials</a></li>
                <?php endif; ?>
                
                <?php if (in_array('manage_team', $_SESSION['permissions'])): ?>
                <li><a href="team.php" <?php echo $currentPage === 'team' ? 'class="active"' : ''; ?>><i class="fas fa-users"></i> Team Members</a></li>
                <?php endif; ?>
                
                <?php if (in_array('manage_careers', $_SESSION['permissions'])): ?>
                <li><a href="careers.php" <?php echo $currentPage === 'careers' ? 'class="active"' : ''; ?>><i class="fas fa-briefcase"></i> Careers</a></li>
                <?php endif; ?>
                
                <?php if (in_array('manage_contacts', $_SESSION['permissions'])): ?>
                <li><a href="contacts.php" <?php echo $currentPage === 'contacts' ? 'class="active"' : ''; ?>><i class="fas fa-envelope"></i> Contact Messages</a></li>
                <?php endif; ?>
                
                <?php if (in_array('manage_statistics', $_SESSION['permissions'])): ?>
                <li><a href="statistics.php" <?php echo $currentPage === 'statistics' ? 'class="active"' : ''; ?>><i class="fas fa-chart-bar"></i> Statistics</a></li>
                <?php endif; ?>
                
                <?php if (in_array('manage_users', $_SESSION['permissions']) && $_SESSION['user_role'] === 'admin'): ?>
                <li><a href="users.php" <?php echo $currentPage === 'users' ? 'class="active"' : ''; ?>><i class="fas fa-user-shield"></i> Users</a></li>
                <?php endif; ?>
                
                <li><a href="profile.php" <?php echo $currentPage === 'profile' ? 'class="active"' : ''; ?>><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const closeSidebar = document.getElementById('closeSidebar');
    
    // Only show close button on mobile
    function updateCloseButtonVisibility() {
        if (window.innerWidth < 768) {
            closeSidebar.style.display = 'block';
        } else {
            closeSidebar.style.display = 'none';
        }
    }
    
    // Call initially
    updateCloseButtonVisibility();
    
    // Toggle sidebar on mobile
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
    
    // Close sidebar on mobile
    closeSidebar.addEventListener('click', function() {
        sidebar.classList.remove('active');
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const isClickInside = sidebar.contains(event.target) || sidebarToggle.contains(event.target);
        if (!isClickInside && sidebar.classList.contains('active') && window.innerWidth < 768) {
            sidebar.classList.remove('active');
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            sidebar.classList.remove('active');
        }
        updateCloseButtonVisibility();
    });
});
</script>