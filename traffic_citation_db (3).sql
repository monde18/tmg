-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 28, 2025 at 10:43 AM
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
  `is_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `citations`
--

INSERT INTO `citations` (`citation_id`, `ticket_number`, `driver_id`, `vehicle_id`, `apprehension_datetime`, `place_of_apprehension`, `is_archived`) VALUES
(19, '06103', 17, 19, '2025-05-28 20:18:00', 'JJGH', 1),
(20, '06104', 18, 20, '2025-05-28 14:10:00', 'JJGH', 1),
(21, '06105', 19, 21, '2025-05-28 14:12:00', 'JJGH', 1),
(22, '06106', 20, 22, '2025-05-29 16:21:00', 'JJGH', 1),
(23, '06107', 20, 23, '2025-05-28 06:23:00', 'JJGH', 1),
(25, '06109', 20, 25, '2025-05-28 06:24:00', 'JJGH', 1),
(26, '06110', 21, 26, '2025-05-28 15:47:00', 'JJGH', 0),
(27, '06111', 21, 27, '0000-00-00 00:00:00', '', 0),
(28, '06112', 21, 28, '2025-05-28 07:48:00', '', 0);

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
(17, 'Rosete', 'Richmond', '', '', '5', 'Barsat East', 'Baggao', 'Cagayan', '000549', 'Non-Professional'),
(18, 'Rosete', 'Richmond', '', '', '5', 'Barsat West', 'Baggao', 'Cagayan', '000549', 'Non-Professional'),
(19, 'Rosete', 'Richmond', '', '', '5', 'Bitag Grande', 'Baggao', 'Cagayan', '000549', 'Non-Professional'),
(20, 'AMBO', 'LIGAN', '', '', '5', 'Bagunot', 'Baggao', 'Cagayan', '000549', 'Non-Professional'),
(21, 'AMBO', 'LIGAN', '', '', '5', 'Barsat West', 'Baggao', 'Cagayan', '000549', 'Non-Professional');

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
(6, 19, 'EMERGENCY GAMIN KANU'),
(7, 20, 'HJ'),
(8, 21, 'ADDA GAYAM LICENSE NA'),
(9, 22, 'HJ'),
(10, 23, 'EMERGENCY GAMIN KANU'),
(12, 25, 'HJ'),
(13, 26, 'HUH');

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
(17, '4564564', 'Motorcycle', 'IHKJ'),
(18, '4564564', 'Motorcycle', 'IHKJ'),
(19, '4564564', 'Kulong', 'IHKJ'),
(20, '4564564', 'Van', 'IHKJ'),
(21, '4564564', 'Truck', 'IHKJ'),
(22, '4564564', 'Motorcycle', 'IHKJ'),
(23, '4564564', 'Tricycle', 'IHKJ'),
(24, '4564564', 'Van', 'IHKJ'),
(25, '4564564', 'Motorcycle', 'IHKJ'),
(26, '4564564', 'Tricycle', 'IHKJ'),
(27, '', 'Unknown', ''),
(28, '', 'Jeep', '');

-- --------------------------------------------------------

--
-- Table structure for table `violations`
--

CREATE TABLE `violations` (
  `violation_id` int(11) NOT NULL,
  `citation_id` int(11) DEFAULT NULL,
  `violation_type` varchar(100) DEFAULT NULL,
  `offense_count` int(11) DEFAULT 1,
  `driver_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `violations`
--

INSERT INTO `violations` (`violation_id`, `citation_id`, `violation_type`, `offense_count`, `driver_id`) VALUES
(49, 26, 'No Helmet (Driver)', 1, 21),
(50, 27, 'No Helmet (Driver)', 2, 21),
(51, 28, 'No Helmet (Driver)', 3, 21);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `citations`
--
ALTER TABLE `citations`
  ADD PRIMARY KEY (`citation_id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`driver_id`);

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
  MODIFY `citation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `driver_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `remarks`
--
ALTER TABLE `remarks`
  MODIFY `remark_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `vehicle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `violations`
--
ALTER TABLE `violations`
  MODIFY `violation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

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
  ADD CONSTRAINT `violations_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`),
  ADD CONSTRAINT `violations_ibfk_3` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
