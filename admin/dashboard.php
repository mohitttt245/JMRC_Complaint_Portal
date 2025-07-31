<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

requireAdmin();

$userName = getCurrentUserName();
$success = '';
$error = '';

// Handle complaint status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $complaintId = (int)$_POST['complaint_id'];
    
    if ($_POST['action'] === 'update_status') {
        $newStatus = sanitizeInput($_POST['status']);
        $adminResponse = sanitizeInput($_POST['admin_response']);
        
        // Validate status
        $validStatuses = ['Pending', 'In Progress', 'Resolved', 'Rejected'];
        if (!in_array($newStatus, $validStatuses)) {
            $error = "Invalid status selected.";
        } elseif ($complaintId <= 0) {
            $error = "Invalid complaint ID.";
        } else {
            try {
                $db = getDB();
                
                // Check if complaint exists
                $checkStmt = $db->prepare("SELECT id FROM complaints WHERE id = ?");
                $checkStmt->execute([$complaintId]);
                
                if ($checkStmt->rowCount() === 0) {
                    $error = "Complaint not found.";
                } else {
                    // Update complaint with proper timestamp handling
                    $updateStmt = $db->prepare("UPDATE complaints SET status = ?, admin_response = ?, updated_at = NOW() WHERE id = ?");
                    
                    if ($updateStmt->execute([$newStatus, $adminResponse, $complaintId])) {
                        $success = "Complaint #$complaintId status updated successfully to '$newStatus'!";
                        
                        // If resolved, set resolved_at timestamp
                        if ($newStatus === 'Resolved') {
                            $resolvedStmt = $db->prepare("UPDATE complaints SET resolved_at = NOW() WHERE id = ?");
                            $resolvedStmt->execute([$complaintId]);
                        }
                    } else {
                        $error = "Failed to update complaint status. Please try again.";
                    }
                }
            } catch (Exception $e) {
                $error = "Database error occurred while updating complaint.";
                error_log("Complaint update error: " . $e->getMessage());
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        if ($complaintId <= 0) {
            $error = "Invalid complaint ID.";
        } else {
            try {
                $db = getDB();
                
                // Check if complaint exists
                $checkStmt = $db->prepare("SELECT id FROM complaints WHERE id = ?");
                $checkStmt->execute([$complaintId]);
                
                if ($checkStmt->rowCount() === 0) {
                    $error = "Complaint not found.";
                } else {
                    $deleteStmt = $db->prepare("DELETE FROM complaints WHERE id = ?");
                    
                    if ($deleteStmt->execute([$complaintId])) {
                        $success = "Complaint #$complaintId deleted successfully!";
                    } else {
                        $error = "Failed to delete complaint. Please try again.";
                    }
                }
            } catch (Exception $e) {
                $error = "Database error occurred while deleting complaint.";
                error_log("Complaint delete error: " . $e->getMessage());
            }
        }
    }
}

// Get all complaints with user information
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.*, u.full_name, u.email 
        FROM complaints c 
        JOIN users u ON c.user_id = u.id 
        ORDER BY c.submitted_at DESC
    ");
    $stmt->execute();
    $complaints = $stmt->fetchAll();
} catch (Exception $e) {
    $complaints = [];
}

