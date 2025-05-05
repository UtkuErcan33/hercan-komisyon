-- Siparişler tablosuna ödeme durumu alanı ekle
ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status ENUM('paid', 'unpaid') NOT NULL DEFAULT 'unpaid';

-- Günlük rapor tablosuna ödeme tutarları alanlarını ekle
ALTER TABLE daily_reports 
ADD COLUMN IF NOT EXISTS total_paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
ADD COLUMN IF NOT EXISTS total_unpaid_amount DECIMAL(10,2) NOT NULL DEFAULT 0;

-- Direct Sales tablosunu oluştur
CREATE TABLE IF NOT EXISTS direct_sales (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Siparişler için cascade delete ekleyelim
ALTER TABLE order_items DROP FOREIGN KEY IF EXISTS order_items_ibfk_2;

ALTER TABLE order_items
ADD CONSTRAINT order_items_ibfk_2
FOREIGN KEY (product_id) REFERENCES products(id)
ON DELETE CASCADE; 