-- Doctor's Queue System
-- This system manages the flow of patients from reception to doctor

-- Queue table to track patients sent to doctor
CREATE TABLE IF NOT EXISTS doctor_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    sent_by INT NOT NULL, -- Receptionist who sent the patient
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('waiting', 'in_progress', 'completed') DEFAULT 'waiting',
    doctor_id INT NULL, -- Doctor who is seeing the patient
    started_at DATETIME NULL, -- When doctor started seeing the patient
    completed_at DATETIME NULL, -- When visit was completed
    notes TEXT NULL, -- Any notes from receptionist
    priority ENUM('normal', 'urgent', 'emergency') DEFAULT 'normal',
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (sent_by) REFERENCES users(id),
    FOREIGN KEY (doctor_id) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at),
    INDEX idx_priority (priority)
);

-- Add a queue_position field to track order (optional, can be calculated)
ALTER TABLE doctor_queue ADD COLUMN queue_position INT NULL;

-- Create a view for easy queue management
CREATE OR REPLACE VIEW current_queue AS
SELECT 
    dq.id as queue_id,
    dq.patient_id,
    dq.sent_at,
    dq.status,
    dq.priority,
    dq.queue_position,
    p.full_name,
    p.card_number,
    p.sex,
    p.phone,
    p.status as patient_status,
    u.full_name as sent_by_name,
    doc.full_name as doctor_name,
    dq.started_at,
    dq.completed_at,
    dq.notes
FROM doctor_queue dq
JOIN patients p ON dq.patient_id = p.id
JOIN users u ON dq.sent_by = u.id
LEFT JOIN users doc ON dq.doctor_id = doc.id
WHERE dq.status IN ('waiting', 'in_progress')
ORDER BY 
    CASE dq.priority 
        WHEN 'emergency' THEN 1 
        WHEN 'urgent' THEN 2 
        ELSE 3 
    END,
    dq.sent_at ASC;

-- Add some sample data for testing (optional)
-- INSERT INTO doctor_queue (patient_id, sent_by, notes, priority) VALUES 
-- (1, 1, 'Patient complaining of headache', 'normal'),
-- (2, 1, 'Follow-up visit', 'normal'),
-- (3, 1, 'Emergency case - chest pain', 'emergency'); 