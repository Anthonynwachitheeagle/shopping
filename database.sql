-- ============================================
--  LUXE Shop — Database Setup
--  Run this file once to create all tables
-- ============================================

CREATE DATABASE IF NOT EXISTS luxe_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE luxe_shop;

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    category ENUM('clothing', 'accessories', 'home') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    emoji VARCHAR(10) DEFAULT '🛍️',
    badge VARCHAR(50) DEFAULT '',
    description TEXT DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(200) NOT NULL,
    customer_email VARCHAR(200) DEFAULT '',
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Seed Data ──
INSERT INTO products (name, category, price, stock, emoji, badge, description) VALUES
('Cashmere Overcoat',  'clothing',    480.00, 12, '🧥', 'New',  'Luxurious double-faced cashmere.'),
('Silk Midi Dress',    'clothing',    295.00,  8, '👗', '',     'Pure 22-momme mulberry silk.'),
('Merino Turtleneck',  'clothing',    155.00, 20, '🥿', '',     'Lightweight extra-fine merino.'),
('Gold Cuff Bracelet', 'accessories', 320.00,  5, '📿', 'Sale', 'Solid 18k gold-plated brass.'),
('Leather Tote',       'accessories', 390.00,  7, '👜', '',     'Full-grain vegetable-tanned leather.'),
('Silk Scarf',         'accessories', 145.00, 14, '🎀', 'New',  'Hand-rolled edges, pure silk.'),
('Linen Throw',        'home',        210.00,  9, '🛋️', '',     'Stonewashed Belgian linen.'),
('Ceramic Vase',       'home',        175.00,  6, '🏺', 'New',  'Wheel-thrown stoneware, matte glaze.');

INSERT INTO orders (customer_name, customer_email, total, status, created_at) VALUES
('Amara Osei',   'amara@example.com',  775.00,  'delivered', '2025-03-28 10:00:00'),
('Fatima Nour',  'fatima@example.com', 295.00,  'pending',   '2025-03-29 14:30:00'),
('Kwame Asante', 'kwame@example.com',  1010.00, 'shipped',   '2025-03-30 09:15:00'),
('Lena Mwangi',  'lena@example.com',   480.00,  'pending',   '2025-03-30 11:45:00'),
('Emeka Olu',    'emeka@example.com',  465.00,  'delivered', '2025-03-27 16:20:00');

-- Default admin: username=admin, password=admin123
INSERT INTO admin_users (username, password_hash) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
