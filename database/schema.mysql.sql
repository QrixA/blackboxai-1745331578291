-- Create database if not exists
CREATE DATABASE IF NOT EXISTS scid_billing;
USE scid_billing;

-- Users table with additional fields
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    balance DECIMAL(10,2) DEFAULT 0.00,
    profile_photo VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_affiliate TINYINT(1) DEFAULT 0,
    affiliate_code VARCHAR(50) UNIQUE DEFAULT NULL,
    affiliate_commission DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products table with pricing tiers
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    ram VARCHAR(20),
    disk VARCHAR(20),
    cpu VARCHAR(20),
    price_hourly DECIMAL(10,2) NOT NULL,
    price_monthly DECIMAL(10,2) NOT NULL,
    price_yearly DECIMAL(10,2) NOT NULL,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    billing_cycle ENUM('hourly', 'monthly', 'yearly') DEFAULT 'monthly',
    quantity INT DEFAULT 1,
    promo_code VARCHAR(50) DEFAULT NULL,
    affiliate_code VARCHAR(50) DEFAULT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders table with additional fields
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    product_id INT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    billing_cycle ENUM('hourly', 'monthly', 'yearly') DEFAULT 'monthly',
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status TINYINT(1) DEFAULT 0,
    promo_code VARCHAR(50) DEFAULT NULL,
    affiliate_code VARCHAR(50) DEFAULT NULL,
    affiliate_commission DECIMAL(10,2) DEFAULT 0.00,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Services table
CREATE TABLE IF NOT EXISTS services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    product_id INT,
    server_name VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings table with additional settings
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    key_name VARCHAR(50) UNIQUE NOT NULL,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Promo Codes table
CREATE TABLE IF NOT EXISTS promo_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('percentage', 'fixed') NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    start_date TIMESTAMP NULL,
    end_date TIMESTAMP NULL,
    max_uses INT DEFAULT NULL,
    current_uses INT DEFAULT 0,
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Balance History table
CREATE TABLE IF NOT EXISTS balance_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('topup', 'deduction', 'afk_reward', 'ad_reward', 'affiliate_commission') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AFK Rewards History table
CREATE TABLE IF NOT EXISTS afk_rewards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    duration_minutes INT NOT NULL,
    reward_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ad Watch History table
CREATE TABLE IF NOT EXISTS ad_watches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    ad_duration_seconds INT NOT NULL,
    reward_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@sakuracloud.id', '$2y$10$dqMBqLc7.8BRQYXOstw05Ofe06tDk0dGOo0rv4egjKDwv0DGqNnAe', 'admin');

-- Insert default settings
INSERT INTO settings (key_name, value) VALUES 
('duitku_api_key', ''),
('duitku_merchant_code', ''),
('duitku_callback_url', ''),
('pterodactyl_api_key', ''),
('tax_percentage', '11'),
('afk_reward_amount', '1000'),
('afk_required_minutes', '5'),
('ad_reward_amount', '500'),
('ad_required_seconds', '30'),
('maintenance_mode', '0'),
('order_prefix', 'ORD'),
('default_affiliate_commission', '10');

-- Insert sample categories
INSERT INTO categories (name, description) VALUES
('VPS Basic', 'Basic Virtual Private Server packages'),
('VPS Pro', 'Professional Virtual Private Server packages'),
('Dedicated Server', 'Dedicated server solutions');

-- Insert sample products with tiered pricing
INSERT INTO products (category_id, name, description, ram, cpu, disk, price_hourly, price_monthly, price_yearly) VALUES
(1, 'Basic VPS 1', 'Entry level VPS', '1GB', '1 vCPU', '20GB', 1000, 50000, 500000),
(1, 'Basic VPS 2', 'Standard VPS', '2GB', '1 vCPU', '40GB', 2000, 100000, 1000000),
(2, 'Pro VPS 1', 'Professional VPS', '4GB', '2 vCPU', '80GB', 4000, 200000, 2000000),
(2, 'Pro VPS 2', 'Advanced VPS', '8GB', '4 vCPU', '160GB', 8000, 400000, 4000000),
(3, 'Dedicated 1', 'Basic Dedicated Server', '16GB', '8 vCPU', '500GB', 20000, 1000000, 10000000),
(3, 'Dedicated 2', 'Premium Dedicated Server', '32GB', '16 vCPU', '1000GB', 40000, 2000000, 20000000);
