-- Database schema for product management
-- Add these tables to your 'laundry' database

-- Products table
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) NOT NULL DEFAULT 0,
  `status` enum('Available','Low Stock','Out of Stock') NOT NULL DEFAULT 'Available',
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--I KNOW THERE ARE OTHERS CREATED ALREADY SO WE WILL BASE IT HERE
--I KNOW THERE ARE OTHERS CREATED ALREADY SO WE WILL BASE IT HERE
--I KNOW THERE ARE OTHERS CREATED ALREADY SO WE WILL BASE IT HERE


-- Stock transactions table (for logging all stock changes)
CREATE TABLE `stock_transactions` (
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `transaction_type` enum('restock','consumption','adjustment') NOT NULL,
  `quantity` int(11) NOT NULL,
  `old_stock` int(11) NOT NULL,
  `new_stock` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`transaction_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `stock_transactions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample data
INSERT INTO `products` (`product_name`, `category`, `unit`, `current_stock`, `reorder_level`, `status`) VALUES
('Laundry Detergent', 'Cleaning Supplies', 'kg', 50, 10, 'Available'),
('Fabric Softener', 'Cleaning Supplies', 'liters', 25, 5, 'Available'),
('Bleach', 'Cleaning Supplies', 'liters', 8, 10, 'Low Stock'),
('Hangers', 'Equipment', 'pieces', 200, 50, 'Available'),
('Plastic Bags', 'Packaging', 'pieces', 0, 100, 'Out of Stock'),
('Starch', 'Cleaning Supplies', 'kg', 15, 5, 'Available'),
('Washing Machine Cleaner', 'Maintenance', 'bottles', 3, 5, 'Low Stock'),
('Dryer Sheets', 'Cleaning Supplies', 'packs', 20, 8, 'Available');

-- Insert sample stock transactions
INSERT INTO `stock_transactions` (`product_id`, `transaction_type`, `quantity`, `old_stock`, `new_stock`, `notes`, `created_by`) VALUES
(1, 'restock', 20, 30, 50, 'Monthly restock from supplier', 'admin'),
(2, 'consumption', 5, 30, 25, 'Used for customer orders', 'staff'),
(3, 'consumption', 2, 10, 8, 'Heavy usage week', 'staff'),
(5, 'consumption', 50, 50, 0, 'Large order depleted stock', 'staff');

-- Create suppliers table (for future use)
CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create feedback table
CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL AUTO_INCREMENT,
  `rating` int(1) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `comments` text NOT NULL,
  `submitted_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`feedback_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create complaints table
CREATE TABLE `complaints` (
  `complaint_id` int(11) NOT NULL AUTO_INCREMENT,
  `description` text NOT NULL,
  `status` enum('pending','resolved','in_progress') NOT NULL DEFAULT 'pending',
  `submitted_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`complaint_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create admin responses table
CREATE TABLE `admin_responses` (
  `response_id` int(11) NOT NULL AUTO_INCREMENT,
  `complaint_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `responded_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`response_id`),
  KEY `complaint_id` (`complaint_id`),
  CONSTRAINT `admin_responses_ibfk_1` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;