<?php
// ultrasound.php - Ultrasound request management for doctors

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

// Handle form submission for new ultrasound request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_request'])) {
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $ultrasound_type = trim($_POST['ultrasound_type'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if ($patient_id <= 0) {
        $error = 'Please select a patient.';
    } elseif (empty($ultrasound_type)) {
        $error = 'Please specify the ultrasound type.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO ultrasound_reports (patient_id, requested_by, report_text, technician_id)
            VALUES (?, ?, ?, NULL)
        ");
        
        if ($stmt->execute([$patient_id, $user_id, $notes])) {
            $message = 'Ultrasound request created successfully!';
        } else {
            $error = 'Error creating ultrasound request. Please try again.';
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

// Get ultrasound requests created by this doctor
$stmt = $pdo->prepare("
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
    ORDER BY ur.created_at DESC
");
$stmt->execute([$user_id]);
$ultrasound_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM ultrasound_reports WHERE requested_by = ? AND technician_id IS NULL");
$stmt->execute([$user_id]);
$pending_count = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM ultrasound_reports WHERE requested_by = ? AND technician_id IS NOT NULL AND DATE(created_at) = CURDATE()");
$stmt->execute([$user_id]);
$completed_today = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ultrasound - EMR Clinic System</title>
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
                        <i class="fas fa-baby"></i> Ultrasound
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                            <i class="fas fa-plus"></i> New Ultrasound Request
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

                <!-- Ultrasound Requests List -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-list"></i> My Ultrasound Requests
                                    <span class="badge bg-secondary"><?php echo count($ultrasound_requests); ?> requests</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($ultrasound_requests)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-baby fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No Ultrasound Requests</h5>
                                        <p class="text-muted">You haven't created any ultrasound requests yet.</p>
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
                                                    <th>Request Notes</th>
                                                    <th>Status</th>
                                                    <th>Requested</th>
                                                    <th>Technician</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($ultrasound_requests as $request): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($request['patient_name']); ?></strong><br>
                                                            <small class="text-muted">
                                                                Card: <?php echo htmlspecialchars($request['card_number']); ?> | 
                                                                <?php echo $request['sex']; ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?php echo htmlspecialchars(substr($request['report_text'], 0, 50)); ?>
                                                            <?php if (strlen($request['report_text']) > 50): ?>...<?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($request['technician_id']): ?>
                                                                <span class="badge bg-success">Completed</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning">Pending</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">
                                                                <?php echo date('M j, Y h:i A', strtotime($request['created_at'])); ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?php if ($request['technician_name']): ?>
                                                                <?php echo htmlspecialchars($request['technician_name']); ?>
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
                                                                        Ultrasound Request Details
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
                                                                                <?php if ($request['technician_id']): ?>
                                                                                    <span class="badge bg-success">Completed</span>
                                                                                <?php else: ?>
                                                                                    <span class="badge bg-warning">Pending</span>
                                                                                <?php endif; ?>
                                                                            </p>
                                                                            <p><strong>Requested:</strong> <?php echo date('F d, Y h:i A', strtotime($request['created_at'])); ?></p>
                                                                            <?php if ($request['technician_name']): ?>
                                                                                <p><strong>Technician:</strong> <?php echo htmlspecialchars($request['technician_name']); ?></p>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <hr>
                                                                    <h6>Request Notes</h6>
                                                                    <p><?php echo nl2br(htmlspecialchars($request['report_text'])); ?></p>
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
                        <i class="fas fa-plus"></i> New Ultrasound Request
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
                            
                            <div class="col-md-6 mb-3">
                                <label for="ultrasound_type" class="form-label">
                                    <i class="fas fa-baby"></i> Ultrasound Type *
                                </label>
                                <select class="form-select" id="ultrasound_type" name="ultrasound_type" required>
                                    <option value="">Select Type</option>
                                    <option value="Pregnancy Scan">Pregnancy Scan</option>
                                    <option value="Abdominal Scan">Abdominal Scan</option>
                                    <option value="Pelvic Scan">Pelvic Scan</option>
                                    <option value="Cardiac Scan">Cardiac Scan</option>
                                    <option value="Thyroid Scan">Thyroid Scan</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">
                                <i class="fas fa-sticky-note"></i> Request Notes
                            </label>
                            <textarea class="form-control" id="notes" name="notes" rows="4" 
                                      placeholder="Describe the ultrasound needed, any specific areas to focus on, or additional instructions..."></textarea>
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