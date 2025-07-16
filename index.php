<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

startSession();

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, full_name, password, is_admin FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                loginUser($user['id'], $user['full_name'], $user['is_admin']);
                if ($user['is_admin']) {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } catch (Exception $e) {
            $error = "Login failed. Please try again.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($name) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            try {
                $db = getDB();
                
                // Check if email already exists
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "Email is already registered.";
                } else {
                    // Insert new user
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
                    if ($stmt->execute([$name, $email, $hashedPassword])) {
                        $success = "Registration successful! You can now login.";
                    } else {
                        $error = "Registration failed. Please try again.";
                    }
                }
            } catch (Exception $e) {
                $error = "Registration failed. Please try again.";
            }
        } else {
            $error = "Passwords do not match.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Jaipur Metro - Complaint Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('images/metro.png') no-repeat center center/cover;
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
        }
        .hero {
            height: 70vh;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-shadow: 2px 2px 8px #000;
        }
        .hero h1 {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        .btn-custom {
            padding: 12px 30px;
            font-size: 1.1rem;
            border-radius: 25px;
        }
        .modal-content {
            border-radius: 15px;
        }
        .modal-header {
            border-radius: 15px 15px 0 0;
        }
        footer {
            background-color: #1a1a1a;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: auto;
        }
        .about-section {
            background-color: rgba(255, 255, 255, 0.95);
            margin: 50px 0;
            padding: 50px 0;
            border-radius: 15px;
        }
        .feature-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 3rem;
            color: #007bff;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-train"></i> Jaipur Metro Complaints</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <button class="btn btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#registerModal">
                            <i class="fas fa-user-plus"></i> Register
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero text-center">
        <div class="container">
            <h1><i class="fas fa-comments"></i> Smart Complaint Management</h1>
            <p>Efficiently manage and resolve commuter complaints in Jaipur Metro</p>
            <button class="btn btn-primary btn-custom" data-bs-toggle="modal" data-bs-target="#loginModal">
                <i class="fas fa-play"></i> Get Started
            </button>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <h2 class="text-center mb-5">About This Portal</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-edit feature-icon"></i>
                        <h4>Easy Complaint Filing</h4>
                        <p>Submit your complaints quickly and easily with our user-friendly interface.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-search feature-icon"></i>
                        <h4>Real-time Tracking</h4>
                        <p>Track the status of your complaints in real-time and get updates.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <i class="fas fa-users feature-icon"></i>
                        <h4>Admin Management</h4>
                        <p>Efficient admin panel for staff to manage and resolve complaints quickly.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2025 Jaipur Metro | Smart Complaint Management System</p>
            <p><i class="fas fa-envelope"></i> support@jaipurmetro.com | <i class="fas fa-phone"></i> +91-141-XXXXXX</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="loginModalLabel"><i class="fas fa-sign-in-alt"></i> Login</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="login">
                    <div class="modal-body">
                        <?php if ($error && isset($_POST['action']) && $_POST['action'] === 'login'): ?>
                            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="loginEmail" class="form-label"><i class="fas fa-envelope"></i> Email address</label>
                            <input type="email" class="form-control" id="loginEmail" name="email" required />
                        </div>
                        <div class="mb-3">
                            <label for="loginPassword" class="form-label"><i class="fas fa-lock"></i> Password</label>
                            <input type="password" class="form-control" id="loginPassword" name="password" required />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="registerModalLabel"><i class="fas fa-user-plus"></i> Register</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="register">
                    <div class="modal-body">
                        <?php if ($error && isset($_POST['action']) && $_POST['action'] === 'register'): ?>
                            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="regName" class="form-label"><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" class="form-control" name="name" required />
                        </div>
                        <div class="mb-3">
                            <label for="regEmail" class="form-label"><i class="fas fa-envelope"></i> Email address</label>
                            <input type="email" class="form-control" name="email" required />
                        </div>
                        <div class="mb-3">
                            <label for="regPassword" class="form-label"><i class="fas fa-lock"></i> Password</label>
                            <input type="password" class="form-control" name="password" required />
                        </div>
                        <div class="mb-3">
                            <label for="regConfirmPassword" class="form-label"><i class="fas fa-lock"></i> Confirm Password</label>
                            <input type="password" class="form-control" name="confirm_password" required />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success"><i class="fas fa-user-plus"></i> Register</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Auto-open modals based on errors
        <?php if ($error && isset($_POST['action']) && $_POST['action'] === 'login'): ?>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('loginModal')).show();
            });
        <?php endif; ?>
        
        <?php if (($error || $success) && isset($_POST['action']) && $_POST['action'] === 'register'): ?>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('registerModal')).show();
            });
        <?php endif; ?>
    </script>

</body>
</html>