-- OceanStock Inventory System — Database Setup
CREATE DATABASE IF NOT EXISTS inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventory_db;

CREATE TABLE IF NOT EXISTS accounts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       VARCHAR(50)  NOT NULL DEFAULT 'Staff',
    email      VARCHAR(150)          DEFAULT '',
    full_name  VARCHAR(150)          DEFAULT '',
    created_at TIMESTAMP             DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    description TEXT,
    quantity    INT          NOT NULL DEFAULT 0,
    category    VARCHAR(100) NOT NULL,
    location    VARCHAR(150)          DEFAULT '',
    created_at  TIMESTAMP             DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at  TIMESTAMP             DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS activity_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT,
    username    VARCHAR(100),
    action      VARCHAR(50),
    action_type VARCHAR(20),
    details     TEXT,
    ip          VARCHAR(45),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO products (name, description, quantity, category, location) VALUES
('Diving Mask',      'Full-face silicone diving mask with tempered glass',  12, 'Diving Gear',      'Storage A'),
('Oxygen Tank',      '3000 PSI aluminum scuba tank, 80 cu ft',               8, 'Diving Gear',      'Boat 1'),
('Fishing Net',      'Heavy-duty 50m nylon net',                              3, 'Nets',             'Storage B'),
('Life Vest',        'Coast Guard approved adult life vest',                 20, 'Safety Equipment', 'Storage A'),
('Rescue Boat',      'Inflatable rescue dinghy 4-person',                    2, 'Boats',            'Harbor'),
('Water Test Kit',   'pH, salinity, nitrate test strips (50 pack)',           7, 'Testing Kits',     'Lab Room'),
('Coral Marker Buoy','Hi-vis yellow marker buoy with 20m line',             15, 'Nets',             'Storage B');

INSERT INTO categories (name) VALUES
('Diving Gear'),
('Nets'),
('Safety Equipment'),
('Boats'),
('Testing Kits');
