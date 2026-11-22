<?php
// Security check to prevent direct access
if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Get featured services
$services = getServices($conn, 3);
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1><?php echo htmlspecialchars($settings['homepage_title'] ?? 'Welcome to Our Company'); ?></h1>
        <p><?php echo htmlspecialchars($settings['homepage_subtitle'] ?? 'Professional services for your business needs'); ?></p>
        <div class="hero-buttons">
            <a href="services" class="btn">Our Services</a>
            <a href="contact" class="btn btn-secondary">Contact Us</a>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="section">
    <div class="container">
        <div class="about-home">
            <div class="about-content">
                <div class="section-title" style="text-align: left;">
                    <h2>About Us</h2>
                </div>
                <p><?php echo !empty($settings['homepage_content']) ? nl2br(htmlspecialchars($settings['homepage_content'])) : 'Welcome to ' . htmlspecialchars($settings['company_name'] ?? 'our company') . '. We are a professional team dedicated to providing high-quality services to our clients. Our mission is to deliver innovative solutions that help businesses grow and succeed.'; ?></p>
                <a href="about" class="btn">Learn More</a>
            </div>
            <div class="about-image">
                <img src="assets/images/about-home.jpg" alt="About Our Company">
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="section" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="section-title">
            <h2>Our Services</h2>
            <p>We offer a wide range of professional services to meet your business needs</p>
        </div>
        
        <div class="services-grid">
            <?php if (count($services) > 0): ?>
                <?php foreach ($services as $service): ?>
                    <div class="service-card">
                        <i class="fas <?php echo htmlspecialchars($service['icon']); ?>"></i>
                        <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                        <p><?php echo substr(strip_tags($service['description']), 0, 100) . '...'; ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Services are added from the site administration. None are published yet.</p>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="services" class="btn">View All Services</a>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<?php
