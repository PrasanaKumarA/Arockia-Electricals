-- ============================================================
-- Arockia Electricals - Inventory & Billing Database
-- Created: 2026-03-23
-- ============================================================

CREATE DATABASE IF NOT EXISTS `arockia_electricals` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `arockia_electricals`;

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','staff') DEFAULT 'admin',
  `last_login` DATETIME DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Default admin: password = admin123
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES
('Administrator', 'admin@arockia.com', '$2y$10$8cyrTGh5lbUP2LVRD5enA.KKklvMTe9KE99E7.XaHp.rIFZd7eBem', 'admin', 1);

-- ============================================================
-- CATEGORIES TABLE
-- ============================================================
CREATE TABLE `categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `categories` (`name`) VALUES ('UPS'), ('Battery'), ('Solar'), ('Appliance'), ('Wiring'), ('Switchgear');

-- ============================================================
-- SUPPLIERS TABLE
-- ============================================================
CREATE TABLE `suppliers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `company` VARCHAR(200) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `gstin` VARCHAR(20) DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `suppliers` (`name`, `company`, `phone`, `email`, `address`) VALUES
('Ramesh Kumar', 'Kumar Electricals', '9876543210', 'ramesh@kumar.com', '12 Main Road, Chennai'),
('Priya Traders', 'Priya Wholesale', '9865432100', 'priya@traders.com', '45 Anna Nagar, Chennai'),
('Suresh Enterprises', 'Suresh Pvt Ltd', '9843210987', 'suresh@enterprise.com', '78 T Nagar, Chennai');

