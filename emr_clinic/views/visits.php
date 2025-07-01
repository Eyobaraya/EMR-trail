<?php
// visits.php

session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if user has permission to view visits (doctor or admin only)
if (!in_array($_SESSION['role'], ['doctor', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Set role variable for sidebar
$role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'guest';
$_SESSION['role'] = $role;

$message = '';
$error = '';

// Handle visit deletion
if (isset($_POST['delete_visit']) && isset($_POST['visit_id'])) {
    $visit_id = (int)$_POST['visit_id'];
    $stmt = $pdo->prepare("DELETE FROM visits WHERE id = ?");
    if ($stmt->execute([$visit_id])) {
        $message = 'Visit deleted successfully.';
    } else {
        $error = 'Error deleting visit.';
    }
}

// Get filter parameters
$patient_search = $_GET['patient_search'] ?? '';
$visit_type_filter = $_GET['visit_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($patient_search)) {
    $where_conditions[] = "p.full_name LIKE ?";
    $params[] = "%$patient_search%";
}

if (!empty($visit_type_filter)) {
    $where_conditions[] = "v.visit_type = ?";
    $params[] = $visit_type_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(v.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(v.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get visits with patient and doctor information
$query = "
    SELECT v.*, p.full_name as patient_name, p.card_number, u.full_name as doctor_name
    FROM visits v
    JOIN patients p ON v.patient_id = p.id
    JOIN users u ON v.user_id = u.id
    $where_clause
    ORDER BY v.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visits - EMR Clinic System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../public/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../templates/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../templates/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-stethoscope"></i> Medical Visits
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="visit_form.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> New Visit
                        </a>
                    </div>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Filters -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-filter"></i> Filter Visits
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="patient_search" class="form-label">Patient Name</label>
                                <input type="text" class="form-control" id="patient_search" name="patient_search" 
                                       value="<?php echo htmlspecialchars($patient_search); ?>" placeholder="Search patient...">
                            </div>
                            <div class="col-md-2">
                                <label for="visit_type" class="form-label">Visit Type</label>
                                <select class="form-select" id="visit_type" name="visit_type">
                                    <option value="">All Types</option>
                                    <option value="general" <?php echo $visit_type_filter === 'general' ? 'selected' : ''; ?>>General</option>
                                    <option value="emergency" <?php echo $visit_type_filter === 'emergency' ? 'selected' : ''; ?>>Emergency</option>
                                    <option value="ultrasound" <?php echo $visit_type_filter === 'ultrasound' ? 'selected' : ''; ?>>Ultrasound</option>
                                    <option value="lab" <?php echo $visit_type_filter === 'lab' ? 'selected' : ''; ?>>Lab</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" 
                                       value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" 
                                       value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="visits.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Visits Table -->
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-list"></i> Visit Records (<?php echo count($visits); ?>)
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($visits)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No visits found</h5>
                                <p class="text-muted">No visits match your current filters.</p>
                                <a href="visit_form.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create First Visit
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Patient</th>
                                            <th>Card #</th>
                                            <th>Type</th>
                                            <th>Doctor</th>
                                            <th>Symptoms</th>
                                            <th>Diagnosis</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($visits as $visit): ?>
                                            <tr>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('M d, Y', strtotime($visit['created_at'])); ?>
                                                    </small>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo date('h:i A', strtotime($visit['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($visit['patient_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($visit['card_number']); ?></span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $type_colors = [
                                                        'general' => 'primary',
                                                        'emergency' => 'danger',
                                                        'ultrasound' => 'info',
                                                        'lab' => 'warning'
                                                    ];
                                                    $color = $type_colors[$visit['visit_type']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?php echo $color; ?>">
                                                        <?php echo ucfirst($visit['visit_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($visit['doctor_name']); ?></td>
                                                <td>
                                                    <?php if (!empty($visit['symptoms'])): ?>
                                                        <span class="text-truncate d-inline-block" style="max-width: 150px;" 
                                                              title="<?php echo htmlspecialchars($visit['symptoms']); ?>">
                                                            <?php echo htmlspecialchars(substr($visit['symptoms'], 0, 50)); ?>
                                                            <?php echo strlen($visit['symptoms']) > 50 ? '...' : ''; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($visit['diagnosis'])): ?>
                                                        <span class="text-truncate d-inline-block" style="max-width: 150px;" 
                                                              title="<?php echo htmlspecialchars($visit['diagnosis']); ?>">
                                                            <?php echo htmlspecialchars(substr($visit['diagnosis'], 0, 50)); ?>
                                                            <?php echo strlen($visit['diagnosis']) > 50 ? '...' : ''; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-primary" 
                                                                data-bs-toggle="modal" data-bs-target="#visitModal<?php echo $visit['id']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <a href="visit_form.php?edit=<?php echo $visit['id']; ?>" 
                                                           class="btn btn-outline-secondary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="confirmDelete(<?php echo $visit['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            <!-- Visit Detail Modal -->
                                            <div class="modal fade" id="visitModal<?php echo $visit['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">
                                                                <i class="fas fa-stethoscope"></i> Visit Details
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <h6>Patient Information</h6>
                                                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($visit['patient_name']); ?></p>
                                                                    <p><strong>Card #:</strong> <?php echo htmlspecialchars($visit['card_number']); ?></p>
                                                                    <p><strong>Visit Date:</strong> <?php echo date('F d, Y h:i A', strtotime($visit['created_at'])); ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h6>Visit Information</h6>
                                                                    <p><strong>Type:</strong> 
                                                                        <span class="badge bg-<?php echo $color; ?>">
                                                                            <?php echo ucfirst($visit['visit_type']); ?>
                                                                        </span>
                                                                    </p>
                                                                    <p><strong>Doctor:</strong> <?php echo htmlspecialchars($visit['doctor_name']); ?></p>
                                                                </div>
                                                            </div>
                                                            
                                                            <?php if (!empty($visit['symptoms'])): ?>
                                                                <div class="mt-3">
                                                                    <h6>Symptoms</h6>
                                                                    <p class="border rounded p-3 bg-light"><?php echo nl2br(htmlspecialchars($visit['symptoms'])); ?></p>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (!empty($visit['diagnosis'])): ?>
                                                                <div class="mt-3">
                                                                    <h6>Diagnosis</h6>
                                                                    <p class="border rounded p-3 bg-light"><?php echo nl2br(htmlspecialchars($visit['diagnosis'])); ?></p>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (!empty($visit['prescription'])): ?>
                                                                <div class="mt-3">
                                                                    <h6>Prescription</h6>
                                                                    <p class="border rounded p-3 bg-light"><?php echo nl2br(htmlspecialchars($visit['prescription'])); ?></p>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <a href="visit_form.php?edit=<?php echo $visit['id']; ?>" class="btn btn-primary">
                                                                <i class="fas fa-edit"></i> Edit Visit
                                                            </a>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle text-danger"></i> Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this visit? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="visit_id" id="deleteVisitId">
                        <input type="hidden" name="delete_visit" value="1">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Visit
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(visitId) {
            document.getElementById('deleteVisitId').value = visitId;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>