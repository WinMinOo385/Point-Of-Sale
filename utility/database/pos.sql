-- POS schema and seed extracted from utility/setup.php

CREATE DATABASE IF NOT EXISTS `POS`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `POS`;

-- Tables
CREATE TABLE IF NOT EXISTS products (
    pid INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL CHECK (price >= 0),
    stock INT NOT NULL DEFAULT 0 CHECK (stock >= 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customers (
    cid INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ux_customers_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales (
    sid INT AUTO_INCREMENT PRIMARY KEY,
    cid INT NOT NULL,
    sale_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cid) REFERENCES customers(cid)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_sales_cid (cid),
    INDEX idx_sales_sale_date (sale_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sale_items (
    siid INT AUTO_INCREMENT PRIMARY KEY,
    sid INT NOT NULL,
    pid INT NOT NULL,
    quantity INT NOT NULL CHECK (quantity > 0),
    total_price DECIMAL(10,2) NOT NULL CHECK (total_price >= 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sid) REFERENCES sales(sid)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (pid) REFERENCES products(pid)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_sale_items_sid (sid),
    INDEX idx_sale_items_pid (pid)
) ENGINE=InnoDB;

-- Seed data
INSERT INTO products (name, price, stock) VALUES
('Pen', 0.50, 500),
('Notebook', 2.50, 200),
('Mouse', 15.00, 50),
('Pencil', 0.30, 300),
('Eraser', 0.20, 150),
('Ruler', 1.00, 100),
('Backpack', 25.00, 30),
('Calculator', 12.00, 40),
('Stapler', 3.50, 60),
('Highlighter', 1.20, 80)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO customers (name, email, phone) VALUES
('Alice Smith', 'alice@example.com', '091-1111111'),
('Bob Chan', 'bob@example.com', '092-2222222'),
('Charlie Lee', 'charlie@example.com', '093-3333333'),
('Diana Wang', 'diana@example.com', '094-4444444'),
('Ethan Brown', 'ethan@example.com', '095-5555555'),
('Fiona Green', 'fiona@example.com', '096-6666666')
ON DUPLICATE KEY UPDATE name = VALUES(name);


