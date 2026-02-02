-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 17, 2026 at 04:51 PM
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
-- Database: `hostel_management_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `application`
--

CREATE TABLE `application` (
  `Application_id` int(100) NOT NULL,
  `Student_id` varchar(255) NOT NULL,
  `Hostel_id` int(10) NOT NULL,
  `Application_status` tinyint(1) DEFAULT NULL,
  `Room_No` int(10) DEFAULT NULL,
  `Message` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `preferred_room_id` int(11) DEFAULT NULL,
  `preferred_bed_number` int(11) DEFAULT NULL,
  `include_food` tinyint(1) DEFAULT 0 COMMENT '0 = No food, 1 = Include food service',
  `food_plan` varchar(20) DEFAULT NULL COMMENT 'basic, standard, premium'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `application`
--

INSERT INTO `application` (`Application_id`, `Student_id`, `Hostel_id`, `Application_status`, `Room_No`, `Message`, `start_date`, `end_date`, `preferred_room_id`, `preferred_bed_number`, `include_food`, `food_plan`) VALUES
(3, '20790520', 1, 0, 103, 'ghgjhjhbiyjhv', '2025-12-01', '2026-12-27', 5, 1, 1, 'premium'),
(13, '20790523', 3, 0, 301, 'dzfbbs', '2025-11-01', '2026-10-31', NULL, NULL, 0, NULL),
(63, '20790524', 2, 0, 201, 'i want a room', '2025-12-31', '2026-07-01', 17, 1, 1, 'standard'),
(64, '20790522', 2, 0, 201, 'i want a room', '2025-12-31', '2026-07-01', 17, 2, 0, ''),
(65, '20790527', 3, 0, 302, 'i want a room', '2026-01-02', '2026-07-30', 28, 2, 1, 'basic');

-- --------------------------------------------------------

--
-- Table structure for table `bed_allocation`
--

CREATE TABLE `bed_allocation` (
  `allocation_id` int(11) NOT NULL,
  `room_id` int(10) NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `bed_number` int(1) NOT NULL COMMENT '1, 2, or 3',
  `allocation_price` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `payment_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `include_food` tinyint(1) DEFAULT 0 COMMENT '0 = No food, 1 = Food included',
  `food_plan` varchar(20) DEFAULT NULL COMMENT 'basic, standard, premium'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `bed_allocation`
--

INSERT INTO `bed_allocation` (`allocation_id`, `room_id`, `student_id`, `bed_number`, `allocation_price`, `start_date`, `end_date`, `is_active`, `payment_status`, `payment_id`, `created_at`, `include_food`, `food_plan`) VALUES
(34, 28, '20790523', 1, 5000.00, '2025-12-29', '2026-12-29', 1, 'pending', NULL, '2025-12-27 17:10:00', 0, NULL),
(36, 5, '20790520', 1, 5000.00, '2025-12-01', '2026-12-27', 1, 'paid', 'ESEW_20260106161807_3376', '2025-12-27 18:06:46', 1, 'premium'),
(42, 17, '20790524', 1, 6500.00, '2025-12-30', '2026-12-30', 1, 'pending', NULL, '2025-12-30 14:50:45', 1, 'standard'),
(43, 17, '20790522', 2, 5000.00, '2025-12-30', '2026-12-30', 1, 'paid', 'ESEW_20260101182201_5362', '2025-12-30 14:51:45', 0, ''),
(44, 28, '20790527', 2, 5500.00, '2025-12-30', '2026-12-30', 1, 'pending', NULL, '2025-12-30 14:56:32', 1, 'basic');

--
-- Triggers `bed_allocation`
--
DELIMITER $$
CREATE TRIGGER `before_bed_allocation_insert` BEFORE INSERT ON `bed_allocation` FOR EACH ROW BEGIN
                           DECLARE room_capacity INT;
                           SELECT bed_capacity INTO room_capacity FROM Room WHERE Room_id = NEW.room_id;
                           IF NEW.bed_number > room_capacity THEN
                               SIGNAL SQLSTATE '45000' 
                               SET MESSAGE_TEXT = 'Bed number cannot exceed room capacity';
                           END IF;
                       END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_bed_allocation_update` BEFORE UPDATE ON `bed_allocation` FOR EACH ROW BEGIN
                           DECLARE room_capacity INT;
                           SELECT bed_capacity INTO room_capacity FROM Room WHERE Room_id = NEW.room_id;
                           IF NEW.bed_number > room_capacity THEN
                               SIGNAL SQLSTATE '45000' 
                               SET MESSAGE_TEXT = 'Bed number cannot exceed room capacity';
                           END IF;
                       END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `complaint_id` int(11) NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `hostel_id` int(10) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `submission_date` datetime DEFAULT current_timestamp(),
  `resolve_date` datetime DEFAULT NULL,
  `status` enum('open','in_progress','resolved') NOT NULL DEFAULT 'open',
  `assigned_manager_id` int(11) DEFAULT NULL,
  `urgency` varchar(16) NOT NULL DEFAULT 'low',
  `notification_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`complaint_id`, `student_id`, `hostel_id`, `category`, `description`, `submission_date`, `resolve_date`, `status`, `assigned_manager_id`, `urgency`, `notification_read`) VALUES
