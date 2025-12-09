-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 09, 2025 at 05:32 PM
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
-- Database: `bfp_archiving_system_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `user_type` varchar(50) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `report_id` int(11) DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `username`, `user_type`, `action`, `report_id`, `id`, `timestamp`, `details`) VALUES
(1, 'admin123', 'admin', 'create', 1, NULL, '2025-12-09 22:56:15', 'Created report: Fire at Patadon West Farm'),
(2, 'admin123', NULL, 'create', 1, NULL, '2025-12-09 23:05:56', 'Created Fire Safety Inspection Report: Mel & Jade'),
(3, 'admin123', 'admin', 'create', 2, NULL, '2025-12-09 23:15:11', 'Created report: Fire at Patadon West Farm'),
(4, 'admin123', 'admin', 'create', 3, NULL, '2025-12-09 23:17:13', 'Created report: Fire at Patadon West Farm'),
(5, 'admin123', NULL, 'create', 2, NULL, '2025-12-09 23:19:22', 'Created Fire Safety Inspection Report: Hardware Inspection 1'),
(6, 'user', NULL, 'create', 4, NULL, '2025-12-09 23:30:57', 'Created report: Fire at Patadon West Farm'),
(7, 'user', NULL, 'create', 3, NULL, '2025-12-09 23:34:59', 'Created Fire Safety Inspection Report: Hardware Inspection 1'),
(8, 'user', NULL, 'update', 3, NULL, '2025-12-09 23:56:04', 'Updated Fire Safety Inspection Report: Hardware Inspection 1');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, '[value-2]', '[value-3]'),
(2, 'admin', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `barangays`
--

CREATE TABLE `barangays` (
  `barangay_name` varchar(100) NOT NULL,
  `barangay_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barangays`
--

INSERT INTO `barangays` (`barangay_name`, `barangay_id`) VALUES
('New Alimodian', 8),
('Bangbang', 10),
('Bato', 11),
('Central Malamote', 12),
('Dalapitan', 13),
('Estado', 14),
('Ilian', 15),
('Kabulacan', 16),
('Kibia', 17),
('Kibudoc', 18),
('Kidama', 19),
('Kilada', 20),
('Lampayan', 21),
('Latagan', 22),
('Linao', 23),
('Lower Malamote', 24),
('Manubuan', 25),
('Manupal', 26),
('Marbel', 27),
('Minamaing', 28),
('Natutungan', 29),
('New Bugasong', 30),
('New Pandan', 31),
('Patadon West', 32),
('Poblacion', 33),
('Salvacion', 34),
('Santa Maria', 35),
('Sarayan', 36),
('Taculen', 37),
('Taguranao', 38),
('Tamped (Tampad)', 39),
('New Abra', 40),
('Pinamaton', 41),
('Arakan', 44);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `departments_id` int(11) NOT NULL,
  `departments` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`departments_id`, `departments`) VALUES
(3, 'Administrative'),
(4, 'Operation'),
(5, 'Fire Safety and Prevention');

-- --------------------------------------------------------

--
-- Table structure for table `fire_incident_reports`
--

CREATE TABLE `fire_incident_reports` (
  `report_id` int(11) NOT NULL,
  `report_title` varchar(255) NOT NULL,
  `fire_location` varchar(255) NOT NULL,
  `street` varchar(255) DEFAULT NULL,
  `purok` varchar(255) DEFAULT NULL,
  `municipality` varchar(255) DEFAULT NULL,
  `incident_date` datetime NOT NULL,
  `establishment` varchar(255) DEFAULT NULL,
  `victims` text DEFAULT NULL,
  `firefighters` text DEFAULT NULL,
  `property_damage` varchar(100) DEFAULT NULL,
  `fire_types` varchar(255) DEFAULT NULL,
  `uploader` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `caller_name` varchar(255) DEFAULT NULL,
  `responding_team` varchar(255) DEFAULT NULL,
  `arrival_time` varchar(50) DEFAULT NULL,
  `fireout_time` varchar(50) DEFAULT NULL,
  `alarm_status` varchar(100) DEFAULT NULL,
  `occupancy_type` varchar(100) DEFAULT NULL,
  `documentation_photos` text DEFAULT NULL,
  `narrative_report` text DEFAULT NULL,
  `progress_report` text DEFAULT NULL,
  `final_investigation_report` text DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fire_incident_reports`
