-- eLibrary schema (structure + views only). Regenerated schema-only dump.
-- Row data comes from database/seeders (php artisan migrate:fresh --seed).

-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: elibrarydb
-- ------------------------------------------------------
-- Server version	8.4.3

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
-- Table structure for table `authors`
--

DROP TABLE IF EXISTS `authors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `authors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `catalog_id` varchar(24) NOT NULL,
  `AuthorTag` int DEFAULT NULL,
  `author_name` varchar(255) NOT NULL,
  `author_name2` varchar(255) DEFAULT NULL,
  `numeration` varchar(100) DEFAULT NULL,
  `title_and_words` varchar(255) DEFAULT NULL,
  `relator_term` varchar(255) DEFAULT NULL,
  `date_associated` date DEFAULT NULL,
  `fuller_form` varchar(255) DEFAULT NULL,
  `affiliation` text,
  `CreatedBy` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedBy` varchar(255) NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `IsActive` tinyint(1) NOT NULL DEFAULT '1',
  `IsDeleted` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `barrowed_books`
--

DROP TABLE IF EXISTS `barrowed_books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `barrowed_books` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `fullname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contactnum` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catalog_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `consortia_id` int DEFAULT NULL,
  `status` enum('For Approval','Borrowed','Returned') COLLATE utf8mb4_unicode_ci DEFAULT 'For Approval',
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `update_return_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `returned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `catalog_import_progress`
--

DROP TABLE IF EXISTS `catalog_import_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_import_progress` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `progress` int DEFAULT '0',
  `total_records` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `catalog_imports`
--

DROP TABLE IF EXISTS `catalog_imports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_imports` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `no_of_records` int DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'imported',
  `is_aanr` tinyint DEFAULT '0',
  `material_type_id` int DEFAULT '21',
  `comment` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `catalogs`
--

DROP TABLE IF EXISTS `catalogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalogs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `material_type_id` int unsigned DEFAULT '1',
  `is_aanr` tinyint NOT NULL DEFAULT '0',
  `catalog_import_id` tinyint DEFAULT NULL,
  `is_published` tinyint NOT NULL DEFAULT '0',
  `marc_class_sys_id` int NOT NULL DEFAULT '1',
  `accession_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `field_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `circulation_fine_history`
--

DROP TABLE IF EXISTS `circulation_fine_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `circulation_fine_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transaction_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `overdue_fine_amount` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `overdue_total_hours` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `other_fine_amount` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `circulation_fine_id` int DEFAULT NULL,
  `other_fine_description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fine_note` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `circulation_fines`
--

DROP TABLE IF EXISTS `circulation_fines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `circulation_fines` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `circulation_reservations`
--

DROP TABLE IF EXISTS `circulation_reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `circulation_reservations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `location_id` int DEFAULT NULL,
  `requested_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title_statement` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reservation_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `visit_date` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `visit_time` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `circulation_status`
--

DROP TABLE IF EXISTS `circulation_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `circulation_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `order_no` int DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_no` (`order_no`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `circulations`
--

DROP TABLE IF EXISTS `circulations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `circulations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `copy_id` int DEFAULT NULL,
  `catalog_id` int DEFAULT NULL,
  `reservation_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `transaction_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1',
  `due_date` datetime DEFAULT NULL,
  `overdue_fine_rate` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `waived_amount` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_fine` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_fine_paid` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_library_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `circulation_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `declined_at` datetime DEFAULT NULL,
  `declined_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `released_at` datetime DEFAULT NULL,
  `released_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `received_by` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `consortias`
--

DROP TABLE IF EXISTS `consortias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consortias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `copies`
--

DROP TABLE IF EXISTS `copies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `copies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `catalog_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `circulation_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cost` int NOT NULL DEFAULT '0',
  `acquisition_mode` int DEFAULT NULL,
  `source` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` char(50) COLLATE utf8mb4_general_ci DEFAULT '1',
  `status` char(50) COLLATE utf8mb4_general_ci DEFAULT '4' COMMENT '1 = available, 2 = reserved, 3 = borrowed, 4 = not available, 5 = deleted',
  `series_statement_id` int DEFAULT NULL,
  `presented` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `holding_id` (`catalog_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `copies_status`
--

DROP TABLE IF EXISTS `copies_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `copies_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `corporate_authors`
--

DROP TABLE IF EXISTS `corporate_authors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `corporate_authors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `catalog_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author_type` int DEFAULT NULL,
  `author_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subordinate_unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_meeting` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_meeting` date DEFAULT NULL,
  `number_of_part` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `isActive` tinyint(1) DEFAULT NULL,
  `isDeleted` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `csfs`
--

DROP TABLE IF EXISTS `csfs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `csfs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `rating1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating3` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating4` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating5` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating6` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating7` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment1` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment2` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment3` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `downloads`
--

