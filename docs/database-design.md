# Thiết kế Database - Gym Management System

## ERD (Entity Relationship Diagram)

```
[users] 1---* [members]
[users] 1---* [staff]
[users] 1---* [trainers]
[users] *---1 [roles]

[members] 1---* [member_packages]
[packages] 1---* [member_packages]

[members] 1---* [training_schedules]
[trainers] 1---* [training_schedules]

[members] 1---* [orders]
[orders] 1---* [order_items]
[products] 1---* [order_items]

[orders] 1---1 [payments]

[members] 1---* [carts]
[products] 1---* [carts]

[equipment] 1---* [equipment_maintenance]

[members] 1---* [feedback]
[users] 1---* [notifications]
```

## Database Schema

### 1. users (Tài khoản người dùng)
```sql
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);
```

### 2. roles (Vai trò)
```sql
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Data mẫu
INSERT INTO roles (role_name, description) VALUES
('admin', 'Quản trị viên hệ thống'),
('staff', 'Nhân viên'),
('trainer', 'Huấn luyện viên'),
('member', 'Hội viên');
```

### 3. members (Hội viên)
```sql
CREATE TABLE members (
    member_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    gender ENUM('male', 'female', 'other'),
    date_of_birth DATE,
    phone VARCHAR(15),
    address TEXT,
    id_card VARCHAR(20),
    emergency_contact VARCHAR(15),
    emergency_name VARCHAR(100),
    health_notes TEXT,
    photo VARCHAR(255),
    membership_status ENUM('active', 'expired', 'suspended') DEFAULT 'active',
    joined_date DATE DEFAULT (CURRENT_DATE),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

### 4. staff (Nhân viên)
```sql
CREATE TABLE staff (
    staff_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    position VARCHAR(50),
    phone VARCHAR(15),
    email VARCHAR(100),
    hire_date DATE,
    salary DECIMAL(10,2),
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

### 5. trainers (Huấn luyện viên)
```sql
CREATE TABLE trainers (
    trainer_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    specialization VARCHAR(255),
    certifications TEXT,
    experience_years INT,
    phone VARCHAR(15),
    email VARCHAR(100),
    hourly_rate DECIMAL(10,2),
    bio TEXT,
    photo VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    rating DECIMAL(3,2) DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

### 6. packages (Gói tập)
```sql
CREATE TABLE packages (
    package_id INT PRIMARY KEY AUTO_INCREMENT,
    package_name VARCHAR(100) NOT NULL,
    duration_days INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    features JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 7. member_packages (Gói tập của hội viên)
```sql
CREATE TABLE member_packages (
    member_package_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    package_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    payment_status ENUM('paid', 'pending', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(package_id)
);
```

### 8. training_schedules (Lịch tập)
```sql
CREATE TABLE training_schedules (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    trainer_id INT NOT NULL,
    scheduled_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id)
);
```

### 9. categories (Danh mục sản phẩm)
```sql
CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    parent_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (parent_id) REFERENCES categories(category_id)
);
```

### 10. products (Sản phẩm)
```sql
CREATE TABLE products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);
```

### 11. orders (Đơn hàng)
```sql
CREATE TABLE orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    notes TEXT,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
);
```

### 12. order_items (Chi tiết đơn hàng)
```sql
CREATE TABLE order_items (
    order_item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);
```

### 13. payments (Thanh toán)
```sql
CREATE TABLE payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NULL,
    member_package_id INT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'bank_transfer', 'e-wallet') NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    notes TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (member_package_id) REFERENCES member_packages(member_package_id)
);
```

### 14. carts (Giỏ hàng)
```sql
CREATE TABLE carts (
    cart_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (member_id, product_id)
);
```

### 15. equipment (Thiết bị)
```sql
CREATE TABLE equipment (
    equipment_id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    quantity INT NOT NULL DEFAULT 1,
    status ENUM('good', 'maintenance', 'broken', 'retired') DEFAULT 'good',
    purchase_date DATE,
    warranty_expiry DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 16. equipment_maintenance (Bảo trì thiết bị)
```sql
CREATE TABLE equipment_maintenance (
    maintenance_id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_id INT NOT NULL,
    maintenance_date DATE NOT NULL,
    maintenance_type ENUM('routine', 'repair', 'inspection') NOT NULL,
    description TEXT,
    cost DECIMAL(10,2),
    performed_by VARCHAR(100),
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE CASCADE
);
```

### 17. feedback (Phản hồi)
```sql
CREATE TABLE feedback (
    feedback_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    feedback_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('new', 'reviewed', 'resolved') DEFAULT 'new',
    admin_response TEXT,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
);
```

### 18. notifications (Thông báo)
```sql
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

### 19. activity_logs (Nhật ký hoạt động)
```sql
CREATE TABLE activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```

## Indexes (Tối ưu hóa)

```sql
-- Users
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role_id);

-- Members
CREATE INDEX idx_members_user ON members(user_id);
CREATE INDEX idx_members_status ON members(membership_status);

-- Training Schedules
CREATE INDEX idx_schedules_date ON training_schedules(scheduled_date);
CREATE INDEX idx_schedules_member ON training_schedules(member_id);
CREATE INDEX idx_schedules_trainer ON training_schedules(trainer_id);

-- Orders
CREATE INDEX idx_orders_member ON orders(member_id);
CREATE INDEX idx_orders_date ON orders(order_date);
CREATE INDEX idx_orders_status ON orders(status);

-- Payments
CREATE INDEX idx_payments_order ON payments(order_id);
CREATE INDEX idx_payments_date ON payments(payment_date);
```

## Views (Báo cáo)

### Doanh thu theo tháng
```sql
CREATE VIEW monthly_revenue AS
SELECT 
    DATE_FORMAT(payment_date, '%Y-%m') as month,
    SUM(amount) as total_revenue,
    COUNT(*) as transaction_count
FROM payments
WHERE status = 'completed'
GROUP BY DATE_FORMAT(payment_date, '%Y-%m');
```

### Thống kê hội viên
```sql
CREATE VIEW member_statistics AS
SELECT 
    membership_status,
    COUNT(*) as total_members
FROM members
GROUP BY membership_status;
```

## Triggers

### Tự động cập nhật tổng tiền đơn hàng
```sql
DELIMITER //
CREATE TRIGGER update_order_total
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    UPDATE orders 
    SET total_amount = (
        SELECT SUM(subtotal) 
        FROM order_items 
        WHERE order_id = NEW.order_id
    )
    WHERE order_id = NEW.order_id;
END//
DELIMITER ;
```

### Ghi log khi tạo user mới
```sql
DELIMITER //
CREATE TRIGGER log_new_user
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, table_name, record_id, description)
    VALUES (NEW.user_id, 'CREATE', 'users', NEW.user_id, 'New user created');
END//
DELIMITER ;
```

## Stored Procedures

### Gia hạn gói tập
```sql
DELIMITER //
CREATE PROCEDURE extend_membership(
    IN p_member_package_id INT,
    IN p_additional_days INT
)
BEGIN
    UPDATE member_packages
    SET end_date = DATE_ADD(end_date, INTERVAL p_additional_days DAY)
    WHERE member_package_id = p_member_package_id;
END//
DELIMITER ;
```

### Kiểm tra lịch trống của trainer
```sql
DELIMITER //
CREATE PROCEDURE check_trainer_availability(
    IN p_trainer_id INT,
    IN p_date DATE,
    IN p_start_time TIME,
    IN p_end_time TIME,
    OUT p_available BOOLEAN
)
BEGIN
    DECLARE conflict_count INT;
    
    SELECT COUNT(*) INTO conflict_count
    FROM training_schedules
    WHERE trainer_id = p_trainer_id
    AND scheduled_date = p_date
    AND status != 'cancelled'
    AND (
        (p_start_time BETWEEN start_time AND end_time) OR
        (p_end_time BETWEEN start_time AND end_time) OR
        (start_time BETWEEN p_start_time AND p_end_time)
    );
    
    SET p_available = (conflict_count = 0);
END//
DELIMITER ;
```

## Backup Strategy

- **Daily backup**: 2:00 AM
- **Weekly full backup**: Sunday 3:00 AM
- **Retention**: 30 days
- **Storage**: Local + Cloud (Google Drive/Dropbox)