(5, '20790520', 1, 'Plumbing', 'Water is leaking in my room', '2025-11-27 19:30:25', NULL, 'in_progress', 20791031, 'medium', 1),
(7, '20790523', 3, 'Food Service', 'Due to poor hygiene in Food Service, I have become sick', '2025-11-27 19:33:31', '2025-11-27 19:35:11', 'resolved', 20791032, 'high', 0);

-- --------------------------------------------------------

--
-- Table structure for table `complaint_classification_audit`
--

CREATE TABLE `complaint_classification_audit` (
  `id` int(11) NOT NULL,
  `complaint_id` int(11) DEFAULT NULL,
  `student_id` varchar(64) DEFAULT NULL,
  `hostel_id` int(11) DEFAULT NULL,
  `urgency` varchar(16) DEFAULT NULL,
  `topic` varchar(64) DEFAULT NULL,
  `score` float DEFAULT NULL,
  `suggested_manager_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewer_id` varchar(64) DEFAULT NULL,
  `reviewed` tinyint(1) DEFAULT 0,
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaint_classification_audit`
--

INSERT INTO `complaint_classification_audit` (`id`, `complaint_id`, `student_id`, `hostel_id`, `urgency`, `topic`, `score`, `suggested_manager_id`, `created_at`, `reviewer_id`, `reviewed`, `reviewed_at`) VALUES
(2, 5, '20790520', 1, 'medium', 'plumbing', 3, 20791031, '2025-11-27 18:30:25', 'admin', 1, '2025-11-27 18:34:02'),
(4, 7, '20790523', 3, 'low', 'general', 0, 20791032, '2025-11-27 18:33:31', 'admin', 1, '2025-11-27 18:33:51');

-- --------------------------------------------------------

--
-- Table structure for table `hostel`
--

CREATE TABLE `hostel` (
  `Hostel_id` int(10) NOT NULL,
  `Hostel_name` varchar(255) NOT NULL,
  `current_no_of_rooms` varchar(255) DEFAULT NULL,
  `No_of_rooms` varchar(255) DEFAULT NULL,
  `No_of_students` varchar(255) DEFAULT NULL,
  `tenure` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `hostel`
--

INSERT INTO `hostel` (`Hostel_id`, `Hostel_name`, `current_no_of_rooms`, `No_of_rooms`, `No_of_students`, `tenure`) VALUES
(1, 'Hostel UNO', '10', '10', NULL, 1),
(2, 'Hostel DOS', '8', '10', NULL, 3),
(3, 'Hostel TRES', '11', '10', NULL, 2),
(7, 'admin', '0', '0', '0', 0);

-- --------------------------------------------------------

--
-- Table structure for table `hostel_manager`
--

CREATE TABLE `hostel_manager` (
  `Hostel_man_id` int(10) NOT NULL,
  `Username` varchar(255) NOT NULL,
  `Fname` varchar(255) NOT NULL,
  `Lname` varchar(255) NOT NULL,
  `Mob_no` varchar(255) NOT NULL,
  `Hostel_id` int(10) NOT NULL,
  `Pwd` longtext NOT NULL,
  `Isadmin` tinyint(1) DEFAULT 0,
  `email` varchar(40) DEFAULT NULL,
  `approval_status` varchar(20) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `hostel_manager`
--

INSERT INTO `hostel_manager` (`Hostel_man_id`, `Username`, `Fname`, `Lname`, `Mob_no`, `Hostel_id`, `Pwd`, `Isadmin`, `email`, `approval_status`) VALUES
(20791020, 'admin', 'Admin', 'Admin', '9800045001', 7, '$2y$10$qm6i2nxwPgJfSMr3J1YNDOpiJI.jpDoKdQh7xPIqPIA1oLjEuwUxy', 1, 'admin@gmail.com', 'approved'),
(20791030, 'RamB', 'RamB', 'Shrestha', '9800556120', 2, '$2y$10$MLOi8a.pkF1oKWCgViJPxud8Mmy2lzEaBNEsLQGXxBuUPPv7kjIgO', 0, 'ramB@gmail.com', 'approved'),
(20791031, 'RamA', 'RamA', 'Shrestha', '9800554578', 1, '$2y$10$Km3nDsFW73hFGLBBDw4ESul.Nvb4XfzarD7cVF4mmGVoeUN2RrDg6', 0, 'ram@gmail.com', 'approved'),
(20791032, 'RamC', 'RamC', 'Shrestha', '9800554578', 3, '$2y$10$SOqRyF0S4v6Ub.PBHjrY5uzPPLYGISFYMqhNoupd2oFQPqfsT.Nn2', 0, 'ramC@gmail.com', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `leave_adjustments`
--

CREATE TABLE `leave_adjustments` (
  `adjustment_id` int(11) NOT NULL,
  `leave_id` int(11) NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `month` varchar(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `food_plan` varchar(50) NOT NULL,
  `original_food_price` decimal(10,2) NOT NULL,
  `leave_days` int(11) NOT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `food_price_reduction` decimal(10,2) NOT NULL,
  `adjusted_food_price` decimal(10,2) NOT NULL,
  `processed_date` date NOT NULL DEFAULT current_timestamp(),
  `billing_month` varchar(7) DEFAULT NULL COMMENT 'Month when adjustment is applied',
  `status` enum('pending','processed','refunded') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_applications`
--

CREATE TABLE `leave_applications` (
  `leave_id` int(11) NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `leave_type` enum('full_month','partial','food_only') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `leave_days` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `applied_date` date NOT NULL DEFAULT current_timestamp(),
  `approved_by` varchar(255) DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `food_plan` varchar(50) DEFAULT NULL,
  `original_food_price` decimal(10,2) DEFAULT NULL,
  `estimated_reduction` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notification_read` tinyint(1) DEFAULT 0,
  `manager_remarks` text DEFAULT NULL COMMENT 'Manager remarks/comments when approving/rejecting leave'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Triggers `leave_applications`
--
DELIMITER $$
CREATE TRIGGER `leave_applications_before_insert` BEFORE INSERT ON `leave_applications` FOR EACH ROW BEGIN
    IF NEW.applied_date IS NULL THEN
        SET NEW.applied_date = CURRENT_DATE();
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `leave_applications_before_update` BEFORE UPDATE ON `leave_applications` FOR EACH ROW BEGIN
    IF NEW.status = 'approved' AND OLD.status != 'approved' THEN
        SET NEW.approved_date = CURRENT_DATE();
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `leave_settings`
--

CREATE TABLE `leave_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `leave_settings`
--

INSERT INTO `leave_settings` (`setting_id`, `setting_name`, `setting_value`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'basic_food_price', '500', 'Basic food plan monthly price in Rs', 1, '2025-12-28 12:05:54', '2025-12-28 12:05:54'),
(2, 'standard_food_price', '1500', 'Standard food plan monthly price in Rs', 1, '2025-12-28 12:05:54', '2025-12-28 12:05:54'),
(3, 'premium_food_price', '2500', 'Premium food plan monthly price in Rs', 1, '2025-12-28 12:05:54', '2025-12-28 12:05:54'),
(4, 'days_in_month', '30', 'Number of days to consider for monthly calculation', 1, '2025-12-28 12:05:54', '2025-12-28 12:05:54'),
(5, 'min_leave_days', '1', 'Minimum leave days required for application', 1, '2025-12-28 12:05:54', '2025-12-28 12:05:54'),
(6, 'max_leave_days', '30', 'Maximum leave days allowed per application', 1, '2025-12-28 12:05:54', '2025-12-28 12:05:54'),
(7, 'auto_approve_days', '7', 'Leave days that get auto-approved', 1, '2025-12-28 12:05:54', '2025-12-28 12:05:54'),
(8, 'partial_leave_threshold', '15', 'Days threshold for partial vs full month leave', 1, '2025-12-28 12:05:54', '2025-12-28 12:05:54');

-- --------------------------------------------------------

--
-- Stand-in structure for view `leave_statistics`
-- (See below for the actual view)
--
CREATE TABLE `leave_statistics` (
`total_applications` bigint(21)
,`pending_applications` decimal(22,0)
,`approved_applications` decimal(22,0)
,`rejected_applications` decimal(22,0)
,`total_leave_days` decimal(32,0)
,`total_estimated_savings` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

CREATE TABLE `message` (
  `msg_id` int(10) NOT NULL,
  `sender_id` varchar(255) DEFAULT NULL,
  `receiver_id` varchar(255) DEFAULT NULL,
  `hostel_id` int(10) DEFAULT NULL,
  `subject_h` varchar(255) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `msg_date` varchar(255) DEFAULT NULL,
  `msg_time` varchar(255) DEFAULT NULL,
  `read_status` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `message`
--

INSERT INTO `message` (`msg_id`, `sender_id`, `receiver_id`, `hostel_id`, `subject_h`, `message`, `msg_date`, `msg_time`, `read_status`) VALUES
(6, '20791020', '20790520', 1, 'allocation', 'you will be allocated soon', '2025-07-29', '10:01 AM', 1),
(8, '20791020', '20790520', 1, 'allocation', 'you are allocated to room 101', '2025-07-29', '10:48 AM', 1),
(9, '20791031', '20790520', 1, 'allocation', 'you are allocated to room 101', '2025-07-29', '10:48 AM', 1),
(10, '20791030', '20790522', 2, 'allocation', 'You haaave been aloocated a room', '2025-08-08', '05:25 PM', 1),
(58, '20791032', '20790523', 3, 'Room no', 'You have been allocated to room number 106.', '2025-11-12', '08:20 AM', 1),
(59, '20791032', '20790523', 3, 'Room no', 'Your room has been changed to 302.', '2025-11-12', '08:22 AM', 1),
(60, '20790523', '20791032', 3, 'Room no', 'I want to change my room', '2025-11-12', '08:23 AM', 1),
(182, '20791030', '20790524', 2, 'Room Allocation Confirmation', 'Your room has been allocated successfully! Room: 201, Bed: 1. Hostel: Hostel DOS. Please check your student portal for details.', '2025-12-29', '07:00 PM', 1),
(183, 'system', '20791030', 2, '[Urgency: LOW] New complaint: Internet', 'A new complaint was submitted. Urgency: low. Topic: internet.', '2025-12-29', '07:16 PM', 1),
(191, '20790520', '20791031', 1, 'change_room', 'Request Type: Change_room\nStudent ID: 20790520\nStudent Name: Sujal Sthapit\nReason: change\nPreferred Room: Room 102', '2025-12-30', '10:29:42', 1),
(192, '20791030', '20790524', 2, 'Room Allocation Confirmation', 'Your room has been allocated successfully! Room: 201, Bed: 1. Hostel: Hostel DOS. Please check your student portal for details.', '2025-12-30', '03:50 PM', 0),
(193, '20791030', '20790522', 2, 'Room Allocation Confirmation', 'Your room has been allocated successfully! Room: 201, Bed: 2. Hostel: Hostel DOS. Please check your student portal for details.', '2025-12-30', '03:51 PM', 1),
(194, '20791032', '20790527', 3, 'Room Allocation Confirmation', 'Your room has been allocated successfully! Room: 302, Bed: 2. Hostel: Hostel TRES. Please check your student portal for details.', '2025-12-30', '03:56 PM', 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `monthly_adjustments`
-- (See below for the actual view)
--
CREATE TABLE `monthly_adjustments` (
`month` varchar(7)
,`total_adjustments` bigint(21)
,`total_reductions` decimal(32,2)
,`total_adjusted_prices` decimal(32,2)
,`affected_students` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `monthly_billing`
--

CREATE TABLE `monthly_billing` (
  `id` int(11) NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `billing_month` varchar(7) NOT NULL,
  `room_charge` decimal(10,2) NOT NULL,
  `food_charge` decimal(10,2) NOT NULL,
  `leave_reduction` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) GENERATED ALWAYS AS (`total_amount` - `amount_paid`) STORED,
  `status` enum('pending','partial','paid','overdue') DEFAULT 'pending',
  `due_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `payment_id` varchar(50) NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_type` enum('room_allocation','food_plan','monthly_fee','other') NOT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `purchase_order_id` varchar(100) NOT NULL,
  `token` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_gateway` enum('esewa','manual') NOT NULL DEFAULT 'esewa' COMMENT 'Payment gateway used (esewa, or manual)',
  `esewa_response` text DEFAULT NULL COMMENT 'eSewa API response data'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `payment_id`, `student_id`, `amount`, `payment_type`, `status`, `purchase_order_id`, `token`, `transaction_id`, `created_at`, `updated_at`, `payment_gateway`, `esewa_response`) VALUES
(64, 'ESEW_20260101180918_5351', '20790520', 7500.00, 'room_allocation', 'completed', 'room_allocation_20790520_20260101180918_6956aa3e9c858', NULL, ' 000DMQ8', '2026-01-01 17:09:18', '2026-01-01 17:13:16', 'esewa', NULL),
(66, 'ESEW_20260101181644_3336', '20790522', 2500.00, 'room_allocation', 'completed', 'room_allocation_20790522_20260101181644_6956abfc3b1a7', NULL, NULL, '2026-01-01 17:16:44', '2026-01-01 17:17:02', 'esewa', NULL),
(67, 'ESEW_20260101182201_5362', '20790522', 2500.00, 'room_allocation', 'completed', 'room_allocation_20790522_20260101182201_6956ad393a581', NULL, NULL, '2026-01-01 17:22:01', '2026-01-01 17:22:23', 'esewa', NULL),
(68, 'ESEW_20260101182558_5198', '20790522', 2500.00, 'room_allocation', 'completed', 'room_allocation_20790522_20260101182558_6956ae26c32d7', NULL, '000DMQF', '2026-01-01 17:25:58', '2026-01-01 17:26:18', 'esewa', NULL),
(91, 'ESEW_20260106161807_3376', '20790520', 7500.00, 'room_allocation', 'completed', 'ESEW_20260106161807_3376', NULL, '000DQX5', '2026-01-06 15:18:07', '2026-01-06 15:18:07', 'esewa', NULL),
(93, 'ESEW_20260117160531_9529', '20790522', 2500.00, 'room_allocation', 'pending', 'room_allocation_20790522_20260117160522', NULL, NULL, '2026-01-17 15:05:31', '2026-01-17 15:05:31', 'esewa', NULL),
(94, 'ESEW_20260117161359_7815', '20790522', 2500.00, 'room_allocation', 'pending', 'room_allocation_20790522_20260117161356', NULL, NULL, '2026-01-17 15:13:59', '2026-01-17 15:13:59', 'esewa', NULL),
(95, 'ESEW_20260117162514_9446', '20790522', 2500.00, 'room_allocation', 'pending', 'room_allocation_20790522_20260117162513', NULL, NULL, '2026-01-17 15:25:14', '2026-01-17 15:25:14', 'esewa', NULL),
(96, 'ESEW_20260117162515_4608', '20790522', 2500.00, 'room_allocation', 'pending', 'room_allocation_20790522_20260117162513', NULL, NULL, '2026-01-17 15:25:15', '2026-01-17 15:25:15', 'esewa', NULL),
(97, 'ESEW_20260117163600_5829', '20790522', 2500.00, 'room_allocation', 'pending', 'room_allocation_20790522_20260117163559', NULL, NULL, '2026-01-17 15:36:00', '2026-01-17 15:36:00', 'esewa', NULL),
(98, 'ESEW_20260117164117_1372', '20790522', 2500.00, 'room_allocation', 'pending', 'room_allocation_20790522_20260117164115', NULL, NULL, '2026-01-17 15:41:17', '2026-01-17 15:41:17', 'esewa', NULL),
(99, 'ESEW_20260117164438_2417', '20790522', 2500.00, 'room_allocation', 'failed', 'room_allocation_20790522_20260117164435', NULL, 'TXN-1768664678-6576', '2026-01-17 15:44:38', '2026-01-17 15:44:51', 'esewa', NULL),
(100, 'ESEW_20260117164712_9390', '20790522', 2500.00, 'room_allocation', 'failed', 'room_allocation_20790522_20260117164710', NULL, 'TXN-1768664832-4243', '2026-01-17 15:47:12', '2026-01-17 15:47:24', 'esewa', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment_gateway_settings`
--

CREATE TABLE `payment_gateway_settings` (
  `id` int(11) NOT NULL,
  `gateway_name` varchar(50) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_gateway_settings`
--

INSERT INTO `payment_gateway_settings` (`id`, `gateway_name`, `setting_key`, `setting_value`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'esewa', 'merchant_code', 'EPAYTEST', 1, '2026-01-01 09:19:26', '2026-01-01 09:29:07'),
(2, 'esewa', 'test_mode', '1', 1, '2026-01-01 09:19:26', '2026-01-01 09:19:26'),
(3, 'esewa', 'product_code', 'EPAYTEST', 1, '2026-01-01 09:19:26', '2026-01-01 09:19:26'),
(4, 'esewa', 'success_url', 'http://localhost/project/payment/esewa_success.php', 1, '2026-01-01 09:19:26', '2026-01-01 09:19:26'),
(5, 'esewa', 'failure_url', 'http://localhost/project/payment/esewa_failure.php', 1, '2026-01-01 09:19:26', '2026-01-01 09:19:26'),
(6, 'esewa', 'secret_key', '8gBm/:&EnhH.1/q', 1, '2026-01-01 09:29:07', '2026-01-01 09:29:07');

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `method_name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `gateway_name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `method_name`, `display_name`, `gateway_name`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'esewa_wallet', 'eSewa Wallet', 'esewa', 1, 1, '2026-01-01 09:19:26', '2026-01-01 09:19:26');

-- --------------------------------------------------------

--
-- Table structure for table `pending_hostel_manager`
--

CREATE TABLE `pending_hostel_manager` (
  `Hostel_man_id` int(10) NOT NULL,
  `Username` varchar(255) NOT NULL,
  `Fname` varchar(255) NOT NULL,
  `Lname` varchar(255) NOT NULL,
  `Mob_no` varchar(255) NOT NULL,
  `Hostel_id` int(10) NOT NULL,
  `Pwd` longtext NOT NULL,
  `Isadmin` tinyint(1) DEFAULT 0,
  `email` varchar(40) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `email_failed` tinyint(1) NOT NULL DEFAULT 0,
  `email_attempts` int(11) NOT NULL DEFAULT 0,
  `last_email_attempt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pending_students`
--

CREATE TABLE `pending_students` (
  `Student_id` varchar(255) NOT NULL,
  `Fname` varchar(255) NOT NULL,
  `Lname` varchar(255) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `Mob_no` varchar(255) NOT NULL,
  `Dept` varchar(255) NOT NULL,
  `Year_of_study` int(11) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Pwd` longtext NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `admission_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pricing_rules`
--

CREATE TABLE `pricing_rules` (
  `rule_id` int(11) NOT NULL,
  `bed_capacity` int(1) NOT NULL,
  `occupancy_count` int(1) NOT NULL,
  `price_multiplier` decimal(3,2) NOT NULL DEFAULT 1.00,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pricing_rules`
--

INSERT INTO `pricing_rules` (`rule_id`, `bed_capacity`, `occupancy_count`, `price_multiplier`, `description`) VALUES
(1, 1, 1, 1.00, 'Single bed - full price'),
(2, 2, 1, 0.80, '2-bed room - single occupant pays 80%'),
(3, 2, 2, 0.60, '2-bed room - both occupants pay 60% each'),
(4, 3, 1, 0.75, '3-bed room - single occupant pays 75%'),
(5, 3, 2, 0.55, '3-bed room - two occupants pay 55% each'),
(6, 3, 3, 0.45, '3-bed room - three occupants pay 45% each');

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `Room_id` int(10) NOT NULL,
  `Hostel_id` int(10) NOT NULL,
  `Room_No` int(10) NOT NULL,
  `Allocated` tinyint(1) DEFAULT 0,
  `bed_capacity` int(1) NOT NULL DEFAULT 1 COMMENT '1, 2, or 3 beds',
  `base_price` decimal(10,2) NOT NULL DEFAULT 5000.00 COMMENT 'Base price for single occupancy',
  `current_occupancy` int(1) NOT NULL DEFAULT 0 COMMENT 'Current number of occupied beds'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`Room_id`, `Hostel_id`, `Room_No`, `Allocated`, `bed_capacity`, `base_price`, `current_occupancy`) VALUES
(1, 1, 106, 0, 1, 5000.00, 0),
(2, 1, 104, 0, 1, 5000.00, 0),
(5, 1, 101, 1, 1, 5000.00, 1),
(8, 1, 102, 0, 1, 5000.00, 0),
(9, 1, 103, 0, 1, 5000.00, 0),
(11, 1, 105, 0, 1, 5000.00, 0),
(13, 1, 107, 0, 1, 5000.00, 0),
(14, 1, 108, 0, 1, 5000.00, 0),
(15, 1, 109, 0, 1, 5000.00, 0),
(16, 1, 110, 0, 1, 5000.00, 0),
(17, 2, 201, 1, 2, 5000.00, 2),
(18, 2, 202, 0, 2, 5000.00, 0),
(19, 2, 203, 0, 2, 5000.00, 0),
(20, 2, 204, 0, 2, 5000.00, 0),
(21, 2, 205, 0, 2, 5000.00, 0),
(22, 2, 206, 0, 2, 5000.00, 0),
(23, 2, 207, 0, 2, 5000.00, 0),
(24, 2, 208, 0, 2, 5000.00, 0),
(25, 2, 209, 0, 2, 5000.00, 0),
(26, 2, 210, 0, 2, 5000.00, 0),
(27, 3, 301, 0, 3, 5000.00, 0),
(28, 3, 302, 1, 3, 5000.00, 2),
(29, 3, 303, 0, 3, 5000.00, 0),
(30, 3, 304, 0, 3, 5000.00, 0),
(31, 3, 305, 0, 3, 5000.00, 0),
(32, 3, 306, 0, 3, 5000.00, 0),
(33, 3, 307, 0, 3, 5000.00, 0),
(34, 3, 308, 0, 3, 5000.00, 0),
(35, 3, 309, 0, 3, 5000.00, 0),
(36, 3, 310, 0, 3, 5000.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `Student_id` varchar(255) NOT NULL,
  `Fname` varchar(255) NOT NULL,
  `Lname` varchar(255) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `Mob_no` varchar(255) NOT NULL,
  `Dept` varchar(255) NOT NULL,
  `Year_of_study` varchar(255) NOT NULL,
  `Pwd` longtext NOT NULL,
  `Hostel_id` int(10) DEFAULT NULL,
  `Room_id` int(10) DEFAULT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `admission_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `last_payment_date` date DEFAULT NULL,
  `payment_due_date` date DEFAULT NULL,
  `total_paid` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`Student_id`, `Fname`, `Lname`, `gender`, `Mob_no`, `Dept`, `Year_of_study`, `Pwd`, `Hostel_id`, `Room_id`, `Email`, `is_verified`, `admission_date`, `start_date`, `end_date`, `last_payment_date`, `payment_due_date`, `total_paid`) VALUES
('20790520', 'Sujal', 'Sthapit', 'Male', '9863591975', 'BIM', '3', '$2y$10$jLTSDkS7XSWJCySG42Lae.NG2ZYUOOBCVQYOBaEVvCIU2k0fTzwxi', 1, 5, 'sujalsthapit@gmail.com', 1, NULL, NULL, NULL, '2026-01-06', '2026-02-06', 15000.00),
('20790522', 'Saugat', 'Shrestha', 'Male', '9863455462', 'BIM', '3', '$2y$10$iAeT09a/8oWY1E4mmfgVB.lgadFzK/t0/PBkNjg40SGc2pRRTPq9y', 2, 17, 'saugatshrestha@gmail.com', 1, '2025-12-30', '2025-12-31', '2026-07-01', '2026-01-02', '2026-02-02', 10000.00),
('20790523', 'Suzal', 'Maharjan', 'Male', '9863457516', 'BIM', '3', '$2y$10$33xnVkTV6ypjmeE77RTX5uKArhCrpZqBNeCGeMbTUXl2adpm9Qd9m', 3, 28, 'suzalmaharjan@gmail.com', 1, NULL, NULL, NULL, NULL, NULL, 0.00),
('20790524', 'Bishes', 'Maharjan', 'Male', '9845124345', 'BIM', '2', '$2y$10$DUEvw6golTyicVnq8.HQMuF.SJyL2mKdBuKcC0uXaGWy69RBf1L/m', 2, 17, 'Bishesmaharjan@gmail.com', 1, '2025-12-30', '2025-12-31', '2026-07-01', NULL, NULL, 0.00),
('20790527', 'Siddhartha', 'Shrestha', 'Male', '9863457815', 'BIM', '2', '$2y$10$XNKcFskTvnaUcE7tmoojPet8STKu.zjuctd2OH6Ll41OEVs9jKJhu', 3, 28, 'siddharthashresthagmail.com', 1, '2025-12-30', '2026-01-02', '2026-07-30', NULL, NULL, 0.00);

-- --------------------------------------------------------

--
-- Structure for view `leave_statistics`
--
DROP TABLE IF EXISTS `leave_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `leave_statistics`  AS SELECT count(0) AS `total_applications`, sum(case when `leave_applications`.`status` = 'pending' then 1 else 0 end) AS `pending_applications`, sum(case when `leave_applications`.`status` = 'approved' then 1 else 0 end) AS `approved_applications`, sum(case when `leave_applications`.`status` = 'rejected' then 1 else 0 end) AS `rejected_applications`, sum(`leave_applications`.`leave_days`) AS `total_leave_days`, sum(`leave_applications`.`estimated_reduction`) AS `total_estimated_savings` FROM `leave_applications` ;

-- --------------------------------------------------------

--
-- Structure for view `monthly_adjustments`
--
DROP TABLE IF EXISTS `monthly_adjustments`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `monthly_adjustments`  AS SELECT `leave_adjustments`.`month` AS `month`, count(0) AS `total_adjustments`, sum(`leave_adjustments`.`food_price_reduction`) AS `total_reductions`, sum(`leave_adjustments`.`adjusted_food_price`) AS `total_adjusted_prices`, count(distinct `leave_adjustments`.`student_id`) AS `affected_students` FROM `leave_adjustments` WHERE `leave_adjustments`.`status` = 'processed' GROUP BY `leave_adjustments`.`month` ORDER BY `leave_adjustments`.`month` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `application`
--
ALTER TABLE `application`
  ADD PRIMARY KEY (`Application_id`),
  ADD KEY `Student_id` (`Student_id`),
  ADD KEY `Hostel_id` (`Hostel_id`),
  ADD KEY `idx_app_preferences` (`preferred_room_id`,`preferred_bed_number`);

--
-- Indexes for table `bed_allocation`
--
ALTER TABLE `bed_allocation`
  ADD PRIMARY KEY (`allocation_id`),
  ADD UNIQUE KEY `unique_bed_assignment` (`room_id`,`bed_number`,`is_active`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`complaint_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `hostel_id` (`hostel_id`);

--
-- Indexes for table `complaint_classification_audit`
--
ALTER TABLE `complaint_classification_audit`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hostel`
--
ALTER TABLE `hostel`
  ADD PRIMARY KEY (`Hostel_id`);

--
-- Indexes for table `hostel_manager`
--
ALTER TABLE `hostel_manager`
  ADD PRIMARY KEY (`Hostel_man_id`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD KEY `Hostel_id` (`Hostel_id`);

--
-- Indexes for table `leave_adjustments`
--
ALTER TABLE `leave_adjustments`
  ADD PRIMARY KEY (`adjustment_id`),
  ADD KEY `idx_leave_id` (`leave_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_month` (`month`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_billing_month` (`billing_month`),
  ADD KEY `idx_leave_adjustments_composite` (`student_id`,`month`,`status`);

--
-- Indexes for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD PRIMARY KEY (`leave_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_applied_date` (`applied_date`),
  ADD KEY `idx_leave_dates` (`start_date`,`end_date`),
  ADD KEY `idx_leave_applications_composite` (`student_id`,`status`,`applied_date`);

--
-- Indexes for table `leave_settings`
--
ALTER TABLE `leave_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `unique_setting_name` (`setting_name`);

--
-- Indexes for table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`msg_id`),
  ADD KEY `hostel_id` (`hostel_id`);

--
-- Indexes for table `monthly_billing`
--
ALTER TABLE `monthly_billing`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_month` (`student_id`,`billing_month`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_billing_month` (`billing_month`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_id` (`payment_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_type` (`payment_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_payment_gateway` (`payment_gateway`);

--
-- Indexes for table `payment_gateway_settings`
--
ALTER TABLE `payment_gateway_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_gateway_setting` (`gateway_name`,`setting_key`),
  ADD KEY `idx_gateway_name` (`gateway_name`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_method_name` (`method_name`),
  ADD KEY `idx_gateway_name` (`gateway_name`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `pending_hostel_manager`
--
ALTER TABLE `pending_hostel_manager`
  ADD KEY `token` (`token`);

--
-- Indexes for table `pending_students`
--
ALTER TABLE `pending_students`
  ADD PRIMARY KEY (`Student_id`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `Email_2` (`Email`);

--
-- Indexes for table `pricing_rules`
--
ALTER TABLE `pricing_rules`
  ADD PRIMARY KEY (`rule_id`),
  ADD UNIQUE KEY `unique_pricing` (`bed_capacity`,`occupancy_count`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`Room_id`),
  ADD KEY `Hostel_id` (`Hostel_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`Student_id`),
  ADD KEY `Hostel_id` (`Hostel_id`),
  ADD KEY `Room_id` (`Room_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `application`
--
ALTER TABLE `application`
  MODIFY `Application_id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `bed_allocation`
--
ALTER TABLE `bed_allocation`
  MODIFY `allocation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `complaint_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `complaint_classification_audit`
--
ALTER TABLE `complaint_classification_audit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `hostel`
--
ALTER TABLE `hostel`
  MODIFY `Hostel_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `hostel_manager`
--
ALTER TABLE `hostel_manager`
  MODIFY `Hostel_man_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20791048;

--
-- AUTO_INCREMENT for table `leave_adjustments`
--
ALTER TABLE `leave_adjustments`
  MODIFY `adjustment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `leave_applications`
--
ALTER TABLE `leave_applications`
  MODIFY `leave_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `leave_settings`
--
ALTER TABLE `leave_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `message`
--
ALTER TABLE `message`
  MODIFY `msg_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=195;

--
-- AUTO_INCREMENT for table `monthly_billing`
--
ALTER TABLE `monthly_billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `payment_gateway_settings`
--
ALTER TABLE `payment_gateway_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pricing_rules`
--
ALTER TABLE `pricing_rules`
  MODIFY `rule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `room`
--
ALTER TABLE `room`
  MODIFY `Room_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `application`
--
ALTER TABLE `application`
  ADD CONSTRAINT `Application_ibfk_1` FOREIGN KEY (`Student_id`) REFERENCES `student` (`Student_id`),
  ADD CONSTRAINT `Application_ibfk_2` FOREIGN KEY (`Hostel_id`) REFERENCES `hostel` (`Hostel_id`),
  ADD CONSTRAINT `fk_app_pref_room` FOREIGN KEY (`preferred_room_id`) REFERENCES `room` (`Room_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `bed_allocation`
--
ALTER TABLE `bed_allocation`
  ADD CONSTRAINT `bed_allocation_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `room` (`Room_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bed_allocation_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`Student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bed_allocation_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`Student_id`) ON DELETE CASCADE;

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`Student_id`),
  ADD CONSTRAINT `complaints_ibfk_2` FOREIGN KEY (`hostel_id`) REFERENCES `hostel` (`Hostel_id`);

--
-- Constraints for table `hostel_manager`
--
ALTER TABLE `hostel_manager`
  ADD CONSTRAINT `Hostel_Manager_ibfk_1` FOREIGN KEY (`Hostel_id`) REFERENCES `hostel` (`Hostel_id`);

--
-- Constraints for table `leave_adjustments`
--
ALTER TABLE `leave_adjustments`
  ADD CONSTRAINT `leave_adjustments_ibfk_1` FOREIGN KEY (`leave_id`) REFERENCES `leave_applications` (`leave_id`) ON DELETE CASCADE;

--
-- Constraints for table `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `Message_ibfk_1` FOREIGN KEY (`hostel_id`) REFERENCES `hostel` (`Hostel_id`);

--
-- Constraints for table `room`
--
ALTER TABLE `room`
  ADD CONSTRAINT `Room_ibfk_1` FOREIGN KEY (`Hostel_id`) REFERENCES `hostel` (`Hostel_id`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `Student_ibfk_1` FOREIGN KEY (`Hostel_id`) REFERENCES `hostel` (`Hostel_id`),
  ADD CONSTRAINT `Student_ibfk_2` FOREIGN KEY (`Room_id`) REFERENCES `room` (`Room_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