DROP TABLE IF EXISTS `downloads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `downloads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `catalog_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `to_review` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `for_reviews`
--

DROP TABLE IF EXISTS `for_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `for_reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `frontpages`
--

DROP TABLE IF EXISTS `frontpages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `frontpages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `catalog_id` varchar(24) NOT NULL,
  `AcquisitionID` varchar(24) NOT NULL,
  `FrontPageName` varchar(250) NOT NULL,
  `FrontPageType` varchar(250) NOT NULL,
  `FrontPageLocation` varchar(255) DEFAULT NULL,
  `FrontPageLocation-back` varchar(250) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=213 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fullpages`
--

DROP TABLE IF EXISTS `fullpages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fullpages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `holdings`
--

DROP TABLE IF EXISTS `holdings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `holdings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `catalog_id` varchar(24) NOT NULL,
  `call_number` varchar(24) DEFAULT NULL,
  `catalog_date` varchar(50) DEFAULT NULL,
  `CatalogSource` varchar(50) DEFAULT NULL,
  `material_type_id` int DEFAULT NULL,
  `ISBN` varchar(50) DEFAULT NULL,
  `ISSN` varchar(50) DEFAULT NULL,
  `CallNumber` varchar(255) DEFAULT NULL,
  `CallNumberFull` varchar(255) DEFAULT NULL,
  `title_statement` varchar(255) DEFAULT NULL,
  `edition_statement` varchar(255) DEFAULT NULL,
  `edition_statement_remainder` varchar(255) DEFAULT NULL,
  `PhysicalDescriptionEtc` varchar(255) DEFAULT NULL,
  `MediaTypeCode` varchar(50) DEFAULT NULL,
  `MediaSource` varchar(50) DEFAULT NULL,
  `CarrierSource` varchar(50) DEFAULT NULL,
  `series_statement_id` int DEFAULT NULL,
  `series_statement` varchar(150) DEFAULT NULL,
  `general_note` text,
  `bibliography` varchar(255) DEFAULT NULL,
  `FormattedContents` varchar(200) DEFAULT NULL,
  `ScopeandContent` varchar(150) DEFAULT NULL,
  `summary` text,
  `AdditionalPhysicalFormAvailableNote` varchar(150) DEFAULT NULL,
  `PublicationStatus` tinyint NOT NULL DEFAULT '0',
  `IsActive` tinyint(1) NOT NULL DEFAULT '1',
  `deleted` tinyint(1) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `CreatedBy` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedBy` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `LanguageID` int DEFAULT NULL,
  `Edition` varchar(100) DEFAULT NULL,
  `VolumeNo` varchar(50) DEFAULT NULL,
  `BroadClass` varchar(10) DEFAULT '37',
  `ContentTypeID` int DEFAULT NULL,
  `ContentTypeSource` varchar(50) DEFAULT NULL,
  `VolumeSequentialDesignation` varchar(150) DEFAULT NULL,
  `PhysicalFormAvailableNote` varchar(150) DEFAULT NULL,
  `AvailabilitySource` varchar(150) DEFAULT NULL,
  `AvailabilityConditions` varchar(150) DEFAULT NULL,
  `MediaTypeID` int DEFAULT NULL,
  `CarrierTypeID` int DEFAULT NULL,
  `CarrierTypeCode` int DEFAULT NULL,
  `ContentTypeCode` int DEFAULT NULL,
  `DissertationNote` varchar(500) DEFAULT NULL,
  `AbbreviatedTitle` varchar(255) DEFAULT NULL,
  `SoundCharacteristics` varchar(255) DEFAULT NULL,
  `DatesofPublication` varchar(255) DEFAULT NULL,
  `TimePeriodofCreation` varchar(255) DEFAULT NULL,
  `CitationReferencesNote` varchar(255) DEFAULT NULL,
  `SpecialCodedDates` varchar(50) DEFAULT NULL,
  `ProductionDistributionandYearSubmitted` varchar(500) DEFAULT NULL,
  `RestrictiononAccessNote` varchar(200) DEFAULT NULL,
  `TypesofReportandPeriodCoveredNote` varchar(200) DEFAULT NULL,
  `consortia_id` int DEFAULT NULL,
  `accession_number` varchar(50) DEFAULT NULL,
  `circulation_number` varchar(50) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `acquisition_modes` enum('Purchase','Subscription','Donation','Complimentary','Exchange','Download') DEFAULT NULL,
  `copy_number` char(10) DEFAULT NULL,
  `classifications_system` enum('Library of Congress','Dewey Decimal Classification') DEFAULT NULL,
  `date_acquired` date DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  `user_restriction` varchar(25) DEFAULT NULL,
  `item_status` varchar(50) DEFAULT NULL,
  `temporary_location` text,
  `nonpublic_note` varchar(255) DEFAULT NULL,
  `frequency` enum('Daily','Weekly','Monthly','Quarterly','Yearly','Irregular') DEFAULT NULL,
  `volume` varchar(25) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `issue_number` varchar(50) DEFAULT NULL,
  `publication_date` varchar(20) DEFAULT NULL,
  `classification_number` varchar(50) DEFAULT NULL,
  `author_number` varchar(100) DEFAULT NULL,
  `copyright_date` date DEFAULT NULL,
  `title_remainder` varchar(255) DEFAULT NULL,
  `statement_responsibility` text,
  `extent` text,
  `other_physical` text,
  `dimension` text,
  `imprint` enum('Not Applicable/Not Provided/Earliest','Intervening','Current/Last') DEFAULT NULL,
  `type_of_imprint` enum('Production','Publication') DEFAULT NULL,
  `publication_place` text,
  `publisher` text,
  `copies` int DEFAULT NULL,
  `pub_url` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `holdingscopypresenteds`
