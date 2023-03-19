-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Czas generowania: 11 Lip 2022, 22:14
-- Wersja serwera: 10.4.24-MariaDB
-- Wersja PHP: 7.4.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Baza danych: `rentito`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `brand`
--

CREATE TABLE `brand` (
  `id_brand` int(11) NOT NULL,
  `name` varchar(30) COLLATE utf8_polish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--	
-- Zrzut danych tabeli `brand`	
--	
INSERT INTO `brand` (`id_brand`, `name`) VALUES	
(1, 'Skoda'),	
(2, 'Volkswagen'),
(3, 'BMW'),	
(4, 'Audi'),	
(5, 'Mazda'),	
(6, 'Ford'),	
(7, 'Fiat'),	
(8, 'Alfa Romeo'),	
(9, 'Hyundai'),	
(10, 'Subaru');	

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `car`
--

CREATE TABLE `car` (
  `id_car` int(11) NOT NULL,
  `id_brand` int(11) NOT NULL,
  `model` varchar(50) COLLATE utf8_polish_ci NOT NULL,
  `year_of_manufacture` year(4) NOT NULL,
  `id_car_type` int(11) NOT NULL,
  `description` varchar(300) COLLATE utf8_polish_ci NOT NULL,
  `daily_rental_price` decimal(10,2) NOT NULL,
  `created_on` datetime NOT NULL DEFAULT current_timestamp(),
  `is_ready` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `car_type`
--

CREATE TABLE `car_type` (
  `id_car_type` int(11) NOT NULL,
  `name` varchar(20) COLLATE utf8_polish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--	
-- Zrzut danych tabeli `car_type`	
--	
INSERT INTO `car_type` (`id_car_type`, `name`) VALUES	
(1, 'Sedan'),	
(2, 'SUV'),
(3, 'Kabriolet'),	
(4, 'Hatchback'),	
(5, 'Minivan'),	
(6, 'Kombi');	

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `image`
--

CREATE TABLE `image` (
  `id_image` int(11) NOT NULL,
  `id_car` int(11) NOT NULL,
  `url` varchar(300) COLLATE utf8_polish_ci NOT NULL,
  `is_main` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `rental`
--

CREATE TABLE `rental` (
  `id_rental` int(11) NOT NULL,
  `id_car` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `rental_start` datetime NOT NULL,
  `rental_end` datetime NOT NULL,
  `created_on` datetime NOT NULL DEFAULT current_timestamp(),
  `is_returned` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `name` varchar(20) COLLATE utf8_polish_ci NOT NULL,
  `surname` varchar(30) COLLATE utf8_polish_ci NOT NULL,
  `birthdate` date NOT NULL,
  `email` varchar(40) COLLATE utf8_polish_ci NOT NULL,
  `phone_number` varchar(11) COLLATE utf8_polish_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_polish_ci NOT NULL,
  `created_on` datetime NOT NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `brand`
--
ALTER TABLE `brand`
  ADD PRIMARY KEY (`id_brand`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeksy dla tabeli `car`
--
ALTER TABLE `car`
  ADD PRIMARY KEY (`id_car`),
  ADD KEY `id_brand` (`id_brand`),
  ADD KEY `id_car_type` (`id_car_type`);

--
-- Indeksy dla tabeli `car_type`
--
ALTER TABLE `car_type`
  ADD PRIMARY KEY (`id_car_type`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeksy dla tabeli `image`
--
ALTER TABLE `image`
  ADD PRIMARY KEY (`id_image`),
  ADD KEY `id_car` (`id_car`);

--
-- Indeksy dla tabeli `rental`
--
ALTER TABLE `rental`
  ADD PRIMARY KEY (`id_rental`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_car` (`id_car`);

--
-- Indeksy dla tabeli `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT dla zrzuconych tabel
--

--
-- AUTO_INCREMENT dla tabeli `brand`
--
ALTER TABLE `brand`
  MODIFY `id_brand` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT dla tabeli `car`
--
ALTER TABLE `car`
  MODIFY `id_car` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `car_type`
--
ALTER TABLE `car_type`
  MODIFY `id_car_type` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT dla tabeli `image`
--
ALTER TABLE `image`
  MODIFY `id_image` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `rental`
--
ALTER TABLE `rental`
  MODIFY `id_rental` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ograniczenia dla zrzutów tabel
--

--
-- Ograniczenia dla tabeli `car`
--
ALTER TABLE `car`
  ADD CONSTRAINT `car_ibfk_1` FOREIGN KEY (`id_brand`) REFERENCES `brand` (`id_brand`),
  ADD CONSTRAINT `car_ibfk_2` FOREIGN KEY (`id_car_type`) REFERENCES `car_type` (`id_car_type`);

--
-- Ograniczenia dla tabeli `image`
--
ALTER TABLE `image`
  ADD CONSTRAINT `image_ibfk_1` FOREIGN KEY (`id_car`) REFERENCES `car` (`id_car`) ON DELETE CASCADE;

--
-- Ograniczenia dla tabeli `rental`
--
ALTER TABLE `rental`
  ADD CONSTRAINT `rental_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`),
  ADD CONSTRAINT `rental_ibfk_2` FOREIGN KEY (`id_car`) REFERENCES `car` (`id_car`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
