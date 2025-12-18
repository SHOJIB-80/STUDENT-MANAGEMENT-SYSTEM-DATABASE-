-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 18, 2025 at 05:51 PM
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
-- Database: `student_management_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `faculty_id` int(11) DEFAULT NULL,
  `max_students` int(11) DEFAULT 0,
  `enroll_start` datetime DEFAULT NULL,
  `enroll_end` datetime DEFAULT NULL,
  `credits` int(11) DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_code`, `course_name`, `faculty_id`, `max_students`, `enroll_start`, `enroll_end`, `credits`) VALUES
(1, 'CS101', 'Intro to Programming', 1, 55, '2025-12-02 14:01:03', '2026-01-02 14:01:03', 3),
(2, 'IT201', 'Networking Basics', 2, 20, '2025-12-02 14:01:03', '2026-01-02 14:01:03', 3),
(3, 'MATH150', 'Calculus I', 3, 40, '2025-12-02 14:01:03', '2026-01-02 14:01:03', 3),
(4, 'STA102', 'Statics', NULL, 40, NULL, NULL, 3),
(6, 'ECO101', 'Economics', NULL, 40, NULL, NULL, 3);

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `status` enum('enrolled','dropped') NOT NULL DEFAULT 'enrolled',
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `grade` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `course_id`, `status`, `enrolled_at`, `grade`) VALUES
(2, 7, 3, 'enrolled', '2025-12-10 14:09:40', '2'),
(3, 8, 1, 'enrolled', '2025-12-17 16:12:50', '4');

-- --------------------------------------------------------

--
-- Table structure for table `faculties`
--

CREATE TABLE `faculties` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculties`
--

INSERT INTO `faculties` (`id`, `name`) VALUES
(1, 'Computer Science'),
(2, 'Information Technology'),
(3, 'Mathematics');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `student_name`, `email`, `phone`, `course`, `enrollment_date`) VALUES
(2, 'STU002', 'Sarah Johnson', 'sarah@example.com', '555-0102', 'Information Technology', '2025-12-03 07:38:19'),
(4, 'STU004', 'Emma Davis', 'emma@example.com', '555-0104', 'Data Science', '2025-12-03 07:38:19'),
(5, 'STU005', 'Alex Brown', 'alex@example.com', '555-0105', 'Computer Science', '2025-12-03 07:38:19'),
(7, 'STU010', 'SHOJIB', 'shojib345@gmail.com', '01789016237', 'Computer Scince', '2025-12-10 14:08:07'),
(8, 'STU100', 'Mahi', 'mahi123@gmail.com', '01912784357', 'GEB', '2025-12-17 16:12:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(20) NOT NULL DEFAULT 'student',
  `student_ref_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `role`, `student_ref_id`) VALUES
(2, 'admin', 'admin@university.com', '$2y$10$vZ6hY7hMBZc5ExAmjFz7Yua8Yo2AD9jEyr8SLjlRE41eChcSu3KiS', '2025-12-03 08:44:13', 'admin', NULL),
(3, 'rafy', 'sh1@gmail.com', '$2y$10$ZRXrJoC349wDqL3TmQK.qu/CYUClhxxs9iTiOV/CF5gnQwHycURti', '2025-12-04 16:34:42', 'student', NULL),
(4, 'STU010', 'shojib345@gmail.com', '$2y$10$0SYWfzNVSpzBv3BK4bCjf.JQfkk/swHfaM1I26uAKn/El5xJOfp/O', '2025-12-10 14:08:07', 'student', 7),
(5, 'STU100', 'mahi@gmail.com', '$2y$10$1f/EvNUPVY/2VGugngh6cO93gEp6wNf9uLvY2F1C/7euU3LQvCRWy', '2025-12-17 16:12:30', 'student', 8);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `faculties`
--
ALTER TABLE `faculties`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_students` (`student_ref_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `faculties`
--
ALTER TABLE `faculties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_students` FOREIGN KEY (`student_ref_id`) REFERENCES `students` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