--

DROP TABLE IF EXISTS `holdingscopypresenteds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `holdingscopypresenteds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `catalog_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `circulation_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imprints`
--

DROP TABLE IF EXISTS `imprints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `imprints` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inquiries`
--

DROP TABLE IF EXISTS `inquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inquiries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `fullname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contactnum` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catalog_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `guest_replied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `marc_class_sys`
--

DROP TABLE IF EXISTS `marc_class_sys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marc_class_sys` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `marc_templates`
--

DROP TABLE IF EXISTS `marc_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marc_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fields` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `marc_templates_backup`
--

DROP TABLE IF EXISTS `marc_templates_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marc_templates_backup` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fields` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `marctag_indicators_loc`
--

DROP TABLE IF EXISTS `marctag_indicators_loc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marctag_indicators_loc` (
  `id` int NOT NULL AUTO_INCREMENT,
  `indicator` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=350 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `marctag_subfields_loc`
--

DROP TABLE IF EXISTS `marctag_subfields_loc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marctag_subfields_loc` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `marctags_loc_id` bigint unsigned DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `repeatable` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2634 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `marctags_loc`
--

DROP TABLE IF EXISTS `marctags_loc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marctags_loc` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_indictors` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `second_indicators` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Repeatable` tinyint NOT NULL DEFAULT '0' COMMENT '0= not repeatable, 1 = repeatable, ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2503 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `materials`
--

DROP TABLE IF EXISTS `materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(100) DEFAULT NULL,
  `tooltip` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `multimedia`
--

DROP TABLE IF EXISTS `multimedia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `multimedia` (
  `id` int NOT NULL AUTO_INCREMENT,
  `catalog_id` varchar(24) DEFAULT NULL,
  `FileName` varchar(255) DEFAULT NULL,
  `FileLocation` varchar(255) DEFAULT NULL,
  `FileType` varchar(255) DEFAULT NULL,
  `Extension` varchar(255) DEFAULT NULL,
  `Permission` varchar(255) DEFAULT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `news_title` text COLLATE utf8mb4_unicode_ci,
  `redirect_url` text COLLATE utf8mb4_unicode_ci,
  `news_body` text COLLATE utf8mb4_unicode_ci,
  `featured` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No',
  `news_cover` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `p_d_f_s`
--

DROP TABLE IF EXISTS `p_d_f_s`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `p_d_f_s` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `catalog_id` int DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patrontypes`
--

DROP TABLE IF EXISTS `patrontypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patrontypes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `types` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ratings`
--

DROP TABLE IF EXISTS `ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ratings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `catalog_id` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `rating` int DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reads`
--

DROP TABLE IF EXISTS `reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `catalog_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reads_user_id_catalog_id_unique` (`user_id`,`catalog_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `regions`
--

DROP TABLE IF EXISTS `regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `regions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `psgcCode` varchar(255) DEFAULT NULL,
  `desc` text,
  `regCode` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reply_inquiries`
--

DROP TABLE IF EXISTS `reply_inquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reply_inquiries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inquiry_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `seen` timestamp NULL DEFAULT NULL,
  `seen_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reservation_messages`
--

DROP TABLE IF EXISTS `reservation_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservation_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reservation_code` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `sender_id` bigint unsigned DEFAULT NULL,
  `sender_role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `seen_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reservation_messages_reservation_code_index` (`reservation_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(125) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(125) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `series`
--

DROP TABLE IF EXISTS `series`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `series` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `thumbnails`
--

DROP TABLE IF EXISTS `thumbnails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `thumbnails` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `type_of_imprints`
--

DROP TABLE IF EXISTS `type_of_imprints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `type_of_imprints` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contactnum` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usertype` enum('Administrator','Student','Patron','Developer') COLLATE utf8mb4_unicode_ci DEFAULT 'Patron',
  `is_banned` tinyint(1) NOT NULL DEFAULT '0',
  `is_blocked` tinyint(1) NOT NULL DEFAULT '0',
  `birthdate` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` text COLLATE utf8mb4_unicode_ci,
  `region_id` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position1` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position2` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position3` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position4` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sex` enum('Female','Male') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `affiliation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `backup` int DEFAULT NULL COMMENT '1 = YES; NULL = NO',
  `patrontypes_id` bigint unsigned NOT NULL,
  `library_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary view structure for view `view_borrowed_books`
--

DROP TABLE IF EXISTS `view_borrowed_books`;
/*!50001 DROP VIEW IF EXISTS `view_borrowed_books`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_borrowed_books` AS SELECT 
 1 AS `id`,
 1 AS `user_id`,
 1 AS `fullname`,
 1 AS `contactnum`,
 1 AS `email`,
 1 AS `catalog_id`,
 1 AS `consortia_id`,
 1 AS `status`,
 1 AS `approved_at`,
 1 AS `approved_by`,
 1 AS `returned_at`,
 1 AS `created_at`,
 1 AS `updated_at`,
 1 AS `deleted_at`,
 1 AS `title_statement`,
 1 AS `request_at_string`,
 1 AS `total_days`,
 1 AS `approved_at_string`,
 1 AS `returned_at_string`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `view_catalogs`
--

DROP TABLE IF EXISTS `view_catalogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `view_catalogs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `catalog_id` int unsigned NOT NULL DEFAULT '0',
  `isbn` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `issn` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `accession_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_published` tinyint NOT NULL DEFAULT '0',
  `catalog_import_id` tinyint DEFAULT NULL,
  `material_type_id` int DEFAULT NULL,
  `material_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `series_statement_id` int DEFAULT NULL,
  `series_description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title_statement` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `summary` text COLLATE utf8mb4_general_ci,
  `general_note` text COLLATE utf8mb4_general_ci,
  `call_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catalog_date` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catalog_status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_acquired` date DEFAULT NULL,
  `edition` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `joint_authors` text COLLATE utf8mb4_general_ci,
  `publisher` text COLLATE utf8mb4_general_ci,
  `pub_url` text COLLATE utf8mb4_general_ci,
  `pub_year` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary view structure for view `view_holding_frontpages`
--

DROP TABLE IF EXISTS `view_holding_frontpages`;
/*!50001 DROP VIEW IF EXISTS `view_holding_frontpages`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_holding_frontpages` AS SELECT 
 1 AS `id`,
 1 AS `catalog_id`,
 1 AS `material_type_id`,
 1 AS `FrontPageLocation`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_holdings`
--

DROP TABLE IF EXISTS `view_holdings`;
/*!50001 DROP VIEW IF EXISTS `view_holdings`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_holdings` AS SELECT 
 1 AS `id`,
 1 AS `catalog_id`,
 1 AS `circulation_number`,
 1 AS `accession_number`,
 1 AS `title_statement`,
 1 AS `summary`,
 1 AS `material_type_id`,
 1 AS `series_statement_id`,
 1 AS `catalog_date`,
 1 AS `date_acquired`,
 1 AS `edition`,
 1 AS `created_at`,
 1 AS `CreatedBy`,
 1 AS `catalog_status`,
 1 AS `material`,
 1 AS `series_description`,
 1 AS `call_number`,
 1 AS `publisher`,
 1 AS `pub_url`,
 1 AS `joint_authors`,
 1 AS `general_note`,
 1 AS `pub_year`,
 1 AS `deleted_at`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_holdings_uncatalog`
--

DROP TABLE IF EXISTS `view_holdings_uncatalog`;
/*!50001 DROP VIEW IF EXISTS `view_holdings_uncatalog`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_holdings_uncatalog` AS SELECT 
 1 AS `id`,
 1 AS `catalog_id`,
 1 AS `call_number`,
 1 AS `catalog_date`,
 1 AS `CatalogSource`,
 1 AS `material_type_id`,
 1 AS `ISBN`,
 1 AS `ISSN`,
 1 AS `CallNumber`,
 1 AS `CallNumberFull`,
 1 AS `title_statement`,
 1 AS `edition_statement`,
 1 AS `edition_statement_remainder`,
 1 AS `PhysicalDescriptionEtc`,
 1 AS `MediaTypeCode`,
 1 AS `MediaSource`,
 1 AS `CarrierSource`,
 1 AS `series_statement_id`,
 1 AS `series_statement`,
 1 AS `general_note`,
 1 AS `bibliography`,
 1 AS `FormattedContents`,
 1 AS `ScopeandContent`,
 1 AS `Summary`,
 1 AS `AdditionalPhysicalFormAvailableNote`,
 1 AS `PublicationStatus`,
 1 AS `IsActive`,
 1 AS `deleted`,
 1 AS `deleted_at`,
 1 AS `CreatedBy`,
 1 AS `created_at`,
 1 AS `UpdatedBy`,
 1 AS `updated_at`,
 1 AS `LanguageID`,
 1 AS `Edition`,
 1 AS `VolumeNo`,
 1 AS `BroadClass`,
 1 AS `ContentTypeID`,
 1 AS `ContentTypeSource`,
 1 AS `VolumeSequentialDesignation`,
 1 AS `PhysicalFormAvailableNote`,
 1 AS `AvailabilitySource`,
 1 AS `AvailabilityConditions`,
 1 AS `MediaTypeID`,
 1 AS `CarrierTypeID`,
 1 AS `CarrierTypeCode`,
 1 AS `ContentTypeCode`,
 1 AS `DissertationNote`,
 1 AS `AbbreviatedTitle`,
 1 AS `SoundCharacteristics`,
 1 AS `DatesofPublication`,
 1 AS `TimePeriodofCreation`,
 1 AS `CitationReferencesNote`,
 1 AS `SpecialCodedDates`,
 1 AS `ProductionDistributionandYearSubmitted`,
 1 AS `RestrictiononAccessNote`,
 1 AS `TypesofReportandPeriodCoveredNote`,
 1 AS `consortia_id`,
 1 AS `accession_number`,
 1 AS `circulation_number`,
 1 AS `cost_price`,
 1 AS `acquisition_modes`,
 1 AS `copy_number`,
 1 AS `classifications_system`,
 1 AS `date_acquired`,
 1 AS `source`,
 1 AS `user_restriction`,
 1 AS `item_status`,
 1 AS `temporary_location`,
 1 AS `nonpublic_note`,
 1 AS `frequency`,
 1 AS `volume`,
 1 AS `issue_date`,
 1 AS `issue_number`,
 1 AS `publication_date`,
 1 AS `classification_number`,
 1 AS `author_number`,
 1 AS `copyright_date`,
 1 AS `title_remainder`,
 1 AS `statement_responsibility`,
 1 AS `extent`,
 1 AS `other_physical`,
 1 AS `dimension`,
 1 AS `imprint`,
 1 AS `type_of_imprint`,
 1 AS `publication_place`,
 1 AS `publisher`,
 1 AS `copies`,
 1 AS `material_desc`,
 1 AS `joint_authors`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_inquiries`
--

DROP TABLE IF EXISTS `view_inquiries`;
/*!50001 DROP VIEW IF EXISTS `view_inquiries`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_inquiries` AS SELECT 
 1 AS `id`,
 1 AS `user_id`,
 1 AS `fullname`,
 1 AS `contactnum`,
 1 AS `email`,
 1 AS `catalog_id`,
 1 AS `subject`,
 1 AS `message`,
 1 AS `created_at`,
 1 AS `updated_at`,
 1 AS `title_statement`,
 1 AS `created_at_string`,
 1 AS `total_msg`,
 1 AS `new_reply`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_inquiry_threads`
--

DROP TABLE IF EXISTS `view_inquiry_threads`;
/*!50001 DROP VIEW IF EXISTS `view_inquiry_threads`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_inquiry_threads` AS SELECT 
 1 AS `id`,
 1 AS `user_id`,
 1 AS `replied_by`,
 1 AS `replied_by_name`,
 1 AS `replied_msg`,
 1 AS `seen`,
 1 AS `created_at_string`,
 1 AS `thread_date`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_most_downloaded`
--

DROP TABLE IF EXISTS `view_most_downloaded`;
/*!50001 DROP VIEW IF EXISTS `view_most_downloaded`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_most_downloaded` AS SELECT 
 1 AS `total`,
 1 AS `catalog_id`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_most_viewed`
--

DROP TABLE IF EXISTS `view_most_viewed`;
/*!50001 DROP VIEW IF EXISTS `view_most_viewed`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_most_viewed` AS SELECT 
 1 AS `total`,
 1 AS `catalog_id`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_ratings`
--

DROP TABLE IF EXISTS `view_ratings`;
/*!50001 DROP VIEW IF EXISTS `view_ratings`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_ratings` AS SELECT 
 1 AS `id`,
 1 AS `catalog_id`,
 1 AS `user_id`,
 1 AS `rating`,
 1 AS `remarks`,
 1 AS `created_at`,
 1 AS `updated_at`,
 1 AS `name`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_searches`
--

DROP TABLE IF EXISTS `view_searches`;
/*!50001 DROP VIEW IF EXISTS `view_searches`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_searches` AS SELECT 
 1 AS `id`,
 1 AS `search`,
 1 AS `material_type_id`,
 1 AS `material`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_to_reviews`
--

DROP TABLE IF EXISTS `view_to_reviews`;
/*!50001 DROP VIEW IF EXISTS `view_to_reviews`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_to_reviews` AS SELECT 
 1 AS `id`,
 1 AS `catalog_id`,
 1 AS `user_id`,
 1 AS `to_review`,
 1 AS `created_at`,
 1 AS `updated_at`,
 1 AS `title_statement`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `view_total_copies`
--

DROP TABLE IF EXISTS `view_total_copies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `view_total_copies` (
  `total` bigint DEFAULT NULL,
  `holdings_id` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `views`
--

DROP TABLE IF EXISTS `views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `views` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `is_guest` enum('YES','NO') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `catalog_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_view` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=332 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `visits`
--

DROP TABLE IF EXISTS `visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `visits` (
  `id` bigint unsigned NOT NULL,
  `user_id` int DEFAULT NULL,
  `fullname` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contactnum` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `schedule` datetime DEFAULT NULL,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_visit` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Final view structure for view `view_borrowed_books`
--

/*!50001 DROP VIEW IF EXISTS `view_borrowed_books`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_borrowed_books` AS select `a`.`id` AS `id`,`a`.`user_id` AS `user_id`,`a`.`fullname` AS `fullname`,`a`.`contactnum` AS `contactnum`,`a`.`email` AS `email`,`a`.`catalog_id` AS `catalog_id`,`a`.`consortia_id` AS `consortia_id`,`a`.`status` AS `status`,`a`.`approved_at` AS `approved_at`,`a`.`approved_by` AS `approved_by`,`a`.`returned_at` AS `returned_at`,`a`.`created_at` AS `created_at`,`a`.`updated_at` AS `updated_at`,`a`.`deleted_at` AS `deleted_at`,`b`.`title_statement` AS `title_statement`,date_format(`a`.`created_at`,'%M %d, %Y %T') AS `request_at_string`,if((`a`.`status` <> 'For Approval'),if((`a`.`returned_at` = NULL),(to_days(cast(`a`.`returned_at` as date)) - to_days(cast(`a`.`approved_at` as date))),(to_days(now()) - to_days(cast(`a`.`approved_at` as date)))),NULL) AS `total_days`,date_format(`a`.`approved_at`,'%M %d, %Y %T') AS `approved_at_string`,date_format(`a`.`returned_at`,'%M %d, %Y %T') AS `returned_at_string` from (`barrowed_books` `a` left join `holdings` `b` on((`b`.`catalog_id` = `a`.`catalog_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_holding_frontpages`
--

/*!50001 DROP VIEW IF EXISTS `view_holding_frontpages`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_holding_frontpages` AS select `a`.`id` AS `id`,`a`.`catalog_id` AS `catalog_id`,`a`.`material_type_id` AS `material_type_id`,`b`.`FrontPageLocation` AS `FrontPageLocation` from (`holdings` `a` left join `frontpages` `b` on((`a`.`catalog_id` = `b`.`catalog_id`))) where (`b`.`FrontPageLocation` is not null) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_holdings`
--

/*!50001 DROP VIEW IF EXISTS `view_holdings`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_holdings` AS select `a`.`id` AS `id`,`a`.`id` AS `catalog_id`,`a`.`circulation_number` AS `circulation_number`,`a`.`accession_number` AS `accession_number`,`a`.`title_statement` AS `title_statement`,if((`a`.`summary` is null),'N/A',`a`.`summary`) AS `summary`,`a`.`material_type_id` AS `material_type_id`,`a`.`series_statement_id` AS `series_statement_id`,`a`.`catalog_date` AS `catalog_date`,`a`.`date_acquired` AS `date_acquired`,`a`.`Edition` AS `edition`,date_format(`a`.`created_at`,'%M %d, %Y %T') AS `created_at`,`a`.`CreatedBy` AS `CreatedBy`,if((`a`.`catalog_date` = ''),'Uncataloged','Cataloged') AS `catalog_status`,`b`.`name` AS `material`,`c`.`description` AS `series_description`,`a`.`call_number` AS `call_number`,`a`.`publisher` AS `publisher`,`a`.`pub_url` AS `pub_url`,(select group_concat(substr(`authors`.`author_name`,8) separator ' - ') from `authors` where (`authors`.`catalog_id` = `a`.`id`) group by `authors`.`catalog_id`) AS `joint_authors`,`a`.`general_note` AS `general_note`,`a`.`publication_date` AS `pub_year`,`a`.`deleted_at` AS `deleted_at` from ((`holdings` `a` left join `materials` `b` on((`a`.`material_type_id` = `b`.`id`))) left join `series` `c` on((`c`.`id` = `a`.`series_statement_id`))) where (`a`.`deleted_at` is null) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_holdings_uncatalog`
--

/*!50001 DROP VIEW IF EXISTS `view_holdings_uncatalog`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_holdings_uncatalog` AS select `a`.`id` AS `id`,`a`.`catalog_id` AS `catalog_id`,`a`.`call_number` AS `call_number`,`a`.`catalog_date` AS `catalog_date`,`a`.`CatalogSource` AS `CatalogSource`,`a`.`material_type_id` AS `material_type_id`,`a`.`ISBN` AS `ISBN`,`a`.`ISSN` AS `ISSN`,`a`.`CallNumber` AS `CallNumber`,`a`.`CallNumberFull` AS `CallNumberFull`,`a`.`title_statement` AS `title_statement`,`a`.`edition_statement` AS `edition_statement`,`a`.`edition_statement_remainder` AS `edition_statement_remainder`,`a`.`PhysicalDescriptionEtc` AS `PhysicalDescriptionEtc`,`a`.`MediaTypeCode` AS `MediaTypeCode`,`a`.`MediaSource` AS `MediaSource`,`a`.`CarrierSource` AS `CarrierSource`,`a`.`series_statement_id` AS `series_statement_id`,`a`.`series_statement` AS `series_statement`,`a`.`general_note` AS `general_note`,`a`.`bibliography` AS `bibliography`,`a`.`FormattedContents` AS `FormattedContents`,`a`.`ScopeandContent` AS `ScopeandContent`,`a`.`summary` AS `Summary`,`a`.`AdditionalPhysicalFormAvailableNote` AS `AdditionalPhysicalFormAvailableNote`,`a`.`PublicationStatus` AS `PublicationStatus`,`a`.`IsActive` AS `IsActive`,`a`.`deleted` AS `deleted`,`a`.`deleted_at` AS `deleted_at`,`a`.`CreatedBy` AS `CreatedBy`,`a`.`created_at` AS `created_at`,`a`.`UpdatedBy` AS `UpdatedBy`,`a`.`updated_at` AS `updated_at`,`a`.`LanguageID` AS `LanguageID`,`a`.`Edition` AS `Edition`,`a`.`VolumeNo` AS `VolumeNo`,`a`.`BroadClass` AS `BroadClass`,`a`.`ContentTypeID` AS `ContentTypeID`,`a`.`ContentTypeSource` AS `ContentTypeSource`,`a`.`VolumeSequentialDesignation` AS `VolumeSequentialDesignation`,`a`.`PhysicalFormAvailableNote` AS `PhysicalFormAvailableNote`,`a`.`AvailabilitySource` AS `AvailabilitySource`,`a`.`AvailabilityConditions` AS `AvailabilityConditions`,`a`.`MediaTypeID` AS `MediaTypeID`,`a`.`CarrierTypeID` AS `CarrierTypeID`,`a`.`CarrierTypeCode` AS `CarrierTypeCode`,`a`.`ContentTypeCode` AS `ContentTypeCode`,`a`.`DissertationNote` AS `DissertationNote`,`a`.`AbbreviatedTitle` AS `AbbreviatedTitle`,`a`.`SoundCharacteristics` AS `SoundCharacteristics`,`a`.`DatesofPublication` AS `DatesofPublication`,`a`.`TimePeriodofCreation` AS `TimePeriodofCreation`,`a`.`CitationReferencesNote` AS `CitationReferencesNote`,`a`.`SpecialCodedDates` AS `SpecialCodedDates`,`a`.`ProductionDistributionandYearSubmitted` AS `ProductionDistributionandYearSubmitted`,`a`.`RestrictiononAccessNote` AS `RestrictiononAccessNote`,`a`.`TypesofReportandPeriodCoveredNote` AS `TypesofReportandPeriodCoveredNote`,`a`.`consortia_id` AS `consortia_id`,`a`.`accession_number` AS `accession_number`,`a`.`circulation_number` AS `circulation_number`,`a`.`cost_price` AS `cost_price`,`a`.`acquisition_modes` AS `acquisition_modes`,`a`.`copy_number` AS `copy_number`,`a`.`classifications_system` AS `classifications_system`,`a`.`date_acquired` AS `date_acquired`,`a`.`source` AS `source`,`a`.`user_restriction` AS `user_restriction`,`a`.`item_status` AS `item_status`,`a`.`temporary_location` AS `temporary_location`,`a`.`nonpublic_note` AS `nonpublic_note`,`a`.`frequency` AS `frequency`,`a`.`volume` AS `volume`,`a`.`issue_date` AS `issue_date`,`a`.`issue_number` AS `issue_number`,`a`.`publication_date` AS `publication_date`,`a`.`classification_number` AS `classification_number`,`a`.`author_number` AS `author_number`,`a`.`copyright_date` AS `copyright_date`,`a`.`title_remainder` AS `title_remainder`,`a`.`statement_responsibility` AS `statement_responsibility`,`a`.`extent` AS `extent`,`a`.`other_physical` AS `other_physical`,`a`.`dimension` AS `dimension`,`a`.`imprint` AS `imprint`,`a`.`type_of_imprint` AS `type_of_imprint`,`a`.`publication_place` AS `publication_place`,`a`.`publisher` AS `publisher`,`a`.`copies` AS `copies`,`b`.`name` AS `material_desc`,(select group_concat(`authors`.`author_name2` separator ' - ') from `authors` where (`authors`.`catalog_id` = `a`.`catalog_id`) group by `authors`.`catalog_id`) AS `joint_authors` from (`holdings` `a` left join `materials` `b` on((`a`.`material_type_id` = `b`.`id`))) where ((`a`.`catalog_date` is null) and (`a`.`deleted_at` is not null)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_inquiries`
--

/*!50001 DROP VIEW IF EXISTS `view_inquiries`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_inquiries` AS select `a`.`id` AS `id`,`a`.`user_id` AS `user_id`,`a`.`fullname` AS `fullname`,`a`.`contactnum` AS `contactnum`,`a`.`email` AS `email`,`a`.`catalog_id` AS `catalog_id`,`a`.`subject` AS `subject`,`a`.`message` AS `message`,`a`.`created_at` AS `created_at`,`a`.`updated_at` AS `updated_at`,`b`.`title_statement` AS `title_statement`,date_format(`a`.`created_at`,'%M %d, %Y %T') AS `created_at_string`,(select count(`reply_inquiries`.`id`) from `reply_inquiries` where (`reply_inquiries`.`inquiry_id` = `a`.`id`)) AS `total_msg`,(select count(`view_inquiry_threads`.`id`) from `view_inquiry_threads` where ((`view_inquiry_threads`.`id` = `a`.`id`) and (`view_inquiry_threads`.`seen` is null) and (`view_inquiry_threads`.`replied_by` <> `a`.`user_id`))) AS `new_reply` from (`inquiries` `a` left join `holdings` `b` on((`b`.`catalog_id` = `a`.`catalog_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_inquiry_threads`
--

/*!50001 DROP VIEW IF EXISTS `view_inquiry_threads`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_inquiry_threads` AS select `b`.`id` AS `id`,`b`.`user_id` AS `user_id`,`a`.`user_id` AS `replied_by`,`c`.`name` AS `replied_by_name`,`a`.`message` AS `replied_msg`,`a`.`seen` AS `seen`,date_format(`b`.`created_at`,'%M %d, %Y %T') AS `created_at_string`,date_format(`a`.`created_at`,'%M %d, %Y %T') AS `thread_date` from ((`reply_inquiries` `a` left join `inquiries` `b` on((`a`.`inquiry_id` = `b`.`id`))) left join `users` `c` on((`a`.`user_id` = `c`.`id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_most_downloaded`
--

/*!50001 DROP VIEW IF EXISTS `view_most_downloaded`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_most_downloaded` AS select count(`a`.`id`) AS `total`,`a`.`catalog_id` AS `catalog_id` from (`downloads` `a` join `catalogs` `c` on((`a`.`catalog_id` = `c`.`id`))) where (`c`.`deleted_at` is null) group by `a`.`catalog_id` order by count(`a`.`id`) desc limit 0,10 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_most_viewed`
--

/*!50001 DROP VIEW IF EXISTS `view_most_viewed`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_most_viewed` AS select count(`a`.`id`) AS `total`,`a`.`catalog_id` AS `catalog_id` from (`views` `a` join `catalogs` `c` on((`a`.`catalog_id` = `c`.`id`))) where (`c`.`deleted_at` is null) group by `a`.`catalog_id` order by count(`a`.`id`) desc limit 0,5 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_ratings`
--

/*!50001 DROP VIEW IF EXISTS `view_ratings`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_ratings` AS select `a`.`id` AS `id`,`a`.`catalog_id` AS `catalog_id`,`a`.`user_id` AS `user_id`,`a`.`rating` AS `rating`,`a`.`remarks` AS `remarks`,`a`.`created_at` AS `created_at`,`a`.`updated_at` AS `updated_at`,`b`.`name` AS `name` from (`ratings` `a` left join `users` `b` on((`b`.`id` = `a`.`user_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_searches`
--

/*!50001 DROP VIEW IF EXISTS `view_searches`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_searches` AS select `a`.`id` AS `id`,concat(`a`.`title_statement`,`a`.`summary`) AS `search`,`a`.`material_type_id` AS `material_type_id`,`b`.`name` AS `material` from (`holdings` `a` left join `materials` `b` on((`b`.`id` = `a`.`material_type_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_to_reviews`
--

/*!50001 DROP VIEW IF EXISTS `view_to_reviews`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_to_reviews` AS select `a`.`id` AS `id`,`a`.`catalog_id` AS `catalog_id`,`a`.`user_id` AS `user_id`,`a`.`to_review` AS `to_review`,`a`.`created_at` AS `created_at`,`a`.`updated_at` AS `updated_at`,`b`.`title_statement` AS `title_statement` from (`downloads` `a` left join `holdings` `b` on((`b`.`catalog_id` = `a`.`catalog_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-14  8:55:36
