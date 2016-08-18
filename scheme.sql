-- MySQL dump 10.11
--
-- Host: localhost    Database: bbs
-- ------------------------------------------------------
-- Server version       5.0.95

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

--
-- Table structure for table `BoardL`
--

DROP TABLE IF EXISTS `BoardL`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `BoardL` (
  `boardname` varchar(30) NOT NULL,
  `bl` int(11) NOT NULL default '0',
  PRIMARY KEY  (`boardname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Inviter`
--

DROP TABLE IF EXISTS `Inviter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Inviter` (
  `inviter` varchar(30) NOT NULL,
  `newuserid` varchar(30) NOT NULL,
  `lastupdate` timestamp NOT NULL default CURRENT_TIMESTAMP,
  KEY `inviter` (`inviter`),
  KEY `newuserid` (`newuserid`),
  KEY `inviter_2` (`inviter`,`newuserid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Post`
--

DROP TABLE IF EXISTS `Post`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Post` (
  `postid` int(11) NOT NULL auto_increment,
  `topicid` int(11) NOT NULL,
  `replyid` int(11) NOT NULL default '0',
  `author` varchar(30) NOT NULL,
  `title` tinytext NOT NULL,
  `content` longtext NOT NULL,
  `summary` varchar(150) NOT NULL default ' ',
  `posttime` timestamp NOT NULL default '0000-00-00 00:00:00',
  `vote` int(11) NOT NULL default '0',
  `boardname` varchar(30) NOT NULL,
  `filename` varchar(30) NOT NULL,
  `flag` int(11) NOT NULL default '0',
  `signature` text NOT NULL,
  `usersign` varchar(80) NOT NULL,
  `ip` varchar(20) NOT NULL default '',
  `lastupdate` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`postid`)
) ENGINE=InnoDB AUTO_INCREMENT=132650 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `PostPart`
--

DROP TABLE IF EXISTS `PostPart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PostPart` (
  `author` varchar(30) NOT NULL,
  `postid` int(11) NOT NULL,
  `flag` int(11) NOT NULL,
  `lastlook` timestamp NOT NULL default CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Topic`
--

DROP TABLE IF EXISTS `Topic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Topic` (
  `topicid` int(11) NOT NULL auto_increment,
  `author` varchar(30) NOT NULL,
  `title` varchar(80) NOT NULL,
  `vote` int(11) NOT NULL default 0,
  `replyusernum` int(11) NOT NULL default '0',
  `replynum` int(11) NOT NULL default '0',
  `boardname` varchar(30) NOT NULL,
  `score` double(20,18) NOT NULL default '0.000000000000000000',
  `fileid` int(11) NOT NULL default '0',
  `lastupdate` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `posttime` timestamp NOT NULL default '0000-00-00 00:00:00',
  `L` int(11) NOT NULL default '0',
  `lastupdateuser` varchar(30) NOT NULL default '',
  PRIMARY KEY  (`topicid`)
) ENGINE=InnoDB AUTO_INCREMENT=53154 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TopicPart`
--

DROP TABLE IF EXISTS `TopicPart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TopicPart` (
  `author` varchar(30) NOT NULL,
  `flag` int(11) NOT NULL,
  `topicid` int(11) NOT NULL,
  `lastlook` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2014-09-17 18:02:57
