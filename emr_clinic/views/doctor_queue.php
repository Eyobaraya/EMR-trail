<?php
// doctor_queue.php - Doctor's queue interface

session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if user has permission to view doctor queue (doctor or admin only)
if (!in_array($_SESSION['role'], ['doctor', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Set role variable for sidebar
$role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'guest';
$_SESSION['role'] = $role;
$user_id = $_SESSION['user_id'];

// Handle "Start Visit" action
if (isset($_POST['start_visit']) && isset($_POST['queue_id'])) {
    $queue_id = (int)$_POST['queue_id'];
    
    // Update queue status to in_progress
    $stmt = $pdo->prepare("
        UPDATE doctor_queue 
        SET status = 'in_progress', doctor_id = ?, started_at = NOW() 
        WHERE id = ? AND status = 'waiting'
    ");
    $stmt->execute([$user_id, $queue_id]);
    
    // Get patient ID and redirect to visit form
    $stmt = $pdo->prepare("SELECT patient_id FROM doctor_queue WHERE id = ?");
    $stmt->execute([$queue_id]);
    $patient_id = $stmt->fetch()['patient_id'];
    
    header("Location: visit_form.php?patient_id=$patient_id&queue_id=$queue_id");
    exit();
}

// Handle "Complete Visit" action
if (isset($_POST['complete_visit']) && isset($_POST['queue_id'])) {
    $queue_id = (int)$_POST['queue_id'];
    
    // Update queue status to completed
    $stmt = $pdo->prepare("
        UPDATE doctor_queue 
        SET status = 'completed', completed_at = NOW() 
        WHERE id = ? AND status = 'in_progress'
    ");
    $stmt->execute([$queue_id]);
    
    header('Location: doctor_queue.php?success=1');
    exit();
}

// Get current queue
$stmt = $pdo->prepare("
    SELECT 
        dq.id as queue_id,
        dq.patient_id,
        dq.sent_at,
        dq.status,
        dq.priority,
        dq.notes,
        dq.started_at,
        p.full_name,
        p.card_number,
        p.sex,
        p.phone,
        p.status as patient_status,
        u.full_name as sent_by_name,
        TIMESTAMPDIFF(MINUTE, dq.sent_at, NOW()) as minutes_waiting
    FROM doctor_queue dq
    JOIN patients p ON dq.patient_id = p.id
    JOIN users u ON dq.sent_by = u.id
    WHERE dq.status IN ('waiting', 'in_progress')
    ORDER BY 
        CASE dq.priority 
            WHEN 'emergency' THEN 1 
            WHEN 'urgent' THEN 2 
            ELSE 3 
        END,
        dq.sent_at ASC
");
$stmt->execute();
$queue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM doctor_queue WHERE status = 'waiting'");
$waiting_count = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM doctor_queue WHERE status = 'in_progress'");
$in_progress_count = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM doctor_queue WHERE status = 'completed' AND DATE(completed_at) = CURDATE()");
$completed_today = $stmt->fetch()['total'];

// Get next patient (first in queue)
$next_patient = !empty($queue) ? $queue[0] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor's Queue - EMR Clinic System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../public/css/style.css" rel="stylesheet">
    <style>
        .next-patient-card {
            border: 3px solid #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        }
        .waiting-time {
            font-size: 0.9em;
            color: #6c757d;
        }
        .priority-emergency {
            border-left: 4px solid #dc3545;
            background-color: #f8d7da;
        }
        .priority-urgent {
            border-left: 4px solid #ffc107;
            background-color: #fff3cd;
        }
        .priority-normal {
            border-left: 4px solid #28a745;
        }
        .queue-number {
            font-size: 1.2em;
            font-weight: bold;
            color: #007bff;
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
                        <i class="fas fa-user-md"></i> Doctor's Queue
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="patients.php" class="btn btn-secondary">
                            <i class="fas fa-users"></i> All Patients
                        </a>
                        <a href="visits.php" class="btn btn-info ms-2">
                            <i class="fas fa-history"></i> Visit History
                        </a>
                    </div>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> Visit completed successfully! Next patient is ready.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Waiting</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $waiting_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">In Progress</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $in_progress_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-stethoscope fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
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
                    
                    <div class="col-md-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Next Patient</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $next_patient ? htmlspecialchars($next_patient['full_name']) : 'None'; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Next Patient Card -->
                <?php if ($next_patient): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card next-patient-card shadow">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-arrow-right"></i> Next Patient
                                        <span class="badge bg-light text-dark ms-2">
                                            <?php echo ucfirst($next_patient['priority']); ?> Priority
                                        </span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h4><?php echo htmlspecialchars($next_patient['full_name']); ?></h4>
                                            <p class="mb-2">
                                                <strong>Card:</strong> <?php echo htmlspecialchars($next_patient['card_number']); ?> | 
                                                <strong>Sex:</strong> <?php echo $next_patient['sex']; ?>
                                                <?php if ($next_patient['phone']): ?>
                                                    | <strong>Phone:</strong> <?php echo htmlspecialchars($next_patient['phone']); ?>
                                                <?php endif; ?>
                                            </p>
                                            <p class="mb-2">
                                                <strong>Sent by:</strong> <?php echo htmlspecialchars($next_patient['sent_by_name']); ?> | 
                                                <strong>Waiting:</strong> <?php echo $next_patient['minutes_waiting']; ?> minutes
                                            </p>
                                            <?php if ($next_patient['notes']): ?>
                                                <div class="alert alert-info mb-0">
                                                    <strong>Notes:</strong> <?php echo htmlspecialchars($next_patient['notes']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-center justify-content-end">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="queue_id" value="<?php echo $next_patient['queue_id']; ?>">
                                                <button type="submit" name="start_visit" class="btn btn-success btn-lg">
                                                    <i class="fas fa-stethoscope"></i> Start Visit
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Queue List -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-list"></i> Patient Queue
                                    <span class="badge bg-secondary"><?php echo count($queue); ?> patients</span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($queue)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                        <h5 class="text-success">No patients in queue!</h5>
                                        <p class="text-muted">All patients have been seen or are currently being treated.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Patient</th>
                                                    <th>Priority</th>
                                                    <th>Sent By</th>
                                                    <th>Waiting Time</th>
                                                    <th>Notes</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($queue as $index => $patient): ?>
                                                    <tr class="priority-<?php echo $patient['priority']; ?>">
                                                        <td>
                                                            <div class="queue-number"><?php echo $index + 1; ?></div>
                                                        </td>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($patient['full_name']); ?></strong><br>
                                                            <small class="text-muted">
                                                                <?php echo htmlspecialchars($patient['card_number']); ?> | 
                                                                <?php echo $patient['sex']; ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $patient['priority'] === 'emergency' ? 'danger' : 
                                                                    ($patient['priority'] === 'urgent' ? 'warning' : 'success'); 
                                                            ?>">
                                                                <?php echo ucfirst($patient['priority']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php echo htmlspecialchars($patient['sent_by_name']); ?>
                                                        </td>
                                                        <td>
                                                            <div class="waiting-time">
                                                                <?php echo $patient['minutes_waiting']; ?> minutes
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?php if ($patient['notes']): ?>
                                                                <small><?php echo htmlspecialchars(substr($patient['notes'], 0, 50)); ?>...</small>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($patient['status'] === 'waiting'): ?>
                                                                <span class="badge bg-warning">Waiting</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-info">In Progress</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($patient['status'] === 'waiting'): ?>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="queue_id" value="<?php echo $patient['queue_id']; ?>">
                                                                    <button type="submit" name="start_visit" class="btn btn-sm btn-success">
                                                                        <i class="fas fa-stethoscope"></i> Start
                                                                    </button>
                                                                </form>
                                                            <?php else: ?>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="queue_id" value="<?php echo $patient['queue_id']; ?>">
                                                                    <button type="submit" name="complete_visit" class="btn btn-sm btn-primary">
                                                                        <i class="fas fa-check"></i> Complete
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
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
    <script src="../public/js/app.js"></script>
</body>
</html> 