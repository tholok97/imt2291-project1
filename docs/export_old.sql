-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: 31. Jan, 2018 21:36 PM
-- Server-versjon: 10.1.28-MariaDB
-- PHP Version: 7.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `imt2291_project1_db`
--

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `Comment`
--

CREATE TABLE `Comment` (
  `cid` int(11) NOT NULL,
  `vid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `text` varchar(2000) NOT NULL,
  `timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `In_playlist`
--

CREATE TABLE `In_playlist` (
  `vid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `position` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `Maintains`
--

CREATE TABLE `Maintains` (
  `pid` int(11) NOT NULL,
  `uid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `Playlist`
--

CREATE TABLE `Playlist` (
  `pid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `thumbnail` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `Rated`
--

CREATE TABLE `Rated` (
  `vid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `rating` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `Subscribes_to`
--

CREATE TABLE `Subscribes_to` (
  `uid` int(11) NOT NULL,
  `pid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `User`
--

CREATE TABLE `User` (
  `uid` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `privilege_level` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `Video`
--

CREATE TABLE `Video` (
  `vid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` varchar(2000) NOT NULL,
  `thumbnail` blob NOT NULL,
  `uid` int(11) NOT NULL,
  `topic` varchar(255) NOT NULL,
  `course_code` varchar(255) NOT NULL,
  `timestamp` datetime NOT NULL,
  `view_count` bigint(20) NOT NULL,
  `mime` varchar(255) NOT NULL,
  `size` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `Wants_privilege`
--

CREATE TABLE `Wants_privilege` (
  `uid` int(11) NOT NULL,
  `privilege_level` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Comment`
--
ALTER TABLE `Comment`
  ADD PRIMARY KEY (`cid`),
  ADD KEY `vid` (`vid`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `In_playlist`
--
ALTER TABLE `In_playlist`
  ADD PRIMARY KEY (`vid`,`pid`),
  ADD KEY `pid` (`pid`);

--
-- Indexes for table `Maintains`
--
ALTER TABLE `Maintains`
  ADD PRIMARY KEY (`pid`,`uid`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `Playlist`
--
ALTER TABLE `Playlist`
  ADD PRIMARY KEY (`pid`);

--
-- Indexes for table `Rated`
--
ALTER TABLE `Rated`
  ADD PRIMARY KEY (`vid`,`uid`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `Subscribes_to`
--
ALTER TABLE `Subscribes_to`
  ADD PRIMARY KEY (`uid`,`pid`),
  ADD KEY `pid` (`pid`);

--
-- Indexes for table `User`
--
ALTER TABLE `User`
  ADD PRIMARY KEY (`uid`);

--
-- Indexes for table `Video`
--
ALTER TABLE `Video`
  ADD PRIMARY KEY (`vid`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `Wants_privilege`
--
ALTER TABLE `Wants_privilege`
  ADD PRIMARY KEY (`uid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Comment`
--
ALTER TABLE `Comment`
  MODIFY `cid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Playlist`
--
ALTER TABLE `Playlist`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `User`
--
ALTER TABLE `User`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Video`
--
ALTER TABLE `Video`
  MODIFY `vid` int(11) NOT NULL AUTO_INCREMENT;

--
-- Begrensninger for dumpede tabeller
--

--
-- Begrensninger for tabell `Comment`
--
ALTER TABLE `Comment`
  ADD CONSTRAINT `Comment_ibfk_1` FOREIGN KEY (`vid`) REFERENCES `Video` (`vid`),
  ADD CONSTRAINT `Comment_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `User` (`uid`);

--
-- Begrensninger for tabell `In_playlist`
--
ALTER TABLE `In_playlist`
  ADD CONSTRAINT `In_playlist_ibfk_1` FOREIGN KEY (`vid`) REFERENCES `Video` (`vid`),
  ADD CONSTRAINT `In_playlist_ibfk_2` FOREIGN KEY (`pid`) REFERENCES `Playlist` (`pid`);

--
-- Begrensninger for tabell `Maintains`
--
ALTER TABLE `Maintains`
  ADD CONSTRAINT `Maintains_ibfk_1` FOREIGN KEY (`pid`) REFERENCES `Playlist` (`pid`),
  ADD CONSTRAINT `Maintains_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `User` (`uid`);

--
-- Begrensninger for tabell `Rated`
--
ALTER TABLE `Rated`
  ADD CONSTRAINT `Rated_ibfk_1` FOREIGN KEY (`vid`) REFERENCES `Video` (`vid`),
  ADD CONSTRAINT `Rated_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `User` (`uid`);

--
-- Begrensninger for tabell `Subscribes_to`
--
ALTER TABLE `Subscribes_to`
  ADD CONSTRAINT `Subscribes_to_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `User` (`uid`),
  ADD CONSTRAINT `Subscribes_to_ibfk_2` FOREIGN KEY (`pid`) REFERENCES `Playlist` (`pid`);

--
-- Begrensninger for tabell `Video`
--
ALTER TABLE `Video`
  ADD CONSTRAINT `Video_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `User` (`uid`);

--
-- Begrensninger for tabell `Wants_privilege`
--
ALTER TABLE `Wants_privilege`
  ADD CONSTRAINT `Wants_privilege_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `User` (`uid`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
