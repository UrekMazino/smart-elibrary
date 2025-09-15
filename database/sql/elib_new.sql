-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 03, 2025 at 05:42 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `elib_new`
--

-- --------------------------------------------------------

--
-- Table structure for table `authors`
--

CREATE TABLE `authors` (
  `id` int(11) NOT NULL,
  `catalog_id` varchar(24) NOT NULL,
  `AuthorTag` int(11) DEFAULT NULL,
  `author_name` varchar(255) NOT NULL,
  `author_name2` varchar(255) DEFAULT NULL,
  `numeration` varchar(100) DEFAULT NULL,
  `title_and_words` varchar(255) DEFAULT NULL,
  `relator_term` varchar(255) DEFAULT NULL,
  `date_associated` date DEFAULT NULL,
  `fuller_form` varchar(255) DEFAULT NULL,
  `affiliation` text DEFAULT NULL,
  `CreatedBy` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` varchar(255) NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `IsDeleted` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `barrowed_books`
--

CREATE TABLE `barrowed_books` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `contactnum` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `catalog_id` varchar(50) DEFAULT NULL,
  `consortia_id` int(11) DEFAULT NULL,
  `status` enum('For Approval','Borrowed','Returned') DEFAULT 'For Approval',
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `update_return_by` varchar(100) DEFAULT NULL,
  `returned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `catalogs`
--

CREATE TABLE `catalogs` (
  `id` int(10) UNSIGNED NOT NULL,
  `material_type_id` int(10) UNSIGNED DEFAULT 1,
  `is_aanr` tinyint(4) NOT NULL DEFAULT 0,
  `catalog_import_id` tinyint(4) DEFAULT NULL,
  `is_published` tinyint(4) NOT NULL DEFAULT 0,
  `marc_class_sys_id` int(10) NOT NULL DEFAULT 1,
  `accession_number` varchar(50) DEFAULT NULL,
  `field_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_imports`
--

CREATE TABLE `catalog_imports` (
  `id` int(10) UNSIGNED NOT NULL,
  `no_of_records` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'imported',
  `is_aanr` tinyint(4) DEFAULT 0,
  `material_type_id` int(11) DEFAULT 21,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` varchar(50) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `catalog_import_progress`
--

CREATE TABLE `catalog_import_progress` (
  `id` int(10) UNSIGNED NOT NULL,
  `progress` int(11) DEFAULT 0,
  `total_records` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `circulations`
--

CREATE TABLE `circulations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `copy_id` int(11) DEFAULT NULL,
  `catalog_id` int(11) DEFAULT NULL,
  `reservation_code` varchar(50) DEFAULT NULL,
  `transaction_code` varchar(50) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `due_date` datetime DEFAULT NULL,
  `overdue_fine_rate` varchar(50) DEFAULT NULL,
  `waived_amount` varchar(50) DEFAULT NULL,
  `total_fine` varchar(50) DEFAULT NULL,
  `total_fine_paid` varchar(50) DEFAULT NULL,
  `user_library_code` varchar(50) DEFAULT NULL,
  `circulation_number` varchar(50) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` varchar(50) DEFAULT NULL,
  `declined_at` datetime DEFAULT NULL,
  `declined_by` varchar(50) DEFAULT NULL,
  `released_at` datetime DEFAULT NULL,
  `released_by` varchar(50) DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `received_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `circulation_fines`
--

CREATE TABLE `circulation_fines` (
  `id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `circulation_fines`
--

INSERT INTO `circulation_fines` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Water Damage', '2023-07-12 01:08:34', '2023-07-12 01:08:35'),
(2, 'Cover Damage', '2023-07-12 01:09:49', '2023-07-12 01:09:50'),
(3, 'Spine Damage', '2023-07-12 01:10:03', '2023-07-12 01:10:04'),
(4, 'Page Tears', '2023-07-12 01:10:18', '2023-07-12 01:10:19'),
(5, 'Highlighting and Underlining', '2023-07-12 01:10:34', '2023-07-12 01:10:35'),
(6, 'Writing or Annotations', '2023-07-12 01:11:05', '2023-07-12 01:11:06'),
(7, 'Dog-Eared Pages', '2023-07-12 01:11:15', '2023-07-12 01:11:16'),
(8, 'Loose Binding', '2023-07-12 01:11:26', '2023-07-12 01:11:27'),
(9, 'Dust Jacket Damage', '2023-07-12 01:11:37', '2023-07-12 01:11:37'),
(10, 'Excessive Wear', '2023-07-12 01:11:47', '2023-07-12 01:11:48');

-- --------------------------------------------------------

--
-- Table structure for table `circulation_fine_history`
--

CREATE TABLE `circulation_fine_history` (
  `id` int(11) NOT NULL,
  `transaction_code` varchar(50) DEFAULT NULL,
  `overdue_fine_amount` varchar(50) DEFAULT NULL,
  `overdue_total_hours` varchar(50) DEFAULT NULL,
  `other_fine_amount` varchar(50) DEFAULT NULL,
  `circulation_fine_id` int(11) DEFAULT NULL,
  `other_fine_description` varchar(255) DEFAULT NULL,
  `fine_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `circulation_reservations`
--

CREATE TABLE `circulation_reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `requested_by` varchar(100) DEFAULT NULL,
  `title_statement` varchar(255) DEFAULT NULL,
  `reservation_code` varchar(50) DEFAULT NULL,
  `visit_date` varchar(50) DEFAULT NULL,
  `visit_time` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `circulation_status`
--

CREATE TABLE `circulation_status` (
  `id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `order_no` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `circulation_status`
--

INSERT INTO `circulation_status` (`id`, `name`, `order_no`, `description`, `created_at`, `updated_at`) VALUES
(1, 'For Approval', 1, 'The booking is in the process of being reviewed and validated by the relevant authority or personnel.', '2023-05-26 06:40:53', '2023-05-26 06:45:06'),
(2, 'Approved', 2, 'Passed the necessary checks and validations and is now ready for check-out.', '2023-05-26 06:53:58', '2023-05-26 06:53:59'),
(3, 'Open', 3, 'The material was check-out already and was received by the patron.', '2023-05-26 07:04:24', '2023-05-26 07:04:26'),
(4, 'Declined', 4, 'Request for reservation denied.', '2023-05-26 07:09:24', '2023-05-26 07:09:26'),
(5, 'Closed', 5, 'The material was returned and fine was settled.', '2023-06-02 06:59:25', '2023-06-02 06:59:27');

-- --------------------------------------------------------

--
-- Table structure for table `consortias`
--

CREATE TABLE `consortias` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(25) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `consortias`
--

INSERT INTO `consortias` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'CorCAARRD', NULL, NULL),
(2, 'MAARRDEC', NULL, NULL),
(3, 'WESMAARRDEC', NULL, NULL),
(4, 'ILAARRDEC', NULL, NULL),
(5, 'BCAARRD', NULL, NULL),
(6, 'NOMCAARRD', NULL, NULL),
(7, 'CVAARRD', NULL, NULL),
(8, 'WESVAARRDEC', NULL, NULL),
(9, 'SMAARRDEC', NULL, NULL),
(10, 'CLAARRDEC', NULL, NULL),
(11, 'CVAARRDEC', NULL, NULL),
(12, 'SOXAARRDEC', NULL, NULL),
(13, 'STAARRDEC', NULL, NULL),
(14, 'ViCARP', NULL, NULL),
(15, 'CCAARRD', NULL, NULL),
(16, 'PCAARRD', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `copies`
--

CREATE TABLE `copies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `catalog_id` varchar(50) DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `circulation_number` varchar(50) DEFAULT NULL,
  `cost` int(11) NOT NULL DEFAULT 0,
  `acquisition_mode` int(11) DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  `location` char(50) DEFAULT '1',
  `status` char(50) DEFAULT '4' COMMENT '1 = available, 2 = reserved, 3 = borrowed, 4 = not available, 5 = deleted',
  `series_statement_id` int(11) DEFAULT NULL,
  `presented` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `copies_status`
--

CREATE TABLE `copies_status` (
  `id` int(11) NOT NULL,
  `order_no` varchar(50) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `copies_status`
--

INSERT INTO `copies_status` (`id`, `order_no`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, '1', 'Available', 'Ready for reservation or borrowing.', '2023-05-29 00:49:36', '2023-05-29 00:49:39'),
(2, '2', 'Reserved', 'On hold. Booking was approved  by admin and now ready for check-out.', '2023-05-29 00:51:16', '2023-05-29 00:51:17'),
(3, '3', 'Borrowed', 'Material currently on check-out and unavailable until returned.', '2023-05-29 00:55:15', '2023-05-29 00:56:10'),
(4, '4', 'Not Available', 'Any material/s marked by admin that can\'t be used for some reason. (damage, repair, etc).', '2023-05-29 00:59:50', '2023-05-29 00:59:52'),
(5, '5', 'Deleted', 'Soft deleted materials.', '2023-07-12 00:33:29', '2023-07-12 00:33:30'),
(6, '6', 'Lost', 'Lost materials.', '2023-08-07 05:34:08', '2023-08-07 05:34:09'),
(7, '7', 'For Repair', 'Damaged materials that need repair.', '2023-08-07 05:34:10', '2023-08-07 05:34:11');

-- --------------------------------------------------------

--
-- Table structure for table `corporate_authors`
--

CREATE TABLE `corporate_authors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `catalog_id` varchar(50) DEFAULT NULL,
  `author_type` int(11) DEFAULT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `subordinate_unit` varchar(255) DEFAULT NULL,
  `location_meeting` varchar(255) DEFAULT NULL,
  `date_of_meeting` date DEFAULT NULL,
  `number_of_part` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `isActive` tinyint(1) DEFAULT NULL,
  `isDeleted` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `csfs`
--

CREATE TABLE `csfs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `rating1` varchar(255) DEFAULT NULL,
  `rating2` varchar(255) DEFAULT NULL,
  `rating3` varchar(255) DEFAULT NULL,
  `rating4` varchar(255) DEFAULT NULL,
  `rating5` varchar(255) DEFAULT NULL,
  `rating6` varchar(255) DEFAULT NULL,
  `rating7` varchar(255) DEFAULT NULL,
  `comment1` varchar(50) DEFAULT NULL,
  `comment2` varchar(50) DEFAULT NULL,
  `comment3` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `downloads`
--

CREATE TABLE `downloads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `catalog_id` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `to_review` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `for_reviews`
--

CREATE TABLE `for_reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `frontpages`
--

CREATE TABLE `frontpages` (
  `id` int(11) NOT NULL,
  `catalog_id` varchar(24) NOT NULL,
  `AcquisitionID` varchar(24) NOT NULL,
  `FrontPageName` varchar(250) NOT NULL,
  `FrontPageType` varchar(250) NOT NULL,
  `FrontPageLocation` varchar(255) DEFAULT NULL,
  `FrontPageLocation-back` varchar(250) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fullpages`
--

CREATE TABLE `fullpages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `filename` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holdings`
--

CREATE TABLE `holdings` (
  `id` int(11) NOT NULL,
  `catalog_id` varchar(24) NOT NULL,
  `call_number` varchar(24) DEFAULT NULL,
  `catalog_date` varchar(50) DEFAULT NULL,
  `CatalogSource` varchar(50) DEFAULT NULL,
  `material_type_id` int(11) DEFAULT NULL,
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
  `series_statement_id` int(11) DEFAULT NULL,
  `series_statement` varchar(150) DEFAULT NULL,
  `general_note` text DEFAULT NULL,
  `bibliography` varchar(255) DEFAULT NULL,
  `FormattedContents` varchar(200) DEFAULT NULL,
  `ScopeandContent` varchar(150) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `AdditionalPhysicalFormAvailableNote` varchar(150) DEFAULT NULL,
  `PublicationStatus` tinyint(4) NOT NULL DEFAULT 0,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `deleted` tinyint(1) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `CreatedBy` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedBy` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `LanguageID` int(11) DEFAULT NULL,
  `Edition` varchar(100) DEFAULT NULL,
  `VolumeNo` varchar(50) DEFAULT NULL,
  `BroadClass` varchar(10) DEFAULT '37',
  `ContentTypeID` int(11) DEFAULT NULL,
  `ContentTypeSource` varchar(50) DEFAULT NULL,
  `VolumeSequentialDesignation` varchar(150) DEFAULT NULL,
  `PhysicalFormAvailableNote` varchar(150) DEFAULT NULL,
  `AvailabilitySource` varchar(150) DEFAULT NULL,
  `AvailabilityConditions` varchar(150) DEFAULT NULL,
  `MediaTypeID` int(11) DEFAULT NULL,
  `CarrierTypeID` int(11) DEFAULT NULL,
  `CarrierTypeCode` int(11) DEFAULT NULL,
  `ContentTypeCode` int(11) DEFAULT NULL,
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
  `consortia_id` int(11) DEFAULT NULL,
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
  `temporary_location` text DEFAULT NULL,
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
  `statement_responsibility` text DEFAULT NULL,
  `extent` text DEFAULT NULL,
  `other_physical` text DEFAULT NULL,
  `dimension` text DEFAULT NULL,
  `imprint` enum('Not Applicable/Not Provided/Earliest','Intervening','Current/Last') DEFAULT NULL,
  `type_of_imprint` enum('Production','Publication') DEFAULT NULL,
  `publication_place` text DEFAULT NULL,
  `publisher` text DEFAULT NULL,
  `copies` int(11) DEFAULT NULL,
  `pub_url` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holdingscopypresenteds`
--

CREATE TABLE `holdingscopypresenteds` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `catalog_id` varchar(100) DEFAULT NULL,
  `circulation_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `imprints`
--

CREATE TABLE `imprints` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `imprints`
--

INSERT INTO `imprints` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Not Applicable/Not Provided/Earliest', NULL, NULL),
(2, 'Intervening', NULL, NULL),
(3, 'Current/Last', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `contactnum` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `catalog_id` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marctags_loc`
--

CREATE TABLE `marctags_loc` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `first_indictors` varchar(255) DEFAULT NULL,
  `second_indicators` varchar(255) DEFAULT NULL,
  `Repeatable` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0= not repeatable, 1 = repeatable, ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `marctags_loc`
--

INSERT INTO `marctags_loc` (`id`, `code`, `description`, `first_indictors`, `second_indicators`, `Repeatable`, `created_at`, `updated_at`) VALUES
(1, '001', 'Control Number', '[]', '[]', 0, NULL, NULL),
(2, '003', 'Control Number Identifier', '[]', '[]', 0, NULL, NULL),
(3, '005', 'Date and Time of Latest Transaction', '[]', '[]', 0, NULL, NULL),
(4, '006', 'Fixed-Length Data Elements -- Additional Material Characteristics', '[]', '[]', 0, NULL, NULL),
(5, '007', 'Physical Description Fixed Field', '[]', '[]', 0, NULL, NULL),
(6, '008', 'Fixed Length Data Elements', '[]', '[]', 0, NULL, NULL),
(7, '010', 'Library of Congress Control Number', '[1]', '[1]', 0, NULL, NULL),
(11, '013', 'Patent Control Information', '[1]', '[1]', 1, NULL, NULL),
(19, '015', 'National Bibliography Number', '[1]', '[1]', 1, NULL, NULL),
(25, '016', 'National Bibliographic Agency Control Number', '[2,3]', '[1]', 1, NULL, NULL),
(29, '017', 'Copyright or Legal Deposit Number', '[1]', '[4,5]', 1, NULL, NULL),
(37, '018', 'Copyright Article-Fee Code', '[1]', '[1]', 0, NULL, NULL),
(40, '020', 'International Standard Book Number', '[1]', '[1]', 1, NULL, NULL),
(46, '022', 'International Standard Serial Number', '[6,7,8]', '[1]', 1, NULL, NULL),
(56, '024', 'Other Standard Identifier', '[3,9,10,11,12,13,15]', '[16,17,18]', 1, NULL, NULL),
(64, '025', 'Overseas Acquisition Number', '[1]', '[1]', 1, NULL, NULL),
(66, '026', 'Fingerprint Identifier', '[1]', '[1]', 1, NULL, NULL),
(67, '027', 'Standard Technical Report Number', '[1]', '[1]', 1, NULL, NULL),
(71, '028', 'Publisher or Distributor Number', '[19,20,21,22,23,24,25]', '[26,27,28,29]', 1, NULL, NULL),
(76, '030', 'CODEN Designation', '[1]', '[1]', 1, NULL, NULL),
(80, '031', 'Musical Incipits Information', '[1]', '[1]', 1, NULL, NULL),
(100, '032', 'Postal Registration Number', '[1]', '[1]', 1, NULL, NULL),
(104, '033', 'Date/Time and Place of an Event', '[30,31,32,33]', '[16,34,35,36]', 1, NULL, NULL),
(114, '034', 'Coded Cartographic Mathematical Data', '[37,38,39]', '[40,41,42]', 1, NULL, NULL),
(139, '035', 'System Control Number', '[1]', '[1]', 1, NULL, NULL),
(143, '036', 'Original Study Number for Computer Data Files', '[1]', '[1]', 0, NULL, NULL),
(147, '037', 'Source of Acquisition', '[43,44,45]', '[1]', 1, NULL, NULL),
(157, '038', 'Record Content Licensor', '[1]', '[1]', 0, NULL, NULL),
(160, '040', 'Cataloging Source', '[1]', '[1]', 0, NULL, NULL),
(167, '041', 'Language Code', '[16, 46,47]', '[3,48]', 1, NULL, NULL),
(187, '042', 'Authentication code', '[1]', '[1]', 1, NULL, NULL),
(188, '043', 'Geographic Area Code', '[1]', '[1]', 1, NULL, NULL),
(196, '044', 'Country of Publishing/Producing Entity Code', '[1]', '[1]', 0, NULL, NULL),
(202, '045', 'Time Period of Content', '[49,50,51,52]', '[1]', 0, NULL, NULL),
(207, '046', 'Special Coded Dates', '[16,53,54,55]', '[1]', 1, NULL, NULL),
(225, '047', 'Form of Musical Composition Code', '[1]', '[3,56]', 1, NULL, NULL),
(228, '048', 'Number of Musical Instruments or Voices Codes', '[1]', '[3,57]', 1, NULL, NULL),
(232, '050', 'Library of Congress Call Number', '[16,58,59]', '[60,61]', 1, NULL, NULL),
(239, '051', 'Library of Congress Copy, Issue, Offprint Statement', '[1]', '[1]', 1, NULL, NULL),
(243, '052', 'Geographic Classification', '[3,62,63]', '[1]', 1, NULL, NULL),
(251, '055', 'Classification Numbers Assigned in Canada', '[64,65,66]', '[67,68,69,70,71,72,73,74,75,76]', 1, NULL, NULL),
(258, '060', 'National Library of Medicine Call Number', '[16,77,78]', '[79,80]', 1, NULL, NULL),
(263, '061', 'National Library of Medicine Copy Statement', '[1]', '[1]', 1, NULL, NULL),
(267, '066', 'Character Sets Present', '[1]', '[1]', 0, NULL, NULL),
(270, '070', 'National Agricultural Library Call Number', '[16,81,82]', '[1]', 1, NULL, NULL),
(275, '071', 'National Agricultural Library Copy Statement', '[1]', '[1]', 1, NULL, NULL),
(279, '072', 'Subject Category Code', '[1]', '[3,83]', 1, NULL, NULL),
(284, '074', 'GPO Item Number', '[1]', '[1]', 1, NULL, NULL),
(287, '080', 'Universal Decimal Classification Number', '[16,84,85]', '[1]', 1, NULL, NULL),
(295, '082', 'Dewey Decimal Classification Number', '[86,87,88]', '[16,60,61]', 1, NULL, NULL),
(303, '083', 'Additional Dewey Decimal Classification Number', '[86,87,88]', '[1]', 1, NULL, NULL),
(313, '084', 'Other Classification Number', '[1]', '[1]', 1, NULL, NULL),
(322, '085', 'Synthesized Classification Number Components', '[1]', '[1]', 1, NULL, NULL),
(338, '086', 'Government Document Classification Number', '[89,90,91]', '[1]', 1, NULL, NULL),
(345, '088', 'Report Number', '[1]', '[1]', 1, NULL, NULL),
(349, '100', 'Main Entry - Personal Name', '[92,93,94]', '[1]', 0, NULL, NULL),
(393, '110', 'Main Entry - Corporate Name', '[95,96,97]', '[1]', 0, NULL, NULL),
(413, '111', 'Main Entry - Meeting Name', '[95,96,97]', '[1]', 0, NULL, NULL),
(434, '130', 'Main Entry - Uniform Title', '[98,99,100,101,102,103,104,105,106,107]', '[1]', 0, NULL, NULL),
(454, '210', 'Abbreviated Title', '[108,109]', '[110,111]', 1, NULL, NULL),
(460, '222', 'Key Title', '[1]', '[112,99,100,101,102,103,104,105,106,107]', 1, NULL, NULL),
(464, '240', 'Uniform Title', '[114,115]', '[98,99,100,101,102,103,104,105,106,107]', 0, NULL, NULL),
(484, '242', 'Translation of Title by Cataloging Agency', '[108, 109]', '[112,99,100,101,102,103,104,105,106,107]', 1, NULL, NULL),
(492, '243', 'Collective Uniform Title', '[114,115]', '[98,99,100,101,102,103,104,105,106,107]', 0, NULL, NULL),
(507, '245', 'Title Statement', '[108, 109]', '[112,99,100,101,102,103,104,105,106,107]', 0, NULL, NULL),
(520, '246', 'Varying Form of Title', '[116,117,118,29]', '[119,120,121,122,123,124,125,126,127,128]', 1, NULL, NULL),
(532, '247', 'Former Title', '[108, 109]', '[129,130]', 1, NULL, NULL),
(543, '250', 'Edition Statement', '[1]', '[1]', 1, NULL, NULL),
(549, '251', 'Version Information', '[1]', '[1]', 1, NULL, NULL),
(556, '254', 'Musical Presentation Statement', '[1]', '[1]', 0, NULL, NULL),
(559, '255', 'Cartographic Mathematical Data', '[1]', '[1]', 1, NULL, NULL),
(569, '256', 'Computer File Characteristics', '[1]', '[1]', 0, NULL, NULL),
(573, '257', 'Country of Producing Entity', '[1]', '[1]', 1, NULL, NULL),
(579, '258', 'Philatelic Issue Data', '[1]', '[1]', 1, NULL, NULL),
(583, '260', 'Publication, Distribution, etc. (Imprint)', '[43,132,133]', '[1]', 1, NULL, NULL),
(592, '263', 'Projected Publication Date', '[1]', '[1]', 0, NULL, NULL),
(595, '264', 'Production, Publication, Distribution, Manufacture, and Copyright Notice', '[43,44,45]', '[134,135,136,137,138]', 1, NULL, NULL),
(602, '270', 'Address', '[139,140]', '[119,141,142]', 1, NULL, NULL),
(623, '300', 'Physical Description', '[1]', '[1]', 1, NULL, NULL),
(633, '306', 'Playing Time', '[1]', '[1]', 0, NULL, NULL),
(636, '307', 'Hours, etc.', '[143,5]', '[1]', 1, NULL, NULL),
(640, '310', 'Current Publication Frequency', '[1]', '[1]', 1, NULL, NULL),
(647, '321', 'Former Publication Frequency', '[1]', '[1]', 1, NULL, NULL),
(654, '334', 'Mode of Issuance', '[1]', '[1]', 1, NULL, NULL),
(661, '335', 'Extension Plan', '[1]', '[1]', 1, NULL, NULL),
(670, '336', 'Content Type', '[1]', '[1]', 1, NULL, NULL),
(679, '337', 'Media Type', '[1]', '[1]', 1, NULL, NULL),
(687, '338', 'Carrier Type', '[1]', '[1]', 1, NULL, NULL),
(695, '340', 'Physical Medium', '[1]', '[1]', 1, NULL, NULL),
(718, '341', 'Accessibility Content', '[16,144,145]', '[1]', 1, NULL, NULL),
(727, '342', 'Geospatial Reference Data', '[146,147]', '[148,149,150,151,152,153,154,155,156]', 1, NULL, NULL),
(753, '343', 'Planar Coordinate Data', '[1]', '[1]', 1, NULL, NULL),
(764, '344', 'Sound Characteristics', '[1]', '[1]', 1, NULL, NULL),
(780, '345', 'Moving Image Characteristics', '[1]', '[1]', 1, NULL, NULL),
(790, '346', 'Video Characteristics', '[1]', '[1]', 1, NULL, NULL),
(798, '347', 'Digital File Characteristics', '[1]', '[1]', 1, NULL, NULL),
(810, '348', 'Notated Music Characteristics', '[1]', '[1]', 1, NULL, NULL),
(821, '351', 'Organization and Arrangement of Materials', '[1]', '[1]', 1, NULL, NULL),
(827, '352', 'Digital Graphic Representation', '[1]', '[1]', 1, NULL, NULL),
(838, '353', 'Supplementary Content Characteristics', '[1]', '[1]', 1, NULL, NULL),
(846, '355', 'Security Classification Control', '[157,158,159,160,161,162,163]', '[1]', 1, NULL, NULL),
(857, '357', 'Originator Dissemination Control', '[1]', '[1]', 0, NULL, NULL),
(863, '361', 'Structured Ownership and Custodial History', '[16,164,165]', '[1]', 1, NULL, NULL),
(864, '362', 'Dates of Publication and/or Sequential Designation', '[166,167]', '[1]', 1, NULL, NULL),
(867, '363', 'Normalized Date and Sequential Designation', '[16,168,169]', '[170,171,172]', 1, NULL, NULL),
(886, '365', 'Trade Price', '[1]', '[1]', 1, NULL, NULL),
(901, '366', 'Trade Availability Information', '[1]', '[1]', 1, NULL, NULL),
(914, '370', 'Associated Place', '[1]', '[1]', 1, NULL, NULL),
(930, '377', 'Associated Language', '[1]', '[48,173]', 1, NULL, NULL),
(939, '380', 'Form of Work', '[1]', '[1]', 1, NULL, NULL),
(947, '381', 'Other Distinguishing Characteristics of Work or Expression', '[1]', '[1]', 1, NULL, NULL),
(957, '382', 'Medium of Performance', '[16,174,175,176,177]', '[16,178,179]', 1, NULL, NULL),
(974, '383', 'Numeric Designation of Musical Work', '[1]', '[1]', 1, NULL, NULL),
(984, '384', 'Key', '[180,181,182,183]', '[1]', 1, NULL, NULL),
(991, '385', 'Audience Characteristics', '[1]', '[1]', 1, NULL, NULL),
(1002, '386', 'Creator/Contributor Characteristics', '[1]', '[1]', 1, NULL, NULL),
(1015, '387', 'Representative Expression Characteristics', '[1]', '[1]', 1, NULL, NULL),
(1035, '388', 'Time Period of Creation', '[16,184.185]', '[1]', 1, NULL, NULL),
(1043, '490', 'Series Statement', '[186,187]', '[1]', 1, NULL, NULL),
(1053, '500', 'General Note', '[1]', '[1]', 1, NULL, NULL),
(1059, '501', 'With Note', '[1]', '[1]', 1, NULL, NULL),
(1064, '502', 'Dissertation Note', '[1]', '[1]', 1, NULL, NULL),
(1073, '504', 'Bibliography, etc. Note', '[1]', '[1]', 1, NULL, NULL),
(1077, '505', 'Formatted Contents Note', '[188,189,190,5]', '[191,192]', 1, NULL, NULL),
(1085, '506', 'Restrictions on Access Note', '[16,193,194]', '[1]', 1, NULL, NULL),
(1099, '507', 'Scale Note for Visual Materials', '[1]', '[1]', 0, NULL, NULL),
(1103, '508', 'Creation/Production Credits Note', '[1]', '[1]', 1, NULL, NULL),
(1107, '510', 'Citation/References Note', '[195,196,197,198,199]', '[1]', 1, NULL, NULL),
(1116, '511', 'Participant or Performer Note ', '[200,201]', '[1]', 1, NULL, NULL),
(1119, '513', 'Type of Report and Period Covered Note', '[1]', '[1]', 1, NULL, NULL),
(1123, '514', 'Data Quality Note', '[1]', '[1]', 0, NULL, NULL),
(1139, '515', 'Numbering Peculiarities Note', '[1]', '[1]', 1, NULL, NULL),
(1143, '516', 'Type of Computer File or Data Note', '[202,5]', '[1]', 1, NULL, NULL),
(1146, '518', 'Date/Time and Place of an Event Note', '[1]', '[1]', 1, NULL, NULL),
(1157, '520', 'Summary, etc.', '[203,204,205,206,207,208,5]', '[1]', 1, NULL, NULL),
(1166, '521', 'Target Audience Note', '[209,210,211,212,213,214,5]', '[1]', 1, NULL, NULL),
(1171, '522', 'Geographic Coverage Note', '[215,5]', '[1]', 1, NULL, NULL),
(1174, '524', 'Preferred Citation of Described Materials Note', '[216,5]', '[1]', 1, NULL, NULL),
(1179, '525', 'Supplement Note', '[1]', '[1]', 1, NULL, NULL),
(1182, '526', 'Study Program Information Note', '[217,5]', '[1]', 1, NULL, NULL),
(1192, '530', 'Additional Physical Form available Note', '[1]', '[1]', 1, NULL, NULL),
(1200, '532', 'Accessibility Note', '[218,219,220,5]', '[1]', 1, NULL, NULL),
(1203, '533', 'Reproduction Note', '[1]', '[1]', 1, NULL, NULL),
(1217, '534', 'Original Version Note', '[1]', '[1]', 1, NULL, NULL),
(1234, '535', 'Location of Originals/Duplicates Note', '[221,222]', '[1]', 1, NULL, NULL),
(1242, '536', 'Funding Information Note', '[1]', '[1]', 1, NULL, NULL),
(1253, '538', 'System Details Note', '[1]', '[1]', 1, NULL, NULL),
(1259, '540', 'Terms Governing Use and Reproduction Note', '[1]', '[1]', 1, NULL, NULL),
(1273, '541', 'Immediate Source of Acquisition Note', '[16,164,165]', '[1]', 1, NULL, NULL),
(1285, '542', 'Information Relating to Copyright Status', '[16,164,165]', '[1]', 1, NULL, NULL),
(1308, '544', 'Location of Other Archival Materials Note', '[16,223,224]', '[1]', 1, NULL, NULL),
(1317, '545', 'Biographical or Historical Data', '[16,225,226]', '[1]', 1, NULL, NULL),
(1322, '546', 'Language Note', '[1]', '[1]', 1, NULL, NULL),
(1328, '547', 'Former Title Complexity Note', '[1]', '[1]', 1, NULL, NULL),
(1331, '550', 'Issuing Body Note', '[1]', '[1]', 1, NULL, NULL),
(1335, '552', 'Entity and Attribute Information Note', '[1]', '[1]', 1, NULL, NULL),
(1355, '555', 'Cumulative Index/Finding Aids Note', '[227,228,5]', '[1]', 1, NULL, NULL),
(1364, '556', 'Information About Documentation Note', '[229,5]', '[1]', 1, NULL, NULL),
(1368, '561', 'Ownership and Custodial History', '[16,164,165]', '[1]', 1, NULL, NULL),
(1374, '562', 'Copy and Version Identification', '[1]', '[1]', 1, NULL, NULL),
(1383, '563', 'Binding Information', '[1]', '[1]', 1, NULL, NULL),
(1389, '565', 'Case File Characteristics Note', '[230,231,5]', '[1]', 1, NULL, NULL),
(1397, '567', 'Methodology Note', '[232,5]', '[1]', 1, NULL, NULL),
(1404, '580', 'Linking Entry Complexity Note', '[1]', '[1]', 1, NULL, NULL),
(1407, '581', 'Publications About Described Materials Note', '[233,5]', '[1]', 1, NULL, NULL),
(1412, '583', 'Action Note', '[16,164,165]', '[1]', 1, NULL, NULL),
(1434, '584', 'Accumulation and Frequency of Use Note', '[1]', '[1]', 1, NULL, NULL),
(1440, '585', 'Exhibitions Note', '[1]', '[1]', 1, NULL, NULL),
(1445, '586', 'Awards Note', '[234,5]', '[1]', 1, NULL, NULL),
(1449, '588', 'Source of Description, Etc. Note', '[16,235,236]', '[1]', 1, NULL, NULL),
(1453, '600', 'Subject Added Entry - Personal Name', '[92,93,94]', '[237,238,239,240,241,242,243,3]', 1, NULL, NULL),
(1485, '610', 'Subject Added Entry - Corporate Name', '[95,96,97]', '[237,238,239,240,241,242,243,3]', 1, NULL, NULL),
(1515, '611', 'Subject Added Entry - Meeting Name', '[95,96,97]', '[237,238,239,240,241,242,243,3]', 1, NULL, NULL),
(1543, '630', 'Subject Added Entry - Uniform Title', '[98,99,100,101,102,103,104,105,106,107]', '[237,238,239,240,241,242,243,3]', 1, NULL, NULL),
(1570, '647', 'Subject Added Entry - Named Event', '[1]', '[237,238,239,240,241,242,243,3]', 1, NULL, NULL),
(1584, '648', 'Subject Added Entry - Chronological Term', '[1]', '[237,238,239,240,241,242,243,3]', 1, NULL, NULL),
(1596, '650', 'Subject Added Entry - Topical Term', '[16,244,139,140]', '[237,238,239,240,241,242,243,3]', 1, NULL, NULL),
(1614, '651', 'Subject Added Entry - Geographic Name', '[1]', '[237,238,239,240,241,242,243,3]', 1, NULL, NULL),
(1629, '653', 'Index Term - Uncontrolled', '[16,244,139,140]', '[16,245,246,247,248,249,250,251]', 1, NULL, NULL),
(1633, '654', 'Subject Added Entry - Faceted Topical Terms', '[16,244,139,140]', '[1]', 1, NULL, NULL),
(1647, '655', 'Index Term - Genre/Form', '[191,252]', '[16,245,246,247,248,249,250,251]', 1, NULL, NULL),
(1662, '656', 'Index Term - Occupation', '[1]', '[3]', 1, NULL, NULL),
(1674, '657', 'Index Term - Function', '[1]', '[3]', 1, NULL, NULL),
(1685, '658', 'Index Term - Curriculum Objective', '[1]', '[1]', 1, NULL, NULL),
(1692, '662', 'Subject Added Entry - Hierarchical Place Name', '[1]', '[1]', 1, NULL, NULL),
(1706, '688', 'Subject Added Entry - Type of Entity Unspecified', '[1]', '[16,3]', 1, NULL, NULL),
(1716, '700', 'Added Entry - Personal Name', '[92,93,94]', '[16,253]', 1, NULL, NULL),
(1747, '710', 'Added Entry - Corporate Name', '[95,96,97]', '[16,253]', 1, NULL, NULL),
(1776, '711', 'Added Entry - Meeting Name', '[95,96,97]', '[16,253]', 1, NULL, NULL),
(1803, '720', 'Added Entry - Uncontrolled Name', '[170,246,254]', '[1]', 1, NULL, NULL),
(1808, '730', 'Added Entry - Uniform Title', '[98,99,100,101,102,103,104,105,106,107]', '[16,253]', 1, NULL, NULL),
(1832, '740', 'Added Entry - Uncontrolled Related/Analytical Title', '[112,99,100,101,102,103,104,105,106,107]', '[16,253]', 1, NULL, NULL),
(1839, '751', 'Added Entry - Geographic Name', '[1]', '[1]', 1, NULL, NULL),
(1850, '752', 'Added Entry - Hierarchical Place Name', '[1]', '[1]', 1, NULL, NULL),
(1864, '753', 'System Details Access to Computer Files', '[1]', '[1]', 1, NULL, NULL),
(1872, '754', 'Added Entry - Taxonomic Identification', '[1]', '[1]', 1, NULL, NULL),
(1882, '758', 'Resource Identifier', '[1]', '[1]', 1, NULL, NULL),
(1892, '760', 'Main Series Entry', '[129,130]', '[255,5]', 1, NULL, NULL),
(1912, '762', 'Subseries Entry', '[129,130]', '[256,5]', 1, NULL, NULL),
(1932, '765', 'Original Language Entry', '[129,130]', '[257,5]', 1, NULL, NULL),
(1957, '767', 'Translation Entry', '[129,130]', '[258,5]', 1, NULL, NULL),
(1980, '770', 'Supplement/Special Issue Entry', '[129,130]', '[259,5]', 1, NULL, NULL),
(2004, '772', 'Supplement Parent Entry', '[129,130]', '[260,261,5]', 1, NULL, NULL),
(2028, '773', 'Host Item Entry', '[129,130]', '[262,5]', 1, NULL, NULL),
(2053, '774', 'Constituent Unit Entry', '[129,130]', '[263,5]', 1, NULL, NULL),
(2077, '775', 'Other Edition Entry', '[129,130]', '[264,5]', 1, NULL, NULL),
(2103, '776', 'Additional Physical Form Entry', '[129,130]', '[265,5]', 1, NULL, NULL),
(2127, '777', 'Issued With Entry', '[129,130]', '[266,5]', 1, NULL, NULL),
(2151, '780', 'Preceding Entry', '[129,130]', '[267,268,269,270,271,272,273,274]', 1, NULL, NULL),
(2175, '785', 'Succeeding Entry', '[129,130]', '[275,276,277,278,279,280,281,282,283]', 1, NULL, NULL),
(2199, '786', 'Data Source Entry', '[129,130]', '[284,5]', 1, NULL, NULL),
(2226, '787', 'Other Relationship Entry', '[129,130]', '[285,5]', 1, NULL, NULL),
(2250, '788', 'Parallel Description in Another Language of Cataloging', '[129,130]', '[286,5]', 1, NULL, NULL),
(2265, '800', 'Series Added Entry - Personal Name', '[92,93,94]', '[1]', 1, NULL, NULL),
(2298, '810', 'Series Added Entry - Corporate Name', '[95,96,97]', '[1]', 1, NULL, NULL),
(2329, '811', 'Series Added Entry - Meeting Name', '[95,96,97]', '[1]', 1, NULL, NULL),
(2358, '830', 'Series Added Entry - Uniform Title', '[1]', '[112,99,100,101,102,103,104,105,106,107]', 1, NULL, NULL),
(2359, '841', 'Holdings Coded Data Values', '[]', '[]', 0, NULL, NULL),
(2360, '842 ', 'Textual Physical Form Designator', '[]', '[]', 0, NULL, NULL),
(2361, '843', 'Reproduction Note', '[]', '[]', 1, NULL, NULL),
(2362, '844', 'Name of Unit', '[]', '[]', 0, NULL, NULL),
(2363, '845', 'Terms Governing Use and Reproduction', '[]', '[]', 1, NULL, NULL),
(2384, '850', 'Holding Institution', '[1]', '[1]', 1, NULL, NULL),
(2386, '852', 'Location', '[16,287,288,289,290,291,292,293,3,294]', '[16,295,296,297]', 1, NULL, NULL),
(2387, '853', 'Captions and Pattern - Basic Bibliographic Unit', '[]', '[]', 1, NULL, NULL),
(2388, '854', 'Captions and Pattern - Supplementary Material', '[]', '[]', 1, NULL, NULL),
(2389, '855', 'Captions and Pattern - Indexes', '[]', '[]', 1, NULL, NULL),
(2390, '863', 'Enumeration and Chronology - Basic Bibliographic Unit', '[]', '[]', 1, NULL, NULL),
(2391, '864', 'Enumeration and Chronology - Supplementary Material', '[]', '[]', 1, NULL, NULL),
(2392, '865', 'Enumeration and Chronology - Indexes', '[]', '[]', 1, NULL, NULL),
(2393, '866', 'Textual Holdings - Basic Bibliographic Unit', '[]', '[]', 1, NULL, NULL),
(2394, '867', 'Textual Holdings - Supplementary Material', '[]', '[]', 1, NULL, NULL),
(2395, '868', 'Textual Holdings - Indexes', '[]', '[]', 1, NULL, NULL),
(2396, '876', 'Item Information - Basic Bibliographic Unit', '[]', '[]', 1, NULL, NULL),
(2397, '877', 'Item Information - Supplementary Material', '[]', '[]', 1, NULL, NULL),
(2398, '878', 'Item Information - Indexes', '[]', '[]', 1, NULL, NULL),
(2411, '856', 'Electronic Location and Access', '[16,298,299,300,301,302,303]', '[16,304,305,306,307,308,5]', 1, NULL, NULL),
(2412, '857', 'Electronic Archive Location and Access', '[16,299,302,303]', '[16,304,305,306,307,308,5]', 1, NULL, NULL),
(2413, '880', 'Alternate Graphic Representation', '[1]', '[1]', 1, NULL, NULL),
(2438, '881', 'Manifestation Statements', '[1]', '[1]', 1, NULL, NULL),
(2455, '882', 'Replacement Record Information', '[1]', '[1]', 0, NULL, NULL),
(2460, '883', 'Metadata Provenance', '[343,344,345,346]', '[1]', 1, NULL, NULL),
(2470, '884', 'Description Conversion Information', '[1]', '[1]', 1, NULL, NULL),
(2475, '885', 'Matching Information', '[1]', '[1]', 1, NULL, NULL),
(2486, '886', 'Tag of the foreign MARC field', '[347,348,349]', '[1]', 1, NULL, NULL),
(2489, '887', 'Non-MARC Information', '[1]', '[1]', 1, NULL, NULL),
(2499, '901', 'Local Data Element A, LDA (RLIN)', '[1]', '[1]', 0, NULL, NULL),
(2500, '999', 'Marctag not found', '[]', '[]', 0, NULL, NULL),
(2501, 'LDR', 'Leader', '[]', '[]', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `marctag_indicators_loc`
--

CREATE TABLE `marctag_indicators_loc` (
  `id` int(11) NOT NULL,
  `indicator` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marctag_indicators_loc`
--

INSERT INTO `marctag_indicators_loc` (`id`, `indicator`, `created_at`, `updated_at`) VALUES
(1, '# - Undefined', '2023-07-21 06:39:50', '2023-07-21 06:39:52'),
(2, '# - Library and Archives Canada', '2024-07-21 06:39:51', '2023-07-21 06:39:52'),
(3, '7 - Source specified in subfield $2', '2023-07-21 06:40:22', '2023-07-21 06:40:23'),
(4, '# - Copyright or legal deposit number', '2023-07-21 06:42:28', '2023-07-21 06:42:30'),
(5, '8 - No display constant generated', '2023-07-21 06:42:41', '2023-07-21 06:42:42'),
(6, '# - No level specified', '2023-07-21 06:44:54', '2023-07-21 06:44:55'),
(7, '0 - Continuing resource of international interest', '2023-07-21 06:45:05', '2023-07-21 06:45:06'),
(8, '1 - Continuing resource not of international inter', '2023-07-21 06:45:16', '2023-07-21 06:45:17'),
(9, '0 - International Standard Recording Code', '2023-07-21 06:46:18', '2023-07-21 06:46:19'),
(10, '1 - Universal Product Code', '2023-07-21 06:46:37', '2023-07-21 06:46:37'),
(11, '2 - International Standard Music Number', '2023-07-21 06:46:49', '2023-07-21 06:46:49'),
(12, '3 - International Article Number', '2023-07-21 06:47:00', '2023-07-21 06:47:01'),
(13, '4 - Serial Item and Contribution Identifier', '2023-07-21 06:47:20', '2023-07-21 06:47:20'),
(15, '8 - Unspecified type of standard number or code', '2023-07-21 06:48:27', '2023-07-21 06:48:28'),
(16, '# - No information provided', '2023-07-21 06:50:24', '2023-07-21 06:50:25'),
(17, '0 - No difference', '2023-07-21 06:50:43', '2023-07-21 06:50:44'),
(18, '1 - Difference', '2023-07-21 06:50:55', '2023-07-21 06:50:55'),
(19, '0 - Issue number', '2023-07-21 06:54:23', '2023-07-21 06:54:24'),
(20, '1 - Matrix number', '2023-07-21 06:54:34', '2023-07-21 06:54:35'),
(21, '2 - Plate number', '2023-07-21 06:54:52', '2023-07-21 06:54:53'),
(22, '3 - Other music publisher number', '2023-07-21 06:55:21', '2023-07-21 06:55:22'),
(23, '4 - Video recording publisher number', '2023-07-21 06:55:31', '2023-07-21 06:55:32'),
(24, '5 - Other publisher number', '2023-07-21 06:55:50', '2023-07-21 06:55:51'),
(25, '6 - Distributor number', '2023-07-21 06:56:01', '2023-07-21 06:56:02'),
(26, '0 - No note, no added entry', '2023-07-21 06:57:20', '2023-07-21 06:57:20'),
(27, '1 - Note, added entry', '2023-07-21 06:57:32', '2023-07-21 06:57:33'),
(28, '2 - Note, no added entry', '2023-07-21 06:57:43', '2023-07-21 06:57:44'),
(29, '3 - No note, added entry', '2023-07-21 06:57:58', '2023-07-21 06:57:59'),
(30, '# - No date information', '2023-07-21 07:01:30', '2023-07-21 07:01:31'),
(31, '0 - Single date', '2023-07-21 07:01:40', '2023-07-21 07:01:41'),
(32, '1 - Multiple single dates', '2023-07-21 07:01:49', '2023-07-21 07:01:49'),
(33, '2 - Range of dates', '2023-07-21 07:02:03', '2023-07-21 07:02:04'),
(34, '0 - Capture', '2023-07-21 07:04:20', '2023-07-21 07:04:21'),
(35, '1 - Broadcast', '2023-07-21 07:04:31', '2023-07-21 07:04:31'),
(36, '2 - Finding', '2023-07-21 07:04:41', '2023-07-21 07:04:42'),
(37, '0 - Scale indeterminable/No scale recorded', '2023-07-21 07:07:23', '2023-07-21 07:07:24'),
(38, '1 - Single scale', '2023-07-21 07:07:49', '2023-07-21 07:07:49'),
(39, '3 - Range of scales', '2023-07-21 07:08:18', '2023-07-21 07:08:19'),
(40, '# - Not applicable', '2023-07-21 07:09:24', '2023-07-21 07:09:25'),
(41, '0 - Outer ring', '2023-07-21 07:09:51', '2023-07-21 07:09:52'),
(42, '1 - Exclusion ring', '2023-07-21 07:10:18', '2023-07-21 07:10:18'),
(43, '# - Not applicable/No information provided/Earliest', '2023-07-21 07:11:46', '2023-07-21 07:11:46'),
(44, '2 - Intervening', '2023-07-21 07:12:09', '2023-07-21 07:12:10'),
(45, '3 - Current/Latest', '2023-07-21 07:12:29', '2023-07-21 07:12:29'),
(46, '0 - Item not a translation/does not include a tran', '2023-07-21 07:17:49', '2023-07-21 07:17:49'),
(47, '1 - Item is or includes a translation', '2023-07-21 07:18:08', '2023-07-21 07:18:09'),
(48, '# - MARC language code', '2023-07-21 07:20:05', '2023-07-21 07:20:06'),
(49, '# - Subfield $b or $c not present', '2023-07-21 07:22:58', '2023-07-21 07:22:59'),
(50, '0 - Single date/time', '2023-07-21 07:23:27', '2023-07-21 07:23:28'),
(51, '1 - Multiple single dates/times', '2023-07-21 07:23:59', '2023-07-21 07:24:00'),
(52, '2 - Range of dates/times', '2023-07-21 07:24:16', '2023-07-21 07:24:17'),
(53, '1 - Work', '2023-07-21 07:25:44', '2023-07-21 07:25:44'),
(54, '2 - Expression', '2023-07-21 07:26:02', '2023-07-21 07:26:03'),
(55, '3 - Manifestation', '2023-07-21 07:26:23', '2023-07-21 07:26:23'),
(56, '# - MARC musical composition code', '2023-07-21 07:27:49', '2023-07-21 07:27:49'),
(57, '# - MARC code', '2023-07-21 07:48:10', '2023-07-21 07:48:10'),
(58, '0 - Item is in LC', '2023-07-21 07:49:53', '2023-07-21 07:49:54'),
(59, '1 - Item is not in LC', '2023-07-21 07:50:14', '2023-07-21 07:50:15'),
(60, '0 - Assigned by LC', '2023-07-21 07:51:36', '2023-07-21 07:51:37'),
(61, '4 - Assigned by agency other than LC', '2023-07-21 07:52:08', '2023-07-21 07:52:08'),
(62, '# - Library of Congress Classification', '2023-07-21 07:53:29', '2023-07-21 07:53:30'),
(63, '1 - U.S. Dept. of Defense Classification', '2023-07-21 07:54:50', '2023-07-21 07:54:50'),
(64, '# - Information not provided', '2023-07-21 07:57:08', '2023-07-21 07:57:09'),
(65, '0 - Work held by LAC', '2023-07-21 07:57:32', '2023-07-21 07:57:33'),
(66, '1 - Work not held by LAC', '2023-07-21 07:57:54', '2023-07-21 07:57:54'),
(67, '0 - LC-based call number assigned by LAC', '2023-07-21 07:58:48', '2023-07-21 07:58:48'),
(68, '1 - Complete LC class number assigned by LAC', '2023-07-21 08:00:48', '2023-07-21 08:00:48'),
(69, '2 - Incomplete LC class number assigned by LAC', '2023-07-21 08:01:07', '2023-07-21 08:01:08'),
(70, '3 - LC-based call number assigned by the contributing library', '2023-07-21 08:01:27', '2023-07-21 08:01:28'),
(71, '4 - Complete LC class number assigned by the contributing library', '2023-07-21 08:01:48', '2023-07-21 08:01:49'),
(72, '5 - Incomplete LC class number assigned by the contributing library', '2023-07-21 08:02:08', '2023-07-21 08:02:08'),
(73, '6 - Other call number assigned by LAC', '2023-07-21 08:02:26', '2023-07-21 08:02:27'),
(74, '7 - Other class number assigned by LAC', '2023-07-21 08:02:48', '2023-07-21 08:02:49'),
(75, '8 - Other call number assigned by the contributing library', '2023-07-21 08:04:30', '2023-07-21 08:04:31'),
(76, '9 - Other class number assigned by the contributing library', '2023-07-21 08:04:49', '2023-07-21 08:05:01'),
(77, '0 - Item is in NLM', '2023-07-21 08:16:14', '2023-07-21 08:16:15'),
(78, '1 - Item is not in NLM', '2023-07-21 08:16:24', '2023-07-21 08:16:25'),
(79, '0 - Assigned by NLM', '2023-07-21 08:17:23', '2023-07-21 08:17:24'),
(80, '4 - Assigned by agency other than NLM', '2023-07-21 08:17:40', '2023-07-21 08:17:40'),
(81, '0 - Item is in NAL', '2023-07-21 08:20:00', '2023-07-21 08:20:00'),
(82, '1 - Item is not in NAL', '2023-07-21 08:20:20', '2023-07-21 08:20:21'),
(83, '0 - NAL subject category code list', '2023-07-21 08:21:53', '2023-07-21 08:21:53'),
(84, '0 - Full', '2023-07-21 08:31:10', '2023-07-21 08:31:11'),
(85, '1 - Abridged', '2023-07-21 08:31:35', '2023-07-21 08:31:41'),
(86, '0 - Full edition', '2023-07-21 08:33:12', '2023-07-21 08:33:13'),
(87, '1 - Abridged edition', '2023-07-21 08:34:04', '2023-07-21 08:34:04'),
(88, '7 - Other edition specified in subfield $2', '2023-07-21 08:34:03', '2023-07-21 08:34:05'),
(89, '# - Source specified in subfield $2', '2023-07-21 08:39:50', '2023-07-21 08:39:52'),
(90, '0 - Superintendent of Documents Classification System', '2023-07-21 08:40:13', '2023-07-21 08:40:14'),
(91, '1 - Government of Canada Publications: Outline of Classification', '2023-07-21 08:40:36', '2023-07-21 08:40:36'),
(92, '0 - Forename', '2023-07-21 08:44:21', '2023-07-21 08:44:22'),
(93, '1 - Surname', '2023-07-21 08:44:39', '2023-07-21 08:44:41'),
(94, '3 - Family name', '2023-07-21 08:44:57', '2023-07-21 08:44:57'),
(95, '0 - Inverted name', '2023-07-21 08:50:33', '2023-07-21 08:50:34'),
(96, '1 - Jurisdiction name', '2023-07-21 08:51:04', '2023-07-21 08:51:05'),
(97, '2 - Name in direct order', '2023-07-21 08:51:26', '2023-07-21 08:51:27'),
(98, '0 - Number of nonfiling characters', '2023-07-21 08:53:49', '2023-07-21 08:53:50'),
(99, '1 - Number of nonfiling characters', '2023-07-21 08:54:13', '2023-07-21 08:54:14'),
(100, '2 - Number of nonfiling characters', '2023-07-21 08:54:12', '2023-07-21 08:54:15'),
(101, '3 - Number of nonfiling characters', '2023-07-21 08:54:21', '2023-07-21 08:54:24'),
(102, '4 - Number of nonfiling characters', '2023-07-21 08:54:41', '2023-07-21 08:54:44'),
(103, '5 - Number of nonfiling characters', '2023-07-21 08:54:51', '2023-07-21 08:54:52'),
(104, '6 - Number of nonfiling characters', '2023-07-21 08:55:02', '2023-07-21 08:55:02'),
(105, '7 - Number of nonfiling characters', '2023-07-21 08:55:10', '2023-07-21 08:55:10'),
(106, '8 - Number of nonfiling characters', '2023-07-21 08:55:18', '2023-07-21 08:55:21'),
(107, '9 - Number of nonfiling characters', '2023-07-21 08:55:29', '2023-07-21 08:55:30'),
(108, '0 - No added entry', '2023-07-21 08:57:21', '2023-07-21 08:57:22'),
(109, '1 - Added entry', '2023-07-21 08:57:46', '2023-07-21 08:57:47'),
(110, '# - Abbreviated key title', '2023-07-21 08:58:34', '2023-07-21 08:58:35'),
(111, '0 - Other abbreviated title', '2023-07-21 08:58:57', '2023-07-21 08:58:57'),
(112, '0 - No nonfiling characters', '2023-07-21 09:00:16', '2023-07-21 09:00:17'),
(114, '0 - Not printed or displayed', '2023-07-21 10:05:15', '2023-07-21 10:05:16'),
(115, '1 - Printed or displayed', '2023-07-21 10:05:41', '2023-07-21 10:05:42'),
(116, '0 - Note, no added entry', '2023-07-21 10:24:24', '2023-07-21 10:24:25'),
(117, '1 - Note, added entry', '2023-07-21 10:25:43', '2023-07-21 10:25:44'),
(118, '2 - No note, no added entry', '2023-07-21 10:26:08', '2023-07-21 10:26:09'),
(119, '# - No type specified', '2023-07-21 10:36:20', '2023-07-21 10:36:21'),
(120, '0 - Portion of title', '2023-07-21 10:36:43', '2023-07-21 10:36:44'),
(121, '1 - Parallel title', '2023-07-21 10:37:06', '2023-07-21 10:37:07'),
(122, '2 - Distinctive title', '2023-07-21 10:37:26', '2023-07-21 10:37:27'),
(123, '3 - Other title', '2023-07-21 10:37:49', '2023-07-21 10:37:50'),
(124, '4 - Cover title', '2023-07-21 10:38:19', '2023-07-21 10:38:20'),
(125, '5 - Added title page title', '2023-07-21 10:38:38', '2023-07-21 10:38:39'),
(126, '6 - Caption title', '2023-07-21 10:43:58', '2023-07-21 10:43:59'),
(127, '7 - Running title', '2023-07-21 10:45:48', '2023-07-21 10:45:49'),
(128, '8 - Spine title', '2023-07-21 10:46:06', '2023-07-21 10:46:07'),
(129, '0 - Display note', '2023-07-21 10:50:44', '2023-07-21 10:50:44'),
(130, '1 - Do not display note', '2023-07-21 10:52:03', '2023-07-21 10:52:04'),
(132, '2 - Intervening publisher', '2023-07-21 10:58:11', '2023-07-21 10:58:11'),
(133, '3 - Current/latest publisher', '2023-07-21 10:58:34', '2023-07-21 10:58:34'),
(134, '0 - Production', '2023-07-21 11:06:51', '2023-07-21 11:06:51'),
(135, '1 - Publication', '2023-07-21 11:08:55', '2023-07-21 11:08:56'),
(136, '2 - Distribution', '2023-07-21 11:09:12', '2023-07-21 11:09:13'),
(137, '3 - Manufacture', '2023-07-21 11:09:41', '2023-07-21 11:09:42'),
(138, '4 - Copyright notice date', '2023-07-21 11:16:06', '2023-07-21 11:16:06'),
(139, '1 - Primary', '2023-07-21 11:18:09', '2023-07-21 11:18:10'),
(140, '2 - Secondary', '2023-07-21 11:18:27', '2023-07-21 11:18:28'),
(141, '0 - Mailing', '2023-07-21 11:30:14', '2023-07-21 11:30:13'),
(142, '7 - Type specified in subfield $i', '2023-07-21 11:30:35', '2023-07-21 11:30:36'),
(143, '# - Hours', '2023-07-21 22:11:22', '2023-07-21 22:11:23'),
(144, '0 - Adaptive features to access primary content', '2023-07-21 22:15:15', '2023-07-21 22:15:16'),
(145, '1 - Adaptive features to access secondary content', '2023-07-21 22:15:38', '2023-07-21 22:15:38'),
(146, '0 - Horizontal coordinate system', '2023-07-21 22:16:29', '2023-07-21 22:16:29'),
(147, '1 - Vertical coordinate system', '2023-07-21 22:16:44', '2023-07-21 22:16:45'),
(148, '0 - Geographic', '2023-07-21 22:17:45', '2023-07-21 22:17:46'),
(149, '1 - Map projection', '2023-07-21 22:18:03', '2023-07-21 22:18:03'),
(150, '2 - Grid coordinate system', '2023-07-21 22:18:15', '2023-07-21 22:18:16'),
(151, '3 - Local planar', '2023-07-21 22:18:53', '2023-07-21 22:18:54'),
(152, '4 - Local', '2023-07-21 22:19:57', '2023-07-21 22:19:57'),
(153, '5 - Geodetic model', '2023-07-21 22:20:20', '2023-07-21 22:20:20'),
(154, '6 - Altitude', '2023-07-21 22:20:35', '2023-07-21 22:20:36'),
(155, '7 - Method specified in $2', '2023-07-21 22:20:54', '2023-07-21 22:20:55'),
(156, '8 - Depth', '2023-07-21 22:21:04', '2023-07-21 22:21:05'),
(157, '0 - Document', '2023-07-21 22:25:43', '2023-07-21 22:25:44'),
(158, '1 - Title', '2023-07-21 22:26:06', '2023-07-21 22:26:07'),
(159, '2 - Abstract', '2023-07-21 22:26:21', '2023-07-21 22:26:22'),
(160, '3 - Contents note', '2023-07-21 22:26:36', '2023-07-21 22:26:36'),
(161, '4 - Author', '2023-07-21 22:26:51', '2023-07-21 22:26:52'),
(162, '5 - Record', '2023-07-21 22:27:07', '2023-07-21 22:27:07'),
(163, '8 - None of the above', '2023-07-21 22:27:23', '2023-07-21 22:27:24'),
(164, '0 - Private', '2023-07-21 22:30:18', '2023-07-21 22:30:19'),
(165, '1 - Not private', '2023-07-21 22:30:37', '2023-07-21 22:30:38'),
(166, '0 - Formatted style', '2023-07-21 22:32:27', '2023-07-21 22:32:28'),
(167, '1 - Unformatted note', '2023-07-21 22:32:54', '2023-07-21 22:32:55'),
(168, '0 - Starting information', '2023-07-21 22:34:34', '2023-07-21 22:34:34'),
(169, '1 - Ending information', '2023-07-21 22:34:53', '2023-07-21 22:34:53'),
(170, '# - Not specified', '2023-07-21 22:35:46', '2023-07-21 22:35:46'),
(171, '0 - Closed', '2023-07-21 22:36:03', '2023-07-21 22:36:03'),
(172, '1 - Open', '2023-07-21 22:36:23', '2023-07-21 22:36:23'),
(173, '7 - Source specified in $2', '2023-07-21 22:38:41', '2023-07-21 22:38:41'),
(174, '0 - Medium of performance', '2023-07-21 22:40:24', '2023-07-21 22:40:25'),
(175, '1 - Partial medium of performance', '2023-07-21 22:40:43', '2023-07-21 22:40:44'),
(176, '2 - Medium of performance of musical content of representative expression', '2023-07-21 22:41:42', '2023-07-21 22:41:43'),
(177, '3 - Partial medium of performance of musical content of representative expression', '2023-07-21 22:42:06', '2023-07-21 22:42:06'),
(178, '0 - Not intended for access', '2023-07-21 22:43:14', '2023-07-21 22:43:15'),
(179, '1 - Intended for access', '2023-07-21 22:43:30', '2023-07-21 22:43:31'),
(180, '# - Relationship to original unknown', '2023-07-21 22:44:58', '2023-07-21 22:44:59'),
(181, '0 - Original key', '2023-07-21 22:45:14', '2023-07-21 22:45:14'),
(182, '1 - Transposed key', '2023-07-21 22:45:32', '2023-07-21 22:45:33'),
(183, '2 - Key of representative expression', '2023-07-21 22:45:47', '2023-07-21 22:45:47'),
(184, '1 - Creation of work', '2023-07-21 22:48:09', '2023-07-21 22:48:09'),
(185, '2 - Creation of aggregate work', '2023-07-21 22:48:20', '2023-07-21 22:48:20'),
(186, '0 - Series not traced', '2023-07-21 22:49:52', '2023-07-21 22:49:52'),
(187, '1 - Series traced', '2023-07-21 22:50:02', '2023-07-21 22:50:03'),
(188, '0 - Contents', '2023-07-21 22:51:58', '2023-07-21 22:51:58'),
(189, '1 - Incomplete contents', '2023-07-21 22:52:31', '2023-07-21 22:52:34'),
(190, '2 - Partial contents', '2023-07-21 22:52:52', '2023-07-21 22:52:52'),
(191, '# - Basic', '2023-07-21 22:54:17', '2023-07-21 22:54:18'),
(192, '0 - Enhanced', '2023-07-21 22:54:29', '2023-07-21 22:54:29'),
(193, '0 - No restrictions', '2023-07-21 22:55:59', '2023-07-21 22:56:00'),
(194, '1 - Restrictions apply', '2023-07-21 22:56:20', '2023-07-21 22:56:19'),
(195, '0 - Coverage unknown', '2023-07-21 22:58:08', '2023-07-21 22:58:08'),
(196, '1 - Coverage complete', '2023-07-21 22:58:18', '2023-07-21 22:58:19'),
(197, '2 - Coverage is selective', '2023-07-21 22:58:31', '2023-07-21 22:58:31'),
(198, '3 - Location in source not given', '2023-07-21 22:58:40', '2023-07-21 22:58:41'),
(199, '4 - Location in source given', '2023-07-21 22:58:49', '2023-07-21 22:58:49'),
(200, '0 - No display constant generated', '2023-07-21 23:00:07', '2023-07-21 23:00:07'),
(201, '1 - Cast', '2023-07-21 23:00:16', '2023-07-21 23:00:17'),
(202, '# - Type of file', '2023-07-21 23:02:03', '2023-07-21 23:02:03'),
(203, '# - Summary', '2023-07-21 23:03:28', '2023-07-21 23:03:28'),
(204, '0 - Subject', '2023-07-21 23:03:42', '2023-07-21 23:03:43'),
(205, '1 - Review', '2023-07-21 23:03:53', '2023-07-21 23:03:53'),
(206, '2 - Scope and content', '2023-07-21 23:04:16', '2023-07-21 23:04:17'),
(207, '3 - Abstract', '2023-07-21 23:04:30', '2023-07-21 23:04:31'),
(208, '4 - Content advice', '2023-07-21 23:04:45', '2023-07-21 23:04:45'),
(209, '# - Audience', '2023-07-21 23:06:02', '2023-07-21 23:06:03'),
(210, '0 - Reading grade level', '2023-07-21 23:06:17', '2023-07-21 23:06:18'),
(211, '1 - Interest age level', '2023-07-21 23:06:28', '2023-07-21 23:06:28'),
(212, '2 - Interest grade level', '2023-07-21 23:06:56', '2023-07-21 23:06:57'),
(213, '3 - Special audience characteristics', '2023-07-21 23:07:11', '2023-07-21 23:07:12'),
(214, '4 - Motivation/interest level', '2023-07-21 23:07:40', '2023-07-21 23:07:40'),
(215, '# - Geographic coverage', '2023-07-21 23:09:18', '2023-07-21 23:09:19'),
(216, '# - Cite as', '2023-07-21 23:10:31', '2023-07-21 23:10:31'),
(217, '0 - Reading program', '2023-07-21 23:11:51', '2023-07-21 23:11:51'),
(218, '0 - Accessibility technical details', '2023-07-21 23:13:25', '2023-07-21 23:13:26'),
(219, '1 - Accessibility features', '2023-07-21 23:13:34', '2023-07-21 23:13:35'),
(220, '2 - Accessibility deficiencies', '2023-07-21 23:13:49', '2023-07-21 23:13:50'),
(221, '1 - Holder of originals', '2023-07-21 23:15:42', '2023-07-21 23:15:42'),
(222, '2 - Holder of duplicates', '2023-07-21 23:15:58', '2023-07-21 23:15:59'),
(223, '0 - Associated materials', '2023-07-21 23:20:33', '2023-07-21 23:20:34'),
(224, '1 - Related materials', '2023-07-21 23:20:45', '2023-07-21 23:20:46'),
(225, '0 - Biographical sketch', '2023-07-21 23:22:35', '2023-07-21 23:22:35'),
(226, '1 - Administrative history', '2023-07-21 23:22:47', '2023-07-21 23:22:47'),
(227, '# - Indexes', '2023-07-21 23:24:56', '2023-07-21 23:24:57'),
(228, '0 - Finding aids', '2023-07-21 23:25:11', '2023-07-21 23:25:12'),
(229, '# - Documentation', '2023-07-21 23:28:08', '2023-07-21 23:28:08'),
(230, '# - File size', '2023-07-21 23:30:44', '2023-07-21 23:30:45'),
(231, '0 - Case file characteristics', '2023-07-21 23:31:07', '2023-07-21 23:31:08'),
(232, '# - Methodology', '2023-07-21 23:32:12', '2023-07-21 23:32:12'),
(233, '# - Publications', '2023-07-21 23:34:58', '2023-07-21 23:34:58'),
(234, '# - Awards', '2023-07-21 23:38:01', '2023-07-21 23:38:02'),
(235, '0 - Source of description', '2023-07-21 23:39:40', '2023-07-21 23:39:41'),
(236, '1 - Latest issue consulted', '2023-07-21 23:40:06', '2023-07-21 23:40:07'),
(237, '0 - Library of Congress Subject Headings', '2023-07-21 23:42:53', '2023-07-21 23:42:54'),
(238, '1 - Library of Congress Children\'s and Young Adults\' Subject Headings', '2023-07-21 23:43:38', '2023-07-21 23:43:39'),
(239, '2 - Medical Subject Headings', '2023-07-21 23:43:56', '2023-07-21 23:43:57'),
(240, '3 - National Agricultural Library subject authority file', '2023-07-21 23:44:06', '2023-07-21 23:44:07'),
(241, '4 - Source not specified', '2023-07-21 23:44:29', '2023-07-21 23:44:29'),
(242, '5 - Canadian Subject Headings', '2023-07-21 23:44:52', '2023-07-21 23:44:52'),
(243, '6 - Répertoire de vedettes-matière', '2023-07-21 23:45:32', '2023-07-21 23:45:33'),
(244, '0 - No level specified', '2023-07-21 23:53:34', '2023-07-21 23:53:34'),
(245, '0 - Topical term', '2023-07-21 23:58:39', '2023-07-21 23:58:40'),
(246, '1 - Personal name', '2023-07-21 23:58:53', '2023-07-21 23:58:53'),
(247, '2 - Corporate name', '2023-07-21 23:59:21', '2023-07-21 23:59:22'),
(248, '3 - Meeting name', '2023-07-21 23:59:32', '2023-07-21 23:59:32'),
(249, '4 - Chronological term', '2023-07-21 23:59:46', '2023-07-21 23:59:47'),
(250, '5 - Geographic name', '2023-07-22 00:00:07', '2023-07-22 00:00:07'),
(251, '6 - Genre/form term', '2023-07-22 00:00:25', '2023-07-22 00:00:26'),
(252, '0 - Faceted', '2023-07-22 00:03:02', '2023-07-22 00:03:02'),
(253, '2 - Analytical entry', '2023-07-22 00:09:22', '2023-07-22 00:09:23'),
(254, '2 - Other', '2023-07-22 00:36:49', '2023-07-22 00:36:49'),
(255, '# - Main series', '2023-07-22 00:42:32', '2023-07-22 00:42:33'),
(256, '# - Has subseries', '2023-07-22 00:43:35', '2023-07-22 00:43:35'),
(257, '# - Translation of', '2023-07-22 00:44:49', '2023-07-22 00:44:48'),
(258, '# - Translated as', '2023-07-22 00:45:50', '2023-07-22 00:45:51'),
(259, '# - Has supplement', '2023-07-22 00:46:38', '2023-07-22 00:46:39'),
(260, '# - Supplement to', '2023-07-22 00:47:28', '2023-07-22 00:47:29'),
(261, '0 - Parent', '2023-07-22 00:47:44', '2023-07-22 00:47:44'),
(262, '# - In', '2023-07-22 00:48:47', '2023-07-22 00:48:48'),
(263, '# - Constituent unit', '2023-07-22 00:49:48', '2023-07-22 00:49:49'),
(264, '# - Other edition available', '2023-07-22 00:50:48', '2023-07-22 00:50:49'),
(265, '# - Available in another form', '2023-07-22 00:51:35', '2023-07-22 00:51:36'),
(266, '# - Issued with', '2023-07-22 00:52:31', '2023-07-22 00:52:31'),
(267, '0 - Continues', '2023-07-22 00:53:31', '2023-07-22 00:53:31'),
(268, '1 - Continues in part', '2023-07-22 00:53:52', '2023-07-22 00:53:53'),
(269, '2 - Supersedes', '2023-07-22 00:54:16', '2023-07-22 00:54:16'),
(270, '3 - Supersedes in part', '2023-07-22 00:54:31', '2023-07-22 00:54:31'),
(271, '4 - Formed by the union of ... and ...', '2023-07-22 00:55:01', '2023-07-22 00:55:02'),
(272, '5 - Absorbed', '2023-07-22 00:55:04', '2023-07-22 00:55:04'),
(273, '6 - Absorbed in part', '2023-07-22 00:55:22', '2023-07-22 00:55:23'),
(274, '7 - Separated from', '2023-07-22 00:55:38', '2023-07-22 00:55:38'),
(275, '0 - Continued by', '2023-07-22 00:56:59', '2023-07-22 00:56:59'),
(276, '1 - Continued in part by', '2023-07-22 00:57:15', '2023-07-22 00:57:15'),
(277, '2 - Superseded by', '2023-07-22 00:57:31', '2023-07-22 00:57:32'),
(278, '3 - Superseded in part by', '2023-07-22 00:57:47', '2023-07-22 00:57:48'),
(279, '4 - Absorbed by', '2023-07-22 00:58:03', '2023-07-22 00:58:04'),
(280, '5 - Absorbed in part by', '2023-07-22 00:58:27', '2023-07-22 00:58:27'),
(281, '6 - Split into ... and ...', '2023-07-22 00:58:51', '2023-07-22 00:58:51'),
(282, '7 - Merged with ... to form ...', '2023-07-22 00:59:09', '2023-07-22 00:59:10'),
(283, '8 - Changed back to', '2023-07-22 00:59:24', '2023-07-22 00:59:25'),
(284, '# - Data source', '2023-07-22 01:00:57', '2023-07-22 01:00:57'),
(285, '# - Related item', '2023-07-22 01:01:54', '2023-07-22 01:01:55'),
(286, '# - Parallel description in another language of cataloging', '2023-07-22 01:02:54', '2023-07-22 01:02:54'),
(287, '0 - Library of Congress classification', '2023-07-22 01:08:05', '2023-07-22 01:08:05'),
(288, '1 - Dewey Decimal classification', '2023-07-22 01:08:19', '2023-07-22 01:08:20'),
(289, '2 - National Library of Medicine classification', '2023-07-22 01:08:34', '2023-07-22 01:08:34'),
(290, '3 - Superintendent of Documents classification', '2023-07-22 01:08:50', '2023-07-22 01:08:50'),
(291, '4 - Shelving control number', '2023-07-22 01:09:10', '2023-07-22 01:09:10'),
(292, '5 - Title', '2023-07-22 01:09:28', '2023-07-22 01:09:28'),
(293, '6 - Shelved separately', '2023-07-22 01:09:48', '2023-07-22 01:09:49'),
(294, '8 - Other scheme', '2023-07-22 01:10:21', '2023-07-22 01:10:22'),
(295, '0 - Not enumeration', '2023-07-22 01:12:00', '2023-07-22 01:12:00'),
(296, '1 - Primary enumeration', '2023-07-22 01:12:18', '2023-07-22 01:12:18'),
(297, '2 - Alternative enumeration', '2023-07-22 01:12:31', '2023-07-22 01:12:32'),
(298, '0 - Email', '2023-07-22 01:14:20', '2023-07-22 01:14:21'),
(299, '1 - FTP', '2023-07-22 01:14:30', '2023-07-22 01:14:30'),
(300, '2 - Remote login (Telnet)', '2023-07-22 01:14:39', '2023-07-22 01:14:40'),
(301, '3 - Dial-up', '2023-07-22 01:14:49', '2023-07-22 01:14:50'),
(302, '4 - HTTP', '2023-07-22 01:15:04', '2023-07-22 01:15:04'),
(303, '7 - Method specified in subfield $2', '2023-07-22 01:15:18', '2023-07-22 01:15:18'),
(304, '0 - Resource', '2023-07-22 01:16:09', '2023-07-22 01:16:10'),
(305, '1 - Version of resource', '2023-07-22 01:16:18', '2023-07-22 01:16:19'),
(306, '2 - Related resource', '2023-07-22 01:16:36', '2023-07-22 01:16:36'),
(307, '3 - Component part(s) of resource', '2023-07-22 01:16:47', '2023-07-22 01:16:48'),
(308, '4 - Version of component part(s) of resource', '2023-07-22 01:17:06', '2023-07-22 01:17:07'),
(343, '# - No information provided/not applicable', '2023-07-22 01:53:03', '2023-07-22 01:53:04'),
(344, '0 - Fully machine-generated', '2023-07-22 01:53:23', '2023-07-22 01:53:23'),
(345, '1 - Partially machine-generated', '2023-07-22 01:53:42', '2023-07-22 01:53:43'),
(346, '2 - Not machine-generated', '2023-07-22 01:54:07', '2023-07-22 01:54:07'),
(347, '0 - Leader', '2023-07-22 01:55:47', '2023-07-22 01:55:48'),
(348, '1 - Variable control fields (002-009)', '2023-07-22 01:55:56', '2023-07-22 01:55:56'),
(349, '2 - Variable data fields (010-999)', '2023-07-22 01:56:10', '2023-07-22 01:56:11');

-- --------------------------------------------------------

--
-- Table structure for table `marctag_subfields_loc`
--

CREATE TABLE `marctag_subfields_loc` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `marctags_loc_id` bigint(20) UNSIGNED DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `repeatable` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `marctag_subfields_loc`
--

INSERT INTO `marctag_subfields_loc` (`id`, `marctags_loc_id`, `code`, `description`, `repeatable`, `created_at`, `updated_at`) VALUES
(1, 1, '001-NS', 'No Subfield', 0, NULL, NULL),
(2, 2, '003-NS', 'No Subfield', 0, NULL, NULL),
(3, 3, '005-NS', 'No Subfield', 0, NULL, NULL),
(4, 4, '006-NS', 'No Subfield', 0, NULL, NULL),
(5, 5, '007-NS', 'No Subfield', 0, NULL, NULL),
(6, 6, '008-NS', 'No Subfield', 0, NULL, NULL),
(7, 7, '010$a', 'LC control number', 0, NULL, NULL),
(8, 7, '010$b', 'NUCMC control number', 1, NULL, NULL),
(9, 7, '010$z', 'Canceled/invalid LC control number', 1, NULL, NULL),
(10, 7, '010$8', 'Field link and sequence number', 1, NULL, NULL),
(11, 11, '013$a', 'Number', 0, NULL, NULL),
(12, 11, '013$b', 'Country', 0, NULL, NULL),
(13, 11, '013$c', 'Type of number', 0, NULL, NULL),
(14, 11, '013$d', 'Date', 0, NULL, NULL),
(15, 11, '013$e', 'Status', 0, NULL, NULL),
(16, 11, '013$f', 'Party to document', 0, NULL, NULL),
(17, 11, '013$6', 'Linkage', 0, NULL, NULL),
(18, 11, '013$8', 'Field link and sequence number', 0, NULL, NULL),
(19, 19, '015$a', 'National bibliography number', 0, NULL, NULL),
(20, 19, '015$q', 'Qualifying information', 0, NULL, NULL),
(21, 19, '015$z', 'Canceled/invalid national bibliography number', 0, NULL, NULL),
(22, 19, '015$2', 'Source', 0, NULL, NULL),
(23, 19, '015$6', 'Linkage', 0, NULL, NULL),
(24, 19, '015$8', 'Field link and sequence number', 0, NULL, NULL),
(25, 25, '016$a', 'Record control number', 0, NULL, NULL),
(26, 25, '016$z', 'Canceled/invalid control number', 0, NULL, NULL),
(27, 25, '016$2', 'Source', 0, NULL, NULL),
(28, 25, '016$8', 'Field link and sequence number', 0, NULL, NULL),
(29, 29, '017$a', 'Copyright or legal deposit number', 0, NULL, NULL),
(30, 29, '017$b', 'Assigning agency', 0, NULL, NULL),
(31, 29, '017$d', 'Date', 0, NULL, NULL),
(32, 29, '017$i', 'Display text', 0, NULL, NULL),
(33, 29, '017$z', 'Canceled/invalid copyright or legal deposit number', 0, NULL, NULL),
(34, 29, '017$2', 'Source', 0, NULL, NULL),
(35, 29, '017$6', 'Linkage', 0, NULL, NULL),
(36, 29, '017$8', 'Field link and sequence number', 0, NULL, NULL),
(37, 37, '018$a', 'Copyright article-fee code', 0, NULL, NULL),
(38, 37, '018$6', 'Linkage', 0, NULL, NULL),
(39, 37, '018$8', 'Field link and sequence number', 0, NULL, NULL),
(40, 40, '020$a', 'International Standard Book Number', 0, NULL, NULL),
(41, 40, '020$c', 'Terms of availability', 0, NULL, NULL),
(42, 40, '020$q', 'Qualifying information', 0, NULL, NULL),
(43, 40, '020$z', 'Canceled/invalid ISBN', 0, NULL, NULL),
(44, 40, '020$6', 'Linkage', 0, NULL, NULL),
(45, 40, '020$8', 'Field link and sequence number', 0, NULL, NULL),
(46, 46, '022$a', 'International Standard Serial Number', 0, NULL, NULL),
(47, 46, '022$l', 'ISSN-L', 0, NULL, NULL),
(48, 46, '022$m', 'Canceled ISSN-L', 0, NULL, NULL),
(49, 46, '022$y', 'Incorrect ISSN', 0, NULL, NULL),
(50, 46, '022$z', 'Canceled ISSN', 0, NULL, NULL),
(51, 46, '022$0', 'Authority record control number or standard number', 0, NULL, NULL),
(52, 46, '022$1', 'Real World Object URI ', 0, NULL, NULL),
(53, 46, '022$2', 'Source', 0, NULL, NULL),
(54, 46, '022$6', 'Linkage', 0, NULL, NULL),
(55, 46, '022$8', 'Field link and sequence number', 0, NULL, NULL),
(56, 56, '024$a', 'Standard number or code', 0, NULL, NULL),
(57, 56, '024$c', 'Terms of availability', 0, NULL, NULL),
(58, 56, '024$d', 'Additional codes following the standard number or code', 0, NULL, NULL),
(59, 56, '024$q', 'Qualifying information', 0, NULL, NULL),
(60, 56, '024$z', 'Canceled/invalid standard number or code', 0, NULL, NULL),
(61, 56, '024$2', 'Source of number or code', 0, NULL, NULL),
(62, 56, '024$6', 'Linkage', 0, NULL, NULL),
(63, 56, '024$8', 'Field link and sequence number', 0, NULL, NULL),
(64, 64, '025$a', 'Overseas acquisition number', 0, NULL, NULL),
(65, 64, '025$8', 'Field link and sequence number', 0, NULL, NULL),
(66, 67, '027$a', 'Standard technical report number', 0, NULL, NULL),
(67, 67, '027$q', 'Qualifying information', 0, NULL, NULL),
(68, 67, '027$z', 'Canceled/invalid number', 0, NULL, NULL),
(69, 67, '027$6', 'Linkage', 0, NULL, NULL),
(70, 67, '027$8', 'Field link and sequence number', 0, NULL, NULL),
(71, 71, '028$a', 'Publisher or distributor number', 0, NULL, NULL),
(72, 71, '028$b', 'Source', 0, NULL, NULL),
(73, 71, '028$q', 'Qualifying information', 0, NULL, NULL),
(74, 71, '028$6', 'Linkage', 0, NULL, NULL),
(75, 71, '028$8', 'Field link and sequence number', 0, NULL, NULL),
(76, 76, '030$a', 'CODEN', 0, NULL, NULL),
(77, 76, '030$z', 'Canceled/invalid CODEN', 0, NULL, NULL),
(78, 76, '030$6', 'Linkage', 0, NULL, NULL),
(79, 76, '030$8', 'Field link and sequence number', 0, NULL, NULL),
(80, 80, '031$a', 'Number of work', 0, NULL, NULL),
(81, 80, '031$b', 'Number of movement', 0, NULL, NULL),
(82, 80, '031$c', 'Number of excerpt', 0, NULL, NULL),
(83, 80, '031$d', 'Caption or heading', 0, NULL, NULL),
(84, 80, '031$e', 'Role', 0, NULL, NULL),
(85, 80, '031$g', 'Clef', 0, NULL, NULL),
(86, 80, '031$m', 'Voice/instrument', 0, NULL, NULL),
(87, 80, '031$n', 'Key signature', 0, NULL, NULL),
(88, 80, '031$o', 'Time signature', 0, NULL, NULL),
(89, 80, '031$p', 'Musical notation', 0, NULL, NULL),
(90, 80, '031$q', 'General note', 0, NULL, NULL),
(91, 80, '031$r', 'Key or mode', 0, NULL, NULL),
(92, 80, '031$s', 'Coded validity note', 0, NULL, NULL),
(93, 80, '031$t', 'Text incipit', 0, NULL, NULL),
(94, 80, '031$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(95, 80, '031$y', 'Link', 0, NULL, NULL),
(96, 80, '031$z', 'Public note', 0, NULL, NULL),
(97, 80, '031$2', 'System code', 0, NULL, NULL),
(98, 80, '031$6', 'Linkage', 0, NULL, NULL),
(99, 80, '031$8', 'Field link and sequence number', 0, NULL, NULL),
(100, 100, '032$a', 'Postal registration number', 0, NULL, NULL),
(101, 100, '032$b', 'Source agency assigning number', 0, NULL, NULL),
(102, 100, '032$6', 'Linkage', 0, NULL, NULL),
(103, 100, '032$8', 'Field link and sequence number', 0, NULL, NULL),
(104, 104, '033$a', 'Formatted date/time', 0, NULL, NULL),
(105, 104, '033$b', 'Geographic classification area code', 0, NULL, NULL),
(106, 104, '033$c', 'Geographic classification subarea code', 0, NULL, NULL),
(107, 104, '033$p', 'Place of event', 0, NULL, NULL),
(108, 104, '033$0', 'Authority record control number or standard number', 0, NULL, NULL),
(109, 104, '033$1', 'Real World Object URI', 0, NULL, NULL),
(110, 104, '033$2', 'Source of term', 0, NULL, NULL),
(111, 104, '033$3', 'Materials specified', 0, NULL, NULL),
(112, 104, '033$6', 'Linkage', 0, NULL, NULL),
(113, 104, '033$8', 'Field link and sequence number', 0, NULL, NULL),
(114, 114, '034$a', 'Category of scale', 0, NULL, NULL),
(115, 114, '034$b', 'Constant ratio linear horizontal scale', 0, NULL, NULL),
(116, 114, '034$c', 'Constant ratio linear vertical scale', 0, NULL, NULL),
(117, 114, '034$d', 'Coordinates - westernmost longitude', 0, NULL, NULL),
(118, 114, '034$e', 'Coordinates - easternmost longitude', 0, NULL, NULL),
(119, 114, '034$f', 'Coordinates - northernmost latitude', 0, NULL, NULL),
(120, 114, '034$g', 'Coordinates - southernmost latitude', 0, NULL, NULL),
(121, 114, '034$h', 'Angular scale', 0, NULL, NULL),
(122, 114, '034$j', 'Declination - northern limit', 0, NULL, NULL),
(123, 114, '034$k', 'Declination - southern limit', 0, NULL, NULL),
(124, 114, '034$m', 'Right ascension - eastern limit', 0, NULL, NULL),
(125, 114, '034$n', 'Right ascension - western limit', 0, NULL, NULL),
(126, 114, '034$p', 'Equinox', 0, NULL, NULL),
(127, 114, '034$r', 'Distance from earth', 0, NULL, NULL),
(128, 114, '034$s', 'G-ring latitude', 0, NULL, NULL),
(129, 114, '034$t', 'G-ring longitude', 0, NULL, NULL),
(130, 114, '034$x', 'Beginning date', 0, NULL, NULL),
(131, 114, '034$y', 'Ending date', 0, NULL, NULL),
(132, 114, '034$z', 'Name of extraterrestrial body', 0, NULL, NULL),
(133, 114, '034$0', 'Authority record control number or standard number', 0, NULL, NULL),
(134, 114, '034$1', 'Real World Object URI', 0, NULL, NULL),
(135, 114, '034$2', 'Source', 0, NULL, NULL),
(136, 114, '034$3', 'Materials specified', 0, NULL, NULL),
(137, 114, '034$6', 'Linkage', 0, NULL, NULL),
(138, 114, '034$8', 'Field link and sequence number', 0, NULL, NULL),
(139, 139, '035$a', 'System control number', 0, NULL, NULL),
(140, 139, '035$z', 'Canceled/invalid control number', 0, NULL, NULL),
(141, 139, '035$6', 'Linkage', 0, NULL, NULL),
(142, 139, '035$8', 'Field link and sequence number', 0, NULL, NULL),
(143, 143, '036$a', 'Original study number', 0, NULL, NULL),
(144, 143, '036$b', 'Source agency assigning number', 0, NULL, NULL),
(145, 143, '036$6', 'Linkage', 0, NULL, NULL),
(146, 143, '036$8', 'Field link and sequence number', 0, NULL, NULL),
(147, 147, '037$a', 'Stock number', 0, NULL, NULL),
(148, 147, '037$b', 'Source of stock number/acquisition', 0, NULL, NULL),
(149, 147, '037$c', 'Terms of availability', 0, NULL, NULL),
(150, 147, '037$f', 'Form of issue', 0, NULL, NULL),
(151, 147, '037$g', 'Additional format characteristics', 0, NULL, NULL),
(152, 147, '037$n', 'Note', 0, NULL, NULL),
(153, 147, '037$3', 'Materials specified', 0, NULL, NULL),
(154, 147, '037$5', 'Institution to which field applies', 0, NULL, NULL),
(155, 147, '037$6', 'Linkage', 0, NULL, NULL),
(156, 147, '037$8', 'Field link and sequence number', 0, NULL, NULL),
(157, 157, '038$a', 'Record content licensor', 0, NULL, NULL),
(158, 157, '038$6', 'Linkage', 0, NULL, NULL),
(159, 157, '038$8', 'Field link and sequence number', 0, NULL, NULL),
(160, 160, '040$a', 'Original cataloging agency', 0, NULL, NULL),
(161, 160, '040$b', 'Language of cataloging', 0, NULL, NULL),
(162, 160, '040$c', 'Transcribing agency', 0, NULL, NULL),
(163, 160, '040$d', 'Modifying agency', 0, NULL, NULL),
(164, 160, '040$e', 'Description conventions', 0, NULL, NULL),
(165, 160, '040$6', 'Linkage', 0, NULL, NULL),
(166, 160, '040$8', 'Field link and sequence number', 0, NULL, NULL),
(167, 167, '041$a', 'Language code of text/sound track or separate title', 0, NULL, NULL),
(168, 167, '041$b', 'Language code of summary or abstract', 0, NULL, NULL),
(169, 167, '041$d', 'Language code of sung or spoken text', 0, NULL, NULL),
(170, 167, '041$e', 'Language code of librettos', 0, NULL, NULL),
(171, 167, '041$f', 'Language code of table of contents', 0, NULL, NULL),
(172, 167, '041$g', 'Language code of accompanying material other than librettos and transcripts', 0, NULL, NULL),
(173, 167, '041$h', 'Language code of original', 0, NULL, NULL),
(174, 167, '041$i', 'Language code of intertitles', 0, NULL, NULL),
(175, 167, '041$j', 'Language code of subtitles', 0, NULL, NULL),
(176, 167, '041$k', 'Language code of intermediate translations', 0, NULL, NULL),
(177, 167, '041$m', 'Language code of original accompanying materials other than librettos', 0, NULL, NULL),
(178, 167, '041$n', 'Language code of original libretto', 0, NULL, NULL),
(179, 167, '041$p', 'Language code of captions', 0, NULL, NULL),
(180, 167, '041$q', 'Language code of accessible audio', 0, NULL, NULL),
(181, 167, '041$r', 'Language code of accessible visual language (non-textual)', 0, NULL, NULL),
(182, 167, '041$t', 'Language code of accompanying transcripts for audiovisual materials', 0, NULL, NULL),
(183, 167, '041$2', 'Source of code', 0, NULL, NULL),
(184, 167, '041$6', 'Linkage ', 0, NULL, NULL),
(185, 167, '041$7', 'Data provenance', 0, NULL, NULL),
(186, 167, '041$8', 'Field link and sequence number', 0, NULL, NULL),
(187, 187, '042$a', 'Authentication code', 0, NULL, NULL),
(188, 188, '043$a', 'Geographic area code', 0, NULL, NULL),
(189, 188, '043$b', 'Local GAC code', 0, NULL, NULL),
(190, 188, '043$c', 'ISO code', 0, NULL, NULL),
(191, 188, '043$0', 'Authority record control number or standard number', 0, NULL, NULL),
(192, 188, '043$1', 'Real World Object URI', 0, NULL, NULL),
(193, 188, '043$2', 'Source of local code', 0, NULL, NULL),
(194, 188, '043$6', 'Linkage', 0, NULL, NULL),
(195, 188, '043$8', 'Field link and sequence number', 0, NULL, NULL),
(196, 196, '044$a', 'MARC country code', 0, NULL, NULL),
(197, 196, '044$b', 'Local subentity code', 0, NULL, NULL),
(198, 196, '044$c', 'ISO country code', 0, NULL, NULL),
(199, 196, '044$2', 'Source of local subentity code ', 0, NULL, NULL),
(200, 196, '044$6', 'Linkage', 0, NULL, NULL),
(201, 196, '044$8', 'Field link and sequence number', 0, NULL, NULL),
(202, 202, '045$a', 'Time period code', 0, NULL, NULL),
(203, 202, '045$b', 'Formatted 9999 B.C. through C.E. time period', 0, NULL, NULL),
(204, 202, '045$c', 'Formatted pre-9999 B.C. time period', 0, NULL, NULL),
(205, 202, '045$6', 'Linkage', 0, NULL, NULL),
(206, 202, '045$8', 'Field link and sequence number', 0, NULL, NULL),
(207, 207, '046$a', 'Type of date code', 0, NULL, NULL),
(208, 207, '046$b', 'Date 1, B.C.E. date', 0, NULL, NULL),
(209, 207, '046$c', 'Date 1, C.E. date ', 0, NULL, NULL),
(210, 207, '046$d', 'Date 2, B.C.E. date', 0, NULL, NULL),
(211, 207, '046$e', 'Date 2, C.E. date', 0, NULL, NULL),
(212, 207, '046$j', 'Date resource modified', 0, NULL, NULL),
(213, 207, '046$k', 'Beginning or single date created', 0, NULL, NULL),
(214, 207, '046$l', 'Ending date created', 0, NULL, NULL),
(215, 207, '046$m', 'Beginning of date valid', 0, NULL, NULL),
(216, 207, '046$n', 'End of date valid', 0, NULL, NULL),
(217, 207, '046$o', 'Single or starting date for aggregated content', 0, NULL, NULL),
(218, 207, '046$p', 'Ending date for aggregated content', 0, NULL, NULL),
(219, 207, '046$x', 'Nonpublic note', 0, NULL, NULL),
(220, 207, '046$z', 'Public note', 0, NULL, NULL),
(221, 207, '046$2', 'Source of date', 0, NULL, NULL),
(222, 207, '046$3', 'Materials specified', 0, NULL, NULL),
(223, 207, '046$6', 'Linkage ', 0, NULL, NULL),
(224, 207, '046$8', 'Field link and sequence number', 0, NULL, NULL),
(225, 225, '047$a', 'Form of musical composition code', 0, NULL, NULL),
(226, 225, '047$2', 'Source of code', 0, NULL, NULL),
(227, 225, '047$8', 'Field link and sequence number', 0, NULL, NULL),
(228, 228, '048$a', 'Performer or ensemble', 0, NULL, NULL),
(229, 228, '048$b', 'Soloist', 0, NULL, NULL),
(230, 228, '048$2', 'Source of code', 0, NULL, NULL),
(231, 228, '048$8', 'Field link and sequence number', 0, NULL, NULL),
(232, 232, '050$a', 'Classification number', 0, NULL, NULL),
(233, 232, '050$b', 'Item number', 0, NULL, NULL),
(234, 232, '050$0', 'Authority record control number or standard number', 0, NULL, NULL),
(235, 232, '050$1', 'Real World Object URI', 0, NULL, NULL),
(236, 232, '050$5', 'Institution to which field applies', 0, NULL, NULL),
(237, 232, '050$6', 'Linkage', 0, NULL, NULL),
(238, 232, '050$8', 'Field link and sequence number', 0, NULL, NULL),
(239, 239, '051$a', 'Classification number', 0, NULL, NULL),
(240, 239, '051$b', 'Item number', 0, NULL, NULL),
(241, 239, '051$c', 'Copy information', 0, NULL, NULL),
(242, 239, '051$8', 'Field link and sequence number', 0, NULL, NULL),
(243, 243, '052$a', 'Geographic classification area code', 0, NULL, NULL),
(244, 243, '052$b', 'Geographic classification subarea code', 0, NULL, NULL),
(245, 243, '052$d', 'Populated place name', 0, NULL, NULL),
(246, 243, '052$0', 'Authority record control number or standard number', 0, NULL, NULL),
(247, 243, '052$1', 'Real World Object URI', 0, NULL, NULL),
(248, 243, '052$2', 'Code source', 0, NULL, NULL),
(249, 243, '052$6', 'Linkage', 0, NULL, NULL),
(250, 243, '052$8', 'Field link and sequence number', 0, NULL, NULL),
(251, 251, '055$a', 'Classification number', 0, NULL, NULL),
(252, 251, '055$b', 'Item number', 0, NULL, NULL),
(253, 251, '055$0', 'Authority record control number or standard number', 0, NULL, NULL),
(254, 251, '055$1', 'Real World Object URI', 0, NULL, NULL),
(255, 251, '055$2', 'Code source', 0, NULL, NULL),
(256, 251, '055$6', 'Linkage', 0, NULL, NULL),
(257, 251, '055$8', 'Field link and sequence number', 0, NULL, NULL),
(258, 258, '060$a', 'Classification number', 0, NULL, NULL),
(259, 258, '060$b', 'Item number', 0, NULL, NULL),
(260, 258, '060$0', 'Authority record control number or standard number', 0, NULL, NULL),
(261, 258, '060$1', 'Real World Object URI', 0, NULL, NULL),
(262, 258, '060$8', 'Field link and sequence number', 0, NULL, NULL),
(263, 263, '061$a', 'Classification number', 0, NULL, NULL),
(264, 263, '061$b', 'Item number', 0, NULL, NULL),
(265, 263, '061$c', 'Copy information', 0, NULL, NULL),
(266, 263, '061$8', 'Field link and sequence number', 0, NULL, NULL),
(267, 267, '066$a', 'Primary G0 character set', 0, NULL, NULL),
(268, 267, '066$b', 'Primary G1 character set', 0, NULL, NULL),
(269, 267, '066$c', 'Alternate G0 or G1 character set', 0, NULL, NULL),
(270, 270, '070$a', 'Classification number', 0, NULL, NULL),
(271, 270, '070$b', 'Item number', 0, NULL, NULL),
(272, 270, '070$0', 'Authority record control number or standard number', 0, NULL, NULL),
(273, 270, '070$1', 'Real World Object URI', 0, NULL, NULL),
(274, 270, '070$8', 'Field link and sequence number', 0, NULL, NULL),
(275, 275, '071$a', 'Classification number', 0, NULL, NULL),
(276, 275, '071$b', 'Item number', 0, NULL, NULL),
(277, 275, '071$c', 'Copy information', 0, NULL, NULL),
(278, 275, '071$8', 'Field link and sequence number', 0, NULL, NULL),
(279, 279, '072$a', 'Subject category code', 0, NULL, NULL),
(280, 279, '072$x', 'Subject category code subdivision', 0, NULL, NULL),
(281, 279, '072$2', 'Source', 0, NULL, NULL),
(282, 279, '072$6', 'Linkage', 0, NULL, NULL),
(283, 279, '072$8', 'Field link and sequence number', 0, NULL, NULL),
(284, 284, '074$a', 'GPO item number', 0, NULL, NULL),
(285, 284, '074$z', 'Canceled/invalid GPO item number', 0, NULL, NULL),
(286, 284, '074$8', 'Field link and sequence number', 0, NULL, NULL),
(287, 287, '080$a', 'Universal Decimal Classification number', 0, NULL, NULL),
(288, 287, '080$b', 'Item number', 0, NULL, NULL),
(289, 287, '080$x', 'Common auxiliary subdivision', 0, NULL, NULL),
(290, 287, '080$0', 'Authority record control number or standard number', 0, NULL, NULL),
(291, 287, '080$1', 'Real World Object URI', 0, NULL, NULL),
(292, 287, '080$2', 'Code source', 0, NULL, NULL),
(293, 287, '080$6', 'Linkage', 0, NULL, NULL),
(294, 287, '080$8', 'Field link and sequence number', 0, NULL, NULL),
(295, 295, '082$a', 'Classification number', 0, NULL, NULL),
(296, 295, '082$b', 'Item number', 0, NULL, NULL),
(297, 295, '082$m', 'Standard or optional designation', 0, NULL, NULL),
(298, 295, '082$q', 'Assigning agency', 0, NULL, NULL),
(299, 295, '082$2', 'Edition information', 0, NULL, NULL),
(300, 295, '082$6', 'Linkage', 0, NULL, NULL),
(301, 295, '082$7', 'Data provenance', 0, NULL, NULL),
(302, 295, '082$8', 'Field link and sequence number', 0, NULL, NULL),
(303, 303, '083$a', 'Classification number', 0, NULL, NULL),
(304, 303, '083$c', 'Classification number--Ending number of span', 0, NULL, NULL),
(305, 303, '083$m', 'Standard or optional designation', 0, NULL, NULL),
(306, 303, '083$q', 'Assigning agency', 0, NULL, NULL),
(307, 303, '083$y', 'Table sequence number for internal subarrangement or add table', 0, NULL, NULL),
(308, 303, '083$z', 'Table identification', 0, NULL, NULL),
(309, 303, '083$2', 'Edition information', 0, NULL, NULL),
(310, 303, '083$6', 'Linkage', 0, NULL, NULL),
(311, 303, '083$7', 'Data provenance', 0, NULL, NULL),
(312, 303, '083$8', 'Field link and sequence number', 0, NULL, NULL),
(313, 313, '084$a', 'Classification number', 0, NULL, NULL),
(314, 313, '084$b', 'Item number', 0, NULL, NULL),
(315, 313, '084$q', 'Assigning agency', 0, NULL, NULL),
(316, 313, '084$0', 'Authority record control number or standard number', 0, NULL, NULL),
(317, 313, '084$1', 'Real World Object URI', 0, NULL, NULL),
(318, 313, '084$2', 'Number source', 0, NULL, NULL),
(319, 313, '084$6', 'Linkage ', 0, NULL, NULL),
(320, 313, '084$7', 'Data provenance', 0, NULL, NULL),
(321, 313, '084$8', 'Field link and sequence number', 0, NULL, NULL),
(322, 322, '085$a', 'Number where instructions are found-single number or beginning number of span', 0, NULL, NULL),
(323, 322, '085$b', 'Base number', 0, NULL, NULL),
(324, 322, '085$c', 'Classification number-ending number of span', 0, NULL, NULL),
(325, 322, '085$f', 'Facet designator', 0, NULL, NULL),
(326, 322, '085$r', 'Root number', 0, NULL, NULL),
(327, 322, '085$s', 'Digits added from classification number in schedule or external table', 0, NULL, NULL),
(328, 322, '085$t', 'Digits added from internal subarrangement or add table', 0, NULL, NULL),
(329, 322, '085$u', 'Number being analyzed', 0, NULL, NULL),
(330, 322, '085$v', 'Number in internal subarrangement or add table where instructions are found', 0, NULL, NULL),
(331, 322, '085$w', 'Table identification-Internal subarrangement or add table', 0, NULL, NULL),
(332, 322, '085$y', 'Table sequence number for internal subarrangement or add table', 0, NULL, NULL),
(333, 322, '085$z', 'Table identification', 0, NULL, NULL),
(334, 322, '085$0', 'Authority record control number or standard number', 0, NULL, NULL),
(335, 322, '085$1', 'Real World Object URI', 0, NULL, NULL),
(336, 322, '085$6', 'Linkage ', 0, NULL, NULL),
(337, 322, '085$8', 'Field link and sequence number', 0, NULL, NULL),
(338, 338, '086$a', 'Classification number', 0, NULL, NULL),
(339, 338, '086$z', 'Canceled/invalid classification number', 0, NULL, NULL),
(340, 338, '086$0', 'Authority record control number or standard number', 0, NULL, NULL),
(341, 338, '086$1', 'Real World Object URI', 0, NULL, NULL),
(342, 338, '086$2', 'Number source', 0, NULL, NULL),
(343, 338, '086$6', 'Linkage ', 0, NULL, NULL),
(344, 338, '086$8', 'Field link and sequence number', 0, NULL, NULL),
(345, 345, '088$a', 'Report number', 0, NULL, NULL),
(346, 345, '088$z', 'Canceled/invalid report number', 0, NULL, NULL),
(347, 345, '088$6', 'Linkage ', 0, NULL, NULL),
(348, 345, '088$8', 'Field link and sequence number', 0, NULL, NULL),
(349, 349, '100$a', 'Personal name', 0, NULL, NULL),
(350, 349, '100$b', 'Numeration', 0, NULL, NULL),
(351, 349, '100$c', 'Titles and words associated with a name', 0, NULL, NULL),
(352, 349, '100$d', 'Dates associated with a name', 0, NULL, NULL),
(353, 349, '100$e', 'Relator term', 0, NULL, NULL),
(354, 349, '100$f', 'Date of a work', 0, NULL, NULL),
(355, 349, '100$g', 'Miscellaneous information', 0, NULL, NULL),
(356, 349, '100$j', 'Attribution qualifier', 0, NULL, NULL),
(357, 349, '100$k', 'Form subheading', 0, NULL, NULL),
(358, 349, '100$l', 'Language of a work', 0, NULL, NULL),
(359, 349, '100$n', 'Number of part/section of a work', 0, NULL, NULL),
(360, 349, '100$p', 'Name of part/section of a work', 0, NULL, NULL),
(361, 349, '100$q', 'Fuller form of name', 0, NULL, NULL),
(362, 349, '100$t', 'Title of a work', 0, NULL, NULL),
(363, 349, '100$u', 'Affiliation', 0, NULL, NULL),
(364, 349, '100$0', 'Authority record control number or standard number', 0, NULL, NULL),
(365, 349, '100$1', 'Real World Object URI', 0, NULL, NULL),
(366, 349, '100$2', 'Source of heading or term', 0, NULL, NULL),
(367, 349, '100$4', 'Relationship ', 0, NULL, NULL),
(368, 349, '100$6', 'Linkage', 0, NULL, NULL),
(369, 349, '100$7', 'Data provenance', 0, NULL, NULL),
(370, 349, '100$8', 'Field link and sequence number', 0, NULL, NULL),
(371, 349, '100$a', 'Personal name', 0, NULL, NULL),
(372, 349, '100$b', 'Numeration', 0, NULL, NULL),
(373, 349, '100$c', 'Titles and words associated with a name', 0, NULL, NULL),
(374, 349, '100$d', 'Dates associated with a name', 0, NULL, NULL),
(375, 349, '100$e', 'Relator term', 0, NULL, NULL),
(376, 349, '100$f', 'Date of a work', 0, NULL, NULL),
(377, 349, '100$g', 'Miscellaneous information', 0, NULL, NULL),
(378, 349, '100$j', 'Attribution qualifier', 0, NULL, NULL),
(379, 349, '100$k', 'Form subheading', 0, NULL, NULL),
(380, 349, '100$l', 'Language of a work', 0, NULL, NULL),
(381, 349, '100$n', 'Number of part/section of a work', 0, NULL, NULL),
(382, 349, '100$p', 'Name of part/section of a work', 0, NULL, NULL),
(383, 349, '100$q', 'Fuller form of name', 0, NULL, NULL),
(384, 349, '100$t', 'Title of a work', 0, NULL, NULL),
(385, 349, '100$u', 'Affiliation', 0, NULL, NULL),
(386, 349, '100$0', 'Authority record control number or standard number', 0, NULL, NULL),
(387, 349, '100$1', 'Real World Object URI', 0, NULL, NULL),
(388, 349, '100$2', 'Source of heading or term', 0, NULL, NULL),
(389, 349, '100$4', 'Relationship', 0, NULL, NULL),
(390, 349, '100$6', 'Linkage', 0, NULL, NULL),
(391, 349, '100$7', 'Data provenance', 0, NULL, NULL),
(392, 349, '100$8', 'Field link and sequence number', 0, NULL, NULL),
(393, 393, '110$a', 'Corporate name or jurisdiction name as entry element', 0, NULL, NULL),
(394, 393, '110$b', 'Subordinate unit', 0, NULL, NULL),
(395, 393, '110$c', 'Location of meeting', 0, NULL, NULL),
(396, 393, '110$d', 'Date of meeting or treaty signing', 0, NULL, NULL),
(397, 393, '110$e', 'Relator term', 0, NULL, NULL),
(398, 393, '110$f', 'Date of a work', 0, NULL, NULL),
(399, 393, '110$g', 'Miscellaneous information', 0, NULL, NULL),
(400, 393, '110$k', 'Form subheading', 0, NULL, NULL),
(401, 393, '110$l', 'Language of a work', 0, NULL, NULL),
(402, 393, '110$n', 'Number of part/section/meeting', 0, NULL, NULL),
(403, 393, '110$p', 'Name of part/section of a work', 0, NULL, NULL),
(404, 393, '110$t', 'Title of a work', 0, NULL, NULL),
(405, 393, '110$u', 'Affiliation', 0, NULL, NULL),
(406, 393, '110$0', 'Authority record control number or standard number', 0, NULL, NULL),
(407, 393, '110$1', 'Real World Object URI', 0, NULL, NULL),
(408, 393, '110$2', 'Source of heading or term', 0, NULL, NULL),
(409, 393, '110$4', 'Relationship', 0, NULL, NULL),
(410, 393, '110$6', 'Linkage', 0, NULL, NULL),
(411, 393, '110$7', 'Data provenance', 0, NULL, NULL),
(412, 393, '110$8', 'Field link and sequence number', 0, NULL, NULL),
(413, 413, '111$a', 'Meeting name or jurisdiction name as entry element', 0, NULL, NULL),
(414, 413, '111$c', 'Location of meeting', 0, NULL, NULL),
(415, 413, '111$d', 'Date of meeting or treaty signing', 0, NULL, NULL),
(416, 413, '111$e', 'Subordinate unit', 0, NULL, NULL),
(417, 413, '111$f', 'Date of a work', 0, NULL, NULL),
(418, 413, '111$g', 'Miscellaneous information', 0, NULL, NULL),
(419, 413, '111$j', 'Relator term', 0, NULL, NULL),
(420, 413, '111$k', 'Form subheading', 0, NULL, NULL),
(421, 413, '111$l', 'Language of a work', 0, NULL, NULL),
(422, 413, '111$n', 'Number of part/section/meeting', 0, NULL, NULL),
(423, 413, '111$p', 'Name of part/section of a work', 0, NULL, NULL),
(424, 413, '111$q', 'Name of meeting following jurisdiction name entry element', 0, NULL, NULL),
(425, 413, '111$t', 'Title of a work', 0, NULL, NULL),
(426, 413, '111$u', 'Affiliation', 0, NULL, NULL),
(427, 413, '111$0', 'Authority record control number or standard number', 0, NULL, NULL),
(428, 413, '111$1', 'Real World Object URI', 0, NULL, NULL),
(429, 413, '111$2', 'Source of heading or term', 0, NULL, NULL),
(430, 413, '111$4', 'Relationship', 0, NULL, NULL),
(431, 413, '111$6', 'Linkage', 0, NULL, NULL),
(432, 413, '111$7', 'Data provenance', 0, NULL, NULL),
(433, 413, '111$8', 'Field link and sequence number', 0, NULL, NULL),
(434, 434, '130$a', 'Uniform title', 0, NULL, NULL),
(435, 434, '130$d', 'Date of treaty signing', 0, NULL, NULL),
(436, 434, '130$f', 'Date of a work', 0, NULL, NULL),
(437, 434, '130$g', 'Miscellaneous information', 0, NULL, NULL),
(438, 434, '130$h', 'Medium', 0, NULL, NULL),
(439, 434, '130$k', 'Form subheading', 0, NULL, NULL),
(440, 434, '130$l', 'Language of a work', 0, NULL, NULL),
(441, 434, '130$m', 'Medium of performance for music', 0, NULL, NULL),
(442, 434, '130$n', 'Number of part/section of a work', 0, NULL, NULL),
(443, 434, '130$o', 'Arranged statement for music', 0, NULL, NULL),
(444, 434, '130$p', 'Name of part/section of a work', 0, NULL, NULL),
(445, 434, '130$r', 'Key for music', 0, NULL, NULL),
(446, 434, '130$s', 'Version', 0, NULL, NULL),
(447, 434, '130$t', 'Title of a work', 0, NULL, NULL),
(448, 434, '130$0', 'Authority record control number or standard number', 0, NULL, NULL),
(449, 434, '130$1', 'Real World Object', 0, NULL, NULL),
(450, 434, '130$2', 'Source of heading or term', 0, NULL, NULL),
(451, 434, '130$6', 'Linkage', 0, NULL, NULL),
(452, 434, '130$7', 'Data provenance', 0, NULL, NULL),
(453, 434, '130$8', 'Field link and sequence number', 0, NULL, NULL),
(454, 454, '210$a', 'Abbreviated title', 0, NULL, NULL),
(455, 454, '210$b', 'Qualifying information', 0, NULL, NULL),
(456, 454, '210$2', 'Source', 0, NULL, NULL),
(457, 454, '210$6', 'Linkage', 0, NULL, NULL),
(458, 454, '210$7', 'Data provenance', 0, NULL, NULL),
(459, 454, '210$8', 'Field link and sequence number', 0, NULL, NULL),
(460, 460, '222$a', 'Key title', 0, NULL, NULL),
(461, 460, '222$b', 'Qualifying information', 0, NULL, NULL),
(462, 460, '222$6', 'Linkage', 0, NULL, NULL),
(463, 460, '222$8', 'Field link and sequence number', 0, NULL, NULL),
(464, 464, '240$a', 'Uniform title', 0, NULL, NULL),
(465, 464, '240$d', 'Date of treaty signing', 0, NULL, NULL),
(466, 464, '240$f', 'Date of a work', 0, NULL, NULL),
(467, 464, '240$g', 'Miscellaneous information', 0, NULL, NULL),
(468, 464, '240$h', 'Medium', 0, NULL, NULL),
(469, 464, '240$k', 'Form subheading', 0, NULL, NULL),
(470, 464, '240$l', 'Language of a work', 0, NULL, NULL),
(471, 464, '240$m', 'Medium of performance for music', 0, NULL, NULL),
(472, 464, '240$n', 'Number of part/section of a work', 0, NULL, NULL),
(473, 464, '240$o', 'Arranged statement for music', 0, NULL, NULL),
(474, 464, '240$p', 'Name of part/section of a work', 0, NULL, NULL),
(475, 464, '240$r', 'Key for music', 0, NULL, NULL),
(476, 464, '240$s', 'Version', 0, NULL, NULL),
(477, 464, '240$0', 'Authority record control number or standard number', 0, NULL, NULL),
(478, 464, '240$1', 'Real World Object', 0, NULL, NULL),
(479, 464, '240$2', 'Source of heading or term', 0, NULL, NULL),
(480, 464, '240$6', 'Linkage', 0, NULL, NULL),
(481, 464, '240$7', 'Data provenance', 0, NULL, NULL),
(482, 464, '240$8', 'Field link and sequence number', 0, NULL, NULL),
(483, 484, '242$a', 'Title', 0, NULL, NULL),
(484, 484, '242$b', 'Remainder of title', 0, NULL, NULL),
(485, 484, '242$c', 'Statement of responsibility, etc.', 0, NULL, NULL),
(486, 484, '242$h', 'Medium', 0, NULL, NULL),
(487, 484, '242$n', 'Number of part/section of a work', 0, NULL, NULL),
(488, 484, '242$p', 'Name of part/section of a work', 0, NULL, NULL),
(489, 484, '242$y', 'Language code of translated title', 0, NULL, NULL),
(490, 484, '242$6', 'Linkage', 0, NULL, NULL),
(491, 484, '242$8', 'Field link and sequence number', 0, NULL, NULL),
(492, 492, '243$a', 'Uniform title', 0, NULL, NULL),
(493, 492, '243$d', 'Date of treaty signing', 0, NULL, NULL),
(494, 492, '243$f', 'Date of a work', 0, NULL, NULL),
(495, 492, '243$g', 'Miscellaneous information', 0, NULL, NULL),
(496, 492, '243$h', 'Medium', 0, NULL, NULL),
(497, 492, '243$k', 'Form subheading', 0, NULL, NULL),
(498, 492, '243$l', 'Language of a work', 0, NULL, NULL),
(499, 492, '243$m', 'Medium of performance for music', 0, NULL, NULL),
(500, 492, '243$n', 'Number of part/section of a work', 0, NULL, NULL),
(501, 492, '243$o', 'Arranged statement for music', 0, NULL, NULL),
(502, 492, '243$p', 'Name of part/section of a work', 0, NULL, NULL),
(503, 492, '243$r', 'Key for music', 0, NULL, NULL),
(504, 492, '243$s', 'Version', 0, NULL, NULL),
(505, 492, '243$6', 'Linkage', 0, NULL, NULL),
(506, 492, '243$8', 'Field link and sequence number', 0, NULL, NULL),
(507, 507, '245$a', 'Title', 0, NULL, NULL),
(508, 507, '245$b', 'Remainder of title', 0, NULL, NULL),
(509, 507, '245$c', 'Statement of responsibility, etc.', 0, NULL, NULL),
(510, 507, '245$f', 'Inclusive dates', 0, NULL, NULL),
(511, 507, '245$g', 'Bulk dates', 0, NULL, NULL),
(512, 507, '245$h', 'Medium', 0, NULL, NULL),
(513, 507, '245$k', 'Form', 0, NULL, NULL),
(514, 507, '245$n', 'Number of part/section of a work', 0, NULL, NULL),
(515, 507, '245$p', 'Name of part/section of a work', 0, NULL, NULL),
(516, 507, '245$s', 'Version', 0, NULL, NULL),
(517, 507, '245$6', 'Linkage', 0, NULL, NULL),
(518, 507, '245$7', 'Data provenance', 0, NULL, NULL),
(519, 507, '245$8', 'Field link and sequence number', 0, NULL, NULL),
(520, 520, '246$a', 'Title proper/short title', 0, NULL, NULL),
(521, 520, '246$b', 'Remainder of title', 0, NULL, NULL),
(522, 520, '246$f', 'Date or sequential designation', 0, NULL, NULL),
(523, 520, '246$g', 'Miscellaneous information', 0, NULL, NULL),
(524, 520, '246$h', 'Medium', 0, NULL, NULL),
(525, 520, '246$i', 'Display text', 0, NULL, NULL),
(526, 520, '246$n', 'Number of part/section of a work', 0, NULL, NULL),
(527, 520, '246$p', 'Name of part/section of a work', 0, NULL, NULL),
(528, 520, '246$5', 'Institution to which field applies', 0, NULL, NULL),
(529, 520, '246$6', 'Linkage', 0, NULL, NULL),
(530, 520, '246$7', 'Data provenance', 0, NULL, NULL),
(531, 520, '246$8', 'Field link and sequence number', 0, NULL, NULL),
(532, 532, '247$a', 'Title', 0, NULL, NULL),
(533, 532, '247$b', 'Remainder of title', 0, NULL, NULL),
(534, 532, '247$f', 'Date or sequential designation', 0, NULL, NULL),
(535, 532, '247$g', 'Miscellaneous information', 0, NULL, NULL),
(536, 532, '247$h', 'Medium', 0, NULL, NULL),
(537, 532, '247$n', 'Number of part/section of a work', 0, NULL, NULL),
(538, 532, '247$p', 'Name of part/section of a work', 0, NULL, NULL),
(539, 532, '247$x', 'International Standard Serial Number', 0, NULL, NULL),
(540, 532, '247$6', 'Linkage', 0, NULL, NULL),
(541, 532, '247$7', 'Data provenance', 0, NULL, NULL),
(542, 532, '247$8', 'Field link and sequence number', 0, NULL, NULL),
(543, 543, '250$a', 'Edition statement', 0, NULL, NULL),
(544, 543, '250$b', 'Remainder of edition statement', 0, NULL, NULL),
(545, 543, '250$3', 'Materials specified', 0, NULL, NULL),
(546, 543, '250$6', 'Linkage', 0, NULL, NULL),
(547, 543, '250$7', 'Data provenance', 0, NULL, NULL),
(548, 543, '250$8', 'Field link and sequence number', 0, NULL, NULL),
(549, 549, '251$a', 'Version', 0, NULL, NULL),
(550, 549, '251$0', 'Authority record control number or standard number', 0, NULL, NULL),
(551, 549, '251$1', 'Real World Object', 0, NULL, NULL),
(552, 549, '251$2', 'Source', 0, NULL, NULL),
(553, 549, '251$3', 'Materials specified', 0, NULL, NULL),
(554, 549, '251$6', 'Linkage', 0, NULL, NULL),
(555, 549, '251$8', 'Field link and sequence number', 0, NULL, NULL),
(556, 556, '254$a', 'Musical presentation statement', 0, NULL, NULL),
(557, 556, '254$6', 'Linkage', 0, NULL, NULL),
(558, 556, '254$8', 'Field link and sequence number', 0, NULL, NULL),
(559, 559, '255$a', 'Statement of scale', 0, NULL, NULL),
(560, 559, '255$b', 'Statement of projection', 0, NULL, NULL),
(561, 559, '255$c', 'Statement of coordinates', 0, NULL, NULL),
(562, 559, '255$d', 'Statement of zone', 0, NULL, NULL),
(563, 559, '255$e', 'Statement of equinox', 0, NULL, NULL),
(564, 559, '255$f', 'Outer G-ring coordinate pairs', 0, NULL, NULL),
(565, 559, '255$g', 'Exclusion G-ring coordinate pairs', 0, NULL, NULL),
(566, 559, '255$6', 'Linkage', 0, NULL, NULL),
(567, 559, '255$7', 'Data provenance', 0, NULL, NULL),
(568, 559, '255$8', 'Field link and sequence number', 0, NULL, NULL),
(569, 569, '256$a', 'Computer file characteristics', 0, NULL, NULL),
(570, 569, '256$6', 'Linkage', 0, NULL, NULL),
(571, 569, '256$7', 'Data provenance', 0, NULL, NULL),
(572, 569, '256$8', 'Field link and sequence number', 0, NULL, NULL),
(573, 573, '257$a', 'Country of producing entity', 0, NULL, NULL),
(574, 573, '257$0', 'Authority record control number or standard number', 0, NULL, NULL),
(575, 573, '257$1', 'Real World Object URI', 0, NULL, NULL),
(576, 573, '257$2', 'Source', 0, NULL, NULL),
(577, 573, '257$6', 'Linkage', 0, NULL, NULL),
(578, 573, '257$8', 'Field link and sequence number', 0, NULL, NULL),
(579, 579, '258$a', 'Issuing jurisdiction', 0, NULL, NULL),
(580, 579, '258$b', 'Denomination', 0, NULL, NULL),
(581, 579, '258$6', 'Linkage', 0, NULL, NULL),
(582, 579, '258$8', 'Field link and sequence number', 0, NULL, NULL),
(583, 583, '260$a', 'Place of publication, distribution, etc.', 0, NULL, NULL),
(584, 583, '260$b', 'Name of publisher, distributor, etc.', 0, NULL, NULL),
(585, 583, '260$c', 'Date of publication, distribution, etc.', 0, NULL, NULL),
(586, 583, '260$e', 'Place of manufacture', 0, NULL, NULL),
(587, 583, '260$f', 'Manufacturer', 0, NULL, NULL),
(588, 583, '260$g', 'Date of manufacture', 0, NULL, NULL),
(589, 583, '260$3', 'Materials specified', 0, NULL, NULL),
(590, 583, '260$6', 'Linkage', 0, NULL, NULL),
(591, 583, '260$8', 'Field link and sequence number', 0, NULL, NULL),
(592, 592, '263$a', 'Projected publication date', 0, NULL, NULL),
(593, 592, '263$6', 'Linkage', 0, NULL, NULL),
(594, 592, '263$8', 'Field link and sequence number', 0, NULL, NULL),
(595, 595, '264$a', 'Place of production, publication, distribution, manufacture', 0, NULL, NULL),
(596, 595, '264$b', 'Name of producer, publisher, distributor, manufacturer', 0, NULL, NULL),
(597, 595, '264$c', 'Date of production, publication, distribution, manufacture, or copyright notice', 0, NULL, NULL),
(598, 595, '264$3', 'Materials specified', 0, NULL, NULL),
(599, 595, '264$6', 'Linkage', 0, NULL, NULL),
(600, 595, '264$7', 'Data provenance', 0, NULL, NULL),
(601, 595, '264$8', 'Field link and sequence number', 0, NULL, NULL),
(602, 602, '270$a', 'Address', 0, NULL, NULL),
(603, 602, '270$b', 'City', 0, NULL, NULL),
(604, 602, '270$c', 'State or province', 0, NULL, NULL),
(605, 602, '270$d', 'Country', 0, NULL, NULL),
(606, 602, '270$e', 'Postal code', 0, NULL, NULL),
(607, 602, '270$f', 'Terms preceding attention name', 0, NULL, NULL),
(608, 602, '270$g', 'Attention name', 0, NULL, NULL),
(609, 602, '270$h', 'Attention position', 0, NULL, NULL),
(610, 602, '270$i', 'Type of address', 0, NULL, NULL),
(611, 602, '270$j', 'Specialized telephone number', 0, NULL, NULL),
(612, 602, '270$k', 'Telephone number', 0, NULL, NULL),
(613, 602, '270$l', 'Fax number', 0, NULL, NULL),
(614, 602, '270$m', 'Electronic mail address', 0, NULL, NULL),
(615, 602, '270$n', 'TDD or TTY number', 0, NULL, NULL),
(616, 602, '270$p', 'Contact person', 0, NULL, NULL),
(617, 602, '270$q', 'Title of contact person', 0, NULL, NULL),
(618, 602, '270$r', 'Hours', 0, NULL, NULL),
(619, 602, '270$z', 'Public note', 0, NULL, NULL),
(620, 602, '270$4', 'Relationship', 0, NULL, NULL),
(621, 602, '270$6', 'Linkage', 0, NULL, NULL),
(622, 602, '270$8', 'Field link and sequence number', 0, NULL, NULL),
(623, 623, '300$a', 'Extent', 0, NULL, NULL),
(624, 623, '300$b', 'Other physical details', 0, NULL, NULL),
(625, 623, '300$c', 'Dimensions', 0, NULL, NULL),
(626, 623, '300$e', 'Accompanying material', 0, NULL, NULL),
(627, 623, '300$f', 'Type of unit', 0, NULL, NULL),
(628, 623, '300$g', 'Size of unit', 0, NULL, NULL),
(629, 623, '300$3', 'Materials specified', 0, NULL, NULL),
(630, 623, '300$6', 'Linkage ', 0, NULL, NULL),
(631, 623, '300$7', 'Data provenance', 0, NULL, NULL),
(632, 623, '300$8', 'Field link and sequence number', 0, NULL, NULL),
(633, 633, '306$a', 'Playing time', 0, NULL, NULL),
(634, 633, '306$6', 'Linkage', 0, NULL, NULL),
(635, 633, '306$8', 'Field link and sequence number', 0, NULL, NULL),
(636, 636, '307$a', 'Hours', 0, NULL, NULL),
(637, 636, '307$b', 'Additional information', 0, NULL, NULL),
(638, 636, '307$6', 'Linkage', 0, NULL, NULL),
(639, 636, '307$8', 'Field link and sequence number', 0, NULL, NULL),
(640, 640, '310$a', 'Current publication frequency', 0, NULL, NULL),
(641, 640, '310$b', 'Date of current publication frequency', 0, NULL, NULL),
(642, 640, '310$0', 'Authority record control number or standard number', 0, NULL, NULL),
(643, 640, '310$1', 'Real World Object URI', 0, NULL, NULL),
(644, 640, '310$2', 'Source', 0, NULL, NULL),
(645, 640, '310$6', 'Linkage', 0, NULL, NULL),
(646, 640, '310$8', 'Field link and sequence number', 0, NULL, NULL),
(647, 647, '321$a', 'Former publication frequency', 0, NULL, NULL),
(648, 647, '321$b', 'Dates of former publication frequency', 0, NULL, NULL),
(649, 647, '321$0', 'Authority record control number or standard number', 0, NULL, NULL),
(650, 647, '321$1', 'Real World Object URI', 0, NULL, NULL),
(651, 647, '321$2', 'Source', 0, NULL, NULL),
(652, 647, '321$6', 'Linkage', 0, NULL, NULL),
(653, 647, '321$8', 'Field link and sequence number', 0, NULL, NULL),
(654, 654, '334$a', 'Mode of issuance term', 0, NULL, NULL),
(655, 654, '334$b', 'Mode of issuance code', 0, NULL, NULL),
(656, 654, '334$0', 'Authority record control number or standard number', 0, NULL, NULL),
(657, 654, '334$1', 'Real World Object URI', 0, NULL, NULL),
(658, 654, '334$2', 'Source', 0, NULL, NULL),
(659, 654, '334$6', 'Linkage', 0, NULL, NULL),
(660, 654, '334$8', 'Field link and sequence number', 0, NULL, NULL),
(661, 661, '335$a', 'Extension plan term', 0, NULL, NULL),
(662, 661, '335$b', 'Extension plan code', 0, NULL, NULL),
(663, 661, '335$0', 'Authority record control number or standard number', 0, NULL, NULL),
(664, 661, '335$1', 'Real World Object URI', 0, NULL, NULL),
(665, 661, '335$2', 'Source', 0, NULL, NULL),
(666, 661, '335$3', 'Materials specified', 0, NULL, NULL),
(667, 661, '335$6', 'Linkage', 0, NULL, NULL),
(668, 661, '335$7', 'Data provenance', 0, NULL, NULL),
(669, 661, '335$8', 'Field link and sequence number', 0, NULL, NULL),
(670, 670, '336$a', 'Content type term', 0, NULL, NULL),
(671, 670, '336$b', 'Content type code', 0, NULL, NULL),
(672, 670, '336$0', 'Authority record control number or standard number', 0, NULL, NULL),
(673, 670, '336$1', 'Real World Object URI', 0, NULL, NULL),
(674, 670, '336$2', 'Source', 0, NULL, NULL),
(675, 670, '336$3', 'Materials specified', 0, NULL, NULL),
(676, 670, '336$6', 'Linkage', 0, NULL, NULL),
(677, 670, '336$7', 'Data provenance', 0, NULL, NULL),
(678, 670, '336$8', 'Field link and sequence number', 0, NULL, NULL),
(679, 679, '337$a', 'Media type term', 0, NULL, NULL),
(680, 679, '337$b', 'Media type code', 0, NULL, NULL),
(681, 679, '337$0', 'Authority record control number or standard number', 0, NULL, NULL),
(682, 679, '337$1', 'Real World Object URI', 0, NULL, NULL),
(683, 679, '337$2', 'Source', 0, NULL, NULL),
(684, 679, '337$3', 'Materials specified', 0, NULL, NULL),
(685, 679, '337$6', 'Linkage', 0, NULL, NULL),
(686, 679, '337$8', 'Field link and sequence number', 0, NULL, NULL),
(687, 687, '338$a', 'Carrier type term', 0, NULL, NULL),
(688, 687, '338$b', 'Carrier type code', 0, NULL, NULL),
(689, 687, '338$0', 'Authority record control number or standard number', 0, NULL, NULL),
(690, 687, '338$1', 'Real World Object URI', 0, NULL, NULL),
(691, 687, '338$2', 'Source', 0, NULL, NULL),
(692, 687, '338$3', 'Materials specified', 0, NULL, NULL),
(693, 687, '338$6', 'Linkage', 0, NULL, NULL),
(694, 687, '338$8', 'Field link and sequence number', 0, NULL, NULL),
(695, 695, '340$a', 'Material base and configuration', 0, NULL, NULL),
(696, 695, '340$b', 'Dimensions', 0, NULL, NULL),
(697, 695, '340$c', 'Materials applied to surface', 0, NULL, NULL),
(698, 695, '340$d', 'Information recording technique', 0, NULL, NULL),
(699, 695, '340$e', 'Support ', 0, NULL, NULL),
(700, 695, '340$f', 'Reduction ratio value', 0, NULL, NULL),
(701, 695, '340$g', 'Color content', 0, NULL, NULL),
(702, 695, '340$h', 'Location within medium', 0, NULL, NULL),
(703, 695, '340$i', 'Technical specifications of medium', 0, NULL, NULL),
(704, 695, '340$j', 'Generation', 0, NULL, NULL),
(705, 695, '340$k', 'Layout', 0, NULL, NULL),
(706, 695, '340$l', 'Binding', 0, NULL, NULL),
(707, 695, '340$m', 'Book format', 0, NULL, NULL),
(708, 695, '340$n', 'Font size', 0, NULL, NULL),
(709, 695, '340$o', 'Polarity', 0, NULL, NULL),
(710, 695, '340$p', 'Illustrative content', 0, NULL, NULL),
(711, 695, '340$q', 'Reduction ratio designator', 0, NULL, NULL),
(712, 695, '340$0', 'Authority record control number or standard number', 0, NULL, NULL),
(713, 695, '340$1', 'Real World Object URI', 0, NULL, NULL),
(714, 695, '340$2', 'Source', 0, NULL, NULL),
(715, 695, '340$3', 'Materials specified', 0, NULL, NULL),
(716, 695, '340$6', 'Linkage', 0, NULL, NULL),
(717, 695, '340$8', 'Field link and sequence number', 0, NULL, NULL),
(718, 718, '341$a', 'Content access mode', 0, NULL, NULL),
(719, 718, '341$b', 'Textual assistive features', 0, NULL, NULL),
(720, 718, '341$c', 'Visual assistive features', 0, NULL, NULL),
(721, 718, '341$d', 'Auditory assistive features', 0, NULL, NULL),
(722, 718, '341$e', 'Tactile assistive features', 0, NULL, NULL),
(723, 718, '341$2', 'Source', 0, NULL, NULL),
(724, 718, '341$3', 'Materials specified', 0, NULL, NULL),
(725, 718, '341$6', 'Linkage', 0, NULL, NULL),
(726, 718, '341$8', 'Field link and sequence number', 0, NULL, NULL),
(727, 727, '342$a', 'Name', 0, NULL, NULL),
(728, 727, '342$b', 'Coordinate units or distance units', 0, NULL, NULL),
(729, 727, '342$c', 'Latitude resolution', 0, NULL, NULL),
(730, 727, '342$d', 'Longitude resolution', 0, NULL, NULL),
(731, 727, '342$e', 'Standard parallel or oblique line latitude', 0, NULL, NULL),
(732, 727, '342$f', 'Oblique line longitude', 0, NULL, NULL),
(733, 727, '342$g', 'Longitude of central meridian or projection center', 0, NULL, NULL),
(734, 727, '342$h', 'Latitude of projection center or projection origin', 0, NULL, NULL),
(735, 727, '342$i', 'False easting', 0, NULL, NULL),
(736, 727, '342$j', 'False northing', 0, NULL, NULL),
(737, 727, '342$k', 'Scale factor', 0, NULL, NULL),
(738, 727, '342$l', 'Height of perspective point above surface', 0, NULL, NULL),
(739, 727, '342$m', 'Azimuthal angle', 0, NULL, NULL),
(740, 727, '342$n', 'Azimuth measure point longitude or straight vertical longitude from pole', 0, NULL, NULL),
(741, 727, '342$o', 'Landsat number and path number', 0, NULL, NULL),
(742, 727, '342$p', 'Zone identifier', 0, NULL, NULL),
(743, 727, '342$q', 'Ellipsoid name', 0, NULL, NULL),
(744, 727, '342$r', 'Semi-major axis', 0, NULL, NULL),
(745, 727, '342$s', 'Denominator of flattening ratio', 0, NULL, NULL),
(746, 727, '342$t', 'Vertical resolution', 0, NULL, NULL),
(747, 727, '342$u', 'Vertical encoding method', 0, NULL, NULL),
(748, 727, '342$v', 'Local planar, local, or other projection or grid description', 0, NULL, NULL),
(749, 727, '342$w', 'Local planar or local georeference information', 0, NULL, NULL),
(750, 718, '341$2', 'Reference method used', 0, NULL, NULL),
(751, 718, '341$6', 'Linkage', 0, NULL, NULL),
(752, 718, '341$8', 'Field link and sequence number', 0, NULL, NULL),
(753, 753, '343$a', 'Planar coordinate encoding method', 0, NULL, NULL),
(754, 753, '343$b', 'Planar distance units', 0, NULL, NULL),
(755, 753, '343$c', 'Abscissa resolution', 0, NULL, NULL),
(756, 753, '343$d', 'Ordinate resolution', 0, NULL, NULL),
(757, 753, '343$e', 'Distance resolution', 0, NULL, NULL),
(758, 753, '343$f', 'Bearing resolution', 0, NULL, NULL),
(759, 753, '343$g', 'Bearing units', 0, NULL, NULL),
(760, 753, '343$h', 'Bearing reference direction', 0, NULL, NULL),
(761, 753, '343$i', 'Bearing reference meridian', 0, NULL, NULL),
(762, 753, '343$6', 'Linkage', 0, NULL, NULL),
(763, 753, '343$8', 'Field link and sequence number', 0, NULL, NULL),
(764, 764, '344$a', 'Type of recording', 0, NULL, NULL),
(765, 764, '344$b', 'Recording medium', 0, NULL, NULL),
(766, 764, '344$c', 'Playing speed', 0, NULL, NULL),
(767, 764, '344$d', 'Groove characteristic', 0, NULL, NULL),
(768, 764, '344$e', 'Track configuration', 0, NULL, NULL),
(769, 764, '344$f', 'Tape configuration', 0, NULL, NULL),
(770, 764, '344$g', 'Configuration of playback channels', 0, NULL, NULL),
(771, 764, '344$h', 'Special playback characteristics', 0, NULL, NULL),
(772, 764, '344$i', 'Sound content', 0, NULL, NULL),
(773, 764, '344$j', 'Original capture and storage technique', 0, NULL, NULL),
(774, 764, '344$0', 'Authority record control number or standard number', 0, NULL, NULL),
(775, 764, '344$1', 'Real World Object URI', 0, NULL, NULL),
(776, 764, '344$2', 'Source', 0, NULL, NULL),
(777, 764, '344$3', 'Materials specified', 0, NULL, NULL),
(778, 764, '344$6', 'Linkage', 0, NULL, NULL),
(779, 764, '344$8', 'Field link and sequence number', 0, NULL, NULL),
(780, 780, '345$a', 'Presentation format', 0, NULL, NULL),
(781, 780, '345$b', 'Projection speed', 0, NULL, NULL),
(782, 780, '345$c', 'Aspect ratio value', 0, NULL, NULL),
(783, 780, '345$d', 'Aspect ratio designator', 0, NULL, NULL),
(784, 780, '345$0', 'Authority record control number or standard number', 0, NULL, NULL),
(785, 780, '345$1', 'Real World Object URI', 0, NULL, NULL),
(786, 780, '345$2', 'Source', 0, NULL, NULL),
(787, 780, '345$3', 'Materials specified', 0, NULL, NULL),
(788, 780, '345$6', 'Linkage', 0, NULL, NULL),
(789, 780, '345$8', 'Field link and sequence number', 0, NULL, NULL),
(790, 790, '346$a', 'Video format', 0, NULL, NULL),
(791, 790, '346$b', 'Broadcast standard', 0, NULL, NULL),
(792, 790, '346$0', 'Authority record control number or standard number', 0, NULL, NULL),
(793, 790, '346$1', 'Real World Object URI', 0, NULL, NULL),
(794, 790, '346$2', 'Source', 0, NULL, NULL),
(795, 790, '346$3', 'Materials specified', 0, NULL, NULL),
(796, 790, '346$6', 'Linkage', 0, NULL, NULL),
(797, 790, '346$8', 'Field link and sequence number', 0, NULL, NULL),
(798, 798, '347$a', 'File type', 0, NULL, NULL),
(799, 798, '347$b', 'Encoding format', 0, NULL, NULL),
(800, 798, '347$c', 'File size', 0, NULL, NULL),
(801, 798, '347$d', 'Resolution ', 0, NULL, NULL),
(802, 798, '347$e', 'Regional encoding', 0, NULL, NULL),
(803, 798, '347$f', 'Encoded bitrate', 0, NULL, NULL),
(804, 798, '347$0', 'Authority record control number or standard number', 0, NULL, NULL),
(805, 798, '347$1', 'Real World Object URI', 0, NULL, NULL),
(806, 798, '347$2', 'Source', 0, NULL, NULL),
(807, 798, '347$3', 'Materials specified', 0, NULL, NULL),
(808, 798, '347$6', 'Linkage', 0, NULL, NULL),
(809, 798, '347$8', 'Field link and sequence number', 0, NULL, NULL),
(810, 810, '348$a', 'Format of notated music term', 0, NULL, NULL),
(811, 810, '348$b', 'Format of notated music code', 0, NULL, NULL),
(812, 810, '348$c', 'Form of musical notation term', 0, NULL, NULL),
(813, 810, '348$d', 'Form of musical notation code', 0, NULL, NULL),
(814, 810, '348$0', 'Authority record control number or standard number', 0, NULL, NULL),
(815, 810, '348$1', 'Real World Object URI', 0, NULL, NULL),
(816, 810, '348$2', 'Source of term', 0, NULL, NULL),
(817, 810, '348$3', 'Materials specified', 0, NULL, NULL),
(818, 810, '348$6', 'Linkage', 0, NULL, NULL),
(819, 810, '348$7', 'Data provenance', 0, NULL, NULL),
(820, 810, '348$8', 'Field link and sequence number', 0, NULL, NULL),
(821, 821, '351$a', 'Organization', 0, NULL, NULL),
(822, 821, '351$b', 'Arrangement', 0, NULL, NULL),
(823, 821, '351$c', 'Hierarchical level', 0, NULL, NULL),
(824, 821, '351$3', 'Materials specified', 0, NULL, NULL),
(825, 821, '351$6', 'Linkage', 0, NULL, NULL),
(826, 821, '351$8', 'Field link and sequence number', 0, NULL, NULL),
(827, 827, '352$a', 'Direct reference method', 0, NULL, NULL),
(828, 827, '352$b', 'Object type', 0, NULL, NULL),
(829, 827, '352$c', 'Object count', 0, NULL, NULL),
(830, 827, '352$d', 'Row count', 0, NULL, NULL),
(831, 827, '352$e', 'Column count', 0, NULL, NULL),
(832, 827, '352$f', 'Vertical count', 0, NULL, NULL),
(833, 827, '352$g', 'VPF topology level', 0, NULL, NULL),
(834, 827, '352$i', 'Indirect reference description', 0, NULL, NULL),
(835, 827, '352$q', 'Format of the digital image', 0, NULL, NULL);
INSERT INTO `marctag_subfields_loc` (`id`, `marctags_loc_id`, `code`, `description`, `repeatable`, `created_at`, `updated_at`) VALUES
(836, 827, '352$6', 'Linkage', 0, NULL, NULL),
(837, 827, '352$8', 'Field link and sequence number', 0, NULL, NULL),
(838, 838, '353$a', 'Supplementary content term', 0, NULL, NULL),
(839, 838, '353$b', 'Supplementary content code', 0, NULL, NULL),
(840, 838, '353$0', 'Authority record control number or standard number', 0, NULL, NULL),
(841, 838, '353$1', 'Real World Object URI', 0, NULL, NULL),
(842, 838, '353$2', 'Source', 0, NULL, NULL),
(843, 838, '353$3', 'Materials specified', 0, NULL, NULL),
(844, 838, '353$6', 'Linkage', 0, NULL, NULL),
(845, 838, '353$8', 'Field link and sequence number', 0, NULL, NULL),
(846, 846, '355$a', 'Security classification', 0, NULL, NULL),
(847, 846, '355$b', 'Handling instructions', 0, NULL, NULL),
(848, 846, '355$c', 'External dissemination information', 0, NULL, NULL),
(849, 846, '355$d', 'Downgrading or declassification event', 0, NULL, NULL),
(850, 846, '355$e', 'Classification system', 0, NULL, NULL),
(851, 846, '355$f', 'Country of origin code', 0, NULL, NULL),
(852, 846, '355$g', 'Downgrading date', 0, NULL, NULL),
(853, 846, '355$h', 'Declassification date', 0, NULL, NULL),
(854, 846, '355$j', 'Authorization ', 0, NULL, NULL),
(855, 846, '355$6', 'Linkage', 0, NULL, NULL),
(856, 846, '355$8', 'Field link and sequence number', 0, NULL, NULL),
(857, 857, '357$a', 'Originator control term', 0, NULL, NULL),
(858, 857, '357$b', 'Originating agency', 0, NULL, NULL),
(859, 857, '357$c', 'Authorized recipients of material', 0, NULL, NULL),
(860, 857, '357$g', 'Other restrictions', 0, NULL, NULL),
(861, 857, '357$6', 'Linkage', 0, NULL, NULL),
(862, 857, '357$8', 'Field link and sequence number', 0, NULL, NULL),
(863, 864, '362$a', 'Dates of publication and/or sequential designation', 0, NULL, NULL),
(864, 864, '362$z', 'Source of information', 0, NULL, NULL),
(865, 864, '362$6', 'Linkage', 0, NULL, NULL),
(866, 864, '362$8', 'Field link and sequence number', 0, NULL, NULL),
(867, 867, '363$a', 'First level of enumeration', 0, NULL, NULL),
(868, 867, '363$b', 'Second level of enumeration', 0, NULL, NULL),
(869, 867, '363$c', 'Third level of enumeration', 0, NULL, NULL),
(870, 867, '363$d', 'Fourth level of enumeration', 0, NULL, NULL),
(871, 867, '363$e', 'Fifth level of enumeration', 0, NULL, NULL),
(872, 867, '363$f', 'Sixth level of enumeration', 0, NULL, NULL),
(873, 867, '363$g', 'Alternative numbering scheme, first level of enumeration', 0, NULL, NULL),
(874, 867, '363$h', 'Alternative numbering scheme, second level of enumeration', 0, NULL, NULL),
(875, 867, '363$i', 'First level of chronology', 0, NULL, NULL),
(876, 867, '363$j', 'Second level of chronology', 0, NULL, NULL),
(877, 867, '363$k', 'Third level of chronology', 0, NULL, NULL),
(878, 867, '363$l', 'Fourth level of chronology', 0, NULL, NULL),
(879, 867, '363$m', 'Alternative numbering scheme, chronology', 0, NULL, NULL),
(880, 867, '363$u', 'First level textual designation', 0, NULL, NULL),
(881, 867, '363$v', 'First level of chronology, issuance', 0, NULL, NULL),
(882, 867, '363$x', 'Nonpublic note', 0, NULL, NULL),
(883, 867, '363$z', 'Public note', 0, NULL, NULL),
(884, 867, '363$6', 'Linkage', 0, NULL, NULL),
(885, 867, '363$8', 'Field link and sequence number', 0, NULL, NULL),
(886, 886, '365$a', 'Price type code', 0, NULL, NULL),
(887, 886, '365$b', 'Price amount', 0, NULL, NULL),
(888, 886, '365$c', 'Currency code', 0, NULL, NULL),
(889, 886, '365$d', 'Unit of pricing', 0, NULL, NULL),
(890, 886, '365$e', 'Price note', 0, NULL, NULL),
(891, 886, '365$f', 'Price effective from', 0, NULL, NULL),
(892, 886, '365$g', 'Price effective until', 0, NULL, NULL),
(893, 886, '365$h', 'Tax rate 1', 0, NULL, NULL),
(894, 886, '365$i', 'Tax rate 2', 0, NULL, NULL),
(895, 886, '365$j', 'ISO country code', 0, NULL, NULL),
(896, 886, '365$k', 'MARC country code', 0, NULL, NULL),
(897, 886, '365$m', 'Identification of pricing entity', 0, NULL, NULL),
(898, 886, '365$2', 'Source of price type code', 0, NULL, NULL),
(899, 886, '365$6', 'Linkage', 0, NULL, NULL),
(900, 886, '365$8', 'Field link and sequence number', 0, NULL, NULL),
(901, 901, '366$a', 'Publishers\' compressed title identification', 0, NULL, NULL),
(902, 901, '366$b', 'Detailed date of publication', 0, NULL, NULL),
(903, 901, '366$c', 'Availability status code', 0, NULL, NULL),
(904, 901, '366$d', 'Expected next availability date', 0, NULL, NULL),
(905, 901, '366$e', 'Note', 0, NULL, NULL),
(906, 901, '366$f', 'Publisher\'s discount category', 0, NULL, NULL),
(907, 901, '366$g', 'Date made out of print', 0, NULL, NULL),
(908, 901, '366$j', 'ISO country code', 0, NULL, NULL),
(909, 901, '366$k', 'MARC country code', 0, NULL, NULL),
(910, 901, '366$m', 'Identification of agency', 0, NULL, NULL),
(911, 901, '366$2', 'Source of price type code', 0, NULL, NULL),
(912, 901, '366$6', 'Linkage', 0, NULL, NULL),
(913, 901, '366$8', 'Field link and sequence number', 0, NULL, NULL),
(914, 914, '370$c', 'Associated country', 0, NULL, NULL),
(915, 914, '370$f', 'Other associated place', 0, NULL, NULL),
(916, 914, '370$g', 'Place of origin of work or expression', 0, NULL, NULL),
(917, 914, '370$i', 'Relationship information', 0, NULL, NULL),
(918, 914, '370$s', 'Start period', 0, NULL, NULL),
(919, 914, '370$t', 'End period', 0, NULL, NULL),
(920, 914, '370$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(921, 914, '370$v', 'Source of information', 0, NULL, NULL),
(922, 914, '370$0', 'Authority record control number or standard number', 0, NULL, NULL),
(923, 914, '370$1', 'Real World Object URI', 0, NULL, NULL),
(924, 914, '370$2', 'Source of term', 0, NULL, NULL),
(925, 914, '370$3', 'Materials specified', 0, NULL, NULL),
(926, 914, '370$4', 'Relationship', 0, NULL, NULL),
(927, 914, '370$6', 'Linkage', 0, NULL, NULL),
(928, 914, '370$7', 'Data provenance', 0, NULL, NULL),
(929, 914, '370$8', 'Field link and sequence number', 0, NULL, NULL),
(930, 930, '377$a', 'Language code', 0, NULL, NULL),
(931, 930, '377$l', 'Language term', 0, NULL, NULL),
(932, 930, '377$0', 'Authority record control number or standard number', 0, NULL, NULL),
(933, 930, '377$1', 'Real World Object URI', 0, NULL, NULL),
(934, 930, '377$2', 'Source', 0, NULL, NULL),
(935, 930, '377$3', 'Materials specified', 0, NULL, NULL),
(936, 930, '377$6', 'Linkage', 0, NULL, NULL),
(937, 930, '377$7', 'Data provenance', 0, NULL, NULL),
(938, 930, '377$8', 'Field link and sequence number', 0, NULL, NULL),
(939, 939, '380$a', 'Form of work', 0, NULL, NULL),
(940, 939, '380$0', 'Authority record control number or standard number', 0, NULL, NULL),
(941, 939, '380$1', 'Real World Object URI', 0, NULL, NULL),
(942, 939, '380$2', 'Source of term', 0, NULL, NULL),
(943, 939, '380$3', 'Materials specified', 0, NULL, NULL),
(944, 939, '380$6', 'Linkage', 0, NULL, NULL),
(945, 939, '380$7', 'Data provenance', 0, NULL, NULL),
(946, 939, '380$8', 'Field link and sequence number', 0, NULL, NULL),
(947, 947, '381$a', 'Other distinguishing characteristic', 0, NULL, NULL),
(948, 947, '381$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(949, 947, '381$v', 'Source of information', 0, NULL, NULL),
(950, 947, '381$0', 'Authority record control number or standard number', 0, NULL, NULL),
(951, 947, '381$1', 'Real World Object URI', 0, NULL, NULL),
(952, 947, '381$2', 'Source of term', 0, NULL, NULL),
(953, 947, '381$3', 'Materials specified', 0, NULL, NULL),
(954, 947, '381$6', 'Linkage', 0, NULL, NULL),
(955, 947, '381$7', 'Data provenance', 0, NULL, NULL),
(956, 947, '381$8', 'Field link and sequence number', 0, NULL, NULL),
(957, 957, '382$a', 'Medium of performance', 0, NULL, NULL),
(958, 957, '382$b', 'Soloist', 0, NULL, NULL),
(959, 957, '382$d', 'Doubling instrument', 0, NULL, NULL),
(960, 957, '382$e', 'Number of ensembles of the same type', 0, NULL, NULL),
(961, 957, '382$n', 'Number of performers of the same medium', 0, NULL, NULL),
(962, 957, '382$p', 'Alternative medium of performance', 0, NULL, NULL),
(963, 957, '382$r', 'Total number of individuals performing alongside ensembles', 0, NULL, NULL),
(964, 957, '382$s', 'Total number of performers', 0, NULL, NULL),
(965, 957, '382$t', 'Total number of ensembles', 0, NULL, NULL),
(966, 957, '382$v', 'Note', 0, NULL, NULL),
(967, 957, '382$0', 'Authority record control number or standard number', 0, NULL, NULL),
(968, 957, '382$1', 'Real World Object URI', 0, NULL, NULL),
(969, 957, '382$2', 'Source of term', 0, NULL, NULL),
(970, 957, '382$3', 'Materials specified', 0, NULL, NULL),
(971, 957, '382$6', 'Linkage', 0, NULL, NULL),
(972, 957, '382$7', 'Data provenance', 0, NULL, NULL),
(973, 957, '382$8', 'Field link and sequence number', 0, NULL, NULL),
(974, 974, '383$a', 'Serial number', 0, NULL, NULL),
(975, 974, '383$b', 'Opus number', 0, NULL, NULL),
(976, 974, '383$c', 'Thematic index number', 0, NULL, NULL),
(977, 974, '383$d', 'Thematic index code', 0, NULL, NULL),
(978, 974, '383$e', 'Publisher associated with opus number', 0, NULL, NULL),
(979, 974, '383$2', 'Source', 0, NULL, NULL),
(980, 974, '383$3', 'Materials specified', 0, NULL, NULL),
(981, 974, '383$6', 'Linkage', 0, NULL, NULL),
(982, 974, '383$7', 'Data provenance', 0, NULL, NULL),
(983, 974, '383$8', 'Field link and sequence number', 0, NULL, NULL),
(984, 984, '384$a', 'Key', 0, NULL, NULL),
(985, 984, '384$0', 'Authority record control number or standard number', 0, NULL, NULL),
(986, 984, '384$1', 'Real World Object URI', 0, NULL, NULL),
(987, 984, '384$3', 'Materials specified', 0, NULL, NULL),
(988, 984, '384$6', 'Linkage', 0, NULL, NULL),
(989, 984, '384$7', 'Data provenance', 0, NULL, NULL),
(990, 984, '384$8', 'Field link and sequence number', 0, NULL, NULL),
(991, 991, '385$a', 'Audience term', 0, NULL, NULL),
(992, 991, '385$b', 'Audience code', 0, NULL, NULL),
(993, 991, '385$m', 'Demographic group term', 0, NULL, NULL),
(994, 991, '385$n', 'Demographic group code', 0, NULL, NULL),
(995, 991, '385$0', 'Authority record control number or standard number', 0, NULL, NULL),
(996, 991, '385$1', 'Real World Object URI', 0, NULL, NULL),
(997, 991, '385$2', 'Source', 0, NULL, NULL),
(998, 991, '385$3', 'Materials specified', 0, NULL, NULL),
(999, 991, '385$6', 'Linkage', 0, NULL, NULL),
(1000, 991, '385$7', 'Data provenance', 0, NULL, NULL),
(1001, 991, '385$8', 'Field link and sequence number', 0, NULL, NULL),
(1002, 1002, '386$a', 'Creator/contributor term', 0, NULL, NULL),
(1003, 1002, '386$b', 'Creator/contributor code', 0, NULL, NULL),
(1004, 1002, '386$i', 'Relationship information', 0, NULL, NULL),
(1005, 1002, '386$m', 'Demographic group term', 0, NULL, NULL),
(1006, 1002, '386$n', 'Demographic group code', 0, NULL, NULL),
(1007, 1002, '386$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1008, 1002, '386$1', 'Real World Object URI', 0, NULL, NULL),
(1009, 1002, '386$2', 'Source', 0, NULL, NULL),
(1010, 1002, '386$3', 'Materials specified', 0, NULL, NULL),
(1011, 1002, '386$4', 'Relationship', 0, NULL, NULL),
(1012, 1002, '386$6', 'Linkage', 0, NULL, NULL),
(1013, 1002, '386$7', 'Data provenance', 0, NULL, NULL),
(1014, 1002, '386$8', 'Field link and sequence number', 0, NULL, NULL),
(1015, 1015, '387$a', 'Aspect ratio of representative expression', 0, NULL, NULL),
(1016, 1015, '387$b', 'Color content of representative expression', 0, NULL, NULL),
(1017, 1015, '387$c', 'Content type of representative expression', 0, NULL, NULL),
(1018, 1015, '387$d', 'Date of capture of representative expression', 0, NULL, NULL),
(1019, 1015, '387$e', 'Date of representative expression', 0, NULL, NULL),
(1020, 1015, '387$f', 'Duration of representative expression', 0, NULL, NULL),
(1021, 1015, '387$g', 'Intended audience of representative expression', 0, NULL, NULL),
(1022, 1015, '387$h', 'Language of representative expression', 0, NULL, NULL),
(1023, 1015, '387$i', 'Place of capture of representative expression', 0, NULL, NULL),
(1024, 1015, '387$j', 'Projection of cartographic content of representative expression', 0, NULL, NULL),
(1025, 1015, '387$k', 'Scale of representative expression', 0, NULL, NULL),
(1026, 1015, '387$l', 'Script of representative expression', 0, NULL, NULL),
(1027, 1015, '387$m', 'Sound content of representative expression', 0, NULL, NULL),
(1028, 1015, '387$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1029, 1015, '387$1', 'Real World Object URI', 0, NULL, NULL),
(1030, 1015, '387$2', 'Source of term', 0, NULL, NULL),
(1031, 1015, '387$3', 'Materials specified', 0, NULL, NULL),
(1032, 1015, '387$6', 'Linkage', 0, NULL, NULL),
(1033, 1015, '387$7', 'Data provenance', 0, NULL, NULL),
(1034, 1015, '387$8', 'Field link and sequence number', 0, NULL, NULL),
(1035, 1035, '388$a', 'Time period of creation term', 0, NULL, NULL),
(1036, 1035, '388$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1037, 1035, '388$1', 'Real World Object URI', 0, NULL, NULL),
(1038, 1035, '388$2', 'Source of term', 0, NULL, NULL),
(1039, 1035, '388$3', 'Materials specified', 0, NULL, NULL),
(1040, 1035, '388$6', 'Linkage', 0, NULL, NULL),
(1041, 1035, '388$7', 'Data provenance', 0, NULL, NULL),
(1042, 1035, '388$8', 'Field link and sequence number', 0, NULL, NULL),
(1043, 1043, '490$a', 'Series statement', 0, NULL, NULL),
(1044, 1043, '490$l', 'Library of Congress call number', 0, NULL, NULL),
(1045, 1043, '490$v', 'Volume/sequential designation', 0, NULL, NULL),
(1046, 1043, '490$x', 'International Standard Serial Number', 0, NULL, NULL),
(1047, 1043, '490$y', 'Incorrect ISSN', 0, NULL, NULL),
(1048, 1043, '490$z', 'Canceled ISSN', 0, NULL, NULL),
(1049, 1043, '490$3', 'Materials specified', 0, NULL, NULL),
(1050, 1043, '490$6', 'Linkage', 0, NULL, NULL),
(1051, 1043, '490$7', 'Data provenance', 0, NULL, NULL),
(1052, 1043, '490$8', 'Field link and sequence number', 0, NULL, NULL),
(1053, 1053, '500$a', 'General note', 0, NULL, NULL),
(1054, 1053, '500$3', 'Materials specified', 0, NULL, NULL),
(1055, 1053, '500$5', 'Institution to which field applies', 0, NULL, NULL),
(1056, 1053, '500$6', 'Linkage', 0, NULL, NULL),
(1057, 1053, '500$7', 'Data provenance', 0, NULL, NULL),
(1058, 1053, '500$8', 'Field link and sequence number', 0, NULL, NULL),
(1059, 1059, '501$a', 'With note', 0, NULL, NULL),
(1060, 1059, '501$5', 'Institution to which field applies', 0, NULL, NULL),
(1061, 1059, '501$6', 'Linkage', 0, NULL, NULL),
(1062, 1059, '501$7', 'Data provenance', 0, NULL, NULL),
(1063, 1059, '501$8', 'Field link and sequence number', 0, NULL, NULL),
(1064, 1064, '502$a', 'Dissertation note', 0, NULL, NULL),
(1065, 1064, '502$b', 'Degree type', 0, NULL, NULL),
(1066, 1064, '502$c', 'Name of granting institution', 0, NULL, NULL),
(1067, 1064, '502$d', 'Year degree granted', 0, NULL, NULL),
(1068, 1064, '502$g', 'Miscellaneous information', 0, NULL, NULL),
(1069, 1064, '502$o', 'Dissertation identifier', 0, NULL, NULL),
(1070, 1064, '502$6', 'Linkage', 0, NULL, NULL),
(1071, 1064, '502$7', 'Data provenance', 0, NULL, NULL),
(1072, 1064, '502$8', 'Field link and sequence number', 0, NULL, NULL),
(1073, 1073, '504$a', 'Bibliography, etc. note', 0, NULL, NULL),
(1074, 1073, '504$b', 'Number of references', 0, NULL, NULL),
(1075, 1073, '504$6', 'Linkage', 0, NULL, NULL),
(1076, 1073, '504$8', 'Field link and sequence number', 0, NULL, NULL),
(1077, 1077, '505$a', 'Formatted contents note', 0, NULL, NULL),
(1078, 1077, '505$g', 'Miscellaneous information', 0, NULL, NULL),
(1079, 1077, '505$r', 'Statement of responsibility', 0, NULL, NULL),
(1080, 1077, '505$t', 'Title', 0, NULL, NULL),
(1081, 1077, '505$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(1082, 1077, '505$6', 'Linkage', 0, NULL, NULL),
(1083, 1077, '505$7', 'Data provenance', 0, NULL, NULL),
(1084, 1077, '505$8', 'Field link and sequence number', 0, NULL, NULL),
(1085, 1085, '506$a', 'Terms governing access', 0, NULL, NULL),
(1086, 1085, '506$b', 'Jurisdiction', 0, NULL, NULL),
(1087, 1085, '506$c', 'Physical access provisions', 0, NULL, NULL),
(1088, 1085, '506$d', 'Authorized users', 0, NULL, NULL),
(1089, 1085, '506$e', 'Authorization', 0, NULL, NULL),
(1090, 1085, '506$f', 'Standardized terminology for access restriction', 0, NULL, NULL),
(1091, 1085, '506$g', 'Availability date', 0, NULL, NULL),
(1092, 1085, '506$q', 'Supplying agencys', 0, NULL, NULL),
(1093, 1085, '506$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(1094, 1085, '506$2', 'Source of term', 0, NULL, NULL),
(1095, 1085, '506$3', 'Materials specified', 0, NULL, NULL),
(1096, 1085, '506$5', 'Institution to which field applies', 0, NULL, NULL),
(1097, 1085, '506$6', 'Linkage', 0, NULL, NULL),
(1098, 1085, '506$8', 'Field link and sequence number', 0, NULL, NULL),
(1099, 1099, '507$a', 'Representative fraction of scale note', 0, NULL, NULL),
(1100, 1099, '507$b', 'Remainder of scale note', 0, NULL, NULL),
(1101, 1099, '507$6', 'Linkage', 0, NULL, NULL),
(1102, 1099, '507$8', 'Field link and sequence number', 0, NULL, NULL),
(1103, 1103, '508$a', 'Creation/production credits note', 0, NULL, NULL),
(1104, 1103, '508$6', 'Linkage', 0, NULL, NULL),
(1105, 1103, '508$7', 'Data provenance', 0, NULL, NULL),
(1106, 1103, '508$8', 'Field link and sequence number', 0, NULL, NULL),
(1107, 1107, '510$a', 'Name of source', 0, NULL, NULL),
(1108, 1107, '510$b', 'Coverage of source', 0, NULL, NULL),
(1109, 1107, '510$c', 'Location within source', 0, NULL, NULL),
(1110, 1107, '510$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(1111, 1107, '510$x', 'International Standard Serial Number', 0, NULL, NULL),
(1112, 1107, '510$3', 'Materials specified', 0, NULL, NULL),
(1113, 1107, '510$6', 'Linkage', 0, NULL, NULL),
(1114, 1107, '510$7', 'Data provenance', 0, NULL, NULL),
(1115, 1107, '510$8', 'Field link and sequence number', 0, NULL, NULL),
(1116, 1116, '511$a', 'Participant or performer note', 0, NULL, NULL),
(1117, 1116, '511$6', 'Linkage', 0, NULL, NULL),
(1118, 1116, '511$8', 'Field link and sequence number', 0, NULL, NULL),
(1119, 1119, '513$a', 'Type of report', 0, NULL, NULL),
(1120, 1119, '513$b', 'Period covered', 0, NULL, NULL),
(1121, 1119, '513$6', 'Linkage', 0, NULL, NULL),
(1122, 1119, '513$8', 'Field link and sequence number', 0, NULL, NULL),
(1123, 1123, '514$a', 'Attribute accuracy report', 0, NULL, NULL),
(1124, 1123, '514$b', 'Attribute accuracy value', 0, NULL, NULL),
(1125, 1123, '514$c', 'Attribute accuracy explanation', 0, NULL, NULL),
(1126, 1123, '514$d', 'Logical consistency report', 0, NULL, NULL),
(1127, 1123, '514$e', 'Completeness report', 0, NULL, NULL),
(1128, 1123, '514$f', 'Horizontal position accuracy report', 0, NULL, NULL),
(1129, 1123, '514$g', 'Horizontal position accuracy value', 0, NULL, NULL),
(1130, 1123, '514$h', 'Horizontal position accuracy explanation', 0, NULL, NULL),
(1131, 1123, '514$i', 'Vertical positional accuracy report', 0, NULL, NULL),
(1132, 1123, '514$j', 'Vertical positional accuracy value', 0, NULL, NULL),
(1133, 1123, '514$k', 'Vertical positional accuracy explanation', 0, NULL, NULL),
(1134, 1123, '514$m', 'Cloud cover', 0, NULL, NULL),
(1135, 1123, '514$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(1136, 1123, '514$z', 'Display note', 0, NULL, NULL),
(1137, 1123, '514$6', 'Linkage', 0, NULL, NULL),
(1138, 1123, '514$8', 'Field link and sequence number', 0, NULL, NULL),
(1139, 1139, '515$a', 'Numbering peculiarities note', 0, NULL, NULL),
(1140, 1139, '515$6', 'Linkage', 0, NULL, NULL),
(1141, 1139, '515$7', 'Data provenance', 0, NULL, NULL),
(1142, 1139, '515$8', 'Field link and sequence number', 0, NULL, NULL),
(1143, 1143, '516$a', 'Type of computer file or data note', 0, NULL, NULL),
(1144, 1143, '516$6', 'Linkage', 0, NULL, NULL),
(1145, 1143, '516$8', 'Field link and sequence number', 0, NULL, NULL),
(1146, 1146, '518$a', 'Date/time and place of an event note', 0, NULL, NULL),
(1147, 1146, '518$d', 'Date of event', 0, NULL, NULL),
(1148, 1146, '518$o', 'Other event information', 0, NULL, NULL),
(1149, 1146, '518$p', 'Place of event', 0, NULL, NULL),
(1150, 1146, '518$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1151, 1146, '518$1', 'Real World Object URI', 0, NULL, NULL),
(1152, 1146, '518$2', 'Source of term', 0, NULL, NULL),
(1153, 1146, '518$3', 'Materials specified', 0, NULL, NULL),
(1154, 1146, '518$6', 'Linkage', 0, NULL, NULL),
(1155, 1146, '518$7', 'Data provenance', 0, NULL, NULL),
(1156, 1146, '518$8', 'Field link and sequence number', 0, NULL, NULL),
(1157, 1157, '520$a', 'Summary, etc.', 0, NULL, NULL),
(1158, 1157, '520$b', 'Expansion of summary note', 0, NULL, NULL),
(1159, 1157, '520$c', 'Assigning source', 0, NULL, NULL),
(1160, 1157, '520$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(1161, 1157, '520$2', 'Source', 0, NULL, NULL),
(1162, 1157, '520$3', 'Materials specified', 0, NULL, NULL),
(1163, 1157, '520$6', 'Linkage', 0, NULL, NULL),
(1164, 1157, '520$7', 'Data provenance', 0, NULL, NULL),
(1165, 1157, '520$8', 'Field link and sequence number', 0, NULL, NULL),
(1166, 1166, '521$a', 'Target audience note', 0, NULL, NULL),
(1167, 1166, '521$b', 'Source', 0, NULL, NULL),
(1168, 1166, '521$3', 'Materials specified', 0, NULL, NULL),
(1169, 1166, '521$6', 'Linkage', 0, NULL, NULL),
(1170, 1166, '521$8', 'Field link and sequence number', 0, NULL, NULL),
(1171, 1171, '522$a', 'Geographic coverage note', 0, NULL, NULL),
(1172, 1171, '522$6', 'Linkage', 0, NULL, NULL),
(1173, 1171, '522$8', 'Field link and sequence number', 0, NULL, NULL),
(1174, 1174, '524$a', 'Preferred citation of described materials note', 0, NULL, NULL),
(1175, 1174, '524$2', 'Source of schema used', 0, NULL, NULL),
(1176, 1174, '524$3', 'Materials specified', 0, NULL, NULL),
(1177, 1174, '524$6', 'Linkage', 0, NULL, NULL),
(1178, 1174, '524$8', 'Field link and sequence number', 0, NULL, NULL),
(1179, 1179, '525$a', 'Supplement note', 0, NULL, NULL),
(1180, 1179, '525$6', 'Linkage', 0, NULL, NULL),
(1181, 1179, '525$8', 'Field link and sequence number', 0, NULL, NULL),
(1182, 1182, '526$a', 'Program name', 0, NULL, NULL),
(1183, 1182, '526$b', 'Interest level', 0, NULL, NULL),
(1184, 1182, '526$c', 'Reading level', 0, NULL, NULL),
(1185, 1182, '526$d', 'Title point value', 0, NULL, NULL),
(1186, 1182, '526$i', 'Display text', 0, NULL, NULL),
(1187, 1182, '526$x', 'Nonpublic note', 0, NULL, NULL),
(1188, 1182, '526$z', 'Public note', 0, NULL, NULL),
(1189, 1182, '526$5', 'Institution to which field applies', 0, NULL, NULL),
(1190, 1182, '526$6', 'Linkage', 0, NULL, NULL),
(1191, 1182, '526$8', 'Field link and sequence number', 0, NULL, NULL),
(1192, 1192, '530$a', 'Additional physical form available note', 0, NULL, NULL),
(1193, 1192, '530$b', 'Availability source', 0, NULL, NULL),
(1194, 1192, '530$c', 'Availability conditions', 0, NULL, NULL),
(1195, 1192, '530$d', 'Order number', 0, NULL, NULL),
(1196, 1192, '530$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(1197, 1192, '530$3', 'Materials specified', 0, NULL, NULL),
(1198, 1192, '530$6', 'Linkage', 0, NULL, NULL),
(1199, 1192, '530$8', 'Field link and sequence number', 0, NULL, NULL),
(1200, 1200, '532$a', 'Summary of accessibility', 0, NULL, NULL),
(1201, 1200, '532$6', 'Linkage', 0, NULL, NULL),
(1202, 1200, '532$8', 'Field link and sequence number', 0, NULL, NULL),
(1203, 1203, '533$a', 'Type of reproduction', 0, NULL, NULL),
(1204, 1203, '533$b', 'Place of reproduction', 0, NULL, NULL),
(1205, 1203, '533$c', 'Agency responsible for reproduction', 0, NULL, NULL),
(1206, 1203, '533$d', 'Date of reproduction', 0, NULL, NULL),
(1207, 1203, '533$e', 'Physical description of reproduction', 0, NULL, NULL),
(1208, 1203, '533$f', 'Series statement of reproduction', 0, NULL, NULL),
(1209, 1203, '533$m', 'Dates and/or sequential designation of issues reproduced', 0, NULL, NULL),
(1210, 1203, '533$n', 'Note about reproduction', 0, NULL, NULL),
(1211, 1203, '533$y', 'Data provenance', 0, NULL, NULL),
(1212, 1203, '533$3', 'Materials specified', 0, NULL, NULL),
(1213, 1203, '533$5', 'Institution to which field applies', 0, NULL, NULL),
(1214, 1203, '533$6', 'Linkage', 0, NULL, NULL),
(1215, 1203, '533$7', 'Fixed-length data elements of reproduction', 0, NULL, NULL),
(1216, 1203, '533$8', 'Field link and sequence number', 0, NULL, NULL),
(1217, 1217, '534$a', 'Main entry of original', 0, NULL, NULL),
(1218, 1217, '534$b', 'Edition statement of original', 0, NULL, NULL),
(1219, 1217, '534$c', 'Publication, distribution, etc. of original', 0, NULL, NULL),
(1220, 1217, '534$e', 'Physical description, etc. of original', 0, NULL, NULL),
(1221, 1217, '534$f', 'Series statement of original', 0, NULL, NULL),
(1222, 1217, '534$k', 'Key title of original', 0, NULL, NULL),
(1223, 1217, '534$l', 'Location of original', 0, NULL, NULL),
(1224, 1217, '534$m', 'Material specific details', 0, NULL, NULL),
(1225, 1217, '534$n', 'Note about original', 0, NULL, NULL),
(1226, 1217, '534$o', 'Other resource identifier', 0, NULL, NULL),
(1227, 1217, '534$p', 'Introductory phrase', 0, NULL, NULL),
(1228, 1217, '534$t', 'Title statement of original', 0, NULL, NULL),
(1229, 1217, '534$x', 'International Standard Serial Number', 0, NULL, NULL),
(1230, 1217, '534$z', 'International Standard Book Number', 0, NULL, NULL),
(1231, 1217, '534$3', 'Materials specified', 0, NULL, NULL),
(1232, 1217, '534$6', 'Linkage', 0, NULL, NULL),
(1233, 1217, '534$8', 'Field link and sequence number', 0, NULL, NULL),
(1234, 1234, '535$a', 'Custodian', 0, NULL, NULL),
(1235, 1234, '535$b', 'Postal address', 0, NULL, NULL),
(1236, 1234, '535$c', 'Country', 0, NULL, NULL),
(1237, 1234, '535$d', 'Telecommunications address', 0, NULL, NULL),
(1238, 1234, '535$g', 'Repository location code', 0, NULL, NULL),
(1239, 1234, '535$3', 'Materials specified', 0, NULL, NULL),
(1240, 1234, '535$6', 'Linkage', 0, NULL, NULL),
(1241, 1234, '535$8', 'Field link and sequence number', 0, NULL, NULL),
(1242, 1242, '536$a', 'Text of note', 0, NULL, NULL),
(1243, 1242, '536$b', 'Contract number', 0, NULL, NULL),
(1244, 1242, '536$c', 'Grant number', 0, NULL, NULL),
(1245, 1242, '536$d', 'Undifferentiated number', 0, NULL, NULL),
(1246, 1242, '536$e', 'Program element number', 0, NULL, NULL),
(1247, 1242, '536$f', 'Project number', 0, NULL, NULL),
(1248, 1242, '536$g', 'Task number', 0, NULL, NULL),
(1249, 1242, '536$h', 'Work unit number', 0, NULL, NULL),
(1250, 1242, '536$6', 'Linkage', 0, NULL, NULL),
(1251, 1242, '536$8', 'Field link and sequence number', 0, NULL, NULL),
(1252, 1253, '538$a', 'System details note', 0, NULL, NULL),
(1253, 1253, '538$i', 'Display text', 0, NULL, NULL),
(1254, 1253, '538$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(1255, 1253, '538$3', 'Materials specified', 0, NULL, NULL),
(1256, 1253, '538$5', 'Institution to which field applies', 0, NULL, NULL),
(1257, 1253, '538$6', 'Linkage', 0, NULL, NULL),
(1258, 1253, '538$8', 'Field link and sequence number', 0, NULL, NULL),
(1259, 1259, '540$a', 'Terms governing use and reproduction', 0, NULL, NULL),
(1260, 1259, '540$b', 'Jurisdiction', 0, NULL, NULL),
(1261, 1259, '540$c', 'Authorization ', 0, NULL, NULL),
(1262, 1259, '540$d', 'Authorized users', 0, NULL, NULL),
(1263, 1259, '540$f', 'Use and reproduction rights', 0, NULL, NULL),
(1264, 1259, '540$g', 'Availability date', 0, NULL, NULL),
(1265, 1259, '540$q', 'Supplying agency', 0, NULL, NULL),
(1266, 1259, '540$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(1267, 1259, '540$2', 'Source of term', 0, NULL, NULL),
(1268, 1259, '540$3', 'Materials specified', 0, NULL, NULL),
(1269, 1259, '540$5', 'Institution to which field applies', 0, NULL, NULL),
(1270, 1259, '540$6', 'Linkage', 0, NULL, NULL),
(1271, 1259, '540$8', 'Field link and sequence number', 0, NULL, NULL),
(1272, 1273, '541$a', 'Source of acquisition', 0, NULL, NULL),
(1273, 1273, '541$b', 'Address', 0, NULL, NULL),
(1274, 1273, '541$c', 'Method of acquisition', 0, NULL, NULL),
(1275, 1273, '541$d', 'Date of acquisition', 0, NULL, NULL),
(1276, 1273, '541$e', 'Accession number', 0, NULL, NULL),
(1277, 1273, '541$f', 'Owner', 0, NULL, NULL),
(1278, 1273, '541$h', 'Purchase price', 0, NULL, NULL),
(1279, 1273, '541$n', 'Extent', 0, NULL, NULL),
(1280, 1273, '541$o', 'Type of unit', 0, NULL, NULL),
(1281, 1273, '541$3', 'Materials specified', 0, NULL, NULL),
(1282, 1273, '541$5', 'Institution to which field applies', 0, NULL, NULL),
(1283, 1273, '541$6', 'Linkage', 0, NULL, NULL),
(1284, 1273, '541$8', 'Field link and sequence number', 0, NULL, NULL),
(1285, 1285, '542$a', 'Personal creator', 0, NULL, NULL),
(1286, 1285, '542$b', 'Personal creator death date', 0, NULL, NULL),
(1287, 1285, '542$c', 'Corporate creator', 0, NULL, NULL),
(1288, 1285, '542$d', 'Copyright holder', 0, NULL, NULL),
(1289, 1285, '542$e', 'Copyright holder contact information', 0, NULL, NULL),
(1290, 1285, '542$f', 'Copyright statement', 0, NULL, NULL),
(1291, 1285, '542$g', 'Copyright date', 0, NULL, NULL),
(1292, 1285, '542$h', 'Copyright renewal date', 0, NULL, NULL),
(1293, 1285, '542$i', 'Publication date', 0, NULL, NULL),
(1294, 1285, '542$j', 'Creation date', 0, NULL, NULL),
(1295, 1285, '542$k', 'Publisher', 0, NULL, NULL),
(1296, 1285, '542$l', 'Copyright status', 0, NULL, NULL),
(1297, 1285, '542$m', 'Publication status', 0, NULL, NULL),
(1298, 1285, '542$n', 'Note', 0, NULL, NULL),
(1299, 1285, '542$o', 'Research date', 0, NULL, NULL),
(1300, 1285, '542$p', 'Country of publication or creation', 0, NULL, NULL),
(1301, 1285, '542$q', 'Supplying agency', 0, NULL, NULL),
(1302, 1285, '542$r', 'Jurisdiction of copyright assessment', 0, NULL, NULL),
(1303, 1285, '542$s', 'Source of information', 0, NULL, NULL),
(1304, 1285, '542$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(1305, 1285, '542$3', 'Materials specified', 0, NULL, NULL),
(1306, 1285, '542$6', 'Linkage', 0, NULL, NULL),
(1307, 1285, '542$8', 'Field link and sequence number', 0, NULL, NULL),
(1308, 1308, '544$a', 'Custodian', 0, NULL, NULL),
(1309, 1308, '544$b', 'Address', 0, NULL, NULL),
(1310, 1308, '544$c', 'Country', 0, NULL, NULL),
(1311, 1308, '544$d', 'Title', 0, NULL, NULL),
(1312, 1308, '544$e', 'Provenance', 0, NULL, NULL),
(1313, 1308, '544$n', 'Note', 0, NULL, NULL),
(1314, 1308, '544$3', 'Materials specified', 0, NULL, NULL),
(1315, 1308, '544$6', 'Linkage', 0, NULL, NULL),
(1316, 1308, '544$8', 'Field link and sequence number', 0, NULL, NULL),
(1317, 1317, '545$a', 'Biographical or historical data', 0, NULL, NULL),
(1318, 1317, '545$b', 'Expansion', 0, NULL, NULL),
(1319, 1317, '545$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(1320, 1317, '545$6', 'Linkage', 0, NULL, NULL),
(1321, 1317, '545$8', 'Field link and sequence number', 0, NULL, NULL),
(1322, 1322, '546$a', 'Language note', 0, NULL, NULL),
(1323, 1322, '546$b', 'Information code or alphabet', 0, NULL, NULL),
(1324, 1322, '546$3', 'Materials specified', 0, NULL, NULL),
(1325, 1322, '546$6', 'Linkage', 0, NULL, NULL),
(1326, 1322, '546$7', 'Data provenance', 0, NULL, NULL),
(1327, 1322, '546$8', 'Field link and sequence number', 0, NULL, NULL),
(1328, 1328, '547$a', 'Former title complexity note', 0, NULL, NULL),
(1329, 1328, '547$6', 'Linkage', 0, NULL, NULL),
(1330, 1328, '547$8', 'Field link and sequence number', 0, NULL, NULL),
(1331, 1331, '550$a', 'Issuing body note', 0, NULL, NULL),
(1332, 1331, '550$6', 'Linkage', 0, NULL, NULL),
(1333, 1331, '550$7', 'Data provenance', 0, NULL, NULL),
(1334, 1331, '550$8', 'Field link and sequence number', 0, NULL, NULL),
(1335, 1335, '552$a', 'Entity type label', 0, NULL, NULL),
(1336, 1335, '552$b', 'Entity type definition and source', 0, NULL, NULL),
(1337, 1335, '552$c', 'Attribute label', 0, NULL, NULL),
(1338, 1335, '552$d', 'Attribute definition and source', 0, NULL, NULL),
(1339, 1335, '552$e', 'Enumerated domain value', 0, NULL, NULL),
(1340, 1335, '552$f', 'Enumerated domain value definition and source', 0, NULL, NULL),
(1341, 1335, '552$g', 'Range domain minimum and maximum', 0, NULL, NULL),
(1342, 1335, '552$h', 'Codeset name and source', 0, NULL, NULL),
(1343, 1335, '552$i', 'Unrepresentable domain', 0, NULL, NULL),
(1344, 1335, '552$j', 'Attribute units of measurement and resolution', 0, NULL, NULL),
(1345, 1335, '552$k', 'Beginning and ending date of attribute values', 0, NULL, NULL),
(1346, 1335, '552$l', 'Attribute value accuracy', 0, NULL, NULL),
(1347, 1335, '552$m', 'Attribute value accuracy explanation', 0, NULL, NULL),
(1348, 1335, '552$n', 'Attribute measurement frequency', 0, NULL, NULL),
(1349, 1335, '552$o', 'Entity and attribute overview', 0, NULL, NULL),
(1350, 1335, '552$p', 'Entity and attribute detail citation', 0, NULL, NULL),
(1351, 1335, '552$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(1352, 1335, '552$z', 'Display note', 0, NULL, NULL),
(1353, 1335, '552$6', 'Linkage', 0, NULL, NULL),
(1354, 1335, '552$8', 'Field link and sequence number', 0, NULL, NULL),
(1355, 1355, '555$a', 'Cumulative index/finding aids note', 0, NULL, NULL),
(1356, 1355, '555$b', 'Availability source', 0, NULL, NULL),
(1357, 1355, '555$c', 'Degree of control', 0, NULL, NULL),
(1358, 1355, '555$d', 'Bibliographic reference', 0, NULL, NULL),
(1359, 1355, '555$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(1360, 1355, '555$3', 'Materials specified', 0, NULL, NULL),
(1361, 1355, '555$6', 'Linkage', 0, NULL, NULL),
(1362, 1355, '555$7', 'Data provenance', 0, NULL, NULL),
(1363, 1355, '555$8', 'Field link and sequence number', 0, NULL, NULL),
(1364, 1364, '556$a', 'Information about documentation note', 0, NULL, NULL),
(1365, 1364, '556$z', 'International Standard Book Number', 0, NULL, NULL),
(1366, 1364, '556$6', 'Linkage', 0, NULL, NULL),
(1367, 1364, '556$8', 'Field link and sequence number', 0, NULL, NULL),
(1368, 1368, '561$a', 'BibHistory', 0, NULL, NULL),
(1369, 1368, '561$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(1370, 1368, '561$3', 'Materials specified', 0, NULL, NULL),
(1371, 1368, '561$5', 'Institution to which field applies', 0, NULL, NULL),
(1372, 1368, '561$6', 'Linkage', 0, NULL, NULL),
(1373, 1368, '561$8', 'Field link and sequence number', 0, NULL, NULL),
(1374, 1374, '562$a', 'Identifying markings', 0, NULL, NULL),
(1375, 1374, '562$b', 'Copy identification', 0, NULL, NULL),
(1376, 1374, '562$c', 'Version identification', 0, NULL, NULL),
(1377, 1374, '562$d', 'Presentation format', 0, NULL, NULL),
(1378, 1374, '562$e', 'Number of copies', 0, NULL, NULL),
(1379, 1374, '562$3', 'Materials specified', 0, NULL, NULL),
(1380, 1374, '562$5', 'Institution to which field applies', 0, NULL, NULL),
(1381, 1374, '562$6', 'Linkage', 0, NULL, NULL),
(1382, 1374, '562$8', 'Field link and sequence number', 0, NULL, NULL),
(1383, 1383, '563$a', 'Binding note', 0, NULL, NULL),
(1384, 1383, '563$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(1385, 1383, '563$3', 'Materials specified', 0, NULL, NULL),
(1386, 1383, '563$5', 'Institution to which field applies', 0, NULL, NULL),
(1387, 1383, '563$6', 'Linkage', 0, NULL, NULL),
(1388, 1383, '563$8', 'Field link and sequence number', 0, NULL, NULL),
(1389, 1389, '565$a', 'Number of cases/variables', 0, NULL, NULL),
(1390, 1389, '565$b', 'Name of variable', 0, NULL, NULL),
(1391, 1389, '565$c', 'Unit of analysis', 0, NULL, NULL),
(1392, 1389, '565$d', 'Universe of data', 0, NULL, NULL),
(1393, 1389, '565$e', 'Filing scheme or code', 0, NULL, NULL),
(1394, 1389, '565$3', 'Materials specified', 0, NULL, NULL),
(1395, 1389, '565$6', 'Linkage', 0, NULL, NULL),
(1396, 1389, '565$8', 'Field link and sequence number', 0, NULL, NULL),
(1397, 1397, '567$a', 'Methodology note', 0, NULL, NULL),
(1398, 1397, '567$b', 'Controlled term', 0, NULL, NULL),
(1399, 1397, '567$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1400, 1397, '567$1', 'Real World Object URI', 0, NULL, NULL),
(1401, 1397, '567$2', 'Source of term', 0, NULL, NULL),
(1402, 1397, '567$6', 'Linkage', 0, NULL, NULL),
(1403, 1397, '567$8', 'Field link and sequence number', 0, NULL, NULL),
(1404, 1404, '580$a', 'Linking entry complexity note', 0, NULL, NULL),
(1405, 1404, '580$6', 'Linkage', 0, NULL, NULL),
(1406, 1404, '580$8', 'Field link and sequence number', 0, NULL, NULL),
(1407, 1407, '581$a', 'Publications about described materials note', 0, NULL, NULL),
(1408, 1407, '581$z', 'International Standard Book Number', 0, NULL, NULL),
(1409, 1407, '581$3', 'Materials specified', 0, NULL, NULL),
(1410, 1407, '581$6', 'Linkage', 0, NULL, NULL),
(1411, 1407, '581$8', 'Field link and sequence number', 0, NULL, NULL),
(1412, 1412, '583$a', 'Action', 0, NULL, NULL),
(1413, 1412, '583$b', 'Action identification', 0, NULL, NULL),
(1414, 1412, '583$c', 'Time/date of action', 0, NULL, NULL),
(1415, 1412, '583$d', 'Action interval', 0, NULL, NULL),
(1416, 1412, '583$e', 'Contingency for action', 0, NULL, NULL),
(1417, 1412, '583$f', 'Authorization', 0, NULL, NULL),
(1418, 1412, '583$h', 'Jurisdiction', 0, NULL, NULL),
(1419, 1412, '583$i', 'Method of action', 0, NULL, NULL),
(1420, 1412, '583$j', 'Site of action', 0, NULL, NULL),
(1421, 1412, '583$k', 'Action agent', 0, NULL, NULL),
(1422, 1412, '583$l', 'Status', 0, NULL, NULL),
(1423, 1412, '583$n', 'Extent', 0, NULL, NULL),
(1424, 1412, '583$o', 'Type of unit', 0, NULL, NULL),
(1425, 1412, '583$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(1426, 1412, '583$x', 'Nonpublic note', 0, NULL, NULL),
(1427, 1412, '583$z', 'Public note', 0, NULL, NULL),
(1428, 1412, '583$2', 'Source of term', 0, NULL, NULL),
(1429, 1412, '583$3', 'Materials specified', 0, NULL, NULL),
(1430, 1412, '583$5', 'Institution to which field applies', 0, NULL, NULL),
(1431, 1412, '583$6', 'Linkage', 0, NULL, NULL),
(1432, 1412, '583$7', 'Data provenance', 0, NULL, NULL),
(1433, 1412, '583$8', 'Field link and sequence number', 0, NULL, NULL),
(1434, 1434, '584$a', 'Accumulation', 0, NULL, NULL),
(1435, 1434, '584$b', 'Frequency of use', 0, NULL, NULL),
(1436, 1434, '584$3', 'Materials specified', 0, NULL, NULL),
(1437, 1434, '584$5', 'Institution to which field applies', 0, NULL, NULL),
(1438, 1434, '584$6', 'Linkage', 0, NULL, NULL),
(1439, 1434, '584$8', 'Field link and sequence number', 0, NULL, NULL),
(1440, 1440, '585$a', 'Exhibitions note', 0, NULL, NULL),
(1441, 1440, '585$3', 'Materials specified', 0, NULL, NULL),
(1442, 1440, '585$5', 'Institution to which field applies', 0, NULL, NULL),
(1443, 1440, '585$6', 'Linkage', 0, NULL, NULL),
(1444, 1440, '585$8', 'Field link and sequence number', 0, NULL, NULL),
(1445, 1445, '586$a', 'Awards note', 0, NULL, NULL),
(1446, 1445, '586$3', 'Materials specified', 0, NULL, NULL),
(1447, 1445, '586$6', 'Linkage', 0, NULL, NULL),
(1448, 1445, '586$8', 'Field link and sequence number', 0, NULL, NULL),
(1449, 1449, '588$a', 'Source of description note', 0, NULL, NULL),
(1450, 1449, '588$5', 'Institution to which field applies', 0, NULL, NULL),
(1451, 1449, '588$6', 'Linkage', 0, NULL, NULL),
(1452, 1449, '588$8', 'Field link and sequence number', 0, NULL, NULL),
(1453, 1453, '600$a', 'Personal name', 0, NULL, NULL),
(1454, 1453, '600$b', 'Numeration', 0, NULL, NULL),
(1455, 1453, '600$c', 'Titles and other words associated with a name', 0, NULL, NULL),
(1456, 1453, '600$d', 'Dates associated with a name', 0, NULL, NULL),
(1457, 1453, '600$e', 'Relator term', 0, NULL, NULL),
(1458, 1453, '600$f', 'Date of a work', 0, NULL, NULL),
(1459, 1453, '600$g', 'Miscellaneous information', 0, NULL, NULL),
(1460, 1453, '600$h', 'Medium', 0, NULL, NULL),
(1461, 1453, '600$j', 'Attribution qualifier', 0, NULL, NULL),
(1462, 1453, '600$k', 'Form subheading', 0, NULL, NULL),
(1463, 1453, '600$l', 'Language of a work', 0, NULL, NULL),
(1464, 1453, '600$m', 'Medium of performance for music', 0, NULL, NULL),
(1465, 1453, '600$n', 'Number of part/section of a work', 0, NULL, NULL),
(1466, 1453, '600$o', 'Arranged statement for music', 0, NULL, NULL),
(1467, 1453, '600$p', 'Name of part/section of a work', 0, NULL, NULL),
(1468, 1453, '600$q', 'Fuller form of name', 0, NULL, NULL),
(1469, 1453, '600$r', 'Key for music', 0, NULL, NULL),
(1470, 1453, '600$s', 'Version', 0, NULL, NULL),
(1471, 1453, '600$t', 'Title of a work', 0, NULL, NULL),
(1472, 1453, '600$u', 'Affiliation', 0, NULL, NULL),
(1473, 1453, '600$v', 'Form subdivision', 0, NULL, NULL),
(1474, 1453, '600$x', 'General subdivision', 0, NULL, NULL),
(1475, 1453, '600$y', 'Chronological subdivision', 0, NULL, NULL),
(1476, 1453, '600$z', 'Geographic subdivision', 0, NULL, NULL),
(1477, 1453, '600$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1478, 1453, '600$1', 'Real World Object URI', 0, NULL, NULL),
(1479, 1453, '600$2', 'Source of heading or term', 0, NULL, NULL),
(1480, 1453, '600$3', 'Materials specified', 0, NULL, NULL),
(1481, 1453, '600$4', 'Relationship ', 0, NULL, NULL),
(1482, 1453, '600$6', 'Linkage', 0, NULL, NULL),
(1483, 1453, '600$7', 'Data provenance', 0, NULL, NULL),
(1484, 1453, '600$8', 'Field link and sequence number', 0, NULL, NULL),
(1485, 1485, '610$a', 'Corporate name or jurisdiction name as entry element', 0, NULL, NULL),
(1486, 1485, '610$b', 'Subordinate unit', 0, NULL, NULL),
(1487, 1485, '610$c', 'Location of meeting', 0, NULL, NULL),
(1488, 1485, '610$d', 'Date of meeting or treaty signing', 0, NULL, NULL),
(1489, 1485, '610$e', 'Relator term', 0, NULL, NULL),
(1490, 1485, '610$f', 'Date of a work', 0, NULL, NULL),
(1491, 1485, '610$g', 'Miscellaneous information', 0, NULL, NULL),
(1492, 1485, '610$h', 'Medium', 0, NULL, NULL),
(1493, 1485, '610$k', 'Form subheading', 0, NULL, NULL),
(1494, 1485, '610$l', 'Language of a work', 0, NULL, NULL),
(1495, 1485, '610$m', 'Medium of performance for music', 0, NULL, NULL),
(1496, 1485, '610$n', 'Number of part/section/meeting', 0, NULL, NULL),
(1497, 1485, '610$o', 'Arranged statement for music', 0, NULL, NULL),
(1498, 1485, '610$p', 'Name of part/section of a work', 0, NULL, NULL),
(1499, 1485, '610$r', 'Key for music', 0, NULL, NULL),
(1500, 1485, '610$s', 'Version', 0, NULL, NULL),
(1501, 1485, '610$t', 'Title of a work', 0, NULL, NULL),
(1502, 1485, '610$u', 'Affiliation', 0, NULL, NULL),
(1503, 1485, '610$v', 'Form subdivision', 0, NULL, NULL),
(1504, 1485, '610$x', 'General subdivision', 0, NULL, NULL),
(1505, 1485, '610$y', 'Chronological subdivision', 0, NULL, NULL),
(1506, 1485, '610$z', 'Geographic subdivision', 0, NULL, NULL),
(1507, 1485, '610$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1508, 1485, '610$1', 'Real World Object URI', 0, NULL, NULL),
(1509, 1485, '610$2', 'Source of heading or term', 0, NULL, NULL),
(1510, 1485, '610$3', 'Materials specified', 0, NULL, NULL),
(1511, 1485, '610$4', 'Relationship ', 0, NULL, NULL),
(1512, 1485, '610$6', 'Linkage', 0, NULL, NULL),
(1513, 1485, '610$7', 'Data provenance', 0, NULL, NULL),
(1514, 1485, '610$8', 'Field link and sequence number', 0, NULL, NULL),
(1515, 1515, '611$a', 'Meeting name or jurisdiction name as entry element', 0, NULL, NULL),
(1516, 1515, '611$c', 'Location of meeting', 0, NULL, NULL),
(1517, 1515, '611$d', 'Date of meeting or treaty signing', 0, NULL, NULL),
(1518, 1515, '611$e', 'Subordinate unit', 0, NULL, NULL),
(1519, 1515, '611$f', 'Date of a work', 0, NULL, NULL),
(1520, 1515, '611$g', 'Miscellaneous information', 0, NULL, NULL),
(1521, 1515, '611$h', 'Medium', 0, NULL, NULL),
(1522, 1515, '611$j', 'Relator term', 0, NULL, NULL),
(1523, 1515, '611$k', 'Form subheading', 0, NULL, NULL),
(1524, 1515, '611$l', 'Language of a work', 0, NULL, NULL),
(1525, 1515, '611$n', 'Number of part/section/meeting', 0, NULL, NULL),
(1526, 1515, '611$p', 'Name of part/section of a work', 0, NULL, NULL),
(1527, 1515, '611$q', 'Name of meeting following jurisdiction name entry element', 0, NULL, NULL),
(1528, 1515, '611$s', 'Version', 0, NULL, NULL),
(1529, 1515, '611$t', 'Title of a work', 0, NULL, NULL),
(1530, 1515, '611$u', 'Affiliation', 0, NULL, NULL),
(1531, 1515, '611$v', 'Form subdivision', 0, NULL, NULL),
(1532, 1515, '611$x', 'General subdivision', 0, NULL, NULL),
(1533, 1515, '611$y', 'Chronological subdivision', 0, NULL, NULL),
(1534, 1515, '611$z', 'Geographic subdivision', 0, NULL, NULL),
(1535, 1515, '611$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1536, 1515, '611$1', 'Real World Object URI', 0, NULL, NULL),
(1537, 1515, '611$2', 'Source of heading or term', 0, NULL, NULL),
(1538, 1515, '611$3', 'Materials specified', 0, NULL, NULL),
(1539, 1515, '611$4', 'Relationship ', 0, NULL, NULL),
(1540, 1515, '611$6', 'Linkage', 0, NULL, NULL),
(1541, 1515, '611$7', 'Data provenance', 0, NULL, NULL),
(1542, 1515, '611$8', 'Field link and sequence number', 0, NULL, NULL),
(1543, 1543, '630$a', 'Uniform title', 0, NULL, NULL),
(1544, 1543, '630$d', 'Date of treaty signing', 0, NULL, NULL),
(1545, 1543, '630$e', 'Relator term', 0, NULL, NULL),
(1546, 1543, '630$f', 'Date of a work', 0, NULL, NULL),
(1547, 1543, '630$g', 'Miscellaneous information', 0, NULL, NULL),
(1548, 1543, '630$h', 'Medium', 0, NULL, NULL),
(1549, 1543, '630$k', 'Form subheading', 0, NULL, NULL),
(1550, 1543, '630$l', 'Language of a work', 0, NULL, NULL),
(1551, 1543, '630$m', 'Medium of performance for music', 0, NULL, NULL),
(1552, 1543, '630$n', 'Number of part/section of a work', 0, NULL, NULL),
(1553, 1543, '630$o', 'Arranged statement for music', 0, NULL, NULL),
(1554, 1543, '630$p', 'Name of part/section of a work', 0, NULL, NULL),
(1555, 1543, '630$r', 'Key for music', 0, NULL, NULL),
(1556, 1543, '630$s', 'Version', 0, NULL, NULL),
(1557, 1543, '630$t', 'Title of a work', 0, NULL, NULL),
(1558, 1543, '630$v', 'Form subdivision', 0, NULL, NULL),
(1559, 1543, '630$x', 'General subdivision', 0, NULL, NULL),
(1560, 1543, '630$y', 'Chronological subdivision', 0, NULL, NULL),
(1561, 1543, '630$z', 'Geographic subdivision', 0, NULL, NULL),
(1562, 1543, '630$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1563, 1543, '630$1', 'Real World Object URI', 0, NULL, NULL),
(1564, 1543, '630$2', 'Source of heading or term', 0, NULL, NULL),
(1565, 1543, '630$3', 'Materials specified', 0, NULL, NULL),
(1566, 1543, '630$4', 'Relationship ', 0, NULL, NULL),
(1567, 1543, '630$6', 'Linkage', 0, NULL, NULL),
(1568, 1543, '630$7', 'Data provenance', 0, NULL, NULL),
(1569, 1543, '630$8', 'Field link and sequence number', 0, NULL, NULL),
(1570, 1570, '647$a', 'Named event', 0, NULL, NULL),
(1571, 1570, '647$c', 'Location of named event', 0, NULL, NULL),
(1572, 1570, '647$d', 'Date of named event', 0, NULL, NULL),
(1573, 1570, '647$g', 'Miscellaneous information', 0, NULL, NULL),
(1574, 1570, '647$v', 'Form subdivision', 0, NULL, NULL),
(1575, 1570, '647$x', 'General subdivision', 0, NULL, NULL),
(1576, 1570, '647$y', 'Chronological subdivision', 0, NULL, NULL),
(1577, 1570, '647$z', 'Geographic subdivision', 0, NULL, NULL),
(1578, 1570, '647$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1579, 1570, '647$1', 'Real World Object URI', 0, NULL, NULL),
(1580, 1570, '647$2', 'Source of heading or term', 0, NULL, NULL),
(1581, 1570, '647$3', 'Materials specified', 0, NULL, NULL),
(1582, 1570, '647$6', 'Linkage', 0, NULL, NULL),
(1583, 1570, '647$8', 'Field link and sequence number', 0, NULL, NULL),
(1584, 1584, '648$a', 'Chronological term', 0, NULL, NULL),
(1585, 1584, '648$v', 'Form subdivision', 0, NULL, NULL),
(1586, 1584, '648$x', 'General subdivision', 0, NULL, NULL),
(1587, 1584, '648$y', 'Chronological subdivision', 0, NULL, NULL),
(1588, 1584, '648$z', 'Geographic subdivision', 0, NULL, NULL),
(1589, 1584, '648$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1590, 1584, '648$1', 'Real World Object URI', 0, NULL, NULL),
(1591, 1584, '648$2', 'Source of heading or term', 0, NULL, NULL),
(1592, 1584, '648$3', 'Materials specified', 0, NULL, NULL),
(1593, 1584, '648$6', 'Linkage', 0, NULL, NULL),
(1594, 1584, '648$7', 'Data provenance', 0, NULL, NULL),
(1595, 1584, '648$8', 'Field link and sequence number', 0, NULL, NULL),
(1596, 1596, '650$a', 'Topical term or geographic name entry element', 0, NULL, NULL),
(1597, 1596, '650$b', 'Topical term following geographic name entry element', 0, NULL, NULL),
(1598, 1596, '650$c', 'Location of event', 0, NULL, NULL),
(1599, 1596, '650$d', 'Active dates', 0, NULL, NULL),
(1600, 1596, '650$e', 'Relator term', 0, NULL, NULL),
(1601, 1596, '650$g', 'Miscellaneous information', 0, NULL, NULL),
(1602, 1596, '650$v', 'Form subdivision', 0, NULL, NULL),
(1603, 1596, '650$x', 'General subdivision', 0, NULL, NULL),
(1604, 1596, '650$y', 'Chronological subdivision', 0, NULL, NULL),
(1605, 1596, '650$z', 'Geographic subdivision', 0, NULL, NULL),
(1606, 1596, '650$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1607, 1596, '650$1', 'Real World Object URI', 0, NULL, NULL),
(1608, 1596, '650$2', 'Source of heading or term', 0, NULL, NULL),
(1609, 1596, '650$3', 'Materials specified', 0, NULL, NULL),
(1610, 1596, '650$4', 'Relationship ', 0, NULL, NULL),
(1611, 1596, '650$6', 'Linkage', 0, NULL, NULL),
(1612, 1596, '650$7', 'Data provenance', 0, NULL, NULL),
(1613, 1596, '650$8', 'Field link and sequence number', 0, NULL, NULL),
(1614, 1614, '651$a', 'Geographic name', 0, NULL, NULL),
(1615, 1614, '651$e', 'Relator term', 0, NULL, NULL),
(1616, 1614, '651$g', 'Miscellaneous information', 0, NULL, NULL),
(1617, 1614, '651$v', 'Form subdivision', 0, NULL, NULL),
(1618, 1614, '651$x', 'General subdivision', 0, NULL, NULL),
(1619, 1614, '651$y', 'Chronological subdivision', 0, NULL, NULL),
(1620, 1614, '651$z', 'Geographic subdivision', 0, NULL, NULL),
(1621, 1614, '651$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1622, 1614, '651$1', 'Real World Object URI', 0, NULL, NULL),
(1623, 1614, '651$2', 'Source of heading or term', 0, NULL, NULL),
(1624, 1614, '651$3', 'Materials specified', 0, NULL, NULL),
(1625, 1614, '651$4', 'Relationship', 0, NULL, NULL),
(1626, 1614, '651$6', 'Linkage', 0, NULL, NULL),
(1627, 1614, '651$7', 'Data provenance', 0, NULL, NULL),
(1628, 1614, '651$8', 'Field link and sequence number', 0, NULL, NULL),
(1629, 1629, '653$a', 'Uncontrolled term', 0, NULL, NULL),
(1630, 1629, '653$6', 'Linkage', 0, NULL, NULL),
(1631, 1629, '653$7', 'Data provenance', 0, NULL, NULL),
(1632, 1629, '653$8', 'Field link and sequence number', 0, NULL, NULL),
(1633, 1633, '654$a', 'Focus term', 0, NULL, NULL),
(1634, 1633, '654$b', 'Non-focus term', 0, NULL, NULL),
(1635, 1633, '654$c', 'Facet/hierarchy designation', 0, NULL, NULL),
(1636, 1633, '654$e', 'Relator term', 0, NULL, NULL),
(1637, 1633, '654$v', 'Form subdivision', 0, NULL, NULL),
(1638, 1633, '654$y', 'Chronological subdivision', 0, NULL, NULL),
(1639, 1633, '654$z', 'Geographic subdivision', 0, NULL, NULL),
(1640, 1633, '654$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1641, 1633, '654$1', 'Real World Object URI', 0, NULL, NULL),
(1642, 1633, '654$2', 'Source of heading or term', 0, NULL, NULL),
(1643, 1633, '654$3', 'Materials specified', 0, NULL, NULL),
(1644, 1633, '654$4', 'Relationship', 0, NULL, NULL),
(1645, 1633, '654$6', 'Linkage', 0, NULL, NULL),
(1646, 1633, '654$8', 'Field link and sequence number', 0, NULL, NULL),
(1647, 1647, '655$a', 'Genre/form data or focus term', 0, NULL, NULL),
(1648, 1647, '655$b', 'Non-focus term', 0, NULL, NULL),
(1649, 1647, '655$c', 'Facet/hierarchy designation', 0, NULL, NULL),
(1650, 1647, '655$v', 'Form subdivision', 0, NULL, NULL);
INSERT INTO `marctag_subfields_loc` (`id`, `marctags_loc_id`, `code`, `description`, `repeatable`, `created_at`, `updated_at`) VALUES
(1651, 1647, '655$x', 'General subdivision', 0, NULL, NULL),
(1652, 1647, '655$y', 'Chronological subdivision', 0, NULL, NULL),
(1653, 1647, '655$z', 'Geographic subdivision', 0, NULL, NULL),
(1654, 1647, '655$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1655, 1647, '655$1', 'Real World Object URI', 0, NULL, NULL),
(1656, 1647, '655$2', 'Source of term', 0, NULL, NULL),
(1657, 1647, '655$3', 'Materials specified', 0, NULL, NULL),
(1658, 1647, '655$5', 'Institution to which field applies', 0, NULL, NULL),
(1659, 1647, '655$6', 'Linkage', 0, NULL, NULL),
(1660, 1647, '655$7', 'Data provenance', 0, NULL, NULL),
(1661, 1647, '655$8', 'Field link and sequence number', 0, NULL, NULL),
(1662, 1662, '656$a', 'Occupation', 0, NULL, NULL),
(1663, 1662, '656$k', 'Form', 0, NULL, NULL),
(1664, 1662, '656$v', 'Form subdivision', 0, NULL, NULL),
(1665, 1662, '656$x', 'General subdivision', 0, NULL, NULL),
(1666, 1662, '656$y', 'Chronological subdivision', 0, NULL, NULL),
(1667, 1662, '656$z', 'Geographic subdivision', 0, NULL, NULL),
(1668, 1662, '656$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1669, 1662, '656$1', 'Real World Object URI', 0, NULL, NULL),
(1670, 1662, '656$2', 'Source of term', 0, NULL, NULL),
(1671, 1662, '656$3', 'Materials specified', 0, NULL, NULL),
(1672, 1662, '656$6', 'Linkage', 0, NULL, NULL),
(1673, 1662, '656$8', 'Field link and sequence number', 0, NULL, NULL),
(1674, 1674, '657$a', 'Function', 0, NULL, NULL),
(1675, 1674, '657$v', 'Form subdivision', 0, NULL, NULL),
(1676, 1674, '657$x', 'General subdivision', 0, NULL, NULL),
(1677, 1674, '657$y', 'Chronological subdivision', 0, NULL, NULL),
(1678, 1674, '657$z', 'Geographic subdivision', 0, NULL, NULL),
(1679, 1674, '657$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1680, 1674, '657$1', 'Real World Object URI', 0, NULL, NULL),
(1681, 1674, '657$2', 'Source of term', 0, NULL, NULL),
(1682, 1674, '657$3', 'Materials specified', 0, NULL, NULL),
(1683, 1674, '657$6', 'Linkage', 0, NULL, NULL),
(1684, 1674, '657$8', 'Field link and sequence number', 0, NULL, NULL),
(1685, 1685, '658$a', 'Main curriculum objective', 0, NULL, NULL),
(1686, 1685, '658$b', 'Subordinate curriculum objective', 0, NULL, NULL),
(1687, 1685, '658$c', 'Curriculum code', 0, NULL, NULL),
(1688, 1685, '658$d', 'Correlation factor', 0, NULL, NULL),
(1689, 1685, '658$2', 'Source of term or code', 0, NULL, NULL),
(1690, 1685, '658$6', 'Linkage', 0, NULL, NULL),
(1691, 1685, '658$8', 'Field link and sequence number', 0, NULL, NULL),
(1692, 1692, '662$a', 'Country or larger entity', 0, NULL, NULL),
(1693, 1692, '662$b', 'First-order political jurisdiction', 0, NULL, NULL),
(1694, 1692, '662$c', 'Intermediate political jurisdiction', 0, NULL, NULL),
(1695, 1692, '662$d', 'City', 0, NULL, NULL),
(1696, 1692, '662$e', 'Relator term', 0, NULL, NULL),
(1697, 1692, '662$f', 'City subsection', 0, NULL, NULL),
(1698, 1692, '662$g', 'Other nonjurisdictional geographic region and feature', 0, NULL, NULL),
(1699, 1692, '662$h', 'Extraterrestrial area', 0, NULL, NULL),
(1700, 1692, '662$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1701, 1692, '662$1', 'Real World Object URI', 0, NULL, NULL),
(1702, 1692, '662$2', 'Source of heading or term', 0, NULL, NULL),
(1703, 1692, '662$4', 'Relationship', 0, NULL, NULL),
(1704, 1692, '662$6', 'Linkage', 0, NULL, NULL),
(1705, 1692, '662$8', 'Field link and sequence number', 0, NULL, NULL),
(1706, 1706, '688$a', 'Name, title, or term', 0, NULL, NULL),
(1707, 1706, '688$e', 'Relator term', 0, NULL, NULL),
(1708, 1706, '688$g', 'Miscellaneous information', 0, NULL, NULL),
(1709, 1706, '688$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1710, 1706, '688$1', 'Real World Object URI', 0, NULL, NULL),
(1711, 1706, '688$2', 'Source of name, title, or term', 0, NULL, NULL),
(1712, 1706, '688$3', 'Materials specified', 0, NULL, NULL),
(1713, 1706, '688$4', 'Relationship', 0, NULL, NULL),
(1714, 1706, '688$6', 'Linkage', 0, NULL, NULL),
(1715, 1706, '688$8', 'Field link and sequence number', 0, NULL, NULL),
(1716, 1716, '700$a', 'Personal name', 0, NULL, NULL),
(1717, 1716, '700$b', 'Numeration', 0, NULL, NULL),
(1718, 1716, '700$c', 'Titles and other words associated with a name', 0, NULL, NULL),
(1719, 1716, '700$d', 'Dates associated with a name', 0, NULL, NULL),
(1720, 1716, '700$e', 'Relator term', 0, NULL, NULL),
(1721, 1716, '700$f', 'Date of a work', 0, NULL, NULL),
(1722, 1716, '700$g', 'Miscellaneous information', 0, NULL, NULL),
(1723, 1716, '700$h', 'Medium', 0, NULL, NULL),
(1724, 1716, '700$i', 'Relationship information', 0, NULL, NULL),
(1725, 1716, '700$j', 'Attribution qualifier', 0, NULL, NULL),
(1726, 1716, '700$k', 'Form subheading', 0, NULL, NULL),
(1727, 1716, '700$l', 'Language of a work', 0, NULL, NULL),
(1728, 1716, '700$m', 'Medium of performance for music', 0, NULL, NULL),
(1729, 1716, '700$n', 'Number of part/section of a work', 0, NULL, NULL),
(1730, 1716, '700$o', 'Arranged statement for music', 0, NULL, NULL),
(1731, 1716, '700$p', 'Name of part/section of a work', 0, NULL, NULL),
(1732, 1716, '700$q', 'Fuller form of name', 0, NULL, NULL),
(1733, 1716, '700$r', 'Key for music', 0, NULL, NULL),
(1734, 1716, '700$s', 'Version', 0, NULL, NULL),
(1735, 1716, '700$t', 'Title of a work', 0, NULL, NULL),
(1736, 1716, '700$u', 'Affiliation', 0, NULL, NULL),
(1737, 1716, '700$x', 'International Standard Serial Number', 0, NULL, NULL),
(1738, 1716, '700$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1739, 1716, '700$1', 'Real World Object URI', 0, NULL, NULL),
(1740, 1716, '700$2', 'Source of heading or term', 0, NULL, NULL),
(1741, 1716, '700$3', 'Materials specified', 0, NULL, NULL),
(1742, 1716, '700$4', 'Relationship', 0, NULL, NULL),
(1743, 1716, '700$5', 'Institution to which field applies', 0, NULL, NULL),
(1744, 1716, '700$6', 'Linkage', 0, NULL, NULL),
(1745, 1716, '700$7', 'Data provenance', 0, NULL, NULL),
(1746, 1716, '700$8', 'Field link and sequence number', 0, NULL, NULL),
(1747, 1747, '710$a', 'Corporate name or jurisdiction name as entry element', 0, NULL, NULL),
(1748, 1747, '710$b', 'Subordinate unit', 0, NULL, NULL),
(1749, 1747, '710$c', 'Location of meeting', 0, NULL, NULL),
(1750, 1747, '710$d', 'Date of meeting or treaty signing', 0, NULL, NULL),
(1751, 1747, '710$e', 'Relator term', 0, NULL, NULL),
(1752, 1747, '710$f', 'Date of a work', 0, NULL, NULL),
(1753, 1747, '710$g', 'Miscellaneous information', 0, NULL, NULL),
(1754, 1747, '710$h', 'Medium', 0, NULL, NULL),
(1755, 1747, '710$i', 'Relationship information', 0, NULL, NULL),
(1756, 1747, '710$k', 'Form subheading', 0, NULL, NULL),
(1757, 1747, '710$l', 'Language of a work', 0, NULL, NULL),
(1758, 1747, '710$m', 'Medium of performance for music', 0, NULL, NULL),
(1759, 1747, '710$n', 'Number of part/section/meeting', 0, NULL, NULL),
(1760, 1747, '710$o', 'Arranged statement for music', 0, NULL, NULL),
(1761, 1747, '710$p', 'Name of part/section of a work', 0, NULL, NULL),
(1762, 1747, '710$r', 'Key for music', 0, NULL, NULL),
(1763, 1747, '710$s', 'Version', 0, NULL, NULL),
(1764, 1747, '710$t', 'Title of a work', 0, NULL, NULL),
(1765, 1747, '710$u', 'Affiliation', 0, NULL, NULL),
(1766, 1747, '710$x', 'International Standard Serial Number', 0, NULL, NULL),
(1767, 1747, '710$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1768, 1747, '710$1', 'Real World Object URI', 0, NULL, NULL),
(1769, 1747, '710$2', 'Source of heading or term', 0, NULL, NULL),
(1770, 1747, '710$3', 'Materials specified', 0, NULL, NULL),
(1771, 1747, '710$4', 'Relationship', 0, NULL, NULL),
(1772, 1747, '710$5', 'Institution to which field applies', 0, NULL, NULL),
(1773, 1747, '710$6', 'Linkage', 0, NULL, NULL),
(1774, 1747, '710$7', 'Data provenance', 0, NULL, NULL),
(1775, 1747, '710$8', 'Field link and sequence number', 0, NULL, NULL),
(1776, 1776, '711$a', 'Meeting name or jurisdiction name as entry element', 0, NULL, NULL),
(1777, 1776, '711$c', 'Location of meeting', 0, NULL, NULL),
(1778, 1776, '711$d', 'Date of meeting or treaty signing', 0, NULL, NULL),
(1779, 1776, '711$e', 'Subordinate unit', 0, NULL, NULL),
(1780, 1776, '711$f', 'Date of a work', 0, NULL, NULL),
(1781, 1776, '711$g', 'Miscellaneous information', 0, NULL, NULL),
(1782, 1776, '711$h', 'Medium', 0, NULL, NULL),
(1783, 1776, '711$i', 'Relationship information', 0, NULL, NULL),
(1784, 1776, '711$j', 'Relator term', 0, NULL, NULL),
(1785, 1776, '711$k', 'Form subheading', 0, NULL, NULL),
(1786, 1776, '711$l', 'Language of a work', 0, NULL, NULL),
(1787, 1776, '711$n', 'Number of part/section/meeting', 0, NULL, NULL),
(1788, 1776, '711$p', 'Name of part/section of a work', 0, NULL, NULL),
(1789, 1776, '711$q', 'Name of meeting following jurisdiction name entry element', 0, NULL, NULL),
(1790, 1776, '711$s', 'Version', 0, NULL, NULL),
(1791, 1776, '711$t', 'Title of a work', 0, NULL, NULL),
(1792, 1776, '711$u', 'Affiliation', 0, NULL, NULL),
(1793, 1776, '711$x', 'International Standard Serial Number', 0, NULL, NULL),
(1794, 1776, '711$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1795, 1776, '711$1', 'Real World Object URI', 0, NULL, NULL),
(1796, 1776, '711$2', 'Source of heading or term', 0, NULL, NULL),
(1797, 1776, '711$3', 'Materials specified', 0, NULL, NULL),
(1798, 1776, '711$4', 'Relationship', 0, NULL, NULL),
(1799, 1776, '711$5', 'Institution to which field applies', 0, NULL, NULL),
(1800, 1776, '711$6', 'Linkage', 0, NULL, NULL),
(1801, 1776, '711$7', 'Data provenance', 0, NULL, NULL),
(1802, 1776, '711$8', 'Field link and sequence number', 0, NULL, NULL),
(1803, 1803, '720$a', 'Name', 0, NULL, NULL),
(1804, 1803, '720$e', 'Relator term', 0, NULL, NULL),
(1805, 1803, '720$4', 'Relationship', 0, NULL, NULL),
(1806, 1803, '720$6', 'Linkage', 0, NULL, NULL),
(1807, 1803, '720$8', 'Field link and sequence number', 0, NULL, NULL),
(1808, 1808, '730$a', 'Uniform title', 0, NULL, NULL),
(1809, 1808, '730$d', 'Date of treaty signing', 0, NULL, NULL),
(1810, 1808, '730$f', 'Date of a work', 0, NULL, NULL),
(1811, 1808, '730$g', 'Miscellaneous information', 0, NULL, NULL),
(1812, 1808, '730$h', 'Medium', 0, NULL, NULL),
(1813, 1808, '730$i', 'Relationship information', 0, NULL, NULL),
(1814, 1808, '730$k', 'Form subheading', 0, NULL, NULL),
(1815, 1808, '730$l', 'Language of a work', 0, NULL, NULL),
(1816, 1808, '730$m', 'Medium of performance for music', 0, NULL, NULL),
(1817, 1808, '730$n', 'Number of part/section/meeting', 0, NULL, NULL),
(1818, 1808, '730$o', 'Arranged statement for music', 0, NULL, NULL),
(1819, 1808, '730$p', 'Name of part/section of a work', 0, NULL, NULL),
(1820, 1808, '730$r', 'Key for music', 0, NULL, NULL),
(1821, 1808, '730$s', 'Version', 0, NULL, NULL),
(1822, 1808, '730$t', 'Title of a work', 0, NULL, NULL),
(1823, 1808, '730$x', 'International Standard Serial Number', 0, NULL, NULL),
(1824, 1808, '730$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1825, 1808, '730$1', 'Real World Object URI', 0, NULL, NULL),
(1826, 1808, '730$2', 'Source of heading or term', 0, NULL, NULL),
(1827, 1808, '730$3', 'Materials specified', 0, NULL, NULL),
(1828, 1808, '730$4', 'Relationship', 0, NULL, NULL),
(1829, 1808, '730$5', 'Institution to which field applies', 0, NULL, NULL),
(1830, 1808, '730$6', 'Linkage', 0, NULL, NULL),
(1831, 1808, '730$8', 'Field link and sequence number', 0, NULL, NULL),
(1832, 1832, '740$a', 'Uncontrolled related/analytical title', 0, NULL, NULL),
(1833, 1832, '740$h', 'Medium', 0, NULL, NULL),
(1834, 1832, '740$n', 'Number of part/section of a work', 0, NULL, NULL),
(1835, 1832, '740$p', 'Name of part/section of a work', 0, NULL, NULL),
(1836, 1832, '740$5', 'Institution to which field applies', 0, NULL, NULL),
(1837, 1832, '740$6', 'Linkage', 0, NULL, NULL),
(1838, 1832, '740$8', 'Field link and sequence number', 0, NULL, NULL),
(1839, 1839, '751$a', 'Geographic name', 0, NULL, NULL),
(1840, 1839, '751$e', 'Relator term', 0, NULL, NULL),
(1841, 1839, '751$g', 'Miscellaneous information', 0, NULL, NULL),
(1842, 1839, '751$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1843, 1839, '751$1', 'Real World Object URI', 0, NULL, NULL),
(1844, 1839, '751$2', 'Source of heading or term', 0, NULL, NULL),
(1845, 1839, '751$3', 'Materials specified', 0, NULL, NULL),
(1846, 1839, '751$4', 'Relationship', 0, NULL, NULL),
(1847, 1839, '751$6', 'Linkage', 0, NULL, NULL),
(1848, 1839, '751$7', 'Data provenance', 0, NULL, NULL),
(1849, 1839, '751$8', 'Field link and sequence number', 0, NULL, NULL),
(1850, 1850, '752$a', 'Country or larger entity', 0, NULL, NULL),
(1851, 1850, '752$b', 'First-order political jurisdiction', 0, NULL, NULL),
(1852, 1850, '752$c', 'Intermediate political jurisdiction', 0, NULL, NULL),
(1853, 1850, '752$d', 'City', 0, NULL, NULL),
(1854, 1850, '752$e', 'Relator term', 0, NULL, NULL),
(1855, 1850, '752$f', 'City subsection', 0, NULL, NULL),
(1856, 1850, '752$g', 'Other nonjurisdictional geographic region and feature', 0, NULL, NULL),
(1857, 1850, '752$h', 'Extraterrestrial area', 0, NULL, NULL),
(1858, 1850, '752$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1859, 1850, '752$1', 'Real World Object URI', 0, NULL, NULL),
(1860, 1850, '752$2', 'Source of heading or term', 0, NULL, NULL),
(1861, 1850, '752$4', 'Relationship', 0, NULL, NULL),
(1862, 1850, '752$6', 'Linkage', 0, NULL, NULL),
(1863, 1850, '752$8', 'Field link and sequence number', 0, NULL, NULL),
(1864, 1864, '753$a', 'Make and model of machine', 0, NULL, NULL),
(1865, 1864, '753$b', 'Programming language', 0, NULL, NULL),
(1866, 1864, '753$c', 'Operating system', 0, NULL, NULL),
(1867, 1864, '753$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1868, 1864, '753$1', 'Real World Object URI', 0, NULL, NULL),
(1869, 1864, '753$2', 'Source of term', 0, NULL, NULL),
(1870, 1864, '753$6', 'Linkage', 0, NULL, NULL),
(1871, 1864, '753$8', 'Field link and sequence number', 0, NULL, NULL),
(1872, 1872, '754$a', 'Taxonomic name', 0, NULL, NULL),
(1873, 1872, '754$c', 'Taxonomic category', 0, NULL, NULL),
(1874, 1872, '754$d', 'Common or alternative name', 0, NULL, NULL),
(1875, 1872, '754$x', 'Non-public note', 0, NULL, NULL),
(1876, 1872, '754$z', 'Public note', 0, NULL, NULL),
(1877, 1872, '754$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1878, 1872, '754$1', 'Real World Object URI', 0, NULL, NULL),
(1879, 1872, '754$2', 'Source of term', 0, NULL, NULL),
(1880, 1872, '754$6', 'Linkage', 0, NULL, NULL),
(1881, 1872, '754$8', 'Field link and sequence number', 0, NULL, NULL),
(1882, 1882, '758$a', 'Label', 0, NULL, NULL),
(1883, 1882, '758$i', 'Relationship information', 0, NULL, NULL),
(1884, 1882, '758$0', 'Authority record control number or standard number', 0, NULL, NULL),
(1885, 1882, '758$1', 'Real World Object URI', 0, NULL, NULL),
(1886, 1882, '758$2', 'Source of heading or term', 0, NULL, NULL),
(1887, 1882, '758$3', 'Materials specified', 0, NULL, NULL),
(1888, 1882, '758$4', 'Relationship', 0, NULL, NULL),
(1889, 1882, '758$5', 'Institution to which field applies', 0, NULL, NULL),
(1890, 1882, '758$6', 'Linkage', 0, NULL, NULL),
(1891, 1882, '758$8', 'Field link and sequence number', 0, NULL, NULL),
(1892, 1892, '760$a', 'Main entry heading', 0, NULL, NULL),
(1893, 1892, '760$b', 'Edition', 0, NULL, NULL),
(1894, 1892, '760$c', 'Qualifying information', 0, NULL, NULL),
(1895, 1892, '760$d', 'Place, publisher, and date of publication', 0, NULL, NULL),
(1896, 1892, '760$g', 'Related parts', 0, NULL, NULL),
(1897, 1892, '760$h', 'Physical description', 0, NULL, NULL),
(1898, 1892, '760$i', 'Relationship information', 0, NULL, NULL),
(1899, 1892, '760$l', 'Data provenance', 0, NULL, NULL),
(1900, 1892, '760$m', 'Material-specific details', 0, NULL, NULL),
(1901, 1892, '760$n', 'Note', 0, NULL, NULL),
(1902, 1892, '760$o', 'Other item identifier', 0, NULL, NULL),
(1903, 1892, '760$s', 'Uniform title', 0, NULL, NULL),
(1904, 1892, '760$t', 'Title', 0, NULL, NULL),
(1905, 1892, '760$w', 'Record control number', 0, NULL, NULL),
(1906, 1892, '760$x', 'International Standard Serial Number', 0, NULL, NULL),
(1907, 1892, '760$y', 'CODEN designation', 0, NULL, NULL),
(1908, 1892, '760$4', 'Relationship', 0, NULL, NULL),
(1909, 1892, '760$6', 'Linkage', 0, NULL, NULL),
(1910, 1892, '760$7', 'Control subfield', 0, NULL, NULL),
(1911, 1892, '760$8', 'Field link and sequence number', 0, NULL, NULL),
(1912, 1912, '762$a', 'Main entry heading', 0, NULL, NULL),
(1913, 1912, '762$b', 'Edition', 0, NULL, NULL),
(1914, 1912, '762$c', 'Qualifying information', 0, NULL, NULL),
(1915, 1912, '762$d', 'Place, publisher, and date of publication', 0, NULL, NULL),
(1916, 1912, '762$g', 'Related parts', 0, NULL, NULL),
(1917, 1912, '762$h', 'Physical description', 0, NULL, NULL),
(1918, 1912, '762$i', 'Relationship information', 0, NULL, NULL),
(1919, 1912, '762$l', 'Data provenance', 0, NULL, NULL),
(1920, 1912, '762$m', 'Material-specific details', 0, NULL, NULL),
(1921, 1912, '762$n', 'Note', 0, NULL, NULL),
(1922, 1912, '762$o', 'Other item identifier', 0, NULL, NULL),
(1923, 1912, '762$s', 'Uniform title', 0, NULL, NULL),
(1924, 1912, '762$t', 'Title', 0, NULL, NULL),
(1925, 1912, '762$w', 'Record control number', 0, NULL, NULL),
(1926, 1912, '762$x', 'International Standard Serial Number', 0, NULL, NULL),
(1927, 1912, '762$y', 'CODEN designation', 0, NULL, NULL),
(1928, 1912, '762$4', 'Relationship', 0, NULL, NULL),
(1929, 1912, '762$6', 'Linkage', 0, NULL, NULL),
(1930, 1912, '762$7', 'Control subfield', 0, NULL, NULL),
(1931, 1912, '762$8', 'Field link and sequence number', 0, NULL, NULL),
(1932, 1932, '765$a', 'Main entry heading', 0, NULL, NULL),
(1933, 1932, '765$b', 'Edition', 0, NULL, NULL),
(1934, 1932, '765$c', 'Qualifying information', 0, NULL, NULL),
(1935, 1932, '765$d', 'Place, publisher, and date of publication', 0, NULL, NULL),
(1936, 1932, '765$g', 'Related parts', 0, NULL, NULL),
(1937, 1932, '765$h', 'Physical description', 0, NULL, NULL),
(1938, 1932, '765$i', 'Relationship information', 0, NULL, NULL),
(1939, 1932, '765$k', 'Series data for related item', 0, NULL, NULL),
(1940, 1932, '765$l', 'Data provenance', 0, NULL, NULL),
(1941, 1932, '765$m', 'Material-specific details', 0, NULL, NULL),
(1942, 1932, '765$n', 'Note', 0, NULL, NULL),
(1943, 1932, '765$o', 'Other item identifier', 0, NULL, NULL),
(1944, 1932, '765$r', 'Report number', 0, NULL, NULL),
(1945, 1932, '765$s', 'Uniform title', 0, NULL, NULL),
(1946, 1932, '765$t', 'Title', 0, NULL, NULL),
(1947, 1932, '765$u', 'Standard Technical Report Number', 0, NULL, NULL),
(1948, 1932, '765$w', 'Record control number', 0, NULL, NULL),
(1949, 1932, '765$x', 'International Standard Serial Number', 0, NULL, NULL),
(1950, 1932, '765$y', 'CODEN designation', 0, NULL, NULL),
(1951, 1932, '765$z', 'International Standard Book Number', 0, NULL, NULL),
(1952, 1932, '765$4', 'Relationship', 0, NULL, NULL),
(1953, 1932, '765$6', 'Linkage', 0, NULL, NULL),
(1954, 1932, '765$7', 'Control subfield', 0, NULL, NULL),
(1955, 1932, '765$8', 'Field link and sequence number', 0, NULL, NULL),
(1956, 1957, '767$a', 'Main entry heading', 0, NULL, NULL),
(1957, 1957, '767$b', 'Edition', 0, NULL, NULL),
(1958, 1957, '767$c', 'Qualifying information', 0, NULL, NULL),
(1959, 1957, '767$d', 'Place, publisher, and date of publication', 0, NULL, NULL),
(1960, 1957, '767$g', 'Related parts', 0, NULL, NULL),
(1961, 1957, '767$h', 'Physical description', 0, NULL, NULL),
(1962, 1957, '767$i', 'Relationship information', 0, NULL, NULL),
(1963, 1957, '767$k', 'Series data for related item', 0, NULL, NULL),
(1964, 1957, '767$l', 'Data provenance', 0, NULL, NULL),
(1965, 1957, '767$m', 'Material-specific details', 0, NULL, NULL),
(1966, 1957, '767$n', 'Note', 0, NULL, NULL),
(1967, 1957, '767$o', 'Other item identifier', 0, NULL, NULL),
(1968, 1957, '767$r', 'Report number', 0, NULL, NULL),
(1969, 1957, '767$s', 'Uniform title', 0, NULL, NULL),
(1970, 1957, '767$t', 'Title', 0, NULL, NULL),
(1971, 1957, '767$u', 'Standard Technical Report Number', 0, NULL, NULL),
(1972, 1957, '767$w', 'Record control number', 0, NULL, NULL),
(1973, 1957, '767$x', 'International Standard Serial Number', 0, NULL, NULL),
(1974, 1957, '767$y', 'CODEN designation', 0, NULL, NULL),
(1975, 1957, '767$z', 'International Standard Book Number', 0, NULL, NULL),
(1976, 1957, '767$4', 'Relationship', 0, NULL, NULL),
(1977, 1957, '767$6', 'Linkage', 0, NULL, NULL),
(1978, 1957, '767$7', 'Control subfield', 0, NULL, NULL),
(1979, 1957, '767$8', 'Field link and sequence number', 0, NULL, NULL),
(1980, 1980, '770$a', 'Main entry heading', 0, NULL, NULL),
(1981, 1980, '770$b', 'Edition', 0, NULL, NULL),
(1982, 1980, '770$c', 'Qualifying information', 0, NULL, NULL),
(1983, 1980, '770$d', 'Place, publisher, and date of publication', 0, NULL, NULL),
(1984, 1980, '770$g', 'Related parts', 0, NULL, NULL),
(1985, 1980, '770$h', 'Physical description', 0, NULL, NULL),
(1986, 1980, '770$i', 'Relationship information', 0, NULL, NULL),
(1987, 1980, '770$k', 'Series data for related item', 0, NULL, NULL),
(1988, 1980, '770$l', 'Data provenance', 0, NULL, NULL),
(1989, 1980, '770$m', 'Material-specific details', 0, NULL, NULL),
(1990, 1980, '770$n', 'Note', 0, NULL, NULL),
(1991, 1980, '770$o', 'Other item identifier', 0, NULL, NULL),
(1992, 1980, '770$r', 'Report number', 0, NULL, NULL),
(1993, 1980, '770$s', 'Uniform title', 0, NULL, NULL),
(1994, 1980, '770$t', 'Title', 0, NULL, NULL),
(1995, 1980, '770$u', 'Standard Technical Report Number', 0, NULL, NULL),
(1996, 1980, '770$w', 'Record control number', 0, NULL, NULL),
(1997, 1980, '770$x', 'International Standard Serial Number', 0, NULL, NULL),
(1998, 1980, '770$y', 'CODEN designation', 0, NULL, NULL),
(1999, 1980, '770$z', 'International Standard Book Number', 0, NULL, NULL),
(2000, 1980, '770$4', 'Relationship', 0, NULL, NULL),
(2001, 1980, '770$6', 'Linkage', 0, NULL, NULL),
(2002, 1980, '770$7', 'Control subfield', 0, NULL, NULL),
(2003, 1980, '770$8', 'Field link and sequence number', 0, NULL, NULL),
(2004, 2004, '772$a', 'Main entry heading', 0, NULL, NULL),
(2005, 2004, '772$b', 'Edition', 0, NULL, NULL),
(2006, 2004, '772$c', 'Qualifying information', 0, NULL, NULL),
(2007, 2004, '772$d', 'Place, publisher, and date of publication', 0, NULL, NULL),
(2008, 2004, '772$g', 'Related parts', 0, NULL, NULL),
(2009, 2004, '772$h', 'Physical description', 0, NULL, NULL),
(2010, 2004, '772$i', 'Relationship information', 0, NULL, NULL),
(2011, 2004, '772$k', 'Series data for related item', 0, NULL, NULL),
(2012, 2004, '772$l', 'Data provenance', 0, NULL, NULL),
(2013, 2004, '772$m', 'Material-specific details', 0, NULL, NULL),
(2014, 2004, '772$n', 'Note', 0, NULL, NULL),
(2015, 2004, '772$o', 'Other item identifier', 0, NULL, NULL),
(2016, 2004, '772$r', 'Report number', 0, NULL, NULL),
(2017, 2004, '772$s', 'Uniform title', 0, NULL, NULL),
(2018, 2004, '772$t', 'Title', 0, NULL, NULL),
(2019, 2004, '772$u', 'Standard Technical Report Number', 0, NULL, NULL),
(2020, 2004, '772$w', 'Record control number', 0, NULL, NULL),
(2021, 2004, '772$x', 'International Standard Serial Number', 0, NULL, NULL),
(2022, 2004, '772$y', 'CODEN designation', 0, NULL, NULL),
(2023, 2004, '772$z', 'International Standard Book Number', 0, NULL, NULL),
(2024, 2004, '772$4', 'Relationship', 0, NULL, NULL),
(2025, 2004, '772$6', 'Linkage', 0, NULL, NULL),
(2026, 2004, '772$7', 'Control subfield', 0, NULL, NULL),
(2027, 2004, '772$8', 'Field link and sequence number', 0, NULL, NULL),
(2028, 2028, '773$a', 'Main entry heading', 0, NULL, NULL),
(2029, 2028, '773$b', 'Edition', 0, NULL, NULL),
(2030, 2028, '773$d', 'Place, publisher, and date of publication', 0, NULL, NULL),
(2031, 2028, '773$g', 'Related parts', 0, NULL, NULL),
(2032, 2028, '773$h', 'Physical description', 0, NULL, NULL),
(2033, 2028, '773$i', 'Relationship information', 0, NULL, NULL),
(2034, 2028, '773$k', 'Series data for related item', 0, NULL, NULL),
(2035, 2028, '773$l', 'Data provenance', 0, NULL, NULL),
(2036, 2028, '773$m', 'Material-specific details', 0, NULL, NULL),
(2037, 2028, '773$n', 'Note', 0, NULL, NULL),
(2038, 2028, '773$o', 'Other item identifier', 0, NULL, NULL),
(2039, 2028, '773$p', 'Abbreviated title', 0, NULL, NULL),
(2040, 2028, '773$q', 'Enumeration and first page', 0, NULL, NULL),
(2041, 2028, '773$r', 'Report number', 0, NULL, NULL),
(2042, 2028, '773$s', 'Uniform title', 0, NULL, NULL),
(2043, 2028, '773$t', 'Title', 0, NULL, NULL),
(2044, 2028, '773$u', 'Standard Technical Report Number', 0, NULL, NULL),
(2045, 2028, '773$w', 'Record control number', 0, NULL, NULL),
(2046, 2028, '773$x', 'International Standard Serial Number', 0, NULL, NULL),
(2047, 2028, '773$y', 'CODEN designation', 0, NULL, NULL),
(2048, 2028, '773$z', 'International Standard Book Number', 0, NULL, NULL),
(2049, 2028, '773$4', 'Relationship', 0, NULL, NULL),
(2050, 2028, '773$6', 'Linkage', 0, NULL, NULL),
(2051, 2028, '773$7', 'Control subfield', 0, NULL, NULL),
(2052, 2028, '773$8', 'Field link and sequence number', 0, NULL, NULL),
(2053, 2053, '774$a', 'Main entry heading', 0, NULL, NULL),
(2054, 2053, '774$b', 'Edition', 0, NULL, NULL),
(2055, 2053, '774$c', 'Qualifying information', 0, NULL, NULL),
(2056, 2053, '774$d', 'Place, publisher, and date of publication', 0, NULL, NULL),
(2057, 2053, '774$g', 'Related parts', 0, NULL, NULL),
(2058, 2053, '774$h', 'Physical description', 0, NULL, NULL),
(2059, 2053, '774$i', 'Relationship information', 0, NULL, NULL),
(2060, 2053, '774$k', 'Series data for related item', 0, NULL, NULL),
(2061, 2053, '774$l', 'Data provenance', 0, NULL, NULL),
(2062, 2053, '774$m', 'Material-specific details', 0, NULL, NULL),
(2063, 2053, '774$n', 'Note', 0, NULL, NULL),
(2064, 2053, '774$o', 'Other item identifier', 0, NULL, NULL),
(2065, 2053, '774$r', 'Report number', 0, NULL, NULL),
(2066, 2053, '774$s', 'Uniform title', 0, NULL, NULL),
(2067, 2053, '774$t', 'Title', 0, NULL, NULL),
(2068, 2053, '774$u', 'Standard Technical Report Number', 0, NULL, NULL),
(2069, 2053, '774$w', 'Record control number', 0, NULL, NULL),
(2070, 2053, '774$x', 'International Standard Serial Number', 0, NULL, NULL),
(2071, 2053, '774$y', 'CODEN designation', 0, NULL, NULL),
(2072, 2053, '774$z', 'International Standard Book Number', 0, NULL, NULL),
(2073, 2053, '774$4', 'Relationship', 0, NULL, NULL),
(2074, 2053, '774$6', 'Linkage', 0, NULL, NULL),
(2075, 2053, '774$7', 'Control subfield', 0, NULL, NULL),
(2076, 2053, '774$8', 'Field link and sequence number', 0, NULL, NULL),
(2077, 2077, '775$a', 'Main entry heading', 0, NULL, NULL),
(2078, 2077, '775$b', 'Edition', 0, NULL, NULL),
(2079, 2077, '775$c', 'Qualifying information', 0, NULL, NULL),
(2080, 2077, '775$d', 'Place, publisher, and date of publication', 0, NULL, NULL),
(2081, 2077, '775$e', 'Language code', 0, NULL, NULL),
(2082, 2077, '775$f', 'Country code', 0, NULL, NULL),
(2083, 2077, '775$g', 'Related parts', 0, NULL, NULL),
(2084, 2077, '775$h', 'Physical description', 0, NULL, NULL),
(2085, 2077, '775$i', 'Relationship information', 0, NULL, NULL),
(2086, 2077, '775$k', 'Series data for related item', 0, NULL, NULL),
(2087, 2077, '775$l', 'Data provenance', 0, NULL, NULL),
(2088, 2077, '775$m', 'Material-specific details', 0, NULL, NULL),
(2089, 2077, '775$n', 'Note', 0, NULL, NULL),
(2090, 2077, '775$o', 'Other item identifier', 0, NULL, NULL),
(2091, 2077, '775$r', 'Report number', 0, NULL, NULL),
(2092, 2077, '775$s', 'Uniform title', 0, NULL, NULL),
(2093, 2077, '775$t', 'Title', 0, NULL, NULL),
(2094, 2077, '775$u', 'Standard Technical Report Number', 0, NULL, NULL),
(2095, 2077, '775$w', 'Record control number', 0, NULL, NULL),
(2096, 2077, '775$x', 'International Standard Serial Number', 0, NULL, NULL),
(2097, 2077, '775$y', 'CODEN designation', 0, NULL, NULL),
(2098, 2077, '775$z', 'International Standard Book Number', 0, NULL, NULL),
(2099, 2077, '775$4', 'Relationship', 0, NULL, NULL),
(2100, 2077, '775$6', 'Linkage', 0, NULL, NULL),
(2101, 2077, '775$7', 'Control subfield', 0, NULL, NULL),
(2102, 2077, '775$8', 'Field link and sequence number', 0, NULL, NULL),
(2103, 2103, '776$a', 'Main entry heading', 0, NULL, NULL),
(2104, 2103, '776$b', 'Edition', 0, NULL, NULL),
(2105, 2103, '776$c', 'Qualifying information', 0, NULL, NULL),
(2106, 2103, '776$d', 'Place, publisher, and date of publication', 0, NULL, NULL),
(2107, 2103, '776$g', 'Related parts', 0, NULL, NULL),
(2108, 2103, '776$h', 'Physical description', 0, NULL, NULL),
(2109, 2103, '776$i', 'Relationship information', 0, NULL, NULL),
(2110, 2103, '776$k', 'Series data for related item', 0, NULL, NULL),
(2111, 2103, '776$l', 'Data provenance', 0, NULL, NULL),
(2112, 2103, '776$m', 'Material-specific details', 0, NULL, NULL),
(2113, 2103, '776$n', 'Note', 0, NULL, NULL),
(2114, 2103, '776$o', 'Other item identifier', 0, NULL, NULL),
(2115, 2103, '776$r', 'Report number', 0, NULL, NULL),
(2116, 2103, '776$s', 'Uniform title', 0, NULL, NULL),
(2117, 2103, '776$t', 'Title', 0, NULL, NULL),
(2118, 2103, '776$u', 'Standard Technical Report Number', 0, NULL, NULL),
(2119, 2103, '776$w', 'Record control number', 0, NULL, NULL),
(2120, 2103, '776$x', 'International Standard Serial Number', 0, NULL, NULL),
(2121, 2103, '776$y', 'CODEN designation', 0, NULL, NULL),
(2122, 2103, '776$z', 'International Standard Book Number', 0, NULL, NULL),
(2123, 2103, '776$4', 'Relationship', 0, NULL, NULL),
(2124, 2103, '776$6', 'Linkage', 0, NULL, NULL),
(2125, 2103, '776$7', 'Control subfield', 0, NULL, NULL),
(2126, 2103, '776$8', 'Field link and sequence number', 0, NULL, NULL),
(2127, 2127, '777$a', 'Main entry heading', 0, NULL, NULL),
(2128, 2127, '777$b', 'Edition', 0, NULL, NULL),
(2129, 2127, '777$c', 'Qualifying information', 0, NULL, NULL),
(2130, 2127, '777$d', 'Place, publisher, and date of publication', 0, NULL, NULL),
(2131, 2127, '777$g', 'Related parts', 0, NULL, NULL),
(2132, 2127, '777$h', 'Physical description', 0, NULL, NULL),
(2133, 2127, '777$i', 'Relationship information', 0, NULL, NULL),
(2134, 2127, '777$k', 'Series data for related item', 0, NULL, NULL),
(2135, 2127, '777$l', 'Data provenance', 0, NULL, NULL),
(2136, 2127, '777$m', 'Material-specific details', 0, NULL, NULL),
(2137, 2127, '777$n', 'Note', 0, NULL, NULL),
(2138, 2127, '777$o', 'Other item identifier', 0, NULL, NULL),
(2139, 2127, '777$r', 'Report number', 0, NULL, NULL),
(2140, 2127, '777$s', 'Uniform title', 0, NULL, NULL),
(2141, 2127, '777$t', 'Title', 0, NULL, NULL),
(2142, 2127, '777$u', 'Standard Technical Report Number', 0, NULL, NULL),
(2143, 2127, '777$w', 'Record control number', 0, NULL, NULL),
(2144, 2127, '777$x', 'International Standard Serial Number', 0, NULL, NULL),
(2145, 2127, '777$y', 'CODEN designation', 0, NULL, NULL),
(2146, 2127, '777$z', 'International Standard Book Number', 0, NULL, NULL),
(2147, 2127, '777$4', 'Relationship', 0, NULL, NULL),
(2148, 2127, '777$6', 'Linkage', 0, NULL, NULL),
(2149, 2127, '777$7', 'Control subfield', 0, NULL, NULL),
(2150, 2127, '777$8', 'Field link and sequence number', 0, NULL, NULL),
(2151, 2151, '780$a', 'Main entry heading', 0, NULL, NULL),
(2152, 2151, '780$b', 'Edition', 0, NULL, NULL),
(2153, 2151, '780$c', 'Qualifying information', 0, NULL, NULL),
(2154, 2151, '780$d', 'Place, publisher, and date of publication', 0, NULL, NULL),
(2155, 2151, '780$g', 'Related parts', 0, NULL, NULL),
(2156, 2151, '780$h', 'Physical description', 0, NULL, NULL),
(2157, 2151, '780$i', 'Relationship information', 0, NULL, NULL),
(2158, 2151, '780$k', 'Series data for related item', 0, NULL, NULL),
(2159, 2151, '780$l', 'Data provenance', 0, NULL, NULL),
(2160, 2151, '780$m', 'Material-specific details', 0, NULL, NULL),
(2161, 2151, '780$n', 'Note', 0, NULL, NULL),
(2162, 2151, '780$o', 'Other item identifier', 0, NULL, NULL),
(2163, 2151, '780$r', 'Report number', 0, NULL, NULL),
(2164, 2151, '780$s', 'Uniform title', 0, NULL, NULL),
(2165, 2151, '780$t', 'Title', 0, NULL, NULL),
(2166, 2151, '780$u', 'Standard Technical Report Number', 0, NULL, NULL),
(2167, 2151, '780$w', 'Record control number', 0, NULL, NULL),
(2168, 2151, '780$x', 'International Standard Serial Number', 0, NULL, NULL),
(2169, 2151, '780$y', 'CODEN designation', 0, NULL, NULL),
(2170, 2151, '780$z', 'International Standard Book Number', 0, NULL, NULL),
(2171, 2151, '780$4', 'Relationship', 0, NULL, NULL),
(2172, 2151, '780$6', 'Linkage', 0, NULL, NULL),
(2173, 2151, '780$7', 'Control subfield', 0, NULL, NULL),
(2174, 2151, '780$8', 'Field link and sequence number', 0, NULL, NULL),
(2175, 2175, '785$a', 'Main entry heading', 0, NULL, NULL),
(2176, 2175, '785$b', 'Edition', 0, NULL, NULL),
(2177, 2175, '785$c', 'Qualifying information', 0, NULL, NULL),
(2178, 2175, '785$d', 'Place, publisher, and date of publication', 0, NULL, NULL),
(2179, 2175, '785$g', 'Related parts', 0, NULL, NULL),
(2180, 2175, '785$h', 'Physical description', 0, NULL, NULL),
(2181, 2175, '785$i', 'Relationship information', 0, NULL, NULL),
(2182, 2175, '785$k', 'Series data for related item', 0, NULL, NULL),
(2183, 2175, '785$l', 'Data provenance', 0, NULL, NULL),
(2184, 2175, '785$m', 'Material-specific details', 0, NULL, NULL),
(2185, 2175, '785$n', 'Note', 0, NULL, NULL),
(2186, 2175, '785$o', 'Other item identifier', 0, NULL, NULL),
(2187, 2175, '785$r', 'Report number', 0, NULL, NULL),
(2188, 2175, '785$s', 'Uniform title', 0, NULL, NULL),
(2189, 2175, '785$t', 'Title', 0, NULL, NULL),
(2190, 2175, '785$u', 'Standard Technical Report Number', 0, NULL, NULL),
(2191, 2175, '785$w', 'Record control number', 0, NULL, NULL),
(2192, 2175, '785$x', 'International Standard Serial Number', 0, NULL, NULL),
(2193, 2175, '785$y', 'CODEN designation', 0, NULL, NULL),
(2194, 2175, '785$z', 'International Standard Book Number', 0, NULL, NULL),
(2195, 2175, '785$4', 'Relationship', 0, NULL, NULL),
(2196, 2175, '785$6', 'Linkage', 0, NULL, NULL),
(2197, 2175, '785$7', 'Control subfield', 0, NULL, NULL),
(2198, 2175, '785$8', 'Field link and sequence number', 0, NULL, NULL),
(2199, 2199, '786$a', 'Main entry heading', 0, NULL, NULL),
(2200, 2199, '786$b', 'Edition', 0, NULL, NULL),
(2201, 2199, '786$c', 'Qualifying information', 0, NULL, NULL),
(2202, 2199, '786$d', 'Place, publisher, and date of publication', 0, NULL, NULL),
(2203, 2199, '786$g', 'Related parts', 0, NULL, NULL),
(2204, 2199, '786$h', 'Physical description', 0, NULL, NULL),
(2205, 2199, '786$i', 'Relationship information', 0, NULL, NULL),
(2206, 2199, '786$j', 'Period of content', 0, NULL, NULL),
(2207, 2199, '786$k', 'Series data for related item', 0, NULL, NULL),
(2208, 2199, '786$l', 'Data provenance', 0, NULL, NULL),
(2209, 2199, '786$m', 'Material-specific details', 0, NULL, NULL),
(2210, 2199, '786$n', 'Note', 0, NULL, NULL),
(2211, 2199, '786$o', 'Other item identifier', 0, NULL, NULL),
(2212, 2199, '786$p', 'Abbreviated title', 0, NULL, NULL),
(2213, 2199, '786$r', 'Report number', 0, NULL, NULL),
(2214, 2199, '786$s', 'Uniform title', 0, NULL, NULL),
(2215, 2199, '786$t', 'Title', 0, NULL, NULL),
(2216, 2199, '786$u', 'Standard Technical Report Number', 0, NULL, NULL),
(2217, 2199, '786$v', 'Source Contribution', 0, NULL, NULL),
(2218, 2199, '786$w', 'Record control number', 0, NULL, NULL),
(2219, 2199, '786$x', 'International Standard Serial Number', 0, NULL, NULL),
(2220, 2199, '786$y', 'CODEN designation', 0, NULL, NULL),
(2221, 2199, '786$z', 'International Standard Book Number', 0, NULL, NULL),
(2222, 2199, '786$4', 'Relationship', 0, NULL, NULL),
(2223, 2199, '786$6', 'Linkage', 0, NULL, NULL),
(2224, 2199, '786$7', 'Control subfield', 0, NULL, NULL),
(2225, 2199, '786$8', 'Field link and sequence number', 0, NULL, NULL),
(2226, 2226, '787$a', 'Main entry heading', 0, NULL, NULL),
(2227, 2226, '787$b', 'Edition', 0, NULL, NULL),
(2228, 2226, '787$c', 'Qualifying information', 0, NULL, NULL),
(2229, 2226, '787$d', 'Place, publisher, and date of publication', 0, NULL, NULL),
(2230, 2226, '787$g', 'Related parts', 0, NULL, NULL),
(2231, 2226, '787$h', 'Physical description', 0, NULL, NULL),
(2232, 2226, '787$i', 'Relationship information', 0, NULL, NULL),
(2233, 2226, '787$k', 'Series data for related item', 0, NULL, NULL),
(2234, 2226, '787$l', 'Data provenance', 0, NULL, NULL),
(2235, 2226, '787$m', 'Material-specific details', 0, NULL, NULL),
(2236, 2226, '787$n', 'Note', 0, NULL, NULL),
(2237, 2226, '787$o', 'Other item identifier', 0, NULL, NULL),
(2238, 2226, '787$r', 'Report number', 0, NULL, NULL),
(2239, 2226, '787$s', 'Uniform title', 0, NULL, NULL),
(2240, 2226, '787$t', 'Title', 0, NULL, NULL),
(2241, 2226, '787$u', 'Standard Technical Report Number', 0, NULL, NULL),
(2242, 2226, '787$w', 'Record control number', 0, NULL, NULL),
(2243, 2226, '787$x', 'International Standard Serial Number', 0, NULL, NULL),
(2244, 2226, '787$y', 'CODEN designation', 0, NULL, NULL),
(2245, 2226, '787$z', 'International Standard Book Number', 0, NULL, NULL),
(2246, 2226, '787$4', 'Relationship', 0, NULL, NULL),
(2247, 2226, '787$6', 'Linkage', 0, NULL, NULL),
(2248, 2226, '787$7', 'Control subfield', 0, NULL, NULL),
(2249, 2226, '787$8', 'Field link and sequence number', 0, NULL, NULL),
(2250, 2250, '788$a', 'Main entry heading', 0, NULL, NULL),
(2251, 2250, '788$b', 'Edition', 0, NULL, NULL),
(2252, 2250, '788$d', 'Place, publisher, and date of publication', 0, NULL, NULL),
(2253, 2250, '788$e', 'Language of cataloging', 0, NULL, NULL),
(2254, 2250, '788$i', 'Relationship information', 0, NULL, NULL),
(2255, 2250, '788$l', 'Data provenance', 0, NULL, NULL),
(2256, 2250, '788$n', 'Note', 0, NULL, NULL),
(2257, 2250, '788$s', 'Uniform title', 0, NULL, NULL),
(2258, 2250, '788$t', 'Title', 0, NULL, NULL),
(2259, 2250, '788$w', 'Record control number', 0, NULL, NULL),
(2260, 2250, '788$x', 'International Standard Serial Number', 0, NULL, NULL),
(2261, 2250, '788$4', 'Relationship', 0, NULL, NULL),
(2262, 2250, '788$5', 'Institution to which field applies', 0, NULL, NULL),
(2263, 2250, '788$6', 'Linkage', 0, NULL, NULL),
(2264, 2250, '788$8', 'Field link and sequence number', 0, NULL, NULL),
(2265, 2265, '800$a', 'Personal name', 0, NULL, NULL),
(2266, 2265, '800$b', 'Numeration ', 0, NULL, NULL),
(2267, 2265, '800$c', 'Titles and other words associated with a name', 0, NULL, NULL),
(2268, 2265, '800$d', 'Dates associated with a name', 0, NULL, NULL),
(2269, 2265, '800$e', 'Relator term', 0, NULL, NULL),
(2270, 2265, '800$f', 'Date of a work', 0, NULL, NULL),
(2271, 2265, '800$g', 'Miscellaneous information', 0, NULL, NULL),
(2272, 2265, '800$h', 'Medium', 0, NULL, NULL),
(2273, 2265, '800$j', 'Attribution qualifier', 0, NULL, NULL),
(2274, 2265, '800$k', 'Form subheading', 0, NULL, NULL),
(2275, 2265, '800$l', 'Language of a work', 0, NULL, NULL),
(2276, 2265, '800$m', 'Medium of performance for music', 0, NULL, NULL),
(2277, 2265, '800$n', 'Number of part/section of a work', 0, NULL, NULL),
(2278, 2265, '800$o', 'Arranged statement for music', 0, NULL, NULL),
(2279, 2265, '800$p', 'Name of part/section of a work', 0, NULL, NULL),
(2280, 2265, '800$q', 'Fuller form of name', 0, NULL, NULL),
(2281, 2265, '800$r', 'Key for music', 0, NULL, NULL),
(2282, 2265, '800$s', 'Version', 0, NULL, NULL),
(2283, 2265, '800$t', 'Title of a work', 0, NULL, NULL),
(2284, 2265, '800$u', 'Affiliation', 0, NULL, NULL),
(2285, 2265, '800$v', 'Volume/sequential designation', 0, NULL, NULL),
(2286, 2265, '800$w', 'Bibliographic record control number', 0, NULL, NULL),
(2287, 2265, '800$x', 'International Standard Serial Number', 0, NULL, NULL),
(2288, 2265, '800$y', 'Data provenance', 0, NULL, NULL),
(2289, 2265, '800$0', 'Authority record control number or standard number', 0, NULL, NULL),
(2290, 2265, '800$1', 'Real World Object URI', 0, NULL, NULL),
(2291, 2265, '800$2', 'Source of heading or term', 0, NULL, NULL),
(2292, 2265, '800$3', 'Materials specified', 0, NULL, NULL),
(2293, 2265, '800$4', 'Relationship', 0, NULL, NULL),
(2294, 2265, '800$5', 'Institution to which field applies', 0, NULL, NULL),
(2295, 2265, '800$6', 'Linkage', 0, NULL, NULL),
(2296, 2265, '800$7', 'Control subfield', 0, NULL, NULL),
(2297, 2265, '800$8', 'Field link and sequence number', 0, NULL, NULL),
(2298, 2298, '810$a', 'Corporate name or jurisdiction name as entry element', 0, NULL, NULL),
(2299, 2298, '810$b', 'Subordinate unit', 0, NULL, NULL),
(2300, 2298, '810$c', 'Location of meeting', 0, NULL, NULL),
(2301, 2298, '810$d', 'Date of meeting or treaty signing', 0, NULL, NULL),
(2302, 2298, '810$e', 'Relator term', 0, NULL, NULL),
(2303, 2298, '810$f', 'Date of a work', 0, NULL, NULL),
(2304, 2298, '810$g', 'Miscellaneous information', 0, NULL, NULL),
(2305, 2298, '810$h', 'Medium', 0, NULL, NULL),
(2306, 2298, '810$k', 'Form subheading', 0, NULL, NULL),
(2307, 2298, '810$l', 'Language of a work', 0, NULL, NULL),
(2308, 2298, '810$m', 'Medium of performance for music', 0, NULL, NULL),
(2309, 2298, '810$n', 'Number of part/section/meeting', 0, NULL, NULL),
(2310, 2298, '810$o', 'Arranged statement for music', 0, NULL, NULL),
(2311, 2298, '810$p', 'Name of part/section of a work', 0, NULL, NULL),
(2312, 2298, '810$r', 'Key for music', 0, NULL, NULL),
(2313, 2298, '810$s', 'Version', 0, NULL, NULL),
(2314, 2298, '810$t', 'Title of a work', 0, NULL, NULL),
(2315, 2298, '810$u', 'Affiliation', 0, NULL, NULL),
(2316, 2298, '810$v', 'Volume/sequential designation', 0, NULL, NULL),
(2317, 2298, '810$w', 'Bibliographic record control number', 0, NULL, NULL),
(2318, 2298, '810$x', 'International Standard Serial Number', 0, NULL, NULL),
(2319, 2298, '810$y', 'Data provenance', 0, NULL, NULL),
(2320, 2298, '810$0', 'Authority record control number or standard number', 0, NULL, NULL),
(2321, 2298, '810$1', 'Real World Object URI', 0, NULL, NULL),
(2322, 2298, '810$2', 'Source of heading or term', 0, NULL, NULL),
(2323, 2298, '810$3', 'Materials specified', 0, NULL, NULL),
(2324, 2298, '810$4', 'Relationship', 0, NULL, NULL),
(2325, 2298, '810$5', 'Institution to which field applies', 0, NULL, NULL),
(2326, 2298, '810$6', 'Linkage', 0, NULL, NULL),
(2327, 2298, '810$7', 'Control subfield', 0, NULL, NULL),
(2328, 2298, '810$8', 'Field link and sequence number', 0, NULL, NULL),
(2329, 2329, '811$a', 'Meeting name or jurisdiction name as entry element', 0, NULL, NULL),
(2330, 2329, '811$c', 'Location of meeting', 0, NULL, NULL),
(2331, 2329, '811$d', 'Date of meeting or treaty signing', 0, NULL, NULL),
(2332, 2329, '811$e', 'Subordinate unit', 0, NULL, NULL),
(2333, 2329, '811$f', 'Date of a work', 0, NULL, NULL),
(2334, 2329, '811$g', 'Miscellaneous information', 0, NULL, NULL),
(2335, 2329, '811$h', 'Medium', 0, NULL, NULL),
(2336, 2329, '811$j', 'Relator term', 0, NULL, NULL),
(2337, 2329, '811$k', 'Form subheading', 0, NULL, NULL),
(2338, 2329, '811$l', 'Language of a work', 0, NULL, NULL),
(2339, 2329, '811$n', 'Number of part/section/meeting', 0, NULL, NULL),
(2340, 2329, '811$p', 'Name of part/section of a work', 0, NULL, NULL),
(2341, 2329, '811$q', 'Name of meeting following jurisdiction name entry element', 0, NULL, NULL),
(2342, 2329, '811$s', 'Version', 0, NULL, NULL),
(2343, 2329, '811$t', 'Title of a work', 0, NULL, NULL),
(2344, 2329, '811$u', 'Affiliation', 0, NULL, NULL),
(2345, 2329, '811$v', 'Volume/sequential designation', 0, NULL, NULL),
(2346, 2329, '811$w', 'Bibliographic record control number', 0, NULL, NULL),
(2347, 2329, '811$x', 'International Standard Serial Number', 0, NULL, NULL),
(2348, 2329, '811$y', 'Data provenance', 0, NULL, NULL),
(2349, 2329, '811$0', 'Authority record control number or standard number', 0, NULL, NULL),
(2350, 2329, '811$1', 'Real World Object URI', 0, NULL, NULL),
(2351, 2329, '811$2', 'Source of heading or term', 0, NULL, NULL),
(2352, 2329, '811$3', 'Materials specified', 0, NULL, NULL),
(2353, 2329, '811$4', 'Relationship', 0, NULL, NULL),
(2354, 2329, '811$5', 'Institution to which field applies', 0, NULL, NULL),
(2355, 2329, '811$6', 'Linkage', 0, NULL, NULL),
(2356, 2329, '811$7', 'Control subfield', 0, NULL, NULL),
(2357, 2329, '811$8', 'Field link and sequence number', 0, NULL, NULL),
(2358, 2358, '830$a', 'Uniform title', 0, NULL, NULL),
(2359, 2358, '830$d', 'Date of treaty signing', 0, NULL, NULL),
(2360, 2358, '830$f', 'Date of a work', 0, NULL, NULL),
(2361, 2358, '830$g', 'Miscellaneous information', 0, NULL, NULL),
(2362, 2358, '830$h', 'Medium', 0, NULL, NULL),
(2363, 2358, '830$k', 'Form subheading', 0, NULL, NULL),
(2364, 2358, '830$l', 'Language of a work', 0, NULL, NULL),
(2365, 2358, '830$m', 'Medium of performance for music', 0, NULL, NULL),
(2366, 2358, '830$n', 'Number of part/section of a work', 0, NULL, NULL),
(2367, 2358, '830$o', 'Arranged statement for music', 0, NULL, NULL),
(2368, 2358, '830$p', 'Name of part/section of a work', 0, NULL, NULL),
(2369, 2358, '830$r', 'Key for music', 0, NULL, NULL),
(2370, 2358, '830$s', 'Version', 0, NULL, NULL),
(2371, 2358, '830$t', 'Title of a work', 0, NULL, NULL),
(2372, 2358, '830$v', 'Volume/sequential designation', 0, NULL, NULL),
(2373, 2358, '830$w', 'Bibliographic record control number', 0, NULL, NULL),
(2374, 2358, '830$x', 'International Standard Serial Number', 0, NULL, NULL),
(2375, 2358, '830$y', 'Data provenance', 0, NULL, NULL),
(2376, 2358, '830$0', 'Authority record control number or standard number', 0, NULL, NULL),
(2377, 2358, '830$1', 'Real World Object URI', 0, NULL, NULL),
(2378, 2358, '830$2', 'Source of heading or term', 0, NULL, NULL),
(2379, 2358, '830$3', 'Materials specified', 0, NULL, NULL),
(2380, 2358, '830$5', 'Institution to which field applies', 0, NULL, NULL),
(2381, 2358, '830$6', 'Linkage', 0, NULL, NULL),
(2382, 2358, '830$7', 'Control subfield', 0, NULL, NULL),
(2383, 2358, '830$8', 'Field link and sequence number', 0, NULL, NULL),
(2384, 2384, '850$a', 'Holding institution', 0, NULL, NULL),
(2385, 2384, '850$8', 'Field link and sequence number', 0, NULL, NULL),
(2386, 2386, '852$a', 'Location', 0, NULL, NULL),
(2387, 2386, '852$b', 'Sublocation or collection', 0, NULL, NULL),
(2388, 2386, '852$c', 'Shelving location', 0, NULL, NULL),
(2389, 2386, '852$d', 'Former shelving location', 0, NULL, NULL),
(2390, 2386, '852$e', 'Address', 0, NULL, NULL),
(2391, 2386, '852$f', 'Coded location qualifier', 0, NULL, NULL),
(2392, 2386, '852$g', 'Non-coded location qualifier', 0, NULL, NULL),
(2393, 2386, '852$h', 'Classification part', 0, NULL, NULL),
(2394, 2386, '852$i', 'Item part', 0, NULL, NULL),
(2395, 2386, '852$j', 'Shelving control number', 0, NULL, NULL),
(2396, 2386, '852$k', 'Call number prefix', 0, NULL, NULL),
(2397, 2386, '852$l', 'Shelving form of title', 0, NULL, NULL),
(2398, 2386, '852$m', 'Call number suffix', 0, NULL, NULL),
(2399, 2386, '852$n', 'Country code', 0, NULL, NULL),
(2400, 2386, '852$p', 'Piece designation', 0, NULL, NULL),
(2401, 2386, '852$q', 'Piece physical condition', 0, NULL, NULL),
(2402, 2386, '852$s', 'Copyright article-fee code', 0, NULL, NULL),
(2403, 2386, '852$t', 'Copy number', 0, NULL, NULL),
(2404, 2386, '852$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(2405, 2386, '852$x', 'Nonpublic note', 0, NULL, NULL),
(2406, 2386, '852$z', 'Public note', 0, NULL, NULL),
(2407, 2386, '852$2', 'Source of classification or shelving scheme', 0, NULL, NULL),
(2408, 2386, '852$3', 'Materials specified', 0, NULL, NULL),
(2409, 2386, '852$6', 'Linkage', 0, NULL, NULL),
(2410, 2386, '852$8', 'Field link and sequence number', 0, NULL, NULL),
(2411, 2411, '856$a', 'Host name', 0, NULL, NULL),
(2412, 2411, '856$c', 'Compression information', 0, NULL, NULL),
(2413, 2411, '856$d', 'Path', 0, NULL, NULL),
(2414, 2411, '856$e', 'Data provenance', 0, NULL, NULL),
(2415, 2411, '856$f', 'Electronic name', 0, NULL, NULL),
(2416, 2411, '856$g', 'Persistent identifier', 0, NULL, NULL),
(2417, 2411, '856$h', 'Non-functioning Uniform Resource Identifier', 0, NULL, NULL),
(2418, 2411, '856$l', 'Standardized information governing access', 0, NULL, NULL),
(2419, 2411, '856$m', 'Contact for access assistance', 0, NULL, NULL),
(2420, 2411, '856$n', 'Terms governing access', 0, NULL, NULL),
(2421, 2411, '856$o', 'Operating system', 0, NULL, NULL),
(2422, 2411, '856$p', 'Port', 0, NULL, NULL),
(2423, 2411, '856$q', 'Electronic format type', 0, NULL, NULL),
(2424, 2411, '856$r', 'Standardized information governing use and reproduction', 0, NULL, NULL),
(2425, 2411, '856$s', 'File size', 0, NULL, NULL),
(2426, 2411, '856$t', 'Terms governing use and reproduction', 0, NULL, NULL),
(2427, 2411, '856$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(2428, 2411, '856$v', 'Hours access method available', 0, NULL, NULL),
(2429, 2411, '856$w', 'Record control number', 0, NULL, NULL),
(2430, 2411, '856$x', 'Nonpublic note', 0, NULL, NULL),
(2431, 2411, '856$y', 'Link text', 0, NULL, NULL),
(2432, 2411, '856$z', 'Public note', 0, NULL, NULL),
(2433, 2411, '856$2', 'Access method', 0, NULL, NULL),
(2434, 2411, '856$3', 'Materials specified', 0, NULL, NULL),
(2435, 2411, '856$6', 'Linkage', 0, NULL, NULL),
(2436, 2411, '856$7', 'Access status', 0, NULL, NULL),
(2437, 2411, '856$8', 'Field link and sequence number', 0, NULL, NULL),
(2438, 2438, '881$a', 'Manifestation statement, high-level/general', 0, NULL, NULL),
(2439, 2438, '881$b', 'Manifestation identifier statement', 0, NULL, NULL),
(2440, 2438, '881$c', 'Manifestation title and responsibility statement', 0, NULL, NULL),
(2441, 2438, '881$d', 'Manifestation edition statement', 0, NULL, NULL),
(2442, 2438, '881$e', 'Manifestation production statement', 0, NULL, NULL),
(2443, 2438, '881$f', 'Manifestation publication statement', 0, NULL, NULL),
(2444, 2438, '881$g', 'Manifestation distribution statement', 0, NULL, NULL),
(2445, 2438, '881$h', 'Manifestation manufacture statement', 0, NULL, NULL),
(2446, 2438, '881$i', 'Manifestation copyright statement', 0, NULL, NULL),
(2447, 2438, '881$j', 'Manifestation frequency statement', 0, NULL, NULL),
(2448, 2438, '881$k', 'Manifestation designation of sequence statement', 0, NULL, NULL),
(2449, 2438, '881$l', 'Manifestation series statement', 0, NULL, NULL),
(2450, 2438, '881$m', 'Manifestation dissertation statement', 0, NULL, NULL),
(2451, 2438, '881$n', 'Manifestation regional encoding statement', 0, NULL, NULL),
(2452, 2438, '881$3', 'Materials specified', 0, NULL, NULL),
(2453, 2438, '881$6', 'Linkage', 0, NULL, NULL),
(2454, 2438, '881$8', 'Field link and sequence number', 0, NULL, NULL),
(2455, 2455, '882$a', 'Replacement title', 0, NULL, NULL),
(2456, 2455, '882$i', 'Explanatory text', 0, NULL, NULL),
(2457, 2455, '882$w', 'Replacement bibliographic record control number', 0, NULL, NULL),
(2458, 2455, '882$6', 'Linkage', 0, NULL, NULL),
(2459, 2455, '882$8', 'Field link and sequence number', 0, NULL, NULL),
(2460, 2460, '883$a', 'Creation process', 0, NULL, NULL),
(2461, 2460, '883$c', 'Confidence value', 0, NULL, NULL),
(2462, 2460, '883$d', 'Creation date', 0, NULL, NULL),
(2463, 2460, '883$q', 'Assigning or generating agency', 0, NULL, NULL),
(2464, 2460, '883$x', 'Validity end date ', 0, NULL, NULL),
(2465, 2460, '883$u', 'Uniform Resource Identifier', 0, NULL, NULL);
INSERT INTO `marctag_subfields_loc` (`id`, `marctags_loc_id`, `code`, `description`, `repeatable`, `created_at`, `updated_at`) VALUES
(2466, 2460, '883$w', 'Bibliographic record control number', 0, NULL, NULL),
(2467, 2460, '883$0', 'Authority record control number or standard number', 0, NULL, NULL),
(2468, 2460, '883$1', 'Real World Object URI', 0, NULL, NULL),
(2469, 2460, '883$8', 'Field link and sequence number', 0, NULL, NULL),
(2470, 2470, '884$a', 'Conversion process', 0, NULL, NULL),
(2471, 2470, '884$g', 'Conversion date', 0, NULL, NULL),
(2472, 2470, '884$k', 'Identifier of source metadata', 0, NULL, NULL),
(2473, 2470, '884$q', 'Conversion agency', 0, NULL, NULL),
(2474, 2470, '884$u', 'Uniform Resource Identifier', 0, NULL, NULL),
(2475, 2475, '885$a', 'Matching information', 0, NULL, NULL),
(2476, 2475, '885$b', 'Status of matching and its checking', 0, NULL, NULL),
(2477, 2475, '885$c', 'Confidence value', 0, NULL, NULL),
(2478, 2475, '885$d', 'Generation date', 0, NULL, NULL),
(2479, 2475, '885$w', 'Record control number', 0, NULL, NULL),
(2480, 2475, '885$x', 'Nonpublic note', 0, NULL, NULL),
(2481, 2475, '885$z', 'Public note', 0, NULL, NULL),
(2482, 2475, '885$0', 'Authority record control number or standard number', 0, NULL, NULL),
(2483, 2475, '885$1', 'Real World Object URI', 0, NULL, NULL),
(2484, 2475, '885$2', 'Source', 0, NULL, NULL),
(2485, 2475, '885$5', 'Institution to which field applies', 0, NULL, NULL),
(2486, 2486, '886$a', 'Tag of the foreign MARC field', 0, NULL, NULL),
(2487, 2486, '886$b', 'Content of the foreign MARC field', 0, NULL, NULL),
(2488, 2486, '886$2', 'Source of data', 0, NULL, NULL),
(2489, 2489, '887$a', 'Content of non-MARC field', 0, NULL, NULL),
(2490, 2489, '887$2', 'Source of data', 0, NULL, NULL),
(2491, 2412, '857$b', 'Name of archiving agency', 0, NULL, NULL),
(2492, 2412, '857$c', 'Name of Web archive or digital archive repository', 0, NULL, NULL),
(2493, 2412, '857$d', 'Date range of archived material', 0, NULL, NULL),
(2494, 2412, '857$e', 'Data provenance', 1, NULL, NULL),
(2495, 2412, '857$f', 'Archive completeness', 0, NULL, NULL),
(2496, 2412, '857$g', 'Persistent identifier', 1, NULL, NULL),
(2497, 2412, '857$h', 'Non-functioning Uniform Resource Identifier', 1, NULL, NULL),
(2498, 2412, '857$l', 'Standardized information governing access', 1, NULL, NULL),
(2499, 2412, '857$m', 'Contact for access assistance', 1, NULL, NULL),
(2500, 2412, '857$n', 'Terms governing access', 1, NULL, NULL),
(2501, 2412, '857$q', 'Electronic format type', 1, NULL, NULL),
(2502, 2412, '857$r', 'Standardized information governing use and reproduction', 1, NULL, NULL),
(2503, 2412, '857$s', 'File size', 1, NULL, NULL),
(2504, 2412, '857$t', 'Terms governing use and reproduction', 1, NULL, NULL),
(2505, 2412, '857$u', 'Uniform Resource Identifier', 1, NULL, NULL),
(2506, 2412, '857$x', 'Nonpublic note', 1, NULL, NULL),
(2507, 2412, '857$y', 'Link text', 1, NULL, NULL),
(2508, 2412, '857$z', 'Public note', 1, NULL, NULL),
(2509, 2412, '857$2', 'Access method', 0, NULL, NULL),
(2510, 2412, '857$3', 'Materials specified', 0, NULL, NULL),
(2511, 2412, '857$5', 'Institution to which field applies', 0, NULL, NULL),
(2512, 2412, '857$6', 'Linkage ', 0, NULL, NULL),
(2513, 2412, '857$7', 'Access status', 0, NULL, NULL),
(2514, 2412, '857$8', 'Field link and sequence number', 1, NULL, NULL),
(2516, 2413, '880$2', '2', 0, NULL, NULL),
(2517, 2413, '880$3', '3', 0, NULL, NULL),
(2518, 2413, '880$4', '4', 0, NULL, NULL),
(2519, 2413, '880$5', '5', 0, NULL, NULL),
(2520, 2413, '880$6', 'Linkage ', 0, NULL, NULL),
(2521, 2413, '880$7', '7', 0, NULL, NULL),
(2522, 2413, '880$8', '8', 0, NULL, NULL),
(2523, 2413, '880$9', '9', 0, NULL, NULL),
(2524, 2413, '880$a', 'a', 0, NULL, NULL),
(2525, 2413, '880$b', 'b', 0, NULL, NULL),
(2526, 2413, '880$c', 'c', 0, NULL, NULL),
(2527, 2413, '880$d', 'd', 0, NULL, NULL),
(2528, 2413, '880$e', 'e', 0, NULL, NULL),
(2529, 2413, '880$f', 'f', 0, NULL, NULL),
(2530, 2413, '880$g', 'g', 0, NULL, NULL),
(2531, 2413, '880$h', 'h', 0, NULL, NULL),
(2532, 2413, '880$i', 'i', 0, NULL, NULL),
(2533, 2413, '880$j', 'j', 0, NULL, NULL),
(2534, 2413, '880$k', 'k', 0, NULL, NULL),
(2535, 2413, '880$l', 'l', 0, NULL, NULL),
(2536, 2413, '880$m', 'm', 0, NULL, NULL),
(2537, 2413, '880$n', 'n', 0, NULL, NULL),
(2538, 2413, '880$o', 'o', 0, NULL, NULL),
(2539, 2413, '880$p', 'p', 0, NULL, NULL),
(2540, 2413, '880$q', 'q', 0, NULL, NULL),
(2541, 2413, '880$r', 'r', 0, NULL, NULL),
(2542, 2413, '880$s', 's', 0, NULL, NULL),
(2543, 2413, '880$t', 't', 0, NULL, NULL),
(2544, 2413, '880$u', 'u', 0, NULL, NULL),
(2545, 2413, '880$v', 'v', 0, NULL, NULL),
(2546, 2413, '880$w', 'w', 0, NULL, NULL),
(2547, 2413, '880$x', 'x', 0, NULL, NULL),
(2548, 2413, '880$y', 'y', 0, NULL, NULL),
(2549, 2413, '880$z', 'z', 0, NULL, NULL),
(2550, 2396, '876$a', 'Internal item number', 0, NULL, NULL),
(2551, 2396, '876$b', 'Invalid or canceled internal item number', 1, NULL, NULL),
(2552, 2396, '876$c', 'Cost', 1, NULL, NULL),
(2553, 2396, '876$d', 'Date acquired', 1, NULL, NULL),
(2554, 2396, '876$e', 'Source of acquisition', 1, NULL, NULL),
(2555, 2396, '876$h', 'Use restrictions', 1, NULL, NULL),
(2556, 2396, '876$j', 'Item status', 1, NULL, NULL),
(2557, 2396, '876$l', 'Temporary location', 1, NULL, NULL),
(2558, 2396, '876$p', 'Piece designation', 1, NULL, NULL),
(2559, 2396, '876$r', 'Invalid or canceled piece designation', 1, NULL, NULL),
(2560, 2396, '876$t', 'Copy number', 0, NULL, NULL),
(2561, 2396, '876$x', 'Nonpublic note', 1, NULL, NULL),
(2562, 2396, '876$z', 'Public note', 1, NULL, NULL),
(2563, 2396, '876$3', 'Materials specified', 0, NULL, NULL),
(2564, 2396, '876$6', 'Linkage', 0, NULL, NULL),
(2565, 2396, '876$8', 'Field link and sequence number', 0, NULL, NULL),
(2566, 2499, '901$0', '0', 0, NULL, NULL),
(2567, 2499, '901$1', '1', 0, NULL, NULL),
(2568, 2499, '901$2', '2', 0, NULL, NULL),
(2569, 2499, '901$3', '3', 0, NULL, NULL),
(2570, 2499, '901$4', '4', 0, NULL, NULL),
(2571, 2499, '901$5', '5', 0, NULL, NULL),
(2572, 2499, '901$6', '6', 0, NULL, NULL),
(2573, 2499, '901$7', '7', 0, NULL, NULL),
(2574, 2499, '901$8', '8', 0, NULL, NULL),
(2575, 2499, '901$9', '9', 0, NULL, NULL),
(2577, 2499, '901$a', 'a', 0, NULL, NULL),
(2578, 2499, '901$b', 'b', 0, NULL, NULL),
(2579, 2499, '901$c', 'c', 0, NULL, NULL),
(2580, 2499, '901$d', 'd', 0, NULL, NULL),
(2581, 2499, '901$e', 'e', 0, NULL, NULL),
(2582, 2499, '901$f', 'f', 0, NULL, NULL),
(2583, 2499, '901$g', 'g', 0, NULL, NULL),
(2584, 2499, '901$h', 'h', 0, NULL, NULL),
(2585, 2499, '901$i', 'i', 0, NULL, NULL),
(2586, 2499, '901$j', 'j', 0, NULL, NULL),
(2587, 2499, '901$k', 'k', 0, NULL, NULL),
(2588, 2499, '901$l', 'l', 0, NULL, NULL),
(2589, 2499, '901$m', 'm', 0, NULL, NULL),
(2590, 2499, '901$n', 'n', 0, NULL, NULL),
(2591, 2499, '901$o', 'o', 0, NULL, NULL),
(2592, 2499, '901$p', 'p', 0, NULL, NULL),
(2593, 2499, '901$q', 'q', 0, NULL, NULL),
(2594, 2499, '901$r', 'r', 0, NULL, NULL),
(2595, 2499, '901$s', 's', 0, NULL, NULL),
(2596, 2499, '901$t', 't', 0, NULL, NULL),
(2597, 2499, '901$u', 'u', 0, NULL, NULL),
(2598, 2499, '901$v', 'v', 0, NULL, NULL),
(2599, 2499, '901$w', 'w', 0, NULL, NULL),
(2600, 2499, '901$x', 'x', 0, NULL, NULL),
(2601, 2499, '901$y', 'y', 0, NULL, NULL),
(2602, 2499, '901$z', 'z', 0, NULL, NULL),
(2603, 6, '008$00-05', 'Date entered on file', 0, NULL, NULL),
(2604, 6, '008$06', 'Type of date/Publication status', 0, NULL, NULL),
(2605, 6, '008$07-10', 'Date 1', 0, NULL, NULL),
(2606, 6, '008$11-14', 'Date2', 0, NULL, NULL),
(2607, 6, '008$15-17', 'Place of publication, production, or execution', 0, NULL, NULL),
(2608, 6, '008$18-34', 'Material specific coded elements', 0, NULL, NULL),
(2609, 6, '008$35-37', 'Language', 0, NULL, NULL),
(2610, 6, '008$38', 'Modified record', 0, NULL, NULL),
(2611, 6, '008$39', 'Cataloging source', 0, NULL, NULL),
(2612, 3, '005$#', 'Date and Time', 0, NULL, NULL),
(2613, 1, '001$#', 'Control Number', 0, NULL, NULL),
(2614, 2, '003$#', 'Control Number Identifier', 0, NULL, NULL),
(2615, 4, '006$#', 'Fixed-Length Data ', 0, NULL, NULL),
(2616, 5, '007$#', 'Physical Description Fixed Field', 0, NULL, NULL),
(2617, 2501, 'LDR$00-04', 'Record length', 0, NULL, NULL),
(2618, 2501, 'LDR$05', 'Record status', 0, NULL, NULL),
(2619, 2501, 'LDR$06', 'Type of record', 0, NULL, NULL),
(2620, 2501, 'LDR$07', 'Bibliographic level', 0, NULL, NULL),
(2621, 2501, 'LDR$08', 'Type of control', 0, NULL, NULL),
(2622, 2501, 'LDR$09', 'Character coding scheme', 0, NULL, NULL),
(2623, 2501, 'LDR$10', 'Indicator count', 0, NULL, NULL),
(2624, 2501, 'LDR$11', 'Subfield code count', 0, NULL, NULL),
(2625, 2501, 'LDR$12-16', 'Base address of data', 0, NULL, NULL),
(2626, 2501, 'LDR$17', 'Encoding level', 0, NULL, NULL),
(2627, 2501, 'LDR$18', 'Descriptive cataloging form', 0, NULL, NULL),
(2628, 2501, 'LDR$19', 'Multipart resource record level', 0, NULL, NULL),
(2629, 2501, 'LDR$20', 'Length of the length-of-field portion', 0, NULL, NULL),
(2630, 2501, 'LDR$21', 'Length of the starting-character-position portion', 0, NULL, NULL),
(2631, 2501, 'LDR$22', 'Length of the implementation-defined portion', 0, NULL, NULL),
(2632, 2501, 'LDR$23', 'Undefined', 0, NULL, NULL),
(2633, 232, '050$d', 'Volumes/dates to which call number applies', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `marc_class_sys`
--

CREATE TABLE `marc_class_sys` (
  `id` int(11) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marc_class_sys`
--

INSERT INTO `marc_class_sys` (`id`, `code`, `name`, `created_at`, `updated_at`) VALUES
(1, 'LOC', 'Library of Congress', '2023-07-22 07:06:23', '2023-07-22 07:06:24'),
(2, 'DDC', 'Dewey Decimal Classification', '2023-07-22 07:07:08', '2023-07-22 07:07:09');

-- --------------------------------------------------------

--
-- Table structure for table `marc_templates`
--

CREATE TABLE `marc_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `fields` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marc_templates`
--

INSERT INTO `marc_templates` (`id`, `name`, `fields`, `created_at`, `updated_at`) VALUES
(6, 'BOOKS_LC_PCAARRD', '[{\"id\":40,\"subfield\":[\"40\"],\"default\":[],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":160,\"subfield\":[null,\"160\",null],\"default\":[null,null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":232,\"subfield\":[\"232\",\"233\",null],\"default\":[null,null,null],\"indicator\":[\"58\",\"61\"],\"required\":0},{\"id\":349,\"subfield\":[\"349\"],\"default\":[],\"indicator\":[\"0\",\"1\"],\"required\":0},{\"id\":393,\"subfield\":[\"393\"],\"default\":[],\"indicator\":[\"97\",\"1\"],\"required\":0},{\"id\":507,\"subfield\":[\"507\",\"508\",\"509\"],\"default\":[null,null,null],\"indicator\":[\"108\",\"0\"],\"required\":0},{\"id\":543,\"subfield\":[\"543\"],\"default\":[],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":623,\"subfield\":[\"623\",\"624\",\"625\",\"626\",null],\"default\":[null,null,null,null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":670,\"subfield\":[\"670\",\"671\"],\"default\":[null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":679,\"subfield\":[\"679\",\"680\"],\"default\":[null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":687,\"subfield\":[\"687\",\"688\"],\"default\":[null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":1043,\"subfield\":[\"1043\"],\"default\":[],\"indicator\":[\"187\",\"1\"],\"required\":0},{\"id\":1053,\"subfield\":[\"1053\"],\"default\":[],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":1073,\"subfield\":[\"1073\"],\"default\":[],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":1077,\"subfield\":[\"1077\"],\"default\":[],\"indicator\":[\"188\",\"191\"],\"required\":0},{\"id\":1157,\"subfield\":[\"1157\",\"1161\"],\"default\":[null,null],\"indicator\":[\"203\",\"1\"],\"required\":0},{\"id\":1596,\"subfield\":[\"1596\",null,null],\"default\":[null,null,null],\"indicator\":[\"139\",\"237\"],\"required\":0},{\"id\":1716,\"subfield\":[\"1716\"],\"default\":[],\"indicator\":[\"92\",\"16\"],\"required\":0},{\"id\":2386,\"subfield\":[null,\"2386\",null],\"default\":[null,null,null],\"indicator\":[\"16\",\"16\"],\"required\":0},{\"id\":167,\"subfield\":[\"167\"],\"default\":[],\"indicator\":[\"16\",\"3\"],\"required\":0},{\"id\":2411,\"subfield\":[\"2427\"],\"default\":[],\"indicator\":[\"302\",\"16\"],\"required\":0},{\"id\":2396,\"subfield\":[\"2550\",\"2552\",\"2553\",\"2554\",\"2555\",\"2556\",\"2558\",\"2560\"],\"default\":[null,null,null,null,null,null,null,null],\"indicator\":[\"0\",\"0\"],\"required\":1},{\"id\":583,\"subfield\":[\"583\",\"584\",\"585\"],\"default\":[null,null,null],\"indicator\":[\"43\",\"1\"],\"required\":0},{\"id\":1716,\"subfield\":[],\"default\":[],\"indicator\":[\"0\",\"0\"],\"required\":0}]', '2024-01-25 06:31:10', '2024-02-01 07:56:06'),
(7, 'CONTINUING RESOURCES_LC_PCAARRD', '[{\"id\":46,\"subfield\":[\"46\"],\"default\":[],\"indicator\":[\"6\",\"1\"],\"required\":0},{\"id\":167,\"subfield\":[\"167\"],\"default\":[],\"indicator\":[\"16\",\"3\"],\"required\":0},{\"id\":232,\"subfield\":[\"232\",\"233\",null],\"default\":[null,null,null],\"indicator\":[\"58\",\"61\"],\"required\":0},{\"id\":349,\"subfield\":[\"349\"],\"default\":[],\"indicator\":[\"92\",\"1\"],\"required\":0},{\"id\":393,\"subfield\":[\"393\"],\"default\":[],\"indicator\":[\"97\",\"1\"],\"required\":0},{\"id\":507,\"subfield\":[\"507\",\"508\",\"509\"],\"default\":[null,null,null],\"indicator\":[\"108\",\"0\"],\"required\":0},{\"id\":623,\"subfield\":[\"623\",\"624\",\"625\",\"626\"],\"default\":[null,null,null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":640,\"subfield\":[\"640\"],\"default\":[],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":647,\"subfield\":[],\"default\":[],\"indicator\":[\"0\",\"0\"],\"required\":0},{\"id\":670,\"subfield\":[\"670\",\"671\"],\"default\":[null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":679,\"subfield\":[\"679\",\"680\"],\"default\":[null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":687,\"subfield\":[\"687\",\"688\"],\"default\":[null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":864,\"subfield\":[\"863\"],\"default\":[],\"indicator\":[\"166\",\"1\"],\"required\":0},{\"id\":1053,\"subfield\":[\"1053\"],\"default\":[],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":1073,\"subfield\":[\"1073\"],\"default\":[],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":1077,\"subfield\":[\"1077\"],\"default\":[],\"indicator\":[\"188\",\"191\"],\"required\":0},{\"id\":1157,\"subfield\":[\"1157\"],\"default\":[],\"indicator\":[\"203\",\"1\"],\"required\":0},{\"id\":1331,\"subfield\":[\"1331\"],\"default\":[],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":1596,\"subfield\":[\"1596\"],\"default\":[],\"indicator\":[\"139\",\"237\"],\"required\":0},{\"id\":2386,\"subfield\":[\"2386\"],\"default\":[],\"indicator\":[\"16\",\"16\"],\"required\":0},{\"id\":2396,\"subfield\":[\"2550\",\"2552\",\"2553\",\"2554\",\"2555\",\"2556\",\"2558\",\"2560\"],\"default\":[null,null,null,null,null,null,null,null,null],\"indicator\":[\"0\",\"0\"],\"required\":1},{\"id\":2411,\"subfield\":[\"2427\"],\"default\":[],\"indicator\":[\"302\",\"16\"],\"required\":0},{\"id\":583,\"subfield\":[\"583\",\"584\",\"585\"],\"default\":[null,null,null],\"indicator\":[\"43\",\"1\"],\"required\":0},{\"id\":1716,\"subfield\":[\"1716\"],\"default\":[],\"indicator\":[\"92\",\"16\"],\"required\":0}]', '2024-01-26 00:41:50', '2024-02-01 07:56:54'),
(8, 'THESIS / DISSERTATION_LC_PCAARRD', '[{\"id\":167,\"subfield\":[\"167\"],\"default\":[],\"indicator\":[\"16\",\"0\"],\"required\":0},{\"id\":232,\"subfield\":[\"232\",\"233\",null],\"default\":[null,null,null],\"indicator\":[\"58\",\"60\"],\"required\":0},{\"id\":349,\"subfield\":[\"349\"],\"default\":[],\"indicator\":[\"92\",\"1\"],\"required\":0},{\"id\":507,\"subfield\":[\"507\",\"508\",\"509\"],\"default\":[null,null,null],\"indicator\":[\"108\",\"0\"],\"required\":1},{\"id\":623,\"subfield\":[\"623\",\"624\",\"625\",\"626\"],\"default\":[null,null,null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":670,\"subfield\":[\"670\",\"671\"],\"default\":[null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":679,\"subfield\":[\"679\",\"680\"],\"default\":[null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":687,\"subfield\":[\"687\",\"688\"],\"default\":[null,null],\"indicator\":[\"0\",\"0\"],\"required\":0},{\"id\":1053,\"subfield\":[\"1053\"],\"default\":[],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":1064,\"subfield\":[\"1064\",\"1065\",\"1066\",\"1067\"],\"default\":[null,null,null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":1073,\"subfield\":[\"1073\"],\"default\":[],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":1077,\"subfield\":[\"1077\"],\"default\":[],\"indicator\":[\"188\",\"191\"],\"required\":0},{\"id\":1157,\"subfield\":[\"1157\"],\"default\":[],\"indicator\":[\"207\",\"1\"],\"required\":0},{\"id\":1596,\"subfield\":[\"1596\"],\"default\":[],\"indicator\":[\"16\",\"237\"],\"required\":0},{\"id\":2386,\"subfield\":[\"2386\"],\"default\":[],\"indicator\":[\"16\",\"16\"],\"required\":0},{\"id\":2411,\"subfield\":[\"2427\"],\"default\":[],\"indicator\":[\"302\",\"16\"],\"required\":0},{\"id\":2396,\"subfield\":[\"2550\",\"2552\",\"2553\",\"2554\",\"2556\",\"2558\",\"2560\",\"2561\"],\"default\":[null,null,null,null,null,null,null,null],\"indicator\":[\"0\",\"0\"],\"required\":1},{\"id\":1716,\"subfield\":[\"1716\"],\"default\":[],\"indicator\":[\"92\",\"16\"],\"required\":0},{\"id\":583,\"subfield\":[\"583\",\"584\",\"585\"],\"default\":[null,null,null],\"indicator\":[\"43\",\"1\"],\"required\":0},{\"id\":1716,\"subfield\":[],\"default\":[],\"indicator\":[\"0\",\"0\"],\"required\":0}]', '2024-01-26 06:08:36', '2024-02-01 08:01:26'),
(9, 'TECHNICAL / TERMINAL REPORTS_LC_PCAARRD', '[{\"id\":167,\"subfield\":[\"167\"],\"default\":[],\"indicator\":[\"16\",\"0\"],\"required\":0},{\"id\":232,\"subfield\":[\"232\",\"233\"],\"default\":[null,null],\"indicator\":[\"58\",\"61\"],\"required\":0},{\"id\":349,\"subfield\":[\"349\"],\"default\":[],\"indicator\":[\"92\",\"1\"],\"required\":0},{\"id\":507,\"subfield\":[\"507\",\"508\",\"509\"],\"default\":[null,null,null],\"indicator\":[\"108\",\"0\"],\"required\":0},{\"id\":623,\"subfield\":[\"623\",\"624\",\"625\",\"626\",null,null],\"default\":[null,null,null,null,null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":670,\"subfield\":[\"670\",\"671\"],\"default\":[null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":679,\"subfield\":[\"679\",\"680\"],\"default\":[null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":687,\"subfield\":[\"687\",\"688\"],\"default\":[null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":1053,\"subfield\":[\"1053\"],\"default\":[],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":1157,\"subfield\":[\"1157\"],\"default\":[],\"indicator\":[\"203\",\"1\"],\"required\":0},{\"id\":1596,\"subfield\":[\"1596\"],\"default\":[],\"indicator\":[\"16\",\"237\"],\"required\":0},{\"id\":2386,\"subfield\":[\"2386\"],\"default\":[],\"indicator\":[\"16\",\"16\"],\"required\":0},{\"id\":2396,\"subfield\":[\"2550\",\"2552\",\"2553\",\"2554\",\"2555\",\"2558\",null,null,\"2560\"],\"default\":[null,null,null,null,null,null,null,null,null],\"indicator\":[\"0\",\"0\"],\"required\":1},{\"id\":583,\"subfield\":[\"583\",\"584\",\"585\"],\"default\":[null,null,null],\"indicator\":[\"43\",\"1\"],\"required\":0},{\"id\":1716,\"subfield\":[\"1716\"],\"default\":[],\"indicator\":[\"92\",\"16\"],\"required\":0}]', '2024-01-26 06:53:12', '2024-02-01 08:01:57'),
(10, 'VERTICAL FILES_LC_PCAARRD', '[{\"id\":167,\"subfield\":[\"167\"],\"default\":[],\"indicator\":[\"16\",\"48\"],\"required\":0},{\"id\":232,\"subfield\":[\"232\",\"233\"],\"default\":[null,null],\"indicator\":[\"58\",\"61\"],\"required\":0},{\"id\":349,\"subfield\":[\"349\"],\"default\":[],\"indicator\":[\"92\",\"1\"],\"required\":0},{\"id\":507,\"subfield\":[\"507\",\"508\",\"509\"],\"default\":[null,null,null],\"indicator\":[\"0\",\"0\"],\"required\":0},{\"id\":623,\"subfield\":[\"623\",\"624\",\"625\",\"626\",null],\"default\":[null,null,null,null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":670,\"subfield\":[\"670\",\"671\"],\"default\":[null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":679,\"subfield\":[\"679\",\"680\"],\"default\":[null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":687,\"subfield\":[\"687\",\"688\"],\"default\":[null,null],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":864,\"subfield\":[\"863\"],\"default\":[],\"indicator\":[\"166\",\"0\"],\"required\":0},{\"id\":1043,\"subfield\":[\"1043\"],\"default\":[],\"indicator\":[\"0\",\"1\"],\"required\":0},{\"id\":1053,\"subfield\":[\"1053\"],\"default\":[],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":1073,\"subfield\":[\"1073\"],\"default\":[],\"indicator\":[\"1\",\"1\"],\"required\":0},{\"id\":1077,\"subfield\":[\"1077\"],\"default\":[],\"indicator\":[\"188\",\"191\"],\"required\":0},{\"id\":1596,\"subfield\":[\"1596\"],\"default\":[],\"indicator\":[\"16\",\"237\"],\"required\":0},{\"id\":2386,\"subfield\":[\"2386\"],\"default\":[],\"indicator\":[\"16\",\"16\"],\"required\":0},{\"id\":2396,\"subfield\":[\"2550\",\"2552\",\"2553\",\"2554\",\"2555\",\"2556\",\"2558\",\"2560\"],\"default\":[null,null,null,null,null,null,null,null],\"indicator\":[\"0\",\"0\"],\"required\":1},{\"id\":583,\"subfield\":[\"583\",\"584\",\"585\"],\"default\":[null,null,null],\"indicator\":[\"43\",\"1\"],\"required\":0},{\"id\":1716,\"subfield\":[\"1716\"],\"default\":[],\"indicator\":[\"92\",\"16\"],\"required\":0}]', '2024-01-26 07:50:15', '2024-02-01 08:02:29'),
(11, 'NON-PRINTS_LC_PCAARRD', '[]', '2024-01-26 07:59:05', '2024-01-26 07:59:05'),
(12, 'Sample', '[{\"id\":2486,\"subfield\":[],\"default\":[],\"indicator\":[\"0\",\"0\"],\"required\":0},{\"id\":2501,\"subfield\":[],\"default\":[],\"indicator\":[\"0\",\"0\"],\"required\":0}]', '2024-05-27 01:53:24', '2024-05-27 01:53:54');

-- --------------------------------------------------------

--
-- Table structure for table `marc_templates_backup`
--

CREATE TABLE `marc_templates_backup` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `fields` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marc_templates_backup`
--

INSERT INTO `marc_templates_backup` (`id`, `name`, `fields`, `created_at`, `updated_at`) VALUES
(2, 'Book', '[{\"id\":40,\"subfield\":[\"40\"],\"default\":[\"\"],\"indicator\":[\"1\",\"1\"],\"required\":1},{\"id\":507,\"subfield\":[\"507\"],\"default\":[],\"indicator\":[\"108\",\"99\"],\"required\":1},{\"id\":583,\"subfield\":[\"584\",\"585\"],\"default\":[null,null],\"indicator\":[\"43\",\"1\"],\"required\":1},{\"id\":1053,\"subfield\":[\"1053\"],\"default\":[],\"indicator\":[\"1\",\"1\"],\"required\":1},{\"id\":1157,\"subfield\":[\"1157\"],\"default\":[],\"indicator\":[\"203\",\"1\"],\"required\":1},{\"id\":1716,\"subfield\":[\"1716\",\"1720\"],\"default\":[null,null],\"indicator\":[\"92\",\"16\"],\"required\":1}]', '2023-10-10 12:14:56', '2023-10-10 12:14:56'),
(3, 'Serials', '[{\"id\":507,\"subfield\":[\"507\"],\"default\":[\"\"],\"indicator\":[\"0\",\"112\"],\"required\":1},{\"id\":583,\"subfield\":[\"584\",\"585\"],\"default\":[null,\"\"],\"indicator\":[\"43\",\"1\"],\"required\":1},{\"id\":1053,\"subfield\":[\"1053\"],\"default\":[],\"indicator\":[\"1\",\"1\"],\"required\":1},{\"id\":1157,\"subfield\":[\"1157\"],\"default\":[],\"indicator\":[\"203\",\"1\"],\"required\":1},{\"id\":1716,\"subfield\":[\"1716\",\"1720\"],\"default\":[null,null],\"indicator\":[\"92\",\"16\"],\"required\":1}]', '2023-10-10 12:28:34', '2023-10-10 12:28:34'),
(4, 'Vertical Files', '[{\"id\":40,\"subfield\":[\"40\"],\"default\":[],\"indicator\":[\"1\",\"1\"],\"required\":1},{\"id\":507,\"subfield\":[\"507\"],\"default\":[],\"indicator\":[\"108\",\"112\"],\"required\":1},{\"id\":583,\"subfield\":[\"584\",\"585\"],\"default\":[null,null],\"indicator\":[\"43\",\"1\"],\"required\":1},{\"id\":1053,\"subfield\":[\"1053\"],\"default\":[],\"indicator\":[\"1\",\"1\"],\"required\":1},{\"id\":1157,\"subfield\":[\"1157\"],\"default\":[],\"indicator\":[\"203\",\"0\"],\"required\":1}]', '2023-10-10 12:41:34', '2023-10-10 12:41:34');

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(100) DEFAULT NULL,
  `tooltip` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`id`, `name`, `code`, `tooltip`, `created_at`, `updated_at`) VALUES
(1, 'Books', '#', 'A division of literary work, which is separately published and has an independent physical existence, although its pagination may be continuous with other volumes, if more than a volume.', '2021-12-29 03:53:16', '2021-12-29 04:20:56'),
(2, 'Serials', '#', 'Publications in any format issued under the same title in successively numbered and/or dated parts or issues, appearing at regular or irregular intervals and intended to be continued indefinitely (e.g. newspapers, newsletters, magazines, and journals) and their electronic counterparts, as well as annuals, yearbooks, transactions, proceedings and monographic series cataloged separately.', '2021-12-29 03:53:16', '2021-12-29 03:53:16'),
(3, 'Thesis/Dissertations', '#', 'Original researches on a specific field or discipline prepared as a condition for the award of a degree or diploma.', '2021-12-29 03:53:16', '2021-12-29 03:53:16'),
(4, 'Non-Prints', '#', 'Materials published in a format other than writing or print on paper, including microfiche and microfilm, slides, filmstrips, films, videorecordings, audiorecordings, and information in digital format such as machine-readable data files (CD-ROM, VHS, etc.) and mostly require special equipment for listening and/or viewing.', '2021-12-29 03:53:16', '2021-12-29 03:53:16'),
(5, 'Vertical Files', '#', ' A collection of loose clippings, pictures, illustrations, pamphlets, leaflets, flyers, posters or other materials of little or no permanent value or materials of brief currency but has reference value or sufficient literacy or historical importance to merit permanent archival storage which, because of size and format, are usually organized in folders by subject or some other classification system to facilitate retrieval and are stored in a filing cabinet.', '2021-12-29 03:53:16', '2021-12-29 03:53:16'),
(6, 'Investigatory Projects', '#', 'These are research activities which aim to develop one\'s ability to identify and determine possible explanations of a particular phenomenon. Usually conducted by high school students.', '2021-12-29 03:53:16', '2021-12-29 03:53:16'),
(7, 'Technical Reports', '#', 'A scientific paper or article describing research or other significant developments in a field of the applied sciences and may be &quot;classified&quot; or subject to other access restrictions. Usually contains the results or findings of conducted R&Ds.', '2021-12-29 03:53:16', '2021-12-29 03:53:16'),
(8, 'Reprints', '#', 'Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequaturA copy of a material made from the original, with which it is identical except for possiblity of a new title-page and a note on the verse of the title-page of the number and date of reprinting and the corrections of minor errors. It is a conribution to a periodical afterwards issued separately.', '2021-12-29 03:53:16', '2021-12-29 03:53:16'),
(9, 'Analytics', '#', 'An entry in a catalog for a part of a book, periodical or other publication, article or contribution of separate authorship in a collection (e.g. journal articles, papers in proceedings, chapter in a book, monographic series).', '2021-12-29 03:53:16', '2021-12-29 03:53:16'),
(10, 'Language material', 'a', 'Used for non-manuscript language material. Manuscript language material uses code t. Includes microforms and electronic resources that are basically textual in nature, whether they are reproductions from print or originally produced.', '2023-09-08 06:42:53', '2023-09-08 06:42:53'),
(11, 'Notated music', 'c', 'Used for printed, microform, or electronic notated music.\r\n\r\n', '2023-09-08 06:45:01', '2023-09-08 06:45:01'),
(12, 'Manuscript notated music', 'd', 'Used for manuscript notated music or a microform of manuscript music.', '2023-09-08 06:45:25', '2023-09-08 06:45:25'),
(13, 'Cartographic material', 'e', 'Used for non-manuscript cartographic material or a microform of non-manuscript cartographic material. Includes maps, atlases, globes, digital maps, and other cartographic items.', '2023-09-08 06:45:57', '2023-09-08 06:45:57'),
(14, 'Manuscript cartographic material', 'f', 'Used for manuscript cartographic material or a microform of manuscript cartographic material.', '2023-09-08 06:46:30', '2023-09-08 06:46:30'),
(15, 'Projected medium', 'g', 'Used for motion pictures, videorecordings (including digital video), filmstrips, slide, transparencies or material specifically designed for projection. Material specifically designed for overhead projection is also included in this type of record category.', '2023-09-08 06:46:55', '2023-09-08 06:46:55'),
(16, 'Nonmusical sound recording', 'i', 'Used for a recording of nonmusical sounds (e.g., speech).', '2023-09-08 06:47:28', '2023-09-08 06:47:28'),
(17, 'Musical sound recording', 'j', 'Used for a musical sound recording (e.g., phonodiscs, compact discs, or cassette tapes.', '2023-09-08 06:48:06', '2023-09-08 06:48:06'),
(18, 'Two-dimensional nonprojectable graphic', 'k', 'Used for two-dimensional nonprojectable graphics such as, activity cards, charts, collages, computer graphics, digital pictures, drawings, duplication masters, flash cards, paintings, photo CDs, photomechanical reproductions, photonegatives, photoprints, pictures, postcards, posters, prints, spirit masters, study prints, technical drawings, transparency masters, and reproductions of any of these.', '2023-09-08 06:48:21', '2023-09-08 06:48:21'),
(19, 'Computer file', 'm', 'Used for the following classes of electronic resources: computer software (including programs, games, fonts), numeric data, computer-oriented multimedia, online systems or services. For these classes of materials, if there is a significant aspect that causes it to fall into another Leader/06 category, the code for that significant aspect is used instead of code m (e.g., vector data that is cartographic is not coded as numeric but as cartographic). Other classes of electronic resources are coded for their most significant aspect (e.g. language material, graphic, cartographic material, sound, music, moving image). In case of doubt or if the most significant aspect cannot be determined, consider the item a computer file.', '2023-09-08 06:49:14', '2023-09-08 06:49:14'),
(20, 'Kit', 'o', 'Used for a mixture of various components issued as a unit and intended primarily for instructional purposes where no one item is the predominant component of the kit.\r\n\r\nExamples are packages of assorted materials, such as a set of school social studies curriculum material (books, workbooks, guides, activities, etc.), or packages of educational test materials (tests, answer sheets, scoring guides, score charts, interpretative manuals, etc.).', '2023-09-08 06:49:43', '2023-09-08 06:49:43'),
(21, 'Mixed materials', 'p', 'Used when there are significant materials in two or more forms that are usually related by virtue of their having been accumulated by or about a person or body. Includes archival fonds and manuscript collections of mixed forms of materials, such as text, photographs, and sound recordings.\r\n\r\nIntended primary purpose is other than for instructional purposes (i.e., other than the purpose of those materials coded as o (Kit)).', '2023-09-08 06:50:37', '2023-09-08 06:50:37'),
(22, 'Three-dimensional artifact or naturally occurring object', 'r', 'Includes man-made objects such as models, dioramas, games, puzzles, simulations, sculptures and other three-dimensional art works, exhibits, machines, clothing, toys, and stitchery. Also includes naturally occurring objects such as, microscope specimens (or representations of them) and other specimens mounted for viewing.', '2023-09-08 06:51:10', '2023-09-08 06:51:10'),
(23, 'Manuscript language material', 't', 'Used for manuscript language material or a microform of manuscript language material. This category is applied to items for language material in handwriting, typescript, or computer printout including printed materials completed by hand or by keyboard. At the time it is created, this material is usually intended, either implicitly or explicitly, to exist as a single instance. Examples include marked or corrected galley and page proofs, manuscript books, legal papers, and unpublished theses and dissertations.', '2023-09-08 06:51:56', '2023-09-08 06:51:56');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(2, '2022_06_02_150509_create_thumbnails_table', 1),
(3, '2022_06_02_154127_create_fullpages_table', 2),
(4, '2022_06_04_094427_create_series_table', 3),
(5, '2022_06_05_011744_create_barrowed_books_table', 4),
(6, '2022_06_05_011758_create_for_reviews_table', 4),
(7, '2022_06_05_011809_create_ratings_table', 4),
(8, '2022_06_06_065549_create_p_d_f_s_table', 5),
(9, '2022_06_06_080902_create_views_table', 6),
(10, '2022_06_06_083355_create_downloads_table', 7),
(11, '2022_07_06_151234_create_visits_table', 8),
(12, '2022_07_19_072409_create_inquiries_table', 9),
(13, '2022_07_19_072539_create_reply_inquiries_table', 10),
(14, '2022_07_26_015327_create_consortias_table', 11),
(15, '2022_07_28_053334_create_imprints_table', 12),
(16, '2022_07_28_053347_create_type_of_imprints_table', 12),
(17, '2022_08_01_012534_create_view_borrowed_books_table', 13),
(18, '2022_08_10_085717_create_jobs_table', 14),
(19, '2022_08_15_051209_create_news_table', 15),
(20, '2022_08_16_051121_create_corporate_authors_table', 16),
(21, '2023_03_17_113327_create_patrontypes_table', 17),
(22, '2023_03_17_124238_add_patrontypes_id_to_users', 17),
(23, '2023_03_22_110145_create_csfs_table', 18),
(0, '2023_11_23_094601_create_permission_tables', 19);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(125) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(125) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(12, 'App\\Models\\User', 1),
(14, 'App\\Models\\User', 9),
(16, 'App\\Models\\User', 10),
(19, 'App\\Models\\User', 3);

-- --------------------------------------------------------

--
-- Table structure for table `multimedia`
--

CREATE TABLE `multimedia` (
  `id` int(11) NOT NULL,
  `catalog_id` varchar(24) DEFAULT NULL,
  `FileName` varchar(255) DEFAULT NULL,
  `FileLocation` varchar(255) DEFAULT NULL,
  `FileType` varchar(255) DEFAULT NULL,
  `Extension` varchar(255) DEFAULT NULL,
  `Permission` varchar(255) DEFAULT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `multimedia`
--

INSERT INTO `multimedia` (`id`, `catalog_id`, `FileName`, `FileLocation`, `FileType`, `Extension`, `Permission`, `Description`, `created_at`, `updated_at`, `deleted_at`) VALUES
(3, '1093', '65e96b3a53706.pdf', 'pdf/65e96b3a53706.pdf', 'pdf', '.pdf', NULL, NULL, '2024-03-07 07:22:34', '2024-03-07 07:22:34', NULL),
(4, '1092', 'ft-65eaace51fca1.pdf', 'pdf/ft-65eaace51fca1.pdf', 'pdf', '.pdf', NULL, NULL, '2024-03-08 06:15:01', '2024-03-08 06:15:01', NULL),
(5, '1089', 'ft-6629eec6516c1.pdf', 'pdf/ft-6629eec6516c1.pdf', 'pdf', '.pdf', NULL, NULL, '2024-04-25 05:48:54', '2024-04-25 05:48:54', NULL),
(6, '669', 'ft-66541cb9673e0.pdf', 'pdf/ft-66541cb9673e0.pdf', 'pdf', '.pdf', NULL, NULL, '2024-05-27 05:40:09', '2024-05-27 05:40:09', NULL),
(7, '2794', 'ft-68255b0c5b842.pdf', 'pdf/ft-68255b0c5b842.pdf', 'pdf', '.pdf', NULL, NULL, '2025-05-15 03:10:04', '2025-05-15 03:10:04', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `news_title` text DEFAULT NULL,
  `redirect_url` text DEFAULT NULL,
  `news_body` text DEFAULT NULL,
  `featured` enum('Yes','No') NOT NULL DEFAULT 'No',
  `news_cover` varchar(255) DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patrontypes`
--

CREATE TABLE `patrontypes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `types` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patrontypes`
--

INSERT INTO `patrontypes` (`id`, `types`, `created_at`, `updated_at`) VALUES
(1, 'Business/Private Sector', '2023-03-23 06:19:09', '2023-03-23 06:19:09'),
(2, 'Farmer', '2023-03-23 06:19:09', '2023-03-23 06:19:09'),
(3, 'Government Employee', '2023-03-23 06:19:09', '2023-03-23 06:19:09'),
(4, 'Policymaker', '2023-03-23 06:19:09', '2023-03-23 06:19:09'),
(5, 'Professional', '2023-03-23 06:19:09', '2023-03-23 06:19:09'),
(6, 'Researcher', '2023-03-23 06:19:09', '2023-03-23 06:19:09'),
(7, 'Student', '2023-03-23 06:19:09', '2023-03-23 06:19:09'),
(8, 'Others', '2023-03-23 06:19:09', '2023-03-23 06:19:09');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(125) NOT NULL,
  `guard_name` varchar(125) NOT NULL,
  `display_name` varchar(125) DEFAULT NULL,
  `description` varchar(125) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'access.cataloging', 'web', 'Cataloging Module Access', 'Allows access to cataloging module', '2023-11-24 02:30:08', '2024-01-31 02:45:39'),
(2, 'edit.catalog', 'web', 'Edit Catalog', 'Allows admin to edit and update catalog entries', '2023-11-24 02:33:39', '2023-11-24 02:33:39'),
(3, 'delete.catalog', 'web', 'Delete Catalog', 'Allows admin to delete catalog entries', '2023-11-24 02:34:06', '2023-11-24 02:34:06'),
(4, 'create.catalog', 'web', 'Create New Catalog', 'Allows admin to create new cataloging entries', '2023-11-24 02:34:38', '2023-11-24 02:34:38'),
(5, 'view.catalog.importlist', 'web', 'View Import List', 'Allows admin to view batch import list', '2023-11-24 02:35:23', '2024-01-31 07:26:41'),
(6, 'create.catalog.import', 'web', 'Create Import', 'Allows admin to create new imports', '2023-11-24 03:25:23', '2023-11-24 03:25:23'),
(7, 'edit.catalog.import', 'web', 'Edit Import', 'Allows admin to edit and update imports', '2023-11-24 03:25:40', '2023-11-24 03:25:40'),
(8, 'delete.catalog.import', 'web', 'Delete Import', 'Allows admin to delete imported records', '2023-11-24 03:25:59', '2023-11-24 03:25:59'),
(9, 'view.catalog.list', 'web', 'View Catalog List', 'Allows admin to view catalog\'s record list and on-hold list', '2023-11-24 03:27:14', '2023-11-24 03:27:14'),
(10, 'access.circulation', 'web', 'Circulation Module Access', 'Allows access to circulation module', '2023-11-24 03:27:36', '2024-01-31 02:46:15'),
(11, 'circulation.reservation.view', 'web', 'View Reservations', 'Allows users to view reservations', '2023-11-24 03:28:18', '2023-11-24 03:28:18'),
(13, 'manage.circulation.checkouts', 'web', 'Manage Check-outs and Check-ins', 'Allows users to manage checkout / borrow and return materials', '2023-11-24 03:39:19', '2024-01-31 08:40:32'),
(15, 'access.circulation.transaction', 'web', 'Transactions Access', 'Allows users to view circulation transactions', '2023-11-24 03:39:51', '2023-11-24 03:39:51'),
(16, 'access.inventory', 'web', 'Inventory Access', 'Enables inventory module', '2023-11-24 03:40:13', '2023-11-24 03:40:13'),
(17, 'access.inquiry', 'web', 'Inquiry Access', 'Allows inquiry access', '2023-11-24 03:40:38', '2023-11-24 03:40:38'),
(19, 'access.news', 'web', 'News Article Access', 'Allows admin to access news articles', '2023-11-24 03:52:03', '2023-11-24 03:52:03'),
(20, 'access.reports', 'web', 'Reports Access', 'Allows admin to access reports', '2023-11-24 03:52:24', '2023-11-24 03:52:24'),
(21, 'access.accounts', 'web', 'Accounts Module Access', 'Allows admin to access account module', '2023-11-24 03:52:41', '2024-01-31 02:45:15'),
(22, 'create.patron', 'web', 'Create Patron', 'Allows admin to create patron account', '2023-11-24 03:52:59', '2023-11-24 03:52:59'),
(23, 'edit.patron', 'web', 'Edit Patron', 'Allows admin to edit and update patron accounts', '2023-11-24 03:53:28', '2023-11-24 03:53:28'),
(24, 'delete.patron', 'web', 'Delete Patron', 'Allows admin to delete patron accounts', '2023-11-24 03:53:47', '2023-11-24 03:53:47'),
(25, 'access.configuration', 'web', 'Configuration/Setup Access', 'Enables config/setup module', '2023-11-24 03:54:12', '2023-11-24 03:54:12'),
(26, 'create.config.template', 'web', 'Create MARC template', 'Allows users to create MARC templates', '2023-11-24 03:54:53', '2023-11-24 03:54:53'),
(27, 'edit.config.template', 'web', 'Edit MARC template', 'Allows users to edit and update MARC templates', '2023-11-24 03:55:18', '2023-11-24 03:55:18'),
(29, 'delete.config.template', 'web', 'Delete MARC template', 'Allows users to delete MARC templates', '2023-11-24 05:39:25', '2023-11-24 05:39:25'),
(31, 'view.config.template', 'web', 'View MARC Templates', 'Allow admin to view the list of MARC Template', '2024-01-31 05:25:34', '2024-01-31 05:25:34'),
(32, 'publish-hold.catalog', 'web', 'Publish/Hold Catalog', 'Allow admin to publish or hold catalog/record from the list', '2024-01-31 07:48:31', '2024-01-31 07:59:52');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `p_d_f_s`
--

CREATE TABLE `p_d_f_s` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `catalog_id` int(11) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `catalog_id` varchar(25) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `regions`
--

CREATE TABLE `regions` (
  `id` int(11) NOT NULL,
  `psgcCode` varchar(255) DEFAULT NULL,
  `desc` text DEFAULT NULL,
  `regCode` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `regions`
--

INSERT INTO `regions` (`id`, `psgcCode`, `desc`, `regCode`) VALUES
(1, '010000000', 'REGION I (ILOCOS REGION)', '01'),
(2, '020000000', 'REGION II (CAGAYAN VALLEY)', '02'),
(3, '030000000', 'REGION III (CENTRAL LUZON)', '03'),
(4, '040000000', 'REGION IV-A (CALABARZON)', '04'),
(5, '170000000', 'REGION IV-B (MIMAROPA)', '17'),
(6, '050000000', 'REGION V (BICOL REGION)', '05'),
(7, '060000000', 'REGION VI (WESTERN VISAYAS)', '06'),
(8, '070000000', 'REGION VII (CENTRAL VISAYAS)', '07'),
(9, '080000000', 'REGION VIII (EASTERN VISAYAS)', '08'),
(10, '090000000', 'REGION IX (ZAMBOANGA PENINSULA)', '09'),
(11, '100000000', 'REGION X (NORTHERN MINDANAO)', '10'),
(12, '110000000', 'REGION XI (DAVAO REGION)', '11'),
(13, '120000000', 'REGION XII (SOCCSKSARGEN)', '12'),
(14, '130000000', 'NATIONAL CAPITAL REGION (NCR)', '13'),
(15, '140000000', 'CORDILLERA ADMINISTRATIVE REGION (CAR)', '14'),
(16, '150000000', 'AUTONOMOUS REGION IN MUSLIM MINDANAO (ARMM)', '15'),
(17, '160000000', 'REGION XIII (Caraga)', '16');

-- --------------------------------------------------------

--
-- Table structure for table `reply_inquiries`
--

CREATE TABLE `reply_inquiries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `inquiry_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `seen` timestamp NULL DEFAULT NULL,
  `seen_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(125) NOT NULL,
  `guard_name` varchar(125) NOT NULL,
  `display_name` varchar(125) DEFAULT NULL,
  `description` varchar(125) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES
(12, 'super_admin', 'web', 'Super Administrator', 'Highest Access Authority', '2023-11-28 06:06:21', '2024-01-31 08:49:58'),
(13, 'serial_admin', 'web', 'Serial Admin', 'Serials User with Specific Admin Privileges.', '2023-11-28 06:27:04', '2023-11-28 06:27:04'),
(14, 'cataloging_admin', 'web', 'Cataloging Admin', 'Cataloging User with Specific Admin Privileges.', '2023-11-28 06:27:53', '2024-02-02 01:59:58'),
(15, 'circulation_admin', 'web', 'Circulation', 'Circulation User with Specific Admin Privileges.', '2023-11-28 06:28:53', '2024-02-02 05:45:10'),
(16, 'faculty', 'web', 'Faculty', 'Faculty: Only Has OPAC Permissions', '2023-11-28 06:29:55', '2024-02-02 01:49:38'),
(17, 'system_admin', 'web', 'System Administrator', 'Manages The System', '2023-11-28 06:31:15', '2024-01-31 06:38:02'),
(19, 'developer', 'web', 'Developer', 'Good Night World!', '2024-02-02 05:17:32', '2024-05-23 07:47:29');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 12),
(1, 14),
(1, 16),
(1, 19),
(2, 12),
(2, 14),
(2, 16),
(2, 19),
(3, 12),
(3, 14),
(3, 16),
(3, 19),
(4, 12),
(4, 14),
(4, 16),
(4, 19),
(5, 12),
(5, 19),
(6, 14),
(6, 19),
(7, 14),
(8, 19),
(9, 12),
(9, 14),
(9, 19),
(10, 12),
(10, 15),
(10, 19),
(11, 12),
(11, 15),
(11, 19),
(13, 12),
(13, 15),
(13, 19),
(15, 12),
(15, 19),
(16, 19),
(17, 12),
(17, 19),
(19, 12),
(19, 19),
(20, 12),
(20, 19),
(21, 12),
(21, 19),
(22, 19),
(23, 19),
(25, 12),
(25, 17),
(25, 19),
(26, 14),
(26, 19),
(27, 12),
(27, 17),
(27, 19),
(29, 12),
(29, 17),
(31, 12),
(31, 17),
(31, 19),
(32, 12),
(32, 19);

-- --------------------------------------------------------

--
-- Table structure for table `series`
--

CREATE TABLE `series` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `thumbnails`
--

CREATE TABLE `thumbnails` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `thumbnails`
--

INSERT INTO `thumbnails` (`id`, `filename`, `created_at`, `updated_at`) VALUES
(1, 'pub1.jpg', NULL, NULL),
(2, 'pub2.jpg', NULL, NULL),
(3, 'pub3.jpg', NULL, NULL),
(4, 'pub4.jpg', NULL, NULL),
(5, 'pub5.jpg', NULL, NULL),
(6, 'pub6.jpg', NULL, NULL),
(7, 'pub7.jpg', NULL, NULL),
(8, 'pub8.jpg', NULL, NULL),
(9, 'pub9.jpg', NULL, NULL),
(10, 'pub10.jpg', NULL, NULL),
(11, 'pub11.jpg', NULL, NULL),
(12, 'pub12.jpg', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `type_of_imprints`
--

CREATE TABLE `type_of_imprints` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `type_of_imprints`
--

INSERT INTO `type_of_imprints` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Production', NULL, NULL),
(2, 'Publication', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `contactnum` varchar(50) DEFAULT NULL,
  `usertype` enum('Administrator','Student','Patron','Developer') DEFAULT 'Patron',
  `birthdate` varchar(50) DEFAULT NULL,
  `location` text DEFAULT NULL,
  `region_id` varchar(10) DEFAULT NULL,
  `position1` varchar(100) DEFAULT NULL,
  `position2` varchar(100) DEFAULT NULL,
  `position3` varchar(100) DEFAULT NULL,
  `position4` varchar(100) DEFAULT NULL,
  `sex` enum('Female','Male') DEFAULT NULL,
  `affiliation` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `backup` int(11) DEFAULT NULL COMMENT '1 = YES; NULL = NO',
  `patrontypes_id` bigint(20) UNSIGNED NOT NULL,
  `library_code` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `name`, `email`, `contactnum`, `usertype`, `birthdate`, `location`, `region_id`, `position1`, `position2`, `position3`, `position4`, `sex`, `affiliation`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `deleted_at`, `backup`, `patrontypes_id`, `library_code`) VALUES
(1, 'Admin   ', 'Admin', 'admin@email.com', '09751478547', 'Administrator', '04/27/1989', 'Taguig City', '14', 'Admin', 'Admin', 'Admin', 'Admin', 'Male', 'Admin', NULL, '$2y$10$i6iMml5o4vsyCl5347t71Or/l7IFuISU..TzYI3ON6PVtbS2MRI7.', NULL, '2023-11-24 07:10:37', '2023-11-23 08:42:24', NULL, NULL, 3, '2023-819933BEPFD'),
(3, 'Joe', 'Joe Cris Viar', 'jcviar027@gmail.com', '09876543211', 'Developer', '12/06/1990', 'Timugan, Los Banos Laguna', '13', NULL, NULL, NULL, NULL, 'Male', 'FPRDI', NULL, '$2y$10$JdVpur5zo0/NLNMGSxSXI.brDbhcohIn4GqQUxR4POjAqXiAU938.', NULL, '2023-07-10 08:05:37', '2025-04-11 02:57:51', NULL, NULL, 3, '2023-915669L3GXY'),
(9, 'Urek', 'Urek Mazino', 'urek@email.test', '09457041129', 'Administrator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$qk09PkJ5iYok1/IEoVlGw.AM/.2FykMqBC16AnOEiPBAkU.In5icC', NULL, '2023-12-12 03:03:14', '2023-12-12 03:03:14', NULL, NULL, 0, '2023-722215T9KHA'),
(10, 'JD', 'John Doe', 'jdoe@email.test', '09457041129', 'Administrator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$yByiA1CcJLcOVs1XhCcRAuNax82XzBcCZDaHLpFVucLkyhezEl022', NULL, '2023-12-12 03:04:06', '2023-12-12 03:04:06', NULL, NULL, 0, '2023-475601ECGP9'),
(11, NULL, 'Khun Agero Agnis', 'sirceojraiv@gmail.com', '09876543211', 'Patron', '2025-04-10', 'Economic Gardern, Timugan, Los Banos, Laguna', '04', NULL, NULL, NULL, NULL, 'Male', 'FPRDI', NULL, '$2y$10$8OTdk8xoySMv1WB8F3LGCeEMJi/VBzbQFmWm.eNJ7uBR9aCvJiR.i', NULL, '2024-05-02 01:38:09', '2025-04-15 01:18:31', NULL, NULL, 5, '2024-687845N5PJN'),
(12, NULL, 'John Doe', 'jd@email.com', '0978542187', 'Patron', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$nFOt312a9FqoADN7sXBnoeX59NZ6D52NNvshzOck..f53l1YCvAvC', NULL, '2024-05-27 05:42:07', '2024-05-27 05:42:07', NULL, NULL, 0, '2024-933151D6STI');

-- --------------------------------------------------------

--
-- Table structure for table `views`
--

CREATE TABLE `views` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `is_guest` enum('YES','NO') DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `catalog_id` varchar(50) DEFAULT NULL,
  `ip_view` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_borrowed_books`
-- (See below for the actual view)
--
CREATE TABLE `view_borrowed_books` (
`id` bigint(20) unsigned
,`user_id` int(11)
,`fullname` varchar(255)
,`contactnum` varchar(50)
,`email` varchar(100)
,`catalog_id` varchar(50)
,`consortia_id` int(11)
,`status` enum('For Approval','Borrowed','Returned')
,`approved_at` timestamp
,`approved_by` varchar(100)
,`returned_at` timestamp
,`created_at` timestamp
,`updated_at` timestamp
,`deleted_at` timestamp
,`title_statement` varchar(255)
,`request_at_string` varchar(82)
,`total_days` int(7)
,`approved_at_string` varchar(82)
,`returned_at_string` varchar(82)
);

-- --------------------------------------------------------

--
-- Table structure for table `view_catalogs`
--

CREATE TABLE `view_catalogs` (
  `id` int(10) UNSIGNED NOT NULL,
  `catalog_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `isbn` varchar(100) DEFAULT NULL,
  `issn` varchar(100) DEFAULT NULL,
  `accession_number` varchar(50) DEFAULT NULL,
  `is_published` tinyint(4) NOT NULL DEFAULT 0,
  `catalog_import_id` tinyint(4) DEFAULT NULL,
  `material_type_id` int(11) DEFAULT NULL,
  `material_type` varchar(100) DEFAULT NULL,
  `series_statement_id` int(11) DEFAULT NULL,
  `series_description` varchar(255) DEFAULT NULL,
  `title_statement` varchar(255) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `general_note` text DEFAULT NULL,
  `call_number` varchar(50) DEFAULT NULL,
  `catalog_date` varchar(50) DEFAULT NULL,
  `catalog_status` varchar(50) DEFAULT NULL,
  `date_acquired` date DEFAULT NULL,
  `edition` varchar(100) DEFAULT NULL,
  `joint_authors` text DEFAULT NULL,
  `publisher` text DEFAULT NULL,
  `pub_url` text DEFAULT NULL,
  `pub_year` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_holdings`
-- (See below for the actual view)
--
CREATE TABLE `view_holdings` (
`id` int(11)
,`catalog_id` int(11)
,`circulation_number` varchar(50)
,`accession_number` varchar(50)
,`title_statement` varchar(255)
,`summary` text
,`material_type_id` int(11)
,`series_statement_id` int(11)
,`catalog_date` varchar(50)
,`date_acquired` date
,`edition` varchar(100)
,`created_at` varchar(82)
,`CreatedBy` varchar(255)
,`catalog_status` varchar(11)
,`material` varchar(100)
,`series_description` varchar(255)
,`call_number` varchar(24)
,`publisher` text
,`pub_url` text
,`joint_authors` mediumtext
,`general_note` text
,`pub_year` varchar(20)
,`deleted_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_holdings_uncatalog`
-- (See below for the actual view)
--
CREATE TABLE `view_holdings_uncatalog` (
`id` int(11)
,`catalog_id` varchar(24)
,`call_number` varchar(24)
,`catalog_date` varchar(50)
,`CatalogSource` varchar(50)
,`material_type_id` int(11)
,`ISBN` varchar(50)
,`ISSN` varchar(50)
,`CallNumber` varchar(255)
,`CallNumberFull` varchar(255)
,`title_statement` varchar(255)
,`edition_statement` varchar(255)
,`edition_statement_remainder` varchar(255)
,`PhysicalDescriptionEtc` varchar(255)
,`MediaTypeCode` varchar(50)
,`MediaSource` varchar(50)
,`CarrierSource` varchar(50)
,`series_statement_id` int(11)
,`series_statement` varchar(150)
,`general_note` text
,`bibliography` varchar(255)
,`FormattedContents` varchar(200)
,`ScopeandContent` varchar(150)
,`Summary` text
,`AdditionalPhysicalFormAvailableNote` varchar(150)
,`PublicationStatus` tinyint(4)
,`IsActive` tinyint(1)
,`deleted` tinyint(1)
,`deleted_at` timestamp
,`CreatedBy` varchar(255)
,`created_at` timestamp
,`UpdatedBy` varchar(255)
,`updated_at` datetime
,`LanguageID` int(11)
,`Edition` varchar(100)
,`VolumeNo` varchar(50)
,`BroadClass` varchar(10)
,`ContentTypeID` int(11)
,`ContentTypeSource` varchar(50)
,`VolumeSequentialDesignation` varchar(150)
,`PhysicalFormAvailableNote` varchar(150)
,`AvailabilitySource` varchar(150)
,`AvailabilityConditions` varchar(150)
,`MediaTypeID` int(11)
,`CarrierTypeID` int(11)
,`CarrierTypeCode` int(11)
,`ContentTypeCode` int(11)
,`DissertationNote` varchar(500)
,`AbbreviatedTitle` varchar(255)
,`SoundCharacteristics` varchar(255)
,`DatesofPublication` varchar(255)
,`TimePeriodofCreation` varchar(255)
,`CitationReferencesNote` varchar(255)
,`SpecialCodedDates` varchar(50)
,`ProductionDistributionandYearSubmitted` varchar(500)
,`RestrictiononAccessNote` varchar(200)
,`TypesofReportandPeriodCoveredNote` varchar(200)
,`consortia_id` int(11)
,`accession_number` varchar(50)
,`circulation_number` varchar(50)
,`cost_price` decimal(10,2)
,`acquisition_modes` enum('Purchase','Subscription','Donation','Complimentary','Exchange','Download')
,`copy_number` char(10)
,`classifications_system` enum('Library of Congress','Dewey Decimal Classification')
,`date_acquired` date
,`source` varchar(50)
,`user_restriction` varchar(25)
,`item_status` varchar(50)
,`temporary_location` text
,`nonpublic_note` varchar(255)
,`frequency` enum('Daily','Weekly','Monthly','Quarterly','Yearly','Irregular')
,`volume` varchar(25)
,`issue_date` date
,`issue_number` varchar(50)
,`publication_date` varchar(20)
,`classification_number` varchar(50)
,`author_number` varchar(100)
,`copyright_date` date
,`title_remainder` varchar(255)
,`statement_responsibility` text
,`extent` text
,`other_physical` text
,`dimension` text
,`imprint` enum('Not Applicable/Not Provided/Earliest','Intervening','Current/Last')
,`type_of_imprint` enum('Production','Publication')
,`publication_place` text
,`publisher` text
,`copies` int(11)
,`material_desc` varchar(100)
,`joint_authors` mediumtext
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_holding_frontpages`
-- (See below for the actual view)
--
CREATE TABLE `view_holding_frontpages` (
`id` int(11)
,`catalog_id` varchar(24)
,`material_type_id` int(11)
,`FrontPageLocation` varchar(255)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_inquiries`
-- (See below for the actual view)
--
CREATE TABLE `view_inquiries` (
`id` bigint(20) unsigned
,`user_id` int(11)
,`fullname` varchar(255)
,`contactnum` varchar(255)
,`email` varchar(255)
,`catalog_id` varchar(50)
,`message` text
,`created_at` timestamp
,`updated_at` timestamp
,`title_statement` varchar(255)
,`created_at_string` varchar(82)
,`total_msg` bigint(21)
,`new_reply` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_inquiry_threads`
-- (See below for the actual view)
--
CREATE TABLE `view_inquiry_threads` (
`id` bigint(20) unsigned
,`user_id` int(11)
,`replied_by` int(11)
,`replied_by_name` varchar(255)
,`replied_msg` text
,`seen` timestamp
,`created_at_string` varchar(82)
,`thread_date` varchar(82)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_most_downloaded`
-- (See below for the actual view)
--
CREATE TABLE `view_most_downloaded` (
`total` bigint(21)
,`catalog_id` varchar(50)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_most_viewed`
-- (See below for the actual view)
--
CREATE TABLE `view_most_viewed` (
`total` bigint(21)
,`catalog_id` varchar(50)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_ratings`
-- (See below for the actual view)
--
CREATE TABLE `view_ratings` (
`id` bigint(20) unsigned
,`catalog_id` varchar(25)
,`user_id` int(11)
,`rating` int(11)
,`remarks` text
,`created_at` timestamp
,`updated_at` timestamp
,`name` varchar(255)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_searches`
-- (See below for the actual view)
--
CREATE TABLE `view_searches` (
`id` int(11)
,`search` mediumtext
,`material_type_id` int(11)
,`material` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `view_total_copies`
--

CREATE TABLE `view_total_copies` (
  `total` bigint(20) DEFAULT NULL,
  `holdings_id` varchar(24) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_to_reviews`
-- (See below for the actual view)
--
CREATE TABLE `view_to_reviews` (
`id` bigint(20) unsigned
,`catalog_id` varchar(50)
,`user_id` int(11)
,`to_review` date
,`created_at` timestamp
,`updated_at` timestamp
,`title_statement` varchar(255)
);

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `contactnum` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `schedule` datetime DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `site_visit` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `visits`
--

INSERT INTO `visits` (`id`, `user_id`, `fullname`, `contactnum`, `email`, `schedule`, `location`, `site_visit`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Mark Anthony Martin Diaz', '09089882853', 'diaz.mark.anthony@gmail.com', '2022-07-29 09:00:00', 'PCAARRD - Los Banos', 1, '2022-07-28 17:29:52', '2022-07-28 17:29:52'),
(2, 13923, 'Mark Anthony Martin Diaz', '09089882853', 'diaz.mark.anthony@gmail.com', '2022-08-15 09:00:00', 'PCAARRD - Los Banos', NULL, '2022-08-14 17:27:01', '2022-08-14 17:27:01'),
(3, 14011, 'Eloisa Mae Bodoso, Bonita', '09553977382', 'eloisamaebodoso7@gmail.com', '2022-12-06 09:00:00', 'PCAARRD - Los Banos', NULL, '2022-12-06 04:34:05', '2022-12-06 04:34:05'),
(4, 1, 'Juan Carlo J. Longamen', '09271350719', 'jclongamen@gmail.com', '2023-02-07 09:00:00', 'PCAARRD - Los Banos', 1, '2023-02-07 04:43:29', '2023-02-07 04:43:29'),
(5, 1, 'Juan Carlo J. Longamen', '09271350719', 'jclongamen@gmail.com', '2023-02-07 09:00:00', 'PCAARRD - Los Banos', 1, '2023-02-07 04:46:51', '2023-02-07 04:46:51'),
(6, 91, 'Juan Carlo J. Longamen', '092713507192', 'jclongamen@gmail.com', '2023-04-13 09:00:00', NULL, NULL, '2023-04-13 01:10:29', '2023-04-13 01:10:29'),
(7, 91, 'Juan Carlo J. Longamen', '092713507192', 'jclongamen@gmail.com', '2023-04-13 09:00:00', NULL, NULL, '2023-04-13 01:12:18', '2023-04-13 01:12:18'),
(8, 91, 'Juan Carlo J. Longamen', '092713507192', 'jclongamen@gmail.com', '2023-04-13 09:00:00', NULL, NULL, '2023-04-13 01:19:25', '2023-04-13 01:19:25'),
(9, 91, 'Juan Carlo J. Longamen', '092713507192', 'jclongamen@gmail.com', '2023-04-13 09:00:00', NULL, NULL, '2023-04-13 01:21:39', '2023-04-13 01:21:39'),
(10, 91, 'Juan Carlo J. Longamen', '092713507192', 'jclongamen@gmail.com', '2023-04-18 09:00:00', NULL, NULL, '2023-04-18 02:23:14', '2023-04-18 02:23:14'),
(11, 91, 'Juan Carlo J. Longamen', '092713507192', 'jclongamen@gmail.com', '2023-04-18 09:00:00', NULL, NULL, '2023-04-18 05:24:17', '2023-04-18 05:24:17'),
(12, 91, 'Juan Carlo J. Longamen', '092713507192', 'jclongamen@gmail.com', '2023-04-18 09:00:00', NULL, NULL, '2023-04-18 05:32:15', '2023-04-18 05:32:15'),
(13, 25490, 'JAY RONEL VILLANERA CONEJOS', '+63495362551', 'jairahandjayron@gmail.com', '2023-05-02 09:00:00', NULL, NULL, '2023-05-02 06:51:13', '2023-05-02 06:51:13'),
(0, 25427, 'Joe D. Mango', '09457041129', 'jcviar@email.test', '2023-05-09 09:00:00', NULL, NULL, '2023-05-09 05:31:54', '2023-05-09 05:31:54');

-- --------------------------------------------------------

--
-- Structure for view `view_borrowed_books`
--
DROP TABLE IF EXISTS `view_borrowed_books`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_borrowed_books`  AS SELECT `a`.`id` AS `id`, `a`.`user_id` AS `user_id`, `a`.`fullname` AS `fullname`, `a`.`contactnum` AS `contactnum`, `a`.`email` AS `email`, `a`.`catalog_id` AS `catalog_id`, `a`.`consortia_id` AS `consortia_id`, `a`.`status` AS `status`, `a`.`approved_at` AS `approved_at`, `a`.`approved_by` AS `approved_by`, `a`.`returned_at` AS `returned_at`, `a`.`created_at` AS `created_at`, `a`.`updated_at` AS `updated_at`, `a`.`deleted_at` AS `deleted_at`, `b`.`title_statement` AS `title_statement`, date_format(`a`.`created_at`,'%M %d, %Y %T') AS `request_at_string`, if(`a`.`status` <> 'For Approval',if(`a`.`returned_at` = NULL,to_days(cast(`a`.`returned_at` as date)) - to_days(cast(`a`.`approved_at` as date)),to_days(current_timestamp()) - to_days(cast(`a`.`approved_at` as date))),NULL) AS `total_days`, date_format(`a`.`approved_at`,'%M %d, %Y %T') AS `approved_at_string`, date_format(`a`.`returned_at`,'%M %d, %Y %T') AS `returned_at_string` FROM (`barrowed_books` `a` left join `holdings` `b` on(`b`.`catalog_id` = `a`.`catalog_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `view_holdings`
--
DROP TABLE IF EXISTS `view_holdings`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_holdings`  AS SELECT `a`.`id` AS `id`, `a`.`id` AS `catalog_id`, `a`.`circulation_number` AS `circulation_number`, `a`.`accession_number` AS `accession_number`, `a`.`title_statement` AS `title_statement`, if(`a`.`summary` is null,'N/A',`a`.`summary`) AS `summary`, `a`.`material_type_id` AS `material_type_id`, `a`.`series_statement_id` AS `series_statement_id`, `a`.`catalog_date` AS `catalog_date`, `a`.`date_acquired` AS `date_acquired`, `a`.`Edition` AS `edition`, date_format(`a`.`created_at`,'%M %d, %Y %T') AS `created_at`, `a`.`CreatedBy` AS `CreatedBy`, if(`a`.`catalog_date` = '','Uncataloged','Cataloged') AS `catalog_status`, `b`.`name` AS `material`, `c`.`description` AS `series_description`, `a`.`call_number` AS `call_number`, `a`.`publisher` AS `publisher`, `a`.`pub_url` AS `pub_url`, (select group_concat(substr(`authors`.`author_name`,8) separator ' - ') from `authors` where `authors`.`catalog_id` = `a`.`id` group by `authors`.`catalog_id`) AS `joint_authors`, `a`.`general_note` AS `general_note`, `a`.`publication_date` AS `pub_year`, `a`.`deleted_at` AS `deleted_at` FROM ((`holdings` `a` left join `materials` `b` on(`a`.`material_type_id` = `b`.`id`)) left join `series` `c` on(`c`.`id` = `a`.`series_statement_id`)) WHERE `a`.`deleted_at` is null ;

-- --------------------------------------------------------

--
-- Structure for view `view_holdings_uncatalog`
--
DROP TABLE IF EXISTS `view_holdings_uncatalog`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_holdings_uncatalog`  AS SELECT `a`.`id` AS `id`, `a`.`catalog_id` AS `catalog_id`, `a`.`call_number` AS `call_number`, `a`.`catalog_date` AS `catalog_date`, `a`.`CatalogSource` AS `CatalogSource`, `a`.`material_type_id` AS `material_type_id`, `a`.`ISBN` AS `ISBN`, `a`.`ISSN` AS `ISSN`, `a`.`CallNumber` AS `CallNumber`, `a`.`CallNumberFull` AS `CallNumberFull`, `a`.`title_statement` AS `title_statement`, `a`.`edition_statement` AS `edition_statement`, `a`.`edition_statement_remainder` AS `edition_statement_remainder`, `a`.`PhysicalDescriptionEtc` AS `PhysicalDescriptionEtc`, `a`.`MediaTypeCode` AS `MediaTypeCode`, `a`.`MediaSource` AS `MediaSource`, `a`.`CarrierSource` AS `CarrierSource`, `a`.`series_statement_id` AS `series_statement_id`, `a`.`series_statement` AS `series_statement`, `a`.`general_note` AS `general_note`, `a`.`bibliography` AS `bibliography`, `a`.`FormattedContents` AS `FormattedContents`, `a`.`ScopeandContent` AS `ScopeandContent`, `a`.`summary` AS `Summary`, `a`.`AdditionalPhysicalFormAvailableNote` AS `AdditionalPhysicalFormAvailableNote`, `a`.`PublicationStatus` AS `PublicationStatus`, `a`.`IsActive` AS `IsActive`, `a`.`deleted` AS `deleted`, `a`.`deleted_at` AS `deleted_at`, `a`.`CreatedBy` AS `CreatedBy`, `a`.`created_at` AS `created_at`, `a`.`UpdatedBy` AS `UpdatedBy`, `a`.`updated_at` AS `updated_at`, `a`.`LanguageID` AS `LanguageID`, `a`.`Edition` AS `Edition`, `a`.`VolumeNo` AS `VolumeNo`, `a`.`BroadClass` AS `BroadClass`, `a`.`ContentTypeID` AS `ContentTypeID`, `a`.`ContentTypeSource` AS `ContentTypeSource`, `a`.`VolumeSequentialDesignation` AS `VolumeSequentialDesignation`, `a`.`PhysicalFormAvailableNote` AS `PhysicalFormAvailableNote`, `a`.`AvailabilitySource` AS `AvailabilitySource`, `a`.`AvailabilityConditions` AS `AvailabilityConditions`, `a`.`MediaTypeID` AS `MediaTypeID`, `a`.`CarrierTypeID` AS `CarrierTypeID`, `a`.`CarrierTypeCode` AS `CarrierTypeCode`, `a`.`ContentTypeCode` AS `ContentTypeCode`, `a`.`DissertationNote` AS `DissertationNote`, `a`.`AbbreviatedTitle` AS `AbbreviatedTitle`, `a`.`SoundCharacteristics` AS `SoundCharacteristics`, `a`.`DatesofPublication` AS `DatesofPublication`, `a`.`TimePeriodofCreation` AS `TimePeriodofCreation`, `a`.`CitationReferencesNote` AS `CitationReferencesNote`, `a`.`SpecialCodedDates` AS `SpecialCodedDates`, `a`.`ProductionDistributionandYearSubmitted` AS `ProductionDistributionandYearSubmitted`, `a`.`RestrictiononAccessNote` AS `RestrictiononAccessNote`, `a`.`TypesofReportandPeriodCoveredNote` AS `TypesofReportandPeriodCoveredNote`, `a`.`consortia_id` AS `consortia_id`, `a`.`accession_number` AS `accession_number`, `a`.`circulation_number` AS `circulation_number`, `a`.`cost_price` AS `cost_price`, `a`.`acquisition_modes` AS `acquisition_modes`, `a`.`copy_number` AS `copy_number`, `a`.`classifications_system` AS `classifications_system`, `a`.`date_acquired` AS `date_acquired`, `a`.`source` AS `source`, `a`.`user_restriction` AS `user_restriction`, `a`.`item_status` AS `item_status`, `a`.`temporary_location` AS `temporary_location`, `a`.`nonpublic_note` AS `nonpublic_note`, `a`.`frequency` AS `frequency`, `a`.`volume` AS `volume`, `a`.`issue_date` AS `issue_date`, `a`.`issue_number` AS `issue_number`, `a`.`publication_date` AS `publication_date`, `a`.`classification_number` AS `classification_number`, `a`.`author_number` AS `author_number`, `a`.`copyright_date` AS `copyright_date`, `a`.`title_remainder` AS `title_remainder`, `a`.`statement_responsibility` AS `statement_responsibility`, `a`.`extent` AS `extent`, `a`.`other_physical` AS `other_physical`, `a`.`dimension` AS `dimension`, `a`.`imprint` AS `imprint`, `a`.`type_of_imprint` AS `type_of_imprint`, `a`.`publication_place` AS `publication_place`, `a`.`publisher` AS `publisher`, `a`.`copies` AS `copies`, `b`.`name` AS `material_desc`, (select group_concat(`authors`.`author_name2` separator ' - ') from `authors` where `authors`.`catalog_id` = `a`.`catalog_id` group by `authors`.`catalog_id`) AS `joint_authors` FROM (`holdings` `a` left join `materials` `b` on(`a`.`material_type_id` = `b`.`id`)) WHERE `a`.`catalog_date` is null AND `a`.`deleted_at` is not null ;

-- --------------------------------------------------------

--
-- Structure for view `view_holding_frontpages`
--
DROP TABLE IF EXISTS `view_holding_frontpages`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_holding_frontpages`  AS SELECT `a`.`id` AS `id`, `a`.`catalog_id` AS `catalog_id`, `a`.`material_type_id` AS `material_type_id`, `b`.`FrontPageLocation` AS `FrontPageLocation` FROM (`holdings` `a` left join `frontpages` `b` on(`a`.`catalog_id` = `b`.`catalog_id`)) WHERE `b`.`FrontPageLocation` is not null ;

-- --------------------------------------------------------

--
-- Structure for view `view_inquiries`
--
DROP TABLE IF EXISTS `view_inquiries`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_inquiries`  AS SELECT `a`.`id` AS `id`, `a`.`user_id` AS `user_id`, `a`.`fullname` AS `fullname`, `a`.`contactnum` AS `contactnum`, `a`.`email` AS `email`, `a`.`catalog_id` AS `catalog_id`, `a`.`message` AS `message`, `a`.`created_at` AS `created_at`, `a`.`updated_at` AS `updated_at`, `b`.`title_statement` AS `title_statement`, date_format(`a`.`created_at`,'%M %d, %Y %T') AS `created_at_string`, (select count(`reply_inquiries`.`id`) from `reply_inquiries` where `reply_inquiries`.`inquiry_id` = `a`.`id`) AS `total_msg`, (select count(`view_inquiry_threads`.`id`) from `view_inquiry_threads` where `view_inquiry_threads`.`id` = `a`.`id` and `view_inquiry_threads`.`seen` is null and `view_inquiry_threads`.`replied_by` <> `a`.`user_id`) AS `new_reply` FROM (`inquiries` `a` left join `holdings` `b` on(`b`.`catalog_id` = `a`.`catalog_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `view_inquiry_threads`
--
DROP TABLE IF EXISTS `view_inquiry_threads`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_inquiry_threads`  AS SELECT `b`.`id` AS `id`, `b`.`user_id` AS `user_id`, `a`.`user_id` AS `replied_by`, `c`.`name` AS `replied_by_name`, `a`.`message` AS `replied_msg`, `a`.`seen` AS `seen`, date_format(`b`.`created_at`,'%M %d, %Y %T') AS `created_at_string`, date_format(`a`.`created_at`,'%M %d, %Y %T') AS `thread_date` FROM ((`reply_inquiries` `a` left join `inquiries` `b` on(`a`.`inquiry_id` = `b`.`id`)) left join `users` `c` on(`a`.`user_id` = `c`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `view_most_downloaded`
--
DROP TABLE IF EXISTS `view_most_downloaded`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_most_downloaded`  AS SELECT count(`a`.`id`) AS `total`, `a`.`catalog_id` AS `catalog_id` FROM (`downloads` `a` join `catalogs` `c` on(`a`.`catalog_id` = `c`.`id`)) WHERE `c`.`deleted_at` is null GROUP BY `a`.`catalog_id` ORDER BY count(`a`.`id`) DESC LIMIT 0, 10 ;

-- --------------------------------------------------------

--
-- Structure for view `view_most_viewed`
--
DROP TABLE IF EXISTS `view_most_viewed`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_most_viewed`  AS SELECT count(`a`.`id`) AS `total`, `a`.`catalog_id` AS `catalog_id` FROM (`views` `a` join `catalogs` `c` on(`a`.`catalog_id` = `c`.`id`)) WHERE `c`.`deleted_at` is null GROUP BY `a`.`catalog_id` ORDER BY count(`a`.`id`) DESC LIMIT 0, 5 ;

-- --------------------------------------------------------

--
-- Structure for view `view_ratings`
--
DROP TABLE IF EXISTS `view_ratings`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_ratings`  AS SELECT `a`.`id` AS `id`, `a`.`catalog_id` AS `catalog_id`, `a`.`user_id` AS `user_id`, `a`.`rating` AS `rating`, `a`.`remarks` AS `remarks`, `a`.`created_at` AS `created_at`, `a`.`updated_at` AS `updated_at`, `b`.`name` AS `name` FROM (`ratings` `a` left join `users` `b` on(`b`.`id` = `a`.`user_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `view_searches`
--
DROP TABLE IF EXISTS `view_searches`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_searches`  AS SELECT `a`.`id` AS `id`, concat(`a`.`title_statement`,`a`.`summary`) AS `search`, `a`.`material_type_id` AS `material_type_id`, `b`.`name` AS `material` FROM (`holdings` `a` left join `materials` `b` on(`b`.`id` = `a`.`material_type_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `view_to_reviews`
--
DROP TABLE IF EXISTS `view_to_reviews`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_to_reviews`  AS SELECT `a`.`id` AS `id`, `a`.`catalog_id` AS `catalog_id`, `a`.`user_id` AS `user_id`, `a`.`to_review` AS `to_review`, `a`.`created_at` AS `created_at`, `a`.`updated_at` AS `updated_at`, `b`.`title_statement` AS `title_statement` FROM (`downloads` `a` left join `holdings` `b` on(`b`.`catalog_id` = `a`.`catalog_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `authors`
--
ALTER TABLE `authors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `barrowed_books`
--
ALTER TABLE `barrowed_books`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catalogs`
--
ALTER TABLE `catalogs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catalog_imports`
--
ALTER TABLE `catalog_imports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `catalog_import_progress`
--
ALTER TABLE `catalog_import_progress`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `circulations`
--
ALTER TABLE `circulations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `circulation_fines`
--
ALTER TABLE `circulation_fines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `circulation_fine_history`
--
ALTER TABLE `circulation_fine_history`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `circulation_reservations`
--
ALTER TABLE `circulation_reservations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `circulation_status`
--
ALTER TABLE `circulation_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_no` (`order_no`);

--
-- Indexes for table `consortias`
--
ALTER TABLE `consortias`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `copies`
--
ALTER TABLE `copies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `holding_id` (`catalog_id`) USING BTREE;

--
-- Indexes for table `copies_status`
--
ALTER TABLE `copies_status`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `corporate_authors`
--
ALTER TABLE `corporate_authors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `csfs`
--
ALTER TABLE `csfs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `downloads`
--
ALTER TABLE `downloads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `for_reviews`
--
ALTER TABLE `for_reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `frontpages`
--
ALTER TABLE `frontpages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fullpages`
--
ALTER TABLE `fullpages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `holdings`
--
ALTER TABLE `holdings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `holdingscopypresenteds`
--
ALTER TABLE `holdingscopypresenteds`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `imprints`
--
ALTER TABLE `imprints`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marctags_loc`
--
ALTER TABLE `marctags_loc`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `marctag_indicators_loc`
--
ALTER TABLE `marctag_indicators_loc`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marctag_subfields_loc`
--
ALTER TABLE `marctag_subfields_loc`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marc_class_sys`
--
ALTER TABLE `marc_class_sys`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marc_templates`
--
ALTER TABLE `marc_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marc_templates_backup`
--
ALTER TABLE `marc_templates_backup`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `multimedia`
--
ALTER TABLE `multimedia`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patrontypes`
--
ALTER TABLE `patrontypes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `p_d_f_s`
--
ALTER TABLE `p_d_f_s`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `regions`
--
ALTER TABLE `regions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reply_inquiries`
--
ALTER TABLE `reply_inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `series`
--
ALTER TABLE `series`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `thumbnails`
--
ALTER TABLE `thumbnails`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `type_of_imprints`
--
ALTER TABLE `type_of_imprints`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `views`
--
ALTER TABLE `views`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `view_catalogs`
--
ALTER TABLE `view_catalogs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `authors`
--
ALTER TABLE `authors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `barrowed_books`
--
ALTER TABLE `barrowed_books`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `catalogs`
--
ALTER TABLE `catalogs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `catalog_imports`
--
ALTER TABLE `catalog_imports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `catalog_import_progress`
--
ALTER TABLE `catalog_import_progress`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `circulations`
--
ALTER TABLE `circulations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `circulation_fines`
--
ALTER TABLE `circulation_fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `circulation_fine_history`
--
ALTER TABLE `circulation_fine_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `circulation_reservations`
--
ALTER TABLE `circulation_reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `circulation_status`
--
ALTER TABLE `circulation_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `consortias`
--
ALTER TABLE `consortias`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `copies`
--
ALTER TABLE `copies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `copies_status`
--
ALTER TABLE `copies_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `corporate_authors`
--
ALTER TABLE `corporate_authors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `csfs`
--
ALTER TABLE `csfs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `downloads`
--
ALTER TABLE `downloads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `for_reviews`
--
ALTER TABLE `for_reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `frontpages`
--
ALTER TABLE `frontpages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fullpages`
--
ALTER TABLE `fullpages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holdings`
--
ALTER TABLE `holdings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holdingscopypresenteds`
--
ALTER TABLE `holdingscopypresenteds`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `imprints`
--
ALTER TABLE `imprints`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marctags_loc`
--
ALTER TABLE `marctags_loc`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2503;

--
-- AUTO_INCREMENT for table `marctag_indicators_loc`
--
ALTER TABLE `marctag_indicators_loc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=350;

--
-- AUTO_INCREMENT for table `marctag_subfields_loc`
--
ALTER TABLE `marctag_subfields_loc`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2634;

--
-- AUTO_INCREMENT for table `marc_class_sys`
--
ALTER TABLE `marc_class_sys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `marc_templates`
--
ALTER TABLE `marc_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `marc_templates_backup`
--
ALTER TABLE `marc_templates_backup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `multimedia`
--
ALTER TABLE `multimedia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patrontypes`
--
ALTER TABLE `patrontypes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `p_d_f_s`
--
ALTER TABLE `p_d_f_s`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `regions`
--
ALTER TABLE `regions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `reply_inquiries`
--
ALTER TABLE `reply_inquiries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `series`
--
ALTER TABLE `series`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `thumbnails`
--
ALTER TABLE `thumbnails`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `type_of_imprints`
--
ALTER TABLE `type_of_imprints`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `views`
--
ALTER TABLE `views`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `view_catalogs`
--
ALTER TABLE `view_catalogs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


