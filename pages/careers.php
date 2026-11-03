<?php
// Security check to prevent direct access
if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Get active job listings
$stmt = $conn->prepare("SELECT * FROM careers WHERE is_active = 1 ORDER BY posted_date DESC");
$stmt->execute();
$careers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Page Header -->
<section class="page-header" style="background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('assets/images/careers-header.jpg') no-repeat center center/cover;">
    <div class="container">
        <h1>Join Our Team</h1>
        <p>Explore career opportunities with us</p>
    </div>
</section>

<!-- Careers Introduction Section -->
<section class="section">
    <div class="container">
        <div class="section-intro" style="text-align: center; max-width: 800px; margin: 0 auto 60px;">
            <h2>Careers at <?php echo htmlspecialchars($settings['company_name'] ?? 'Our Company'); ?></h2>
            <p>We're always looking for talented individuals to join our growing team. At <?php echo htmlspecialchars($settings['company_name'] ?? 'our company'); ?>, we offer a dynamic work environment, opportunities for professional growth, and a chance to make a real impact.</p>
        </div>
        
        <!-- Current Openings Section -->
        <div class="openings">
            <h3 style="text-align: center; margin-bottom: 30px;">Current Openings</h3>
            
            <?php if (count($careers) > 0): ?>
                <div class="job-listings">
                    <?php foreach ($careers as $job): ?>
                        <div class="job-card" style="background-color: white; border-radius: 5px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); padding: 25px; margin-bottom: 30px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; flex-wrap: wrap;">
                                <h4 style="margin: 0; font-size: 20px;"><?php echo htmlspecialchars($job['position']); ?></h4>
                                <span class="job-location" style="background-color: #f8f9fa; padding: 5px 15px; border-radius: 20px; font-size: 14px; margin-top: 5px;">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?>
                                </span>
                            </div>
                            
                            <div class="job-meta" style="color: #6c757d; font-size: 14px; margin-bottom: 15px;">
                                <span><i class="fas fa-calendar"></i> Posted: <?php echo date('F j, Y', strtotime($job['posted_date'])); ?></span>
                                <?php if (!empty($job['closing_date'])): ?>
                                    <span style="margin-left: 15px;"><i class="fas fa-clock"></i> Closing: <?php echo date('F j, Y', strtotime($job['closing_date'])); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="job-description" style="margin-bottom: 20px;">
                                <?php echo substr(nl2br(htmlspecialchars($job['description'])), 0, 300) . (strlen($job['description']) > 300 ? '...' : ''); ?>
                            </div>
                            
                            <a href="career-detail/<?php echo $job['id']; ?>" class="btn btn-primary">View Details</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-jobs" style="text-align: center; padding: 50px 0; background-color: #f8f9fa; border-radius: 5px;">
                    <i class="fas fa-briefcase" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                    <h4>No Current Openings</h4>
                    <p>We don't have any open positions at the moment, but we're always interested in hearing from talented individuals.</p>
                    <p>Send your resume to <a href="mailto:<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>"><?php echo htmlspecialchars($settings['contact_email'] ?? 'contact@example.com'); ?></a> with the subject line "Future Opportunities".</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Company Culture Section -->
<section class="section" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="section-title">
            <h2>Our Company Culture</h2>
        </div>
        
        <div class="culture-content" style="max-width: 800px; margin: 0 auto; text-align: center;">
            <p>At <?php echo htmlspecialchars($settings['company_name'] ?? 'our company'); ?>, we believe in fostering a positive, inclusive, and collaborative work environment. Our team members are encouraged to think creatively, take initiative, and contribute ideas that drive our company forward.</p>
            
            <div class="culture-images" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 40px 0;">
                <img src="assets/images/culture-placeholder.jpg" alt="Company Culture" style="width: 100%; border-radius: 5px;">
                <img src="assets/images/culture-placeholder.jpg" alt="Company Culture" style="width: 100%; border-radius: 5px;">
                <img src="assets/images/culture-placeholder.jpg" alt="Company Culture" style="width: 100%; border-radius: 5px;">
            </div>
            
            <p>We value work-life balance, continuous learning, and recognize the achievements of our team members. Join us and be part of a company that's making a difference in the industries we serve.</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="section" style="background-color: #007bff; color: white; text-align: center;">
    <div class="container">
        <h2>Ready to Take the Next Step in Your Career?</h2>
        <p>Explore our current openings and join our team of talented professionals.</p>
        <div style="margin-top: 20px;">
            <?php if (count($careers) > 0): ?>
                <a href="#" class="btn" style="background-color: white; color: #007bff;">View Open Positions</a>
            <?php else: ?>
                <a href="contact" class="btn" style="background-color: white; color: #007bff;">Contact Us</a>
            <?php endif; ?>
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

@media screen and (max-width: 768px) {
    .job-card div:first-child {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .job-location {
        margin-top: 10px;
    }
}
</style>