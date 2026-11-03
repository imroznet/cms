<?php
if (!defined('INCLUDED')) {
    exit('Direct access not permitted');
}
?>

<footer class="site-footer">
    <div class="footer-top">
        <div class="container">
            <div class="footer-content">
                <div class="footer-company">
                    <h3><?php echo htmlspecialchars($settings['company_name'] ?? 'Company Name'); ?></h3>
                    <?php if (!empty($settings['motto'])): ?>
                    <p class="motto"><?php echo htmlspecialchars($settings['motto']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($settings['address'])): ?>
                    <div class="footer-address">
                        <i class="fas fa-map-marker-alt"></i>
                        <p><?php echo nl2br(htmlspecialchars($settings['address'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="footer-links">
                    <h4>Quick Links</h4>
    <ul>
        <li><a href="index">Home</a></li>
        <li><a href="about">About Us</a></li>
        <li><a href="services">Services</a></li>
        <li><a href="news">News</a></li>
        <li><a href="careers">Careers</a></li>
        <li><a href="https://blog.ahmad.com.bd" target="_blank">Blog</a></li>
        <li><a href="contact">Contact Us</a></li>
    </ul>
                </div>
                
                <div class="footer-contact">
                    <h4>Contact Us</h4>
                    <?php if (!empty($settings['contact_phone'])): ?>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($settings['contact_phone']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($settings['contact_email'])): ?>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($settings['contact_email']); ?></p>
                    <?php endif; ?>
                    
                    <?php
                    $socialLinks = getSocialLinks($conn);
                    if (count($socialLinks) > 0):
                    ?>
                    <div class="footer-social">
                        <h4>Follow Us</h4>
                        <div class="social-icons">
                            <?php foreach ($socialLinks as $social): ?>
                            <a href="<?php echo htmlspecialchars($social['url']); ?>" target="_blank">
                                <i class="fab fa-<?php echo htmlspecialchars($social['icon']); ?>"></i>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['company_name'] ?? 'Company Name'); ?>. All Rights Reserved.</p>
        </div>
    </div>
</footer>

<style>
/* Footer Styles */
.site-footer {
    background-color: #343a40;
    color: #fff;
}

.footer-top {
    padding: 60px 0 40px;
}

.footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 40px;
}

.footer-company h3 {
    font-size: 24px;
    margin-bottom: 15px;
}

.motto {
    margin-bottom: 20px;
    font-style: italic;
    color: #ccc;
}

.footer-address {
    display: flex;
    margin-top: 15px;
}

.footer-address i {
    margin-right: 10px;
    margin-top: 5px;
}

.footer-links h4, .footer-contact h4 {
    font-size: 18px;
    margin-bottom: 15px;
    position: relative;
}

.footer-links h4::after, .footer-contact h4::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 40px;
    height: 2px;
    background-color: #007bff;
}

.footer-links ul {
    list-style: none;
}

.footer-links ul li {
    margin-bottom: 10px;
}

.footer-links ul li a {
    color: #ccc;
    text-decoration: none;
    transition: color 0.3s;
}

.footer-links ul li a:hover {
    color: #007bff;
}

.footer-contact p {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
}

.footer-contact i {
    margin-right: 10px;
    color: #007bff;
}

.footer-social {
    margin-top: 20px;
}

.social-icons {
    display: flex;
    gap: 15px;
    margin-top: 10px;
}

.social-icons a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    color: #fff;
    transition: background-color 0.3s;
    text-decoration: none; /* Remove underlines */
    position: relative; /* Added for precise icon positioning */
}

.social-icons a i {
    position: absolute; /* Position the icon absolutely */
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%); /* Perfect centering */
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.social-icons a:hover {
    background-color: #007bff;
    text-decoration: none;
}

/* Update the footer-bottom class */
.footer-bottom {
    background-color: #212529;
    padding: 20px 0;
    text-align: center;
    width: 100%;
}

/* Make sure the container inside footer-bottom doesn't override the centering */
.footer-bottom .container {
    text-align: center;
    width: 100%;
    display: flex;
    justify-content: center;
}

.footer-bottom p {
    margin: 0;
    font-size: 14px;
    text-align: center; /* Triple ensure centering */
    width: 100%; /* Ensure the paragraph takes full width */
}

/* Responsive Footer */
@media screen and (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
    document.querySelector('.main-nav').classList.toggle('active');
});
</script>
</body>
</html>