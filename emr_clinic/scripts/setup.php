<?php
// Setup script for EMR Clinic System
// Run this once to initialize the database with sample data

require_once '../config/db.php';

echo "<h2>EMR Clinic System Setup</h2>";

try {
    // Create sample users
    $users = [
        [
            'full_name' => 'Dr. John Smith',
            'username' => 'doctor',
            'password' => 'password123',
            'role' => 'doctor'
        ],
        [
            'full_name' => 'Lab Technician Sarah',
            'username' => 'lab',
            'password' => 'password123',
            'role' => 'lab'
        ],
        [
            'full_name' => 'Ultrasound Tech Mike',
            'username' => 'ultrasound',
            'password' => 'password123',
            'role' => 'ultrasound'
        ],
        [
            'full_name' => 'Emergency Nurse Lisa',
            'username' => 'emergency',
            'password' => 'password123',
            'role' => 'emergency'
        ],
        [
            'full_name' => 'Receptionist Anna',
            'username' => 'receptionist',
            'password' => 'password123',
            'role' => 'receptionist'
        ],
        [
            'full_name' => 'System Administrator',
            'username' => 'admin',
            'password' => 'admin123',
            'role' => 'admin'
        ]
    ];

    echo "<h3>Creating sample users...</h3>";
    
    foreach ($users as $user) {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$user['username']]);
        
        if (!$stmt->fetch()) {
            $password_hash = password_hash($user['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user['full_name'], $user['username'], $password_hash, $user['role']]);
            echo "✓ Created user: {$user['full_name']} ({$user['username']})<br>";
        } else {
            echo "⚠ User already exists: {$user['username']}<br>";
        }
    }

    // Create sample patients
    $patients = [
        [
            'full_name' => 'Alice Johnson',
            'sex' => 'Female',
            'phone' => '+1234567890',
            'card_number' => 'P001',
            'status' => 'active'
        ],
        [
            'full_name' => 'Bob Wilson',
            'sex' => 'Male',
            'phone' => '+1234567891',
            'card_number' => 'P002',
            'status' => 'active'
        ],
        [
            'full_name' => 'Carol Davis',
            'sex' => 'Female',
            'phone' => '+1234567892',
            'card_number' => 'P003',
            'status' => 'inactive'
        ],
        [
            'full_name' => 'David Brown',
            'sex' => 'Male',
            'phone' => '+1234567893',
            'card_number' => 'P004',
            'status' => 'active'
        ],
        [
            'full_name' => 'Emma Garcia',
            'sex' => 'Female',
            'phone' => '+1234567894',
            'card_number' => 'P005',
            'status' => 'active'
        ]
    ];

    echo "<h3>Creating sample patients...</h3>";
    
    foreach ($patients as $patient) {
        // Check if patient already exists
        $stmt = $pdo->prepare("SELECT id FROM patients WHERE card_number = ?");
        $stmt->execute([$patient['card_number']]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO patients (full_name, sex, phone, card_number, date_registered, status) VALUES (?, ?, ?, ?, CURDATE(), ?)");
            $stmt->execute([$patient['full_name'], $patient['sex'], $patient['phone'], $patient['card_number'], $patient['status']]);
            echo "✓ Created patient: {$patient['full_name']} ({$patient['card_number']})<br>";
        } else {
            echo "⚠ Patient already exists: {$patient['card_number']}<br>";
        }
    }

    // Create sample visits
    echo "<h3>Creating sample visits...</h3>";
    
    // Get doctor and patients
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'doctor' LIMIT 1");
    $doctor = $stmt->fetch();
    
    $stmt = $pdo->query("SELECT id FROM patients WHERE status = 'active' LIMIT 3");
    $active_patients = $stmt->fetchAll();
    
    if ($doctor && !empty($active_patients)) {
        $visit_types = ['general', 'emergency', 'lab', 'ultrasound'];
        $symptoms = [
            'Fever and headache',
            'Chest pain',
            'Abdominal pain',
            'Back pain',
            'Cough and cold'
        ];
        $diagnoses = [
            'Common cold',
            'Hypertension',
            'Gastritis',
            'Muscle strain',
            'Upper respiratory infection'
        ];
        $prescriptions = [
            'Paracetamol 500mg 3x daily for 3 days',
            'Amlodipine 5mg once daily',
            'Omeprazole 20mg once daily',
            'Ibuprofen 400mg 3x daily for 5 days',
            'Amoxicillin 500mg 3x daily for 7 days'
        ];
        
        foreach ($active_patients as $index => $patient) {
            $visit_type = $visit_types[array_rand($visit_types)];
            $symptom = $symptoms[array_rand($symptoms)];
            $diagnosis = $diagnoses[array_rand($diagnoses)];
            $prescription = $prescriptions[array_rand($prescriptions)];
            
            // Create visit with random date in last 30 days
            $random_days = rand(0, 30);
            $visit_date = date('Y-m-d H:i:s', strtotime("-$random_days days"));
            
            $stmt = $pdo->prepare("INSERT INTO visits (patient_id, user_id, visit_type, symptoms, diagnosis, prescription, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$patient['id'], $doctor['id'], $visit_type, $symptom, $diagnosis, $prescription, $visit_date]);
            
            echo "✓ Created visit for patient ID {$patient['id']} ({$visit_type})<br>";
        }
    }

    // Create sample lab requests
    echo "<h3>Creating sample lab requests...</h3>";
    
    if ($doctor && !empty($active_patients)) {
        $tests = [
            'Complete Blood Count (CBC)',
            'Blood Glucose Test',
            'Liver Function Test',
            'Kidney Function Test',
            'Lipid Profile'
        ];
        
        foreach (array_slice($active_patients, 0, 2) as $patient) {
            $test = $tests[array_rand($tests)];
            $status = rand(0, 1) ? 'pending' : 'completed';
            
            $stmt = $pdo->prepare("INSERT INTO lab_requests (patient_id, doctor_id, tests_requested, status, date_requested) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$patient['id'], $doctor['id'], $test, $status]);
            
            echo "✓ Created lab request for patient ID {$patient['id']} ({$test}) - {$status}<br>";
        }
    }

    echo "<h3>Setup completed successfully!</h3>";
    echo "<p><strong>Default login credentials:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> username: admin, password: admin123</li>";
    echo "<li><strong>Doctor:</strong> username: doctor, password: password123</li>";
    echo "<li><strong>Lab Tech:</strong> username: lab, password: password123</li>";
    echo "<li><strong>Ultrasound Tech:</strong> username: ultrasound, password: password123</li>";
    echo "<li><strong>Emergency Nurse:</strong> username: emergency, password: password123</li>";
    echo "<li><strong>Receptionist:</strong> username: receptionist, password: password123</li>";
    echo "</ul>";
    
    echo "<p><a href='../index.php' class='btn btn-primary'>Go to EMR System</a></p>";

} catch (Exception $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
}
?> 