$statistics = array_values(array_filter(getStatistics($conn), static function (array $stat): bool {
    $numeric = preg_replace('/[^0-9.\-]/', '', (string) ($stat['value'] ?? ''));

    return $numeric !== '' && (float) $numeric !== 0.0;
}));
if (count($statistics) > 0):
?>
<section class="statistics">
    <div class="container">
        <div class="statistics-grid">
            <?php foreach ($statistics as $stat): 
                // prepare numeric-only target and suffix (like + or %)
                $rawValue = (string)$stat['value'];
                $numeric = preg_replace('/[^0-9.\-]/', '', $rawValue); // keep digits, dot, minus
                $numeric = $numeric === '' ? '0' : $numeric;
                $suffix = preg_replace('/[0-9.\-\,]/', '', $rawValue); // characters that are not part of numeric
            ?>
                <div class="statistic">
                    <i class="fas <?php echo htmlspecialchars($stat['icon']); ?>"></i>
                    <div class="number"
                         data-target="<?php echo htmlspecialchars($numeric); ?>"
                         data-suffix="<?php echo htmlspecialchars($suffix); ?>"
                         aria-live="polite">0</div>
                    <div class="title"><?php echo htmlspecialchars($stat['title']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
$achievementSlides = array_values(array_filter(getAchievements($conn, true), static function (array $achievement): bool {
    return storedUploadName($achievement['photo'] ?? null) !== null;
}));
if (count($achievementSlides) > 0):
?>
<!-- Achievements Section -->
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Achievements</h2>
        </div>
        <div class="achievements-slider" data-achievements-slider>
            <?php foreach ($achievementSlides as $index => $achievement): ?>
                <div class="achievement-slide<?php echo $index === 0 ? ' active' : ''; ?>">
                    <img src="<?php echo htmlspecialchars(publicAsset('assets/images/uploads/' . $achievement['photo'])); ?>" alt="<?php echo htmlspecialchars((string) ($achievement['title'] ?? '')); ?>">
                    <h3><?php echo htmlspecialchars((string) ($achievement['title'] ?? '')); ?></h3>
                    <?php if (trim((string) ($achievement['year'] ?? '')) !== ''): ?>
                        <p class="achievement-year"><?php echo (int) $achievement['year']; ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <button type="button" class="achievement-slider-prev" data-achievements-prev aria-label="Previous achievement">&#8249;</button>
            <button type="button" class="achievement-slider-next" data-achievements-next aria-label="Next achievement">&#8250;</button>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
$latestNews = [];
try {
    $newsSql = "SELECT * FROM news WHERE 1 = 1" . publishedNewsSql($conn) . " ORDER BY publish_date DESC, id DESC LIMIT 3";
    $latestNews = $conn->query($newsSql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $latestNews = [];
}
if (count($latestNews) > 0):
?>
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>News</h2>
        </div>
        <div class="news-grid">
            <?php foreach ($latestNews as $news): ?>
                <article class="news-card">
                    <?php if (!empty($news['image'])): ?>
                        <img src="<?php echo htmlspecialchars(publicAsset('assets/images/uploads/' . $news['image'])); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>">
                    <?php endif; ?>
                    <div class="news-card-content">
                        <div class="news-date"><?php echo date('F j, Y', strtotime($news['publish_date'])); ?></div>
                        <h3><?php echo htmlspecialchars($news['title']); ?></h3>
                        <p><?php echo htmlspecialchars(substr(strip_tags($news['content']), 0, 150)); ?>...</p>
                        <a href="news-detail/<?php echo (int) $news['id']; ?>" class="btn btn-secondary">Read more</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div style="text-align: center; margin-top: 40px;">
            <a href="news" class="btn">All news</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Call to Action -->
<section class="cta">
    <div class="container">
        <h2>Ready to work with us?</h2>
        <p>Contact our team today to discuss your needs and how we can help.</p>
        <a href="contact" class="btn">Get in Touch</a>
    </div>
</section>

<style>
/* Hero Section */
.hero {
    background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('assets/images/hero-bg.jpg') no-repeat center center/cover;
    height: 80vh;
    color: white;
    display: flex;
    align-items: center;
    text-align: center;
}

.hero-content {
    width: 100%;
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
}

.hero h1 {
    font-size: 48px;
    margin-bottom: 20px;
}

.hero p {
    font-size: 20px;
    margin-bottom: 30px;
}

.hero-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
}

/* About Home */
.about-home {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    align-items: center;
}

.about-content {
    padding-right: 20px;
}

.about-image img {
    width: 100%;
    border-radius: 5px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

/* Statistics Section - centered on screen */
.statistics {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 60px 0;
    min-height: 40vh; /* adjust this value if you want more or less vertical height */
    background: transparent;
}

.statistics .container {
    width: 100%;
    max-width: 1100px;
}

/* Make the grid centered and responsive */
.statistics-grid {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 30px;
    flex-wrap: wrap;
}

/* Individual stat cards */
.statistic {
    text-align: center;
    flex: 0 1 200px;
    padding: 20px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.95);
    box-shadow: 0 6px 18px rgba(0,0,0,0.06);
}

.statistic i {
    font-size: 32px;
    color: #007bff;
    margin-bottom: 10px;
}

.statistic .number {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 6px;
    color: #333;
}

.statistic .title {
    font-size: 14px;
    color: #666;
}

/* CTA Section */
.cta {
    background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('assets/images/cta-bg.jpg') no-repeat center center/cover;
    color: white;
    text-align: center;
    padding: 80px 0;
}

.cta h2 {
    font-size: 36px;
    margin-bottom: 20px;
}

.cta p {
    font-size: 18px;
    max-width: 600px;
    margin: 0 auto 30px;
}

/* Achievements slider */
.achievements-slider {
    position: relative;
    max-width: 800px;
    margin: 0 auto;
    overflow: hidden;
    border-radius: 5px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.achievement-slide {
    display: none;
    text-align: center;
    background: #fff;
    padding-bottom: 20px;
}

.achievement-slide.active {
    display: block;
}

.achievement-slide img {
    width: 100%;
    height: 420px;
    object-fit: cover;
}

.achievement-slide h3 {
    margin: 20px 20px 5px;
}

.achievement-year {
    color: #007bff;
    font-weight: 700;
    margin-bottom: 0;
}

.achievements-slider button {
    position: absolute;
    top: 210px;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.5);
    color: #fff;
    border: none;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    font-size: 28px;
    line-height: 1;
    cursor: pointer;
}

.achievement-slider-prev {
    left: 15px;
}

.achievement-slider-next {
    right: 15px;
}

/* Responsive */
@media screen and (max-width: 768px) {
    .hero h1 {
        font-size: 36px;
    }
    
    .hero p {
        font-size: 18px;
    }
    
    .about-home {
        grid-template-columns: 1fr;
    }
    
    .about-content {
        padding-right: 0;
    }

    .statistics {
        padding: 40px 0;
        min-height: auto;
    }

    .statistic {
        flex: 0 1 100%;
        max-width: 420px;
        margin: 0 auto;
    }
}
</style>

<script>
/*
  Counting effect for statistics.
  - Uses IntersectionObserver to start animation when the statistics section enters view (threshold 0.5).
  - Each .number element must have:
      data-target = numeric value to count to (e.g. "1200" or "75.5")
      data-suffix = optional suffix to append (e.g. "+" or "%")
  - Ensures each counter animates only once.
*/
document.addEventListener('DOMContentLoaded', function () {
    const formatNumber = (value) => {
        // preserve decimals if present
        if (Number.isInteger(value)) return value.toLocaleString();
        return value.toLocaleString(undefined, {maximumFractionDigits: 2});
    };

    const animateCount = (el) => {
        const targetRaw = el.getAttribute('data-target') || '0';
        const suffix = el.getAttribute('data-suffix') || '';
        const target = parseFloat(targetRaw) || 0;
        const duration = 1600; // ms
        let startTime = null;
        const startValue = 0;

        const step = (timestamp) => {
            if (!startTime) startTime = timestamp;
            const elapsed = timestamp - startTime;
            const progress = Math.min(elapsed / duration, 1);
            // easeOutCubic
            const eased = 1 - Math.pow(1 - progress, 3);
            let current = startValue + (target - startValue) * eased;
            // if target is integer-like, show integer during animation
            if (Math.abs(target - Math.round(target)) < 0.00001) {
                current = Math.round(current);
            } else {
                current = Math.round(current * 100) / 100;
            }
            el.textContent = formatNumber(current) + suffix;
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                // ensure final exact value
                el.textContent = formatNumber(target) + suffix;
            }
        };

        requestAnimationFrame(step);
    };

    const setupObserver = () => {
        const sections = document.querySelectorAll('.statistics');
        if (sections.length === 0) return;

        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const nums = entry.target.querySelectorAll('.number');
                        nums.forEach(n => {
                            if (!n.dataset.animated) {
                                animateCount(n);
                                n.dataset.animated = 'true';
                            }
                        });
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });

            sections.forEach(s => io.observe(s));
        } else {
            // Fallback: animate immediately
            const nums = document.querySelectorAll('.statistics .number');
            nums.forEach(n => {
                if (!n.dataset.animated) {
                    animateCount(n);
                    n.dataset.animated = 'true';
                }
            });
        }
    };

    setupObserver();

    // Achievements photo slider: auto-rotates, with prev/next controls.
    const achievementsSlider = document.querySelector('[data-achievements-slider]');
    if (achievementsSlider) {
        const slides = achievementsSlider.querySelectorAll('.achievement-slide');
        const prevButton = achievementsSlider.querySelector('[data-achievements-prev]');
        const nextButton = achievementsSlider.querySelector('[data-achievements-next]');
        let currentSlide = 0;
        let sliderTimer = null;

        const showSlide = (index) => {
            slides[currentSlide].classList.remove('active');
            currentSlide = (index + slides.length) % slides.length;
            slides[currentSlide].classList.add('active');
        };

        const startSlider = () => {
            if (slides.length > 1) {
                sliderTimer = setInterval(() => showSlide(currentSlide + 1), 5000);
            }
        };

        const stopSlider = () => {
            if (sliderTimer !== null) {
                clearInterval(sliderTimer);
                sliderTimer = null;
            }
        };

        if (slides.length < 2) {
            if (prevButton) { prevButton.style.display = 'none'; }
            if (nextButton) { nextButton.style.display = 'none'; }
        } else {
            if (prevButton) { prevButton.addEventListener('click', () => { stopSlider(); showSlide(currentSlide - 1); startSlider(); }); }
            if (nextButton) { nextButton.addEventListener('click', () => { stopSlider(); showSlide(currentSlide + 1); startSlider(); }); }
            achievementsSlider.addEventListener('mouseenter', stopSlider);
            achievementsSlider.addEventListener('mouseleave', startSlider);
            startSlider();
        }
    }
});
</script>