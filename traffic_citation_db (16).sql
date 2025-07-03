-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 25, 2025 at 04:47 AM
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
  `payment_status` enum('Unpaid','Paid') NOT NULL DEFAULT 'Unpaid',
  `payment_date` datetime DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL
) ;

--
-- Dumping data for table `citations`
--

INSERT INTO `citations` (`citation_id`, `ticket_number`, `driver_id`, `vehicle_id`, `apprehension_datetime`, `place_of_apprehension`, `is_archived`, `payment_status`, `payment_date`, `payment_amount`) VALUES
(37254, '06101', 38627, 36260, '2025-06-25 08:06:00', 'TALLANG', 0, 'Paid', '2025-06-25 09:40:11', 3000.00),
(37255, '06102', 38628, 36261, '2025-06-25 08:30:00', 'TALLANG', 0, 'Unpaid', NULL, 0.00),
(37256, '06103', 38629, 36262, '2025-06-25 00:46:00', 'JJGH', 0, 'Unpaid', NULL, 0.00),
(37257, '06104', 38630, 36263, '2025-06-25 01:00:00', 'JJGH', 0, 'Paid', '2025-06-25 09:39:02', 200.00);

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
(38627, 'AMBO', 'LIGAN', 'A', NULL, '5', 'Bitag Grande', 'Baggao', 'Cagayan', NULL, NULL),
(38628, 'AMBO', 'LIGAN', 'A', NULL, '5', 'ALCALA', NULL, NULL, NULL, NULL),
(38629, 'AMBO', 'LIGAN', 'A', NULL, '5', 'Adag', 'Baggao', 'Cagayan', NULL, NULL),
(38630, 'AMBO', 'LIGAN', 'A', NULL, '5', 'Adag', 'Baggao', 'Cagayan', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `remarks`
--

CREATE TABLE `remarks` (
  `remark_id` int(11) NOT NULL,
  `citation_id` int(11) DEFAULT NULL,
  `remark_text` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(36260, '4564564', 'Motorcycle', 'IHKJ'),
(36261, '4564564', 'Motorcycle', 'IHKJ'),
(36262, '4564564', 'Motorcycle', 'IHKJ'),
(36263, '4564564', 'Motorcycle', 'IHKJ');

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
(39496, 37254, 'NO HELMET (Backrider)', 1, 38627, 150.00),
(39497, 37254, 'NOISY MUFFLER (98db above)', 1, 38627, 2500.00),
(39498, 37255, 'NO HELMET (Backrider)', 1, 38628, 150.00),
(39499, 37256, 'NO HELMET (Backrider)', 1, 38629, 150.00),
(39500, 37257, 'NO HELMET (Backrider)', 2, 38630, 150.00);

-- --------------------------------------------------------

--
-- Table structure for table `violation_types`
--

CREATE TABLE `violation_types` (
  `violation_type_id` int(11) NOT NULL,
  `violation_type` varchar(100) NOT NULL,
  `fine_amount_1` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fine_amount_2` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fine_amount_3` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `violation_types`
--

INSERT INTO `violation_types` (`violation_type_id`, `violation_type`, `fine_amount_1`, `fine_amount_2`, `fine_amount_3`) VALUES
(2, 'NO HELMET (Driver)', 150.00, 150.00, 150.00),
(3, 'NO HELMET (Backrider)', 150.00, 150.00, 150.00),
(4, 'NO DRIVER’S LICENSE / MINOR', 500.00, 500.00, 500.00),
(5, 'NO / EXPIRED VEHICLE REGISTRATION', 2500.00, 2500.00, 2500.00),
(6, 'NO / DEFECTIVE PARTS & ACCESSORIES', 500.00, 500.00, 500.00),
(7, 'RECKLESS / ARROGANT DRIVING', 500.00, 750.00, 1000.00),
(8, 'DISREGARDING TRAFFIC SIGN', 150.00, 150.00, 150.00),
(9, 'ILLEGAL MODIFICATION', 500.00, 500.00, 500.00),
(10, 'PASSENGER ON TOP OF THE VEHICLE', 150.00, 150.00, 150.00),
(11, 'NOISY MUFFLER (98db above)', 2500.00, 2500.00, 2500.00),
(12, 'NO MUFFLER ATTACHED', 2500.00, 2500.00, 2500.00),
(13, 'ILLEGAL PARKING', 200.00, 500.00, 2500.00),
(14, 'ROAD OBSTRUCTION', 200.00, 500.00, 2500.00),
(15, 'BLOCKING PEDESTRIAN LANE', 200.00, 500.00, 2500.00),
(16, 'LOADING/UNLOADING IN PROHIBITED ZONE', 200.00, 500.00, 2500.00),
(17, 'DOUBLE PARKING', 200.00, 500.00, 2500.00),
(18, 'DRUNK DRIVING', 500.00, 1000.00, 1500.00),
(19, 'COLORUM OPERATION', 2500.00, 3000.00, 3000.00),
(20, 'NO TRASHBIN', 1000.00, 2000.00, 2500.00),
(21, 'DRIVING IN SHORT / SANDO', 200.00, 500.00, 1000.00),
(22, 'OVERLOADED PASSENGER', 500.00, 750.00, 1000.00),
(23, 'OVER CHARGING / UNDER CHARGING', 500.00, 750.00, 1000.00),
(24, 'REFUSAL TO CONVEY PASSENGER/S', 500.00, 750.00, 1000.00),
(25, 'DRAG RACING', 1000.00, 1500.00, 2500.00),
(26, 'NO ENHANCED OPLAN VISA STICKER', 300.00, 300.00, 300.00),
(27, 'FAILURE TO PRESENT E-OV MATCH CARD', 200.00, 200.00, 200.00);

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
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `idx_citation_violation_type` (`citation_id`,`violation_type`);

--
-- Indexes for table `violation_types`
--
ALTER TABLE `violation_types`
  ADD PRIMARY KEY (`violation_type_id`),
  ADD UNIQUE KEY `violation_type` (`violation_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `citations`
--
ALTER TABLE `citations`
  MODIFY `citation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `driver_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38631;

--
-- AUTO_INCREMENT for table `remarks`
--
ALTER TABLE `remarks`
  MODIFY `remark_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9486;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `vehicle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36264;

--
-- AUTO_INCREMENT for table `violations`
--
ALTER TABLE `violations`
  MODIFY `violation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39501;

--
-- AUTO_INCREMENT for table `violation_types`
--
ALTER TABLE `violation_types`
  MODIFY `violation_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

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
