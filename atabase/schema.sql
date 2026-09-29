CREATE DATABASE IF NOT EXISTS smart_loyalty;
USE smart_loyalty;

-- 1. ตารางผู้ใช้งานระบบ (ลูกค้า, พนักงาน, แอดมิน)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer','employee','admin') NOT NULL,
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. ตารางรายการของรางวัล
CREATE TABLE rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reward_name VARCHAR(100) NOT NULL,
    points_required INT NOT NULL
);

-- 3. ตารางบันทึกประวัติการซื้อสินค้าและสะสมแต้ม
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    points_earned INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
);
