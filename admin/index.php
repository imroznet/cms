<?php
require_once __DIR__ . '/../includes/security.php';
require_once '../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['_confirm_action'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Get user permissions
                $stmt = $conn->prepare("SELECT permission_name FROM user_permissions WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // If there are no explicit permissions and user is admin, grant all permissions
                if (empty($permissions) && $user['role'] === 'admin') {
                    $permissions = [
                        'manage_settings',
                        'manage_news',
                        'manage_services',
                        'manage_careers',
                        'manage_contacts',
                        'manage_statistics',
                        'manage_users'
                    ];
                    
                    // Save these default permissions to database
                    $stmt = $conn->prepare("INSERT INTO user_permissions (user_id, permission_name) VALUES (?, ?)");
                    foreach ($permissions as $permission) {
                        $stmt->execute([$user['id'], $permission]);
                    }
                }
                
                $_SESSION['permissions'] = $permissions;
                
                // Log the successful login
                try {
                    $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action, details, ip_address) VALUES (?, 'login', ?, ?)");
                    $details = json_encode(['browser' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']);
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                    $stmt->execute([$user['id'], $details, $ip]);
                } catch (PDOException $e) {
                    // Silent fail - the login should proceed even if logging fails
                }
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid username or password";
            }
        } catch (PDOException $e) {
            $error = "The operation failed. Please contact your administrator.";
        }
    }
}
?>
<!-- Rest of the login page remains unchanged -->
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .login-container {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 30px;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-logo h1 {
            color: #333;
            margin: 10px 0 0;
            font-size: 24px;
        }
        
        .login-form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .login-form button {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .login-form button:hover {
            background-color: #0069d9;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-user-shield" style="font-size: 48px; color: #007bff;"></i>
            <h1>Admin Login</h1>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form class="login-form" method="post" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>