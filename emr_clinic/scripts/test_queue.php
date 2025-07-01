<?php
// test_queue.php - Add sample patients to doctor's queue for testing

require_once '../config/db.php';

echo "<h2>Adding Sample Patients to Doctor's Queue</h2>";

// Get some patients and users for testing
$stmt = $pdo->query("SELECT id FROM patients LIMIT 5");
$patients = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->query("SELECT id FROM users WHERE role = 'receptionist' LIMIT 1");
$receptionist_id = $stmt->fetchColumn();

if (!$receptionist_id) {
    echo "<p style='color: red;'>No receptionist found. Please run the setup script first.</p>";
    exit;
}

$priorities = ['normal', 'urgent', 'emergency'];
$notes = [
    'Patient complaining of headache',
    'Follow-up visit',
    'Emergency case - chest pain',
    'Regular checkup',
    'Patient with fever'
];

$count = 0;
foreach ($patients as $index => $patient_id) {
    // Check if patient is already in queue
    $stmt = $pdo->prepare("SELECT id FROM doctor_queue WHERE patient_id = ? AND status IN ('waiting', 'in_progress')");
    $stmt->execute([$patient_id]);
    
    if (!$stmt->fetch()) {
        $priority = $priorities[$index % count($priorities)];
        $note = $notes[$index % count($notes)];
        
        $stmt = $pdo->prepare("
            INSERT INTO doctor_queue (patient_id, sent_by, notes, priority) 
            VALUES (?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$patient_id, $receptionist_id, $note, $priority])) {
            echo "<p style='color: green;'>✓ Added patient ID $patient_id to queue with $priority priority</p>";
            $count++;
        } else {
            echo "<p style='color: red;'>✗ Failed to add patient ID $patient_id to queue</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ Patient ID $patient_id is already in queue</p>";
    }
}

echo "<h3>Summary</h3>";
echo "<p>Added $count patients to the doctor's queue.</p>";
echo "<p><a href='../views/doctor_queue.php'>View Doctor's Queue</a></p>";
echo "<p><a href='../views/patients.php'>View Patients</a></p>";
?> 