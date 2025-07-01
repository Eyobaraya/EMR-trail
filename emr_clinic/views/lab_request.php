<?php
// lab_request.php - Lab request management for doctors

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

$message = '';
$error = '';

// Handle form submission for new lab request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_request'])) {
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $tests_requested = trim($_POST['tests_requested'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if ($patient_id <= 0) {
        $error = 'Please select a patient.';
    } elseif (empty($tests_requested)) {
        $error = 'Please specify the tests requested.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO lab_requests (patient_id, doctor_id, tests_requested, result_text, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        
        if ($stmt->execute([$patient_id, $user_id, $tests_requested, $notes])) {
            $message = 'Lab request created successfully!';
        } else {
            $error = 'Error creating lab request. Please try again.';
        }
    }
}

// Get all patients for dropdown
$stmt = $pdo->prepare("
    SELECT id, full_name, card_number, sex, phone
    FROM patients 
    WHERE status = 'active'
    ORDER BY full_name ASC
");
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get lab requests created by this doctor
$stmt = $pdo->prepare("
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
    ORDER BY lr.date_requested DESC
");
$stmt->execute([$user_id]);
$lab_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM lab_requests WHERE doctor_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$pending_count = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM lab_requests WHERE doctor_id = ? AND status = 'completed' AND DATE(date_completed) = CURDATE()");
$stmt->execute([$user_id]);
$completed_today = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Requests - EMR Clinic System</title>
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
                        <i class="fas fa-flask"></i> Lab Requests
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                            <i class="fas fa-plus"></i> New Lab Request
                        </button>
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

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Requests</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed Today</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $completed_today; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lab Requests List -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-list"></i> My Lab Requests
                                    <span class="badge bg-secondary"><?php echo count($lab_requests); ?> requests</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($lab_requests)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-flask fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No Lab Requests</h5>
                                        <p class="text-muted">You haven't created any lab requests yet.</p>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                                            <i class="fas fa-plus"></i> Create First Request
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Patient</th>
                                                    <th>Tests Requested</th>
                                                    <th>Status</th>
                                                    <th>Requested</th>
                                                    <th>Completed</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($lab_requests as $request): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($request['patient_name']); ?></strong><br>
                                                            <small class="text-muted">
                                                                Card: <?php echo htmlspecialchars($request['card_number']); ?> | 
                                                                <?php echo $request['sex']; ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?php echo htmlspecialchars($request['tests_requested']); ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $status_colors = [
                                                                'pending' => 'warning',
                                                                'completed' => 'success'
                                                            ];
                                                            $color = $status_colors[$request['status']] ?? 'secondary';
                                                            ?>
                                                            <span class="badge bg-<?php echo $color; ?>">
                                                                <?php echo ucfirst($request['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">
                                                                <?php echo date('M j, Y h:i A', strtotime($request['date_requested'])); ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?php if ($request['date_completed']): ?>
                                                                <small class="text-muted">
                                                                    <?php echo date('M j, Y h:i A', strtotime($request['date_completed'])); ?>
                                                                </small>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                    data-bs-toggle="modal" data-bs-target="#requestModal<?php echo $request['id']; ?>">
                                                                <i class="fas fa-eye"></i> View
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    
                                                    <!-- Modal for request details -->
                                                    <div class="modal fade" id="requestModal<?php echo $request['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">
                                                                        Lab Request Details
                                                                    </h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <h6>Patient Information</h6>
                                                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($request['patient_name']); ?></p>
                                                                            <p><strong>Card #:</strong> <?php echo htmlspecialchars($request['card_number']); ?></p>
                                                                            <p><strong>Sex:</strong> <?php echo $request['sex']; ?></p>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <h6>Request Information</h6>
                                                                            <p><strong>Status:</strong> 
                                                                                <span class="badge bg-<?php echo $color; ?>">
                                                                                    <?php echo ucfirst($request['status']); ?>
                                                                                </span>
                                                                            </p>
                                                                            <p><strong>Requested:</strong> <?php echo date('F d, Y h:i A', strtotime($request['date_requested'])); ?></p>
                                                                            <?php if ($request['date_completed']): ?>
                                                                                <p><strong>Completed:</strong> <?php echo date('F d, Y h:i A', strtotime($request['date_completed'])); ?></p>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <hr>
                                                                    <h6>Tests Requested</h6>
                                                                    <p><?php echo nl2br(htmlspecialchars($request['tests_requested'])); ?></p>
                                                                    
                                                                    <?php if ($request['result_text']): ?>
                                                                        <hr>
                                                                        <h6>Results</h6>
                                                                        <p><?php echo nl2br(htmlspecialchars($request['result_text'])); ?></p>
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
            </main>
        </div>
    </div>

    <!-- New Request Modal -->
    <div class="modal fade" id="newRequestModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus"></i> New Lab Request
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="patient_id" class="form-label">
                                    <i class="fas fa-user"></i> Patient *
                                </label>
                                <select class="form-select" id="patient_id" name="patient_id" required>
                                    <option value="">Select Patient</option>
                                    <?php foreach ($patients as $patient): ?>
                                        <option value="<?php echo $patient['id']; ?>">
                                            <?php echo htmlspecialchars($patient['full_name']); ?> 
                                            (<?php echo htmlspecialchars($patient['card_number']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tests_requested" class="form-label">
                                <i class="fas fa-flask"></i> Tests Requested *
                            </label>
                            <textarea class="form-control" id="tests_requested" name="tests_requested" rows="4" 
                                      placeholder="Specify the lab tests needed..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">
                                <i class="fas fa-sticky-note"></i> Additional Notes
                            </label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Any additional notes or instructions..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_request" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 