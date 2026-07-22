-- ============================================================
-- Restaurant Menu - Database Setup
-- Run this file once in phpMyAdmin or MySQL CLI:
--   mysql -u root -p < setup.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS restaurant_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE restaurant_db;

-- Stores each customer order
CREATE TABLE IF NOT EXISTS orders (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(120)   NOT NULL,
    items        TEXT           NOT NULL,   -- JSON list of selected item names
    total_price  DECIMAL(10,2)  NOT NULL,
    order_time   TIMESTAMP      DEFAULT CURRENT_TIMESTAMP
);

-- Stores contact form submissions
CREATE TABLE IF NOT EXISTS contact_messages (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(120)   NOT NULL,
    email          VARCHAR(255)   NOT NULL,
    phone          VARCHAR(30)    DEFAULT NULL,
    guests         INT            DEFAULT NULL,
    preferred_date DATE           DEFAULT NULL,
    message        TEXT           DEFAULT NULL,
    submitted_at   TIMESTAMP      DEFAULT CURRENT_TIMESTAMP
);
