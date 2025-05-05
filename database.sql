-- Veritabanını oluştur
CREATE DATABASE IF NOT EXISTS hercan_komisyon;
USE hercan_komisyon;

-- Kullanıcılar tablosunu oluştur
CREATE TABLE users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    display_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'customer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Örnek admin kullanıcısı ekle
INSERT INTO users (display_name, email, password, user_type) VALUES 
('Admin', 'admin@hercankomisyon.com', '123456', 'admin');

-- Örnek müşteri kullanıcısı ekle
INSERT INTO users (display_name, email, password, user_type) VALUES 
('Test Müşteri', 'musteri@gmail.com', '123456', 'customer');

-- Ürünler tablosunu oluştur
CREATE TABLE products (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    weight DECIMAL(10,2) DEFAULT 1.00,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sepet tablosunu oluştur
CREATE TABLE cart (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Siparişler tablosunu oluştur
CREATE TABLE orders (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'shipping', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    payment_status ENUM('paid', 'unpaid') NOT NULL DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Sipariş detayları tablosunu oluştur
CREATE TABLE order_items (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total_weight DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Günlük rapor tablosunu oluştur
CREATE TABLE daily_reports (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    report_date DATE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    total_paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_unpaid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_weight DECIMAL(10,2) NOT NULL,
    total_orders INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Örnek ürünler ekle
INSERT INTO products (name, description, price, weight, image_url) VALUES 
('Domates', 'Taze ve kaliteli domates', 15.90, 1.00, 'https://images.unsplash.com/photo-1546470427-1ec0a9b0115d?w=500&q=80'),
('Salatalık', 'Taze ve kaliteli salatalık', 12.90, 0.50, 'https://images.unsplash.com/photo-1589621316382-008455b857cd?w=500&q=80'),
('Biber', 'Taze ve kaliteli biber', 18.90, 0.25, 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=500&q=80'),
('Patlıcan', 'Taze ve kaliteli patlıcan', 16.90, 0.75, 'https://images.unsplash.com/photo-1528826007177-f38517ce0a30?w=500&q=80'); 