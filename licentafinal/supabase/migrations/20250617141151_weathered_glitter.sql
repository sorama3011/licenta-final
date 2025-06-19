-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Database: `gusturi_romanesti`
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gusturi_romanesti`
--
CREATE DATABASE IF NOT EXISTS `gusturi_romanesti` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `gusturi_romanesti`;

-- --------------------------------------------------------

--
-- Table structure for table `utilizatori`
--

CREATE TABLE `utilizatori` (
  `id` int(11) NOT NULL,
  `prenume` varchar(50) NOT NULL,
  `nume` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `parola` varchar(255) NOT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `rol` enum('client','administrator','angajat') NOT NULL DEFAULT 'client',
  `data_inregistrare` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultima_autentificare` timestamp NULL DEFAULT NULL,
  `activ` tinyint(1) NOT NULL DEFAULT 1,
  `token_resetare` varchar(100) DEFAULT NULL,
  `expirare_token` timestamp NULL DEFAULT NULL,
  `newsletter` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utilizatori`
--

INSERT INTO `utilizatori` (`id`, `prenume`, `nume`, `email`, `parola`, `telefon`, `rol`, `data_inregistrare`, `ultima_autentificare`, `activ`, `token_resetare`, `expirare_token`, `newsletter`) VALUES
(1, 'Maria', 'Popescu', 'client@example.com', '$2y$10$Hl0QlCzBS0F9oSZiGx5YOOcgwqN0XcgQYnR.vwKHsL9TAFbjOXS0.', '+40721234567', 'client', '2024-01-01 10:00:00', '2024-01-15 08:30:00', 1, NULL, NULL, 1),
(2, 'Admin', 'Administrator', 'admin@example.com', '$2y$10$Hl0QlCzBS0F9oSZiGx5YOOcgwqN0XcgQYnR.vwKHsL9TAFbjOXS0.', '+40722345678', 'administrator', '2024-01-01 10:00:00', '2024-01-15 09:15:00', 1, NULL, NULL, 0),
(3, 'Elena', 'Ionescu', 'employee@example.com', '$2y$10$Hl0QlCzBS0F9oSZiGx5YOOcgwqN0XcgQYnR.vwKHsL9TAFbjOXS0.', '+40723456789', 'angajat', '2024-01-01 10:00:00', '2024-01-15 08:45:00', 1, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `adrese`
--

CREATE TABLE `adrese` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nume_adresa` varchar(50) DEFAULT NULL,
  `adresa` varchar(255) NOT NULL,
  `oras` varchar(50) NOT NULL,
  `judet` varchar(50) NOT NULL,
  `cod_postal` varchar(10) NOT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `implicit` tinyint(1) NOT NULL DEFAULT 0,
  `data_adaugare` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categorii`
--

CREATE TABLE `categorii` (
  `id` int(11) NOT NULL,
  `nume` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `descriere` text DEFAULT NULL,
  `imagine` varchar(255) DEFAULT NULL,
  `activ` tinyint(1) NOT NULL DEFAULT 1,
  `data_adaugare` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categorii`
--

