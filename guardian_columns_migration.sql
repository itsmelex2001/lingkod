-- Migration: Add Guardian Information for Minors (Under 18)
-- Allows NULL for adult patients

ALTER TABLE patients 
ADD COLUMN guardian_name VARCHAR(100) NULL AFTER with_companion,
ADD COLUMN guardian_contact_number VARCHAR(25) NULL AFTER guardian_name;

