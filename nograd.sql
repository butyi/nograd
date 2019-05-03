-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb2+deb7u1
-- http://www.phpmyadmin.net
--
-- Hoszt: localhost
-- Létrehozás ideje: 2018. febr. 17. 17:43
-- Szerver verzió: 5.5.40
-- PHP verzió: 5.4.36-0+deb7u3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Adatbázis: `nograd`
--
DROP DATABASE `nograd`;
CREATE DATABASE `nograd` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `nograd`;

-- --------------------------------------------------------

--
-- Tábla szerkezet: `history`
--
-- Létrehozás: 2018. febr. 17. 15:42
-- Utolsó frissítés: 2018. febr. 17. 15:43
--

DROP TABLE IF EXISTS `history`;
CREATE TABLE IF NOT EXISTS `history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) NOT NULL DEFAULT '?',
  `value` int(11) NOT NULL DEFAULT 0,
  `author` varchar(256) NOT NULL DEFAULT '?',
  `time` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=53 ;

--
-- A tábla adatainak kiíratása `history`
--

INSERT INTO `history` (`id`, `name`, `value`, `author`, `time`) VALUES
(1, 'room_temp_demand', 0, '?', '2018-02-02 11:39:12'),
(2, 'room_heater_state', 0, '?', '2018-02-02 16:17:27'),
(3, 'kitchen_lamp_state', 0, '?', '2018-02-02 16:17:28'),
(4, 'room_lamp_state', 0, '?', '2018-02-02 16:17:29'),
(5, 'shower_lamp_state', 0, '?', '2018-02-02 16:17:30'),
(6, 'terrace_lamp_state', 0, '?', '2018-02-02 16:17:31');


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