--

INSERT INTO `fire_incident_reports` (`report_id`, `report_title`, `fire_location`, `street`, `purok`, `municipality`, `incident_date`, `establishment`, `victims`, `firefighters`, `property_damage`, `fire_types`, `uploader`, `department`, `caller_name`, `responding_team`, `arrival_time`, `fireout_time`, `alarm_status`, `occupancy_type`, `documentation_photos`, `narrative_report`, `progress_report`, `final_investigation_report`, `deleted`, `deleted_at`, `created_at`) VALUES
(1, 'Fire at Patadon West Farm', 'Patadon West', 'Matalam Hardware', 'Purok 4', 'Matalam', '2025-12-09 22:55:00', 'Warehouse', '', '', '1,000,000', '', 'admin123', 'Administrative', 'Carlos Dizon', 'Team A', '22:56', '22:56', '1st Alarm', 'Residential', '', '', '', '', 0, NULL, '2025-12-09 22:56:15'),
(2, 'Fire at Patadon West Farm', 'Patadon West', 'Matalam Hardware', 'Purok 4', 'Matalam', '2025-10-10 23:13:00', 'Warehouse', '', '', '1,000,000', '', 'admin123', 'Administrative', 'Carlos Dizon', 'Team A', '23:13', '23:13', '1st Alarm', 'Residential', '../uploads/1765293311_fire 1 - Copy.jpg,../uploads/1765293311_fire 1.jpg,../uploads/1765293311_fire 2 - Copy.jpg,../uploads/1765293311_fire 2.jpg,../uploads/1765293311_fire3 - Copy.jpg,../uploads/1765293311_fire3.jpg', '../uploads/1765293311_Spot Investigation Report Sample.pdf', '../uploads/1765293311_progress_Progress Investigation Report.pdf', '../uploads/1765293311_final_Final Investigation Report.pdf', 0, NULL, '2025-12-09 23:15:11'),
(3, 'Fire at Patadon West Farm', 'Patadon West', 'Matalam Hardware', 'Purok 4', 'Matalam', '2001-09-11 15:00:00', 'Warehouse', '', '', '1,000,000', 'Airplane crash', 'admin123', 'Administrative', 'Carlos Dizon', 'Team A', '15:15', '18:50', '1st Alarm', 'Residential', '../uploads/1765293433_fire 1 - Copy.jpg,../uploads/1765293433_fire 1.jpg,../uploads/1765293433_fire 2 - Copy.jpg,../uploads/1765293433_fire 2.jpg,../uploads/1765293433_fire3 - Copy.jpg,../uploads/1765293433_fire3.jpg', '../uploads/1765293433_Spot Investigation Report Sample.pdf', '../uploads/1765293433_progress_Progress Investigation Report.pdf', '../uploads/1765293433_final_Final Investigation Report.pdf', 0, NULL, '2025-12-09 23:17:13'),
(4, 'Fire at Patadon West Farm', 'Patadon West', 'Matalam Hardware', 'Purok 4', 'Matalam', '2025-08-19 23:25:00', 'Warehouse', '', '', '1,000,000', 'Pyrotechnics', 'user', 'Operation', 'Carlos Dizon', 'Team A', '23:25', '12:25', '1st Alarm', 'Residential', '', '', '', '', 0, NULL, '2025-12-09 23:30:57');

-- --------------------------------------------------------

--
-- Table structure for table `fire_safety_inspection_certificate`
--

