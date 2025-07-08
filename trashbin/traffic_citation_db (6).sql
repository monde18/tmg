-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 30, 2025 at 08:51 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `traffic_citation_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `citations`
--

CREATE TABLE `citations` (
  `citation_id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `apprehension_datetime` datetime DEFAULT NULL,
  `place_of_apprehension` varchar(255) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `payment_status` enum('Paid','Unpaid') DEFAULT 'Unpaid',
  `payment_date` datetime DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `citations`
--

INSERT INTO `citations` (`citation_id`, `ticket_number`, `driver_id`, `vehicle_id`, `apprehension_datetime`, `place_of_apprehension`, `is_archived`, `payment_status`, `payment_date`, `payment_amount`) VALUES
(35, '06101', 27, 35, '2025-05-30 11:46:00', 'JJGH', 0, 'Paid', '2025-05-30 13:07:21', 1000.00),
(36, '06102', 27, 36, '2025-05-30 03:48:00', '', 0, 'Paid', '2025-05-30 14:33:26', 1000.00),
(37, '06103', 28, 37, '2025-05-30 11:54:00', 'JJGH', 0, 'Paid', '2025-05-30 12:55:21', 500.00),
(38, '06104', 29, 38, '2025-05-30 14:10:00', 'JJGH', 0, 'Unpaid', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `driver_id` int(11) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_initial` varchar(5) DEFAULT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `zone` varchar(50) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `municipality` varchar(100) DEFAULT 'Baggao',
  `province` varchar(100) DEFAULT 'Cagayan',
  `license_number` varchar(20) DEFAULT NULL,
  `license_type` enum('Non-Professional','Professional') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`driver_id`, `last_name`, `first_name`, `middle_initial`, `suffix`, `zone`, `barangay`, `municipality`, `province`, `license_number`, `license_type`) VALUES
(22, 'AMBO', 'LIGAN', '', '', '5', 'Bitag Grande', 'Baggao', 'Cagayan', '000549', 'Non-Professional'),
(23, 'AMBO', 'LIGAN', '', '', '5', 'Agaman Norte', 'Baggao', 'Cagayan', '000549', 'Non-Professional'),
(24, 'AMBO', 'LIGAN', '', '', '5', 'Barsat East', 'Baggao', 'Cagayan', '000549', 'Non-Professional'),
(25, 'AMBO', 'LIGAN', '', '', '5', 'Barsat West', 'Baggao', 'Cagayan', '000549', 'Non-Professional'),
(26, 'Rosete', 'Richmond', '', '', '5', 'Adag', 'Baggao', 'Cagayan', '000549', 'Non-Professional'),
(27, 'Rosete', 'Richmond', '', '', '5', 'Bitag Grande', 'Baggao', 'Cagayan', '000549', 'Non-Professional'),
(28, 'Rosete', 'Richmond', '', '', '5', 'Bitag Grande', 'Baggao', 'Cagayan', '000549', 'Non-Professional'),
(29, 'Rosete', 'Richmond', '', '', '5', 'Bungel', 'Baggao', 'Cagayan', '000549', 'Non-Professional');

-- --------------------------------------------------------

--
-- Table structure for table `remarks`
--

CREATE TABLE `remarks` (
  `remark_id` int(11) NOT NULL,
  `citation_id` int(11) DEFAULT NULL,
  `remark_text` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `remarks`
--

INSERT INTO `remarks` (`remark_id`, `citation_id`, `remark_text`) VALUES
(15, 35, 'EMERGENCY GAMIN KANU');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `vehicle_id` int(11) NOT NULL,
  `plate_mv_engine_chassis_no` varchar(50) DEFAULT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `vehicle_description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`vehicle_id`, `plate_mv_engine_chassis_no`, `vehicle_type`, `vehicle_description`) VALUES
(29, '4564564', 'Motorcycle', 'IHKJ'),
(30, '4564564', 'Tricycle', 'IHKJ'),
(31, '4564564', 'Tricycle', 'IHKJ'),
(32, '4564564', 'Motorcycle', 'IHKJ'),
(33, '4564564', 'Van', 'IHKJ'),
(34, '', 'Unknown', ''),
(35, '4564564', 'Truck', 'IHKJ'),
(36, '4564564', 'Motorcycle', ''),
(37, '4564564', 'Motorcycle', 'IHKJ'),
(38, '4564564', 'Tricycle', 'IHKJ');

-- --------------------------------------------------------

--
-- Table structure for table `violations`
--

CREATE TABLE `violations` (
  `violation_id` int(11) NOT NULL,
  `citation_id` int(11) DEFAULT NULL,
  `violation_type` varchar(100) DEFAULT NULL,
  `offense_count` int(11) DEFAULT 1,
  `driver_id` int(11) NOT NULL,
  `fine_amount` decimal(10,2) DEFAULT 500.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `violations`
--

INSERT INTO `violations` (`violation_id`, `citation_id`, `violation_type`, `offense_count`, `driver_id`, `fine_amount`) VALUES
(62, 35, 'Illegal Parking', 1, 27, 500.00),
(65, 36, 'No Helmet (Driver)', 1, 27, 500.00),
(68, 37, 'No Helmet (Driver)', 1, 28, 500.00),
(69, 37, 'Defective Accessories', 1, 28, 500.00),
(70, 38, 'No Helmet (Driver)', 1, 29, 500.00),
(71, 38, 'No Helmet (Backrider)', 1, 29, 500.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `citations`
--
ALTER TABLE `citations`
  ADD PRIMARY KEY (`citation_id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `idx_ticket_number` (`ticket_number`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`driver_id`),
  ADD KEY `idx_name` (`last_name`,`first_name`);

--
-- Indexes for table `remarks`
--
ALTER TABLE `remarks`
  ADD PRIMARY KEY (`remark_id`),
  ADD KEY `citation_id` (`citation_id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`vehicle_id`);

--
-- Indexes for table `violations`
--
ALTER TABLE `violations`
  ADD PRIMARY KEY (`violation_id`),
  ADD KEY `citation_id` (`citation_id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `citations`
--
ALTER TABLE `citations`
  MODIFY `citation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `driver_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `remarks`
--
ALTER TABLE `remarks`
  MODIFY `remark_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `vehicle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `violations`
--
ALTER TABLE `violations`
  MODIFY `violation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `citations`
--
ALTER TABLE `citations`
  ADD CONSTRAINT `citations_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`),
  ADD CONSTRAINT `citations_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`);

--
-- Constraints for table `remarks`
--
ALTER TABLE `remarks`
  ADD CONSTRAINT `remarks_ibfk_1` FOREIGN KEY (`citation_id`) REFERENCES `citations` (`citation_id`);

--
-- Constraints for table `violations`
--
ALTER TABLE `violations`
  ADD CONSTRAINT `violations_ibfk_1` FOREIGN KEY (`citation_id`) REFERENCES `citations` (`citation_id`),
  ADD CONSTRAINT `violations_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
