<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

// Set role variable for sidebar
$role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'guest';
$_SESSION['role'] = $role;

// Handle patient activation/deactivation
if (isset($_POST['action']) && isset($_POST['patient_id'])) {
    $patient_id = (int)$_POST['patient_id'];
    $action = $_POST['action'];
    
    if ($action === 'activate') {
        $stmt = $pdo->prepare("UPDATE patients SET status = 'active' WHERE id = ?");
        $stmt->execute([$patient_id]);
    } elseif ($action === 'deactivate') {
        $stmt = $pdo->prepare("UPDATE patients SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$patient_id]);
    }
    
    header('Location: patients.php?success=1');
    exit();
}

// Handle "Send to Doctor" action
if (isset($_POST['send_to_doctor']) && isset($_POST['patient_id'])) {
    $patient_id = (int)$_POST['patient_id'];
    $notes = trim($_POST['notes'] ?? '');
    $priority = $_POST['priority'] ?? 'normal';
    $user_id = $_SESSION['user_id'];
    
    // Check if patient is already in queue
    $stmt = $pdo->prepare("SELECT id FROM doctor_queue WHERE patient_id = ? AND status IN ('waiting', 'in_progress')");
    $stmt->execute([$patient_id]);
    
    if (!$stmt->fetch()) {
        // Add patient to queue
        $stmt = $pdo->prepare("
            INSERT INTO doctor_queue (patient_id, sent_by, notes, priority) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$patient_id, $user_id, $notes, $priority]);
        
        header('Location: patients.php?success=2');
        exit();
    } else {
        header('Location: patients.php?error=1');
        exit();
    }
}

// Search and filter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE ? OR card_number LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM patients $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_patients = $stmt->fetch()['total'];
$total_pages = ceil($total_patients / $per_page);

// Get patients
$sql = "
    SELECT p.*, 
           COUNT(v.id) as visit_count,
           MAX(v.created_at) as last_visit
    FROM patients p 
    LEFT JOIN visits v ON p.id = v.patient_id 
    $where_clause 
    GROUP BY p.id 
    ".(
        $role === 'doctor' ? 'ORDER BY p.status = "inactive" DESC, p.date_registered ASC' : 'ORDER BY p.full_name'
    )."
    LIMIT $per_page OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients - EMR Clinic System</title>
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
                        <i class="fas fa-users"></i> Patients
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if ($role === 'receptionist' || $role === 'admin'): ?>
                            <a href="add_patient.php" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Add Patient
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> 
                        <?php if ($_GET['success'] == '1'): ?>
                            Patient status updated successfully!
                        <?php elseif ($_GET['success'] == '2'): ?>
                            Patient sent to doctor successfully!
                        <?php endif; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <?php if ($_GET['error'] == '1'): ?>
                            Patient is already in the doctor's queue!
                        <?php endif; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Search and Filter -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">
                                    <i class="fas fa-search"></i> Search
                                </label>
                                <input type="text" class="form-control patient-search" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Name, card number, or phone">
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">
                                    <i class="fas fa-filter"></i> Status
                                </label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="patients.php" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Patients Table -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i> Patient List 
                            <span class="badge bg-secondary"><?php echo $total_patients; ?> patients</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($patients)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No patients found</h5>
                                <p class="text-muted">Try adjusting your search criteria<?php if ($role === 'receptionist' || $role === 'admin'): ?> or add a new patient<?php endif; ?>.</p>
                                <?php if ($role === 'receptionist' || $role === 'admin'): ?>
                                    <a href="add_patient.php" class="btn btn-primary">
                                        <i class="fas fa-user-plus"></i> Add First Patient
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Card Number</th>
                                            <th>Sex</th>
                                            <th>Phone</th>
                                            <th>Status</th>
                                            <th>Visits</th>
                                            <th>Last Visit</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($patients as $patient): ?>
                                            <tr class="patient-card<?php echo $patient['status'] === 'inactive' ? ' table-warning' : ''; ?>">
                                                <td>
                                                    <?php if ($role === 'doctor'): ?>
                                                        <a href="visit_form.php?patient_id=<?php echo $patient['id']; ?>" class="fw-bold text-decoration-underline">
                                                            <?php echo htmlspecialchars($patient['full_name']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <strong><?php echo htmlspecialchars($patient['full_name']); ?></strong>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($patient['card_number']); ?></code>
                                                </td>
                                                <td>
                                                    <i class="fas fa-<?php echo $patient['sex'] === 'Male' ? 'mars text-primary' : 'venus text-danger'; ?>"></i>
                                                    <?php echo $patient['sex']; ?>
                                                </td>
                                                <td>
                                                    <?php echo $patient['phone'] ? htmlspecialchars($patient['phone']) : '<span class="text-muted">-</span>'; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $patient['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                        <?php echo ucfirst($patient['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $patient['visit_count']; ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($patient['last_visit']): ?>
                                                        <?php echo date('M j, Y', strtotime($patient['last_visit'])); ?>
                                                    </td>
                                                    <?php else: ?>
                                                        <span class="text-muted">No visits</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <?php if ($role === 'receptionist' || $role === 'admin'): ?>
                                                            <button type="button" class="btn btn-sm btn-primary" 
                                                                    data-bs-toggle="modal" data-bs-target="#sendToDoctorModal<?php echo $patient['id']; ?>" 
                                                                    title="Send to Doctor">
                                                                <i class="fas fa-user-md"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <a href="visit_form.php?patient_id=<?php echo $patient['id']; ?>" 
                                                           class="btn btn-sm btn-success" title="New Visit">
                                                            <i class="fas fa-stethoscope"></i>
                                                        </a>
                                                        <a href="visits.php?patient_id=<?php echo $patient['id']; ?>" 
                                                           class="btn btn-sm btn-info" title="View Visits">
                                                            <i class="fas fa-history"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-warning" 
                                                                data-bs-toggle="modal" data-bs-target="#editPatientModal<?php echo $patient['id']; ?>" 
                                                                title="Edit Patient">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- Status toggle -->
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                                                        <?php if ($patient['status'] === 'active'): ?>
                                                            <button type="submit" name="action" value="deactivate" 
                                                                    class="btn btn-sm btn-outline-warning" 
                                                                    onclick="return confirm('Deactivate this patient?')" 
                                                                    title="Deactivate">
                                                                <i class="fas fa-user-slash"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="submit" name="action" value="activate" 
                                                                    class="btn btn-sm btn-outline-success" 
                                                                    onclick="return confirm('Activate this patient?')" 
                                                                    title="Activate">
                                                                <i class="fas fa-user-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Patient pagination">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                    <i class="fas fa-chevron-left"></i> Previous
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                    Next <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Send to Doctor Modals -->
    <?php foreach ($patients as $patient): ?>
        <div class="modal fade" id="sendToDoctorModal<?php echo $patient['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-user-md"></i> Send to Doctor
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <p>Send <strong><?php echo htmlspecialchars($patient['full_name']); ?></strong> to the doctor?</p>
                            
                            <div class="mb-3">
                                <label for="priority<?php echo $patient['id']; ?>" class="form-label">Priority</label>
                                <select class="form-select" name="priority" id="priority<?php echo $patient['id']; ?>">
                                    <option value="normal">Normal</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="emergency">Emergency</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes<?php echo $patient['id']; ?>" class="form-label">Notes (Optional)</label>
                                <textarea class="form-control" name="notes" id="notes<?php echo $patient['id']; ?>" rows="3" 
                                          placeholder="Any notes for the doctor..."></textarea>
                            </div>
                            
                            <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="send_to_doctor" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send to Doctor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../public/js/app.js"></script>
</body>
</html>