<?php
// lab.php - Lab department interface for lab technicians

session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if user has permission to view lab referrals (lab tech or admin only)
if (!in_array($_SESSION['role'], ['lab', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Set role variable for sidebar
$role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'guest';
$_SESSION['role'] = $role;
$user_id = $_SESSION['user_id'];

// Handle status updates
if (isset($_POST['update_status']) && isset($_POST['referral_id'])) {
    $referral_id = (int)$_POST['referral_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $pdo->prepare("
        UPDATE department_referrals 
        SET status = ?, completed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE NULL END
        WHERE id = ? AND to_department = 'lab'
    ");
    
    if ($stmt->execute([$new_status, $new_status, $referral_id])) {
        $message = "Status updated successfully!";
    } else {
        $error = "Error updating status. Please try again.";
    }
}

// Get lab referrals
$stmt = $pdo->prepare("
    SELECT 
        dr.id as referral_id,
        dr.status,
        dr.created_at,
        dr.completed_at,
        dr.notes,
        p.full_name as patient_name,
        p.card_number,
        p.sex,
        p.phone,
        v.visit_type,
        v.symptoms,
        v.diagnosis,
        u.full_name as doctor_name
    FROM department_referrals dr
    JOIN patients p ON dr.patient_id = p.id
    JOIN visits v ON dr.visit_id = v.id
    JOIN users u ON dr.from_doctor_id = u.id
    WHERE dr.to_department = 'lab'
    ORDER BY 
        CASE dr.status 
            WHEN 'pending' THEN 1 
            WHEN 'in_progress' THEN 2 
            ELSE 3 
        END,
        dr.created_at ASC
");
$stmt->execute();
$referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM department_referrals WHERE to_department = 'lab' AND status = 'pending'");
$pending_count = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM department_referrals WHERE to_department = 'lab' AND status = 'in_progress'");
$in_progress_count = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM department_referrals WHERE to_department = 'lab' AND status = 'completed' AND DATE(completed_at) = CURDATE()");
$completed_today = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Department - EMR Clinic System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../public/css/style.css" rel="stylesheet">
    <style>
        .status-pending {
            border-left: 4px solid #ffc107;
            background-color: #fff3cd;
        }
        .status-in-progress {
            border-left: 4px solid #17a2b8;
            background-color: #d1ecf1;
        }
        .status-completed {
            border-left: 4px solid #28a745;
            background-color: #d4edda;
        }
    </style>
</head>
<body>
    <?php include '../templates/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../templates/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-flask"></i> Lab Department
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="visits.php" class="btn btn-secondary">
                            <i class="fas fa-stethoscope"></i> All Visits
                        </a>
                    </div>
                </div>

                <?php if (isset($message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">In Progress</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $in_progress_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-microscope fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
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

                <!-- Lab Referrals -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-list"></i> Lab Referrals
                                    <span class="badge bg-secondary"><?php echo count($referrals); ?> referrals</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($referrals)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-flask fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No Lab Referrals</h5>
                                        <p class="text-muted">No patients have been referred to the lab department yet.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Patient</th>
                                                    <th>Doctor</th>
                                                    <th>Visit Info</th>
                                                    <th>Status</th>
                                                    <th>Received</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($referrals as $referral): ?>
                                                    <tr class="status-<?php echo $referral['status']; ?>">
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($referral['patient_name']); ?></strong><br>
                                                            <small class="text-muted">
                                                                Card: <?php echo htmlspecialchars($referral['card_number']); ?> | 
                                                                <?php echo $referral['sex']; ?>
                                                                <?php if ($referral['phone']): ?>
                                                                    | <?php echo htmlspecialchars($referral['phone']); ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($referral['doctor_name']); ?></strong><br>
                                                            <small class="text-muted">Visit #<?php echo $referral['referral_id']; ?></small>
                                                        </td>
                                                        <td>
                                                            <strong><?php echo ucfirst($referral['visit_type']); ?></strong><br>
                                                            <small class="text-muted">
                                                                <?php if ($referral['symptoms']): ?>
                                                                    Symptoms: <?php echo htmlspecialchars(substr($referral['symptoms'], 0, 50)); ?>...
                                                                <?php endif; ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $status_colors = [
                                                                'pending' => 'warning',
                                                                'in_progress' => 'info',
                                                                'completed' => 'success'
                                                            ];
                                                            $color = $status_colors[$referral['status']] ?? 'secondary';
                                                            ?>
                                                            <span class="badge bg-<?php echo $color; ?>">
                                                                <?php echo ucfirst(str_replace('_', ' ', $referral['status'])); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">
                                                                <?php echo date('M j, Y h:i A', strtotime($referral['created_at'])); ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                        data-bs-toggle="modal" data-bs-target="#referralModal<?php echo $referral['referral_id']; ?>">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                                
                                                                <?php if ($referral['status'] === 'pending'): ?>
                                                                    <form method="POST" style="display: inline;">
                                                                        <input type="hidden" name="referral_id" value="<?php echo $referral['referral_id']; ?>">
                                                                        <input type="hidden" name="new_status" value="in_progress">
                                                                        <button type="submit" name="update_status" class="btn btn-sm btn-info">
                                                                            <i class="fas fa-play"></i> Start
                                                                        </button>
                                                                    </form>
                                                                <?php elseif ($referral['status'] === 'in_progress'): ?>
                                                                    <form method="POST" style="display: inline;">
                                                                        <input type="hidden" name="referral_id" value="<?php echo $referral['referral_id']; ?>">
                                                                        <input type="hidden" name="new_status" value="completed">
                                                                        <button type="submit" name="update_status" class="btn btn-sm btn-success">
                                                                            <i class="fas fa-check"></i> Complete
                                                                        </button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    
                                                    <!-- Modal for referral details -->
                                                    <div class="modal fade" id="referralModal<?php echo $referral['referral_id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">
                                                                        Lab Referral Details
                                                                    </h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <h6>Patient Information</h6>
                                                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($referral['patient_name']); ?></p>
                                                                            <p><strong>Card #:</strong> <?php echo htmlspecialchars($referral['card_number']); ?></p>
                                                                            <p><strong>Sex:</strong> <?php echo $referral['sex']; ?></p>
                                                                            <?php if ($referral['phone']): ?>
                                                                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($referral['phone']); ?></p>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <h6>Visit Information</h6>
                                                                            <p><strong>Type:</strong> <?php echo ucfirst($referral['visit_type']); ?></p>
                                                                            <p><strong>Doctor:</strong> <?php echo htmlspecialchars($referral['doctor_name']); ?></p>
                                                                            <p><strong>Status:</strong> 
                                                                                <span class="badge bg-<?php echo $color; ?>">
                                                                                    <?php echo ucfirst(str_replace('_', ' ', $referral['status'])); ?>
                                                                                </span>
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <?php if ($referral['symptoms']): ?>
                                                                        <hr>
                                                                        <h6>Symptoms</h6>
                                                                        <p><?php echo nl2br(htmlspecialchars($referral['symptoms'])); ?></p>
                                                                    <?php endif; ?>
                                                                    
                                                                    <?php if ($referral['diagnosis']): ?>
                                                                        <hr>
                                                                        <h6>Diagnosis</h6>
                                                                        <p><?php echo nl2br(htmlspecialchars($referral['diagnosis'])); ?></p>
                                                                    <?php endif; ?>
                                                                    
                                                                    <?php if ($referral['notes']): ?>
                                                                        <hr>
                                                                        <h6>Referral Notes</h6>
                                                                        <p><?php echo nl2br(htmlspecialchars($referral['notes'])); ?></p>
                                                                    <?php endif; ?>
                                                                    
                                                                    <hr>
                                                                    <p><strong>Received:</strong> <?php echo date('F d, Y h:i A', strtotime($referral['created_at'])); ?></p>
                                                                    
                                                                    <?php if ($referral['completed_at']): ?>
                                                                        <p><strong>Completed:</strong> <?php echo date('F d, Y h:i A', strtotime($referral['completed_at'])); ?></p>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>