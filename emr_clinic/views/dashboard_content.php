<?php
// dashboard_content.php - Dashboard content only (no header/sidebar)

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the correct path based on where this file is being called from
$base_path = dirname(__DIR__);
require_once $base_path . '/config/db.php';
require_once $base_path . '/includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit();
}

$role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'guest';
$user_id = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;

// Set session variables for sidebar compatibility
$_SESSION['role'] = $role;
$_SESSION['user_id'] = $user_id;

// Debug: Let's see what role is being detected
// echo "<!-- Debug: Role detected as: " . $role . " -->";
// echo "<!-- Debug: Session user role: " . ($_SESSION['user']['role'] ?? 'not set') . " -->";
// echo "<!-- Debug: Session role: " . ($_SESSION['role'] ?? 'not set') . " -->";

// Get basic statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM patients");
$total_patients = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM patients WHERE status = 'active'");
$active_patients = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM visits WHERE DATE(created_at) = CURDATE()");
$today_visits = $stmt->fetch()['total'];
?>

<!-- Welcome Message -->
<div class="alert alert-info">
    <h5><i class="fas fa-user-circle"></i> Welcome, <?php echo htmlspecialchars($_SESSION['user']['full_name']); ?>!</h5>
    <p class="mb-0">You are logged in as a <strong><?php echo ucfirst($role); ?></strong>. Here's your dashboard overview.</p>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Patients</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_patients; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Patients</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $active_patients; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Today's Visits</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $today_visits; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-stethoscope fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Your Role</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo ucfirst($role); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-cog fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-bolt"></i> Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php 
                    // Debug: Show current role
                    // echo "<!-- Current role: " . $role . " -->";
                    
                    if ($role === 'receptionist' || $role === 'admin'): 
                    ?>
                        <div class="col-md-3 mb-2">
                            <a href="views/add_patient.php" class="btn btn-primary w-100">
                                <i class="fas fa-user-plus"></i> Add New Patient
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($role === 'doctor' || $role === 'admin'): ?>
                        <div class="col-md-3 mb-2">
                            <a href="views/visit_form.php" class="btn btn-success w-100">
                                <i class="fas fa-notes-medical"></i> Create Visit
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="col-md-3 mb-2">
                        <a href="views/patients.php" class="btn btn-info w-100">
                            <i class="fas fa-users"></i> View Patients
                        </a>
                    </div>
                    
                    <?php if ($role === 'doctor' || $role === 'admin'): ?>
                        <div class="col-md-3 mb-2">
                            <a href="views/visits.php" class="btn btn-warning w-100">
                                <i class="fas fa-stethoscope"></i> View Visits
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Status -->
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle"></i> System Status
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Current Phase: Phase 7 - Complete EMR System</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> User Authentication</li>
                            <li><i class="fas fa-check text-success"></i> Patient Management</li>
                            <li><i class="fas fa-check text-success"></i> Visit Management</li>
                            <li><i class="fas fa-check text-success"></i> Lab Module (Phase 5)</li>
                            <li><i class="fas fa-check text-success"></i> Ultrasound Module (Phase 6)</li>
                            <li><i class="fas fa-check text-success"></i> Emergency Module (Phase 7)</li>
                            <li><i class="fas fa-check text-success"></i> Department Referrals</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Your Permissions:</h6>
                        <ul class="list-unstyled">
                            <?php if ($role === 'receptionist' || $role === 'admin'): ?>
                                <li><i class="fas fa-check text-success"></i> Add Patients</li>
                            <?php endif; ?>
                            <?php if ($role === 'doctor' || $role === 'admin'): ?>
                                <li><i class="fas fa-check text-success"></i> Create Visits</li>
                                <li><i class="fas fa-check text-success"></i> View Medical History</li>
                            <?php endif; ?>
                            <li><i class="fas fa-check text-success"></i> View Patients</li>
                            <li><i class="fas fa-check text-success"></i> Access Dashboard</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 