INSERT INTO `categorii` (`id`, `nume`, `slug`, `descriere`, `imagine`, `activ`, `data_adaugare`) VALUES
(1, 'Dulcețuri & Miere', 'dulceturi', 'Gemuri, dulcețuri și miere naturală din regiunile Carpaților', 'dulceturi.jpg', 1, '2024-01-01 10:00:00'),
(2, 'Conserve & Murături', 'conserve', 'Conserve și murături tradiționale românești', 'conserve.jpg', 1, '2024-01-01 10:00:00'),
(3, 'Mezeluri', 'mezeluri', 'Cârnați, slănină și specialități afumate', 'mezeluri.jpg', 1, '2024-01-01 10:00:00'),
(4, 'Brânzeturi', 'branza', 'Brânzeturi tradiționale de vacă, oaie și capră', 'branzeturi.jpg', 1, '2024-01-01 10:00:00'),
(5, 'Băuturi', 'bauturi', 'Țuică, pălincă, vinuri și siropuri naturale', 'bauturi.jpg', 1, '2024-01-01 10:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `regiuni`
--

CREATE TABLE `regiuni` (
  `id` int(11) NOT NULL,
  `nume` varchar(50) NOT NULL,
  `descriere` text DEFAULT NULL,
  `imagine` varchar(255) DEFAULT NULL,
  `activ` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `regiuni`
--

INSERT INTO `regiuni` (`id`, `nume`, `descriere`, `imagine`, `activ`) VALUES
(1, 'Transilvania', 'Produse tradiționale din inima României', 'transilvania.jpg', 1),
(2, 'Muntenia', 'Specialități din câmpiile sudice', 'muntenia.jpg', 1),
(3, 'Maramureș', 'Produse autentice din nordul țării', 'maramures.jpg', 1),
(4, 'Banat', 'Gusturi tradiționale din vestul României', 'banat.jpg', 1),
(5, 'Oltenia', 'Specialități din sud-vestul țării', 'oltenia.jpg', 1),
(6, 'Dobrogea', 'Produse tradiționale din sud-estul României', 'dobrogea.jpg', 1),
(7, 'Crișana', 'Gusturi autentice din vestul țării', 'crisana.jpg', 1),
(8, 'Bucovina', 'Produse tradiționale din nordul Moldovei', 'bucovina.jpg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `etichete`
--

CREATE TABLE `etichete` (
  `id` int(11) NOT NULL,
  `nume` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `descriere` text DEFAULT NULL,
  `activ` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `etichete`
--

INSERT INTO `etichete` (`id`, `nume`, `slug`, `descriere`, `activ`) VALUES
(1, 'Produs de post', 'produs-de-post', 'Produse potrivite pentru perioadele de post', 1),
(2, 'Fără zahăr', 'fara-zahar', 'Produse fără zahăr adăugat', 1),
(3, 'Artizanal', 'artizanal', 'Produse realizate prin metode tradiționale', 1),
(4, 'Fără aditivi', 'fara-aditivi', 'Produse fără conservanți sau aditivi artificiali', 1),
(5, 'Ambalat manual', 'ambalat-manual', 'Produse ambalate manual de producători', 1);

-- --------------------------------------------------------

--
-- Table structure for table `produse`
--

CREATE TABLE `produse` (
  `id` int(11) NOT NULL,
  `nume` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `descriere_scurta` varchar(255) NOT NULL,
  `descriere` text NOT NULL,
  `pret` decimal(10,2) NOT NULL,
  `pret_redus` decimal(10,2) DEFAULT NULL,
  `stoc` int(11) NOT NULL DEFAULT 0,
  `cantitate` varchar(20) NOT NULL,
  `categorie_id` int(11) NOT NULL,
  `regiune_id` int(11) NOT NULL,
  `imagine` varchar(255) NOT NULL,
  `recomandat` tinyint(1) NOT NULL DEFAULT 0,
  `data_expirare` date DEFAULT NULL,
  `restrictie_varsta` tinyint(1) NOT NULL DEFAULT 0,
  `activ` tinyint(1) NOT NULL DEFAULT 1,
  `data_adaugare` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_actualizare` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `produse`
--

INSERT INTO `produse` (`id`, `nume`, `slug`, `descriere_scurta`, `descriere`, `pret`, `pret_redus`, `stoc`, `cantitate`, `categorie_id`, `regiune_id`, `imagine`, `recomandat`, `data_expirare`, `restrictie_varsta`, `activ`, `data_adaugare`) VALUES
(1, 'Dulceață de Căpșuni de Argeș', 'dulceata-capsuni-arges', 'Dulceață tradițională din căpșuni proaspete de Argeș', 'Dulceață tradițională din căpșuni proaspete cultivate în dealurile pitorești ale Argeșului. Preparată după rețete străvechi, fără conservanți artificiali, păstrând gustul autentic al căpșunilor de vară.', '18.99', NULL, 25, '350g', 1, 2, 'https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Dulceata+Capsuni', 1, '2025-10-15', 0, 1, '2024-01-01 10:00:00'),
(2, 'Zacuscă de Buzău', 'zacusca-buzau', 'Zacuscă tradițională cu vinete și ardei copți', 'Zacuscă tradițională preparată din vinete și ardei copți pe foc de lemne, după rețeta autentică din zona Buzăului. Un produs 100% natural, fără conservanți artificiali, care păstrează gustul autentic al legumelor de vară.', '15.50', NULL, 32, '450g', 2, 2, 'https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Zacusca+Buzau', 1, '2025-08-22', 0, 1, '2024-01-01 10:00:00'),
(3, 'Brânză de Burduf', 'branza-burduf-maramures', 'Brânză tradițională de oaie maturată în burduf', 'Brânză tradițională de oaie maturată în burduf de brad, preparată după rețete străvechi din Maramureș. Un produs autentic cu gust intens și aromat, specific zonei montane.', '32.00', NULL, 15, '500g', 4, 3, 'https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Branza+Burduf', 1, '2024-12-05', 0, 1, '2024-01-01 10:00:00'),
(4, 'Țuică de Prune Hunedoara', 'tuica-prune-hunedoara', 'Țuică tradițională de prune, 52% alcool', 'Țuică tradițională de prune din Hunedoara, distilată după rețete străvechi transmise din generație în generație. Cu o concentrație de 52% alcool, această țuică oferă un gust autentic și o aromă intensă specifică prunelor de Transilvania.', '45.00', NULL, 20, '500ml', 5, 1, 'https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Tuica+Prune', 0, NULL, 1, 1, '2024-01-01 10:00:00'),
(5, 'Miere de Salcâm Transilvania', 'miere-salcam-transilvania', 'Miere pură de salcâm din Munții Apuseni', 'Miere pură de salcâm din Munții Apuseni, Transilvania. Această miere cristalizată natural are un gust delicat și o aromă florală specifică, fiind considerată una dintre cele mai fine soiuri de miere din România.', '28.50', NULL, 0, '500g', 1, 1, 'https://via.placeholder.com/300x200/8B0000/FFFFFF?text=Miere+Salcam', 1, '2026-01-01', 0, 1, '2024-01-01 10:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `produse_etichete`
--

CREATE TABLE `produse_etichete` (
  `produs_id` int(11) NOT NULL,
  `eticheta_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `produse_etichete`
--

INSERT INTO `produse_etichete` (`produs_id`, `eticheta_id`) VALUES
(1, 3),
(1, 4),
(2, 1),
(2, 3),
(2, 4),
(3, 3),
(3, 5),
(4, 3),
(4, 4),
(5, 3),
(5, 4);

-- --------------------------------------------------------

--
-- Table structure for table `produse_nutritionale`
--

CREATE TABLE `produse_nutritionale` (
  `id` int(11) NOT NULL,
  `produs_id` int(11) NOT NULL,
  `valoare_energetica` varchar(50) DEFAULT NULL,
  `grasimi` varchar(20) DEFAULT NULL,
  `grasimi_saturate` varchar(20) DEFAULT NULL,
  `glucide` varchar(20) DEFAULT NULL,
  `zaharuri` varchar(20) DEFAULT NULL,
  `fibre` varchar(20) DEFAULT NULL,
  `proteine` varchar(20) DEFAULT NULL,
  `sare` varchar(20) DEFAULT NULL,
  `ingrediente` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `produse_relationate`
--

CREATE TABLE `produse_relationate` (
  `produs_id` int(11) NOT NULL,
  `produs_relationat_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `istoric_preturi`
--

CREATE TABLE `istoric_preturi` (
  `id` int(11) NOT NULL,
  `produs_id` int(11) NOT NULL,
  `pret_vechi` decimal(10,2) NOT NULL,
  `pret_nou` decimal(10,2) NOT NULL,
  `user_id` int(11) NOT NULL,
  `data_modificare` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cos_cumparaturi`
--

CREATE TABLE `cos_cumparaturi` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `produs_id` int(11) NOT NULL,
  `cantitate` int(11) NOT NULL DEFAULT 1,
  `data_adaugare` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_actualizare` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favorite`
--

CREATE TABLE `favorite` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `produs_id` int(11) NOT NULL,
  `data_adaugare` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comenzi`
--

CREATE TABLE `comenzi` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `numar_comanda` varchar(20) NOT NULL,
  `status` enum('plasata','procesata','in_livrare','livrata','anulata') NOT NULL DEFAULT 'plasata',
  `subtotal` decimal(10,2) NOT NULL,
  `transport` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `metoda_plata` enum('card','transfer','ramburs') NOT NULL,
  `status_plata` enum('in_asteptare','platita','rambursata','anulata') NOT NULL DEFAULT 'in_asteptare',
  `adresa_livrare_id` int(11) NOT NULL,
  `adresa_facturare_id` int(11) NOT NULL,
  `voucher_id` int(11) DEFAULT NULL,
  `puncte_folosite` int(11) NOT NULL DEFAULT 0,
  `puncte_castigate` int(11) NOT NULL DEFAULT 0,
  `observatii` text DEFAULT NULL,
  `data_plasare` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_procesare` timestamp NULL DEFAULT NULL,
  `data_livrare` timestamp NULL DEFAULT NULL,
  `data_actualizare` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comenzi_produse`
--

CREATE TABLE `comenzi_produse` (
  `id` int(11) NOT NULL,
  `comanda_id` int(11) NOT NULL,
  `produs_id` int(11) NOT NULL,
  `nume_produs` varchar(100) NOT NULL,
  `pret` decimal(10,2) NOT NULL,
  `cantitate` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vouchere`
--

CREATE TABLE `vouchere` (
  `id` int(11) NOT NULL,
  `cod` varchar(20) NOT NULL,
  `tip` enum('procent','valoare') NOT NULL,
  `valoare` decimal(10,2) NOT NULL,
  `minim_comanda` decimal(10,2) NOT NULL DEFAULT 0.00,
  `data_inceput` date NOT NULL,
  `data_sfarsit` date NOT NULL,
  `utilizari_maxime` int(11) DEFAULT NULL,
  `utilizari_curente` int(11) NOT NULL DEFAULT 0,
  `activ` tinyint(1) NOT NULL DEFAULT 1,
  `data_creare` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vouchere_utilizatori`
--

CREATE TABLE `vouchere_utilizatori` (
  `id` int(11) NOT NULL,
  `voucher_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `utilizat` tinyint(1) NOT NULL DEFAULT 0,
  `data_utilizare` timestamp NULL DEFAULT NULL,
  `comanda_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `puncte_fidelitate`
--

CREATE TABLE `puncte_fidelitate` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `puncte` int(11) NOT NULL DEFAULT 0,
  `data_actualizare` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `puncte_fidelitate`
--

INSERT INTO `puncte_fidelitate` (`id`, `user_id`, `puncte`, `data_actualizare`) VALUES
(1, 1, 320, '2024-01-15 10:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `tranzactii_puncte`
--

CREATE TABLE `tranzactii_puncte` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `puncte` int(11) NOT NULL,
  `tip` enum('adaugare','folosire') NOT NULL,
  `comanda_id` int(11) DEFAULT NULL,
  `descriere` varchar(255) DEFAULT NULL,
  `data_tranzactie` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recenzii`
--

CREATE TABLE `recenzii` (
  `id` int(11) NOT NULL,
  `produs_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `titlu` varchar(100) DEFAULT NULL,
  `comentariu` text DEFAULT NULL,
  `aprobat` tinyint(1) NOT NULL DEFAULT 0,
  `data_adaugare` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_aprobare` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jurnalizare`
--

CREATE TABLE `jurnalizare` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `actiune` varchar(100) NOT NULL,
  `detalii` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `data_actiune` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_abonati`
--

CREATE TABLE `newsletter_abonati` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `nume` varchar(100) DEFAULT NULL,
  `activ` tinyint(1) NOT NULL DEFAULT 1,
  `token` varchar(100) DEFAULT NULL,
  `data_abonare` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

--
-- Indexes for table `utilizatori`
--
ALTER TABLE `utilizatori`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `adrese`
--
ALTER TABLE `adrese`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categorii`
--
ALTER TABLE `categorii`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `regiuni`
--
ALTER TABLE `regiuni`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `etichete`
--
ALTER TABLE `etichete`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `produse`
--
ALTER TABLE `produse`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `categorie_id` (`categorie_id`),
  ADD KEY `regiune_id` (`regiune_id`);

--
-- Indexes for table `produse_etichete`
--
ALTER TABLE `produse_etichete`
  ADD PRIMARY KEY (`produs_id`,`eticheta_id`),
  ADD KEY `eticheta_id` (`eticheta_id`);

--
-- Indexes for table `produse_nutritionale`
--
ALTER TABLE `produse_nutritionale`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `produs_id` (`produs_id`);

--
-- Indexes for table `produse_relationate`
--
ALTER TABLE `produse_relationate`
  ADD PRIMARY KEY (`produs_id`,`produs_relationat_id`),
  ADD KEY `produs_relationat_id` (`produs_relationat_id`);

--
-- Indexes for table `istoric_preturi`
--
ALTER TABLE `istoric_preturi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produs_id` (`produs_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cos_cumparaturi`
--
ALTER TABLE `cos_cumparaturi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_produs` (`user_id`,`produs_id`),
  ADD KEY `produs_id` (`produs_id`);

--
-- Indexes for table `favorite`
--
ALTER TABLE `favorite`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_produs` (`user_id`,`produs_id`),
  ADD KEY `produs_id` (`produs_id`);

--
-- Indexes for table `comenzi`
--
ALTER TABLE `comenzi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numar_comanda` (`numar_comanda`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `adresa_livrare_id` (`adresa_livrare_id`),
  ADD KEY `adresa_facturare_id` (`adresa_facturare_id`),
  ADD KEY `voucher_id` (`voucher_id`);

--
-- Indexes for table `comenzi_produse`
--
ALTER TABLE `comenzi_produse`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comanda_id` (`comanda_id`),
  ADD KEY `produs_id` (`produs_id`);

--
-- Indexes for table `vouchere`
--
ALTER TABLE `vouchere`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cod` (`cod`);

--
-- Indexes for table `vouchere_utilizatori`
--
ALTER TABLE `vouchere_utilizatori`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voucher_id` (`voucher_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `comanda_id` (`comanda_id`);

--
-- Indexes for table `puncte_fidelitate`
--
ALTER TABLE `puncte_fidelitate`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `tranzactii_puncte`
--
ALTER TABLE `tranzactii_puncte`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `comanda_id` (`comanda_id`);

--
-- Indexes for table `recenzii`
--
ALTER TABLE `recenzii`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produs_id` (`produs_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `jurnalizare`
--
ALTER TABLE `jurnalizare`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `newsletter_abonati`
--
ALTER TABLE `newsletter_abonati`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `utilizatori`
--
ALTER TABLE `utilizatori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `adrese`
--
ALTER TABLE `adrese`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categorii`
--
ALTER TABLE `categorii`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `regiuni`
--
ALTER TABLE `regiuni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `etichete`
--
ALTER TABLE `etichete`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `produse`
--
ALTER TABLE `produse`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `produse_nutritionale`
--
ALTER TABLE `produse_nutritionale`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `istoric_preturi`
--
ALTER TABLE `istoric_preturi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cos_cumparaturi`
--
ALTER TABLE `cos_cumparaturi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favorite`
--
ALTER TABLE `favorite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comenzi`
--
ALTER TABLE `comenzi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comenzi_produse`
--
ALTER TABLE `comenzi_produse`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vouchere`
--
ALTER TABLE `vouchere`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vouchere_utilizatori`
--
ALTER TABLE `vouchere_utilizatori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `puncte_fidelitate`
--
ALTER TABLE `puncte_fidelitate`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tranzactii_puncte`
--
ALTER TABLE `tranzactii_puncte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recenzii`
--
ALTER TABLE `recenzii`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jurnalizare`
--
ALTER TABLE `jurnalizare`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `newsletter_abonati`
--
ALTER TABLE `newsletter_abonati`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `adrese`
--
ALTER TABLE `adrese`
  ADD CONSTRAINT `adrese_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilizatori` (`id`);

--
-- Constraints for table `produse`
--
ALTER TABLE `produse`
  ADD CONSTRAINT `produse_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `categorii` (`id`),
  ADD CONSTRAINT `produse_ibfk_2` FOREIGN KEY (`regiune_id`) REFERENCES `regiuni` (`id`);

--
-- Constraints for table `produse_etichete`
--
ALTER TABLE `produse_etichete`
  ADD CONSTRAINT `produse_etichete_ibfk_1` FOREIGN KEY (`produs_id`) REFERENCES `produse` (`id`),
  ADD CONSTRAINT `produse_etichete_ibfk_2` FOREIGN KEY (`eticheta_id`) REFERENCES `etichete` (`id`);

--
-- Constraints for table `produse_nutritionale`
--
ALTER TABLE `produse_nutritionale`
  ADD CONSTRAINT `produse_nutritionale_ibfk_1` FOREIGN KEY (`produs_id`) REFERENCES `produse` (`id`);

--
-- Constraints for table `produse_relationate`
--
ALTER TABLE `produse_relationate`
  ADD CONSTRAINT `produse_relationate_ibfk_1` FOREIGN KEY (`produs_id`) REFERENCES `produse` (`id`),
  ADD CONSTRAINT `produse_relationate_ibfk_2` FOREIGN KEY (`produs_relationat_id`) REFERENCES `produse` (`id`);

--
-- Constraints for table `istoric_preturi`
--
ALTER TABLE `istoric_preturi`
  ADD CONSTRAINT `istoric_preturi_ibfk_1` FOREIGN KEY (`produs_id`) REFERENCES `produse` (`id`),
  ADD CONSTRAINT `istoric_preturi_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `utilizatori` (`id`);

--
-- Constraints for table `cos_cumparaturi`
--
ALTER TABLE `cos_cumparaturi`
  ADD CONSTRAINT `cos_cumparaturi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilizatori` (`id`),
  ADD CONSTRAINT `cos_cumparaturi_ibfk_2` FOREIGN KEY (`produs_id`) REFERENCES `produse` (`id`);

--
-- Constraints for table `favorite`
--
ALTER TABLE `favorite`
  ADD CONSTRAINT `favorite_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilizatori` (`id`),
  ADD CONSTRAINT `favorite_ibfk_2` FOREIGN KEY (`produs_id`) REFERENCES `produse` (`id`);

--
-- Constraints for table `comenzi`
--
ALTER TABLE `comenzi`
  ADD CONSTRAINT `comenzi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilizatori` (`id`),
  ADD CONSTRAINT `comenzi_ibfk_2` FOREIGN KEY (`adresa_livrare_id`) REFERENCES `adrese` (`id`),
  ADD CONSTRAINT `comenzi_ibfk_3` FOREIGN KEY (`adresa_facturare_id`) REFERENCES `adrese` (`id`),
  ADD CONSTRAINT `comenzi_ibfk_4` FOREIGN KEY (`voucher_id`) REFERENCES `vouchere` (`id`);

--
-- Constraints for table `comenzi_produse`
--
ALTER TABLE `comenzi_produse`
  ADD CONSTRAINT `comenzi_produse_ibfk_1` FOREIGN KEY (`comanda_id`) REFERENCES `comenzi` (`id`),
  ADD CONSTRAINT `comenzi_produse_ibfk_2` FOREIGN KEY (`produs_id`) REFERENCES `produse` (`id`);

--
-- Constraints for table `vouchere_utilizatori`
--
ALTER TABLE `vouchere_utilizatori`
  ADD CONSTRAINT `vouchere_utilizatori_ibfk_1` FOREIGN KEY (`voucher_id`) REFERENCES `vouchere` (`id`),
  ADD CONSTRAINT `vouchere_utilizatori_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `utilizatori` (`id`),
  ADD CONSTRAINT `vouchere_utilizatori_ibfk_3` FOREIGN KEY (`comanda_id`) REFERENCES `comenzi` (`id`);

--
-- Constraints for table `puncte_fidelitate`
--
ALTER TABLE `puncte_fidelitate`
  ADD CONSTRAINT `puncte_fidelitate_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilizatori` (`id`);

--
-- Constraints for table `tranzactii_puncte`
--
ALTER TABLE `tranzactii_puncte`
  ADD CONSTRAINT `tranzactii_puncte_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilizatori` (`id`),
  ADD CONSTRAINT `tranzactii_puncte_ibfk_2` FOREIGN KEY (`comanda_id`) REFERENCES `comenzi` (`id`);

--
-- Constraints for table `recenzii`
--
ALTER TABLE `recenzii`
  ADD CONSTRAINT `recenzii_ibfk_1` FOREIGN KEY (`produs_id`) REFERENCES `produse` (`id`),
  ADD CONSTRAINT `recenzii_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `utilizatori` (`id`);

--
-- Constraints for table `jurnalizare`
--
ALTER TABLE `jurnalizare`
  ADD CONSTRAINT `jurnalizare_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilizatori` (`id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;