// Get statistics
try {
    $stmt = $db->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
        FROM complaints");
    $stmt->execute();
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
    <title>Admin Dashboard - Jaipur Metro</title>
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
        .admin-header {
            background: linear-gradient(135deg, #dc3545, #c82333);
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
            text-align: center;
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
        .main-content {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
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
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            margin: 0 1px;
        }
        .modal-body textarea {
            min-height: 100px;
        }
        .table td {
            vertical-align: middle;
        }
        .actions-column {
            min-width: 160px;
        }
        .complaint-subject {
            max-width: 200px;
            word-wrap: break-word;
        }
        .complaint-description {
            max-width: 150px;
            word-wrap: break-word;
        }
        #viewModal .modal-body h6 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 0.25rem;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-shield-alt"></i> Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php"><i class="fas fa-user"></i> User View</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($userName); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Admin Header -->
    <section class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-shield-alt"></i> Admin Dashboard</h1>
                    <p class="lead">Manage all complaints and monitor system performance.</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark fs-6">
                        <i class="fas fa-clock"></i> <?php echo date('M d, Y H:i'); ?>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <!-- Success/Error Messages -->
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

        <!-- Complaints Management -->
        <div class="main-content">
            <h3 class="mb-4"><i class="fas fa-cogs"></i> Manage All Complaints</h3>
            
            <?php if (count($complaints) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Submitted</th>
                            <th class="actions-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($complaints as $complaint): ?>
                        <tr>
                            <td>#<?php echo $complaint['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($complaint['full_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($complaint['email']); ?></small>
                            </td>
                            <td class="complaint-subject">
                                <strong><?php echo htmlspecialchars(substr($complaint['subject'], 0, 30)); ?><?php echo strlen($complaint['subject']) > 30 ? '...' : ''; ?></strong><br>
                                <small class="text-muted complaint-description"><?php echo htmlspecialchars(substr($complaint['description'], 0, 50)); ?>...</small>
                            </td>
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
                            <td class="actions-column">
                                <div class="btn-group-vertical btn-group-sm d-grid gap-1" role="group">
                                    <button class="btn btn-sm btn-info" onclick="viewComplaint(<?php echo $complaint['id']; ?>, '<?php echo htmlspecialchars($complaint['subject'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($complaint['description'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($complaint['complaint_type'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($complaint['priority'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($complaint['full_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($complaint['email'], ENT_QUOTES); ?>', '<?php echo $complaint['submitted_at']; ?>', '<?php echo htmlspecialchars($complaint['admin_response'] ?? '', ENT_QUOTES); ?>', '<?php echo $complaint['image_path'] ? htmlspecialchars($complaint['image_path'], ENT_QUOTES) : ''; ?>')">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button class="btn btn-sm btn-primary" onclick="editComplaint(<?php echo $complaint['id']; ?>, '<?php echo $complaint['status']; ?>', '<?php echo htmlspecialchars($complaint['admin_response'] ?? '', ENT_QUOTES); ?>')">
                                        <i class="fas fa-edit"></i> Update
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteComplaint(<?php echo $complaint['id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No Complaints Found</h4>
                <p class="text-muted">There are no complaints in the system yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2025 Jaipur Metro | Admin Dashboard</p>
        </div>
    </footer>

    <!-- Update Complaint Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Update Complaint</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="complaint_id" id="updateComplaintId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" name="status" id="updateStatus" required>
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="admin_response" class="form-label">Admin Response</label>
                            <textarea class="form-control" name="admin_response" id="updateResponse" rows="4" 
                                      placeholder="Provide feedback or resolution details..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update Complaint</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-trash"></i> Delete Complaint</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this complaint? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="complaint_id" id="deleteComplaintId">
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Complaint Details Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-eye"></i> Complaint Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-hashtag"></i> Complaint ID</h6>
                            <p id="viewComplaintId" class="text-muted"></p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-calendar"></i> Submitted Date</h6>
                            <p id="viewSubmittedDate" class="text-muted"></p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-user"></i> User Details</h6>
                            <p id="viewUserName" class="fw-bold mb-1"></p>
                            <p id="viewUserEmail" class="text-muted"></p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-tags"></i> Type & Priority</h6>
                            <p id="viewComplaintType" class="mb-1"></p>
                            <p id="viewPriority"></p>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <h6><i class="fas fa-heading"></i> Subject</h6>
                        <p id="viewSubject" class="fw-bold"></p>
                    </div>

                    <div class="mb-3">
                        <h6><i class="fas fa-align-left"></i> Description</h6>
                        <div id="viewDescription" class="border p-3 rounded bg-light" style="white-space: pre-wrap;"></div>
                    </div>

                    <div class="mb-3" id="imageSection" style="display: none;">
                        <h6><i class="fas fa-image"></i> Attached Image</h6>
                        <img id="viewImage" src="" alt="Complaint Image" class="img-fluid rounded border" style="max-height: 300px;">
                    </div>

                    <div class="mb-3" id="responseSection">
                        <h6><i class="fas fa-reply"></i> Admin Response</h6>
                        <div id="viewAdminResponse" class="border p-3 rounded bg-light" style="white-space: pre-wrap;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function editComplaint(id, status, response) {
            document.getElementById('updateComplaintId').value = id;
            document.getElementById('updateStatus').value = status;
            document.getElementById('updateResponse').value = response || '';
            new bootstrap.Modal(document.getElementById('updateModal')).show();
        }

        function deleteComplaint(id) {
            document.getElementById('deleteComplaintId').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        function viewComplaint(id, subject, description, type, priority, userName, userEmail, submittedAt, adminResponse, imagePath) {
            // Set basic details
            document.getElementById('viewComplaintId').textContent = '#' + id;
            document.getElementById('viewSubject').textContent = subject;
            document.getElementById('viewDescription').textContent = description;
            document.getElementById('viewComplaintType').textContent = type;
            document.getElementById('viewUserName').textContent = userName;
            document.getElementById('viewUserEmail').textContent = userEmail;
            
            // Format and set submitted date
            const date = new Date(submittedAt);
            document.getElementById('viewSubmittedDate').textContent = date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Set priority with appropriate styling
            const priorityElement = document.getElementById('viewPriority');
            priorityElement.textContent = priority;
            priorityElement.className = 'mb-1';
            switch(priority) {
                case 'Low': priorityElement.classList.add('text-success'); break;
                case 'Medium': priorityElement.classList.add('text-warning'); break;
                case 'High': priorityElement.classList.add('text-danger'); break;
                case 'Critical': priorityElement.classList.add('text-danger', 'fw-bold'); break;
            }
            
            // Handle admin response
            const responseElement = document.getElementById('viewAdminResponse');
            if (adminResponse && adminResponse.trim() !== '') {
                responseElement.textContent = adminResponse;
                document.getElementById('responseSection').style.display = 'block';
            } else {
                responseElement.textContent = 'No admin response yet.';
                responseElement.classList.add('text-muted', 'fst-italic');
            }
            
            // Handle image
            const imageSection = document.getElementById('imageSection');
            if (imagePath && imagePath.trim() !== '') {
                document.getElementById('viewImage').src = '../' + imagePath;
                imageSection.style.display = 'block';
            } else {
                imageSection.style.display = 'none';
            }
            
            // Show modal
            new bootstrap.Modal(document.getElementById('viewModal')).show();
        }

        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>

</body>
</html>