<?php
// visit_form.php

session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

// Check if user has permission to create visits (doctor or admin only)
if (!in_array($_SESSION['role'], ['doctor', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Set role variable for sidebar
$role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'guest';
$_SESSION['role'] = $role;

$message = '';
$error = '';
$visit = null;
$is_edit = false;
$pre_selected_patient = null;
$pre_selected_visit_type = 'general'; // Default visit type

// Get visit data for editing
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $visit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("
        SELECT v.*, p.full_name as patient_name, p.card_number
        FROM visits v
        JOIN patients p ON v.patient_id = p.id
        WHERE v.id = ?
    ");
    $stmt->execute([$visit_id]);
    $visit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($visit) {
        $is_edit = true;
    } else {
        $error = 'Visit not found.';
    }
}

// Get pre-selected patient from queue if coming from doctor's queue
if (isset($_GET['patient_id']) && is_numeric($_GET['patient_id'])) {
    $patient_id = (int)$_GET['patient_id'];
    $stmt = $pdo->prepare("
        SELECT p.*, dq.notes as queue_notes, dq.priority
        FROM patients p
        LEFT JOIN doctor_queue dq ON p.id = dq.patient_id AND dq.status = 'waiting'
        WHERE p.id = ?
    ");
    $stmt->execute([$patient_id]);
    $pre_selected_patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pre_selected_patient) {
        // Don't auto-select visit type - let doctor choose based on patient needs
        $pre_selected_visit_type = '';
    }
}

// Handle "Send to Department" action
if (isset($_POST['send_to_department']) && isset($_POST['referral_department']) && isset($_POST['visit_id'])) {
    $referral_department = $_POST['referral_department'];
    $visit_id = (int)$_POST['visit_id'];
    $user_id = $_SESSION['user_id'];
    
    // Get patient ID from visit
    $stmt = $pdo->prepare("SELECT patient_id FROM visits WHERE id = ?");
    $stmt->execute([$visit_id]);
    $visit_data = $stmt->fetch();
    
    if ($visit_data) {
        // Create department referral
        $stmt = $pdo->prepare("
            INSERT INTO department_referrals (patient_id, visit_id, from_doctor_id, to_department, notes)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $notes = "Referred from visit #$visit_id";
        if ($stmt->execute([$visit_data['patient_id'], $visit_id, $user_id, $referral_department, $notes])) {
            $message = "Patient sent to $referral_department department successfully!";
        } else {
            $error = "Error sending patient to department. Please try again.";
        }
    } else {
        $error = "Visit not found.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $visit_type = $_POST['visit_type'] ?? '';
    $symptoms = trim($_POST['symptoms'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $prescription = trim($_POST['prescription'] ?? '');
    
    // Validation
    if ($patient_id <= 0) {
        $error = 'Please select a patient.';
    } elseif (empty($visit_type)) {
        $error = 'Please select visit type.';
    } else {
        $user_id = $_SESSION['user_id'];
        
        if ($is_edit && $visit) {
            // Update existing visit
            $stmt = $pdo->prepare("
                UPDATE visits 
                SET patient_id = ?, visit_type = ?, symptoms = ?, diagnosis = ?, prescription = ?
                WHERE id = ?
            ");
            
            if ($stmt->execute([$patient_id, $visit_type, $symptoms, $diagnosis, $prescription, $visit['id']])) {
                $message = 'Visit updated successfully!';
                // Refresh visit data
                $stmt = $pdo->prepare("
                    SELECT v.*, p.full_name as patient_name, p.card_number
                    FROM visits v
                    JOIN patients p ON v.patient_id = p.id
                    WHERE v.id = ?
                ");
                $stmt->execute([$visit['id']]);
                $visit = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = 'Error updating visit. Please try again.';
            }
        } else {
            // Create new visit
            $stmt = $pdo->prepare("
                INSERT INTO visits (patient_id, user_id, visit_type, symptoms, diagnosis, prescription)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$patient_id, $user_id, $visit_type, $symptoms, $diagnosis, $prescription])) {
                // Activate patient if this is their first visit
                $stmt = $pdo->prepare("
                    UPDATE patients 
                    SET status = 'active', first_visit_date = CURDATE()
                    WHERE id = ? AND status = 'inactive'
                ");
                $stmt->execute([$patient_id]);
                
                // Complete queue entry if this visit was started from queue
                if (isset($_GET['queue_id']) || isset($_POST['queue_id'])) {
                    $queue_id = (int)($_GET['queue_id'] ?? $_POST['queue_id']);
                    $stmt = $pdo->prepare("
                        UPDATE doctor_queue 
                        SET status = 'completed', completed_at = NOW() 
                        WHERE id = ? AND status = 'in_progress'
                    ");
                    $stmt->execute([$queue_id]);
                }
                
                $visit_id = $pdo->lastInsertId();
                $message = 'Visit created successfully!';
                
                // Redirect to doctor's queue if this was from queue
                if (isset($_GET['queue_id']) || isset($_POST['queue_id'])) {
                    header('Location: doctor_queue.php?success=1');
                    exit();
                }
                
                // Clear form data
                $_POST = [];
            } else {
                $error = 'Error creating visit. Please try again.';
            }
        }
    }
}

// Get all patients for dropdown - ordered by first-come-first-serve (oldest registrations first)
$stmt = $pdo->prepare("
    SELECT id, full_name, card_number, status, date_registered, 
           CASE 
               WHEN status = 'inactive' THEN 'Waiting for first visit'
               ELSE 'Active patient'
           END as queue_status
    FROM patients 
    ORDER BY 
        CASE WHEN status = 'inactive' THEN 0 ELSE 1 END, -- Inactive patients first (waiting)
        date_registered ASC -- Then by registration time (oldest first)
");
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Visit' : 'New Visit'; ?> - EMR Clinic System</title>
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
                        <i class="fas fa-stethoscope"></i> 
                        <?php echo $is_edit ? 'Edit Visit' : 'New Visit'; ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="visits.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Visits
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
                                    <i class="fas fa-edit"></i> Visit Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="needs-validation" novalidate>
                                    <?php if ($pre_selected_patient): ?>
                                        <!-- Pre-selected patient from queue -->
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">
                                                    <i class="fas fa-user"></i> Patient *
                                                </label>
                                                <div class="form-control-plaintext">
                                                    <strong><?php echo htmlspecialchars($pre_selected_patient['full_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        Card: <?php echo htmlspecialchars($pre_selected_patient['card_number']); ?> | 
                                                        Sex: <?php echo $pre_selected_patient['sex']; ?>
                                                        <?php if ($pre_selected_patient['phone']): ?>
                                                            | Phone: <?php echo htmlspecialchars($pre_selected_patient['phone']); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <input type="hidden" name="patient_id" value="<?php echo $pre_selected_patient['id']; ?>">
                                                <?php if (isset($_GET['queue_id'])): ?>
                                                    <input type="hidden" name="queue_id" value="<?php echo (int)$_GET['queue_id']; ?>">
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="visit_type" class="form-label">
                                                    <i class="fas fa-clipboard-list"></i> Visit Type *
                                                </label>
                                                <select class="form-select" id="visit_type" name="visit_type" required>
                                                    <option value="">Select Type</option>
                                                    <option value="general" <?php echo $pre_selected_visit_type === 'general' ? 'selected' : ''; ?>>General</option>
                                                    <option value="emergency" <?php echo $pre_selected_visit_type === 'emergency' ? 'selected' : ''; ?>>Emergency</option>
                                                    <option value="ultrasound" <?php echo $pre_selected_visit_type === 'ultrasound' ? 'selected' : ''; ?>>Ultrasound</option>
                                                    <option value="lab" <?php echo $pre_selected_visit_type === 'lab' ? 'selected' : ''; ?>>Lab</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <?php if ($pre_selected_patient['queue_notes']): ?>
                                            <div class="alert alert-info">
                                                <strong>Queue Notes:</strong> <?php echo htmlspecialchars($pre_selected_patient['queue_notes']); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <!-- Regular patient selection -->
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="patient_id" class="form-label">
                                                    <i class="fas fa-user"></i> Patient *
                                                </label>
                                                <select class="form-select" id="patient_id" name="patient_id" required>
                                                    <option value="">Select Patient</option>
                                                    <?php foreach ($patients as $patient): ?>
                                                        <option value="<?php echo $patient['id']; ?>" 
                                                                <?php echo ($is_edit && $visit['patient_id'] == $patient['id']) || 
                                                                         (!empty($_POST['patient_id']) && $_POST['patient_id'] == $patient['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($patient['full_name']); ?> 
                                                            (<?php echo htmlspecialchars($patient['card_number']); ?>)
                                                            - <?php echo $patient['queue_status']; ?>
                                                            <?php if ($patient['status'] === 'inactive'): ?>
                                                                [Registered: <?php echo date('M j, Y', strtotime($patient['date_registered'])); ?>]
                                                            <?php endif; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="invalid-feedback">
                                                    Please select a patient.
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="visit_type" class="form-label">
                                                    <i class="fas fa-clipboard-list"></i> Visit Type *
                                                </label>
                                                <select class="form-select" id="visit_type" name="visit_type" required>
                                                    <option value="">Select Type</option>
                                                    <option value="general" <?php echo ($is_edit && $visit['visit_type'] === 'general') || 
                                                                               (!empty($_POST['visit_type']) && $_POST['visit_type'] === 'general') ? 'selected' : ''; ?>>General</option>
                                                    <option value="emergency" <?php echo ($is_edit && $visit['visit_type'] === 'emergency') || 
                                                                               (!empty($_POST['visit_type']) && $_POST['visit_type'] === 'emergency') ? 'selected' : ''; ?>>Emergency</option>
                                                    <option value="ultrasound" <?php echo ($is_edit && $visit['visit_type'] === 'ultrasound') || 
                                                                               (!empty($_POST['visit_type']) && $_POST['visit_type'] === 'ultrasound') ? 'selected' : ''; ?>>Ultrasound</option>
                                                    <option value="lab" <?php echo ($is_edit && $visit['visit_type'] === 'lab') || 
                                                                       (!empty($_POST['visit_type']) && $_POST['visit_type'] === 'lab') ? 'selected' : ''; ?>>Lab</option>
                                                </select>
                                                <div class="invalid-feedback">
                                                    Please select visit type.
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="symptoms" class="form-label">
                                            <i class="fas fa-thermometer-half"></i> Symptoms
                                        </label>
                                        <textarea class="form-control" id="symptoms" name="symptoms" rows="4" 
                                                  placeholder="Describe patient symptoms..."><?php echo htmlspecialchars($is_edit ? $visit['symptoms'] : ($_POST['symptoms'] ?? '')); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="diagnosis" class="form-label">
                                            <i class="fas fa-search"></i> Diagnosis
                                        </label>
                                        <textarea class="form-control" id="diagnosis" name="diagnosis" rows="4" 
                                                  placeholder="Enter diagnosis..."><?php echo htmlspecialchars($is_edit ? $visit['diagnosis'] : ($_POST['diagnosis'] ?? '')); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="prescription" class="form-label">
                                            <i class="fas fa-pills"></i> Prescription
                                        </label>
                                        <textarea class="form-control" id="prescription" name="prescription" rows="4" 
                                                  placeholder="Enter prescription details..."><?php echo htmlspecialchars($is_edit ? $visit['prescription'] : ($_POST['prescription'] ?? '')); ?></textarea>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="visits.php" class="btn btn-secondary me-md-2">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> 
                                            <?php echo $is_edit ? 'Update Visit' : 'Create Visit'; ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <?php if ($message && !$is_edit && isset($visit_id)): ?>
                            <!-- Send to Department Section -->
                            <div class="card shadow mt-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-share"></i> Send Patient to Department
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-3">
                                        <i class="fas fa-info-circle"></i> 
                                        Based on the visit type, you can send the patient to the appropriate department for further services.
                                    </p>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="card border-primary">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-flask fa-3x text-primary mb-3"></i>
                                                    <h6>Lab Tests</h6>
                                                    <p class="small text-muted">Blood tests, urine analysis, etc.</p>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="referral_department" value="lab">
                                                        <input type="hidden" name="visit_id" value="<?php echo $visit_id; ?>">
                                                        <button type="submit" name="send_to_department" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-paper-plane"></i> Send to Lab
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="card border-info">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-baby fa-3x text-info mb-3"></i>
                                                    <h6>Ultrasound</h6>
                                                    <p class="small text-muted">Pregnancy scans, abdominal scans</p>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="referral_department" value="ultrasound">
                                                        <input type="hidden" name="visit_id" value="<?php echo $visit_id; ?>">
                                                        <button type="submit" name="send_to_department" class="btn btn-info btn-sm">
                                                            <i class="fas fa-paper-plane"></i> Send to Ultrasound
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="card border-danger">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-ambulance fa-3x text-danger mb-3"></i>
                                                    <h6>Emergency</h6>
                                                    <p class="small text-muted">Urgent care, emergency treatment</p>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="referral_department" value="emergency">
                                                        <input type="hidden" name="visit_id" value="<?php echo $visit_id; ?>">
                                                        <button type="submit" name="send_to_department" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-paper-plane"></i> Send to Emergency
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 text-center">
                                        <a href="visits.php" class="btn btn-secondary">
                                            <i class="fas fa-check"></i> Done - No Further Action Needed
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
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
                                        Patient will be activated on first visit
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i> 
                                        Visit is automatically timestamped
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i> 
                                        All fields except patient and type are optional
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i> 
                                        You can edit visits later if needed
                                    </li>
                                </ul>
                                
                                <?php if ($is_edit && $visit): ?>
                                    <hr>
                                    <h6>Visit Details</h6>
                                    <p><strong>Created:</strong> <?php echo date('F d, Y h:i A', strtotime($visit['created_at'])); ?></p>
                                    <p><strong>Patient:</strong> <?php echo htmlspecialchars($visit['patient_name']); ?></p>
                                    <p><strong>Card #:</strong> <?php echo htmlspecialchars($visit['card_number']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="card shadow mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-bolt"></i> Quick Actions
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="add_patient.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-user-plus"></i> Add New Patient
                                    </a>
                                    <a href="patients.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-users"></i> View All Patients
                                    </a>
                                    <a href="visits.php" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-list"></i> View All Visits
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html> 