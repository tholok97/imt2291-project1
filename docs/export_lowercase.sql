-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 06. Feb, 2018 10:08 AM
-- Server-versjon: 10.1.25-MariaDB
-- PHP Version: 7.1.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `imt2291_project1_test`
--

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `comment`
--

CREATE TABLE `comment` (
  `cid` int(11) NOT NULL,
  `vid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `text` varchar(2000) NOT NULL,
  `timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `in_playlist`
--

CREATE TABLE `in_playlist` (
  `vid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `position` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `maintains`
--

CREATE TABLE `maintains` (
  `pid` int(11) NOT NULL,
  `uid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `playlist`
--

CREATE TABLE `playlist` (
  `pid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `thumbnail` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `rated`
--

CREATE TABLE `rated` (
  `vid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `rating` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `subscribes_to`
--

CREATE TABLE `subscribes_to` (
  `uid` int(11) NOT NULL,
  `pid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `user`
--

CREATE TABLE `user` (
  `uid` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `privilege_level` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `video`
--

CREATE TABLE `video` (
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
-- Tabellstruktur for tabell `wants_privilege`
--

CREATE TABLE `wants_privilege` (
  `uid` int(11) NOT NULL,
  `privilege_level` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`cid`),
  ADD KEY `vid` (`vid`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `in_playlist`
--
ALTER TABLE `in_playlist`
  ADD PRIMARY KEY (`vid`,`pid`),
  ADD KEY `pid` (`pid`);

--
-- Indexes for table `maintains`
--
ALTER TABLE `maintains`
  ADD PRIMARY KEY (`pid`,`uid`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `playlist`
--
ALTER TABLE `playlist`
  ADD PRIMARY KEY (`pid`);

--
-- Indexes for table `rated`
--
ALTER TABLE `rated`
  ADD PRIMARY KEY (`vid`,`uid`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `subscribes_to`
--
ALTER TABLE `subscribes_to`
  ADD PRIMARY KEY (`uid`,`pid`),
  ADD KEY `pid` (`pid`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`uid`);

--
-- Indexes for table `video`
--
ALTER TABLE `video`
  ADD PRIMARY KEY (`vid`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `wants_privilege`
--
ALTER TABLE `wants_privilege`
  ADD PRIMARY KEY (`uid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comment`
--
ALTER TABLE `comment`
  MODIFY `cid` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `playlist`
--
ALTER TABLE `playlist`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `video`
--
ALTER TABLE `video`
  MODIFY `vid` int(11) NOT NULL AUTO_INCREMENT;
--
-- Begrensninger for dumpede tabeller
--

--
-- Begrensninger for tabell `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `Comment_ibfk_1` FOREIGN KEY (`vid`) REFERENCES `video` (`vid`),
  ADD CONSTRAINT `Comment_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `user` (`uid`);

--
-- Begrensninger for tabell `in_playlist`
--
ALTER TABLE `in_playlist`
  ADD CONSTRAINT `In_playlist_ibfk_1` FOREIGN KEY (`vid`) REFERENCES `video` (`vid`),
  ADD CONSTRAINT `In_playlist_ibfk_2` FOREIGN KEY (`pid`) REFERENCES `playlist` (`pid`);

--
-- Begrensninger for tabell `maintains`
--
ALTER TABLE `maintains`
  ADD CONSTRAINT `Maintains_ibfk_1` FOREIGN KEY (`pid`) REFERENCES `playlist` (`pid`),
  ADD CONSTRAINT `Maintains_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `user` (`uid`);

--
-- Begrensninger for tabell `rated`
--
ALTER TABLE `rated`
  ADD CONSTRAINT `Rated_ibfk_1` FOREIGN KEY (`vid`) REFERENCES `video` (`vid`),
  ADD CONSTRAINT `Rated_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `user` (`uid`);

--
-- Begrensninger for tabell `subscribes_to`
--
ALTER TABLE `subscribes_to`
  ADD CONSTRAINT `Subscribes_to_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `user` (`uid`),
  ADD CONSTRAINT `Subscribes_to_ibfk_2` FOREIGN KEY (`pid`) REFERENCES `playlist` (`pid`);

--
-- Begrensninger for tabell `video`
--
ALTER TABLE `video`
  ADD CONSTRAINT `Video_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `user` (`uid`);

--
-- Begrensninger for tabell `wants_privilege`
--
ALTER TABLE `wants_privilege`
  ADD CONSTRAINT `Wants_privilege_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `user` (`uid`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
