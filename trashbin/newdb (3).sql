-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 08, 2025 at 05:59 AM
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
(372801, '06101', 38664, 36299, '2025-07-04 20:39:00', 'SAN JOSE', 0, 'Paid', '2025-07-04 14:41:21', 300.00, 'PAY-20250705-0225-69'),
(372802, '06102', 38665, 36300, '2025-07-04 15:34:00', 'SAN JOSE', 0, 'Paid', '2025-07-04 16:59:49', 300.00, 'PAY-20250705-0225-40'),
(372803, '06103', 38666, 36301, '2025-07-04 16:58:00', 'SAN JOSE', 0, 'Paid', '2025-07-04 16:59:06', 300.00, 'PAY-20250705-0225-78'),
(372804, '06104', 38667, 36302, '2025-07-04 17:00:00', 'SAN JOSE', 0, 'Paid', '2025-07-07 11:52:33', 300.00, 'PAY-20250708-0225-58'),
(372805, '06105', 38664, 36303, '2025-07-04 17:01:00', 'SAN JOSE', 0, 'Paid', '2025-07-04 17:06:26', 300.00, 'PAY-20250705-0225-61'),
(372806, '06106', 38668, 36304, '2025-07-07 11:51:00', 'SAN JOSE', 0, 'Paid', '2025-07-07 11:51:55', 300.00, 'PAY-20250708-0225-41'),
(372807, '06107', 38669, 36305, '2025-07-07 11:53:00', 'SAN JOSE', 0, 'Paid', '2025-07-07 11:53:25', 300.00, 'PAY-20250708-0225-55'),
(372808, '06108', 38670, 36306, '2025-07-07 11:54:00', 'SAN JOSE', 0, 'Paid', '2025-07-07 11:54:53', 300.00, 'PAY-20250708-0225-37'),
(372809, '06109', 38671, 36307, '2025-07-07 12:08:00', 'SAN JOSE', 0, 'Paid', '2025-07-07 12:08:37', 300.00, 'PAY-20250708-0225-94'),
(372810, '06110', 38672, 36308, '2025-07-07 12:17:00', 'SAN JOSE', 0, 'Paid', '2025-07-07 12:17:43', 300.00, 'PAY-20250708-0225-89'),
(372811, '06111', 38673, 36309, '2025-07-07 12:26:00', 'SAN JOSE', 0, 'Paid', '2025-07-07 12:26:58', 300.00, 'PAY-20250708-0225-04'),
(372812, '06112', 38674, 36310, '2025-07-07 12:30:00', 'SAN JOSE', 0, 'Paid', '2025-07-07 12:30:46', 300.00, 'PAY-20250708-0225-75'),
(372813, '06113', 38675, 36311, '2025-07-07 12:49:00', 'SAN JOSE', 0, 'Paid', '2025-07-07 12:50:14', 300.00, 'PAY-20250708-0225-85'),
(372814, '06114', 38676, 36312, '2025-07-07 13:01:00', 'SAN JOSE', 0, 'Paid', '2025-07-07 13:01:47', 300.00, 'PAY-20250708-0225-32'),
(372815, '06115', 38677, 36313, '2025-07-07 13:09:00', 'SAN JOSE', 0, 'Paid', '2025-07-07 13:09:24', 300.00, 'PAY-20250708-0225-61'),
(372816, '06116', 38678, 36314, '2025-07-07 14:12:00', 'SAN JOSE', 0, 'Paid', '2025-07-07 14:13:15', 300.00, 'PAY-20250708-0225-27'),
(372817, '06117', 38679, 36315, '2025-07-07 14:18:00', 'SAN JOSE', 0, 'Paid', '2025-07-07 14:18:18', 300.00, 'PAY-20250708-0225-35'),
(372818, '06118', 38680, 36316, '2025-07-07 14:19:00', 'SAN JOSE', 0, 'Paid', '2025-07-07 14:20:12', 300.00, 'PAY-20250708-0225-44'),
(372819, '06119', 38681, 36317, '2025-07-07 14:53:00', 'SAN JOSE', 0, 'Paid', '2025-07-07 14:53:38', 500.00, 'PAY-20250708-0225-68');

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
(38664, 'rosete', 'richmond', 'R', NULL, '1', 'Agaman Sur', NULL, NULL, '', 'Non-Professional'),
(38665, 'jyt', 'Rogelio', 'G', NULL, '1', 'Agaman', 'Baggao', 'Cagayan', NULL, NULL),
(38666, 'KJH', 'Rogelio', 'G', NULL, '1', 'Bungel', 'Baggao', 'Cagayan', NULL, NULL),
(38667, 'BHFGF', 'GYRT', 'G', NULL, '1', 'Carupian', 'Baggao', 'Cagayan', NULL, NULL),
(38668, 'BHFGF', 'GYRT', 'G', NULL, '1', 'Canagatan', 'Baggao', 'Cagayan', NULL, NULL),
(38669, 'rosete', 'richmond', 'R', NULL, '1', 'Canagatan', 'Baggao', 'Cagayan', NULL, NULL),
(38670, 'rosete', 'richmond', 'R', NULL, '1', 'Canagatan', 'Baggao', 'Cagayan', NULL, NULL),
(38671, 'rosete', 'richmond', 'R', NULL, '1', 'Carupian', 'Baggao', 'Cagayan', NULL, NULL),
(38672, 'rosete', 'richmond', 'R', NULL, '1', 'Bungel', 'Baggao', 'Cagayan', NULL, NULL),
(38673, 'rosete', 'richmond', 'R', NULL, '1', 'Canagatan', 'Baggao', 'Cagayan', NULL, NULL),
(38674, 'rosete', 'richmond', 'R', NULL, '1', 'Carupian', 'Baggao', 'Cagayan', NULL, NULL),
(38675, 'rosete', 'richmond', 'R', NULL, '1', 'Bungel', 'Baggao', 'Cagayan', NULL, NULL),
(38676, 'rosete', 'richmond', 'R', NULL, '1', 'Carupian', 'Baggao', 'Cagayan', NULL, NULL),
(38677, 'rosete', 'richmond', 'R', NULL, '1', 'Bungel', 'Baggao', 'Cagayan', NULL, NULL),
(38678, 'rosete', 'richmond', 'R', NULL, '1', 'Canagatan', 'Baggao', 'Cagayan', NULL, NULL),
(38679, 'rosete', 'richmond', 'R', NULL, '1', 'Bungel', 'Baggao', 'Cagayan', NULL, NULL),
(38680, 'rosete', 'richmond', 'R', NULL, '1', 'Agaman', 'Baggao', 'Cagayan', NULL, NULL),
(38681, 'rosete', 'richmond', 'R', NULL, '1', 'Agaman', 'Baggao', 'Cagayan', NULL, NULL);

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
(36299, '5JK567', 'Motorcycle', 'RIDER 150'),
(36300, '5JK567', 'Motorcycle', 'RIDER 150'),
(36301, '5JK567', 'Motorcycle', 'RIDER 150'),
(36302, '5JK567', 'Motorcycle', 'RIDER 150'),
(36303, '5JK567', 'Motorcycle', 'RIDER 150'),
(36304, '5JK567', 'Motorcycle', 'RIDER 150'),
(36305, '5JK567', 'Motorcycle', 'RIDER 150'),
(36306, '5JK567', 'Motorcycle', 'RIDER 150'),
(36307, '5JK567', 'Motorcycle', 'RIDER 150'),
(36308, '5JK567', 'Motorcycle', 'RIDER 150'),
(36309, '5JK567', 'Motorcycle', 'RIDER 150'),
(36310, '5JK567', 'Motorcycle', 'RIDER 150'),
(36311, '5JK567', 'Motorcycle', 'RIDER 150'),
(36312, '5JK567', 'Motorcycle', 'RIDER 150'),
(36313, '5JK567', 'Motorcycle', 'RIDER 150'),
(36314, '5JK567', 'Motorcycle', 'RIDER 150'),
(36315, '5JK567', 'Motorcycle', 'RIDER 150'),
(36316, '5JK567', 'Motorcycle', 'RIDER 150'),
(36317, '5JK567', 'Motorcycle', 'RIDER 150');

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
(49, 372801, 'NO HELMET (BACKRIDER)', 1, 38664, 'Paid'),
(50, 372801, 'NO HELMET (DRIVER)', 1, 38664, 'Paid'),
(51, 372802, 'NO HELMET (BACKRIDER)', 1, 38665, 'Paid'),
(52, 372802, 'NO HELMET (DRIVER)', 1, 38665, 'Paid'),
(53, 372803, 'NO HELMET (BACKRIDER)', 1, 38666, 'Paid'),
(54, 372803, 'NO HELMET (DRIVER)', 1, 38666, 'Paid'),
(55, 372804, 'NO HELMET (BACKRIDER)', 1, 38667, 'Paid'),
(56, 372804, 'NO HELMET (DRIVER)', 1, 38667, 'Paid'),
(57, 372805, 'NO HELMET (DRIVER)', 2, 38664, 'Paid'),
(58, 372805, 'NO HELMET (BACKRIDER)', 2, 38664, 'Paid'),
(59, 372806, 'NO HELMET (BACKRIDER)', 1, 38668, 'Paid'),
(60, 372806, 'NO HELMET (DRIVER)', 1, 38668, 'Paid'),
(61, 372807, 'NO HELMET (BACKRIDER)', 1, 38669, 'Paid'),
(62, 372807, 'NO HELMET (DRIVER)', 1, 38669, 'Paid'),
(63, 372808, 'NO HELMET (BACKRIDER)', 1, 38670, 'Paid'),
(64, 372808, 'NO HELMET (DRIVER)', 1, 38670, 'Paid'),
(65, 372809, 'NO HELMET (BACKRIDER)', 1, 38671, 'Paid'),
(66, 372809, 'NO HELMET (DRIVER)', 1, 38671, 'Paid'),
(67, 372810, 'NO HELMET (BACKRIDER)', 1, 38672, 'Paid'),
(68, 372810, 'NO HELMET (DRIVER)', 1, 38672, 'Paid'),
(69, 372811, 'NO HELMET (BACKRIDER)', 1, 38673, 'Paid'),
(70, 372811, 'NO HELMET (DRIVER)', 1, 38673, 'Paid'),
(71, 372812, 'NO HELMET (BACKRIDER)', 1, 38674, 'Paid'),
(72, 372812, 'NO HELMET (DRIVER)', 1, 38674, 'Paid'),
(73, 372813, 'NO HELMET (BACKRIDER)', 1, 38675, 'Paid'),
(74, 372813, 'NO HELMET (DRIVER)', 1, 38675, 'Paid'),
(75, 372814, 'NO HELMET (BACKRIDER)', 1, 38676, 'Paid'),
(76, 372814, 'NO HELMET (DRIVER)', 1, 38676, 'Paid'),
(77, 372815, 'NO HELMET (BACKRIDER)', 1, 38677, 'Paid'),
(78, 372815, 'NO HELMET (DRIVER)', 1, 38677, 'Paid'),
(79, 372816, 'NO HELMET (BACKRIDER)', 1, 38678, 'Paid'),
(80, 372816, 'NO HELMET (DRIVER)', 1, 38678, 'Paid'),
(81, 372817, 'NO HELMET (BACKRIDER)', 1, 38679, 'Paid'),
(82, 372817, 'NO HELMET (DRIVER)', 1, 38679, 'Paid'),
(83, 372818, 'NO HELMET (BACKRIDER)', 1, 38680, 'Paid'),
(84, 372818, 'NO HELMET (DRIVER)', 1, 38680, 'Paid'),
(85, 372819, 'NO HELMET (BACKRIDER)', 1, 38681, 'Paid'),
(86, 372819, 'NO HELMET (DRIVER)', 1, 38681, 'Paid'),
(87, 372819, 'FAILURE TO PRESENT E-OV MATCH CARD', 1, 38681, 'Paid');

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
  MODIFY `citation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=372820;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `driver_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38682;

--
-- AUTO_INCREMENT for table `remarks`
--
ALTER TABLE `remarks`
  MODIFY `remark_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9486;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `vehicle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36318;

--
-- AUTO_INCREMENT for table `violations`
--
ALTER TABLE `violations`
  MODIFY `violation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

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
