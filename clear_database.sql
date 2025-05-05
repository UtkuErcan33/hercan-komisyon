-- Önce foreign key kontrollerini devre dışı bırak
SET FOREIGN_KEY_CHECKS = 0;

-- Tabloları temizle
TRUNCATE TABLE order_items;
TRUNCATE TABLE orders;
TRUNCATE TABLE cart;
TRUNCATE TABLE products;
TRUNCATE TABLE daily_reports;
TRUNCATE TABLE users;

-- Foreign key kontrollerini tekrar etkinleştir
SET FOREIGN_KEY_CHECKS = 1;

-- Örnek admin kullanıcısını tekrar ekle
INSERT INTO users (display_name, email, password, user_type) VALUES 
('Admin', 'admin@hercankomisyon.com', '123456', 'admin');

-- Örnek ürünleri tekrar ekle
INSERT INTO products (name, description, price, weight, image_url) VALUES 
('Domates', 'Taze ve kaliteli domates', 15.90, 1.00, 'https://images.unsplash.com/photo-1546470427-1ec0a9b0115d?w=500&q=80'),
('Salatalık', 'Taze ve kaliteli salatalık', 12.90, 0.50, 'https://images.unsplash.com/photo-1589621316382-008455b857cd?w=500&q=80'),
('Biber', 'Taze ve kaliteli biber', 18.90, 0.25, 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=500&q=80'),
('Patlıcan', 'Taze ve kaliteli patlıcan', 16.90, 0.75, 'https://images.unsplash.com/photo-1528826007177-f38517ce0a30?w=500&q=80'); 