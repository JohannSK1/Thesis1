-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 02, 2025 at 09:53 PM
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
-- Database: `repository_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(3, 'admin', '$2y$10$EkliBVcpyTVrSEKHEV/V1.tIKIwwm34tCAOHVX/qzbMPxWPqCyCUi');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `paper_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `paper_id`, `faculty_id`, `comment_text`, `created_at`) VALUES
(1, 125, 3, 'Outdated', '2025-05-03 03:14:15');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_initial` char(1) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `department` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`id`, `first_name`, `middle_initial`, `last_name`, `email`, `password`, `department`) VALUES
(3, 'Jathniel Ira', 'D', 'Ramos', 'rjd0412@dlsud.edu.ph', '$2y$10$uEjfUiysKi2OdnuKr9r1CuoKz0kxIp8rB/oeWigFW6xUvgS9egxiS', 'CICS'),
(5, 'Niel', 'D', 'Ramos', 'idol@dlsud.edu.ph', '$2y$10$X4lx0DKW4MimmyMaYc/usuqZ5OJy1M5svRfIU7tsCtr/0zXYsGpW2', 'CICS');

-- --------------------------------------------------------

--
-- Table structure for table `papers`
--

CREATE TABLE `papers` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `date_published` date NOT NULL,
  `department` varchar(100) NOT NULL,
  `abstract` text NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `url` varchar(512) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `review_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `papers`
--

INSERT INTO `papers` (`id`, `title`, `author`, `date_published`, `department`, `abstract`, `file_path`, `url`, `uploaded_by`, `status`, `review_notes`) VALUES
(125, 'SMART INVENTORY SYSTEM:  SALES ANALYSIS USING  SARIMAX FOR A CHEMICAL  MANUFACTURING COMPANY', 'Nedtran, Encarnacion, Villanueva', '2025-03-02', 'College of Information and Computer Studies', 'Smart inventory management systems are mainly used by many businesses to track real-time stock. When tackling daily sales, time series analysis algorithms are favored over Neural Networks. The proponent Emdee Chemical Marketing Corporation is currently facing problems of not having a digital inventory system to track their data. Furthermore, SARIMAX, a time-series forecasting algorithm, offers a promising approach for forecasting sudden bulk sales of a product. This study employs Rapid Application Development (RAD) software methodology to iteratively design and implement the Smart Inventory Management System (SIMS) features. The web-based application is built using Python, TypeScript, and SQL, and is deployed in the cloud. \r\nIt features two core functionalities: inventory management and sales forecasting. The forecasting module utilizes internal sales invoice data for predictive analytics, enabling the business to anticipate fluctuating demand and optimize inventory levels. The research utilized questionnaires with Likert-scale questions and open-ended questions to evaluate the web-based application to its target beneficiary. SARIMAX was relatively accurate in predicting the sudden increase of sales since it can predict the trendline of it going up, but not to the extent of the original sales. When scaled down the lowest or most accurate split is the 80/20 split with scores of MAE, RMSE, and MAAPE of 0.27, 0.33, and 0.2078. Overall, Emdee Chem believes with the integration of the web-based application into their day-to-day operations they would have increased productivity and inventory accuracy.', '1746200624_a8e9592a21.pdf', NULL, 3, 'approved', 'PASS: ABSTRACT is present in the paper.\nPASS: REFERENCES is present in the paper.\nPASS: Paper is complete. All required chapters is present in the paper\nPASS: The paper consists of 25699 words (>= 1500) which met the required count of words.\nPASS: No unnecessary placeholders found in the paper.');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `student_number` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_initial` char(1) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `student_number`, `first_name`, `middle_initial`, `last_name`, `email`, `password`, `role`) VALUES
(5, '202030824', 'Johann', '', 'Karim', 'kjs0824@dlsud.edu.ph', '$2y$10$GYzQgjLxn/WW.nWBK36g9.eSAifV6p6M/WhU5h.B74aUd/1jR4M7W', 'user'),
(6, '201980412', 'Jathniel Ira', 'D', 'Ramos', 'rjd0412@dlsud.edu.ph', '$2y$10$No6QwcskwaN9.nmGyLufm.rsU/YEo50sVTBsFVWYbpksbiyOEbq5O', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paper_id` (`paper_id`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `papers`
--
ALTER TABLE `papers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `student_number` (`student_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `papers`
--
ALTER TABLE `papers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`paper_id`) REFERENCES `papers` (`id`),
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
