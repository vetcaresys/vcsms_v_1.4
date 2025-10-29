-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 08, 2025 at 08:23 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vcs_dbb`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `pet_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `appointment_date` datetime DEFAULT NULL,
  `status` enum('pending','approved','completed','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `clinic_id`, `pet_id`, `service_id`, `appointment_date`, `status`) VALUES
(6, 1, 1, 2, '2025-07-07 09:45:00', 'completed'),
(7, 1, 1, 2, '2025-07-30 03:27:00', 'pending'),
(8, 4, 1, 3, '2025-09-02 10:00:00', 'pending'),
(9, 1, 1, 2, '2025-08-18 08:30:00', 'completed'),
(10, 1, 1, 2, '2025-08-22 13:55:00', 'approved'),
(11, 6, 3, 5, '2025-08-24 10:00:00', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(1, 'Medicine'),
(2, 'Supplies');

-- --------------------------------------------------------

--
-- Table structure for table `clinics`
--

CREATE TABLE `clinics` (
  `clinic_id` int(11) NOT NULL,
  `parent_clinic_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `clinic_name` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_info` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `business_permit` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinics`
--

INSERT INTO `clinics` (`clinic_id`, `parent_clinic_id`, `user_id`, `clinic_name`, `address`, `contact_info`, `latitude`, `longitude`, `logo`, `business_permit`, `status`) VALUES
(1, NULL, 3, 'Beast Friend Veterinary Clinic and Petshop', 'Don Anselmo Bernad Avenue, Ozamiz, Misamis Occidental', '09853209765', 8.1487758, 123.8487536, 'uploads/1757211145_Snapchat-1198494440.jpg', NULL, 'approved'),
(2, NULL, 7, 'Purrfect Care Animal Clinic', 'FRJ3+8VQ, Barrientos St, Oroquieta City, Misamis Occidental', '099886867', 8.4694006, 123.7860918, 'uploads/1751479628_294188934_444612651011421_6970265907136733838_n.jpg', NULL, 'pending'),
(3, NULL, 4, 'Oro Woof N\' Meow Veterinary Clinic', 'Barrientos St, Oroquieta City, Misamis Occidental', '09128231337', 8.4828145, 123.8067555, 'uploads/1751479661_315124013_502591345220821_1332258268528888907_n.jpg', NULL, 'pending'),
(4, NULL, 10, 'bear', 'Ozamis, Misamis Occidental', '0999999151515', 8.2144676, 123.7689644, 'uploads/1755149739_532000988_1988635878639049_1361643519910452461_n.jpg', 'uploads/permits/1755098309_526817876_2221007224979123_7734104549475679078_n.jpg', 'approved'),
(5, NULL, 13, 'S clinic jim', 'Jimenez, Misamis Occidental', '09000000000000', 8.3365180, 123.8467524, 'uploads/1755655309_Laos.jpg', 'uploads/permits/1755655178_532000988_1988635878639049_1361643519910452461_n.jpg', 'approved'),
(6, NULL, 15, 'Zhiena May Larotin\'s Clinic', 'Ozamiz, Misamis Occidental', '09770548521', 8.1765528, 123.8610862, 'uploads/1757211374_Untitled design (2).jpg', 'uploads/permits/1755918324_6629d84c-b735-4ee5-89e2-7daab53e4b43.jpeg', 'approved'),
(7, NULL, 18, 'Poras\'s Clinic', 'Aloran, Misamis Occidental', '0999999999999999', 8.4164194, 123.8210876, 'uploads/1757217530_20241019_172330.jpg', 'uploads/permits/1755961746_download.jpeg', 'approved'),
(8, NULL, 19, 'joshua\'s Clinic', 'Palilan, Jimenez, Misamis Occidental', '0797979797', 8.1989174, 123.8610649, 'uploads/1757244756_20241018_233126.jpg', 'uploads/permits/1756454085_1.jpg', 'approved'),
(9, NULL, 21, 'Jeffry Cabalog\'s Clinic', 'Plaridel, Misamis Occidental', '12345678900000', 8.6197199, 123.7124062, 'uploads/1757326205_20241018_234236.jpg', 'uploads/permits/1757326053_20241018_233126.jpg', 'approved'),
(10, NULL, 22, 'elyana\'s Clinic', 'Clarin, Misamis Occidental', '23455', 8.1994271, 123.8612366, 'uploads/1757331526_20241115_191619.jpg', 'uploads/permits/1757331457_20241105_194719.jpg', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `clinic_schedules`
--

CREATE TABLE `clinic_schedules` (
  `schedule_id` int(11) NOT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `day_of_week` varchar(20) DEFAULT NULL,
  `open_time` time DEFAULT NULL,
  `close_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinic_schedules`
--

INSERT INTO `clinic_schedules` (`schedule_id`, `clinic_id`, `day_of_week`, `open_time`, `close_time`) VALUES
(1, 1, 'Monday', '08:00:00', '18:00:00'),
(2, 1, 'Wednesday', '08:00:00', '18:00:00'),
(3, 4, 'Monday', '08:00:00', '18:00:00'),
(4, 4, 'Tuesday', '08:00:00', '18:00:00'),
(5, 5, 'Thursday', '10:00:00', '18:00:00'),
(6, 6, 'Sunday', '08:00:00', '18:00:00'),
(7, 6, 'Saturday', '08:00:00', '18:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `clinic_services`
--

CREATE TABLE `clinic_services` (
  `service_id` int(11) NOT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `service_name` varchar(100) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinic_services`
--

INSERT INTO `clinic_services` (`service_id`, `clinic_id`, `service_name`, `duration`, `price`) VALUES
(2, 1, 'Grooming', '1 hour', 600.00),
(3, 4, 'Vaccination', '1 hour', 500.00),
(4, 5, 'Vaccination', '1 hour', 100.00),
(5, 6, 'Grooming', '40', 500.00),
(6, 6, 'Vaccination', '40', 800.00);

-- --------------------------------------------------------

--
-- Table structure for table `form_answers`
--

CREATE TABLE `form_answers` (
  `answer_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `answer` text DEFAULT NULL,
  `answered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_answers`
--

INSERT INTO `form_answers` (`answer_id`, `pet_id`, `question_id`, `staff_id`, `answer`, `answered_at`) VALUES
(1, 2, 1, 1, 'Yes', '2025-08-19 09:24:37'),
(2, 1, 1, 1, 'Yes', '2025-08-20 02:40:15'),
(3, 3, 2, 4, 'Flu', '2025-08-23 03:26:37'),
(4, 3, 3, 4, '2025-08-11', '2025-08-23 03:26:37');

-- --------------------------------------------------------

--
-- Table structure for table `form_questions`
--

CREATE TABLE `form_questions` (
  `question_id` int(11) NOT NULL,
  `form_type_id` int(11) NOT NULL,
  `question_text` varchar(255) NOT NULL,
  `input_type` enum('text','textarea','yesno','select','number','date') DEFAULT 'text',
  `options` text DEFAULT NULL,
  `linked_inventory` enum('yes','no') DEFAULT 'no'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_questions`
--

INSERT INTO `form_questions` (`question_id`, `form_type_id`, `question_text`, `input_type`, `options`, `linked_inventory`) VALUES
(1, 1, 'Is your pet sick?', 'yesno', NULL, 'no'),
(2, 3, 'Unsay sakit sa imong pet?', 'textarea', NULL, 'no'),
(3, 3, 'Unsang adlawa gasugod?', 'date', NULL, 'no'),
(4, 4, 'Nakatry na ba ni siyag vaccine?', 'yesno', NULL, 'no'),
(5, 4, 'If Yes, kanus-a?', 'date', NULL, 'no'),
(6, 4, 'Unsay gigamit na medicine', '', NULL, 'no'),
(7, 4, 'Unsay gigamit na medicine', '', NULL, 'no'),
(8, 4, 'Unsay gigamit na medicine', '', NULL, 'no'),
(9, 4, 'Unsay gigamit na medicine', '', NULL, 'no');

-- --------------------------------------------------------

--
-- Table structure for table `form_types`
--

CREATE TABLE `form_types` (
  `form_type_id` int(11) NOT NULL,
  `clinic_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_types`
--

INSERT INTO `form_types` (`form_type_id`, `clinic_id`, `name`, `description`) VALUES
(1, 1, 'Consultations', 'For consult'),
(2, 1, 'Vaccination', 'for vaccine'),
(3, 6, 'Consultations', 'Sa mga magpaconsult like walk-in'),
(4, 6, 'Vaccination', 'Para sa Anti-rabbies');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `inquiry_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `sent_date` datetime DEFAULT NULL,
  `status` enum('unread','read','resolved') DEFAULT 'unread'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `item_id` int(11) NOT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `item_name` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `status` enum('available','low_stock','out_of_stock') DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`item_id`, `clinic_id`, `item_name`, `quantity`, `expiration_date`, `status`, `category_id`) VALUES
(1, 1, 'Anti-rabbies bottle w/ injection', 100, '2026-07-17', 'available', NULL),
(3, 6, 'Paracetamol', 50, '2025-10-30', 'available', NULL),
(4, 6, 'Fever Syrup', 50, '2026-08-23', 'available', 1);

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `pet_id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `pet_name` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'alive',
  `date_of_death` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pets`
--

INSERT INTO `pets` (`pet_id`, `owner_id`, `pet_name`, `photo`, `breed`, `birth_date`, `description`, `status`, `date_of_death`) VALUES
(1, 20, 'Anyong', 'uploads/1751277682_Kiesha.jpg', 'Germany', '2024-01-03', 'Dog', 'deceased', '2025-09-06'),
(2, 20, 'Mayang', 'uploads/1757212510_20241018_234236.jpg', 'Husky', '2025-08-01', 'Dog', 'alive', NULL),
(3, 16, 'Klare', 'uploads/1757245542_20241018_233740.jpg', 'Persian', '2025-02-08', 'Cat', 'alive', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pet_records`
--

CREATE TABLE `pet_records` (
  `record_id` int(11) NOT NULL,
  `pet_id` int(11) DEFAULT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `visit_date` date DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `report_type` varchar(100) DEFAULT NULL,
  `generated_at` datetime DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `role` enum('staff','doctor') DEFAULT 'staff',
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `clinic_id`, `name`, `role`, `contact_number`, `email`, `password`) VALUES
(1, 1, 'Maria Plariza', 'staff', '0945454545', 'maria@gmail.com', '$2y$10$I9V6MBdO4CAslHVjRygze.hN4iAHz1cj7D2EsKtTIGUe68NBLCtBq'),
(2, 4, 'miming', 'staff', '454545454', 'ming@gmail.com', '$2y$10$i153.pn1J8/67Ysy8V9Rh.Zl/tK0/U4FIp/wynKxzu8Ba9x6qZuom'),
(3, 1, 'Mar', 'doctor', '0999999009099', 'marr@gmail.com', '$2y$10$C1mKE6JMnQKEB512cbq/w.WxrSytDMssNXgONFzAvEI0K/jhR/6Xy'),
(4, 6, 'Rica May Telecio', 'staff', '09709917483', 'ricamaytelecio@gmail.com', '$2y$10$PiS6S8SraFtd0Baf3jttcObQyBXGR9yKbFy.CQ81gmESWINHN55aK'),
(5, 10, 'key', 'staff', '099747475784', 'key@gmail.com', '$2y$10$rVLXjhLQ5ABWITKgz0Gr.ekyWzjgjqspjfROpdseGora/fCk.UgAy');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','staff','clinic_owner','pet_owner') DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `reset_token_hash` varchar(64) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `contact_number`, `address`, `profile_picture`, `reset_token_hash`, `reset_token_expires_at`) VALUES
(1, 'Loelyn', 'admin@gmail.com', '$2y$10$XEpmTkU/ejF32a2.9.W6Ye9Nnpq5PysBoaVdrHJmXIZDc7fAM28Gi', 'admin', '09709096518', 'Makawa', NULL, NULL, NULL),
(3, 'Zhenn', 'lar@gmail.com', '$2y$10$vYID.nbp.61zkVDv42q.4OozbFTSmHJ6l15yFh7luDU5JfCgWOccK', 'clinic_owner', '0888888888888', 'Ozamiz, Misamis Occidental', '1757211212_ustp logo.png', NULL, NULL),
(4, 'julliebert', 'simbajonjulliebert16@gmail.com', '$2y$10$NazqPatEhqn1dGE9bBp9EeCNl902jnim8QPzeLCOfqEEB23zKGw5G', 'clinic_owner', '0987654', 'Oroquieta City', 'uploads/1751272319_wegie.jpg', NULL, NULL),
(7, 'julliebert', 'ju@gmail.com', '$2y$10$C5cCxf8g5hlpdWAVzokV2eedV6QZLntxtIgksePXghZE1qc8eVwYS', 'clinic_owner', '0987654', 'Oroquieta City', 'uploads/1751272372_wegie.jpg', NULL, NULL),
(8, 'Maria Lyza Ates', 'lyza@gmail.com', '$2y$10$N3nX2oT6gj4484Lv2XqY6.zeNjViCyyTHK0TVPDD4lxZYybLI8qxS', 'pet_owner', '09090999999', 'Labo', '1757299170_20241019_172325.jpg', NULL, NULL),
(9, 'Poras', 'poras@gmail.com', '$2y$10$UfZECtW5aaG98JH0bE44C.uSURwt2xCQ.E/UfZ1cmmwQBzF4RPoS.', 'clinic_owner', '09999919191', 'panaon', 'uploads/1755095499_523881554_1276395114148173_8117072645148638693_n.jpg', NULL, NULL),
(10, 'bear', 'bear@gmail.com', '$2y$10$RRrq8cYPrA9JwYiRL0OuBecrQ4bXzZ1N/yjQ9xTcYx5J/2sRjJUpi', 'clinic_owner', '0999999151515', 'Ozamis, Misamis Occidental', '1757217391_20241018_233740.jpg', NULL, NULL),
(11, 'Dodong', 'dong@gmail.com', '$2y$10$SVy5OuoWJgOT2A2WeadBHeIilgCG4IQ3YBSjd149T.LTy/15WJNLS', 'pet_owner', '099992020202', 'Sinacaban', '1757221072_IMG_20250904_105625_115.jpg', NULL, NULL),
(13, 'Suan', 'suan@gmail.com', '$2y$10$w1zp85UcYh3ApOuKhigBv.1qkjlVdPNRYkbtra3OIMnrLcJU4prYy', 'clinic_owner', '09000000000000', 'Jimenez', 'uploads/1755655178_523881554_1276395114148173_8117072645148638693_n.jpg', NULL, NULL),
(15, 'Zhiena May Larotin', 'larotinzhienamay@gmail.com', '$2y$10$bRsjMr9otFsRoKZyH9jGtu1KX8JwwVWLovmeplrm31i5uZWtTJhF.', 'clinic_owner', '09770548521', 'Ozamiz, Misamis Occidental', '1757211390_Untitled design.png', '239daa8ccaef27df65399e343dd4212678ae4c92c26c93332b44db0701ffa6ca', '2025-08-23 06:31:20'),
(16, 'Mark Rey Embodo', 'embodo@gmail.com', '$2y$10$IL2bzxYS3OmGP2qEGztNTe4dU0BJBhBIBEdGNFcYyu8PtU3q95sFG', 'pet_owner', '0906060606060', 'Panaon, Misamis Occidental', '1757220386_image_1757190546107.jpeg', NULL, NULL),
(17, 'kkkkkk', 'k@gmail.com', '$2y$10$drvhEgOVR/4L7/nkziapa.UXFx2ASQRkmiLq1z3FBBQu3XCNHoskq', 'clinic_owner', '0909090909', 'Makawa, Misamis Occidental', 'uploads/1755961374_Laos.jpg', NULL, NULL),
(18, 'Poras', 'p@gmail.com', '$2y$10$S397e5MvyHlKvCqQumqMf./LqdkUWGSzOhET/k3iArRTwHJzvboVC', 'clinic_owner', '0999999999999999', 'Aloran, Misamis Occidental', '1757217517_20241105_194721.jpg', NULL, NULL),
(19, 'joshua', 'josh@gmail.com', '$2y$10$8UqIOE4BmJYCyvos8Xeyw.m.2A7LEMyohajJmqza7ZKeIqKv5L9M6', 'clinic_owner', '0797979797', 'Palilan, Jimenez, Misamis Occidental', '1757244692_20241018_233735.jpg', '0379b778977440812b0787274b43345acacf15897a4939d4f8ae8c7d938232d3', '2025-09-08 03:56:56'),
(20, 'Unknown', 'unknown@example.com', NULL, 'pet_owner', NULL, NULL, NULL, NULL, NULL),
(21, 'Jeffry Cabalog', 'jeffry@gmail.com', '$2y$10$QQLPeudREQYJPEW3EMGxveUCHt0v7pg1QH9tZpWTF/htTTVFBORry', 'clinic_owner', '12345678900000', 'Plaridel, Misamis Occidental', 'uploads/1757326053_20241018_233638.jpg', NULL, NULL),
(22, 'elyanas', 'el@gmail.com', '$2y$10$otW3wOVqwJubSnpSTHZ82ehT17znMZWtgv1JngpOIKcXybnQfkD9.', 'clinic_owner', '8796786959', 'Lapasan, Clarin, Misamis Occidental', '1757331457_20241019_172330.jpg', NULL, NULL),
(24, 'eden', 'ed@gmail.com', '$2y$10$kUJmvk1SsxzVYpAQuvamb.EzR9/pBKG5jgtvl7AUVr2kGL3hK8IBe', 'pet_owner', '079698858757', 'Sinacaban, Misamis Occidental', '1757338295_24690e3d-75e4-48ba-8547-a9add56f4f2f.jpg', NULL, NULL),
(25, 'loloy', 'loloy@gmail.com', '$2y$10$6.G6qj7Ew2MJd/53WzLJ4Oy0f7FO5EscFF2VVd49MdKAVifyhhz7y', 'pet_owner', '09979696875', 'Dela Paz, Panaon, Misamis Occidental', '1757338365_449477958_837625154496901_323361473445353136_n.jpg', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `clinic_id` (`clinic_id`),
  ADD KEY `pet_id` (`pet_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `clinics`
--
ALTER TABLE `clinics`
  ADD PRIMARY KEY (`clinic_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_parent_clinic` (`parent_clinic_id`);

--
-- Indexes for table `clinic_schedules`
--
ALTER TABLE `clinic_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `clinic_services`
--
ALTER TABLE `clinic_services`
  ADD PRIMARY KEY (`service_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `form_answers`
--
ALTER TABLE `form_answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `pet_id` (`pet_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `form_questions`
--
ALTER TABLE `form_questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `form_type_id` (`form_type_id`);

--
-- Indexes for table `form_types`
--
ALTER TABLE `form_types`
  ADD PRIMARY KEY (`form_type_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`inquiry_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `clinic_id` (`clinic_id`),
  ADD KEY `fk_category` (`category_id`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`pet_id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `pet_records`
--
ALTER TABLE `pet_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `pet_id` (`pet_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `generated_by` (`generated_by`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `reset_token_hash` (`reset_token_hash`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `clinics`
--
ALTER TABLE `clinics`
  MODIFY `clinic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `clinic_schedules`
--
ALTER TABLE `clinic_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `clinic_services`
--
ALTER TABLE `clinic_services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `form_answers`
--
ALTER TABLE `form_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `form_questions`
--
ALTER TABLE `form_questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `form_types`
--
ALTER TABLE `form_types`
  MODIFY `form_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `inquiry_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `pet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pet_records`
--
ALTER TABLE `pet_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `clinic_services` (`service_id`);

--
-- Constraints for table `clinics`
--
ALTER TABLE `clinics`
  ADD CONSTRAINT `clinics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_parent_clinic` FOREIGN KEY (`parent_clinic_id`) REFERENCES `clinics` (`clinic_id`) ON DELETE SET NULL;

--
-- Constraints for table `clinic_schedules`
--
ALTER TABLE `clinic_schedules`
  ADD CONSTRAINT `clinic_schedules_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Constraints for table `clinic_services`
--
ALTER TABLE `clinic_services`
  ADD CONSTRAINT `clinic_services_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Constraints for table `form_answers`
--
ALTER TABLE `form_answers`
  ADD CONSTRAINT `form_answers_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`),
  ADD CONSTRAINT `form_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `form_questions` (`question_id`),
  ADD CONSTRAINT `form_answers_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `form_questions`
--
ALTER TABLE `form_questions`
  ADD CONSTRAINT `form_questions_ibfk_1` FOREIGN KEY (`form_type_id`) REFERENCES `form_types` (`form_type_id`);

--
-- Constraints for table `form_types`
--
ALTER TABLE `form_types`
  ADD CONSTRAINT `form_types_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Constraints for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD CONSTRAINT `inquiries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `inquiries_ibfk_2` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `pet_records`
--
ALTER TABLE `pet_records`
  ADD CONSTRAINT `pet_records_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`),
  ADD CONSTRAINT `pet_records_ibfk_2` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
