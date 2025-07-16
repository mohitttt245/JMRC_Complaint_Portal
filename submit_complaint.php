<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireLogin();

$userName = getCurrentUserName();
$userId = getCurrentUserId();
$success = '';
$error = '';

// Handle complaint submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = sanitizeInput($_POST['subject']);
    $type = sanitizeInput($_POST['type']);
    $description = sanitizeInput($_POST['description']);
    
    if (!empty($subject) && !empty($type) && !empty($description)) {
        try {
            // Handle file upload
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/';
                
                // Create uploads directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($fileExtension, $allowedExtensions)) {
                    $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
                    $imagePath = $uploadDir . $fileName;
                    
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                        $imagePath = null;
                        $error = "Failed to upload image.";
                    }
                } else {
                    $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
                }
            }
            
            if (empty($error)) {
                $db = getDB();
                $stmt = $db->prepare("INSERT INTO complaints (user_id, subject, complaint_type, description, image_path) VALUES (?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$userId, $subject, $type, $description, $imagePath])) {
                    $success = "Complaint submitted successfully! You can track its status from your dashboard.";
                    // Clear form data
                    $_POST = array();
                } else {
                    $error = "Failed to submit complaint. Please try again.";
                }
            }
        } catch (Exception $e) {
            $error = "Failed to submit complaint. Please try again.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Submit Complaint - Jaipur Metro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .page-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .form-container {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .form-label {
            font-weight: 600;
            color: #333;
        }
        .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        .btn-custom {
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
        }
        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 3rem;
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 8px;
            display: none;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-train"></i> Jaipur Metro</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="submit_complaint.php"><i class="fas fa-edit"></i> Submit Complaint</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($userName); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-edit"></i> Submit a Complaint</h1>
                    <p class="lead">Help us improve Jaipur Metro services by reporting issues or concerns.</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard.php" class="btn btn-light btn-custom">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Complaint Form -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-container">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="subject" class="form-label">
                                <i class="fas fa-heading"></i> Complaint Subject *
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="subject" 
                                   name="subject" 
                                   placeholder="Brief description of your complaint"
                                   value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>"
                                   required>
                        </div>

                        <div class="mb-4">
                            <label for="type" class="form-label">
                                <i class="fas fa-tags"></i> Complaint Type *
                            </label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">-- Select Complaint Type --</option>
                                <option value="Train Delay" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Train Delay') ? 'selected' : ''; ?>>Train Delay</option>
                                <option value="Cleanliness Issue" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Cleanliness Issue') ? 'selected' : ''; ?>>Cleanliness Issue</option>
                                <option value="Staff Misbehavior" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Staff Misbehavior') ? 'selected' : ''; ?>>Staff Misbehavior</option>
                                <option value="Technical Glitch" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Technical Glitch') ? 'selected' : ''; ?>>Technical Glitch</option>
                                <option value="Safety Issue" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Safety Issue') ? 'selected' : ''; ?>>Safety Issue</option>
                                <option value="Ticketing Issue" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Ticketing Issue') ? 'selected' : ''; ?>>Ticketing Issue</option>
                                <option value="Others" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Others') ? 'selected' : ''; ?>>Others</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">
                                <i class="fas fa-comment"></i> Detailed Description *
                            </label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="6" 
                                      placeholder="Please provide detailed information about your complaint including date, time, location, and specific details..."
                                      required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="image" class="form-label">
                                <i class="fas fa-image"></i> Upload Image (Optional)
                            </label>
                            <input class="form-control" 
                                   type="file" 
                                   id="image" 
                                   name="image" 
                                   accept="image/*"
                                   onchange="previewImage(this)">
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i> Supported formats: JPG, JPEG, PNG, GIF. Max size: 5MB
                            </div>
                            <img id="preview" class="preview-image" alt="Preview">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-success btn-custom w-100">
                                    <i class="fas fa-paper-plane"></i> Submit Complaint
                                </button>
                            </div>
                            <div class="col-md-6">
                                <a href="dashboard.php" class="btn btn-outline-secondary btn-custom w-100">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Complaint Guidelines -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Complaint Guidelines</h5>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li>Provide as much detail as possible including date, time, and location</li>
                            <li>Be specific about the issue and how it affected your journey</li>
                            <li>Include any relevant evidence such as photos or videos</li>
                            <li>Use respectful language when describing staff or situations</li>
                            <li>You will receive email updates about your complaint status</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2025 Jaipur Metro | Smart Complaint Management System</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Image preview function
        function previewImage(input) {
            const preview = document.getElementById('preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }

        // Character counter for description
        const description = document.getElementById('description');
        const counter = document.createElement('div');
        counter.className = 'form-text text-end';
        counter.innerHTML = '<span id="charCount">0</span>/1000 characters';
        description.parentNode.appendChild(counter);

        description.addEventListener('input', function() {
            const count = this.value.length;
            document.getElementById('charCount').textContent = count;
            
            if (count > 800) {
                counter.className = 'form-text text-end text-warning';
            } else if (count > 950) {
                counter.className = 'form-text text-end text-danger';
            } else {
                counter.className = 'form-text text-end';
            }
        });
    </script>

</body>
</html>