-- ============================================================
-- CUSTOMERS TABLE
-- ============================================================
CREATE TABLE `customers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `gstin` VARCHAR(20) DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `customers` (`name`, `phone`, `email`, `address`) VALUES
('Walk-in Customer', '0000000000', '', 'Local'),
('John Prakash', '9876512345', 'john@email.com', '23 Gandhi St, Coimbatore'),
('Anitha Rajan', '9865498765', 'anitha@email.com', '56 Nehru Nagar, Salem'),
('Murugan Shop', '9843267890', 'murugan@shop.com', '9 Market Road, Erode');

-- ============================================================
-- PRODUCTS TABLE
-- ============================================================
CREATE TABLE `products` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_name` VARCHAR(200) NOT NULL,
  `category_id` INT(11) DEFAULT NULL,
  `brand` VARCHAR(100) DEFAULT NULL,
  `sku` VARCHAR(100) DEFAULT NULL UNIQUE,
  `purchase_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `selling_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock_quantity` INT(11) NOT NULL DEFAULT 0,
  `minimum_stock` INT(11) NOT NULL DEFAULT 5,
  `unit` VARCHAR(20) DEFAULT 'pcs',
  `description` TEXT DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_product_category` (`category_id`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO `products` (`product_name`, `category_id`, `brand`, `sku`, `purchase_price`, `selling_price`, `stock_quantity`, `minimum_stock`) VALUES
('APC 600VA UPS', 1, 'APC', 'UPS-APC-600', 2800.00, 3500.00, 15, 5),
('Luminous 1KVA UPS', 1, 'Luminous', 'UPS-LUM-1K', 4200.00, 5200.00, 8, 3),
('Exide 12V 7Ah Battery', 2, 'Exide', 'BAT-EXD-7A', 850.00, 1100.00, 20, 10),
('Amaron 12V 26Ah Battery', 2, 'Amaron', 'BAT-AMR-26A', 3200.00, 4000.00, 6, 3),
('Vikram Solar 100W Panel', 3, 'Vikram Solar', 'SOL-VIK-100W', 4500.00, 5800.00, 4, 2),
('Syska LED 9W Bulb', 4, 'Syska', 'LED-SSK-9W', 65.00, 95.00, 50, 20),
('Havells Ceiling Fan', 4, 'Havells', 'FAN-HAV-CL', 1800.00, 2200.00, 3, 2),
('Polycab 1.5mm Wire 90m', 5, 'Polycab', 'WIR-POL-15', 1400.00, 1750.00, 2, 3),
('L&T MCB 32A', 6, 'L&T', 'MCB-LT-32A', 220.00, 320.00, 30, 10),
('Schneider Switch 6A', 6, 'Schneider', 'SWT-SCH-6A', 45.00, 75.00, 60, 20);

-- ============================================================
-- PURCHASES TABLE
-- ============================================================
CREATE TABLE `purchases` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` VARCHAR(50) NOT NULL,
  `supplier_id` INT(11) DEFAULT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(10,2) DEFAULT 0.00,
  `payment_status` ENUM('paid','partial','pending') DEFAULT 'pending',
  `payment_method` VARCHAR(50) DEFAULT 'cash',
  `notes` TEXT DEFAULT NULL,
  `purchase_date` DATE NOT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_purchase_supplier` (`supplier_id`),
  CONSTRAINT `fk_purchase_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- PURCHASE ITEMS TABLE
-- ============================================================
CREATE TABLE `purchase_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` INT(11) NOT NULL,
  `product_id` INT(11) DEFAULT NULL,
  `quantity` INT(11) NOT NULL DEFAULT 1,
  `purchase_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `fk_pitem_purchase` (`purchase_id`),
  KEY `fk_pitem_product` (`product_id`),
  CONSTRAINT `fk_pitem_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pitem_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Sample purchase
INSERT INTO `purchases` (`invoice_number`, `supplier_id`, `total_amount`, `paid_amount`, `payment_status`, `purchase_date`) VALUES
('PUR-2026-001', 1, 14000.00, 14000.00, 'paid', '2026-03-20');
INSERT INTO `purchase_items` (`purchase_id`, `product_id`, `quantity`, `purchase_price`, `total_price`) VALUES
(1, 1, 3, 2800.00, 8400.00),
(1, 3, 5, 850.00, 4250.00),
(1, 9, 5, 220.00, 1100.00);

-- ============================================================
-- SALES TABLE
-- ============================================================
CREATE TABLE `sales` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
  `customer_id` INT(11) DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) DEFAULT 0.00,
  `gst_rate` DECIMAL(5,2) DEFAULT 0.00,
  `gst_amount` DECIMAL(10,2) DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(10,2) DEFAULT 0.00,
  `payment_status` ENUM('paid','partial','pending') DEFAULT 'paid',
  `payment_method` VARCHAR(50) DEFAULT 'cash',
  `notes` TEXT DEFAULT NULL,
  `sale_date` DATE NOT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_sale_customer` (`customer_id`),
  CONSTRAINT `fk_sale_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- SALE ITEMS TABLE
-- ============================================================
CREATE TABLE `sale_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `sale_id` INT(11) NOT NULL,
  `product_id` INT(11) DEFAULT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `quantity` INT(11) NOT NULL DEFAULT 1,
  `selling_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `purchase_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `fk_sitem_sale` (`sale_id`),
  KEY `fk_sitem_product` (`product_id`),
  CONSTRAINT `fk_sitem_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sitem_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Sample sales
INSERT INTO `sales` (`invoice_number`, `customer_id`, `subtotal`, `gst_rate`, `gst_amount`, `total_amount`, `paid_amount`, `payment_status`, `payment_method`, `sale_date`) VALUES
('INV-2026-0001', 2, 4700.00, 18, 846.00, 5546.00, 5546.00, 'paid', 'cash', '2026-03-21'),
('INV-2026-0002', 3, 2200.00, 18, 396.00, 2596.00, 2596.00, 'paid', 'upi', '2026-03-22'),
('INV-2026-0003', 1, 1250.00, 0, 0.00, 1250.00, 1250.00, 'paid', 'cash', '2026-03-23');

INSERT INTO `sale_items` (`sale_id`, `product_id`, `product_name`, `quantity`, `selling_price`, `purchase_price`, `total_price`) VALUES
(1, 1, 'APC 600VA UPS', 1, 3500.00, 2800.00, 3500.00),
(1, 3, 'Exide 12V 7Ah Battery', 1, 1100.00, 850.00, 1100.00),
(1, 6, 'Syska LED 9W Bulb', 1, 95.00, 65.00, 95.00),
(2, 7, 'Havells Ceiling Fan', 1, 2200.00, 1800.00, 2200.00),
(3, 6, 'Syska LED 9W Bulb', 5, 95.00, 65.00, 475.00),
(3, 9, 'L&T MCB 32A', 2, 320.00, 220.00, 640.00),
(3, 10, 'Schneider Switch 6A', 2, 75.00, 45.00, 150.00);
