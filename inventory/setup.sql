-- OceanStock Inventory System — Database Setup
CREATE DATABASE IF NOT EXISTS inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventory_db;

CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    description TEXT,
    quantity    INT NOT NULL DEFAULT 0,
    price       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    category    VARCHAR(100) NOT NULL,
    location    VARCHAR(150) DEFAULT '',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ALTER TABLE products ADD COLUMN location VARCHAR(150) DEFAULT '' AFTER category;

INSERT INTO products (name,description,quantity,price,category,location) VALUES
('Diving Mask','Full-face silicone diving mask with tempered glass',12,1850.00,'Diving Gear','Storage A'),
('Oxygen Tank','3000 PSI aluminum scuba tank, 80 cu ft',8,12500.00,'Diving Gear','Boat 1'),
('Fishing Net','Heavy-duty 50m nylon net',3,2200.00,'Nets','Storage B'),
('Life Vest','Coast Guard approved adult life vest',20,950.00,'Safety Equipment','Storage A'),
('Rescue Boat','Inflatable rescue dinghy 4-person',2,45000.00,'Boats','Harbor'),
('Water Test Kit','pH, salinity, nitrate test strips (50 pack)',7,480.00,'Testing Kits','Lab Room'),
('Coral Marker Buoy','Hi-vis yellow marker buoy with 20m line',15,320.00,'Nets','Storage B');
