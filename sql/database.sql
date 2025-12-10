-- TrackBox/sql/database.sql

-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.5.0.6677
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Criar o banco de dados se ele não existir
CREATE DATABASE IF NOT EXISTS `trackbox` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `trackbox`;

-- Tabela `users`
-- Removidas as colunas `is_admin` e `share_code`
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela `countries`
CREATE TABLE IF NOT EXISTS `countries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `country_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
  `continent` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_fake_prone` tinyint(1) NOT NULL DEFAULT '0', -- Indica se o país é conhecido por falsificações
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir dados na tabela `countries`
INSERT INTO `countries` (`country_name`, `continent`, `is_fake_prone`) VALUES
('Estados Unidos', 'América do Norte', 0),
('Canadá', 'América do Norte', 0),
('México', 'América do Norte', 0),
('Brasil', 'América do Sul', 0),
('Argentina', 'América do Sul', 1),
('Reino Unido', 'Europa', 0),
('Alemanha', 'Europa', 0),
('França', 'Europa', 0),
('Japão', 'Ásia', 0),
('China', 'Ásia', 1),
('Rússia', 'Europa/Ásia', 1),
('Austrália', 'Oceania', 0),
('África do Sul', 'África', 0);

-- Tabela `disks`
CREATE TABLE IF NOT EXISTS `disks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` enum('CD','LP','BoxSet') COLLATE utf8mb4_unicode_ci NOT NULL,
  `artist` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `album_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` int DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_id` int DEFAULT NULL,
  `is_imported` tinyint(1) NOT NULL DEFAULT '0',
  `edition` enum('primeira_edicao','reedicao') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'primeira_edicao',
  `condition_disk` enum('G','G+','VG','VG+','E','E+','Mint') COLLATE utf8mb4_unicode_ci NOT NULL,
  `condition_cover` enum('G','G+','VG','VG+','E','E+','Mint') COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_sealed` tinyint(1) NOT NULL DEFAULT '0',
  `image_path` text COLLATE utf8mb4_unicode_ci, -- Caminho para a imagem da capa do disco
  `is_favorite` tinyint(1) NOT NULL DEFAULT '0', -- Indica se o disco é favorito
  `observations` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_disk_user` (`user_id`),
  KEY `fk_disk_country` (`country_id`),
  CONSTRAINT `fk_disk_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_disk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela `disk_extras`
CREATE TABLE IF NOT EXISTS `disk_extras` (
  `disk_id` int NOT NULL,
  `has_booklet` tinyint(1) NOT NULL DEFAULT '0',
  `has_poster` tinyint(1) NOT NULL DEFAULT '0',
  `has_photos` tinyint(1) NOT NULL DEFAULT '0',
  `has_extra_disk` tinyint(1) NOT NULL DEFAULT '0',
  `has_lyrics` tinyint(1) NOT NULL DEFAULT '0',
  `other_extras` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`disk_id`),
  CONSTRAINT `fk_disk_extras_disk` FOREIGN KEY (`disk_id`) REFERENCES `disks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela `boxset_details`
CREATE TABLE IF NOT EXISTS `boxset_details` (
  `disk_id` int NOT NULL,
  `is_limited_edition` tinyint(1) NOT NULL DEFAULT '0',
  `edition_number` int DEFAULT NULL,
  `total_editions` int DEFAULT NULL,
  `special_items` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`disk_id`),
  CONSTRAINT `fk_boxset_details_disk` FOREIGN KEY (`disk_id`) REFERENCES `disks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela `activity_log`
-- Mantida para registro de atividades do usuário, sem contexto de admin
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_activity_user` (`user_id`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela `deleted_disks_backup`
-- Mantida para fins de backup de discos excluídos
CREATE TABLE IF NOT EXISTS `deleted_disks_backup` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `disk_data` longtext COLLATE utf8mb4_unicode_ci NOT NULL, -- JSON do disco excluído
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_deleted_user` (`user_id`),
  CONSTRAINT `fk_deleted_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;