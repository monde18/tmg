-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 04, 2025 at 04:51 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
  `payment_status` enum('Unpaid','Paid','Partially Paid') NOT NULL DEFAULT 'Unpaid',
  `payment_date` datetime DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `reference_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `citations`
--

INSERT INTO `citations` (`citation_id`, `ticket_number`, `driver_id`, `vehicle_id`, `apprehension_datetime`, `place_of_apprehension`, `is_archived`, `payment_status`, `payment_date`, `payment_amount`, `reference_number`) VALUES
(372794, '06101', 38657, 36292, '2025-07-03 15:34:00', 'SAN JOSE', 0, 'Paid', '2025-07-03 15:36:02', 2700.00, 'PAY-20250704-0225-37'),
(372795, '06102', 38658, 36293, '2025-07-03 16:03:00', 'SAN JOSE', 0, 'Paid', '2025-07-03 16:04:45', 400.00, 'PAY-20250704-0225-09'),
(372796, '06103', 38659, 36294, '2025-07-03 16:19:00', 'SAN JOSE', 0, 'Paid', '2025-07-03 16:19:46', 300.00, 'PAY-20250704-0225-75'),
(372797, '06104', 38660, 36295, '2025-07-03 16:21:00', 'SAN JOSE', 0, 'Paid', '2025-07-04 10:40:51', 650.00, 'PAY-20250705-0225-11'),
(372798, '06105', 38661, 36296, '2025-07-03 16:32:00', 'SAN JOSE', 0, 'Paid', '2025-07-03 17:39:08', 1300.00, 'PAY-20250704-0225-79'),
(372799, '06106', 38661, 36297, '2025-07-03 16:57:00', 'SAN JOSE', 0, 'Paid', '2025-07-03 16:58:27', 300.00, 'PAY-20250704-0225-82'),
(372800, '06107', 38662, 36298, '2025-07-04 10:39:00', 'SAN JOSE', 0, 'Paid', '2025-07-04 10:41:04', 1000.00, 'PAY-20250705-0225-35');

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
(38657, 'Rrrrr', 'ZALDY', 'R', NULL, '1', 'Mission', 'Baggao', 'Cagayan', NULL, NULL),
(38658, 'Rrrrr', 'Rogelio', 'G', NULL, '1', 'Canagatan', 'Baggao', 'Cagayan', NULL, NULL),
(38659, 'Rrrrr', 'Rogelio', 'G', NULL, '1', 'Asassi', 'Baggao', 'Cagayan', NULL, NULL),
(38660, 'Rrrrr', 'Rogelio', 'G', NULL, '1', 'Canagatan', 'Baggao', 'Cagayan', NULL, NULL),
(38661, 'Rrrrr', 'Rogelio', 'G', NULL, '1', 'Canagatan', '', '', NULL, 'Non-Professional'),
(38662, 'Rrrrr', 'Rogelio', 'G', NULL, '1', 'Canagatan', 'Baggao', 'Cagayan', NULL, NULL);

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
(9486, 372799, 'GGG');

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
(36292, '5JK567', 'Motorcycle', 'RIDER 150'),
(36293, '5JK567', 'Tricycle', 'RIDER 150'),
(36294, '5JK567', 'Motorcycle', 'RIDER 150'),
(36295, '5JK567', 'Motorcycle', 'RIDER 150'),
(36296, '5JK567', 'Motorcycle', 'RIDER 150'),
(36297, '5JK567', 'Motorcycle', 'RIDER 150'),
(36298, '5JK567', 'Motorcycle', 'RIDER 150');

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
  `payment_status` enum('Unpaid','Paid') DEFAULT 'Unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `violations`
--

INSERT INTO `violations` (`violation_id`, `citation_id`, `violation_type`, `offense_count`, `driver_id`, `payment_status`) VALUES
(34, 372794, 'FAILURE TO PRESENT E-OV MATCH CARD', 1, 38657, 'Paid'),
(35, 372794, 'NO / EXPIRED VEHICLE REGISTRATION', 1, 38657, 'Paid'),
(36, 372795, 'DOUBLE PARKING', 1, 38658, 'Paid'),
(37, 372795, 'LOADING/UNLOADING IN PROHIBITED ZONE', 1, 38658, 'Paid'),
(38, 372796, 'NO HELMET (BACKRIDER)', 1, 38659, 'Paid'),
(39, 372796, 'NO HELMET (DRIVER)', 1, 38659, 'Paid'),
(40, 372797, 'NO HELMET (BACKRIDER)', 1, 38660, 'Paid'),
(41, 372797, 'NO HELMET (DRIVER)', 1, 38660, 'Paid'),
(46, 372799, 'NO HELMET (BACKRIDER)', 2, 38661, 'Unpaid'),
(47, 372799, 'NO HELMET (DRIVER)', 2, 38661, 'Unpaid'),
(48, 372798, 'NO HELMET (BACKRIDER)', 2, 38661, 'Paid'),
(49, 372798, 'NO HELMET (DRIVER)', 2, 38661, 'Paid'),
(50, 372800, 'DRAG RACING', 1, 38662, 'Paid');

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
(2, 'NO HELMET (DRIVER)', 150.00, 150.00, 150.00),
(3, 'NO HELMET (BACKRIDER)', 150.00, 150.00, 150.00),
(4, 'NO DRIVER’S LICENSE / MINOR', 500.00, 500.00, 500.00),
(5, 'NO / EXPIRED VEHICLE REGISTRATION', 2500.00, 2500.00, 2500.00),
(6, 'NO / DEFECTIVE PARTS & ACCESSORIES', 500.00, 500.00, 500.00),
(7, 'RECKLESS / ARROGANT DRIVING', 500.00, 750.00, 1000.00),
(8, 'DISREGARDING TRAFFIC SIGN', 150.00, 150.00, 150.00),
(9, 'ILLEGAL MODIFICATION', 500.00, 500.00, 500.00),
(10, 'PASSENGER ON TOP OF THE VEHICLE', 150.00, 150.00, 150.00),
(11, 'NOISY MUFFLER (98DB ABOVE)', 2500.00, 2500.00, 2500.00),
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
  ADD UNIQUE KEY `reference_number` (`reference_number`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `idx_ticket_number` (`ticket_number`),
  ADD KEY `idx_apprehension_datetime` (`apprehension_datetime`),
  ADD KEY `idx_citations_datetime` (`apprehension_datetime`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_citations_driver` (`driver_id`);

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
  ADD KEY `idx_citation_violation_type` (`citation_id`,`violation_type`),
  ADD KEY `idx_violations_driver` (`driver_id`),
  ADD KEY `idx_violations_type` (`violation_type`),
  ADD KEY `idx_violations_citation` (`citation_id`);

--
-- Indexes for table `violation_types`
--
ALTER TABLE `violation_types`
  ADD PRIMARY KEY (`violation_type_id`),
  ADD UNIQUE KEY `violation_type` (`violation_type`),
  ADD KEY `idx_violation_types` (`violation_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `citations`
--
ALTER TABLE `citations`
  MODIFY `citation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=372801;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `driver_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38663;

--
-- AUTO_INCREMENT for table `remarks`
--
ALTER TABLE `remarks`
  MODIFY `remark_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9487;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `vehicle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36299;

--
-- AUTO_INCREMENT for table `violations`
--
ALTER TABLE `violations`
  MODIFY `violation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

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
  ADD CONSTRAINT `fk_violations_violation_type` FOREIGN KEY (`violation_type`) REFERENCES `violation_types` (`violation_type`) ON UPDATE CASCADE,
  ADD CONSTRAINT `violations_ibfk_1` FOREIGN KEY (`citation_id`) REFERENCES `citations` (`citation_id`),
  ADD CONSTRAINT `violations_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
