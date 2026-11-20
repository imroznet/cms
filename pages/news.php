<?php
// Security check to prevent direct access
if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Pagination settings
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$perPage = 6;
$offset = ($page - 1) * $perPage;

// Get total number of news articles
$published = publishedNewsSql($conn);
$stmt = $conn->query("SELECT COUNT(*) FROM news WHERE 1 = 1" . $published);
$totalNews = $stmt->fetchColumn();
$totalPages = (int) ceil($totalNews / $perPage);

// Get news articles with pagination
$stmt = $conn->prepare("SELECT * FROM news WHERE 1 = 1" . $published . " ORDER BY publish_date DESC, id DESC LIMIT ? OFFSET ?");
$stmt->bindParam(1, $perPage, PDO::PARAM_INT);
$stmt->bindParam(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$newsArticles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Page Header -->
<section class="page-header" style="background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('<?php echo publicAsset('assets/images/news-header.jpg'); ?>') no-repeat center center/cover;">
    <div class="container">
        <h1>News & Updates</h1>
        <p>Stay informed about our latest developments</p>
    </div>
</section>

<!-- News Section -->
<section class="section">
    <div class="container">
        <div class="news-grid">
            <?php if (count($newsArticles) > 0): ?>
                <?php foreach ($newsArticles as $news): ?>
                    <div class="news-card">
                        <?php if (!empty($news['image'])): ?>
                            <img src="<?php echo htmlspecialchars(publicAsset('assets/images/uploads/' . $news['image'])); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>">
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars(publicAsset('assets/images/news-placeholder.jpg')); ?>" alt="News">
                        <?php endif; ?>
                        <div class="news-card-content">
                            <div class="news-date"><?php echo date('F j, Y', strtotime($news['publish_date'])); ?></div>
                            <h3><?php echo htmlspecialchars($news['title']); ?></h3>
                            <p><?php echo htmlspecialchars(substr(strip_tags($news['content']), 0, 150)) . '...'; ?></p>
                            <a href="news-detail/<?php echo $news['id']; ?>" class="btn btn-secondary">Read More</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-news" style="grid-column: 1 / -1; text-align: center; padding: 50px 0;">
                    <i class="fas fa-newspaper" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                    <h3>No News Articles Yet</h3>
                    <p>Check back soon for updates and news about our company and services.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination" style="margin-top: 40px; text-align: center;">
                <?php if ($page > 1): ?>
                    <a href="news?p=<?php echo $page - 1; ?>" class="pagination-item">&laquo; Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="pagination-item active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="news?p=<?php echo $i; ?>" class="pagination-item"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="news?p=<?php echo $page + 1; ?>" class="pagination-item">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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

.news-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
}

.news-card {
    background: white;
    border-radius: 5px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.news-card img {
    width: 100%;
    height: 220px;
    object-fit: contain;
    object-position: center;
    background: #f8f9fa;
}

.news-card-content {
    padding: 20px;
}

.news-date {
    color: #6c757d;
    font-size: 14px;
    margin-bottom: 10px;
}

.pagination {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 10px;
}

.pagination-item {
    display: inline-block;
    padding: 8px 16px;
    background-color: #f8f9fa;
    color: #007bff;
    border-radius: 4px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.pagination-item:hover {
    background-color: #e9ecef;
}

.pagination-item.active {
    background-color: #007bff;
    color: white;
}
</style>