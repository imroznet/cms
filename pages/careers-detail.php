<?php
// Security check to prevent direct access
if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Get job ID from URL
$jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get job listing details
$stmt = $conn->prepare("SELECT * FROM careers WHERE id = ? AND is_active = 1");
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

// If job listing not found, redirect to careers page
if (!$job) {
    header("Location: careers");
    exit;
}

// Process application form if submitted
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $cover_letter = sanitize($_POST['cover_letter'] ?? '');
    
    // Validate form
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email is required";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    // Resume upload validation
    if (empty($_FILES['resume']['name'])) {
        $errors[] = "Resume is required";
    } else {
        $allowed = ['pdf', 'doc', 'docx'];
        $filename = $_FILES['resume']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $errors[] = "Resume must be in PDF, DOC, or DOCX format";
        }
    }
    
    if (empty($errors)) {
        // Handle resume upload
        $upload_dir = 'assets/uploads/resumes/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $resume_filename = time() . '_' . $_FILES['resume']['name'];
        $upload_path = $upload_dir . $resume_filename;
        
        if (move_uploaded_file($_FILES['resume']['tmp_name'], $upload_path)) {
            // Get site settings for admin email
            $settings = getSiteSettings($conn);
            $adminEmail = $settings['admin_email'] ?? '';
            
            // Send email notification to admin if configured
            if (!empty($adminEmail)) {
                $emailSubject = "New Job Application: " . $job['position'];
                $emailBody = "You have received a new job application.\n\n";
                $emailBody .= "Position: " . $job['position'] . "\n";
                $emailBody .= "Name: " . $name . "\n";
                $emailBody .= "Email: " . $email . "\n";
                $emailBody .= "Phone: " . $phone . "\n\n";
                $emailBody .= "Cover Letter:\n" . $cover_letter . "\n\n";
                $emailBody .= "Resume is attached to this email or available in the uploads directory.";
                
                $headers = "From: " . $email . "\r\n";
                
                // Use SMTP if configured, otherwise use PHP mail()
                if (!empty($settings['smtp_host']) && !empty($settings['smtp_port'])) {
                    // Here you would implement SMTP mail sending
                    // For simplicity, we'll use PHP mail() for now
                }
                
                mail($adminEmail, $emailSubject, $emailBody, $headers);
            }
            
            $message = "Thank you for your application! We will review your information and contact you if there's a match.";
            $messageType = "success";
        } else {
            $message = "There was an error uploading your resume. Please try again.";
            $messageType = "error";
        }
    } else {
        $message = "Please correct the following errors: <ul><li>" . implode("</li><li>", $errors) . "</li></ul>";
        $messageType = "error";
    }
}
?>

<!-- Page Header -->
<section class="page-header" style="background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('assets/images/careers-header.jpg') no-repeat center center/cover;">
    <div class="container">
        <h1><?php echo htmlspecialchars($job['position']); ?></h1>
        <p><?php echo htmlspecialchars($job['location']); ?></p>
    </div>
</section>

<!-- Job Detail Section -->
<section class="section">
    <div class="container">
        <div class="job-detail" style="max-width: 800px; margin: 0 auto;">
            <div class="job-meta" style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">
                <div style="background-color: #f8f9fa; padding: 10px 20px; border-radius: 5px;">
                    <i class="fas fa-calendar"></i> Posted: <?php echo date('F j, Y', strtotime($job['posted_date'])); ?>
                </div>
                
                <?php if (!empty($job['closing_date'])): ?>
                    <div style="background-color: #f8f9fa; padding: 10px 20px; border-radius: 5px;">
                        <i class="fas fa-clock"></i> Closing: <?php echo date('F j, Y', strtotime($job['closing_date'])); ?>
                    </div>
                <?php endif; ?>
                
                <div style="background-color: #f8f9fa; padding: 10px 20px; border-radius: 5px;">
                    <i class="fas fa-map-marker-alt"></i> Location: <?php echo htmlspecialchars($job['location']); ?>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert <?php echo $messageType === 'success' ? 'alert-success' : 'alert-error'; ?>" style="margin-bottom: 30px; padding: 15px; border-radius: 5px;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="job-description" style="margin-bottom: 40px; background-color: #f8f9fa; padding: 25px; border-radius: 5px;">
                <h3 style="margin-top: 0; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #dee2e6;">Job Description</h3>
                <div style="line-height: 1.8;">
                    <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                </div>
            </div>
            
            <?php if (!empty($job['requirements'])): ?>
            <div class="job-requirements" style="margin-bottom: 40px; background-color: #f8f9fa; padding: 25px; border-radius: 5px;">
                <h3 style="margin-top: 0; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #dee2e6;">Requirements</h3>
                <div style="line-height: 1.8;">
                    <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="apply-section">
                <h3 style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee;">Apply for this Position</h3>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone *</label>
                        <input type="text" id="phone" name="phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="resume">Resume (PDF, DOC, DOCX) *</label>
                        <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cover_letter">Cover Letter</label>
                        <textarea id="cover_letter" name="cover_letter" rows="6"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Submit Application</button>
                    </div>
                </form>
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

input[type="file"] {
    padding: 10px 0;
}
</style>