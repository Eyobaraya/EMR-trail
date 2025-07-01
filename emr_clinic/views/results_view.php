<?php
// results_view.php - View lab results and ultrasound reports for doctors

session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if user has permission (doctor or admin only)
if (!in_array($_SESSION['role'], ['doctor', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Set role variable for sidebar
$role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'guest';
$_SESSION['role'] = $role;
$user_id = $_SESSION['user_id'];

// Get filter parameters
$filter_type = $_GET['type'] ?? 'all';
$patient_search = $_GET['patient_search'] ?? '';

// Get lab results for this doctor
$lab_query = "
    SELECT 
        lr.id,
        lr.tests_requested,
        lr.result_text,
        lr.status,
        lr.date_requested,
        lr.date_completed,
        p.full_name as patient_name,
        p.card_number,
        p.sex
    FROM lab_requests lr
    JOIN patients p ON lr.patient_id = p.id
    WHERE lr.doctor_id = ?
";

if ($patient_search) {
    $lab_query .= " AND p.full_name LIKE ?";
}

$lab_query .= " ORDER BY lr.date_completed DESC, lr.date_requested DESC";

$stmt = $pdo->prepare($lab_query);
if ($patient_search) {
    $stmt->execute([$user_id, "%$patient_search%"]);
} else {
    $stmt->execute([$user_id]);
}
$lab_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get ultrasound reports for this doctor
$ultrasound_query = "
    SELECT 
        ur.id,
        ur.report_text,
        ur.created_at,
        ur.technician_id,
        p.full_name as patient_name,
        p.card_number,
        p.sex,
        u.full_name as technician_name
    FROM ultrasound_reports ur
    JOIN patients p ON ur.patient_id = p.id
    LEFT JOIN users u ON ur.technician_id = u.id
    WHERE ur.requested_by = ?
";

if ($patient_search) {
    $ultrasound_query .= " AND p.full_name LIKE ?";
}

$ultrasound_query .= " ORDER BY ur.created_at DESC";

$stmt = $pdo->prepare($ultrasound_query);
if ($patient_search) {
    $stmt->execute([$user_id, "%$patient_search%"]);
} else {
    $stmt->execute([$user_id]);
}
$ultrasound_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM lab_requests WHERE doctor_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completed_lab_count = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM ultrasound_reports WHERE requested_by = ? AND technician_id IS NOT NULL");
$stmt->execute([$user_id]);
$completed_ultrasound_count = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results View - EMR Clinic System</title>
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
                        <i class="fas fa-file-medical"></i> Results View
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="lab_request.php" class="btn btn-primary me-2">
                            <i class="fas fa-flask"></i> Lab Requests
                        </a>
                        <a href="ultrasound.php" class="btn btn-info">
                            <i class="fas fa-baby"></i> Ultrasound
                        </a>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Completed Lab Tests</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $completed_lab_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-flask fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Completed Ultrasounds</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $completed_ultrasound_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-baby fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-filter"></i> Filter Results
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="patient_search" class="form-label">Patient Name</label>
                                <input type="text" class="form-control" id="patient_search" name="patient_search" 
                                       value="<?php echo htmlspecialchars($patient_search); ?>" placeholder="Search patient...">
                            </div>
                            <div class="col-md-3">
                                <label for="type" class="form-label">Result Type</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Results</option>
                                    <option value="lab" <?php echo $filter_type === 'lab' ? 'selected' : ''; ?>>Lab Results</option>
                                    <option value="ultrasound" <?php echo $filter_type === 'ultrasound' ? 'selected' : ''; ?>>Ultrasound Reports</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="results_view.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lab Results -->
                <?php if ($filter_type === 'all' || $filter_type === 'lab'): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-flask"></i> Lab Results
                                        <span class="badge bg-primary"><?php echo count($lab_results); ?> results</span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($lab_results)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-flask fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No Lab Results</h5>
                                            <p class="text-muted">No completed lab tests found.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Patient</th>
                                                        <th>Tests</th>
                                                        <th>Results</th>
                                                        <th>Status</th>
                                                        <th>Completed</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($lab_results as $result): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($result['patient_name']); ?></strong><br>
                                                                <small class="text-muted">
                                                                    Card: <?php echo htmlspecialchars($result['card_number']); ?> | 
                                                                    <?php echo $result['sex']; ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <?php echo htmlspecialchars($result['tests_requested']); ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($result['result_text']): ?>
                                                                    <?php echo htmlspecialchars(substr($result['result_text'], 0, 50)); ?>
                                                                    <?php if (strlen($result['result_text']) > 50): ?>...<?php endif; ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">No results yet</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                $status_colors = [
                                                                    'pending' => 'warning',
                                                                    'completed' => 'success'
                                                                ];
                                                                $color = $status_colors[$result['status']] ?? 'secondary';
                                                                ?>
                                                                <span class="badge bg-<?php echo $color; ?>">
                                                                    <?php echo ucfirst($result['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php if ($result['date_completed']): ?>
                                                                    <small class="text-muted">
                                                                        <?php echo date('M j, Y h:i A', strtotime($result['date_completed'])); ?>
                                                                    </small>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                        data-bs-toggle="modal" data-bs-target="#labModal<?php echo $result['id']; ?>">
                                                                    <i class="fas fa-eye"></i> View
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        
                                                        <!-- Modal for lab result details -->
                                                        <div class="modal fade" id="labModal<?php echo $result['id']; ?>" tabindex="-1">
                                                            <div class="modal-dialog modal-lg">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">
                                                                            Lab Result Details
                                                                        </h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <div class="row">
                                                                            <div class="col-md-6">
                                                                                <h6>Patient Information</h6>
                                                                                <p><strong>Name:</strong> <?php echo htmlspecialchars($result['patient_name']); ?></p>
                                                                                <p><strong>Card #:</strong> <?php echo htmlspecialchars($result['card_number']); ?></p>
                                                                                <p><strong>Sex:</strong> <?php echo $result['sex']; ?></p>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <h6>Test Information</h6>
                                                                                <p><strong>Status:</strong> 
                                                                                    <span class="badge bg-<?php echo $color; ?>">
                                                                                        <?php echo ucfirst($result['status']); ?>
                                                                                    </span>
                                                                                </p>
                                                                                <p><strong>Requested:</strong> <?php echo date('F d, Y h:i A', strtotime($result['date_requested'])); ?></p>
                                                                                <?php if ($result['date_completed']): ?>
                                                                                    <p><strong>Completed:</strong> <?php echo date('F d, Y h:i A', strtotime($result['date_completed'])); ?></p>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <hr>
                                                                        <h6>Tests Requested</h6>
                                                                        <p><?php echo nl2br(htmlspecialchars($result['tests_requested'])); ?></p>
                                                                        
                                                                        <?php if ($result['result_text']): ?>
                                                                            <hr>
                                                                            <h6>Results</h6>
                                                                            <p><?php echo nl2br(htmlspecialchars($result['result_text'])); ?></p>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="modal-footer">
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
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Ultrasound Reports -->
                <?php if ($filter_type === 'all' || $filter_type === 'ultrasound'): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-baby"></i> Ultrasound Reports
                                        <span class="badge bg-info"><?php echo count($ultrasound_reports); ?> reports</span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($ultrasound_reports)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-baby fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No Ultrasound Reports</h5>
                                            <p class="text-muted">No completed ultrasound reports found.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Patient</th>
                                                        <th>Report</th>
                                                        <th>Status</th>
                                                        <th>Date</th>
                                                        <th>Technician</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($ultrasound_reports as $report): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($report['patient_name']); ?></strong><br>
                                                                <small class="text-muted">
                                                                    Card: <?php echo htmlspecialchars($report['card_number']); ?> | 
                                                                    <?php echo $report['sex']; ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <?php echo htmlspecialchars(substr($report['report_text'], 0, 50)); ?>
                                                                <?php if (strlen($report['report_text']) > 50): ?>...<?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($report['technician_id']): ?>
                                                                    <span class="badge bg-success">Completed</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-warning">Pending</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo date('M j, Y h:i A', strtotime($report['created_at'])); ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <?php if ($report['technician_name']): ?>
                                                                    <?php echo htmlspecialchars($report['technician_name']); ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                        data-bs-toggle="modal" data-bs-target="#ultrasoundModal<?php echo $report['id']; ?>">
                                                                    <i class="fas fa-eye"></i> View
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        
                                                        <!-- Modal for ultrasound report details -->
                                                        <div class="modal fade" id="ultrasoundModal<?php echo $report['id']; ?>" tabindex="-1">
                                                            <div class="modal-dialog modal-lg">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">
                                                                            Ultrasound Report Details
                                                                        </h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <div class="row">
                                                                            <div class="col-md-6">
                                                                                <h6>Patient Information</h6>
                                                                                <p><strong>Name:</strong> <?php echo htmlspecialchars($report['patient_name']); ?></p>
                                                                                <p><strong>Card #:</strong> <?php echo htmlspecialchars($report['card_number']); ?></p>
                                                                                <p><strong>Sex:</strong> <?php echo $report['sex']; ?></p>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <h6>Report Information</h6>
                                                                                <p><strong>Status:</strong> 
                                                                                    <?php if ($report['technician_id']): ?>
                                                                                        <span class="badge bg-success">Completed</span>
                                                                                    <?php else: ?>
                                                                                        <span class="badge bg-warning">Pending</span>
                                                                                    <?php endif; ?>
                                                                                </p>
                                                                                <p><strong>Date:</strong> <?php echo date('F d, Y h:i A', strtotime($report['created_at'])); ?></p>
                                                                                <?php if ($report['technician_name']): ?>
                                                                                    <p><strong>Technician:</strong> <?php echo htmlspecialchars($report['technician_name']); ?></p>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <hr>
                                                                        <h6>Report</h6>
                                                                        <p><?php echo nl2br(htmlspecialchars($report['report_text'])); ?></p>
                                                                    </div>
                                                                    <div class="modal-footer">
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
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 