-- OceanStock Inventory System — Migration Script
-- Run this on an existing database to bring it up to date

-- ── accounts table ────────────────────────────────────────
ALTER TABLE accounts ADD COLUMN IF NOT EXISTS role       VARCHAR(50)  NOT NULL DEFAULT 'Staff' AFTER username;
ALTER TABLE accounts ADD COLUMN IF NOT EXISTS email      VARCHAR(150)          DEFAULT ''      AFTER role;
ALTER TABLE accounts ADD COLUMN IF NOT EXISTS full_name  VARCHAR(150)          DEFAULT ''      AFTER email;

-- ── products table ────────────────────────────────────────
ALTER TABLE products ADD COLUMN IF NOT EXISTS location   VARCHAR(150)          DEFAULT ''      AFTER category;

-- Remove price column if it still exists (no longer used)
ALTER TABLE products DROP COLUMN IF EXISTS price;

-- ── categories table ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed categories from existing product data (safe — ignores duplicates)
INSERT IGNORE INTO categories (name)
SELECT DISTINCT category FROM products
WHERE category != '' AND name != '__cat_placeholder__';

-- ── activity_logs table ───────────────────────────────────
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