CREATE TABLE `fire_safety_inspection_certificate` (
  `id` int(11) NOT NULL,
  `permit_name` varchar(255) NOT NULL,
  `inspection_establishment` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `inspection_address` varchar(255) NOT NULL,
  `inspection_date` date NOT NULL,
  `establishment_type` varchar(50) NOT NULL,
  `inspection_purpose` varchar(50) NOT NULL,
  `fire_alarms` tinyint(1) DEFAULT 0,
  `fire_extinguishers` tinyint(1) DEFAULT 0,
  `emergency_exits` tinyint(1) DEFAULT 0,
  `sprinkler_systems` tinyint(1) DEFAULT 0,
  `fire_drills` tinyint(1) DEFAULT 0,
  `exit_signs` tinyint(1) DEFAULT 0,
  `electrical_wiring` tinyint(1) DEFAULT 0,
  `emergency_evacuations` tinyint(1) DEFAULT 0,
  `inspected_by` varchar(255) NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `contact_number` varchar(50) NOT NULL,
  `number_of_occupants` int(11) NOT NULL,
  `nature_of_business` varchar(255) NOT NULL,
  `number_of_floors` int(11) NOT NULL,
  `floor_area` varchar(100) NOT NULL,
  `classification_of_hazards` varchar(50) NOT NULL,
  `building_construction` varchar(255) NOT NULL,
  `possible_problems` text DEFAULT NULL,
  `hazardous_materials` text DEFAULT NULL,
  `application_form` varchar(255) DEFAULT NULL,
  `proof_of_ownership` varchar(255) DEFAULT NULL,
  `building_plans` varchar(255) DEFAULT NULL,
  `fire_safety_inspection_certificate` varchar(255) DEFAULT NULL,
  `fire_safety_inspection_checklist` varchar(255) DEFAULT NULL,
  `occupancy_permit` varchar(255) DEFAULT NULL,
  `business_permit` varchar(255) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `uploader` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fire_safety_inspection_certificate`
--

INSERT INTO `fire_safety_inspection_certificate` (`id`, `permit_name`, `inspection_establishment`, `owner`, `inspection_address`, `inspection_date`, `establishment_type`, `inspection_purpose`, `fire_alarms`, `fire_extinguishers`, `emergency_exits`, `sprinkler_systems`, `fire_drills`, `exit_signs`, `electrical_wiring`, `emergency_evacuations`, `inspected_by`, `contact_person`, `contact_number`, `number_of_occupants`, `nature_of_business`, `number_of_floors`, `floor_area`, `classification_of_hazards`, `building_construction`, `possible_problems`, `hazardous_materials`, `application_form`, `proof_of_ownership`, `building_plans`, `fire_safety_inspection_certificate`, `fire_safety_inspection_checklist`, `occupancy_permit`, `business_permit`, `deleted_at`, `uploader`, `department`, `created_at`) VALUES
(1, 'Mel & Jade', 'Warehouse', 'Ana Lopez', 'Warehouse', '2025-12-09', 'healthcare', 'routine', 0, 0, 0, 0, 0, 0, 0, 0, 'Mark Villanueva', 'Carlos Dizon', '5555555', 2, 'None', 2, '750 sqm', 'Class_A', 'concrete', 'None', 'Wood', '../uploads/20251209160556_Application Form (BFP).pdf', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin123', 'Administrative', '2025-12-09 23:05:56'),
(2, 'Hardware Inspection 1', 'Warehouse', 'Ana Lopez', 'Warehouse', '2025-12-09', 'commercial', 'compliance', 0, 0, 0, 0, 0, 0, 0, 0, 'Mark Villanueva', 'Carlos Dizon', '5555555', 2, 'None', 2, '750 sqm', 'Class_A', 'concrete', 'Narrow Road\r\nToo Crowded', 'Paper\r\nPlastic\r\nWood', '../uploads/20251209161922_Application Form (BFP).pdf', '../uploads/20251209161922_Proof of Ownership.pdf', '../uploads/20251209161922_Building Plans.pdf', '../uploads/20251209161922_FSIC-Sample.pdf', '../uploads/20251209161922_Fire-Safety-Inspection-Checklist.pdf', '../uploads/20251209161922_FSIC-Sample.pdf', '../uploads/20251209161922_Tax Assessment.pdf', NULL, 'admin123', 'Administrative', '2025-12-09 23:19:22'),
(3, 'Hardware Inspection 1', 'Warehouse', 'Ana Lopez', 'Warehouse', '2025-12-09', 'industrial', 'routine', 0, 0, 0, 0, 0, 0, 0, 0, 'Mark Villanueva', 'Carlos Dizon', '5555555', 2, 'None', 2, '750 sqm', 'Class_A', 'concrete', 'Too Narrow, Too Crowded', 'Plastic, wood, paper', '../uploads/69384693ef687_Application Form (BFP).pdf', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'Operation', '2025-12-09 23:34:59');

-- --------------------------------------------------------

--
-- Table structure for table `fire_types`
--

CREATE TABLE `fire_types` (
  `fire_types_id` int(11) NOT NULL,
  `fire_types` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fire_types`
--

INSERT INTO `fire_types` (`fire_types_id`, `fire_types`, `description`) VALUES
(1, 'Electrical connection', 'Fires caused by faulty or poor electrical connections, resulting in overheating or sparks.'),
(2, 'Electrical appliances', 'Fires due to malfunctioning or damaged electrical appliances, like faulty wiring or overheating.'),
(3, 'Electrical machineries', 'Fires originating from electrical machinery malfunction, including motor failures or wiring issues.'),
(4, 'Spontaneous combustion', 'Fires occurring without external ignition, usually due to chemical reactions or organic material heating up internally.'),
(5, 'Open flame due to unattended cooking/stove', 'Fires caused by stoves or cooking appliances left unattended, leading to overheating or flare-ups.'),
(6, 'Open flame due to torch or sulo', 'Fires caused by unattended torches or makeshift open flame sources, like a sulo (bin).'),
(7, 'Open flame due to unattended lighted candle or gasera', 'Fires from candles or lamps left unattended, igniting nearby combustible materials.'),
(8, 'LPG explosion due to direct flame contact or static electricity', 'Explosions caused by LPG gas coming into contact with a flame or igniting from static electricity.'),
(9, 'Lighted cigarette butt', 'Fires caused by discarded or improperly handled cigarette butts igniting combustible materials.'),
(10, 'Chemicals/LPG leaking', 'Fires from chemicals or LPG leaking and igniting due to external sparks, heat, or other sources.'),
(11, 'Pyrotechnics', 'Fires caused by the improper handling or malfunctioning of fireworks or other pyrotechnic devices.'),
(12, 'Lighted matchstick or lighter', 'Fires caused by igniting combustible materials with a matchstick or lighter.'),
(13, 'Incendiary device/mechanism or ignited flammable liquids', 'Fires intentionally caused by a device or flammable liquids used to start a fire.'),
(14, 'Lightning', 'Fires started by a lightning strike, igniting flammable materials in its path.'),
(15, 'Open flame', 'Fires originating from open flames in uncontrolled environments, including candles, bonfires, or other ignition sources.'),
(16, 'Mechanical collision', 'Fires caused by mechanical impacts or friction, such as vehicle crashes or industrial machinery failure.'),
(17, 'Airplane crash', 'Fires resulting from an airplane crash, typically due to fuel ignition or damage to electrical systems.'),
(18, 'Bomb explosion', 'Fires caused by explosions from bombs or similar devices, causing widespread damage and ignition.'),
(20, 'Others', 'Fires caused by various uncommon or undefined factors not covered in the listed categories.');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `system_name` varchar(255) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `about` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `system_name`, `contact_email`, `logo`, `about`) VALUES
(1, 'BUREAU OF FIRE PROTECTION REPORT ARCHIVING SYSTEM', 'admin@example.com', 'REPORT.png', '            <p>The Bureau of Fire Protection Archiving System is designed to securely manage, store, and retrieve official fire protection reports and documents. Our platform streamlines the archiving process, ensuring efficiency, accuracy, and accessibility for authorized personnel.</p><p><strong>Features:</strong><ul><li>Search and retrieve archived reports quickly</li><li>Create and manage new archive entries</li><li>Generate and export comprehensive reports</li><li>Role-based access for employees and administrators</li></ul></p><p>We are committed to providing a reliable and user-friendly system to support the Bureau of Fire Protection\\\'s mission of safeguarding lives and property.</p>\r\n');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `system_name` varchar(255) NOT NULL,
  `welcome_content` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `cover` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` text NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `birthday` date NOT NULL,
  `address` text NOT NULL,
  `username` varchar(50) NOT NULL,
  `gender` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `contact` varchar(255) NOT NULL,
  `user_type` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verified` tinyint(1) DEFAULT 0,
  `status` varchar(255) DEFAULT 'not verified'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `avatar`, `first_name`, `middle_name`, `last_name`, `birthday`, `address`, `username`, `gender`, `password`, `department`, `contact`, `user_type`, `created_at`, `updated_at`, `verified`, `status`) VALUES
