-- Users table with additional fields
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT CHECK(role IN ('user', 'admin')) DEFAULT 'user',
    is_active INTEGER DEFAULT 1,
    profile_photo TEXT DEFAULT NULL,
    balance DECIMAL(10,2) DEFAULT 0.00,
    is_affiliate INTEGER DEFAULT 0,
    affiliate_code TEXT UNIQUE DEFAULT NULL,
    affiliate_commission DECIMAL(5,2) DEFAULT 0.00,
    last_login TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Login Attempts table for security
CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL,
    ip_address TEXT NOT NULL,
    success INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Activity Logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT NOT NULL,
    details TEXT,
    ip_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    status INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table with tiered pricing
CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER,
    name TEXT NOT NULL,
    description TEXT,
    ram TEXT,
    disk TEXT,
    cpu TEXT,
    price_hourly DECIMAL(10,2) NOT NULL,
    price_monthly DECIMAL(10,2) NOT NULL,
    price_yearly DECIMAL(10,2) NOT NULL,
    status INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Orders table with additional fields
CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    product_id INTEGER,
    order_number TEXT UNIQUE NOT NULL,
    status TEXT CHECK(status IN ('pending', 'confirmed', 'cancelled')) DEFAULT 'pending',
    billing_cycle TEXT CHECK(billing_cycle IN ('hourly', 'monthly', 'yearly')) DEFAULT 'monthly',
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status INTEGER DEFAULT 0,
    promo_code TEXT DEFAULT NULL,
    affiliate_code TEXT DEFAULT NULL,
    affiliate_commission DECIMAL(10,2) DEFAULT 0.00,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Services table
CREATE TABLE IF NOT EXISTS services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    product_id INTEGER,
    server_name TEXT NOT NULL,
    ip_address TEXT,
    status INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key_name TEXT UNIQUE NOT NULL,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cart table
CREATE TABLE IF NOT EXISTS cart (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    billing_cycle TEXT CHECK(billing_cycle IN ('hourly', 'monthly', 'yearly')) DEFAULT 'monthly',
    quantity INTEGER DEFAULT 1,
    promo_code TEXT DEFAULT NULL,
    affiliate_code TEXT DEFAULT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Promo Codes table
CREATE TABLE IF NOT EXISTS promo_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    type TEXT CHECK(type IN ('percentage', 'fixed')) NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    start_date TIMESTAMP,
    end_date TIMESTAMP,
    max_uses INTEGER DEFAULT NULL,
    current_uses INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payment Requests table
CREATE TABLE IF NOT EXISTS payment_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    order_id INTEGER DEFAULT NULL,
    reference TEXT UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type TEXT CHECK(type IN ('topup', 'order')) NOT NULL,
    status TEXT CHECK(status IN ('pending', 'success', 'failed')) DEFAULT 'pending',
    payment_url TEXT,
    payment_code TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- Balance History table
CREATE TABLE IF NOT EXISTS balance_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type TEXT CHECK(type IN ('topup', 'deduction', 'afk_reward', 'ad_reward', 'affiliate_commission')) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- AFK Rewards table
CREATE TABLE IF NOT EXISTS afk_rewards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    duration_minutes INTEGER NOT NULL,
    reward_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Ad Watches table
CREATE TABLE IF NOT EXISTS ad_watches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    ad_duration_seconds INTEGER NOT NULL,
    reward_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT OR IGNORE INTO users (username, email, password, role, is_active, balance) VALUES 
('admin', 'admin@sakuracloud.id', '$2y$10$dqMBqLc7.8BRQYXOstw05Ofe06tDk0dGOo0rv4egjKDwv0DGqNnAe', 'admin', 1, 0);

-- Insert default settings
INSERT OR IGNORE INTO settings (key_name, value) VALUES 
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
INSERT OR IGNORE INTO categories (name, description) VALUES
('VPS Basic', 'Basic Virtual Private Server packages'),
('VPS Pro', 'Professional Virtual Private Server packages'),
('Dedicated Server', 'Dedicated server solutions');

-- Insert sample products with tiered pricing
INSERT OR IGNORE INTO products (category_id, name, description, ram, cpu, disk, price_hourly, price_monthly, price_yearly) VALUES
(1, 'Basic VPS 1', 'Entry level VPS', '1GB', '1 vCPU', '20GB', 1000, 50000, 500000),
(1, 'Basic VPS 2', 'Standard VPS', '2GB', '1 vCPU', '40GB', 2000, 100000, 1000000),
(2, 'Pro VPS 1', 'Professional VPS', '4GB', '2 vCPU', '80GB', 4000, 200000, 2000000),
(2, 'Pro VPS 2', 'Advanced VPS', '8GB', '4 vCPU', '160GB', 8000, 400000, 4000000),
(3, 'Dedicated 1', 'Basic Dedicated Server', '16GB', '8 vCPU', '500GB', 20000, 1000000, 10000000),
(3, 'Dedicated 2', 'Premium Dedicated Server', '32GB', '16 vCPU', '1000GB', 40000, 2000000, 20000000);
