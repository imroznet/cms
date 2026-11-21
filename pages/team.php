<?php
// Security check to prevent direct access
if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Get team members
$teamMembers = getTeamMembers($conn);
?>

<!-- Page Header -->
<section class="page-header" style="background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('assets/images/team-header.jpg') no-repeat center center/cover;">
    <div class="container">
        <h1>Our Team</h1>
        <p>Meet the professionals behind our success</p>
    </div>
</section>

<!-- Team Section -->
<section class="section">
    <div class="container">
        <div class="section-intro" style="text-align: center; max-width: 800px; margin: 0 auto 60px;">
            <p>Our team comprises experienced professionals dedicated to delivering excellence in every project. With diverse backgrounds and complementary skills, we work together to provide innovative solutions for our clients.</p>
        </div>
        
        <?php if (count($teamMembers) > 0): ?>
            <div class="team-grid">
                <?php foreach ($teamMembers as $member): ?>
                <div class="team-member">
                    <div class="team-member-image">
                        <?php if ($member['image'] && file_exists('assets/images/team/' . $member['image'])): ?>
                            <img src="<?php echo publicAsset('assets/images/team/' . $member['image']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>" class="circular-image">
                        <?php else: ?>
                            <img src="assets/images/team-placeholder.jpg" alt="<?php echo htmlspecialchars($member['name']); ?>" class="circular-image">
                        <?php endif; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($member['name']); ?></h3>
                    <p class="position"><?php echo htmlspecialchars($member['position']); ?></p>
                    
                    <?php if (!empty($member['bio'])): ?>
                    <p class="bio"><?php echo nl2br(htmlspecialchars($member['bio'])); ?></p>
                    <?php endif; ?>
                    
                    <div class="team-social">
                        <?php if (!empty($member['social_linkedin'])): ?>
                            <a href="<?php echo htmlspecialchars($member['social_linkedin']); ?>" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                        <?php endif; ?>
                        
                        <?php if (!empty($member['social_twitter'])): ?>
                            <a href="<?php echo htmlspecialchars($member['social_twitter']); ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        
                        <?php if (!empty($member['social_facebook'])): ?>
                            <a href="<?php echo htmlspecialchars($member['social_facebook']); ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        
                        <?php if (!empty($member['social_instagram'])): ?>
                            <a href="<?php echo htmlspecialchars($member['social_instagram']); ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-team-members" style="text-align: center; padding: 50px 0;">
                <i class="fas fa-users" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                <p>Team information will be coming soon.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Join Our Team Section -->
<section class="section" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="row">
            <div style="max-width: 800px; margin: 0 auto; text-align: center;">
                <h2>Join Our Team</h2>
                <p>We're always looking for talented individuals to join our team. If you're passionate about what you do and looking for new challenges, we'd love to hear from you.</p>
                <a href="careers" class="btn btn-primary" style="margin-top: 20px;">View Open Positions</a>
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

/* Ensure proper grid layout */
.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 30px;
    margin: 40px 0;
}

/* Make profile images circular */
.circular-image {
    border-radius: 50% !important;
    width: 150px !important;
    height: 150px !important;
    object-fit: cover !important;
    margin: 0 auto 15px !important;
    display: block;
}

/* Adjust team member styling */
.team-member {
    text-align: center;
    padding: 15px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.team-member:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.team-member h3 {
    margin-bottom: 5px;
}

.team-member .position {
    color: #007bff;
    margin-bottom: 10px;
}

.team-member .bio {
    font-size: 0.9em;
    margin-bottom: 15px;
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