(125, '', 'User', 'User', 'User', '2025-12-05', 'Poblacion, Matalam, Cotabato', 'user', 'male', '$2y$10$tqn3wqfLZRH5kZ6Vzn2fy.BV25Dbr5mTZE6hV23iCNwkm85I72Dnu', 'Operation', '09777461096', 'staff', '2025-12-05 12:23:09', '2025-12-09 15:24:31', 0, 'verified'),
(127, '', 'admin123', 'admin123', 'admin123', '2025-12-06', 'admin', 'admin123', '', '$2y$10$OuHGKATpLL6g5f0/Q4XTV.gk5x63tveBLGWDbG6WnKASGkZRD/nCa', 'Administrative', '1234567890', 'admin', '2025-12-06 11:17:03', '2025-12-07 04:37:27', 0, 'verified'),
(130, '', 'admin admin admin', '', 'admin', '2025-12-07', 'Matalam Hardware', 'admin456', '', '$2y$10$3MrJ0T0yaO.ud1WPe48xHuFT3fY6lT4mW60n.9Yt1REYzDFANOPsu', 'Administrative', '1234567890', 'admin', '2025-12-07 02:34:09', '2025-12-07 02:34:09', 0, 'not verified'),
(131, NULL, 'John', 'Mar', 'Doe', '1995-12-25', 'Poblacion, Matalam, Cotabato', 'john doe', 'Male', '$2y$10$DC3pVFhPl4OYJXniH7f83uZZW0PI/.BvGHqt4E92LtPg8jQPJ/2je', 'Fire Safety and Prevention', '1234567890', 'staff', '2025-12-09 04:33:52', '2025-12-09 04:34:23', 0, 'verified');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `barangays`
--
ALTER TABLE `barangays`
  ADD PRIMARY KEY (`barangay_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`departments_id`);

--
-- Indexes for table `fire_incident_reports`
--
ALTER TABLE `fire_incident_reports`
  ADD PRIMARY KEY (`report_id`);

--
-- Indexes for table `fire_safety_inspection_certificate`
--
ALTER TABLE `fire_safety_inspection_certificate`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fire_types`
--
ALTER TABLE `fire_types`
  ADD PRIMARY KEY (`fire_types_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `barangays`
--
ALTER TABLE `barangays`
  MODIFY `barangay_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `departments_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `fire_incident_reports`
--
ALTER TABLE `fire_incident_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `fire_safety_inspection_certificate`
--
ALTER TABLE `fire_safety_inspection_certificate`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `fire_types`
--
ALTER TABLE `fire_types`
  MODIFY `fire_types_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
