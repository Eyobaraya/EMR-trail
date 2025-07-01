-- Create database
CREATE DATABASE IF NOT EXISTS emr_clinic;
USE emr_clinic;

-- Users table (doctor, lab tech, receptionist, emergency, etc.)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100),
    username VARCHAR(50) UNIQUE,
    password_hash VARCHAR(255),
    role ENUM('admin', 'doctor', 'lab', 'receptionist', 'emergency', 'ultrasound') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Patients table
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100),
    sex ENUM('Male', 'Female') NOT NULL,
    phone VARCHAR(20),
    card_number VARCHAR(20) UNIQUE,
    date_registered DATE,
    status ENUM('active', 'inactive') DEFAULT 'inactive',
    first_visit_date DATE NULL
);

-- Visits table
CREATE TABLE visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    user_id INT, -- Doctor or emergency nurse
    visit_type ENUM('general', 'emergency', 'ultrasound', 'lab'),
    symptoms TEXT,
    diagnosis TEXT,
    prescription TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Lab Requests table
CREATE TABLE lab_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    doctor_id INT,
    tests_requested TEXT,
    result_text TEXT,
    scanned_file_path VARCHAR(255),
    status ENUM('pending', 'completed') DEFAULT 'pending',
    date_requested TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_completed TIMESTAMP NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES users(id)
);

-- Ultrasound Reports table
CREATE TABLE ultrasound_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    requested_by INT, -- Doctor ID
    technician_id INT,
    report_text TEXT,
    scanned_file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (technician_id) REFERENCES users(id)
);

-- Emergency Room Records table
CREATE TABLE emergency_visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    staff_id INT, -- Nurse or emergency staff
    vitals TEXT,
    symptoms TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (staff_id) REFERENCES users(id)
);
