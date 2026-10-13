-- phpMyAdmin SQL Dump
-- version 4.4.15.10
-- https://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 03, 2024 at 10:01 PM
-- Server version: 10.5.25-MariaDB-cll-lve
-- PHP Version: 5.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hd_filmuz`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kanallar`
--

CREATE TABLE IF NOT EXISTS `kanallar` (
  `id` int(11) NOT NULL,
  `channelID` varchar(255) NOT NULL,
  `title` text DEFAULT NULL,
  `link` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `left_users`
--

CREATE TABLE IF NOT EXISTS `left_users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `date` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `movie_data`
--

CREATE TABLE IF NOT EXISTS `movie_data` (
  `id` int(11) NOT NULL,
  `title` text NOT NULL,
  `caption` text NOT NULL,
  `movie_id` text NOT NULL,
  `downloads` text NOT NULL,
  `kino_ids` text NOT NULL,
  `type` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `obunalar`
--

CREATE TABLE IF NOT EXISTS `obunalar` (
  `id` int(11) NOT NULL,
  `user_id` text NOT NULL,
  `subcribe_date` text NOT NULL,
  `subcribe_enddate` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE IF NOT EXISTS `requests` (
  `user_id` int(11) NOT NULL,
  `id` varchar(255) NOT NULL,
  `chat_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `send`
--

CREATE TABLE IF NOT EXISTS `send` (
  `send_id` int(11) NOT NULL,
  `time1` text NOT NULL,
  `time2` text NOT NULL,
  `start_id` text NOT NULL,
  `stop_id` text NOT NULL,
  `admin_id` text NOT NULL,
  `message_id` text NOT NULL,
  `reply_markup` text NOT NULL,
  `edit_mess_id` text DEFAULT NULL,
  `sends_count` varchar(255) DEFAULT '0',
  `receive_count` varchar(255) DEFAULT '0',
  `statistics` text NOT NULL,
  `status` text DEFAULT NULL,
  `step` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL,
  `movieChannel` text NOT NULL,
  `sub_price` text NOT NULL,
  `status` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `users_id` int(11) NOT NULL,
  `user_id` text NOT NULL,
  `holat` text NOT NULL,
  `vaqt` text NOT NULL,
  `step` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kanallar`
--
ALTER TABLE `kanallar`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `left_users`
--
ALTER TABLE `left_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `movie_data`
--
ALTER TABLE `movie_data`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `obunalar`
--
ALTER TABLE `obunalar`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `send`
--
ALTER TABLE `send`
  ADD PRIMARY KEY (`send_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`users_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `kanallar`
--
ALTER TABLE `kanallar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `left_users`
--
ALTER TABLE `left_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `movie_data`
--
ALTER TABLE `movie_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `obunalar`
--
ALTER TABLE `obunalar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `send`
--
ALTER TABLE `send`
  MODIFY `send_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `users_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Initial seed data
--
INSERT INTO `settings` (`id`, `movieChannel`, `sub_price`, `status`) VALUES
(1, '@kinolar_07_bot', '10000', '✅')
ON DUPLICATE KEY UPDATE `id` = 1;

INSERT INTO `admins` (`id`, `user_id`) VALUES
(1, '365446274')
ON DUPLICATE KEY UPDATE `user_id` = '365446274';

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
