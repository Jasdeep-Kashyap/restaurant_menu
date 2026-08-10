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

-- Stores menu items (managed via admin panel)
CREATE TABLE IF NOT EXISTS menu_items (
    id          VARCHAR(20)    PRIMARY KEY,
    category    VARCHAR(50)    NOT NULL,
    name        VARCHAR(150)   NOT NULL,
    description TEXT           NOT NULL,
    price       DECIMAL(10,2)  NOT NULL,
    badge       VARCHAR(30)    DEFAULT 'Veg',
    image_path  VARCHAR(500)   DEFAULT NULL,
    is_visible  TINYINT(1)     DEFAULT 1,
    sort_order  INT            DEFAULT 0,
    created_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP
);

-- ── Seed default menu items (only if table is empty) ──────────
INSERT IGNORE INTO menu_items (id, category, name, description, price, badge, image_path, sort_order) VALUES
('s1', 'Starters', 'Bruschetta',
 'Toasted sourdough topped with marinated cherry tomatoes, fresh basil, and a drizzle of aged balsamic.',
 280.00, 'Non-Veg', '../Image_Content/Bruschetta.jfif', 1),

('s2', 'Starters', 'Creamy Mushroom Soup',
 'Velvety blend of wild forest mushrooms with truffle oil, cream, and fresh thyme croutons.',
 120.00, 'Veg', '../Image_Content/Soup.jfif', 2),

('s3', 'Starters', 'Calamari Fritti',
 'Lightly dusted and fried squid rings served with house spicy marinara and lemon wedge.',
 350.00, 'Non-Veg', '../Image_Content/Calamari_fritti.jfif', 3),

('m1', 'Mains', 'Grilled Salmon Fillet',
 'Atlantic salmon, herb-crusted, served on saffron risotto with asparagus and lemon beurre blanc.',
 520.00, 'Non-Veg', '../Image_Content/Grilled Salmon.jfif', 4),

('m2', 'Mains', 'Spaghetti',
 '220g prime-cut fillet, pan-seared with garlic butter, served with truffle fries and roasted veggies.',
 320.00, 'Veg', '../Image_Content/Spaghetti.jfif', 5),

('m3', 'Mains', 'Wild Mushroom Pasta',
 'Fresh tagliatelle tossed with porcini, cremini, and shiitake mushrooms in a garlic white wine cream sauce.',
 175.00, 'Veg', '../Image_Content/Pasta.jfif', 6),

('m4', 'Mains', 'Chicken Milanese',
 'Crispy breaded chicken breast served with arugula salad, shaved parmesan, and cherry tomatoes.',
 190.00, 'Non-Veg', '../Image_Content/Milanese.jfif', 7),

('d1', 'Desserts', 'Classic Tiramisu',
 'Mascarpone cream layered with espresso-soaked ladyfingers, dusted with Valrhona cocoa.',
 150.00, 'Veg', '../Image_Content/tiramisu.jfif', 8),

('d2', 'Desserts', 'Warm Chocolate Fondant',
 'Dark chocolate lava cake with a molten centre, served with vanilla bean ice cream.',
 105.00, 'Veg', '../Image_Content/warm chocolate.jfif', 9),

('dr1', 'Drinks', 'Fresh Citrus Lemonade',
 'Hand-pressed lemons with mint, agave syrup, and sparkling water. Refreshingly vibrant.',
 50.00, 'Veg', '../Image_Content/lemonade.jfif', 10),

('dr2', 'Drinks', 'Matcha Latte',
 'Premium ceremonial-grade matcha whisked with oat milk, served over ice with honey.',
 65.00, 'Veg', '../Image_Content/matcha.jfif', 11);

-- ============================================================
-- Migration: Order Processing Upgrade
-- Adds cycling order numbers (1–500) and delivery scheduling.
-- Safe to re-run (IF NOT EXISTS / IGNORE).
-- ============================================================

-- New columns on orders table
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS order_number  SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER id,
    ADD COLUMN IF NOT EXISTS scheduled_at  DATETIME DEFAULT NULL AFTER order_time,
    ADD COLUMN IF NOT EXISTS utr_number    VARCHAR(50) DEFAULT NULL AFTER total_price;

-- Single-row counter for cycling Order IDs (1–500)
CREATE TABLE IF NOT EXISTS order_counter (
    id          TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
    next_number SMALLINT UNSIGNED NOT NULL DEFAULT 1
);
INSERT IGNORE INTO order_counter (id, next_number) VALUES (1, 1);

-- ============================================================
-- Restaurant Table Reservations
-- ============================================================

CREATE TABLE IF NOT EXISTS `tables` (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    name     VARCHAR(30)  NOT NULL,
    zone     ENUM('Main','Patio') NOT NULL,
    capacity TINYINT UNSIGNED NOT NULL,
    status   ENUM('FREE','RESERVED') NOT NULL DEFAULT 'FREE',
    x        TINYINT UNSIGNED NOT NULL,
    y        TINYINT UNSIGNED NOT NULL
);

-- Seed 10 tables (safe to re-run)
INSERT IGNORE INTO `tables` (id, name, zone, capacity, status, x, y) VALUES
(1,  'T1',  'Main',  2, 'FREE',     1, 1),
(2,  'T2',  'Main',  4, 'RESERVED', 3, 1),
(3,  'T3',  'Main',  6, 'FREE',     5, 1),
(4,  'T4',  'Main',  4, 'RESERVED', 1, 3),
(5,  'T5',  'Main',  2, 'FREE',     3, 3),
(6,  'T6',  'Main',  6, 'FREE',     5, 3),
(7,  'T7',  'Patio', 2, 'FREE',     1, 1),
(8,  'T8',  'Patio', 4, 'RESERVED', 3, 1),
(9,  'T9',  'Patio', 4, 'FREE',     1, 3),
(10, 'T10', 'Patio', 6, 'FREE',     3, 3);

-- ============================================================
-- Time-Bound Table Reservations & Cancellation System
-- ============================================================

CREATE TABLE IF NOT EXISTS `reservations` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_id INT NOT NULL,
    customer_name VARCHAR(120) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(30) NOT NULL,
    res_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    duration_hours TINYINT UNSIGNED NOT NULL DEFAULT 2,
    guests TINYINT UNSIGNED NOT NULL DEFAULT 2,
    status ENUM('CONFIRMED', 'CANCELLED', 'EXPIRED') NOT NULL DEFAULT 'CONFIRMED',
    cancellation_token VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES `tables`(id) ON DELETE CASCADE
);

