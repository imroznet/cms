<?php
// Security check to prevent direct access
if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Process contact form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $messageText = sanitize($_POST['message'] ?? '');
    
    // Validate form
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email is required";
    }
    
    if (empty($subject)) {
        $errors[] = "Subject is required";
    }
    
    if (empty($messageText)) {
        $errors[] = "Message is required";
    }
    
    if (empty($errors)) {
        try {
            // Save message to database
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$name, $email, $subject, $messageText]);
            
            if ($result) {
                // Send email notification if admin email is set
                if (!empty($settings['admin_email'])) {
                    $adminEmail = $settings['admin_email'];
                    $emailSubject = "New Contact Form Message: $subject";
                    $emailBody = "You have received a new message from your website contact form.\n\n";
                    $emailBody .= "Name: $name\n";
                    $emailBody .= "Email: $email\n";
                    $emailBody .= "Subject: $subject\n\n";
                    $emailBody .= "Message:\n$messageText\n";
                    
                    $headers = "From: $email\r\n";
                    
                    mail($adminEmail, $emailSubject, $emailBody, $headers);
                }
                
                $message = "Thank you! Your message has been sent successfully.";
                $messageType = "success";
                
                // Reset form fields
                $name = $email = $subject = $messageText = '';
            } else {
                $message = "Sorry, there was an error sending your message. Please try again.";
                $messageType = "error";
            }
        } catch (PDOException $e) {
            $message = "The operation failed. Please try again later.";
            $messageType = "error";
        }
    } else {
        $message = "Please correct the following errors: <ul><li>" . implode("</li><li>", $errors) . "</li></ul>";
        $messageType = "error";
    }
}
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1>Contact Us</h1>
        <p>Get in touch with our team</p>
    </div>
</section>

<!-- Contact Section -->
<section class="section">
    <div class="container">
        <?php if ($message): ?>
            <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>" style="margin-bottom: 30px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Contact Information First -->
        <div class="contact-info-container">
            <h2>Contact Information</h2>
            <p>We're here to help! Reach out to us with any questions or inquiries.</p>
            
            <?php if (!empty($settings['address'])): ?>
            <div class="contact-info-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <h4>Our Address</h4>
                    <p><?php echo nl2br(htmlspecialchars($settings['address'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($settings['contact_email'])): ?>
            <div class="contact-info-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <h4>Email Us</h4>
                    <p><a href="mailto:<?php echo htmlspecialchars($settings['contact_email']); ?>" class="contact-link"><?php echo htmlspecialchars($settings['contact_email']); ?></a></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($settings['contact_phone'])): ?>
            <div class="contact-info-item">
                <i class="fas fa-phone-alt"></i>
                <div>
                    <h4>Call Us</h4>
                    <p><a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $settings['contact_phone']); ?>" class="contact-link"><?php echo htmlspecialchars($settings['contact_phone']); ?></a></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Social Links -->
            <?php
            $socialLinks = getSocialLinks($conn);
            if (count($socialLinks) > 0):
            ?>
            <div class="contact-info-item">
                <i class="fas fa-share-alt"></i>
                <div>
                    <h4>Follow Us</h4>
                    <div class="contact-social-links">
                        <?php foreach ($socialLinks as $social): ?>
                        <a href="<?php echo htmlspecialchars($social['url']); ?>" target="_blank">
                            <i class="fab fa-<?php echo htmlspecialchars($social['icon']); ?>"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Contact Form Below -->
        <div class="contact-form-container">
            <h2>Send Us a Message</h2>
            <form method="post" action="">
                <div class="form-group">
                    <label for="name">Your Name *</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Your Email *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="message">Your Message *</label>
                    <textarea id="message" name="message" rows="6" required><?php echo htmlspecialchars($messageText ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>
        </div>
        
        <!-- Google Maps Section -->
        <?php if (!empty($settings['map_embed_url']) || (!empty($settings['map_latitude']) && !empty($settings['map_longitude']))): ?>
        <div class="map-section" style="margin-top: 60px;">
            <h2 style="text-align: center; margin-bottom: 30px;">Find Us</h2>
            <?php if (!empty($settings['map_embed_url'])): ?>
                <div class="map-embed">
                    <iframe 
                        src="<?php echo htmlspecialchars($settings['map_embed_url']); ?>" 
                        width="100%" 
                        height="450" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            <?php elseif (!empty($settings['map_latitude']) && !empty($settings['map_longitude'])): ?>
                <!-- Alternative: Display map using latitude and longitude -->
                <div id="map" style="width: 100%; height: 450px;"></div>
                <script>
                function initMap() {
                    var location = {lat: <?php echo floatval($settings['map_latitude']); ?>, lng: <?php echo floatval($settings['map_longitude']); ?>};
                    var map = new google.maps.Map(document.getElementById('map'), {
                        zoom: 15,
                        center: location
                    });
                    var marker = new google.maps.Marker({
                        position: location,
                        map: map,
                        title: '<?php echo htmlspecialchars($settings['company_name']); ?>'
                    });
                }
                </script>
                <script async defer src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap"></script>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.page-header {
    background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('assets/images/contact-header.jpg') no-repeat center center/cover;
    color: white;
    padding: 80px 0;
    text-align: center;
    margin-bottom: 40px;
    margin-top: 0;
    position: relative;
    z-index: 1;
}

.page-header h1 {
    font-size: 2.5rem;
    margin-bottom: 10px;
}

/* Alert Styles */
.alert {
    padding: 15px;
    border-radius: 4px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Contact Layout - Changed to vertical layout */
.contact-info-container {
    background-color: #f8f9fa;
    padding: 30px;
    border-radius: 5px;
    margin-bottom: 40px;
}

.contact-form-container {
    margin-bottom: 40px;
}

/* Contact Info */
.contact-info-item {
    display: flex;
    margin-bottom: 25px;
}

.contact-info-item i {
    font-size: 24px;
    color: #007bff;
    margin-right: 20px;
    margin-top: 5px;
}

.contact-info-item h4 {
    margin: 0 0 10px 0;
}

.contact-info-item p {
    margin: 0;
}

/* Fix for contact links - added black color */
.contact-link {
    color: #333 !important; /* Force black color for links */
    text-decoration: none;
}

.contact-link:hover {
    text-decoration: underline;
}

.contact-social-links {
    display: flex;
    gap: 15px;
    margin-top: 10px;
}

.contact-social-links a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background-color: #e9ecef;
    border-radius: 50%;
    color: #007bff;
    text-decoration: none;
    transition: all 0.3s;
}

.contact-social-links a i {
    font-size: 18px; /* Fixed font size */
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    margin: 0; /* Remove any margin that might affect centering */
    padding: 0; /* Remove any padding that might affect centering */
    line-height: 0; /* Reset line height to help with vertical centering */
}

.contact-social-links a:hover {
    background-color: #007bff;
    color: white;
}

/* Google Maps */
.map-embed {
    border-radius: 5px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

/* Form spacing and styling */
.form-group {
    margin-bottom: 20px;
}

button[type="submit"] {
    padding: 12px 25px;
}

@media (max-width: 768px) {
    .contact-form-container,
    .contact-info-container {
        padding: 20px;
    }
}
</style>