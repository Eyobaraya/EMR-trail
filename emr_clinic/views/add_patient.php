<?php
// add_patient.php

session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if user has permission to add patients (receptionist or admin only)
if (!in_array($_SESSION['role'], ['receptionist', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Set role variable for sidebar
$role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'guest';
$_SESSION['role'] = $role;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $sex = $_POST['sex'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $card_number = trim($_POST['card_number'] ?? '');
    
    // Validation
    if (empty($full_name)) {
        $error = 'Full name is required.';
    } elseif (empty($sex)) {
        $error = 'Sex is required.';
    } elseif (empty($card_number)) {
        $error = 'Card number is required.';
    } else {
        // Check if card number already exists
        $stmt = $pdo->prepare("SELECT id FROM patients WHERE card_number = ?");
        $stmt->execute([$card_number]);
        if ($stmt->fetch()) {
            $error = 'Card number already exists.';
        } else {
            // Insert new patient
            $stmt = $pdo->prepare("
                INSERT INTO patients (full_name, sex, phone, card_number, date_registered, status) 
                VALUES (?, ?, ?, ?, CURDATE(), 'inactive')
            ");
            
            if ($stmt->execute([$full_name, $sex, $phone, $card_number])) {
                $message = 'Patient registered successfully! Patient will be activated on first visit.';
                // Clear form data
                $_POST = [];
            } else {
                $error = 'Error registering patient. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Patient - EMR Clinic System</title>
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
                        <i class="fas fa-user-plus"></i> Add New Patient
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="patients.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Patients
                        </a>
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
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-user-edit"></i> Patient Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="needs-validation" novalidate>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="full_name" class="form-label">
                                                <i class="fas fa-user"></i> Full Name *
                                            </label>
                                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                                            <div class="invalid-feedback">
                                                Please provide a full name.
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="sex" class="form-label">
                                                <i class="fas fa-venus-mars"></i> Sex *
                                            </label>
                                            <select class="form-select" id="sex" name="sex" required>
                                                <option value="">Select Sex</option>
                                                <option value="Male" <?php echo ($_POST['sex'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                                <option value="Female" <?php echo ($_POST['sex'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                            </select>
                                            <div class="invalid-feedback">
                                                Please select sex.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">
                                                <i class="fas fa-phone"></i> Phone Number
                                            </label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="card_number" class="form-label">
                                                <i class="fas fa-id-card"></i> Card Number *
                                            </label>
                                            <input type="text" class="form-control" id="card_number" name="card_number" 
                                                   value="<?php echo htmlspecialchars($_POST['card_number'] ?? ''); ?>" required>
                                            <div class="invalid-feedback">
                                                Please provide a card number.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="reset" class="btn btn-secondary me-md-2">
                                            <i class="fas fa-undo"></i> Reset
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Register Patient
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card shadow">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-info-circle"></i> Information
                                </h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i> 
                                        Patient will be registered as inactive
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i> 
                                        Status will change to active on first visit
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i> 
                                        Card number must be unique
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i> 
                                        Phone number is optional
                                    </li>
                                </ul>
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