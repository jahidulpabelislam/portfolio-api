-- MySQL dump 10.13  Distrib 5.7.23, for Linux (x86_64)
--
-- Host: localhost    Database: portfolio
-- ------------------------------------------------------
-- Server version	5.7.23-0ubuntu0.16.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

DROP TABLE IF EXISTS `PortfolioProject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PortfolioProject` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(200) COLLATE latin1_general_ci NOT NULL,
  `LongDescription` varchar(10000) COLLATE latin1_general_ci DEFAULT NULL,
  `ShortDescription` varchar(10000) COLLATE latin1_general_ci NOT NULL,
  `Skills` varchar(200) COLLATE latin1_general_ci DEFAULT NULL,
  `Link` varchar(200) COLLATE latin1_general_ci DEFAULT NULL,
  `GitHub` varchar(200) COLLATE latin1_general_ci NOT NULL,
  `Download` varchar(200) COLLATE latin1_general_ci DEFAULT NULL,
  `Colour` varchar(20) COLLATE latin1_general_ci DEFAULT NULL,
  `Date` date NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PortfolioProject`
--

LOCK TABLES `PortfolioProject` WRITE;
/*!40000 ALTER TABLE `PortfolioProject` DISABLE KEYS */;
INSERT INTO `PortfolioProject` VALUES (1,'Test Project','Test Long Description.','Test Desc','Testing,Developing','https://jahidulpabelislam.com/','http://github.com/jahidulpabelislam/portfolio/','','Blue','2016-06-08');
/*!40000 ALTER TABLE `PortfolioProject` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PortfolioProjectImage`
--

DROP TABLE IF EXISTS `PortfolioProjectImage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PortfolioProjectImage` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ProjectID` int(11) NOT NULL,
  `File` varchar(500) COLLATE latin1_general_ci NOT NULL,
  `Number` int(11) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `File` (`File`),
  KEY `ProjectIDFK` (`ProjectID`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PortfolioProjectImage`
--

LOCK TABLES `PortfolioProjectImage` WRITE;
/*!40000 ALTER TABLE `PortfolioProjectImage` DISABLE KEYS */;
INSERT INTO `PortfolioProjectImage` VALUES (1,1,'/file/path/to/test/image/filename.png',1);
/*!40000 ALTER TABLE `PortfolioProjectImage` ENABLE KEYS */;
UNLOCK TABLES;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-10-12  7:37:58
