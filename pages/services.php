<?php
// Security check to prevent direct access
if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Get services from database
$services = getServices($conn);
?>

<!-- Page Header -->
<section class="page-header" style="background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('assets/images/services-header.jpg') no-repeat center center/cover;">
    <div class="container">
        <h1>Our Services</h1>
        <p>Discover what we can do for your business</p>
    </div>
</section>

<!-- Services Section -->
<section class="section">
    <div class="container">
        <div class="section-intro" style="text-align: center; max-width: 800px; margin: 0 auto 60px;">
            <p>At <?php echo $settings['company_name']; ?>, we offer a comprehensive range of services designed to help businesses automate processes, improve efficiency, and achieve sustainable growth. Our focus areas include automation solutions, food industry innovations, and agricultural technology.</p>
        </div>
        
        <div class="services-grid">
            <?php if (count($services) > 0): ?>
                <?php foreach ($services as $service): ?>
                    <div class="service-card">
                        <i class="fas <?php echo $service['icon'] ?? 'fa-cog'; ?>"></i>
                        <h3><?php echo $service['title']; ?></h3>
                        <p><?php echo nl2br($service['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Services are added from the site administration. None are published yet.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Process Section -->
<section class="section" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="section-title">
            <h2>Our Process</h2>
        </div>
        
        <div class="process-steps" style="max-width: 900px; margin: 0 auto;">
            <div class="process-step" style="display: flex; align-items: flex-start; margin-bottom: 40px;">
                <div class="process-number" style="
                    background-color: #007bff;
                    color: white;
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 24px;
                    font-weight: bold;
                    margin-right: 20px;
                    flex-shrink: 0;
                ">1</div>
                <div>
                    <h3>Consultation & Analysis</h3>
                    <p>We begin by understanding your business needs, challenges, and goals. Our team conducts a thorough analysis of your current processes to identify areas for improvement.</p>
                </div>
            </div>
            
            <div class="process-step" style="display: flex; align-items: flex-start; margin-bottom: 40px;">
                <div class="process-number" style="
                    background-color: #007bff;
                    color: white;
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 24px;
                    font-weight: bold;
                    margin-right: 20px;
                    flex-shrink: 0;
                ">2</div>
                <div>
                    <h3>Strategy Development</h3>
                    <p>Based on our analysis, we develop a customized strategy to address your specific requirements. We outline the proposed solutions, technologies, and timeline.</p>
                </div>
            </div>
            
            <div class="process-step" style="display: flex; align-items: flex-start; margin-bottom: 40px;">
                <div class="process-number" style="
                    background-color: #007bff;
                    color: white;
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 24px;
                    font-weight: bold;
                    margin-right: 20px;
                    flex-shrink: 0;
                ">3</div>
                <div>
                    <h3>Implementation</h3>
                    <p>Our experienced team implements the agreed solutions with minimal disruption to your operations. We ensure all systems are properly integrated and functioning as expected.</p>
                </div>
            </div>
            
            <div class="process-step" style="display: flex; align-items: flex-start;">
                <div class="process-number" style="
                    background-color: #007bff;
                    color: white;
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 24px;
                    font-weight: bold;
                    margin-right: 20px;
                    flex-shrink: 0;
                ">4</div>
                <div>
                    <h3>Training & Support</h3>
                    <p>We provide comprehensive training to your team and ongoing support to ensure smooth operation. Our commitment continues well beyond implementation to guarantee long-term success.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="section" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="section-title">
            <h2>Client Testimonials</h2>
        </div>
        
        <?php
        $testimonials = getTestimonials($conn);
        if (count($testimonials) > 0):
        ?>
        <div class="testimonials-slider">
            <?php foreach ($testimonials as $testimonial): ?>
                <div class="testimonial">
                    <div class="testimonial-content">
                        <div class="testimonial-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $testimonial['rating'] ? 'filled' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="testimonial-text"><?php echo htmlspecialchars($testimonial['message']); ?></p>
                    </div>
                    <div class="testimonial-author">
                        <?php if ($testimonial['image'] && file_exists('assets/images/testimonials/' . $testimonial['image'])): ?>
                            <img src="assets/images/testimonials/<?php echo htmlspecialchars($testimonial['image']); ?>" alt="<?php echo htmlspecialchars($testimonial['name']); ?>" class="testimonial-image">
                        <?php else: ?>
                            <div class="testimonial-image-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <div class="testimonial-info">
                            <h4><?php echo htmlspecialchars($testimonial['name']); ?></h4>
                            <?php if (!empty($testimonial['position']) || !empty($testimonial['company'])): ?>
                                <p>
                                    <?php 
                                    echo !empty($testimonial['position']) ? htmlspecialchars($testimonial['position']) : '';
                                    echo !empty($testimonial['position']) && !empty($testimonial['company']) ? ', ' : '';
                                    echo !empty($testimonial['company']) ? htmlspecialchars($testimonial['company']) : '';
                                    ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="no-testimonials">
            <p>Client testimonials coming soon.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Section -->
<section class="section" style="background-color: #007bff; color: white; text-align: center;">
    <div class="container">
        <h2>Ready to Transform Your Business?</h2>
        <p>Contact us today to discuss how our services can help you achieve your goals.</p>
        <a href="contact" class="btn btn-secondary" style="background-color: white; color: #007bff; margin-top: 20px;">Get Started</a>
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
    .process-step {
        flex-direction: column;
    }
    
    .process-number {
        margin-bottom: 15px;
        margin-right: 0 !important;
    }
}
</style>