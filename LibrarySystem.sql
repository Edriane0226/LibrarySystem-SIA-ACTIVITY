-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: localhost    Database: LibrarySystem
-- ------------------------------------------------------
-- Server version	8.0.41

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `barrowed_books`
--

DROP TABLE IF EXISTS `barrowed_books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `barrowed_books` (
  `barrowID` int NOT NULL AUTO_INCREMENT,
  `ISBN` varchar(50) DEFAULT NULL,
  `status` enum('Available','Barrowed','Returned','Overdue','Lost') DEFAULT 'Available',
  `barrower_id` varchar(20) DEFAULT NULL,
  `barrowed_date` date DEFAULT NULL,
  PRIMARY KEY (`barrowID`),
  KEY `fk_barrower` (`barrower_id`),
  CONSTRAINT `fk_barrower` FOREIGN KEY (`barrower_id`) REFERENCES `students` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `barrowed_books`
--

LOCK TABLES `barrowed_books` WRITE;
/*!40000 ALTER TABLE `barrowed_books` DISABLE KEYS */;
INSERT INTO `barrowed_books` VALUES (9,'1102-1102-0929','Returned','2311600032','2025-06-03'),(10,'1102-1102-0929','Returned','2311600032','2025-06-03'),(11,'1102-1102-0929','Returned','2311600032','2025-06-03'),(12,'9780441172719','Returned','2311600055','2025-06-03'),(13,'9780060853983','Returned','2311600011','2025-06-03'),(14,'9780441172719','Overdue','2311600011','2025-06-03'),(15,'9780307588371','Lost','2311600011','2025-06-03'),(16,'9780060853983','Returned','2311600011','2025-06-03'),(17,'9781594631931','Barrowed','2311600011','2025-06-03'),(18,'9780060853983','Returned','2311600032','2025-06-16'),(19,'9780141439518','Returned','2311600032','2025-09-17'),(20,'9780060853983','Returned','2311600032','2025-09-24'),(21,'9780060853983','Returned','2311600032','2025-10-07'),(22,'9780141439518','Returned','2311600032','2025-10-07'),(23,'978-0-452-28423-4','Returned','2311600012','2025-10-07'),(24,'978-0-452-28423-4','Returned','2311600012','2025-10-07'),(25,'978-0-590-35340-3','Barrowed','2311600012','2025-10-07'),(26,'978-0-452-28423-4','Returned','2311600218','2025-10-08'),(27,'9780060853983','Returned','2311600032','2025-10-08'),(28,'978-0-452-28423-4','Returned','2311600032','2025-10-08'),(29,'9780141439518','Returned','2311600032','2025-10-08'),(30,'9780060853983','Returned','2311600218','2025-10-08'),(31,'9780060853983','Returned','2311600020','2025-10-08'),(32,'9780060853983','Barrowed','2311600032','2025-10-08');
/*!40000 ALTER TABLE `barrowed_books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `books` (
  `title` varchar(50) DEFAULT NULL,
  `author` varchar(59) DEFAULT NULL,
  `ISBN` varchar(50) NOT NULL,
  `publication_year` date DEFAULT NULL,
  `no_copies` int DEFAULT NULL,
  `shelf_loc` varchar(20) DEFAULT NULL,
  `book_cover` varchar(250) DEFAULT NULL,
  `genre` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`ISBN`),
  UNIQUE KEY `ISBN` (`ISBN`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `books`
--

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
INSERT INTO `books` VALUES ('To Kill a Mockingbird','Harper Lee','978-0-06-112008-4','1960-07-11',5,'A1','','Educational'),('1984','Goerge Orwell','978-0-452-28423-4','1949-06-08',10,'A2','','Sci-Fi'),('Harry Potter and the Sorcerer’s Stone','J.K. Rowling','978-0-590-35340-3','1997-09-01',12,'B1','','Sci-Fi'),('The Hobbit','J.R.R. Tolkien','978-0-618-00221-3','1937-09-21',7,'B2','','Action'),('The Great Gatsby','F. Scott Fitzgerald','978-0-7432-7356-5','1925-04-10',2,'A3','','Action'),('Good Omens','Neil Gaiman & Terry Pratchett','9780060853983','2006-01-20',1,'A1','1 pic.jpeg','Comedy'),('Pride and Prejudice','Jane Austen','9780141439518','1813-01-01',1,'D4','4.jpeg','Romance'),('Gone Girl','Gillian Flynn','9780307588371','2012-01-01',1,'C3','3.jpeg','Thriller'),('Dune','Frank Herbert','9780441172719','1965-01-01',1,'E5','5.jpeg','Sci-Fi'),('The Kite Runner','Khaled Hosseini','9781594631931','2003-01-01',1,'B2','2.jpeg','Drama');
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `call_signaling`
--

DROP TABLE IF EXISTS `call_signaling`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `call_signaling` (
  `signaling_id` int NOT NULL AUTO_INCREMENT,
  `call_id` int NOT NULL,
  `signal_type` enum('offer','answer','candidate') NOT NULL,
  `payload` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`signaling_id`),
  KEY `call_id` (`call_id`),
  CONSTRAINT `call_signaling_ibfk_1` FOREIGN KEY (`call_id`) REFERENCES `video_calls` (`call_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `call_signaling`
--

LOCK TABLES `call_signaling` WRITE;
/*!40000 ALTER TABLE `call_signaling` DISABLE KEYS */;
/*!40000 ALTER TABLE `call_signaling` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `genres`
--

DROP TABLE IF EXISTS `genres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `genres` (
  `genre` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `genres`
--

LOCK TABLES `genres` WRITE;
/*!40000 ALTER TABLE `genres` DISABLE KEYS */;
INSERT INTO `genres` VALUES ('Comedy'),('Drama'),('Thriller'),('Romance'),('Sci-Fi'),('Action'),('Educational');
/*!40000 ALTER TABLE `genres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_type` enum('student','staff') NOT NULL,
  `sender_id` varchar(20) NOT NULL,
  `receiver_type` enum('student','staff') NOT NULL,
  `receiver_id` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (63,'staff','1','student','2311600032','hello','2025-09-16 02:29:59'),(64,'staff','1','student','2311600032','','2025-09-16 05:00:56'),(65,'student','2311600032','staff','1','','2025-09-16 05:17:20'),(66,'staff','1','student','2311600032','','2025-09-16 05:18:05'),(67,'staff','1','student','2311600032','','2025-09-16 05:22:46'),(68,'staff','1','student','2311600032','sdf','2025-09-16 05:22:56'),(69,'student','2311600032','staff','1','yawa','2025-09-16 05:46:52'),(70,'student','23116000','staff','1','oiii','2025-09-17 06:26:41'),(71,'staff','1','student','23116000','wertyu','2025-09-17 06:30:18'),(72,'student','23116000','staff','1','asdfghj,','2025-09-17 06:30:27'),(73,'staff','1','student','23116000','dfghjk','2025-09-17 07:22:39'),(74,'student','23116000','staff','1','ghm','2025-09-17 07:22:44');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `staff_id` varchar(20) NOT NULL,
  `first_name` varchar(20) DEFAULT NULL,
  `surname` varchar(20) DEFAULT NULL,
  `initial` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(11) DEFAULT NULL,
  PRIMARY KEY (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` VALUES ('1','Edriane','Bangonon','O','2311600032','09669145349');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `student_id` varchar(20) NOT NULL,
  `first_name` varchar(20) DEFAULT NULL,
  `surname` varchar(20) DEFAULT NULL,
  `initial` varchar(2) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(250) DEFAULT NULL,
  `phone` varchar(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `verify_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES ('1109','Edriane','Ortiz','','edriane.bangonon26@gmail.com','$2y$10$ya9jOA4BfmvV7fER9HoB4e4fk3ndVac1cY0DN12g2pQb/GI.QkLSW',NULL,'approved','a130e6aefc1cc86def57bf35ab58dea2'),('1111111111111','dfghjk','rdtfghj','q','adriennemarinay@gmail.com','$2y$10$2I1JxCM6XzCbTMMvtwAX6e.ayzEC.pLwctaIMiPZEzmdgCmBoRxUC','09669145349','approved','e0eaa363bf9d161ee95409ff6e68f49c'),('12231321','qwer','qwer','','adringmaring@gmail.com','$2y$10$lJBldAYT9hIllQK0ipNQmuCA2HaS2ReTSgbmOj7iDc2Kkdg/nb.HS','0915081499','approved','7102d81684acdfa2993e2fee7e4e7233'),('123','Edriane','werty','','edriane.bangonon26@gmail.com','$2y$10$dEkNie9S9E7WGQjtIVMT.OCKJH0y0wb3kCBVYQJF2Kx0DbHRn8Mq2',NULL,'approved','30d39d8f4a5ad210dca66ffeff108e25'),('12345','ed','riane','','edriane.bangonon26@gmail.com','$2y$10$r9rP9WfYeYvz8lP.cpKSNuPqNJEPmhBmeHNTvsVUOnKQ5xNsr9uim',NULL,'approved','90bd3eb059aa5a19ee15b35d74b03c4e'),('123456781','Ed','qwertyuiop','w','adriennemarinay@gmail.com','$2y$10$GRqbW900/vtDEsIeQiZFveTPODfp1QmmXqcMxULuQ/7xw6J5hOwKm','09669145349','approved','fbd1aa33319a7d5865124299941e132f'),('123456789','Erjay','Egaran','','adriennemarinay@gmail.com','$2y$10$gDWK7k7NNmbwh9UZa5QQ1.FokN0CP0VFsF6WlSIMX9tyijonDUXbe','','approved','2a1473db302084991102de77fea97c7e'),('123654742','qwerty','asdfg','','adringmaring@gmail.com','$2y$10$UAJfW0hjs9YH3r.2I2ytVe0HV0hqfqz7tEgPDK9f515FXqmXNf0xi','','approved','14416e8abf0416937b10773d0615ba8c'),('1236547532','qqwasdfgh','fghjgfds','','adringmaring@gmail.com','$2y$10$8voZmBNwnX5u9xHoJUlWAO9oqTMpKFqdG6c9bc9COj8kSvK/XkcWC','09150814199','approved','2748b52ff1b39d0ee6d68940495220d8'),('148597','riane','ortiz','','edriane.bangonon26@gmail.com','$2y$10$E4PvC8.E0tukVo06jnVM..TnASQZBhdwqAlQzfMEzA/dlnR9Xj28.','09669145349','approved','f7906281eb888c793f2302339686a770'),('23116000','Francis','Marinay','',NULL,'123456',NULL,'pending',NULL),('2311600001','Max','Verstappend','','maxVerstappen@gmail.com','$2y$10$cBPMGOSgdyzHV5GuHYJrm.wXHKrMhCUN3EHNBHFFv7pkh4BsbvG7C',NULL,'pending','d7ce1e7b773fd479222b5da2b7ec7f99'),('2311600002','Edriane','Verstappend','','adringmaring@gmail.com','$2y$10$X15ZNZ6viU8JM5QONKCDieSWddA2m7jbP8k0JWO8oPr7iSKJ.tji2','','approved','7a34ae34d4158c59778f253263c95f8e'),('2311600011','Peter','Pan','B',NULL,'Edriane1109',NULL,'pending',NULL),('2311600012','Erjay','Dosdos','C','erjaydosdos@gmail.com','12345678',NULL,'pending',NULL),('2311600020','Loraine','Jewel','','lorainejewelb@gmail.com','12345678',NULL,'pending',NULL),('2311600032','Edriane','Bangonon',NULL,'edriane.bangonon26@gmail.com','2311600032Ed','09669145349','approved','40e0414ae69c0e96859d5b4e79b147f0'),('2311600044','Lebrom','James','S',NULL,'Edriane1109',NULL,'pending',NULL),('2311600055','James','Brown','A',NULL,'Edriane1109',NULL,'pending',NULL),('2311600155','adrienne','qwerty','','adringmaring@gmail.com','$2y$10$cka1AMpZThUe7CX59QJxvOQoBcSBXfeMBp38.c4CG0Vq1PF5pOOVC','09150814199','approved','4a972c72c77c0934918459a94d10beb7'),('2311600218','Josh','Pecayo','','Joshuapecayo25@gmail.com','12345678',NULL,'pending',NULL),('23232323111111','adrienne','qwertyuiop','','adriennemarinay@gmail.com','$2y$10$yB/hfcCGaRgWb3ODwU90D.5h34JKPkt4kv3vUZ6jVyLT3iF17raX6','09150814199','approved','5830eb50fd9dabde0b6d3ebee1195665'),('67','er','ja','y','erjaydosdos@gmail.com','$2y$10$HDYC5O0l9UlpfgVmenJnm.BJ/DcdcYnM6pWlaFxpEMdSEHfhIUip6','09556799670','approved','abb7995625ec71bc4a0cfa0922060472'),('987456321','josh','gwapa','','ianmarinay23@gmail.com','$2y$10$3aJjdRF5/scLdEMOvS..X.gSvFtPQL74nArHafNYDpa2i8qRziesC','09776779217','approved','fc29dd6862db6bbfee76a2b7136163f5');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `video_calls`
--

DROP TABLE IF EXISTS `video_calls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `video_calls` (
  `call_id` int NOT NULL AUTO_INCREMENT,
  `caller_id` varchar(20) NOT NULL,
  `caller_type` enum('student','staff') NOT NULL,
  `receiver_id` varchar(20) NOT NULL,
  `receiver_type` enum('student','staff') NOT NULL,
  `status` enum('pending','accepted','declined','ended','missed') DEFAULT 'pending',
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `room_id` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`call_id`),
  KEY `caller_id` (`caller_id`),
  KEY `receiver_id` (`receiver_id`)
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `video_calls`
--

LOCK TABLES `video_calls` WRITE;
/*!40000 ALTER TABLE `video_calls` DISABLE KEYS */;
INSERT INTO `video_calls` VALUES (68,'2311600032','student','1','staff','accepted',NULL,NULL,'room_68c8d11300e639.83790496','2025-09-16 02:53:07'),(70,'2311600032','student','1','staff','accepted',NULL,NULL,'room_68c8d13ec55161.53423802','2025-09-16 02:53:50'),(73,'2311600032','student','1','staff','accepted',NULL,NULL,'room_68c8eab5c61980.47524595','2025-09-16 04:42:29'),(77,'test_staff_123','staff','test_student_456','student','pending',NULL,NULL,'room_68c8ed1e332686.89122796','2025-09-16 04:52:46'),(78,'1','staff','2311600032','student','accepted',NULL,NULL,'room_68c8ed6c2bba70.50377927','2025-09-16 04:54:04'),(79,'2311600032','student','1','staff','accepted',NULL,NULL,'room_68c8ef1da4c690.21572759','2025-09-16 05:01:17'),(80,'1','staff','2311600032','student','accepted',NULL,NULL,'room_68c8f437e13ea3.12103148','2025-09-16 05:23:03'),(81,'2311600032','student','1','staff','accepted',NULL,NULL,'room_68c8f5639d6de3.43165058','2025-09-16 05:28:03'),(82,'2311600032','student','1','staff','accepted',NULL,NULL,'room_68c8f58c5b3817.76024949','2025-09-16 05:28:44'),(83,'2311600032','student','1','staff','accepted',NULL,NULL,'room_68c8f5b64771f9.60982982','2025-09-16 05:29:26'),(84,'1','staff','2311600032','student','accepted',NULL,NULL,'room_68c8f69038e479.21741992','2025-09-16 05:33:04'),(85,'2311600032','student','1','staff','accepted',NULL,NULL,'room_68c8f6be908f51.12013666','2025-09-16 05:33:50'),(86,'1','staff','2311600032','student','accepted',NULL,NULL,'room_68c8f703d8d4e0.04221716','2025-09-16 05:34:59'),(87,'2311600032','student','1','staff','accepted',NULL,NULL,'room_68c8f715b0a600.75068968','2025-09-16 05:35:17'),(88,'1','staff','2311600032','student','accepted',NULL,NULL,'room_68c8f7281cac58.29097262','2025-09-16 05:35:36'),(89,'2311600032','student','1','staff','accepted',NULL,NULL,'room_68c8f7377cd4e1.98790659','2025-09-16 05:35:51'),(90,'1','staff','2311600032','student','accepted',NULL,NULL,'room_68c8f776b67c01.74639921','2025-09-16 05:36:54'),(91,'1','staff','2311600032','student','accepted',NULL,NULL,'room_68c8f78509ffb6.17668670','2025-09-16 05:37:09'),(92,'2311600032','student','1','staff','accepted',NULL,NULL,'room_68c8f918c39554.49961842','2025-09-16 05:43:52'),(93,'1','staff','2311600032','student','declined',NULL,NULL,'room_68c95c122b0723.23157770','2025-09-16 12:46:10'),(94,'1','staff','2311600032','student','accepted',NULL,NULL,'room_68c95c1e146971.25131103','2025-09-16 12:46:22'),(95,'1','staff','2311600032','student','declined',NULL,NULL,'room_68c95c2e9807d8.02125322','2025-09-16 12:46:38'),(96,'1','staff','2311600032','student','accepted',NULL,NULL,'room_68c95c3cedd6c5.52328407','2025-09-16 12:46:52'),(97,'2311600032','student','1','staff','accepted',NULL,NULL,'room_68c95dbb705e99.28692892','2025-09-16 12:53:15'),(98,'1','staff','2311600032','student','declined',NULL,NULL,'room_68c95dca5bb2f4.11160976','2025-09-16 12:53:30'),(99,'1','staff','2311600032','student','accepted',NULL,NULL,'room_68c95ddc762ef3.33339494','2025-09-16 12:53:48'),(100,'1','staff','2311600032','student','accepted',NULL,NULL,'room_68c95dfec95ca3.76530636','2025-09-16 12:54:22'),(101,'2311600032','student','1','staff','accepted',NULL,NULL,'room_68ca1189cc1b07.45770986','2025-09-17 01:40:25'),(102,'2311600032','student','1','staff','accepted',NULL,NULL,'room_68ca15b10d1952.23081619','2025-09-17 01:58:09'),(103,'1','staff','2311600032','student','declined',NULL,NULL,'room_68ca15c89f9769.00400105','2025-09-17 01:58:32'),(104,'23116000','student','1','staff','accepted',NULL,NULL,'room_68ca4f56035ac4.11007272','2025-09-17 06:04:06'),(105,'1','staff','23116000','student','accepted',NULL,NULL,'room_68ca4f6e5d8340.09427821','2025-09-17 06:04:30'),(106,'23116000','student','1','staff','accepted',NULL,NULL,'room_68ca541b45ab09.86945080','2025-09-17 06:24:27'),(107,'23116000','student','1','staff','declined',NULL,NULL,'room_68ca543c2ed976.39515083','2025-09-17 06:25:00'),(108,'23116000','student','1','staff','declined',NULL,NULL,'room_68ca5453a4d670.02918190','2025-09-17 06:25:23'),(109,'23116000','student','1','staff','accepted',NULL,NULL,'room_68ca5457bf13c1.42981293','2025-09-17 06:25:27'),(110,'23116000','student','1','staff','accepted',NULL,NULL,'room_68ca61c77c5bc6.21203332','2025-09-17 07:22:47');
/*!40000 ALTER TABLE `video_calls` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-03 21:37:35
