USE scheduling_db;

-- 1. Patients Table Update
ALTER TABLE patients 
  MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'completed', 'no-show') DEFAULT 'pending',
  ADD COLUMN active_medications TEXT DEFAULT NULL,
  ADD COLUMN next_visit_date DATE DEFAULT NULL;

-- 2. Progress Notes / Triage Data
ALTER TABLE progress_notes
  ADD COLUMN blood_pressure VARCHAR(20) DEFAULT NULL,
  ADD COLUMN temperature DECIMAL(5,2) DEFAULT NULL,
  ADD COLUMN heart_rate INT DEFAULT NULL,
  ADD COLUMN weight DECIMAL(5,2) DEFAULT NULL;

-- 3. Doctors Table Update
ALTER TABLE doctors 
  MODIFY COLUMN status ENUM('available', 'booked', 'leave', 'in_clinic', 'on_break', 'emergency') DEFAULT 'leave';

-- 4. Book Appointment New Fields Update
ALTER TABLE patients
  ADD COLUMN IF NOT EXISTS first_name VARCHAR(60) DEFAULT NULL AFTER full_name,
  ADD COLUMN IF NOT EXISTS last_name VARCHAR(60) DEFAULT NULL AFTER first_name,
  ADD COLUMN IF NOT EXISTS middle_initial VARCHAR(10) DEFAULT NULL AFTER last_name,
  ADD COLUMN IF NOT EXISTS reference_no VARCHAR(20) DEFAULT NULL AFTER patient_id,
  ADD COLUMN IF NOT EXISTS with_companion TINYINT(1) NOT NULL DEFAULT 0 AFTER address,
  ADD COLUMN IF NOT EXISTS relationship_to_patient VARCHAR(50) DEFAULT NULL AFTER with_companion,
  ADD COLUMN IF NOT EXISTS appointment_recipient VARCHAR(50) DEFAULT 'Patient (Me)' AFTER relationship_to_patient,
  ADD COLUMN IF NOT EXISTS existing_medical_conditions TEXT DEFAULT NULL AFTER illness_description,
  ADD COLUMN IF NOT EXISTS current_medications TEXT DEFAULT NULL AFTER existing_medical_conditions,
  ADD COLUMN IF NOT EXISTS preferred_time_slot VARCHAR(60) DEFAULT NULL AFTER scheduled_time;

ALTER TABLE appointments
  ADD COLUMN IF NOT EXISTS reference_no VARCHAR(20) DEFAULT NULL AFTER id,
  ADD COLUMN IF NOT EXISTS relationship_to_patient VARCHAR(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS consultation_mode VARCHAR(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS preferred_time_slot VARCHAR(60) DEFAULT NULL;
