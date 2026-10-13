--
-- Database: cinema (Tarjima Kinolar Boti)
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:00";
SET NAMES utf8mb4;

-- --------------------------------------------------------
-- Admins jadvali
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Majburiy kanallar jadvali
CREATE TABLE IF NOT EXISTS `kanallar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channelID` varchar(255) NOT NULL,
  `title` text DEFAULT NULL,
  `link` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tark etgan foydalanuvchilar
CREATE TABLE IF NOT EXISTS `left_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) NOT NULL,
  `date` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Kinolar bazasi
CREATE TABLE IF NOT EXISTS `movie_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `caption` text NOT NULL,
  `movie_id` text NOT NULL,
  `downloads` text NOT NULL DEFAULT '0',
  `kino_ids` text NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'usual',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Kanalga a'zolik so'rovlari
CREATE TABLE IF NOT EXISTS `requests` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `id` varchar(255) NOT NULL,
  `chat_id` varchar(255) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Bot sozlamalari
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `movieChannel` text NOT NULL,
  `sub_price` text NOT NULL DEFAULT '0',
  `status` varchar(10) NOT NULL DEFAULT '✅',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Foydalanuvchilar jadvali
CREATE TABLE IF NOT EXISTS `users` (
  `users_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) NOT NULL,
  `holat` varchar(10) NOT NULL DEFAULT '✅',
  `vaqt` text NOT NULL,
  `step` varchar(255) NOT NULL DEFAULT 'none',
  PRIMARY KEY (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Boshlang'ich ma'lumotlar
INSERT INTO `settings` (`id`, `movieChannel`, `sub_price`, `status`) VALUES
(1, '@kinolar_07_bot', '0', '✅')
ON DUPLICATE KEY UPDATE `id` = 1;

INSERT INTO `admins` (`id`, `user_id`) VALUES
(1, '365446274')
ON DUPLICATE KEY UPDATE `user_id` = '365446274';
