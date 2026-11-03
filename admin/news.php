<?php
require_once __DIR__ . '/../includes/security.php';
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Check if user has permission to manage news
if (!in_array('manage_news', $_SESSION['permissions'])) {
    header("Location: dashboard.php");
    exit;
}

// Set page-specific variables
define('INCLUDED', true);
$currentPage = 'news';
$pageTitle = 'Manage News';

// Process actions
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$messageType = '';

// Delete news article
if ($action === 'delete' && $id > 0) {
    // Get image filename before deletion
    $stmt = $conn->prepare("SELECT image FROM news WHERE id = ?");
    $stmt->execute([$id]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    $image = $article['image'] ?? null;
    
    // Delete the article from database
    $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        // Delete associated image if exists
        if ($image && file_exists('../assets/images/uploads/' . $image)) {
            unlink('../assets/images/uploads/' . $image);
        }
        
        $message = "News article deleted successfully";
        $messageType = "success";
    } else {
        $message = "Error deleting news article";
        $messageType = "error";
    }
    
    $action = 'list';
}

// Get news article for editing
$editArticle = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([$id]);
    $editArticle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$editArticle) {
        $message = "News article not found";
        $messageType = "error";
        $action = 'list';
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['_confirm_action'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $publish_date = trim($_POST['publish_date'] ?? '');
    
    // Validate form
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($content)) {
        $errors[] = "Content is required";
    }
    
    if (empty($publish_date)) {
        $errors[] = "Publish date is required";
    }
    
    if (empty($errors)) {
        // Handle image upload
        $image = $id > 0 ? $editArticle['image'] : null; // Keep existing image by default
        
        if (!empty($_FILES['image']['name'])) {
            $upload_dir = '../assets/images/uploads/';
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
                
                // Delete old image if exists and it's not a default image
                if ($id > 0 && $editArticle['image'] && file_exists($upload_dir . $editArticle['image'])) {
                    unlink($upload_dir . $editArticle['image']);
                }
            } else {
                $errors[] = "Error uploading image";
            }
        }
        
        if (empty($errors)) {
            try {
                if ($id > 0) {
                    // Update existing article
                    if ($image) {
                        $stmt = $conn->prepare("UPDATE news SET title = ?, content = ?, publish_date = ?, image = ?, updated_at = NOW() WHERE id = ?");
                        $result = $stmt->execute([$title, $content, $publish_date, $image, $id]);
                    } else {
                        $stmt = $conn->prepare("UPDATE news SET title = ?, content = ?, publish_date = ?, updated_at = NOW() WHERE id = ?");
                        $result = $stmt->execute([$title, $content, $publish_date, $id]);
                    }
                    
                    if ($result) {
                        $message = "News article updated successfully";
                        $messageType = "success";
                        $action = 'list';
                    } else {
                        $message = "Error updating news article";
                        $messageType = "error";
                    }
                } else {
                    // Add new article
                    $stmt = $conn->prepare("INSERT INTO news (title, content, publish_date, image, created_by) VALUES (?, ?, ?, ?, ?)");
                    $result = $stmt->execute([$title, $content, $publish_date, $image, $_SESSION['user_id']]);
                    
                    if ($result) {
                        $message = "News article added successfully";
                        $messageType = "success";
                        $action = 'list';
                    } else {
                        $message = "Error adding news article";
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
    } else {
        $message = "Please correct the following errors: <ul><li>" . implode("</li><li>", $errors) . "</li></ul>";
        $messageType = "error";
    }
}

// Get news articles for list view
$articles = [];
if ($action === 'list') {
    // Pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    // Count total articles
    $stmt = $conn->query("SELECT COUNT(*) FROM news");
    $totalArticles = $stmt->fetchColumn();
    $totalPages = ceil($totalArticles / $perPage);
    
    // Get articles for current page
    $stmt = $conn->prepare("SELECT n.*, u.username FROM news n LEFT JOIN users u ON n.created_by = u.id ORDER BY n.publish_date DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        
        <h1>Manage News</h1>
        
        <?php if ($action === 'list'): ?>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Article</a>
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
            <!-- Add/Edit News Form -->
            <div class="panel">
                <h3><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> News Article</h3>
                
                <form action="" method="post" enctype="multipart/form-data">
                    <?php if ($action === 'edit' && $editArticle): ?>
                        <input type="hidden" name="id" value="<?php echo $editArticle['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" value="<?php echo $action === 'edit' ? htmlspecialchars($editArticle['title']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="publish_date">Publish Date *</label>
                        <input type="date" id="publish_date" name="publish_date" value="<?php echo $action === 'edit' ? date('Y-m-d', strtotime($editArticle['publish_date'])) : date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Featured Image <?php echo $action === 'add' ? '*' : ''; ?></label>
                        <input type="file" id="image" name="image" accept="image/*" <?php echo $action === 'add' ? 'required' : ''; ?>>
                        <p class="note">
                            Recommended size: 800x450 pixels. Max file size: 2MB.<br>
                            Supported formats: JPG, JPEG, PNG, GIF
                        </p>
                        
                        <?php if ($action === 'edit' && $editArticle['image']): ?>
                            <div style="margin-top: 10px;">
                                <p>Current image:</p>
                                <img src="../assets/images/uploads/<?php echo htmlspecialchars($editArticle['image']); ?>" alt="Current Image" style="max-width: 200px; max-height: 150px; margin-top: 10px; border: 1px solid #ddd;">
                                <p class="note">Leave the image field empty to keep the current image.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Content *</label>
                        <textarea id="content" name="content" rows="15" required><?php echo $action === 'edit' ? htmlspecialchars($editArticle['content']) : ''; ?></textarea>
                        <p class="note">
                            You can use basic HTML tags for formatting.<br>
                            Use &lt;p&gt; for paragraphs, &lt;h2&gt;-&lt;h6&gt; for headings, &lt;strong&gt; for bold, &lt;em&gt; for italic, &lt;ul&gt; and &lt;li&gt; for lists.
                        </p>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Add' : 'Update'; ?> Article</button>
                        <a href="?action=list" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- News Articles List -->
            <div class="panel">
                <h3>News Articles</h3>
                
                <?php if (count($articles) > 0): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Publish Date</th>
                                    <th>Author</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($articles as $article): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($article['title']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($article['publish_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($article['username'] ?? 'Unknown'); ?></td>
                                        <td class="action-buttons">
                                            <a href="?action=edit&id=<?php echo $article['id']; ?>" title="Edit">
                                                <i class="fas fa-edit" style="color: #007bff;"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $article['id']; ?>" onclick="return confirm('Are you sure you want to delete this article?');" title="Delete">
                                                <i class="fas fa-trash-alt" style="color: #dc3545;"></i>
                                            </a>
                                            <a href="../news-detail/<?php echo $article['id']; ?>" target="_blank" title="View">
                                                <i class="fas fa-eye" style="color: #28a745;"></i>
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
                    <p>No news articles found. <a href="?action=add">Add your first article</a>.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>