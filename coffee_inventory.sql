-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 05, 2025 at 06:31 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `coffee_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `ActivityLog`
--

CREATE TABLE `ActivityLog` (
  `logID` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Category`
--

CREATE TABLE `Category` (
  `categoryID` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Category`
--

INSERT INTO `Category` (`categoryID`, `name`, `description`, `is_active`) VALUES
(1, 'Coffee', 'Various coffee drinks and beans', 1),
(2, 'Tea', 'Different types of tea', 1),
(3, 'Pastries', 'Bakery items and desserts', 1),
(4, 'Equipment', 'Coffee machines and accessories', 1);

-- --------------------------------------------------------

--
-- Table structure for table `Employee`
--

CREATE TABLE `Employee` (
  `employeeID` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Employee`
--

INSERT INTO `Employee` (`employeeID`, `name`, `role`, `email`, `phone`, `hire_date`, `status`) VALUES
(1, 'Emma Davis', 'Manager', 'emma@coffeeshop.com', NULL, NULL, 'active'),
(2, 'Alex Chen', 'Barista', 'alex@coffeeshop.com', NULL, NULL, 'active'),
(3, 'Lisa Wong', 'Barista', 'lisa@coffeeshop.com', NULL, NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `InventoryItem`
--

CREATE TABLE `InventoryItem` (
  `inventoryID` int(11) NOT NULL,
  `productID` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `InventoryItem`
--

INSERT INTO `InventoryItem` (`inventoryID`, `productID`, `quantity`, `is_active`) VALUES
(1, 1, 98, 1),
(2, 2, 103, 1),
(3, 3, 234, 1),
(4, 4, 0, 1),
(5, 5, 58, 1);

-- --------------------------------------------------------

--
-- Table structure for table `Order`
--

CREATE TABLE `Order` (
  `orderID` int(11) NOT NULL,
  `orderDate` date DEFAULT NULL,
  `customerID` int(11) DEFAULT NULL,
  `employeeID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Order`
--

INSERT INTO `Order` (`orderID`, `orderDate`, `customerID`, `employeeID`) VALUES
(1, '2025-12-05', 1, 2),
(2, '2025-12-05', 2, 3),
(3, '2025-12-04', 3, 2),
(4, '2025-12-03', 1, 3);

-- --------------------------------------------------------

--
-- Table structure for table `OrderItem`
--

CREATE TABLE `OrderItem` (
  `orderItemID` int(11) NOT NULL,
  `orderID` int(11) DEFAULT NULL,
  `productID` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `OrderItem`
--

INSERT INTO `OrderItem` (`orderItemID`, `orderID`, `productID`, `quantity`) VALUES
(1, 1, 1, 2),
(2, 1, 4, 1),
(3, 2, 2, 1),
(4, 2, 5, 2),
(5, 3, 3, 1),
(6, 3, 6, 1),
(7, 4, 1, 3),
(8, 4, 4, 2);

-- --------------------------------------------------------

--
-- Table structure for table `Product`
--

CREATE TABLE `Product` (
  `productID` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `categoryID` int(11) DEFAULT NULL,
  `supplierID` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Product`
--

INSERT INTO `Product` (`productID`, `name`, `description`, `price`, `categoryID`, `supplierID`, `is_deleted`, `is_active`) VALUES
(1, 'Espresso', 'Strong black coffee shot', 3.50, 1, 1, 0, 1),
(2, 'Latte', 'Espresso with steamed milk', 4.50, 1, 1, 0, 1),
(3, 'Cappuccino', 'Espresso with foam and milk', 4.00, 1, 1, 0, 0),
(4, 'Croissant', 'French butter pastry', 3.00, 3, 3, 0, 1),
(5, 'Muffin', 'Blueberry muffin', 2.50, 3, 3, 0, 1),
(6, 'Green Tea', 'Refreshing green tea', 2.50, 2, 1, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `Settings`
--

CREATE TABLE `Settings` (
  `settingID` int(11) NOT NULL,
  `setting_key` varchar(50) DEFAULT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Settings`
--

INSERT INTO `Settings` (`settingID`, `setting_key`, `setting_value`, `description`) VALUES
(1, 'site_name', 'BrewFlow Coffee Shop', 'Website/Business Name'),
(2, 'currency', 'USD', 'Default Currency'),
(3, 'tax_rate', '10', 'Tax Rate Percentage'),
(4, 'low_stock_threshold', '10', 'Low Stock Alert Threshold'),
(5, 'theme', 'dark', 'Default Theme');

-- --------------------------------------------------------

--
-- Table structure for table `Stock`
--

CREATE TABLE `Stock` (
  `stockID` int(11) NOT NULL,
  `productID` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Stock`
--

INSERT INTO `Stock` (`stockID`, `productID`, `quantity`, `last_updated`) VALUES
(1, 1, -2, '2025-12-04 19:10:05'),
(2, 4, -1, '2025-12-04 19:10:05'),
(3, 2, -1, '2025-12-04 19:10:05'),
(4, 5, -2, '2025-12-04 19:10:05'),
(5, 3, -1, '2025-12-04 19:10:05'),
(6, 6, -1, '2025-12-04 19:10:05'),
(7, 1, -3, '2025-12-04 19:10:05'),
(8, 4, -2, '2025-12-04 19:10:05'),
(9, 2, 103, '2025-12-04 19:14:03'),
(10, 3, 234, '2025-12-04 19:14:12'),
(11, 4, 23, '2025-12-04 19:44:01'),
(12, 4, 3, '2025-12-04 19:57:12'),
(13, 4, 0, '2025-12-04 19:58:03');

-- --------------------------------------------------------

--
-- Table structure for table `Supplier`
--

CREATE TABLE `Supplier` (
  `supplierID` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `contactinfo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Supplier`
--

INSERT INTO `Supplier` (`supplierID`, `name`, `contactinfo`, `is_active`) VALUES
(1, 'Coffee Bean Co.', 'contact@coffeebean.com | +1 (555) 123-4567', 1),
(2, 'Milk Suppliers Inc.', 'sales@milksuppliers.com | +1 (555) 987-6543', 1),
(3, 'Bakery Partners', 'orders@bakerypartners.com | +1 (555) 456-7891', 1);

-- --------------------------------------------------------

--
-- Table structure for table `Users`
--

CREATE TABLE `Users` (
  `userID` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Users`
--

INSERT INTO `Users` (`userID`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '5d896cd278363908292d1fde8315c1b8', 'admin', '2025-12-04 19:10:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ActivityLog`
--
ALTER TABLE `ActivityLog`
  ADD PRIMARY KEY (`logID`);

--
-- Indexes for table `Category`
--
ALTER TABLE `Category`
  ADD PRIMARY KEY (`categoryID`);

--
-- Indexes for table `Employee`
--
ALTER TABLE `Employee`
  ADD PRIMARY KEY (`employeeID`);

--
-- Indexes for table `InventoryItem`
--
ALTER TABLE `InventoryItem`
  ADD PRIMARY KEY (`inventoryID`),
  ADD KEY `productID` (`productID`);

--
-- Indexes for table `Order`
--
ALTER TABLE `Order`
  ADD PRIMARY KEY (`orderID`),
  ADD KEY `idx_order_orderdate` (`orderDate`);

--
-- Indexes for table `OrderItem`
--
ALTER TABLE `OrderItem`
  ADD PRIMARY KEY (`orderItemID`),
  ADD KEY `idx_orderitem_orderid` (`orderID`),
  ADD KEY `idx_orderitem_productid` (`productID`);

--
-- Indexes for table `Product`
--
ALTER TABLE `Product`
  ADD PRIMARY KEY (`productID`),
  ADD KEY `categoryID` (`categoryID`),
  ADD KEY `supplierID` (`supplierID`);

--
-- Indexes for table `Settings`
--
ALTER TABLE `Settings`
  ADD PRIMARY KEY (`settingID`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `Stock`
--
ALTER TABLE `Stock`
  ADD PRIMARY KEY (`stockID`),
  ADD KEY `productID` (`productID`);

--
-- Indexes for table `Supplier`
--
ALTER TABLE `Supplier`
  ADD PRIMARY KEY (`supplierID`);

--
-- Indexes for table `Users`
--
ALTER TABLE `Users`
  ADD PRIMARY KEY (`userID`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ActivityLog`
--
ALTER TABLE `ActivityLog`
  MODIFY `logID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Category`
--
ALTER TABLE `Category`
  MODIFY `categoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `Employee`
--
ALTER TABLE `Employee`
  MODIFY `employeeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `InventoryItem`
--
ALTER TABLE `InventoryItem`
  MODIFY `inventoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `Order`
--
ALTER TABLE `Order`
  MODIFY `orderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `OrderItem`
--
ALTER TABLE `OrderItem`
  MODIFY `orderItemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `Product`
--
ALTER TABLE `Product`
  MODIFY `productID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `Settings`
--
ALTER TABLE `Settings`
  MODIFY `settingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `Stock`
--
ALTER TABLE `Stock`
  MODIFY `stockID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `Supplier`
--
ALTER TABLE `Supplier`
  MODIFY `supplierID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `Users`
--
ALTER TABLE `Users`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `InventoryItem`
--
ALTER TABLE `InventoryItem`
  ADD CONSTRAINT `inventoryitem_ibfk_1` FOREIGN KEY (`productID`) REFERENCES `Product` (`productID`);

--
-- Constraints for table `OrderItem`
--
ALTER TABLE `OrderItem`
  ADD CONSTRAINT `orderitem_ibfk_1` FOREIGN KEY (`orderID`) REFERENCES `Order` (`orderID`) ON DELETE CASCADE,
  ADD CONSTRAINT `orderitem_ibfk_2` FOREIGN KEY (`productID`) REFERENCES `Product` (`productID`);

--
-- Constraints for table `Product`
--
ALTER TABLE `Product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`categoryID`) REFERENCES `Category` (`categoryID`),
  ADD CONSTRAINT `product_ibfk_2` FOREIGN KEY (`supplierID`) REFERENCES `Supplier` (`supplierID`);

--
-- Constraints for table `Stock`
--
ALTER TABLE `Stock`
  ADD CONSTRAINT `stock_ibfk_1` FOREIGN KEY (`productID`) REFERENCES `Product` (`productID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
