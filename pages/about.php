<?php
// Security check to prevent direct access
if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Get statistics. A stored zero is not a company figure, so it stays off the page.
$statistics = array_values(array_filter(getStatistics($conn), static function (array $stat): bool {
    $numeric = preg_replace('/[^0-9.\-]/', '', (string) ($stat['value'] ?? ''));

    return $numeric !== '' && (float) $numeric !== 0.0;
}));

// Get team members
$team_members = [];
$team_query = "SELECT name, position, bio FROM team_members WHERE is_active = 1 ORDER BY display_order ASC";
$team_result = $conn->query($team_query);

$team_members = $team_result ? $team_result->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<!-- Page Header -->
<section class="page-header" style="background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('<?php echo publicAsset('assets/images/about-header.jpg'); ?>') no-repeat center center/cover;">
    <div class="container">
        <h1>About Us</h1>
        <p>Learn more about our company</p>
    </div>
</section>

<!-- About Section -->
<section class="section">
    <div class="container">
        <div class="about-content">
            <?php if (!empty($settings['about_content'])): ?>
                <?php echo nl2br($settings['about_content']); ?>
            <?php else: ?>
                <p>The company story is added from site administration.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<?php if (count($statistics) > 0): ?>
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

<?php $documents = getCompanyDocuments($conn, true); ?>
<?php if ($documents !== []): ?>
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Documentation</h2>
        </div>
        <?php include __DIR__ . '/../includes/company-documents.php'; ?>
    </div>
</section>
<?php endif; ?>

<?php $achievements = getAchievements($conn, true); ?>
<?php if ($achievements !== []): ?>
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Achievements</h2>
        </div>
        <?php include __DIR__ . '/../includes/achievements.php'; ?>
    </div>
</section>
<?php endif; ?>

<!-- Team Section -->
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Our Team</h2>
        </div>
        
        <div class="team-grid"style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 30px;">
            <?php
            // Fetch active team members ordered by display_order using PDO
            $stmt = $conn->prepare("SELECT name, position, bio FROM team_members WHERE is_active = 1 ORDER BY display_order ASC");
            $stmt->execute();
            $team_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($team_members)) {
                foreach ($team_members as $member) {
                    ?>
                    <div class="team-member" style="text-align: center;">
                        <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($member['name']); ?></h3>
                        <p style="color: #007bff; margin-bottom: 10px;"><?php echo htmlspecialchars($member['position']); ?></p>
                        <?php if (!empty($member['bio'])): ?>
                        <p style="font-size: 0.9em;"><?php echo htmlspecialchars($member['bio']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php
                }
            } else {
                // No team members found or database error
                echo '<p class="text-center">Our team information will be available soon.</p>';
            }
            ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="section" style="background-color: #007bff; color: white; text-align: center;">
    <div class="container">
        <h2>Ready to work with us?</h2>
        <p>Contact our team today to discuss how we can help your business grow.</p>
        <a href="contact" class="btn btn-secondary" style="background-color: white; color: #007bff; margin-top: 20px;">Get In Touch</a>
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

.about-content {
    max-width: 800px;
    margin: 0 auto;
    line-height: 1.8;
}

.about-content h2 {
    margin-top: 30px;
    margin-bottom: 15px;
    color: #333;
}

.about-content ul {
    margin-left: 20px;
    margin-bottom: 20px;
}

.about-content ul li {
    margin-bottom: 10px;
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
});
</script>