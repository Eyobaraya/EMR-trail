<?php
// patient_queue.php - Shows waiting patients in first-come-first-serve order

session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if user has permission to view queue (doctor or admin only)
if (!in_array($_SESSION['role'], ['doctor', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Set role variable for sidebar
$role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'guest';
$_SESSION['role'] = $role;

// Get waiting patients (inactive patients) ordered by registration time (first-come-first-serve)
$stmt = $pdo->prepare("
    SELECT p.*, 
           DATEDIFF(CURDATE(), p.date_registered) as days_waiting,
           CASE 
               WHEN DATEDIFF(CURDATE(), p.date_registered) = 0 THEN 'Today'
               WHEN DATEDIFF(CURDATE(), p.date_registered) = 1 THEN 'Yesterday'
               ELSE CONCAT(DATEDIFF(CURDATE(), p.date_registered), ' days ago')
           END as waiting_since
    FROM patients p 
    WHERE p.status = 'inactive'
    ORDER BY p.date_registered ASC
");
$stmt->execute();
$waiting_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's active patients (patients who had their first visit today)
$stmt = $pdo->prepare("
    SELECT p.*, 
           v.created_at as visit_time,
           TIME_FORMAT(v.created_at, '%H:%i') as visit_time_formatted
    FROM patients p 
    JOIN visits v ON p.id = v.patient_id
    WHERE p.status = 'active' 
    AND DATE(v.created_at) = CURDATE()
    AND v.id = (
        SELECT MIN(v2.id) 
        FROM visits v2 
        WHERE v2.patient_id = p.id
    )
    ORDER BY v.created_at ASC
");
$stmt->execute();
$today_visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM patients WHERE status = 'inactive'");
$total_waiting = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM patients WHERE status = 'active' AND DATE(first_visit_date) = CURDATE()");
$total_seen_today = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Queue - EMR Clinic System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../public/css/style.css" rel="stylesheet">
    <style>
        .queue-number {
            font-size: 1.2em;
            font-weight: bold;
            color: #dc3545;
        }
        .waiting-time {
            font-size: 0.9em;
            color: #6c757d;
        }
        .patient-card {
            transition: all 0.3s ease;
        }
        .patient-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .urgent {
            border-left: 4px solid #dc3545;
        }
        .normal {
            border-left: 4px solid #28a745;
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
                        <i class="fas fa-clock"></i> Patient Queue
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="visit_form.php" class="btn btn-success">
                            <i class="fas fa-stethoscope"></i> Start Visit
                        </a>
                        <a href="visits.php" class="btn btn-secondary ms-2">
                            <i class="fas fa-list"></i> All Visits
                        </a>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Waiting Patients</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_waiting; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Seen Today</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_seen_today; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Next Patient</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo !empty($waiting_patients) ? htmlspecialchars($waiting_patients[0]['full_name']) : 'None'; ?>
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

                <div class="row">
                    <!-- Waiting Patients -->
                    <div class="col-md-8">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-clock"></i> Waiting Patients (First-Come-First-Serve)
                                    <span class="badge bg-warning"><?php echo count($waiting_patients); ?></span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($waiting_patients)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                        <h5 class="text-success">No patients waiting!</h5>
                                        <p class="text-muted">All registered patients have been seen.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($waiting_patients as $index => $patient): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card patient-card <?php echo $patient['days_waiting'] > 2 ? 'urgent' : 'normal'; ?>">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div class="queue-number">#<?php echo $index + 1; ?></div>
                                                            <div class="waiting-time">
                                                                <i class="fas fa-calendar"></i> <?php echo $patient['waiting_since']; ?>
                                                            </div>
                                                        </div>
                                                        
                                                        <h6 class="card-title mb-1">
                                                            <?php echo htmlspecialchars($patient['full_name']); ?>
                                                        </h6>
                                                        
                                                        <p class="card-text mb-2">
                                                            <small class="text-muted">
                                                                <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($patient['card_number']); ?><br>
                                                                <i class="fas fa-<?php echo $patient['sex'] === 'Male' ? 'mars text-primary' : 'venus text-danger'; ?>"></i> <?php echo $patient['sex']; ?>
                                                                <?php if ($patient['phone']): ?>
                                                                    <br><i class="fas fa-phone"></i> <?php echo htmlspecialchars($patient['phone']); ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        </p>
                                                        
                                                        <div class="d-grid">
                                                            <a href="visit_form.php?patient_id=<?php echo $patient['id']; ?>" 
                                                               class="btn btn-success btn-sm">
                                                                <i class="fas fa-stethoscope"></i> Start Visit
                                                            </a>
                                                        </div>
                                                        
                                                        <?php if ($patient['days_waiting'] > 2): ?>
                                                            <div class="mt-2">
                                                                <span class="badge bg-danger">
                                                                    <i class="fas fa-exclamation-triangle"></i> Waiting <?php echo $patient['days_waiting']; ?> days
                                                                </span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Today's Visits -->
                    <div class="col-md-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-check-circle"></i> Seen Today
                                    <span class="badge bg-success"><?php echo count($today_visits); ?></span>
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($today_visits)): ?>
                                    <p class="text-muted text-center">No patients seen today yet.</p>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($today_visits as $visit): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($visit['full_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($visit['card_number']); ?>
                                                    </small>
                                                </div>
                                                <span class="badge bg-success rounded-pill">
                                                    <?php echo $visit['visit_time_formatted']; ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
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