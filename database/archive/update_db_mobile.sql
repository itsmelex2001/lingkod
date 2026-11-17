ALTER TABLE scheduling_db.users ADD COLUMN mobile_number VARCHAR(20) NULL AFTER full_name;
ALTER TABLE scheduling_db.users ADD COLUMN recovery_code VARCHAR(10) NULL AFTER password;
