<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireLogin();

$userName = getCurrentUserName();
$userId = getCurrentUserId();
$isAdminUser = isAdmin();

// Get user's complaints
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM complaints WHERE user_id = ? ORDER BY submitted_at DESC");
    $stmt->execute([$userId]);
    $complaints = $stmt->fetchAll();
} catch (Exception $e) {
    $complaints = [];
}

// Get complaint statistics
try {
    $stmt = $db->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
        FROM complaints WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
} catch (Exception $e) {
    $stats = ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'resolved' => 0, 'rejected' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dashboard - Jaipur Metro Complaint Portal</title>
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
        .dashboard-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            text-align: center;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .action-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        .action-card:hover {
            transform: translateY(-5px);
        }
        .action-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .main-content {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .badge-status {
            font-size: 0.75rem;
            padding: 0.4rem 0.8rem;
        }
        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 3rem;
        }
        .btn-custom {
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 500;
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
                        <a class="nav-link active" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="submit_complaint.php"><i class="fas fa-edit"></i> Submit Complaint</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($isAdminUser): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/dashboard.php"><i class="fas fa-user-shield"></i> Admin Panel</a>
                    </li>
                    <?php endif; ?>
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

    <!-- Dashboard Header -->
    <section class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-tachometer-alt"></i> Welcome, <?php echo htmlspecialchars($userName); ?>!</h1>
                    <p class="lead">Manage your complaints and track their status from your personal dashboard.</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="submit_complaint.php" class="btn btn-light btn-custom">
                        <i class="fas fa-plus"></i> New Complaint
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-primary">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-number text-primary"><?php echo $stats['total']; ?></div>
                    <div>Total Complaints</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number text-warning"><?php echo $stats['pending']; ?></div>
                    <div>Pending</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-info">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-number text-info"><?php echo $stats['in_progress']; ?></div>
                    <div>In Progress</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number text-success"><?php echo $stats['resolved']; ?></div>
                    <div>Resolved</div>
                </div>
            </div>
        </div>

        <!-- Action Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="action-card">
                    <div class="action-icon text-primary">
                        <i class="fas fa-edit"></i>
                    </div>
                    <h4>Submit New Complaint</h4>
                    <p>File a new complaint about any issue you've experienced with Jaipur Metro services.</p>
                    <a href="submit_complaint.php" class="btn btn-primary btn-custom">
                        <i class="fas fa-plus"></i> Submit Complaint
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="action-card">
                    <div class="action-icon text-info">
                        <i class="fas fa-search"></i>
                    </div>
                    <h4>Track Complaints</h4>
                    <p>View and track the status of all your previously submitted complaints.</p>
                    <a href="#complaints-table" class="btn btn-info btn-custom">
                        <i class="fas fa-eye"></i> View Complaints
                    </a>
                </div>
            </div>
            <?php if ($isAdminUser): ?>
            <div class="col-md-4">
                <div class="action-card">
                    <div class="action-icon text-danger">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h4>Admin Panel</h4>
                    <p>Access the administrative panel to manage all complaints and users.</p>
                    <a href="admin/dashboard.php" class="btn btn-danger btn-custom">
                        <i class="fas fa-cog"></i> Admin Panel
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Complaints -->
        <div class="main-content" id="complaints-table">
            <h3 class="mb-4"><i class="fas fa-list"></i> Your Recent Complaints</h3>
            
            <?php if (count($complaints) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Submitted</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($complaints as $complaint): ?>
                        <tr>
                            <td>#<?php echo $complaint['id']; ?></td>
                            <td><?php echo htmlspecialchars($complaint['subject']); ?></td>
                            <td><?php echo htmlspecialchars($complaint['complaint_type']); ?></td>
                            <td>
                                <?php
                                $statusClass = '';
                                switch ($complaint['status']) {
                                    case 'Pending': $statusClass = 'bg-warning'; break;
                                    case 'In Progress': $statusClass = 'bg-info'; break;
                                    case 'Resolved': $statusClass = 'bg-success'; break;
                                    case 'Rejected': $statusClass = 'bg-danger'; break;
                                }
                                ?>
                                <span class="badge <?php echo $statusClass; ?> badge-status">
                                    <?php echo $complaint['status']; ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $priorityClass = '';
                                switch ($complaint['priority']) {
                                    case 'Low': $priorityClass = 'text-success'; break;
                                    case 'Medium': $priorityClass = 'text-warning'; break;
                                    case 'High': $priorityClass = 'text-danger'; break;
                                    case 'Critical': $priorityClass = 'text-danger fw-bold'; break;
                                }
                                ?>
                                <span class="<?php echo $priorityClass; ?>">
                                    <?php echo $complaint['priority']; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($complaint['submitted_at'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($complaint['updated_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No Complaints Yet</h4>
                <p class="text-muted">You haven't submitted any complaints yet. Click the button below to submit your first complaint.</p>
                <a href="submit_complaint.php" class="btn btn-primary btn-custom">
                    <i class="fas fa-plus"></i> Submit Your First Complaint
                </a>
            </div>
            <?php endif; ?>
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

</body>
</html>