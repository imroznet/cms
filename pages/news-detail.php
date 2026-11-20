<?php
// Security check to prevent direct access
if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Get news ID from URL
$newsId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get news article details
$stmt = $conn->prepare("SELECT * FROM news WHERE id = ?" . publishedNewsSql($conn));
$stmt->execute([$newsId]);
$news = $stmt->fetch(PDO::FETCH_ASSOC);

// If news article not found, redirect to news page
if (!$news) {
    header("Location: news");
    exit;
}

// Get author details
$authorName = "Admin";
if (!empty($news['created_by'])) {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$news['created_by']]);
    $author = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($author) {
        $authorName = $author['username'];
    }
}
?>

<!-- Page Header -->
<section class="page-header" style="background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('<?php echo publicAsset('assets/images/news-header.jpg'); ?>') no-repeat center center/cover;">
    <div class="container">
        <h1><?php echo $news['title']; ?></h1>
        <p><?php echo date('F j, Y', strtotime($news['publish_date'])); ?></p>
    </div>
</section>

<!-- News Detail Section -->
<section class="section">
    <div class="container">
        <div class="news-detail" style="max-width: 800px; margin: 0 auto;">
            <?php if (!empty($news['image'])): ?>
                <div class="news-image" style="margin-bottom: 30px;">
                    <img src="<?php echo htmlspecialchars(publicAsset('assets/images/uploads/' . $news['image'])); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>" style="width: 100%; height: auto; object-fit: contain; background: #f8f9fa; border-radius: 5px;">
                </div>
            <?php endif; ?>
            
            <div class="news-meta" style="margin-bottom: 20px; color: #6c757d;">
                <span><i class="fas fa-calendar"></i> <?php echo date('F j, Y', strtotime($news['publish_date'])); ?></span>
                <span style="margin-left: 15px;"><i class="fas fa-user"></i> <?php echo $authorName; ?></span>
            </div>
            
            <div class="news-content" style="line-height: 1.8;">
                <?php echo nl2br($news['content']); ?>
            </div>
            
            <div class="news-navigation" style="margin-top: 40px; display: flex; justify-content: space-between; border-top: 1px solid #eee; padding-top: 20px;">
                <a href="news" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to News</a>
                
                <!-- Share buttons -->
                <div class="share-buttons">
                    <span style="margin-right: 10px;">Share:</span>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" style="color: #3b5998; margin-right: 10px;"><i class="fab fa-facebook"></i></a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($news['title']); ?>" target="_blank" style="color: #1da1f2; margin-right: 10px;"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" style="color: #0077b5;"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.page-header {
    color: white;
    padding: 80px 0;
    text-align: center;
    margin-bottom: 40px;
}

.page-header h1 {
    font-size: 2.5rem;
    margin-bottom: 10px;
}

.news-content {
    font-size: 1.1rem;
}

.news-content p {
    margin-bottom: 20px;
}

.news-content img {
    max-width: 100%;
    height: auto;
    margin: 20px 0;
}

.share-buttons a {
    font-size: 1.5rem;
    transition: opacity 0.3s;
}

.share-buttons a:hover {
    opacity: 0.8;
}
</style>