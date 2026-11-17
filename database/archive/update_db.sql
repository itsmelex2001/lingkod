ALTER TABLE scheduling_db.users ADD COLUMN full_name VARCHAR(100) NULL AFTER username;
UPDATE scheduling_db.users SET full_name = username;
