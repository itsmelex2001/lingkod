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
