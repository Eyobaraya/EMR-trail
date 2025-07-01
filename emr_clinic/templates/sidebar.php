<?php
// Get role from session if not already set
if (!isset($role)) {
    if (isset($_SESSION['role'])) {
        $role = $_SESSION['role'];
    } elseif (isset($_SESSION['user']['role'])) {
        $role = $_SESSION['user']['role'];
    } else {
        $role = 'guest';
    }
}

// Determine the base path for navigation
$current_script = $_SERVER['PHP_SELF'];
$is_in_views = strpos($current_script, '/views/') !== false;
$base_path = $is_in_views ? '../' : '';
?>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>index.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <!-- Patient Management -->
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'patients.php') !== false ? 'active' : ''; ?>" href="<?php echo $base_path; ?>views/patients.php">
                    <i class="fas fa-users"></i> Patients
                </a>
            </li>
            
            <?php if (in_array($role, ['receptionist', 'admin'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'add_patient.php') !== false ? 'active' : ''; ?>" href="<?php echo $base_path; ?>views/add_patient.php">
                    <i class="fas fa-user-plus"></i> Add Patient
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Visit Management -->
            <?php if (in_array($role, ['doctor', 'admin'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'visits.php') !== false ? 'active' : ''; ?>" href="<?php echo $base_path; ?>views/visits.php">
                    <i class="fas fa-stethoscope"></i> Visits
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'visit_form.php') !== false ? 'active' : ''; ?>" href="<?php echo $base_path; ?>views/visit_form.php">
                    <i class="fas fa-notes-medical"></i> New Visit
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array($role, ['doctor', 'admin'])): ?>
            <!-- Lab Module -->
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'lab_request.php') !== false ? 'active' : ''; ?>" href="<?php echo $base_path; ?>views/lab_request.php">
                    <i class="fas fa-flask"></i> Lab Requests
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'results_view.php') !== false ? 'active' : ''; ?>" href="<?php echo $base_path; ?>views/results_view.php">
                    <i class="fas fa-file-medical"></i> Lab Results
                </a>
            </li>
            
            <!-- Ultrasound Module -->
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'ultrasound.php') !== false ? 'active' : ''; ?>" href="<?php echo $base_path; ?>views/ultrasound.php">
                    <i class="fas fa-baby"></i> Ultrasound
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array($role, ['lab', 'admin'])): ?>
            <!-- Lab Technician -->
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'lab.php') !== false ? 'active' : ''; ?>" href="<?php echo $base_path; ?>views/lab.php">
                    <i class="fas fa-microscope"></i> Lab Tests
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array($role, ['ultrasound', 'admin'])): ?>
            <!-- Ultrasound Technician -->
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'ultrasound_tech.php') !== false ? 'active' : ''; ?>" href="<?php echo $base_path; ?>views/ultrasound_tech.php">
                    <i class="fas fa-baby"></i> Ultrasound Tests
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (in_array($role, ['emergency', 'admin'])): ?>
            <!-- Emergency Module -->
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'emergency.php') !== false ? 'active' : ''; ?>" href="<?php echo $base_path; ?>views/emergency.php">
                    <i class="fas fa-ambulance"></i> Emergency
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($role === 'admin'): ?>
            <!-- Admin Only -->
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'users.php') !== false ? 'active' : ''; ?>" href="<?php echo $base_path; ?>views/users.php">
                    <i class="fas fa-user-cog"></i> User Management
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'reports.php') !== false ? 'active' : ''; ?>" href="<?php echo $base_path; ?>views/reports.php">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($role === 'doctor' || $role === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'doctor_queue.php') !== false ? 'active' : ''; ?>" href="<?php echo $base_path; ?>views/doctor_queue.php">
                    <i class="fas fa-user-md"></i> Doctor's Queue
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav> 