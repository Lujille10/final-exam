-- Run this to add missing columns
ALTER TABLE products ADD COLUMN IF NOT EXISTS location VARCHAR(150) DEFAULT '' AFTER category;
ALTER TABLE accounts ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'Staff' AFTER username;
ALTER TABLE accounts ADD COLUMN IF NOT EXISTS email VARCHAR(150) DEFAULT '' AFTER role;
ALTER TABLE accounts ADD COLUMN IF NOT EXISTS full_name VARCHAR(150) DEFAULT '' AFTER email;

-- Activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    username VARCHAR(100),
    action VARCHAR(50),
    action_type VARCHAR(20),
    details TEXT,
    ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
