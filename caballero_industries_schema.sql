CREATE DATABASE IF NOT EXISTS caballero_industries
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE caballero_industries;

CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    age           TINYINT UNSIGNED NOT NULL,
    role          ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    product_code   VARCHAR(20) NOT NULL UNIQUE,
    name           VARCHAR(150) NOT NULL,
    description    TEXT NULL,
    price          DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) NULL,
    quantity       INT UNSIGNED NOT NULL DEFAULT 0,
    image          VARCHAR(255) NOT NULL,
    is_featured    TINYINT(1) NOT NULL DEFAULT 0,
    is_popular     TINYINT(1) NOT NULL DEFAULT 0,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cart_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT UNSIGNED NOT NULL,
    added_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_product (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    recipient_name   VARCHAR(100) NOT NULL,
    contact_number   VARCHAR(30)  NOT NULL,
    delivery_address VARCHAR(255) NOT NULL,
    city             VARCHAR(100) NOT NULL,
    province         VARCHAR(100) NOT NULL,
    postal_code      VARCHAR(20)  NOT NULL,
    total_amount     DECIMAL(10,2) NOT NULL,
    status           VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_items (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    order_id     INT NOT NULL,
    product_id   INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    quantity     INT UNSIGNED NOT NULL,
    unit_price   DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reviews (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id    INT NOT NULL,
    rating     TINYINT UNSIGNED NOT NULL,
    comment    TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_rating_range CHECK (rating BETWEEN 1 AND 5)
);

-- Starter admin. INSERT IGNORE makes the schema safe to run more than once.
-- Email: admin@caballeroindustries.com
-- Password: Admin123!
INSERT IGNORE INTO users (full_name, email, password_hash, age, role) VALUES
('Site Admin', 'admin@caballeroindustries.com',
 '$2y$10$I1UFAHBIsO/IHEsAE.DwB.g2RBkAjBzIV567Xg5av.ukfcHTmivUK', 30, 'admin');

-- Starter products. These image names match the files in /images.
INSERT IGNORE INTO products
(product_code, name, description, price, discount_price, quantity, image, is_featured, is_popular) VALUES
('CI 001', 'TACTICAL JACKET', 'A durable, weatherproofed jacket built with modular attachment points for demanding environments.', 2450.00, NULL, 18, 'images/jacket.png', 1, 0),
('CI 002', 'TACTICAL CAP', 'A structured cap with a reinforced brim, designed for everyday tactical wear.', 950.00, NULL, 40, 'images/hat.png', 1, 0),
('CI 003', 'TACTICAL BAG', 'A modular utility bag with reinforced seams and multiple compartments.', 1650.00, 1350.00, 12, 'images/bag.png', 1, 0),
('CI 004', 'TACTICAL HOODIE', 'A heavyweight hoodie built for layering, with a clean, functional silhouette.', 2150.00, NULL, 25, 'images/hoodie.png', 1, 0);
