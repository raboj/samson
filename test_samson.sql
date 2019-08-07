-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 07, 2019 at 10:49 PM
-- Server version: 5.7.27-0ubuntu0.16.04.1
-- PHP Version: 7.0.33-0ubuntu0.16.04.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `test_samson`
--

-- --------------------------------------------------------

--
-- Table structure for table `a_category`
--

CREATE TABLE `a_category` (
  `id` int(11) NOT NULL,
  `id_parent` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `a_category`
--

INSERT INTO `a_category` (`id`, `id_parent`, `code`, `name`) VALUES
(201, 0, '', 'Бумага'),
(202, 0, '', 'Принтеры'),
(203, 202, '', 'МФУ');

-- --------------------------------------------------------

--
-- Table structure for table `a_price`
--

CREATE TABLE `a_price` (
  `id_product` int(11) NOT NULL,
  `price_type` varchar(255) NOT NULL,
  `price` decimal(8,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `a_price`
--

INSERT INTO `a_price` (`id_product`, `price_type`, `price`) VALUES
(487, 'Базовая', '11.50'),
(487, 'Москва', '12.50'),
(488, 'Базовая', '18.50'),
(488, 'Москва', '22.50'),
(489, 'Базовая', '3010.00'),
(489, 'Москва', '3500.00'),
(490, 'Базовая', '3310.00'),
(490, 'Москва', '2999.00');

-- --------------------------------------------------------

--
-- Table structure for table `a_product`
--

CREATE TABLE `a_product` (
  `id` int(11) NOT NULL,
  `code` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `a_product`
--

INSERT INTO `a_product` (`id`, `code`, `name`) VALUES
(487, 201, 'Бумага А4'),
(488, 202, 'Бумага А3'),
(489, 302, 'Принтер Canon'),
(490, 305, 'Принтер HP');

-- --------------------------------------------------------

--
-- Table structure for table `a_product_category`
--

CREATE TABLE `a_product_category` (
  `id_category` int(11) NOT NULL,
  `id_product` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `a_product_category`
--

INSERT INTO `a_product_category` (`id_category`, `id_product`) VALUES
(201, 487),
(201, 488),
(202, 489),
(203, 489),
(202, 490),
(203, 490);

-- --------------------------------------------------------

--
-- Table structure for table `a_property`
--

CREATE TABLE `a_property` (
  `id_product` int(11) NOT NULL,
  `property` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `atribut_property` varchar(255) NOT NULL,
  `atribut_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `a_property`
--

INSERT INTO `a_property` (`id_product`, `property`, `value`, `atribut_property`, `atribut_value`) VALUES
(487, 'Плотность', '100', '', ''),
(487, 'Белизна', '150', 'ЕдИзм', '%'),
(488, 'Плотность', '90', '', ''),
(488, 'Белизна', '100', 'ЕдИзм', '%'),
(489, 'Формат', 'A4', '', ''),
(489, 'Формат', 'A3', '', ''),
(489, 'Тип', 'Лазерный', '', ''),
(490, 'Формат', 'A3', '', ''),
(490, 'Тип', 'Лазерный', '', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `a_category`
--
ALTER TABLE `a_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `a_product`
--
ALTER TABLE `a_product`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `a_category`
--
ALTER TABLE `a_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=204;
--
-- AUTO_INCREMENT for table `a_product`
--
ALTER TABLE `a_product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=491;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
