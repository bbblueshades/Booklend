Database · SQL
-- Booklend database
-- Seed data: 11 subjects and 1 admin account (no personal or test data)
 
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;
 
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
 
--
-- Database: `booklend`
--
CREATE DATABASE IF NOT EXISTS `booklend` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `booklend`;
 
-- --------------------------------------------------------
 
--
-- Table structure for table `users`
--
 
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
--
-- Dumping data for table `users`
-- Admin login: admin@booklend.local / password  (change before deploying)
--
 
INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Admin User', 'admin@booklend.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-11-11 10:33:44');
 
-- --------------------------------------------------------
 
--
-- Table structure for table `subjects`
--
 
DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
--
-- Dumping data for table `subjects`
--
 
INSERT INTO `subjects` (`subject_id`, `subject_name`) VALUES
(4, 'Art'),
(3, 'Business & Economics'),
(5, 'Culinary Arts'),
(7, 'Health & Medicine'),
(6, 'Law'),
(10, 'Literature'),
(1, 'Mathematics'),
(9, 'Philosophy & Religion'),
(8, 'Psychology'),
(2, 'Science & Technology'),
(11, 'Social Sciences');
 
-- --------------------------------------------------------
 
--
-- Table structure for table `books`
--
 
DROP TABLE IF EXISTS `books`;
CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(150) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `book_condition` enum('As New','Fine','Good','Poor') NOT NULL DEFAULT 'Fine',
  `availability` enum('available','borrowed') NOT NULL DEFAULT 'available',
  `cover_url` varchar(255) DEFAULT NULL,
  `qr_code` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- --------------------------------------------------------
 
--
-- Table structure for table `borrow_requests`
--
 
DROP TABLE IF EXISTS `borrow_requests`;
CREATE TABLE `borrow_requests` (
  `request_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrower_id` int(11) NOT NULL,
  `status` enum('Requested','Approved','Borrowed','Returned','Rejected') NOT NULL DEFAULT 'Requested',
  `due_date` date DEFAULT NULL,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `returned_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- --------------------------------------------------------
 
--
-- Table structure for table `notifications`
--
 
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
--
-- Indexes for dumped tables
--
 
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);
 
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_name` (`subject_name`);
 
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `subject_id` (`subject_id`);
 
ALTER TABLE `borrow_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `borrower_id` (`borrower_id`);
 
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `request_id` (`request_id`);
 
--
-- AUTO_INCREMENT for dumped tables
--
 
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
 
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
 
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT;
 
ALTER TABLE `borrow_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;
 
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;
 
--
-- Constraints for dumped tables
--
 
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `books_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON UPDATE CASCADE;
 
ALTER TABLE `borrow_requests`
  ADD CONSTRAINT `borrow_requests_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `borrow_requests_ibfk_2` FOREIGN KEY (`borrower_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
 
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`request_id`) REFERENCES `borrow_requests` (`request_id`) ON DELETE SET NULL ON UPDATE CASCADE;
 
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
 
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
 
Checking for a response to your message. If Claude is still working on it, you’ll see it here.
