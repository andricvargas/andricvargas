-- MySQL dump 10.13  Distrib 8.0.41, for Linux (x86_64)
--
-- Host: localhost    Database: saludos_db
-- ------------------------------------------------------
-- Server version	8.0.41-0ubuntu0.22.04.1

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
-- Table structure for table `saludos`
--

DROP TABLE IF EXISTS `saludos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saludos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `saludo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` datetime NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saludos`
--

LOCK TABLES `saludos` WRITE;
/*!40000 ALTER TABLE `saludos` DISABLE KEYS */;
INSERT INTO `saludos` VALUES (1,'test','2025-02-28 16:50:18','38.187.18.9'),(2,'Está es una prueba de envío desde bitel','2025-02-28 16:54:37','181.176.112.158'),(3,'$query = \"SELECT * FROM users WHERE username = \'$input\' AND password = \'$password\'\";','2025-02-28 16:59:43','181.176.112.158'),(4,'\'; DROP TABLE saludos; --','2025-02-28 17:05:06','181.176.112.158'),(5,'\'; CREATE TABLE hack (data TEXT); INSERT INTO hack (data) VALUES (\'Robado\'); --','2025-02-28 17:24:46','38.187.18.9'),(6,'test','2025-02-28 17:32:38','38.187.18.9'),(7,'test','2025-02-28 18:26:23','38.187.18.9'),(8,'Test ññ#\'\"-@;\"(#?;\"-#','2025-02-28 18:42:49','181.176.112.158'),(9,'[¨1293081201¨[*{}*¨][]*[][][]','2025-02-28 19:28:54','38.187.18.9'),(10,'Registra saludos','2025-02-28 20:23:46','181.176.112.158'),(11,'Texto texto @-$+#(/@)@(#&+#)@/#@+#-$#()#(_+_!;\"!\"?ñ,🏞️💙👏♻️🐁♻️🏞️♻️🐁🦖🐕🪽🦆⌛🦖🐕🦖','2025-02-28 20:51:21','181.176.112.158'),(12,'🦖🦖🦖🦖🐕🐕🐕⌛🦖🦆🦖⌛🐕🦆🐕⌛🐕🪽🐕🦆🐕🪽🦖6🐕🪽🦖🦆🦖🐁🐕🪽🦖🐕🐕🦆🐕🦖🦆🐕⌛🪽','2025-02-28 21:27:12','181.176.112.158'),(13,'Regalos , se vienen regalos','2025-02-28 23:18:01','181.176.121.169'),(14,'Hola es el cumpleaños de mi papa medas algo una cosa par el cumple de mi papito','2025-02-28 23:53:09','179.6.6.132'),(15,'Saludos desde Colombia','2025-03-01 03:26:47','190.239.67.44'),(16,'Saludos desde Ecuador!','2025-03-01 11:50:00','190.236.203.20'),(17,'saludos a todxs jijiji','2025-03-01 09:56:06','190.236.203.20');
/*!40000 ALTER TABLE `saludos` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-03-01 11:10:55
