-- =============================================
-- FASHION STORE DATABASE
-- =============================================

CREATE DATABASE IF NOT EXISTS fashion_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fashion_store;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255),
    sizes VARCHAR(100) COMMENT 'Comma-separated: S,M,L,XL',
    colors VARCHAR(200) COMMENT 'Comma-separated: Black,White,Red',
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_name VARCHAR(100) NOT NULL,
    shipping_phone VARCHAR(20) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(100) NOT NULL,
    payment_method ENUM('transfer','cod') DEFAULT 'transfer',
    payment_status ENUM('unpaid','paid') DEFAULT 'unpaid',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    product_name VARCHAR(200) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    size VARCHAR(10),
    color VARCHAR(50),
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- =============================================
-- SEED DATA
-- =============================================

-- Admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@fashionstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Categories
INSERT INTO categories (name, slug, description) VALUES
('Atasan', 'atasan', 'Kemeja, kaos, blouse, dan berbagai pilihan atasan'),
('Bawahan', 'bawahan', 'Celana, rok, dan berbagai pilihan bawahan'),
('Dress', 'dress', 'Dress kasual, formal, dan semi-formal'),
('Outerwear', 'outerwear', 'Jaket, cardigan, blazer, dan outwear lainnya'),
('Aksesoris', 'aksesoris', 'Tas, topi, dan aksesoris fashion');

-- Products
INSERT INTO products (category_id, name, slug, description, price, stock, image, sizes, colors, is_featured) VALUES
(1, 'Kaos Basic Polos', 'kaos-basic-polos', 'Kaos basic berkualitas tinggi, bahan katun combed 30s yang lembut dan nyaman dipakai sehari-hari.', 89000, 50, 'kaos-basic.jpg', 'S,M,L,XL,XXL', 'Putih,Hitam,Navy,Abu-abu', 1),
(1, 'Kemeja Flanel Kotak', 'kemeja-flanel-kotak', 'Kemeja flanel motif kotak yang trendi, cocok untuk casual maupun semi-formal.', 159000, 30, 'kemeja-flanel.jpg', 'S,M,L,XL', 'Merah,Biru,Hijau', 1),
(1, 'Blouse Chiffon Wanita', 'blouse-chiffon', 'Blouse chiffon elegan dengan detail renda, perfect untuk tampilan feminin.', 135000, 25, 'blouse-chiffon.jpg', 'S,M,L,XL', 'Putih,Pink,Cream', 0),
(2, 'Celana Jeans Slim Fit', 'celana-jeans-slim', 'Celana jeans slim fit premium, bahan denim berkualitas dengan potongan modern.', 299000, 40, 'celana-jeans.jpg', '28,29,30,31,32,33,34', 'Biru Muda,Biru Tua,Hitam', 1),
(2, 'Rok Mini Plisket', 'rok-mini-plisket', 'Rok mini plisket yang stylish, cocok untuk OOTD kasual maupun semi-formal.', 129000, 35, 'rok-plisket.jpg', 'S,M,L,XL', 'Hitam,Putih,Marun', 0),
(3, 'Dress Casual Midi', 'dress-casual-midi', 'Dress midi kasual dengan motif bunga yang cantik, bahan rayon yang sejuk.', 219000, 20, 'dress-midi.jpg', 'S,M,L,XL', 'Floral Blue,Floral Pink', 1),
(4, 'Jaket Denim Oversize', 'jaket-denim-oversize', 'Jaket denim oversize yang timeless, bisa dipadupadankan dengan berbagai outfit.', 349000, 15, 'jaket-denim.jpg', 'S,M,L,XL,XXL', 'Biru Muda,Biru Tua', 1),
(4, 'Cardigan Rajut', 'cardigan-rajut', 'Cardigan rajut hangat dan stylish, perfect untuk musim hujan.', 189000, 28, 'cardigan-rajut.jpg', 'S,M,L,XL', 'Cream,Brown,Grey', 0),
(5, 'Tote Bag Canvas', 'tote-bag-canvas', 'Tote bag canvas premium yang spacious dan stylish, cocok untuk daily use.', 149000, 60, 'tote-bag.jpg', '', 'Natural,Hitam,Navy', 0),
(5, 'Topi Baseball Cap', 'topi-baseball', 'Topi baseball cap unisex yang casual dan sporty.', 79000, 45, 'topi-baseball.jpg', 'Free Size', 'Hitam,Putih,Navy,Merah', 0);
