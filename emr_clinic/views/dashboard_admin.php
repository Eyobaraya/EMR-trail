<?php
// Admin Dashboard
require_once '../config/db.php';

// Get statistics
$stats = [];

// Total patients
$stmt = $pdo->query("SELECT COUNT(*) as total FROM patients");
$stats['patients'] = $stmt->fetch()['total'];

// Active patients
$stmt = $pdo->query("SELECT COUNT(*) as total FROM patients WHERE status = 'active'");
$stats['active_patients'] = $stmt->fetch()['total'];

// Today's visits
$stmt = $pdo->query("SELECT COUNT(*) as total FROM visits WHERE DATE(created_at) = CURDATE()");
$stats['today_visits'] = $stmt->fetch()['total'];

// Pending lab requests
$stmt = $pdo->query("SELECT COUNT(*) as total FROM lab_requests WHERE status = 'pending'");
$stats['pending_lab'] = $stmt->fetch()['total'];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$stats['users'] = $stmt->fetch()['total'];

// Recent visits
$stmt = $pdo->query("
    SELECT v.*, p.full_name as patient_name, u.full_name as doctor_name 
    FROM visits v 
    JOIN patients p ON v.patient_id = p.id 
    JOIN users u ON v.user_id = u.id 
    ORDER BY v.created_at DESC 
    LIMIT 5
");
$recent_visits = $stmt->fetchAll();
?>

<div class="row">
    <!-- Statistics Cards -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Patients
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['patients']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users icon text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Active Patients
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['active_patients']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-check icon text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Today's Visits
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['today_visits']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-stethoscope icon text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Pending Lab Tests
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending_lab']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-flask icon text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <a href="add_patient.php" class="btn btn-primary btn-block">
                            <i class="fas fa-user-plus"></i> Add New Patient
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="visit_form.php" class="btn btn-success btn-block">
                            <i class="fas fa-notes-medical"></i> New Visit
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="users.php" class="btn btn-info btn-block">
                            <i class="fas fa-user-cog"></i> Manage Users
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="reports.php" class="btn btn-warning btn-block">
                            <i class="fas fa-chart-bar"></i> View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Recent Visits</h6>
            </div>
            <div class="card-body">
                <?php if (empty($recent_visits)): ?>
                    <p class="text-muted">No recent visits.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_visits as $visit): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($visit['patient_name']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($visit['doctor_name']); ?> • 
                                        <?php echo ucfirst($visit['visit_type']); ?>
                                    </small>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('M j, Y', strtotime($visit['created_at'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- System Information -->
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">System Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Total Users:</strong> <?php echo $stats['users']; ?>
                    </div>
                    <div class="col-md-3">
                        <strong>System Version:</strong> 1.0.0
                    </div>
                    <div class="col-md-3">
                        <strong>Last Backup:</strong> <?php echo date('M j, Y H:i'); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Database:</strong> MySQL
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 