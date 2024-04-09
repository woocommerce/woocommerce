-- MariaDB dump 10.19  Distrib 10.11.6-MariaDB, for Linux (aarch64)
--
-- Host: tests-mysql    Database: tests-wordpress
-- ------------------------------------------------------
-- Server version	11.3.2-MariaDB-1:11.3.2+maria~ubu2204

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `wp_actionscheduler_actions`
--

DROP TABLE IF EXISTS `wp_actionscheduler_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_actionscheduler_actions` (
  `action_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hook` varchar(191) NOT NULL,
  `status` varchar(20) NOT NULL,
  `scheduled_date_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `scheduled_date_local` datetime DEFAULT '0000-00-00 00:00:00',
  `priority` tinyint(3) unsigned NOT NULL DEFAULT 10,
  `args` varchar(191) DEFAULT NULL,
  `schedule` longtext DEFAULT NULL,
  `group_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_attempt_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `last_attempt_local` datetime DEFAULT '0000-00-00 00:00:00',
  `claim_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `extended_args` varchar(8000) DEFAULT NULL,
  PRIMARY KEY (`action_id`),
  KEY `hook_status_scheduled_date_gmt` (`hook`(163),`status`,`scheduled_date_gmt`),
  KEY `status_scheduled_date_gmt` (`status`,`scheduled_date_gmt`),
  KEY `scheduled_date_gmt` (`scheduled_date_gmt`),
  KEY `args` (`args`),
  KEY `group_id` (`group_id`),
  KEY `last_attempt_gmt` (`last_attempt_gmt`),
  KEY `claim_id_status_scheduled_date_gmt` (`claim_id`,`status`,`scheduled_date_gmt`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_actionscheduler_actions`
--

LOCK TABLES `wp_actionscheduler_actions` WRITE;
/*!40000 ALTER TABLE `wp_actionscheduler_actions` DISABLE KEYS */;
INSERT INTO `wp_actionscheduler_actions` VALUES
(5,'action_scheduler/migration_hook','pending','2024-04-09 08:13:39','2024-04-09 08:13:39',10,'[]','O:30:\"ActionScheduler_SimpleSchedule\":2:{s:22:\"\0*\0scheduled_timestamp\";i:1712650419;s:41:\"\0ActionScheduler_SimpleSchedule\0timestamp\";i:1712650419;}',1,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0,NULL),
(6,'woocommerce_run_product_attribute_lookup_update_callback','pending','2024-04-09 08:13:03','2024-04-09 08:13:03',10,'[26,2]','O:30:\"ActionScheduler_SimpleSchedule\":2:{s:22:\"\0*\0scheduled_timestamp\";i:1712650383;s:41:\"\0ActionScheduler_SimpleSchedule\0timestamp\";i:1712650383;}',2,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0,NULL);
/*!40000 ALTER TABLE `wp_actionscheduler_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_actionscheduler_claims`
--

DROP TABLE IF EXISTS `wp_actionscheduler_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_actionscheduler_claims` (
  `claim_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date_created_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`claim_id`),
  KEY `date_created_gmt` (`date_created_gmt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_actionscheduler_claims`
--

LOCK TABLES `wp_actionscheduler_claims` WRITE;
/*!40000 ALTER TABLE `wp_actionscheduler_claims` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_actionscheduler_claims` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_actionscheduler_groups`
--

DROP TABLE IF EXISTS `wp_actionscheduler_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_actionscheduler_groups` (
  `group_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  PRIMARY KEY (`group_id`),
  KEY `slug` (`slug`(191))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_actionscheduler_groups`
--

LOCK TABLES `wp_actionscheduler_groups` WRITE;
/*!40000 ALTER TABLE `wp_actionscheduler_groups` DISABLE KEYS */;
INSERT INTO `wp_actionscheduler_groups` VALUES
(1,'action-scheduler-migration'),
(2,'woocommerce-db-updates');
/*!40000 ALTER TABLE `wp_actionscheduler_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_actionscheduler_logs`
--

DROP TABLE IF EXISTS `wp_actionscheduler_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_actionscheduler_logs` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `action_id` bigint(20) unsigned NOT NULL,
  `message` text NOT NULL,
  `log_date_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `log_date_local` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`log_id`),
  KEY `action_id` (`action_id`),
  KEY `log_date_gmt` (`log_date_gmt`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_actionscheduler_logs`
--

LOCK TABLES `wp_actionscheduler_logs` WRITE;
/*!40000 ALTER TABLE `wp_actionscheduler_logs` DISABLE KEYS */;
INSERT INTO `wp_actionscheduler_logs` VALUES
(1,5,'action created','2024-04-09 08:12:39','2024-04-09 08:12:39'),
(2,6,'action created','2024-04-09 08:13:02','2024-04-09 08:13:02');
/*!40000 ALTER TABLE `wp_actionscheduler_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_commentmeta`
--

DROP TABLE IF EXISTS `wp_commentmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_commentmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `comment_id` (`comment_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_commentmeta`
--

LOCK TABLES `wp_commentmeta` WRITE;
/*!40000 ALTER TABLE `wp_commentmeta` DISABLE KEYS */;
INSERT INTO `wp_commentmeta` VALUES
(1,1,'rating','5'),
(2,1,'verified','0'),
(3,2,'rating','4'),
(4,2,'verified','0'),
(5,3,'rating','1'),
(6,3,'verified','0'),
(7,4,'rating','2'),
(8,4,'verified','0');
/*!40000 ALTER TABLE `wp_commentmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_comments`
--

DROP TABLE IF EXISTS `wp_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_comments` (
  `comment_ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_post_ID` bigint(20) unsigned NOT NULL DEFAULT 0,
  `comment_author` tinytext NOT NULL,
  `comment_author_email` varchar(100) NOT NULL DEFAULT '',
  `comment_author_url` varchar(200) NOT NULL DEFAULT '',
  `comment_author_IP` varchar(100) NOT NULL DEFAULT '',
  `comment_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_content` text NOT NULL,
  `comment_karma` int(11) NOT NULL DEFAULT 0,
  `comment_approved` varchar(20) NOT NULL DEFAULT '1',
  `comment_agent` varchar(255) NOT NULL DEFAULT '',
  `comment_type` varchar(20) NOT NULL DEFAULT 'comment',
  `comment_parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`comment_ID`),
  KEY `comment_post_ID` (`comment_post_ID`),
  KEY `comment_approved_date_gmt` (`comment_approved`,`comment_date_gmt`),
  KEY `comment_date_gmt` (`comment_date_gmt`),
  KEY `comment_parent` (`comment_parent`),
  KEY `comment_author_email` (`comment_author_email`(10)),
  KEY `woo_idx_comment_type` (`comment_type`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_comments`
--

LOCK TABLES `wp_comments` WRITE;
/*!40000 ALTER TABLE `wp_comments` DISABLE KEYS */;
INSERT INTO `wp_comments` VALUES
(1,7,'Jane Smith','customer@woocommerceblockse2etestsuite.com','','','2024-04-09 08:13:07','2024-04-09 08:13:07','Nice album!',0,'1','','review',0,0),
(2,7,'Jane Smith','customer@woocommerceblockse2etestsuite.com','','','2024-04-09 08:13:08','2024-04-09 08:13:08','Not bad.',0,'1','','review',0,0),
(3,12,'Jane Smith','customer@woocommerceblockse2etestsuite.com','','','2024-04-09 08:13:10','2024-04-09 08:13:10','Really awful.',0,'1','','review',0,0),
(4,12,'Jane Smith','customer@woocommerceblockse2etestsuite.com','','','2024-04-09 08:13:11','2024-04-09 08:13:11','Bad!',0,'1','','review',0,0);
/*!40000 ALTER TABLE `wp_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_links`
--

DROP TABLE IF EXISTS `wp_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_links` (
  `link_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `link_url` varchar(255) NOT NULL DEFAULT '',
  `link_name` varchar(255) NOT NULL DEFAULT '',
  `link_image` varchar(255) NOT NULL DEFAULT '',
  `link_target` varchar(25) NOT NULL DEFAULT '',
  `link_description` varchar(255) NOT NULL DEFAULT '',
  `link_visible` varchar(20) NOT NULL DEFAULT 'Y',
  `link_owner` bigint(20) unsigned NOT NULL DEFAULT 1,
  `link_rating` int(11) NOT NULL DEFAULT 0,
  `link_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `link_rel` varchar(255) NOT NULL DEFAULT '',
  `link_notes` mediumtext NOT NULL,
  `link_rss` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`link_id`),
  KEY `link_visible` (`link_visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_links`
--

LOCK TABLES `wp_links` WRITE;
/*!40000 ALTER TABLE `wp_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_options`
--

DROP TABLE IF EXISTS `wp_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_options` (
  `option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `option_name` varchar(191) NOT NULL DEFAULT '',
  `option_value` longtext NOT NULL,
  `autoload` varchar(20) NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`option_id`),
  UNIQUE KEY `option_name` (`option_name`),
  KEY `autoload` (`autoload`)
) ENGINE=InnoDB AUTO_INCREMENT=371 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_options`
--

LOCK TABLES `wp_options` WRITE;
/*!40000 ALTER TABLE `wp_options` DISABLE KEYS */;
INSERT INTO `wp_options` VALUES
(1,'siteurl','http://localhost:8889','yes'),
(2,'home','http://localhost:8889','yes'),
(3,'blogname','WooCommerce Blocks E2E Test Suite','yes'),
(4,'blogdescription','','yes'),
(5,'users_can_register','0','yes'),
(6,'admin_email','wordpress@example.com','yes'),
(7,'start_of_week','1','yes'),
(8,'use_balanceTags','0','yes'),
(9,'use_smilies','1','yes'),
(10,'require_name_email','1','yes'),
(11,'comments_notify','1','yes'),
(12,'posts_per_rss','10','yes'),
(13,'rss_use_excerpt','0','yes'),
(14,'mailserver_url','mail.example.com','yes'),
(15,'mailserver_login','login@example.com','yes'),
(16,'mailserver_pass','password','yes'),
(17,'mailserver_port','110','yes'),
(18,'default_category','1','yes'),
(19,'default_comment_status','open','yes'),
(20,'default_ping_status','open','yes'),
(21,'default_pingback_flag','1','yes'),
(22,'posts_per_page','10','yes'),
(23,'date_format','F j, Y','yes'),
(24,'time_format','g:i a','yes'),
(25,'links_updated_date_format','F j, Y g:i a','yes'),
(26,'comment_moderation','0','yes'),
(27,'moderation_notify','1','yes'),
(28,'permalink_structure','/%postname%/','yes'),
(29,'rewrite_rules','a:190:{s:24:\"^wc-auth/v([1]{1})/(.*)?\";s:63:\"index.php?wc-auth-version=$matches[1]&wc-auth-route=$matches[2]\";s:22:\"^wc-api/v([1-3]{1})/?$\";s:51:\"index.php?wc-api-version=$matches[1]&wc-api-route=/\";s:24:\"^wc-api/v([1-3]{1})(.*)?\";s:61:\"index.php?wc-api-version=$matches[1]&wc-api-route=$matches[2]\";s:21:\"^wc/file/transient/?$\";s:33:\"index.php?wc-transient-file-name=\";s:24:\"^wc/file/transient/(.+)$\";s:44:\"index.php?wc-transient-file-name=$matches[1]\";s:7:\"shop/?$\";s:27:\"index.php?post_type=product\";s:37:\"shop/feed/(feed|rdf|rss|rss2|atom)/?$\";s:44:\"index.php?post_type=product&feed=$matches[1]\";s:32:\"shop/(feed|rdf|rss|rss2|atom)/?$\";s:44:\"index.php?post_type=product&feed=$matches[1]\";s:24:\"shop/page/([0-9]{1,})/?$\";s:45:\"index.php?post_type=product&paged=$matches[1]\";s:11:\"^wp-json/?$\";s:22:\"index.php?rest_route=/\";s:14:\"^wp-json/(.*)?\";s:33:\"index.php?rest_route=/$matches[1]\";s:21:\"^index.php/wp-json/?$\";s:22:\"index.php?rest_route=/\";s:24:\"^index.php/wp-json/(.*)?\";s:33:\"index.php?rest_route=/$matches[1]\";s:17:\"^wp-sitemap\\.xml$\";s:23:\"index.php?sitemap=index\";s:17:\"^wp-sitemap\\.xsl$\";s:36:\"index.php?sitemap-stylesheet=sitemap\";s:23:\"^wp-sitemap-index\\.xsl$\";s:34:\"index.php?sitemap-stylesheet=index\";s:48:\"^wp-sitemap-([a-z]+?)-([a-z\\d_-]+?)-(\\d+?)\\.xml$\";s:75:\"index.php?sitemap=$matches[1]&sitemap-subtype=$matches[2]&paged=$matches[3]\";s:34:\"^wp-sitemap-([a-z]+?)-(\\d+?)\\.xml$\";s:47:\"index.php?sitemap=$matches[1]&paged=$matches[2]\";s:47:\"category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?category_name=$matches[1]&feed=$matches[2]\";s:42:\"category/(.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?category_name=$matches[1]&feed=$matches[2]\";s:23:\"category/(.+?)/embed/?$\";s:46:\"index.php?category_name=$matches[1]&embed=true\";s:35:\"category/(.+?)/page/?([0-9]{1,})/?$\";s:53:\"index.php?category_name=$matches[1]&paged=$matches[2]\";s:32:\"category/(.+?)/wc-api(/(.*))?/?$\";s:54:\"index.php?category_name=$matches[1]&wc-api=$matches[3]\";s:43:\"category/(.+?)/wc/file/transient(/(.*))?/?$\";s:65:\"index.php?category_name=$matches[1]&wc/file/transient=$matches[3]\";s:17:\"category/(.+?)/?$\";s:35:\"index.php?category_name=$matches[1]\";s:44:\"tag/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?tag=$matches[1]&feed=$matches[2]\";s:39:\"tag/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?tag=$matches[1]&feed=$matches[2]\";s:20:\"tag/([^/]+)/embed/?$\";s:36:\"index.php?tag=$matches[1]&embed=true\";s:32:\"tag/([^/]+)/page/?([0-9]{1,})/?$\";s:43:\"index.php?tag=$matches[1]&paged=$matches[2]\";s:29:\"tag/([^/]+)/wc-api(/(.*))?/?$\";s:44:\"index.php?tag=$matches[1]&wc-api=$matches[3]\";s:40:\"tag/([^/]+)/wc/file/transient(/(.*))?/?$\";s:55:\"index.php?tag=$matches[1]&wc/file/transient=$matches[3]\";s:14:\"tag/([^/]+)/?$\";s:25:\"index.php?tag=$matches[1]\";s:45:\"type/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?post_format=$matches[1]&feed=$matches[2]\";s:40:\"type/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?post_format=$matches[1]&feed=$matches[2]\";s:21:\"type/([^/]+)/embed/?$\";s:44:\"index.php?post_format=$matches[1]&embed=true\";s:33:\"type/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?post_format=$matches[1]&paged=$matches[2]\";s:15:\"type/([^/]+)/?$\";s:33:\"index.php?post_format=$matches[1]\";s:55:\"product-category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?product_cat=$matches[1]&feed=$matches[2]\";s:50:\"product-category/(.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?product_cat=$matches[1]&feed=$matches[2]\";s:31:\"product-category/(.+?)/embed/?$\";s:44:\"index.php?product_cat=$matches[1]&embed=true\";s:43:\"product-category/(.+?)/page/?([0-9]{1,})/?$\";s:51:\"index.php?product_cat=$matches[1]&paged=$matches[2]\";s:25:\"product-category/(.+?)/?$\";s:33:\"index.php?product_cat=$matches[1]\";s:52:\"product-tag/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?product_tag=$matches[1]&feed=$matches[2]\";s:47:\"product-tag/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?product_tag=$matches[1]&feed=$matches[2]\";s:28:\"product-tag/([^/]+)/embed/?$\";s:44:\"index.php?product_tag=$matches[1]&embed=true\";s:40:\"product-tag/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?product_tag=$matches[1]&paged=$matches[2]\";s:22:\"product-tag/([^/]+)/?$\";s:33:\"index.php?product_tag=$matches[1]\";s:46:\"color/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pa_color=$matches[1]&feed=$matches[2]\";s:41:\"color/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pa_color=$matches[1]&feed=$matches[2]\";s:22:\"color/([^/]+)/embed/?$\";s:41:\"index.php?pa_color=$matches[1]&embed=true\";s:34:\"color/([^/]+)/page/?([0-9]{1,})/?$\";s:48:\"index.php?pa_color=$matches[1]&paged=$matches[2]\";s:16:\"color/([^/]+)/?$\";s:30:\"index.php?pa_color=$matches[1]\";s:45:\"size/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:46:\"index.php?pa_size=$matches[1]&feed=$matches[2]\";s:40:\"size/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:46:\"index.php?pa_size=$matches[1]&feed=$matches[2]\";s:21:\"size/([^/]+)/embed/?$\";s:40:\"index.php?pa_size=$matches[1]&embed=true\";s:33:\"size/([^/]+)/page/?([0-9]{1,})/?$\";s:47:\"index.php?pa_size=$matches[1]&paged=$matches[2]\";s:15:\"size/([^/]+)/?$\";s:29:\"index.php?pa_size=$matches[1]\";s:35:\"product/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:45:\"product/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:65:\"product/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:60:\"product/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:60:\"product/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:41:\"product/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:24:\"product/([^/]+)/embed/?$\";s:40:\"index.php?product=$matches[1]&embed=true\";s:28:\"product/([^/]+)/trackback/?$\";s:34:\"index.php?product=$matches[1]&tb=1\";s:48:\"product/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:46:\"index.php?product=$matches[1]&feed=$matches[2]\";s:43:\"product/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:46:\"index.php?product=$matches[1]&feed=$matches[2]\";s:36:\"product/([^/]+)/page/?([0-9]{1,})/?$\";s:47:\"index.php?product=$matches[1]&paged=$matches[2]\";s:43:\"product/([^/]+)/comment-page-([0-9]{1,})/?$\";s:47:\"index.php?product=$matches[1]&cpage=$matches[2]\";s:33:\"product/([^/]+)/wc-api(/(.*))?/?$\";s:48:\"index.php?product=$matches[1]&wc-api=$matches[3]\";s:44:\"product/([^/]+)/wc/file/transient(/(.*))?/?$\";s:59:\"index.php?product=$matches[1]&wc/file/transient=$matches[3]\";s:39:\"product/[^/]+/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:50:\"product/[^/]+/attachment/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:50:\"product/[^/]+/([^/]+)/wc/file/transient(/(.*))?/?$\";s:62:\"index.php?attachment=$matches[1]&wc/file/transient=$matches[3]\";s:61:\"product/[^/]+/attachment/([^/]+)/wc/file/transient(/(.*))?/?$\";s:62:\"index.php?attachment=$matches[1]&wc/file/transient=$matches[3]\";s:32:\"product/([^/]+)(?:/([0-9]+))?/?$\";s:46:\"index.php?product=$matches[1]&page=$matches[2]\";s:24:\"product/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:34:\"product/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:54:\"product/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:49:\"product/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:49:\"product/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:30:\"product/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:12:\"robots\\.txt$\";s:18:\"index.php?robots=1\";s:13:\"favicon\\.ico$\";s:19:\"index.php?favicon=1\";s:48:\".*wp-(atom|rdf|rss|rss2|feed|commentsrss2)\\.php$\";s:18:\"index.php?feed=old\";s:20:\".*wp-app\\.php(/.*)?$\";s:19:\"index.php?error=403\";s:18:\".*wp-register.php$\";s:23:\"index.php?register=true\";s:32:\"feed/(feed|rdf|rss|rss2|atom)/?$\";s:27:\"index.php?&feed=$matches[1]\";s:27:\"(feed|rdf|rss|rss2|atom)/?$\";s:27:\"index.php?&feed=$matches[1]\";s:8:\"embed/?$\";s:21:\"index.php?&embed=true\";s:20:\"page/?([0-9]{1,})/?$\";s:28:\"index.php?&paged=$matches[1]\";s:17:\"wc-api(/(.*))?/?$\";s:29:\"index.php?&wc-api=$matches[2]\";s:28:\"wc/file/transient(/(.*))?/?$\";s:40:\"index.php?&wc/file/transient=$matches[2]\";s:41:\"comments/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?&feed=$matches[1]&withcomments=1\";s:36:\"comments/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?&feed=$matches[1]&withcomments=1\";s:17:\"comments/embed/?$\";s:21:\"index.php?&embed=true\";s:26:\"comments/wc-api(/(.*))?/?$\";s:29:\"index.php?&wc-api=$matches[2]\";s:37:\"comments/wc/file/transient(/(.*))?/?$\";s:40:\"index.php?&wc/file/transient=$matches[2]\";s:44:\"search/(.+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?s=$matches[1]&feed=$matches[2]\";s:39:\"search/(.+)/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?s=$matches[1]&feed=$matches[2]\";s:20:\"search/(.+)/embed/?$\";s:34:\"index.php?s=$matches[1]&embed=true\";s:32:\"search/(.+)/page/?([0-9]{1,})/?$\";s:41:\"index.php?s=$matches[1]&paged=$matches[2]\";s:29:\"search/(.+)/wc-api(/(.*))?/?$\";s:42:\"index.php?s=$matches[1]&wc-api=$matches[3]\";s:40:\"search/(.+)/wc/file/transient(/(.*))?/?$\";s:53:\"index.php?s=$matches[1]&wc/file/transient=$matches[3]\";s:14:\"search/(.+)/?$\";s:23:\"index.php?s=$matches[1]\";s:47:\"author/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?author_name=$matches[1]&feed=$matches[2]\";s:42:\"author/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?author_name=$matches[1]&feed=$matches[2]\";s:23:\"author/([^/]+)/embed/?$\";s:44:\"index.php?author_name=$matches[1]&embed=true\";s:35:\"author/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?author_name=$matches[1]&paged=$matches[2]\";s:32:\"author/([^/]+)/wc-api(/(.*))?/?$\";s:52:\"index.php?author_name=$matches[1]&wc-api=$matches[3]\";s:43:\"author/([^/]+)/wc/file/transient(/(.*))?/?$\";s:63:\"index.php?author_name=$matches[1]&wc/file/transient=$matches[3]\";s:17:\"author/([^/]+)/?$\";s:33:\"index.php?author_name=$matches[1]\";s:69:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]\";s:64:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]\";s:45:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/embed/?$\";s:74:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&embed=true\";s:57:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$\";s:81:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]\";s:54:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/wc-api(/(.*))?/?$\";s:82:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&wc-api=$matches[5]\";s:65:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/wc/file/transient(/(.*))?/?$\";s:93:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&wc/file/transient=$matches[5]\";s:39:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$\";s:63:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]\";s:56:\"([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]\";s:51:\"([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]\";s:32:\"([0-9]{4})/([0-9]{1,2})/embed/?$\";s:58:\"index.php?year=$matches[1]&monthnum=$matches[2]&embed=true\";s:44:\"([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$\";s:65:\"index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]\";s:41:\"([0-9]{4})/([0-9]{1,2})/wc-api(/(.*))?/?$\";s:66:\"index.php?year=$matches[1]&monthnum=$matches[2]&wc-api=$matches[4]\";s:52:\"([0-9]{4})/([0-9]{1,2})/wc/file/transient(/(.*))?/?$\";s:77:\"index.php?year=$matches[1]&monthnum=$matches[2]&wc/file/transient=$matches[4]\";s:26:\"([0-9]{4})/([0-9]{1,2})/?$\";s:47:\"index.php?year=$matches[1]&monthnum=$matches[2]\";s:43:\"([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?year=$matches[1]&feed=$matches[2]\";s:38:\"([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?year=$matches[1]&feed=$matches[2]\";s:19:\"([0-9]{4})/embed/?$\";s:37:\"index.php?year=$matches[1]&embed=true\";s:31:\"([0-9]{4})/page/?([0-9]{1,})/?$\";s:44:\"index.php?year=$matches[1]&paged=$matches[2]\";s:28:\"([0-9]{4})/wc-api(/(.*))?/?$\";s:45:\"index.php?year=$matches[1]&wc-api=$matches[3]\";s:39:\"([0-9]{4})/wc/file/transient(/(.*))?/?$\";s:56:\"index.php?year=$matches[1]&wc/file/transient=$matches[3]\";s:13:\"([0-9]{4})/?$\";s:26:\"index.php?year=$matches[1]\";s:27:\".?.+?/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:37:\".?.+?/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:57:\".?.+?/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\".?.+?/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\".?.+?/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:33:\".?.+?/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:16:\"(.?.+?)/embed/?$\";s:41:\"index.php?pagename=$matches[1]&embed=true\";s:20:\"(.?.+?)/trackback/?$\";s:35:\"index.php?pagename=$matches[1]&tb=1\";s:40:\"(.?.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pagename=$matches[1]&feed=$matches[2]\";s:35:\"(.?.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pagename=$matches[1]&feed=$matches[2]\";s:28:\"(.?.+?)/page/?([0-9]{1,})/?$\";s:48:\"index.php?pagename=$matches[1]&paged=$matches[2]\";s:35:\"(.?.+?)/comment-page-([0-9]{1,})/?$\";s:48:\"index.php?pagename=$matches[1]&cpage=$matches[2]\";s:25:\"(.?.+?)/wc-api(/(.*))?/?$\";s:49:\"index.php?pagename=$matches[1]&wc-api=$matches[3]\";s:36:\"(.?.+?)/wc/file/transient(/(.*))?/?$\";s:60:\"index.php?pagename=$matches[1]&wc/file/transient=$matches[3]\";s:28:\"(.?.+?)/order-pay(/(.*))?/?$\";s:52:\"index.php?pagename=$matches[1]&order-pay=$matches[3]\";s:33:\"(.?.+?)/order-received(/(.*))?/?$\";s:57:\"index.php?pagename=$matches[1]&order-received=$matches[3]\";s:25:\"(.?.+?)/orders(/(.*))?/?$\";s:49:\"index.php?pagename=$matches[1]&orders=$matches[3]\";s:29:\"(.?.+?)/view-order(/(.*))?/?$\";s:53:\"index.php?pagename=$matches[1]&view-order=$matches[3]\";s:28:\"(.?.+?)/downloads(/(.*))?/?$\";s:52:\"index.php?pagename=$matches[1]&downloads=$matches[3]\";s:31:\"(.?.+?)/edit-account(/(.*))?/?$\";s:55:\"index.php?pagename=$matches[1]&edit-account=$matches[3]\";s:31:\"(.?.+?)/edit-address(/(.*))?/?$\";s:55:\"index.php?pagename=$matches[1]&edit-address=$matches[3]\";s:34:\"(.?.+?)/payment-methods(/(.*))?/?$\";s:58:\"index.php?pagename=$matches[1]&payment-methods=$matches[3]\";s:32:\"(.?.+?)/lost-password(/(.*))?/?$\";s:56:\"index.php?pagename=$matches[1]&lost-password=$matches[3]\";s:34:\"(.?.+?)/customer-logout(/(.*))?/?$\";s:58:\"index.php?pagename=$matches[1]&customer-logout=$matches[3]\";s:37:\"(.?.+?)/add-payment-method(/(.*))?/?$\";s:61:\"index.php?pagename=$matches[1]&add-payment-method=$matches[3]\";s:40:\"(.?.+?)/delete-payment-method(/(.*))?/?$\";s:64:\"index.php?pagename=$matches[1]&delete-payment-method=$matches[3]\";s:45:\"(.?.+?)/set-default-payment-method(/(.*))?/?$\";s:69:\"index.php?pagename=$matches[1]&set-default-payment-method=$matches[3]\";s:31:\".?.+?/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:42:\".?.+?/attachment/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:42:\".?.+?/([^/]+)/wc/file/transient(/(.*))?/?$\";s:62:\"index.php?attachment=$matches[1]&wc/file/transient=$matches[3]\";s:53:\".?.+?/attachment/([^/]+)/wc/file/transient(/(.*))?/?$\";s:62:\"index.php?attachment=$matches[1]&wc/file/transient=$matches[3]\";s:24:\"(.?.+?)(?:/([0-9]+))?/?$\";s:47:\"index.php?pagename=$matches[1]&page=$matches[2]\";s:27:\"[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:37:\"[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:57:\"[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\"[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\"[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:33:\"[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:16:\"([^/]+)/embed/?$\";s:37:\"index.php?name=$matches[1]&embed=true\";s:20:\"([^/]+)/trackback/?$\";s:31:\"index.php?name=$matches[1]&tb=1\";s:40:\"([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?name=$matches[1]&feed=$matches[2]\";s:35:\"([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?name=$matches[1]&feed=$matches[2]\";s:28:\"([^/]+)/page/?([0-9]{1,})/?$\";s:44:\"index.php?name=$matches[1]&paged=$matches[2]\";s:35:\"([^/]+)/comment-page-([0-9]{1,})/?$\";s:44:\"index.php?name=$matches[1]&cpage=$matches[2]\";s:25:\"([^/]+)/wc-api(/(.*))?/?$\";s:45:\"index.php?name=$matches[1]&wc-api=$matches[3]\";s:36:\"([^/]+)/wc/file/transient(/(.*))?/?$\";s:56:\"index.php?name=$matches[1]&wc/file/transient=$matches[3]\";s:31:\"[^/]+/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:42:\"[^/]+/attachment/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:42:\"[^/]+/([^/]+)/wc/file/transient(/(.*))?/?$\";s:62:\"index.php?attachment=$matches[1]&wc/file/transient=$matches[3]\";s:53:\"[^/]+/attachment/([^/]+)/wc/file/transient(/(.*))?/?$\";s:62:\"index.php?attachment=$matches[1]&wc/file/transient=$matches[3]\";s:24:\"([^/]+)(?:/([0-9]+))?/?$\";s:43:\"index.php?name=$matches[1]&page=$matches[2]\";s:16:\"[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:26:\"[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:46:\"[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:41:\"[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:41:\"[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:22:\"[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";}','yes'),
(30,'hack_file','0','yes'),
(31,'blog_charset','UTF-8','yes'),
(32,'moderation_keys','','no'),
(33,'active_plugins','a:4:{i:0;s:21:\"master/basic-auth.php\";i:1;s:35:\"woo-test-helper/woo-test-helper.php\";i:2;s:27:\"woocommerce/woocommerce.php\";i:3;s:41:\"wordpress-importer/wordpress-importer.php\";}','yes'),
(34,'category_base','','yes'),
(35,'ping_sites','http://rpc.pingomatic.com/','yes'),
(36,'comment_max_links','2','yes'),
(37,'gmt_offset','0','yes'),
(38,'default_email_category','1','yes'),
(39,'recently_edited','','no'),
(40,'template','twentytwentyfour','yes'),
(41,'stylesheet','twentytwentyfour','yes'),
(42,'comment_registration','0','yes'),
(43,'html_type','text/html','yes'),
(44,'use_trackback','0','yes'),
(45,'default_role','subscriber','yes'),
(46,'db_version','57155','yes'),
(47,'uploads_use_yearmonth_folders','1','yes'),
(48,'upload_path','','yes'),
(49,'blog_public','1','yes'),
(50,'default_link_category','2','yes'),
(51,'show_on_front','posts','yes'),
(52,'tag_base','','yes'),
(53,'show_avatars','1','yes'),
(54,'avatar_rating','G','yes'),
(55,'upload_url_path','','yes'),
(56,'thumbnail_size_w','150','yes'),
(57,'thumbnail_size_h','150','yes'),
(58,'thumbnail_crop','1','yes'),
(59,'medium_size_w','300','yes'),
(60,'medium_size_h','300','yes'),
(61,'avatar_default','mystery','yes'),
(62,'large_size_w','1024','yes'),
(63,'large_size_h','1024','yes'),
(64,'image_default_link_type','none','yes'),
(65,'image_default_size','','yes'),
(66,'image_default_align','','yes'),
(67,'close_comments_for_old_posts','0','yes'),
(68,'close_comments_days_old','14','yes'),
(69,'thread_comments','1','yes'),
(70,'thread_comments_depth','5','yes'),
(71,'page_comments','0','yes'),
(72,'comments_per_page','50','yes'),
(73,'default_comments_page','newest','yes'),
(74,'comment_order','asc','yes'),
(75,'sticky_posts','a:0:{}','yes'),
(76,'widget_categories','a:0:{}','yes'),
(77,'widget_text','a:0:{}','yes'),
(78,'widget_rss','a:0:{}','yes'),
(79,'uninstall_plugins','a:0:{}','no'),
(80,'timezone_string','','yes'),
(81,'page_for_posts','0','yes'),
(82,'page_on_front','0','yes'),
(83,'default_post_format','0','yes'),
(84,'link_manager_enabled','0','yes'),
(85,'finished_splitting_shared_terms','1','yes'),
(86,'site_icon','0','yes'),
(87,'medium_large_size_w','768','yes'),
(88,'medium_large_size_h','0','yes'),
(89,'wp_page_for_privacy_policy','60','yes'),
(90,'show_comments_cookies_opt_in','1','yes'),
(91,'admin_email_lifespan','1728202355','yes'),
(92,'disallowed_keys','','no'),
(93,'comment_previously_approved','1','yes'),
(94,'auto_plugin_theme_update_emails','a:0:{}','no'),
(95,'auto_update_core_dev','enabled','yes'),
(96,'auto_update_core_minor','enabled','yes'),
(97,'auto_update_core_major','enabled','yes'),
(98,'wp_force_deactivated_plugins','a:0:{}','yes'),
(99,'wp_attachment_pages_enabled','0','yes'),
(100,'initial_db_version','57155','yes'),
(101,'wp_user_roles','a:7:{s:13:\"administrator\";a:2:{s:4:\"name\";s:13:\"Administrator\";s:12:\"capabilities\";a:114:{s:13:\"switch_themes\";b:1;s:11:\"edit_themes\";b:1;s:16:\"activate_plugins\";b:1;s:12:\"edit_plugins\";b:1;s:10:\"edit_users\";b:1;s:10:\"edit_files\";b:1;s:14:\"manage_options\";b:1;s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:6:\"import\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:8:\"level_10\";b:1;s:7:\"level_9\";b:1;s:7:\"level_8\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;s:12:\"delete_users\";b:1;s:12:\"create_users\";b:1;s:17:\"unfiltered_upload\";b:1;s:14:\"edit_dashboard\";b:1;s:14:\"update_plugins\";b:1;s:14:\"delete_plugins\";b:1;s:15:\"install_plugins\";b:1;s:13:\"update_themes\";b:1;s:14:\"install_themes\";b:1;s:11:\"update_core\";b:1;s:10:\"list_users\";b:1;s:12:\"remove_users\";b:1;s:13:\"promote_users\";b:1;s:18:\"edit_theme_options\";b:1;s:13:\"delete_themes\";b:1;s:6:\"export\";b:1;s:18:\"manage_woocommerce\";b:1;s:24:\"view_woocommerce_reports\";b:1;s:12:\"edit_product\";b:1;s:12:\"read_product\";b:1;s:14:\"delete_product\";b:1;s:13:\"edit_products\";b:1;s:20:\"edit_others_products\";b:1;s:16:\"publish_products\";b:1;s:21:\"read_private_products\";b:1;s:15:\"delete_products\";b:1;s:23:\"delete_private_products\";b:1;s:25:\"delete_published_products\";b:1;s:22:\"delete_others_products\";b:1;s:21:\"edit_private_products\";b:1;s:23:\"edit_published_products\";b:1;s:20:\"manage_product_terms\";b:1;s:18:\"edit_product_terms\";b:1;s:20:\"delete_product_terms\";b:1;s:20:\"assign_product_terms\";b:1;s:15:\"edit_shop_order\";b:1;s:15:\"read_shop_order\";b:1;s:17:\"delete_shop_order\";b:1;s:16:\"edit_shop_orders\";b:1;s:23:\"edit_others_shop_orders\";b:1;s:19:\"publish_shop_orders\";b:1;s:24:\"read_private_shop_orders\";b:1;s:18:\"delete_shop_orders\";b:1;s:26:\"delete_private_shop_orders\";b:1;s:28:\"delete_published_shop_orders\";b:1;s:25:\"delete_others_shop_orders\";b:1;s:24:\"edit_private_shop_orders\";b:1;s:26:\"edit_published_shop_orders\";b:1;s:23:\"manage_shop_order_terms\";b:1;s:21:\"edit_shop_order_terms\";b:1;s:23:\"delete_shop_order_terms\";b:1;s:23:\"assign_shop_order_terms\";b:1;s:16:\"edit_shop_coupon\";b:1;s:16:\"read_shop_coupon\";b:1;s:18:\"delete_shop_coupon\";b:1;s:17:\"edit_shop_coupons\";b:1;s:24:\"edit_others_shop_coupons\";b:1;s:20:\"publish_shop_coupons\";b:1;s:25:\"read_private_shop_coupons\";b:1;s:19:\"delete_shop_coupons\";b:1;s:27:\"delete_private_shop_coupons\";b:1;s:29:\"delete_published_shop_coupons\";b:1;s:26:\"delete_others_shop_coupons\";b:1;s:25:\"edit_private_shop_coupons\";b:1;s:27:\"edit_published_shop_coupons\";b:1;s:24:\"manage_shop_coupon_terms\";b:1;s:22:\"edit_shop_coupon_terms\";b:1;s:24:\"delete_shop_coupon_terms\";b:1;s:24:\"assign_shop_coupon_terms\";b:1;}}s:6:\"editor\";a:2:{s:4:\"name\";s:6:\"Editor\";s:12:\"capabilities\";a:34:{s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;}}s:6:\"author\";a:2:{s:4:\"name\";s:6:\"Author\";s:12:\"capabilities\";a:10:{s:12:\"upload_files\";b:1;s:10:\"edit_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;s:22:\"delete_published_posts\";b:1;}}s:11:\"contributor\";a:2:{s:4:\"name\";s:11:\"Contributor\";s:12:\"capabilities\";a:5:{s:10:\"edit_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;}}s:10:\"subscriber\";a:2:{s:4:\"name\";s:10:\"Subscriber\";s:12:\"capabilities\";a:2:{s:4:\"read\";b:1;s:7:\"level_0\";b:1;}}s:8:\"customer\";a:2:{s:4:\"name\";s:8:\"Customer\";s:12:\"capabilities\";a:1:{s:4:\"read\";b:1;}}s:12:\"shop_manager\";a:2:{s:4:\"name\";s:12:\"Shop manager\";s:12:\"capabilities\";a:92:{s:7:\"level_9\";b:1;s:7:\"level_8\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:4:\"read\";b:1;s:18:\"read_private_pages\";b:1;s:18:\"read_private_posts\";b:1;s:10:\"edit_posts\";b:1;s:10:\"edit_pages\";b:1;s:20:\"edit_published_posts\";b:1;s:20:\"edit_published_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"edit_private_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:17:\"edit_others_pages\";b:1;s:13:\"publish_posts\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_posts\";b:1;s:12:\"delete_pages\";b:1;s:20:\"delete_private_pages\";b:1;s:20:\"delete_private_posts\";b:1;s:22:\"delete_published_pages\";b:1;s:22:\"delete_published_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:19:\"delete_others_pages\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:17:\"moderate_comments\";b:1;s:12:\"upload_files\";b:1;s:6:\"export\";b:1;s:6:\"import\";b:1;s:10:\"list_users\";b:1;s:18:\"edit_theme_options\";b:1;s:18:\"manage_woocommerce\";b:1;s:24:\"view_woocommerce_reports\";b:1;s:12:\"edit_product\";b:1;s:12:\"read_product\";b:1;s:14:\"delete_product\";b:1;s:13:\"edit_products\";b:1;s:20:\"edit_others_products\";b:1;s:16:\"publish_products\";b:1;s:21:\"read_private_products\";b:1;s:15:\"delete_products\";b:1;s:23:\"delete_private_products\";b:1;s:25:\"delete_published_products\";b:1;s:22:\"delete_others_products\";b:1;s:21:\"edit_private_products\";b:1;s:23:\"edit_published_products\";b:1;s:20:\"manage_product_terms\";b:1;s:18:\"edit_product_terms\";b:1;s:20:\"delete_product_terms\";b:1;s:20:\"assign_product_terms\";b:1;s:15:\"edit_shop_order\";b:1;s:15:\"read_shop_order\";b:1;s:17:\"delete_shop_order\";b:1;s:16:\"edit_shop_orders\";b:1;s:23:\"edit_others_shop_orders\";b:1;s:19:\"publish_shop_orders\";b:1;s:24:\"read_private_shop_orders\";b:1;s:18:\"delete_shop_orders\";b:1;s:26:\"delete_private_shop_orders\";b:1;s:28:\"delete_published_shop_orders\";b:1;s:25:\"delete_others_shop_orders\";b:1;s:24:\"edit_private_shop_orders\";b:1;s:26:\"edit_published_shop_orders\";b:1;s:23:\"manage_shop_order_terms\";b:1;s:21:\"edit_shop_order_terms\";b:1;s:23:\"delete_shop_order_terms\";b:1;s:23:\"assign_shop_order_terms\";b:1;s:16:\"edit_shop_coupon\";b:1;s:16:\"read_shop_coupon\";b:1;s:18:\"delete_shop_coupon\";b:1;s:17:\"edit_shop_coupons\";b:1;s:24:\"edit_others_shop_coupons\";b:1;s:20:\"publish_shop_coupons\";b:1;s:25:\"read_private_shop_coupons\";b:1;s:19:\"delete_shop_coupons\";b:1;s:27:\"delete_private_shop_coupons\";b:1;s:29:\"delete_published_shop_coupons\";b:1;s:26:\"delete_others_shop_coupons\";b:1;s:25:\"edit_private_shop_coupons\";b:1;s:27:\"edit_published_shop_coupons\";b:1;s:24:\"manage_shop_coupon_terms\";b:1;s:22:\"edit_shop_coupon_terms\";b:1;s:24:\"delete_shop_coupon_terms\";b:1;s:24:\"assign_shop_coupon_terms\";b:1;}}}','yes'),
(102,'fresh_site','0','yes'),
(103,'user_count','2','no'),
(104,'widget_block','a:6:{i:2;a:1:{s:7:\"content\";s:19:\"<!-- wp:search /-->\";}i:3;a:1:{s:7:\"content\";s:154:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Recent Posts</h2><!-- /wp:heading --><!-- wp:latest-posts /--></div><!-- /wp:group -->\";}i:4;a:1:{s:7:\"content\";s:227:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Recent Comments</h2><!-- /wp:heading --><!-- wp:latest-comments {\"displayAvatar\":false,\"displayDate\":false,\"displayExcerpt\":false} /--></div><!-- /wp:group -->\";}i:5;a:1:{s:7:\"content\";s:146:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Archives</h2><!-- /wp:heading --><!-- wp:archives /--></div><!-- /wp:group -->\";}i:6;a:1:{s:7:\"content\";s:150:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Categories</h2><!-- /wp:heading --><!-- wp:categories /--></div><!-- /wp:group -->\";}s:12:\"_multiwidget\";i:1;}','yes'),
(105,'sidebars_widgets','a:4:{s:19:\"wp_inactive_widgets\";a:0:{}s:9:\"sidebar-1\";a:3:{i:0;s:7:\"block-2\";i:1;s:7:\"block-3\";i:2;s:7:\"block-4\";}s:9:\"sidebar-2\";a:2:{i:0;s:7:\"block-5\";i:1;s:7:\"block-6\";}s:13:\"array_version\";i:3;}','yes'),
(106,'cron','a:14:{i:1712650356;a:5:{s:32:\"recovery_mode_clean_expired_keys\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:34:\"wp_privacy_delete_old_export_files\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}s:16:\"wp_version_check\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}s:17:\"wp_update_plugins\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}s:16:\"wp_update_themes\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1712650357;a:2:{s:26:\"action_scheduler_run_queue\";a:1:{s:32:\"0d04ed39571b55704c122d726248bbac\";a:3:{s:8:\"schedule\";s:12:\"every_minute\";s:4:\"args\";a:1:{i:0;s:7:\"WP Cron\";}s:8:\"interval\";i:60;}}s:14:\"wc_admin_daily\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1712650359;a:3:{s:20:\"jetpack_clean_nonces\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}s:20:\"jetpack_v2_heartbeat\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:33:\"wc_admin_process_orders_milestone\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}}i:1712650360;a:1:{s:31:\"woocommerce_flush_rewrite_rules\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:2:{s:8:\"schedule\";b:0;s:4:\"args\";a:0:{}}}}i:1712650367;a:3:{s:33:\"woocommerce_cleanup_personal_data\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:30:\"woocommerce_tracker_send_event\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:30:\"generate_category_lookup_table\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:2:{s:8:\"schedule\";b:0;s:4:\"args\";a:0:{}}}}i:1712650386;a:1:{s:8:\"do_pings\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:2:{s:8:\"schedule\";b:0;s:4:\"args\";a:0:{}}}}i:1712650392;a:1:{s:30:\"wp_delete_temp_updater_backups\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"weekly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:604800;}}}i:1712650417;a:1:{s:25:\"woocommerce_geoip_updater\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:11:\"fifteendays\";s:4:\"args\";a:0:{}s:8:\"interval\";i:1296000;}}}i:1712653957;a:1:{s:32:\"woocommerce_cancel_unpaid_orders\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:2:{s:8:\"schedule\";b:0;s:4:\"args\";a:0:{}}}}i:1712661157;a:2:{s:24:\"woocommerce_cleanup_logs\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:31:\"woocommerce_cleanup_rate_limits\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1712671957;a:1:{s:28:\"woocommerce_cleanup_sessions\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1712707200;a:1:{s:27:\"woocommerce_scheduled_sales\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1712736756;a:1:{s:30:\"wp_site_health_scheduled_check\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"weekly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:604800;}}}s:7:\"version\";i:2;}','yes'),
(107,'widget_pages','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(108,'widget_calendar','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(109,'widget_archives','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(110,'widget_media_audio','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(111,'widget_media_image','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(112,'widget_media_gallery','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(113,'widget_media_video','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(114,'widget_meta','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(115,'widget_search','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(116,'widget_recent-posts','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(117,'widget_recent-comments','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(118,'widget_tag_cloud','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(119,'widget_nav_menu','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(120,'widget_custom_html','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(121,'_transient_wp_core_block_css_files','a:2:{s:7:\"version\";s:7:\"6.5-RC4\";s:5:\"files\";a:500:{i:0;s:23:\"archives/editor-rtl.css\";i:1;s:27:\"archives/editor-rtl.min.css\";i:2;s:19:\"archives/editor.css\";i:3;s:23:\"archives/editor.min.css\";i:4;s:22:\"archives/style-rtl.css\";i:5;s:26:\"archives/style-rtl.min.css\";i:6;s:18:\"archives/style.css\";i:7;s:22:\"archives/style.min.css\";i:8;s:20:\"audio/editor-rtl.css\";i:9;s:24:\"audio/editor-rtl.min.css\";i:10;s:16:\"audio/editor.css\";i:11;s:20:\"audio/editor.min.css\";i:12;s:19:\"audio/style-rtl.css\";i:13;s:23:\"audio/style-rtl.min.css\";i:14;s:15:\"audio/style.css\";i:15;s:19:\"audio/style.min.css\";i:16;s:19:\"audio/theme-rtl.css\";i:17;s:23:\"audio/theme-rtl.min.css\";i:18;s:15:\"audio/theme.css\";i:19;s:19:\"audio/theme.min.css\";i:20;s:21:\"avatar/editor-rtl.css\";i:21;s:25:\"avatar/editor-rtl.min.css\";i:22;s:17:\"avatar/editor.css\";i:23;s:21:\"avatar/editor.min.css\";i:24;s:20:\"avatar/style-rtl.css\";i:25;s:24:\"avatar/style-rtl.min.css\";i:26;s:16:\"avatar/style.css\";i:27;s:20:\"avatar/style.min.css\";i:28;s:20:\"block/editor-rtl.css\";i:29;s:24:\"block/editor-rtl.min.css\";i:30;s:16:\"block/editor.css\";i:31;s:20:\"block/editor.min.css\";i:32;s:21:\"button/editor-rtl.css\";i:33;s:25:\"button/editor-rtl.min.css\";i:34;s:17:\"button/editor.css\";i:35;s:21:\"button/editor.min.css\";i:36;s:20:\"button/style-rtl.css\";i:37;s:24:\"button/style-rtl.min.css\";i:38;s:16:\"button/style.css\";i:39;s:20:\"button/style.min.css\";i:40;s:22:\"buttons/editor-rtl.css\";i:41;s:26:\"buttons/editor-rtl.min.css\";i:42;s:18:\"buttons/editor.css\";i:43;s:22:\"buttons/editor.min.css\";i:44;s:21:\"buttons/style-rtl.css\";i:45;s:25:\"buttons/style-rtl.min.css\";i:46;s:17:\"buttons/style.css\";i:47;s:21:\"buttons/style.min.css\";i:48;s:22:\"calendar/style-rtl.css\";i:49;s:26:\"calendar/style-rtl.min.css\";i:50;s:18:\"calendar/style.css\";i:51;s:22:\"calendar/style.min.css\";i:52;s:25:\"categories/editor-rtl.css\";i:53;s:29:\"categories/editor-rtl.min.css\";i:54;s:21:\"categories/editor.css\";i:55;s:25:\"categories/editor.min.css\";i:56;s:24:\"categories/style-rtl.css\";i:57;s:28:\"categories/style-rtl.min.css\";i:58;s:20:\"categories/style.css\";i:59;s:24:\"categories/style.min.css\";i:60;s:19:\"code/editor-rtl.css\";i:61;s:23:\"code/editor-rtl.min.css\";i:62;s:15:\"code/editor.css\";i:63;s:19:\"code/editor.min.css\";i:64;s:18:\"code/style-rtl.css\";i:65;s:22:\"code/style-rtl.min.css\";i:66;s:14:\"code/style.css\";i:67;s:18:\"code/style.min.css\";i:68;s:18:\"code/theme-rtl.css\";i:69;s:22:\"code/theme-rtl.min.css\";i:70;s:14:\"code/theme.css\";i:71;s:18:\"code/theme.min.css\";i:72;s:22:\"columns/editor-rtl.css\";i:73;s:26:\"columns/editor-rtl.min.css\";i:74;s:18:\"columns/editor.css\";i:75;s:22:\"columns/editor.min.css\";i:76;s:21:\"columns/style-rtl.css\";i:77;s:25:\"columns/style-rtl.min.css\";i:78;s:17:\"columns/style.css\";i:79;s:21:\"columns/style.min.css\";i:80;s:29:\"comment-content/style-rtl.css\";i:81;s:33:\"comment-content/style-rtl.min.css\";i:82;s:25:\"comment-content/style.css\";i:83;s:29:\"comment-content/style.min.css\";i:84;s:30:\"comment-template/style-rtl.css\";i:85;s:34:\"comment-template/style-rtl.min.css\";i:86;s:26:\"comment-template/style.css\";i:87;s:30:\"comment-template/style.min.css\";i:88;s:42:\"comments-pagination-numbers/editor-rtl.css\";i:89;s:46:\"comments-pagination-numbers/editor-rtl.min.css\";i:90;s:38:\"comments-pagination-numbers/editor.css\";i:91;s:42:\"comments-pagination-numbers/editor.min.css\";i:92;s:34:\"comments-pagination/editor-rtl.css\";i:93;s:38:\"comments-pagination/editor-rtl.min.css\";i:94;s:30:\"comments-pagination/editor.css\";i:95;s:34:\"comments-pagination/editor.min.css\";i:96;s:33:\"comments-pagination/style-rtl.css\";i:97;s:37:\"comments-pagination/style-rtl.min.css\";i:98;s:29:\"comments-pagination/style.css\";i:99;s:33:\"comments-pagination/style.min.css\";i:100;s:29:\"comments-title/editor-rtl.css\";i:101;s:33:\"comments-title/editor-rtl.min.css\";i:102;s:25:\"comments-title/editor.css\";i:103;s:29:\"comments-title/editor.min.css\";i:104;s:23:\"comments/editor-rtl.css\";i:105;s:27:\"comments/editor-rtl.min.css\";i:106;s:19:\"comments/editor.css\";i:107;s:23:\"comments/editor.min.css\";i:108;s:22:\"comments/style-rtl.css\";i:109;s:26:\"comments/style-rtl.min.css\";i:110;s:18:\"comments/style.css\";i:111;s:22:\"comments/style.min.css\";i:112;s:20:\"cover/editor-rtl.css\";i:113;s:24:\"cover/editor-rtl.min.css\";i:114;s:16:\"cover/editor.css\";i:115;s:20:\"cover/editor.min.css\";i:116;s:19:\"cover/style-rtl.css\";i:117;s:23:\"cover/style-rtl.min.css\";i:118;s:15:\"cover/style.css\";i:119;s:19:\"cover/style.min.css\";i:120;s:22:\"details/editor-rtl.css\";i:121;s:26:\"details/editor-rtl.min.css\";i:122;s:18:\"details/editor.css\";i:123;s:22:\"details/editor.min.css\";i:124;s:21:\"details/style-rtl.css\";i:125;s:25:\"details/style-rtl.min.css\";i:126;s:17:\"details/style.css\";i:127;s:21:\"details/style.min.css\";i:128;s:20:\"embed/editor-rtl.css\";i:129;s:24:\"embed/editor-rtl.min.css\";i:130;s:16:\"embed/editor.css\";i:131;s:20:\"embed/editor.min.css\";i:132;s:19:\"embed/style-rtl.css\";i:133;s:23:\"embed/style-rtl.min.css\";i:134;s:15:\"embed/style.css\";i:135;s:19:\"embed/style.min.css\";i:136;s:19:\"embed/theme-rtl.css\";i:137;s:23:\"embed/theme-rtl.min.css\";i:138;s:15:\"embed/theme.css\";i:139;s:19:\"embed/theme.min.css\";i:140;s:19:\"file/editor-rtl.css\";i:141;s:23:\"file/editor-rtl.min.css\";i:142;s:15:\"file/editor.css\";i:143;s:19:\"file/editor.min.css\";i:144;s:18:\"file/style-rtl.css\";i:145;s:22:\"file/style-rtl.min.css\";i:146;s:14:\"file/style.css\";i:147;s:18:\"file/style.min.css\";i:148;s:23:\"footnotes/style-rtl.css\";i:149;s:27:\"footnotes/style-rtl.min.css\";i:150;s:19:\"footnotes/style.css\";i:151;s:23:\"footnotes/style.min.css\";i:152;s:23:\"freeform/editor-rtl.css\";i:153;s:27:\"freeform/editor-rtl.min.css\";i:154;s:19:\"freeform/editor.css\";i:155;s:23:\"freeform/editor.min.css\";i:156;s:22:\"gallery/editor-rtl.css\";i:157;s:26:\"gallery/editor-rtl.min.css\";i:158;s:18:\"gallery/editor.css\";i:159;s:22:\"gallery/editor.min.css\";i:160;s:21:\"gallery/style-rtl.css\";i:161;s:25:\"gallery/style-rtl.min.css\";i:162;s:17:\"gallery/style.css\";i:163;s:21:\"gallery/style.min.css\";i:164;s:21:\"gallery/theme-rtl.css\";i:165;s:25:\"gallery/theme-rtl.min.css\";i:166;s:17:\"gallery/theme.css\";i:167;s:21:\"gallery/theme.min.css\";i:168;s:20:\"group/editor-rtl.css\";i:169;s:24:\"group/editor-rtl.min.css\";i:170;s:16:\"group/editor.css\";i:171;s:20:\"group/editor.min.css\";i:172;s:19:\"group/style-rtl.css\";i:173;s:23:\"group/style-rtl.min.css\";i:174;s:15:\"group/style.css\";i:175;s:19:\"group/style.min.css\";i:176;s:19:\"group/theme-rtl.css\";i:177;s:23:\"group/theme-rtl.min.css\";i:178;s:15:\"group/theme.css\";i:179;s:19:\"group/theme.min.css\";i:180;s:21:\"heading/style-rtl.css\";i:181;s:25:\"heading/style-rtl.min.css\";i:182;s:17:\"heading/style.css\";i:183;s:21:\"heading/style.min.css\";i:184;s:19:\"html/editor-rtl.css\";i:185;s:23:\"html/editor-rtl.min.css\";i:186;s:15:\"html/editor.css\";i:187;s:19:\"html/editor.min.css\";i:188;s:20:\"image/editor-rtl.css\";i:189;s:24:\"image/editor-rtl.min.css\";i:190;s:16:\"image/editor.css\";i:191;s:20:\"image/editor.min.css\";i:192;s:19:\"image/style-rtl.css\";i:193;s:23:\"image/style-rtl.min.css\";i:194;s:15:\"image/style.css\";i:195;s:19:\"image/style.min.css\";i:196;s:19:\"image/theme-rtl.css\";i:197;s:23:\"image/theme-rtl.min.css\";i:198;s:15:\"image/theme.css\";i:199;s:19:\"image/theme.min.css\";i:200;s:29:\"latest-comments/style-rtl.css\";i:201;s:33:\"latest-comments/style-rtl.min.css\";i:202;s:25:\"latest-comments/style.css\";i:203;s:29:\"latest-comments/style.min.css\";i:204;s:27:\"latest-posts/editor-rtl.css\";i:205;s:31:\"latest-posts/editor-rtl.min.css\";i:206;s:23:\"latest-posts/editor.css\";i:207;s:27:\"latest-posts/editor.min.css\";i:208;s:26:\"latest-posts/style-rtl.css\";i:209;s:30:\"latest-posts/style-rtl.min.css\";i:210;s:22:\"latest-posts/style.css\";i:211;s:26:\"latest-posts/style.min.css\";i:212;s:18:\"list/style-rtl.css\";i:213;s:22:\"list/style-rtl.min.css\";i:214;s:14:\"list/style.css\";i:215;s:18:\"list/style.min.css\";i:216;s:25:\"media-text/editor-rtl.css\";i:217;s:29:\"media-text/editor-rtl.min.css\";i:218;s:21:\"media-text/editor.css\";i:219;s:25:\"media-text/editor.min.css\";i:220;s:24:\"media-text/style-rtl.css\";i:221;s:28:\"media-text/style-rtl.min.css\";i:222;s:20:\"media-text/style.css\";i:223;s:24:\"media-text/style.min.css\";i:224;s:19:\"more/editor-rtl.css\";i:225;s:23:\"more/editor-rtl.min.css\";i:226;s:15:\"more/editor.css\";i:227;s:19:\"more/editor.min.css\";i:228;s:30:\"navigation-link/editor-rtl.css\";i:229;s:34:\"navigation-link/editor-rtl.min.css\";i:230;s:26:\"navigation-link/editor.css\";i:231;s:30:\"navigation-link/editor.min.css\";i:232;s:29:\"navigation-link/style-rtl.css\";i:233;s:33:\"navigation-link/style-rtl.min.css\";i:234;s:25:\"navigation-link/style.css\";i:235;s:29:\"navigation-link/style.min.css\";i:236;s:33:\"navigation-submenu/editor-rtl.css\";i:237;s:37:\"navigation-submenu/editor-rtl.min.css\";i:238;s:29:\"navigation-submenu/editor.css\";i:239;s:33:\"navigation-submenu/editor.min.css\";i:240;s:25:\"navigation/editor-rtl.css\";i:241;s:29:\"navigation/editor-rtl.min.css\";i:242;s:21:\"navigation/editor.css\";i:243;s:25:\"navigation/editor.min.css\";i:244;s:24:\"navigation/style-rtl.css\";i:245;s:28:\"navigation/style-rtl.min.css\";i:246;s:20:\"navigation/style.css\";i:247;s:24:\"navigation/style.min.css\";i:248;s:23:\"nextpage/editor-rtl.css\";i:249;s:27:\"nextpage/editor-rtl.min.css\";i:250;s:19:\"nextpage/editor.css\";i:251;s:23:\"nextpage/editor.min.css\";i:252;s:24:\"page-list/editor-rtl.css\";i:253;s:28:\"page-list/editor-rtl.min.css\";i:254;s:20:\"page-list/editor.css\";i:255;s:24:\"page-list/editor.min.css\";i:256;s:23:\"page-list/style-rtl.css\";i:257;s:27:\"page-list/style-rtl.min.css\";i:258;s:19:\"page-list/style.css\";i:259;s:23:\"page-list/style.min.css\";i:260;s:24:\"paragraph/editor-rtl.css\";i:261;s:28:\"paragraph/editor-rtl.min.css\";i:262;s:20:\"paragraph/editor.css\";i:263;s:24:\"paragraph/editor.min.css\";i:264;s:23:\"paragraph/style-rtl.css\";i:265;s:27:\"paragraph/style-rtl.min.css\";i:266;s:19:\"paragraph/style.css\";i:267;s:23:\"paragraph/style.min.css\";i:268;s:25:\"post-author/style-rtl.css\";i:269;s:29:\"post-author/style-rtl.min.css\";i:270;s:21:\"post-author/style.css\";i:271;s:25:\"post-author/style.min.css\";i:272;s:33:\"post-comments-form/editor-rtl.css\";i:273;s:37:\"post-comments-form/editor-rtl.min.css\";i:274;s:29:\"post-comments-form/editor.css\";i:275;s:33:\"post-comments-form/editor.min.css\";i:276;s:32:\"post-comments-form/style-rtl.css\";i:277;s:36:\"post-comments-form/style-rtl.min.css\";i:278;s:28:\"post-comments-form/style.css\";i:279;s:32:\"post-comments-form/style.min.css\";i:280;s:27:\"post-content/editor-rtl.css\";i:281;s:31:\"post-content/editor-rtl.min.css\";i:282;s:23:\"post-content/editor.css\";i:283;s:27:\"post-content/editor.min.css\";i:284;s:23:\"post-date/style-rtl.css\";i:285;s:27:\"post-date/style-rtl.min.css\";i:286;s:19:\"post-date/style.css\";i:287;s:23:\"post-date/style.min.css\";i:288;s:27:\"post-excerpt/editor-rtl.css\";i:289;s:31:\"post-excerpt/editor-rtl.min.css\";i:290;s:23:\"post-excerpt/editor.css\";i:291;s:27:\"post-excerpt/editor.min.css\";i:292;s:26:\"post-excerpt/style-rtl.css\";i:293;s:30:\"post-excerpt/style-rtl.min.css\";i:294;s:22:\"post-excerpt/style.css\";i:295;s:26:\"post-excerpt/style.min.css\";i:296;s:34:\"post-featured-image/editor-rtl.css\";i:297;s:38:\"post-featured-image/editor-rtl.min.css\";i:298;s:30:\"post-featured-image/editor.css\";i:299;s:34:\"post-featured-image/editor.min.css\";i:300;s:33:\"post-featured-image/style-rtl.css\";i:301;s:37:\"post-featured-image/style-rtl.min.css\";i:302;s:29:\"post-featured-image/style.css\";i:303;s:33:\"post-featured-image/style.min.css\";i:304;s:34:\"post-navigation-link/style-rtl.css\";i:305;s:38:\"post-navigation-link/style-rtl.min.css\";i:306;s:30:\"post-navigation-link/style.css\";i:307;s:34:\"post-navigation-link/style.min.css\";i:308;s:28:\"post-template/editor-rtl.css\";i:309;s:32:\"post-template/editor-rtl.min.css\";i:310;s:24:\"post-template/editor.css\";i:311;s:28:\"post-template/editor.min.css\";i:312;s:27:\"post-template/style-rtl.css\";i:313;s:31:\"post-template/style-rtl.min.css\";i:314;s:23:\"post-template/style.css\";i:315;s:27:\"post-template/style.min.css\";i:316;s:24:\"post-terms/style-rtl.css\";i:317;s:28:\"post-terms/style-rtl.min.css\";i:318;s:20:\"post-terms/style.css\";i:319;s:24:\"post-terms/style.min.css\";i:320;s:24:\"post-title/style-rtl.css\";i:321;s:28:\"post-title/style-rtl.min.css\";i:322;s:20:\"post-title/style.css\";i:323;s:24:\"post-title/style.min.css\";i:324;s:26:\"preformatted/style-rtl.css\";i:325;s:30:\"preformatted/style-rtl.min.css\";i:326;s:22:\"preformatted/style.css\";i:327;s:26:\"preformatted/style.min.css\";i:328;s:24:\"pullquote/editor-rtl.css\";i:329;s:28:\"pullquote/editor-rtl.min.css\";i:330;s:20:\"pullquote/editor.css\";i:331;s:24:\"pullquote/editor.min.css\";i:332;s:23:\"pullquote/style-rtl.css\";i:333;s:27:\"pullquote/style-rtl.min.css\";i:334;s:19:\"pullquote/style.css\";i:335;s:23:\"pullquote/style.min.css\";i:336;s:23:\"pullquote/theme-rtl.css\";i:337;s:27:\"pullquote/theme-rtl.min.css\";i:338;s:19:\"pullquote/theme.css\";i:339;s:23:\"pullquote/theme.min.css\";i:340;s:39:\"query-pagination-numbers/editor-rtl.css\";i:341;s:43:\"query-pagination-numbers/editor-rtl.min.css\";i:342;s:35:\"query-pagination-numbers/editor.css\";i:343;s:39:\"query-pagination-numbers/editor.min.css\";i:344;s:31:\"query-pagination/editor-rtl.css\";i:345;s:35:\"query-pagination/editor-rtl.min.css\";i:346;s:27:\"query-pagination/editor.css\";i:347;s:31:\"query-pagination/editor.min.css\";i:348;s:30:\"query-pagination/style-rtl.css\";i:349;s:34:\"query-pagination/style-rtl.min.css\";i:350;s:26:\"query-pagination/style.css\";i:351;s:30:\"query-pagination/style.min.css\";i:352;s:25:\"query-title/style-rtl.css\";i:353;s:29:\"query-title/style-rtl.min.css\";i:354;s:21:\"query-title/style.css\";i:355;s:25:\"query-title/style.min.css\";i:356;s:20:\"query/editor-rtl.css\";i:357;s:24:\"query/editor-rtl.min.css\";i:358;s:16:\"query/editor.css\";i:359;s:20:\"query/editor.min.css\";i:360;s:19:\"quote/style-rtl.css\";i:361;s:23:\"quote/style-rtl.min.css\";i:362;s:15:\"quote/style.css\";i:363;s:19:\"quote/style.min.css\";i:364;s:19:\"quote/theme-rtl.css\";i:365;s:23:\"quote/theme-rtl.min.css\";i:366;s:15:\"quote/theme.css\";i:367;s:19:\"quote/theme.min.css\";i:368;s:23:\"read-more/style-rtl.css\";i:369;s:27:\"read-more/style-rtl.min.css\";i:370;s:19:\"read-more/style.css\";i:371;s:23:\"read-more/style.min.css\";i:372;s:18:\"rss/editor-rtl.css\";i:373;s:22:\"rss/editor-rtl.min.css\";i:374;s:14:\"rss/editor.css\";i:375;s:18:\"rss/editor.min.css\";i:376;s:17:\"rss/style-rtl.css\";i:377;s:21:\"rss/style-rtl.min.css\";i:378;s:13:\"rss/style.css\";i:379;s:17:\"rss/style.min.css\";i:380;s:21:\"search/editor-rtl.css\";i:381;s:25:\"search/editor-rtl.min.css\";i:382;s:17:\"search/editor.css\";i:383;s:21:\"search/editor.min.css\";i:384;s:20:\"search/style-rtl.css\";i:385;s:24:\"search/style-rtl.min.css\";i:386;s:16:\"search/style.css\";i:387;s:20:\"search/style.min.css\";i:388;s:20:\"search/theme-rtl.css\";i:389;s:24:\"search/theme-rtl.min.css\";i:390;s:16:\"search/theme.css\";i:391;s:20:\"search/theme.min.css\";i:392;s:24:\"separator/editor-rtl.css\";i:393;s:28:\"separator/editor-rtl.min.css\";i:394;s:20:\"separator/editor.css\";i:395;s:24:\"separator/editor.min.css\";i:396;s:23:\"separator/style-rtl.css\";i:397;s:27:\"separator/style-rtl.min.css\";i:398;s:19:\"separator/style.css\";i:399;s:23:\"separator/style.min.css\";i:400;s:23:\"separator/theme-rtl.css\";i:401;s:27:\"separator/theme-rtl.min.css\";i:402;s:19:\"separator/theme.css\";i:403;s:23:\"separator/theme.min.css\";i:404;s:24:\"shortcode/editor-rtl.css\";i:405;s:28:\"shortcode/editor-rtl.min.css\";i:406;s:20:\"shortcode/editor.css\";i:407;s:24:\"shortcode/editor.min.css\";i:408;s:24:\"site-logo/editor-rtl.css\";i:409;s:28:\"site-logo/editor-rtl.min.css\";i:410;s:20:\"site-logo/editor.css\";i:411;s:24:\"site-logo/editor.min.css\";i:412;s:23:\"site-logo/style-rtl.css\";i:413;s:27:\"site-logo/style-rtl.min.css\";i:414;s:19:\"site-logo/style.css\";i:415;s:23:\"site-logo/style.min.css\";i:416;s:27:\"site-tagline/editor-rtl.css\";i:417;s:31:\"site-tagline/editor-rtl.min.css\";i:418;s:23:\"site-tagline/editor.css\";i:419;s:27:\"site-tagline/editor.min.css\";i:420;s:25:\"site-title/editor-rtl.css\";i:421;s:29:\"site-title/editor-rtl.min.css\";i:422;s:21:\"site-title/editor.css\";i:423;s:25:\"site-title/editor.min.css\";i:424;s:24:\"site-title/style-rtl.css\";i:425;s:28:\"site-title/style-rtl.min.css\";i:426;s:20:\"site-title/style.css\";i:427;s:24:\"site-title/style.min.css\";i:428;s:26:\"social-link/editor-rtl.css\";i:429;s:30:\"social-link/editor-rtl.min.css\";i:430;s:22:\"social-link/editor.css\";i:431;s:26:\"social-link/editor.min.css\";i:432;s:27:\"social-links/editor-rtl.css\";i:433;s:31:\"social-links/editor-rtl.min.css\";i:434;s:23:\"social-links/editor.css\";i:435;s:27:\"social-links/editor.min.css\";i:436;s:26:\"social-links/style-rtl.css\";i:437;s:30:\"social-links/style-rtl.min.css\";i:438;s:22:\"social-links/style.css\";i:439;s:26:\"social-links/style.min.css\";i:440;s:21:\"spacer/editor-rtl.css\";i:441;s:25:\"spacer/editor-rtl.min.css\";i:442;s:17:\"spacer/editor.css\";i:443;s:21:\"spacer/editor.min.css\";i:444;s:20:\"spacer/style-rtl.css\";i:445;s:24:\"spacer/style-rtl.min.css\";i:446;s:16:\"spacer/style.css\";i:447;s:20:\"spacer/style.min.css\";i:448;s:20:\"table/editor-rtl.css\";i:449;s:24:\"table/editor-rtl.min.css\";i:450;s:16:\"table/editor.css\";i:451;s:20:\"table/editor.min.css\";i:452;s:19:\"table/style-rtl.css\";i:453;s:23:\"table/style-rtl.min.css\";i:454;s:15:\"table/style.css\";i:455;s:19:\"table/style.min.css\";i:456;s:19:\"table/theme-rtl.css\";i:457;s:23:\"table/theme-rtl.min.css\";i:458;s:15:\"table/theme.css\";i:459;s:19:\"table/theme.min.css\";i:460;s:23:\"tag-cloud/style-rtl.css\";i:461;s:27:\"tag-cloud/style-rtl.min.css\";i:462;s:19:\"tag-cloud/style.css\";i:463;s:23:\"tag-cloud/style.min.css\";i:464;s:28:\"template-part/editor-rtl.css\";i:465;s:32:\"template-part/editor-rtl.min.css\";i:466;s:24:\"template-part/editor.css\";i:467;s:28:\"template-part/editor.min.css\";i:468;s:27:\"template-part/theme-rtl.css\";i:469;s:31:\"template-part/theme-rtl.min.css\";i:470;s:23:\"template-part/theme.css\";i:471;s:27:\"template-part/theme.min.css\";i:472;s:30:\"term-description/style-rtl.css\";i:473;s:34:\"term-description/style-rtl.min.css\";i:474;s:26:\"term-description/style.css\";i:475;s:30:\"term-description/style.min.css\";i:476;s:27:\"text-columns/editor-rtl.css\";i:477;s:31:\"text-columns/editor-rtl.min.css\";i:478;s:23:\"text-columns/editor.css\";i:479;s:27:\"text-columns/editor.min.css\";i:480;s:26:\"text-columns/style-rtl.css\";i:481;s:30:\"text-columns/style-rtl.min.css\";i:482;s:22:\"text-columns/style.css\";i:483;s:26:\"text-columns/style.min.css\";i:484;s:19:\"verse/style-rtl.css\";i:485;s:23:\"verse/style-rtl.min.css\";i:486;s:15:\"verse/style.css\";i:487;s:19:\"verse/style.min.css\";i:488;s:20:\"video/editor-rtl.css\";i:489;s:24:\"video/editor-rtl.min.css\";i:490;s:16:\"video/editor.css\";i:491;s:20:\"video/editor.min.css\";i:492;s:19:\"video/style-rtl.css\";i:493;s:23:\"video/style-rtl.min.css\";i:494;s:15:\"video/style.css\";i:495;s:19:\"video/style.min.css\";i:496;s:19:\"video/theme-rtl.css\";i:497;s:23:\"video/theme-rtl.min.css\";i:498;s:15:\"video/theme.css\";i:499;s:19:\"video/theme.min.css\";}}','yes'),
(122,'_transient_doing_cron','1712650356.7139770984649658203125','yes'),
(123,'action_scheduler_hybrid_store_demarkation','4','yes'),
(124,'schema-ActionScheduler_StoreSchema','7.0.1712650357','yes'),
(125,'schema-ActionScheduler_LoggerSchema','3.0.1712650357','yes'),
(126,'_transient_timeout_as-post-store-dependencies-met','1712736757','no'),
(127,'_transient_as-post-store-dependencies-met','yes','no'),
(130,'woocommerce_newly_installed','yes','yes'),
(131,'woocommerce_schema_version','430','yes'),
(132,'woocommerce_store_address','','yes'),
(133,'woocommerce_store_address_2','','yes'),
(134,'woocommerce_store_city','','yes'),
(135,'woocommerce_default_country','US:CA','yes'),
(136,'woocommerce_store_postcode','','yes'),
(137,'woocommerce_allowed_countries','all','yes'),
(138,'woocommerce_all_except_countries','','yes'),
(139,'woocommerce_specific_allowed_countries','','yes'),
(140,'woocommerce_ship_to_countries','','yes'),
(141,'woocommerce_specific_ship_to_countries','','yes'),
(142,'woocommerce_default_customer_address','base','yes'),
(143,'woocommerce_calc_taxes','yes','yes'),
(144,'woocommerce_enable_coupons','yes','yes'),
(145,'woocommerce_calc_discounts_sequentially','no','no'),
(146,'woocommerce_currency','USD','yes'),
(147,'woocommerce_currency_pos','left','yes'),
(148,'woocommerce_price_thousand_sep',',','yes'),
(149,'woocommerce_price_decimal_sep','.','yes'),
(150,'woocommerce_price_num_decimals','2','yes'),
(151,'woocommerce_shop_page_id','55','yes'),
(152,'woocommerce_cart_redirect_after_add','no','yes'),
(153,'woocommerce_enable_ajax_add_to_cart','yes','yes'),
(154,'woocommerce_placeholder_image','4','yes'),
(155,'woocommerce_weight_unit','kg','yes'),
(156,'woocommerce_dimension_unit','cm','yes'),
(157,'woocommerce_enable_reviews','yes','yes'),
(158,'woocommerce_review_rating_verification_label','yes','no'),
(159,'woocommerce_review_rating_verification_required','no','no'),
(160,'woocommerce_enable_review_rating','yes','yes'),
(161,'woocommerce_review_rating_required','yes','no'),
(162,'woocommerce_manage_stock','yes','yes'),
(163,'woocommerce_hold_stock_minutes','60','no'),
(164,'woocommerce_notify_low_stock','yes','no'),
(165,'woocommerce_notify_no_stock','yes','no'),
(166,'woocommerce_stock_email_recipient','wordpress@example.com','no'),
(167,'woocommerce_notify_low_stock_amount','2','no'),
(168,'woocommerce_notify_no_stock_amount','0','yes'),
(169,'woocommerce_hide_out_of_stock_items','no','yes'),
(170,'woocommerce_stock_format','','yes'),
(171,'woocommerce_file_download_method','force','no'),
(172,'woocommerce_downloads_redirect_fallback_allowed','no','no'),
(173,'woocommerce_downloads_require_login','no','no'),
(174,'woocommerce_downloads_grant_access_after_payment','yes','no'),
(175,'woocommerce_downloads_deliver_inline','','no'),
(176,'woocommerce_downloads_add_hash_to_filename','yes','yes'),
(177,'woocommerce_attribute_lookup_enabled','no','yes'),
(178,'woocommerce_attribute_lookup_direct_updates','no','yes'),
(179,'woocommerce_product_match_featured_image_by_sku','no','yes'),
(180,'woocommerce_prices_include_tax','no','yes'),
(181,'woocommerce_tax_based_on','shipping','yes'),
(182,'woocommerce_shipping_tax_class','inherit','yes'),
(183,'woocommerce_tax_round_at_subtotal','no','yes'),
(184,'woocommerce_tax_classes','','yes'),
(185,'woocommerce_tax_display_shop','excl','yes'),
(186,'woocommerce_tax_display_cart','excl','yes'),
(187,'woocommerce_price_display_suffix','','yes'),
(188,'woocommerce_tax_total_display','itemized','no'),
(189,'woocommerce_enable_shipping_calc','yes','no'),
(190,'woocommerce_shipping_cost_requires_address','no','yes'),
(191,'woocommerce_ship_to_destination','billing','no'),
(192,'woocommerce_shipping_debug_mode','no','yes'),
(193,'woocommerce_enable_guest_checkout','yes','no'),
(194,'woocommerce_enable_checkout_login_reminder','no','no'),
(195,'woocommerce_enable_signup_and_login_from_checkout','no','no'),
(196,'woocommerce_enable_myaccount_registration','no','no'),
(197,'woocommerce_registration_generate_username','yes','no'),
(198,'woocommerce_registration_generate_password','yes','no'),
(199,'woocommerce_erasure_request_removes_order_data','no','no'),
(200,'woocommerce_erasure_request_removes_download_data','no','no'),
(201,'woocommerce_allow_bulk_remove_personal_data','no','no'),
(202,'woocommerce_registration_privacy_policy_text','Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our [privacy_policy].','yes'),
(203,'woocommerce_checkout_privacy_policy_text','Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our [privacy_policy].','yes'),
(204,'woocommerce_delete_inactive_accounts','a:2:{s:6:\"number\";s:0:\"\";s:4:\"unit\";s:6:\"months\";}','no'),
(205,'woocommerce_trash_pending_orders','','no'),
(206,'woocommerce_trash_failed_orders','','no'),
(207,'woocommerce_trash_cancelled_orders','','no'),
(208,'woocommerce_anonymize_completed_orders','a:2:{s:6:\"number\";s:0:\"\";s:4:\"unit\";s:6:\"months\";}','no'),
(209,'woocommerce_email_from_name','woocommerce-blocks','no'),
(210,'woocommerce_email_from_address','wordpress@example.com','no'),
(211,'woocommerce_email_header_image','','no'),
(212,'woocommerce_email_footer_text','{site_title} &mdash; Built with {WooCommerce}','no'),
(213,'woocommerce_email_base_color','#7f54b3','no'),
(214,'woocommerce_email_background_color','#f7f7f7','no'),
(215,'woocommerce_email_body_background_color','#ffffff','no'),
(216,'woocommerce_email_text_color','#3c3c3c','no'),
(217,'woocommerce_merchant_email_notifications','no','no'),
(218,'woocommerce_cart_page_id','56','no'),
(219,'woocommerce_checkout_page_id','57','no'),
(220,'woocommerce_myaccount_page_id','58','no'),
(221,'woocommerce_terms_page_id','59','no'),
(222,'woocommerce_force_ssl_checkout','no','yes'),
(223,'woocommerce_unforce_ssl_checkout','no','yes'),
(224,'woocommerce_checkout_pay_endpoint','order-pay','yes'),
(225,'woocommerce_checkout_order_received_endpoint','order-received','yes'),
(226,'woocommerce_myaccount_add_payment_method_endpoint','add-payment-method','yes'),
(227,'woocommerce_myaccount_delete_payment_method_endpoint','delete-payment-method','yes'),
(228,'woocommerce_myaccount_set_default_payment_method_endpoint','set-default-payment-method','yes'),
(229,'woocommerce_myaccount_orders_endpoint','orders','yes'),
(230,'woocommerce_myaccount_view_order_endpoint','view-order','yes'),
(231,'woocommerce_myaccount_downloads_endpoint','downloads','yes'),
(232,'woocommerce_myaccount_edit_account_endpoint','edit-account','yes'),
(233,'woocommerce_myaccount_edit_address_endpoint','edit-address','yes'),
(234,'woocommerce_myaccount_payment_methods_endpoint','payment-methods','yes'),
(235,'woocommerce_myaccount_lost_password_endpoint','lost-password','yes'),
(236,'woocommerce_logout_endpoint','customer-logout','yes'),
(237,'woocommerce_api_enabled','no','yes'),
(238,'woocommerce_allow_tracking','no','no'),
(239,'woocommerce_show_marketplace_suggestions','yes','no'),
(240,'woocommerce_custom_orders_table_enabled','no','yes'),
(241,'woocommerce_analytics_enabled','yes','yes'),
(242,'woocommerce_feature_order_attribution_enabled','yes','yes'),
(243,'woocommerce_feature_product_block_editor_enabled','no','yes'),
(244,'woocommerce_single_image_width','600','yes'),
(245,'woocommerce_thumbnail_image_width','300','yes'),
(246,'woocommerce_checkout_highlight_required_fields','yes','yes'),
(247,'woocommerce_demo_store','no','no'),
(248,'wc_downloads_approved_directories_mode','enabled','yes'),
(249,'woocommerce_permalinks','a:5:{s:12:\"product_base\";s:7:\"product\";s:13:\"category_base\";s:16:\"product-category\";s:8:\"tag_base\";s:11:\"product-tag\";s:14:\"attribute_base\";s:0:\"\";s:22:\"use_verbose_page_rules\";b:0;}','yes'),
(250,'current_theme_supports_woocommerce','yes','yes'),
(251,'woocommerce_queue_flush_rewrite_rules','no','yes'),
(256,'default_product_cat','15','yes'),
(258,'woocommerce_refund_returns_page_id','64','yes'),
(259,'_transient_timeout__wc_activation_redirect','1712650387','no'),
(260,'_transient__wc_activation_redirect','1','no'),
(261,'woocommerce_paypal_settings','a:23:{s:7:\"enabled\";s:2:\"no\";s:5:\"title\";s:6:\"PayPal\";s:11:\"description\";s:85:\"Pay via PayPal; you can pay with your credit card if you don\'t have a PayPal account.\";s:5:\"email\";s:21:\"wordpress@example.com\";s:8:\"advanced\";s:0:\"\";s:8:\"testmode\";s:2:\"no\";s:5:\"debug\";s:2:\"no\";s:16:\"ipn_notification\";s:3:\"yes\";s:14:\"receiver_email\";s:21:\"wordpress@example.com\";s:14:\"identity_token\";s:0:\"\";s:14:\"invoice_prefix\";s:3:\"WC-\";s:13:\"send_shipping\";s:3:\"yes\";s:16:\"address_override\";s:2:\"no\";s:13:\"paymentaction\";s:4:\"sale\";s:9:\"image_url\";s:0:\"\";s:11:\"api_details\";s:0:\"\";s:12:\"api_username\";s:0:\"\";s:12:\"api_password\";s:0:\"\";s:13:\"api_signature\";s:0:\"\";s:20:\"sandbox_api_username\";s:0:\"\";s:20:\"sandbox_api_password\";s:0:\"\";s:21:\"sandbox_api_signature\";s:0:\"\";s:12:\"_should_load\";s:2:\"no\";}','yes'),
(262,'woocommerce_version','8.9.0','yes'),
(263,'woocommerce_db_version','8.9.0','yes'),
(264,'woocommerce_store_id','3affe447-935c-4a41-9ccc-3a955a14c958','yes'),
(265,'woocommerce_admin_install_timestamp','1712650357','yes'),
(266,'woocommerce_inbox_variant_assignment','4','yes'),
(267,'woocommerce_remote_variant_assignment','88','yes'),
(268,'_transient_timeout__woocommerce_upload_directory_status','1712736757','no'),
(269,'_transient__woocommerce_upload_directory_status','protected','no'),
(270,'_transient_woocommerce_activated_plugin','woocommerce/woocommerce.php','yes'),
(271,'_transient_jetpack_autoloader_plugin_paths','a:1:{i:0;s:29:\"{{WP_PLUGIN_DIR}}/woocommerce\";}','yes'),
(272,'woocommerce_admin_notices','a:2:{i:0;s:20:\"no_secure_connection\";i:1;s:14:\"template_files\";}','yes'),
(273,'wc_blocks_version','11.8.0-dev','yes'),
(274,'jetpack_connection_active_plugins','a:1:{s:11:\"woocommerce\";a:1:{s:4:\"name\";s:11:\"WooCommerce\";}}','yes'),
(275,'woocommerce_maxmind_geolocation_settings','a:1:{s:15:\"database_prefix\";s:32:\"5500JVPfj6KkuqxQEGRcWoLgXVFRFBar\";}','yes'),
(276,'_transient_woocommerce_webhook_ids_status_active','a:0:{}','yes'),
(277,'widget_woocommerce_widget_cart','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(278,'widget_woocommerce_layered_nav_filters','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(279,'widget_woocommerce_layered_nav','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(280,'widget_woocommerce_price_filter','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(281,'widget_woocommerce_product_categories','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(282,'widget_woocommerce_product_search','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(283,'widget_woocommerce_product_tag_cloud','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(284,'widget_woocommerce_products','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(285,'widget_woocommerce_recently_viewed_products','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(286,'widget_woocommerce_top_rated_products','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(287,'widget_woocommerce_recent_reviews','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(288,'widget_woocommerce_rating_filter','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
(291,'_transient_product_query-transient-version','1712650391','yes'),
(293,'category_children','a:0:{}','yes'),
(295,'woocommerce_custom_orders_table_created','no','yes'),
(300,'product_cat_children','a:1:{i:20;a:2:{i:0;i:10;i:1;i:11;}}','yes'),
(307,'_transient_product-transient-version','1712650391','yes'),
(320,'_transient_timeout_wc_related_10','1712736779','no'),
(321,'_transient_wc_related_10','a:1:{s:50:\"limit=5&exclude_ids%5B0%5D=0&exclude_ids%5B1%5D=10\";a:5:{i:0;s:2:\"11\";i:1;s:2:\"12\";i:2;s:2:\"13\";i:3;s:2:\"27\";i:4;s:1:\"7\";}}','no'),
(326,'_transient_timeout_wc_related_26','1712736782','no'),
(327,'_transient_wc_related_26','a:1:{s:50:\"limit=5&exclude_ids%5B0%5D=0&exclude_ids%5B1%5D=26\";a:4:{i:0;s:1:\"6\";i:1;s:1:\"9\";i:2;s:2:\"16\";i:3;s:2:\"17\";}}','no'),
(331,'_transient_wc_attribute_taxonomies','a:2:{i:0;O:8:\"stdClass\":6:{s:12:\"attribute_id\";s:1:\"1\";s:14:\"attribute_name\";s:5:\"color\";s:15:\"attribute_label\";s:5:\"Color\";s:14:\"attribute_type\";s:6:\"select\";s:17:\"attribute_orderby\";s:10:\"menu_order\";s:16:\"attribute_public\";s:1:\"1\";}i:1;O:8:\"stdClass\":6:{s:12:\"attribute_id\";s:1:\"2\";s:14:\"attribute_name\";s:4:\"size\";s:15:\"attribute_label\";s:4:\"Size\";s:14:\"attribute_type\";s:6:\"select\";s:17:\"attribute_orderby\";s:10:\"menu_order\";s:16:\"attribute_public\";s:1:\"1\";}}','yes'),
(332,'wp_calendar_block_has_published_posts','1','yes'),
(333,'_transient_shipping-transient-version','1712650387','yes'),
(334,'woocommerce_cod_settings','a:4:{s:7:\"enabled\";s:3:\"yes\";s:5:\"title\";s:16:\"Cash on delivery\";s:11:\"description\";s:28:\"Cash on delivery description\";s:12:\"instructions\";s:29:\"Cash on delivery instructions\";}','yes'),
(335,'_site_transient_timeout_theme_roots','1712652202','no'),
(336,'_site_transient_theme_roots','a:13:{s:10:\"emptytheme\";s:7:\"/themes\";s:38:\"storefront-child__block-notices-filter\";s:7:\"/themes\";s:40:\"storefront-child__block-notices-template\";s:7:\"/themes\";s:42:\"storefront-child__classic-notices-template\";s:7:\"/themes\";s:10:\"storefront\";s:7:\"/themes\";s:24:\"theme-with-woo-templates\";s:7:\"/themes\";s:44:\"twentytwentyfour-child__block-notices-filter\";s:7:\"/themes\";s:46:\"twentytwentyfour-child__block-notices-template\";s:7:\"/themes\";s:48:\"twentytwentyfour-child__classic-notices-template\";s:7:\"/themes\";s:16:\"twentytwentyfour\";s:7:\"/themes\";s:15:\"twentytwentyone\";s:7:\"/themes\";s:17:\"twentytwentythree\";s:7:\"/themes\";s:15:\"twentytwentytwo\";s:7:\"/themes\";}','no'),
(337,'woocommerce_flat_rate_1_settings','a:3:{s:5:\"title\";s:18:\"Flat rate shipping\";s:10:\"tax_status\";s:7:\"taxable\";s:4:\"cost\";s:2:\"10\";}','yes'),
(338,'woocommerce_bacs_settings','a:4:{s:7:\"enabled\";s:3:\"yes\";s:5:\"title\";s:20:\"Direct bank transfer\";s:11:\"description\";s:32:\"Direct bank transfer description\";s:12:\"instructions\";s:33:\"Direct bank transfer instructions\";}','yes'),
(339,'woocommerce_free_shipping_2_settings','a:4:{s:5:\"title\";s:13:\"Free shipping\";s:8:\"requires\";s:0:\"\";s:10:\"min_amount\";s:1:\"0\";s:16:\"ignore_discounts\";s:2:\"no\";}','yes'),
(342,'_transient_orders-transient-version','1712650388','yes'),
(343,'_transient_timeout_wc_customer_bought_product_a2d570180528fa59591ce61456deba6a','1715242388','no'),
(344,'_transient_wc_customer_bought_product_a2d570180528fa59591ce61456deba6a','a:2:{s:7:\"version\";s:10:\"1712650388\";s:5:\"value\";a:0:{}}','no'),
(345,'woocommerce_cheque_settings','a:4:{s:7:\"enabled\";s:3:\"yes\";s:5:\"title\";s:14:\"Check payments\";s:11:\"description\";s:26:\"Check payments description\";s:12:\"instructions\";s:27:\"Check payments instructions\";}','yes'),
(356,'_transient_timeout_wc_term_counts','1715242391','no'),
(357,'_transient_wc_term_counts','a:0:{}','no'),
(358,'_site_transient_update_core','O:8:\"stdClass\":4:{s:7:\"updates\";a:2:{i:0;O:8:\"stdClass\":10:{s:8:\"response\";s:7:\"upgrade\";s:8:\"download\";s:57:\"https://downloads.wordpress.org/release/wordpress-6.5.zip\";s:6:\"locale\";s:5:\"en_US\";s:8:\"packages\";O:8:\"stdClass\":5:{s:4:\"full\";s:57:\"https://downloads.wordpress.org/release/wordpress-6.5.zip\";s:10:\"no_content\";s:68:\"https://downloads.wordpress.org/release/wordpress-6.5-no-content.zip\";s:11:\"new_bundled\";s:69:\"https://downloads.wordpress.org/release/wordpress-6.5-new-bundled.zip\";s:7:\"partial\";s:0:\"\";s:8:\"rollback\";s:0:\"\";}s:7:\"current\";s:3:\"6.5\";s:7:\"version\";s:3:\"6.5\";s:11:\"php_version\";s:5:\"7.0.0\";s:13:\"mysql_version\";s:3:\"5.0\";s:11:\"new_bundled\";s:3:\"6.4\";s:15:\"partial_version\";s:0:\"\";}i:1;O:8:\"stdClass\":11:{s:8:\"response\";s:10:\"autoupdate\";s:8:\"download\";s:57:\"https://downloads.wordpress.org/release/wordpress-6.5.zip\";s:6:\"locale\";s:5:\"en_US\";s:8:\"packages\";O:8:\"stdClass\":5:{s:4:\"full\";s:57:\"https://downloads.wordpress.org/release/wordpress-6.5.zip\";s:10:\"no_content\";s:68:\"https://downloads.wordpress.org/release/wordpress-6.5-no-content.zip\";s:11:\"new_bundled\";s:69:\"https://downloads.wordpress.org/release/wordpress-6.5-new-bundled.zip\";s:7:\"partial\";s:0:\"\";s:8:\"rollback\";s:0:\"\";}s:7:\"current\";s:3:\"6.5\";s:7:\"version\";s:3:\"6.5\";s:11:\"php_version\";s:5:\"7.0.0\";s:13:\"mysql_version\";s:3:\"5.0\";s:11:\"new_bundled\";s:3:\"6.4\";s:15:\"partial_version\";s:0:\"\";s:9:\"new_files\";s:1:\"1\";}}s:12:\"last_checked\";i:1712650392;s:15:\"version_checked\";s:7:\"6.5-RC4\";s:12:\"translations\";a:0:{}}','no'),
(359,'_transient_timeout__woocommerce_helper_subscriptions','1712651292','no'),
(360,'_transient__woocommerce_helper_subscriptions','a:0:{}','no'),
(361,'_transient_timeout__woocommerce_helper_updates','1712693592','no'),
(362,'_transient__woocommerce_helper_updates','a:4:{s:4:\"hash\";s:32:\"d751713988987e9331980363e24189ce\";s:7:\"updated\";i:1712650392;s:8:\"products\";a:0:{}s:6:\"errors\";a:1:{i:0;s:10:\"http-error\";}}','no'),
(363,'_site_transient_update_plugins','O:8:\"stdClass\":4:{s:12:\"last_checked\";i:1712650402;s:8:\"response\";a:1:{s:41:\"wordpress-importer/wordpress-importer.php\";O:8:\"stdClass\":13:{s:2:\"id\";s:32:\"w.org/plugins/wordpress-importer\";s:4:\"slug\";s:18:\"wordpress-importer\";s:6:\"plugin\";s:41:\"wordpress-importer/wordpress-importer.php\";s:11:\"new_version\";s:5:\"0.8.2\";s:3:\"url\";s:49:\"https://wordpress.org/plugins/wordpress-importer/\";s:7:\"package\";s:67:\"https://downloads.wordpress.org/plugin/wordpress-importer.0.8.2.zip\";s:5:\"icons\";a:2:{s:2:\"1x\";s:63:\"https://ps.w.org/wordpress-importer/assets/icon.svg?rev=2791650\";s:3:\"svg\";s:63:\"https://ps.w.org/wordpress-importer/assets/icon.svg?rev=2791650\";}s:7:\"banners\";a:1:{s:2:\"1x\";s:72:\"https://ps.w.org/wordpress-importer/assets/banner-772x250.png?rev=547654\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"5.2\";s:6:\"tested\";s:5:\"6.4.3\";s:12:\"requires_php\";s:3:\"5.6\";s:16:\"requires_plugins\";a:0:{}}}s:12:\"translations\";a:0:{}s:9:\"no_update\";a:3:{s:19:\"akismet/akismet.php\";O:8:\"stdClass\":10:{s:2:\"id\";s:21:\"w.org/plugins/akismet\";s:4:\"slug\";s:7:\"akismet\";s:6:\"plugin\";s:19:\"akismet/akismet.php\";s:11:\"new_version\";s:5:\"5.3.2\";s:3:\"url\";s:38:\"https://wordpress.org/plugins/akismet/\";s:7:\"package\";s:56:\"https://downloads.wordpress.org/plugin/akismet.5.3.2.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:60:\"https://ps.w.org/akismet/assets/icon-256x256.png?rev=2818463\";s:2:\"1x\";s:60:\"https://ps.w.org/akismet/assets/icon-128x128.png?rev=2818463\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:63:\"https://ps.w.org/akismet/assets/banner-1544x500.png?rev=2900731\";s:2:\"1x\";s:62:\"https://ps.w.org/akismet/assets/banner-772x250.png?rev=2900731\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"5.8\";}s:9:\"hello.php\";O:8:\"stdClass\":10:{s:2:\"id\";s:25:\"w.org/plugins/hello-dolly\";s:4:\"slug\";s:11:\"hello-dolly\";s:6:\"plugin\";s:9:\"hello.php\";s:11:\"new_version\";s:5:\"1.7.2\";s:3:\"url\";s:42:\"https://wordpress.org/plugins/hello-dolly/\";s:7:\"package\";s:60:\"https://downloads.wordpress.org/plugin/hello-dolly.1.7.3.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:64:\"https://ps.w.org/hello-dolly/assets/icon-256x256.jpg?rev=2052855\";s:2:\"1x\";s:64:\"https://ps.w.org/hello-dolly/assets/icon-128x128.jpg?rev=2052855\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:67:\"https://ps.w.org/hello-dolly/assets/banner-1544x500.jpg?rev=2645582\";s:2:\"1x\";s:66:\"https://ps.w.org/hello-dolly/assets/banner-772x250.jpg?rev=2052855\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"4.6\";}s:27:\"woocommerce/woocommerce.php\";O:8:\"stdClass\":10:{s:2:\"id\";s:25:\"w.org/plugins/woocommerce\";s:4:\"slug\";s:11:\"woocommerce\";s:6:\"plugin\";s:27:\"woocommerce/woocommerce.php\";s:11:\"new_version\";s:5:\"8.7.0\";s:3:\"url\";s:42:\"https://wordpress.org/plugins/woocommerce/\";s:7:\"package\";s:60:\"https://downloads.wordpress.org/plugin/woocommerce.8.7.0.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:64:\"https://ps.w.org/woocommerce/assets/icon-256x256.gif?rev=2869506\";s:2:\"1x\";s:64:\"https://ps.w.org/woocommerce/assets/icon-128x128.gif?rev=2869506\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:67:\"https://ps.w.org/woocommerce/assets/banner-1544x500.png?rev=3000842\";s:2:\"1x\";s:66:\"https://ps.w.org/woocommerce/assets/banner-772x250.png?rev=3000842\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"6.3\";}}}','no'),
(367,'woocommerce_refund_returns_page_created','64','no');
/*!40000 ALTER TABLE `wp_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_postmeta`
--

DROP TABLE IF EXISTS `wp_postmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_postmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `post_id` (`post_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=981 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_postmeta`
--

LOCK TABLES `wp_postmeta` WRITE;
/*!40000 ALTER TABLE `wp_postmeta` DISABLE KEYS */;
INSERT INTO `wp_postmeta` VALUES
(1,6,'_sku','woo-vneck-tee'),
(2,6,'_sale_price_dates_from',''),
(3,6,'_sale_price_dates_to',''),
(4,6,'total_sales','0'),
(5,6,'_tax_status','taxable'),
(6,6,'_tax_class',''),
(7,6,'_manage_stock','no'),
(8,6,'_backorders','no'),
(9,6,'_low_stock_amount',''),
(10,6,'_sold_individually','no'),
(11,6,'_weight','0.5'),
(12,6,'_length','24'),
(13,6,'_width','1'),
(14,6,'_height','2'),
(15,6,'_upsell_ids','a:0:{}'),
(16,6,'_crosssell_ids','a:0:{}'),
(17,6,'_purchase_note',''),
(18,6,'_default_attributes','a:0:{}'),
(19,6,'_virtual','no'),
(20,6,'_downloadable','no'),
(21,6,'_product_image_gallery','32,33'),
(22,6,'_download_limit','0'),
(23,6,'_download_expiry','0'),
(24,6,'_stock',''),
(25,6,'_stock_status','instock'),
(26,6,'_wc_average_rating','0'),
(27,6,'_wc_rating_count','a:0:{}'),
(28,6,'_wc_review_count','0'),
(29,6,'_downloadable_files','a:0:{}'),
(30,6,'_product_attributes','a:2:{s:8:\"pa_color\";a:6:{s:4:\"name\";s:8:\"pa_color\";s:5:\"value\";s:0:\"\";s:8:\"position\";i:0;s:10:\"is_visible\";i:1;s:12:\"is_variation\";i:1;s:11:\"is_taxonomy\";i:1;}s:7:\"pa_size\";a:6:{s:4:\"name\";s:7:\"pa_size\";s:5:\"value\";s:0:\"\";s:8:\"position\";i:1;s:10:\"is_visible\";i:1;s:12:\"is_variation\";i:1;s:11:\"is_taxonomy\";i:1;}}'),
(31,6,'_product_version','3.5.3'),
(32,6,'_thumbnail_id','31'),
(33,6,'_price','15'),
(34,6,'_price','20'),
(35,6,'_regular_price',''),
(36,6,'_sale_price',''),
(37,7,'_sku','woo-hoodie'),
(38,7,'_sale_price_dates_from',''),
(39,7,'_sale_price_dates_to',''),
(40,7,'total_sales','0'),
(41,7,'_tax_status','taxable'),
(42,7,'_tax_class',''),
(43,7,'_manage_stock','no'),
(44,7,'_backorders','no'),
(45,7,'_low_stock_amount',''),
(46,7,'_sold_individually','no'),
(47,7,'_weight','1.5'),
(48,7,'_length','10'),
(49,7,'_width','8'),
(50,7,'_height','3'),
(51,7,'_upsell_ids','a:0:{}'),
(52,7,'_crosssell_ids','a:0:{}'),
(53,7,'_purchase_note',''),
(54,7,'_default_attributes','a:0:{}'),
(55,7,'_virtual','no'),
(56,7,'_downloadable','no'),
(57,7,'_product_image_gallery','37,36,34'),
(58,7,'_download_limit','0'),
(59,7,'_download_expiry','0'),
(60,7,'_stock',''),
(61,7,'_stock_status','instock'),
(62,7,'_wc_average_rating','5.00'),
(63,7,'_wc_rating_count','a:1:{i:5;i:1;}'),
(64,7,'_wc_review_count','2'),
(65,7,'_downloadable_files','a:0:{}'),
(66,7,'_product_attributes','a:2:{s:8:\"pa_color\";a:6:{s:4:\"name\";s:8:\"pa_color\";s:5:\"value\";s:0:\"\";s:8:\"position\";i:0;s:10:\"is_visible\";i:1;s:12:\"is_variation\";i:1;s:11:\"is_taxonomy\";i:1;}s:4:\"logo\";a:6:{s:4:\"name\";s:4:\"Logo\";s:5:\"value\";s:8:\"Yes | No\";s:8:\"position\";i:1;s:10:\"is_visible\";i:1;s:12:\"is_variation\";i:1;s:11:\"is_taxonomy\";i:0;}}'),
(67,7,'_product_version','8.9.0'),
(68,7,'_thumbnail_id','34'),
(69,7,'_price','42'),
(70,7,'_price','45'),
(71,7,'_regular_price',''),
(72,7,'_sale_price',''),
(73,8,'_sku','woo-hoodie-with-logo'),
(74,8,'_regular_price','45'),
(75,8,'_sale_price',''),
(76,8,'_sale_price_dates_from',''),
(77,8,'_sale_price_dates_to',''),
(78,8,'total_sales','0'),
(79,8,'_tax_status','taxable'),
(80,8,'_tax_class',''),
(81,8,'_manage_stock','no'),
(82,8,'_backorders','no'),
(83,8,'_low_stock_amount',''),
(84,8,'_sold_individually','no'),
(85,8,'_weight','2'),
(86,8,'_length','10'),
(87,8,'_width','6'),
(88,8,'_height','3'),
(89,8,'_upsell_ids','a:0:{}'),
(90,8,'_crosssell_ids','a:0:{}'),
(91,8,'_purchase_note',''),
(92,8,'_default_attributes','a:0:{}'),
(93,8,'_virtual','no'),
(94,8,'_downloadable','no'),
(95,8,'_product_image_gallery',''),
(96,8,'_download_limit','0'),
(97,8,'_download_expiry','0'),
(98,8,'_stock',''),
(99,8,'_stock_status','instock'),
(100,8,'_wc_average_rating','0'),
(101,8,'_wc_rating_count','a:0:{}'),
(102,8,'_wc_review_count','0'),
(103,8,'_downloadable_files','a:0:{}'),
(104,8,'_product_attributes','a:1:{s:8:\"pa_color\";a:6:{s:4:\"name\";s:8:\"pa_color\";s:5:\"value\";s:0:\"\";s:8:\"position\";i:0;s:10:\"is_visible\";i:1;s:12:\"is_variation\";i:0;s:11:\"is_taxonomy\";i:1;}}'),
(105,8,'_product_version','3.5.3'),
(106,8,'_price','45'),
(107,8,'_thumbnail_id','37'),
(108,9,'_sku','woo-tshirt'),
(109,9,'_regular_price','18'),
(110,9,'_sale_price',''),
(111,9,'_sale_price_dates_from',''),
(112,9,'_sale_price_dates_to',''),
(113,9,'total_sales','0'),
(114,9,'_tax_status','taxable'),
(115,9,'_tax_class',''),
(116,9,'_manage_stock','no'),
(117,9,'_backorders','no'),
(118,9,'_low_stock_amount',''),
(119,9,'_sold_individually','no'),
(120,9,'_weight','0.8'),
(121,9,'_length','8'),
(122,9,'_width','6'),
(123,9,'_height','1'),
(124,9,'_upsell_ids','a:0:{}'),
(125,9,'_crosssell_ids','a:0:{}'),
(126,9,'_purchase_note',''),
(127,9,'_default_attributes','a:0:{}'),
(128,9,'_virtual','no'),
(129,9,'_downloadable','no'),
(130,9,'_product_image_gallery',''),
(131,9,'_download_limit','0'),
(132,9,'_download_expiry','0'),
(133,9,'_stock',''),
(134,9,'_stock_status','instock'),
(135,9,'_wc_average_rating','0'),
(136,9,'_wc_rating_count','a:0:{}'),
(137,9,'_wc_review_count','0'),
(138,9,'_downloadable_files','a:0:{}'),
(139,9,'_product_attributes','a:1:{s:8:\"pa_color\";a:6:{s:4:\"name\";s:8:\"pa_color\";s:5:\"value\";s:0:\"\";s:8:\"position\";i:0;s:10:\"is_visible\";i:1;s:12:\"is_variation\";i:0;s:11:\"is_taxonomy\";i:1;}}'),
(140,9,'_product_version','3.5.3'),
(141,9,'_price','18'),
(142,9,'_thumbnail_id','38'),
(143,10,'_sku','woo-beanie'),
(144,10,'_regular_price','20'),
(145,10,'_sale_price','18'),
(146,10,'_sale_price_dates_from',''),
(147,10,'_sale_price_dates_to',''),
(148,10,'total_sales','0'),
(149,10,'_tax_status','taxable'),
(150,10,'_tax_class',''),
(151,10,'_manage_stock','no'),
(152,10,'_backorders','no'),
(153,10,'_low_stock_amount',''),
(154,10,'_sold_individually','no'),
(155,10,'_weight','0.2'),
(156,10,'_length','4'),
(157,10,'_width','5'),
(158,10,'_height','0.5'),
(159,10,'_upsell_ids','a:0:{}'),
(160,10,'_crosssell_ids','12'),
(161,10,'_purchase_note',''),
(162,10,'_default_attributes','a:0:{}'),
(163,10,'_virtual','no'),
(164,10,'_downloadable','no'),
(165,10,'_product_image_gallery',''),
(166,10,'_download_limit','0'),
(167,10,'_download_expiry','0'),
(168,10,'_stock',''),
(169,10,'_stock_status','instock'),
(170,10,'_wc_average_rating','0'),
(171,10,'_wc_rating_count','a:0:{}'),
(172,10,'_wc_review_count','0'),
(173,10,'_downloadable_files','a:0:{}'),
(174,10,'_product_attributes','a:1:{s:8:\"pa_color\";a:6:{s:4:\"name\";s:8:\"pa_color\";s:5:\"value\";s:0:\"\";s:8:\"position\";i:0;s:10:\"is_visible\";i:1;s:12:\"is_variation\";i:0;s:11:\"is_taxonomy\";i:1;}}'),
(175,10,'_product_version','8.9.0'),
(176,10,'_price','18'),
(177,10,'_thumbnail_id','39'),
(178,11,'_sku','woo-belt'),
(179,11,'_regular_price','65'),
(180,11,'_sale_price','55'),
(181,11,'_sale_price_dates_from',''),
(182,11,'_sale_price_dates_to',''),
(183,11,'total_sales','0'),
(184,11,'_tax_status','taxable'),
(185,11,'_tax_class',''),
(186,11,'_manage_stock','no'),
(187,11,'_backorders','no'),
(188,11,'_low_stock_amount',''),
(189,11,'_sold_individually','no'),
(190,11,'_weight','1.2'),
(191,11,'_length','12'),
(192,11,'_width','2'),
(193,11,'_height','1.5'),
(194,11,'_upsell_ids','a:0:{}'),
(195,11,'_crosssell_ids','a:0:{}'),
(196,11,'_purchase_note',''),
(197,11,'_default_attributes','a:0:{}'),
(198,11,'_virtual','no'),
(199,11,'_downloadable','no'),
(200,11,'_product_image_gallery',''),
(201,11,'_download_limit','0'),
(202,11,'_download_expiry','0'),
(203,11,'_stock',''),
(204,11,'_stock_status','instock'),
(205,11,'_wc_average_rating','0'),
(206,11,'_wc_rating_count','a:0:{}'),
(207,11,'_wc_review_count','0'),
(208,11,'_downloadable_files','a:0:{}'),
(209,11,'_product_attributes','a:0:{}'),
(210,11,'_product_version','3.5.3'),
(211,11,'_price','55'),
(212,11,'_thumbnail_id','40'),
(213,12,'_sku','woo-cap'),
(214,12,'_regular_price','18'),
(215,12,'_sale_price','16'),
(216,12,'_sale_price_dates_from',''),
(217,12,'_sale_price_dates_to',''),
(218,12,'total_sales','0'),
(219,12,'_tax_status','taxable'),
(220,12,'_tax_class',''),
(221,12,'_manage_stock','no'),
(222,12,'_backorders','no'),
(223,12,'_low_stock_amount',''),
(224,12,'_sold_individually','no'),
(225,12,'_weight','0.6'),
(226,12,'_length','8'),
(227,12,'_width','6.5'),
(228,12,'_height','4'),
(229,12,'_upsell_ids','a:0:{}'),
(230,12,'_crosssell_ids','a:0:{}'),
(231,12,'_purchase_note',''),
(232,12,'_default_attributes','a:0:{}'),
(233,12,'_virtual','no'),
(234,12,'_downloadable','no'),
(235,12,'_product_image_gallery',''),
(236,12,'_download_limit','0'),
(237,12,'_download_expiry','0'),
(238,12,'_stock',''),
(239,12,'_stock_status','instock'),
(240,12,'_wc_average_rating','1.00'),
(241,12,'_wc_rating_count','a:1:{i:1;i:1;}'),
(242,12,'_wc_review_count','2'),
(243,12,'_downloadable_files','a:0:{}'),
(244,12,'_product_attributes','a:1:{s:8:\"pa_color\";a:6:{s:4:\"name\";s:8:\"pa_color\";s:5:\"value\";s:0:\"\";s:8:\"position\";i:0;s:10:\"is_visible\";i:1;s:12:\"is_variation\";i:0;s:11:\"is_taxonomy\";i:1;}}'),
(245,12,'_product_version','8.9.0'),
(246,12,'_price','16'),
(247,12,'_thumbnail_id','41'),
(248,13,'_sku','woo-sunglasses'),
(249,13,'_regular_price','90'),
(250,13,'_sale_price',''),
(251,13,'_sale_price_dates_from',''),
(252,13,'_sale_price_dates_to',''),
(253,13,'total_sales','0'),
(254,13,'_tax_status','taxable'),
(255,13,'_tax_class',''),
(256,13,'_manage_stock','no'),
(257,13,'_backorders','no'),
(258,13,'_low_stock_amount',''),
(259,13,'_sold_individually','no'),
(260,13,'_weight','0.2'),
(261,13,'_length','4'),
(262,13,'_width','1.4'),
(263,13,'_height','1'),
(264,13,'_upsell_ids','a:0:{}'),
(265,13,'_crosssell_ids','a:0:{}'),
(266,13,'_purchase_note',''),
(267,13,'_default_attributes','a:0:{}'),
(268,13,'_virtual','no'),
(269,13,'_downloadable','no'),
(270,13,'_product_image_gallery',''),
(271,13,'_download_limit','0'),
(272,13,'_download_expiry','0'),
(273,13,'_stock',''),
(274,13,'_stock_status','instock'),
(275,13,'_wc_average_rating','0'),
(276,13,'_wc_rating_count','a:0:{}'),
(277,13,'_wc_review_count','0'),
(278,13,'_downloadable_files','a:0:{}'),
(279,13,'_product_attributes','a:0:{}'),
(280,13,'_product_version','3.5.3'),
(281,13,'_price','90'),
(282,13,'_thumbnail_id','42'),
(283,14,'_sku','woo-hoodie-with-pocket'),
(284,14,'_regular_price','45'),
(285,14,'_sale_price','35'),
(286,14,'_sale_price_dates_from',''),
(287,14,'_sale_price_dates_to',''),
(288,14,'total_sales','0'),
(289,14,'_tax_status','taxable'),
(290,14,'_tax_class',''),
(291,14,'_manage_stock','no'),
(292,14,'_backorders','no'),
(293,14,'_low_stock_amount',''),
(294,14,'_sold_individually','no'),
(295,14,'_weight','3'),
(296,14,'_length','10'),
(297,14,'_width','8'),
(298,14,'_height','2'),
(299,14,'_upsell_ids','a:0:{}'),
(300,14,'_crosssell_ids','a:0:{}'),
(301,14,'_purchase_note',''),
(302,14,'_default_attributes','a:0:{}'),
(303,14,'_virtual','no'),
(304,14,'_downloadable','no'),
(305,14,'_product_image_gallery',''),
(306,14,'_download_limit','0'),
(307,14,'_download_expiry','0'),
(308,14,'_stock',''),
(309,14,'_stock_status','instock'),
(310,14,'_wc_average_rating','0'),
(311,14,'_wc_rating_count','a:0:{}'),
(312,14,'_wc_review_count','0'),
(313,14,'_downloadable_files','a:0:{}'),
(314,14,'_product_attributes','a:1:{s:8:\"pa_color\";a:6:{s:4:\"name\";s:8:\"pa_color\";s:5:\"value\";s:0:\"\";s:8:\"position\";i:0;s:10:\"is_visible\";i:1;s:12:\"is_variation\";i:0;s:11:\"is_taxonomy\";i:1;}}'),
(315,14,'_product_version','3.5.3'),
(316,14,'_price','35'),
(317,14,'_thumbnail_id','43'),
(318,15,'_sku','woo-hoodie-with-zipper'),
(319,15,'_regular_price','45'),
(320,15,'_sale_price',''),
(321,15,'_sale_price_dates_from',''),
(322,15,'_sale_price_dates_to',''),
(323,15,'total_sales','0'),
(324,15,'_tax_status','taxable'),
(325,15,'_tax_class',''),
(326,15,'_manage_stock','no'),
(327,15,'_backorders','no'),
(328,15,'_low_stock_amount',''),
(329,15,'_sold_individually','no'),
(330,15,'_weight','2'),
(331,15,'_length','8'),
(332,15,'_width','6'),
(333,15,'_height','2'),
(334,15,'_upsell_ids','a:0:{}'),
(335,15,'_crosssell_ids','a:0:{}'),
(336,15,'_purchase_note',''),
(337,15,'_default_attributes','a:0:{}'),
(338,15,'_virtual','no'),
(339,15,'_downloadable','no'),
(340,15,'_product_image_gallery',''),
(341,15,'_download_limit','0'),
(342,15,'_download_expiry','0'),
(343,15,'_stock',''),
(344,15,'_stock_status','instock'),
(345,15,'_wc_average_rating','0'),
(346,15,'_wc_rating_count','a:0:{}'),
(347,15,'_wc_review_count','0'),
(348,15,'_downloadable_files','a:0:{}'),
(349,15,'_product_attributes','a:0:{}'),
(350,15,'_product_version','3.5.3'),
(351,15,'_price','45'),
(352,15,'_thumbnail_id','44'),
(353,16,'_sku','woo-long-sleeve-tee'),
(354,16,'_regular_price','25'),
(355,16,'_sale_price',''),
(356,16,'_sale_price_dates_from',''),
(357,16,'_sale_price_dates_to',''),
(358,16,'total_sales','0'),
(359,16,'_tax_status','taxable'),
(360,16,'_tax_class',''),
(361,16,'_manage_stock','no'),
(362,16,'_backorders','no'),
(363,16,'_low_stock_amount',''),
(364,16,'_sold_individually','no'),
(365,16,'_weight','1'),
(366,16,'_length','7'),
(367,16,'_width','5'),
(368,16,'_height','1'),
(369,16,'_upsell_ids','a:0:{}'),
(370,16,'_crosssell_ids','a:0:{}'),
(371,16,'_purchase_note',''),
(372,16,'_default_attributes','a:0:{}'),
(373,16,'_virtual','no'),
(374,16,'_downloadable','no'),
(375,16,'_product_image_gallery',''),
(376,16,'_download_limit','0'),
(377,16,'_download_expiry','0'),
(378,16,'_stock',''),
(379,16,'_stock_status','instock'),
(380,16,'_wc_average_rating','0'),
(381,16,'_wc_rating_count','a:0:{}'),
(382,16,'_wc_review_count','0'),
(383,16,'_downloadable_files','a:0:{}'),
(384,16,'_product_attributes','a:1:{s:8:\"pa_color\";a:6:{s:4:\"name\";s:8:\"pa_color\";s:5:\"value\";s:0:\"\";s:8:\"position\";i:0;s:10:\"is_visible\";i:1;s:12:\"is_variation\";i:0;s:11:\"is_taxonomy\";i:1;}}'),
(385,16,'_product_version','3.5.3'),
(386,16,'_price','25'),
(387,16,'_thumbnail_id','45'),
(388,17,'_sku','woo-polo'),
(389,17,'_regular_price','20'),
(390,17,'_sale_price',''),
(391,17,'_sale_price_dates_from',''),
(392,17,'_sale_price_dates_to',''),
(393,17,'total_sales','0'),
(394,17,'_tax_status','taxable'),
(395,17,'_tax_class',''),
(396,17,'_manage_stock','no'),
(397,17,'_backorders','no'),
(398,17,'_low_stock_amount',''),
(399,17,'_sold_individually','no'),
(400,17,'_weight','0.8'),
(401,17,'_length','6'),
(402,17,'_width','5'),
(403,17,'_height','1'),
(404,17,'_upsell_ids','a:0:{}'),
(405,17,'_crosssell_ids','a:0:{}'),
(406,17,'_purchase_note',''),
(407,17,'_default_attributes','a:0:{}'),
(408,17,'_virtual','no'),
(409,17,'_downloadable','no'),
(410,17,'_product_image_gallery',''),
(411,17,'_download_limit','0'),
(412,17,'_download_expiry','0'),
(413,17,'_stock',''),
(414,17,'_stock_status','instock'),
(415,17,'_wc_average_rating','0'),
(416,17,'_wc_rating_count','a:0:{}'),
(417,17,'_wc_review_count','0'),
(418,17,'_downloadable_files','a:0:{}'),
(419,17,'_product_attributes','a:1:{s:8:\"pa_color\";a:6:{s:4:\"name\";s:8:\"pa_color\";s:5:\"value\";s:0:\"\";s:8:\"position\";i:0;s:10:\"is_visible\";i:1;s:12:\"is_variation\";i:0;s:11:\"is_taxonomy\";i:1;}}'),
(420,17,'_product_version','3.5.3'),
(421,17,'_price','20'),
(422,17,'_thumbnail_id','46'),
(423,18,'_sku','woo-album'),
(424,18,'_regular_price','15'),
(425,18,'_sale_price',''),
(426,18,'_sale_price_dates_from',''),
(427,18,'_sale_price_dates_to',''),
(428,18,'total_sales','0'),
(429,18,'_tax_status','taxable'),
(430,18,'_tax_class',''),
(431,18,'_manage_stock','no'),
(432,18,'_backorders','no'),
(433,18,'_low_stock_amount',''),
(434,18,'_sold_individually','no'),
(435,18,'_weight',''),
(436,18,'_length',''),
(437,18,'_width',''),
(438,18,'_height',''),
(439,18,'_upsell_ids','a:0:{}'),
(440,18,'_crosssell_ids','a:0:{}'),
(441,18,'_purchase_note',''),
(442,18,'_default_attributes','a:0:{}'),
(443,18,'_virtual','yes'),
(444,18,'_downloadable','yes'),
(445,18,'_product_image_gallery',''),
(446,18,'_download_limit','1'),
(447,18,'_download_expiry','1'),
(448,18,'_stock',''),
(449,18,'_stock_status','instock'),
(450,18,'_wc_average_rating','0'),
(451,18,'_wc_rating_count','a:0:{}'),
(452,18,'_wc_review_count','0'),
(453,18,'_downloadable_files','a:2:{s:36:\"356506a5-cc15-41b9-801b-9104dda1702c\";a:3:{s:2:\"id\";s:36:\"356506a5-cc15-41b9-801b-9104dda1702c\";s:4:\"name\";s:8:\"Single 1\";s:4:\"file\";s:85:\"https://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2017/08/single.jpg\";}s:36:\"18e70c59-59f3-43a3-8525-ce1ea0c12943\";a:3:{s:2:\"id\";s:36:\"18e70c59-59f3-43a3-8525-ce1ea0c12943\";s:4:\"name\";s:8:\"Single 2\";s:4:\"file\";s:84:\"https://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2017/08/album.jpg\";}}'),
(454,18,'_product_attributes','a:0:{}'),
(455,18,'_product_version','3.5.3'),
(456,18,'_price','15'),
(457,18,'_thumbnail_id','47'),
(458,19,'_sku','woo-single'),
(459,19,'_regular_price','3'),
(460,19,'_sale_price','2'),
(461,19,'_sale_price_dates_from',''),
(462,19,'_sale_price_dates_to',''),
(463,19,'total_sales','0'),
(464,19,'_tax_status','taxable'),
(465,19,'_tax_class',''),
(466,19,'_manage_stock','no'),
(467,19,'_backorders','no'),
(468,19,'_low_stock_amount',''),
(469,19,'_sold_individually','no'),
(470,19,'_weight',''),
(471,19,'_length',''),
(472,19,'_width',''),
(473,19,'_height',''),
(474,19,'_upsell_ids','a:0:{}'),
(475,19,'_crosssell_ids','a:0:{}'),
(476,19,'_purchase_note',''),
(477,19,'_default_attributes','a:0:{}'),
(478,19,'_virtual','yes'),
(479,19,'_downloadable','yes'),
(480,19,'_product_image_gallery',''),
(481,19,'_download_limit','1'),
(482,19,'_download_expiry','1'),
(483,19,'_stock',''),
(484,19,'_stock_status','instock'),
(485,19,'_wc_average_rating','0'),
(486,19,'_wc_rating_count','a:0:{}'),
(487,19,'_wc_review_count','0'),
(488,19,'_downloadable_files','a:1:{s:36:\"a0fdda89-5f0e-440d-93f5-188e12c910d1\";a:3:{s:2:\"id\";s:36:\"a0fdda89-5f0e-440d-93f5-188e12c910d1\";s:4:\"name\";s:6:\"Single\";s:4:\"file\";s:85:\"https://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2017/08/single.jpg\";}}'),
(489,19,'_product_attributes','a:0:{}'),
(490,19,'_product_version','3.5.3'),
(491,19,'_price','2'),
(492,19,'_thumbnail_id','48'),
(493,20,'_sku','woo-vneck-tee-red'),
(494,20,'_regular_price','20'),
(495,20,'_sale_price',''),
(496,20,'_sale_price_dates_from',''),
(497,20,'_sale_price_dates_to',''),
(498,20,'total_sales','0'),
(499,20,'_tax_status','taxable'),
(500,20,'_tax_class',''),
(501,20,'_manage_stock','no'),
(502,20,'_backorders','no'),
(503,20,'_low_stock_amount',''),
(504,20,'_sold_individually','no'),
(505,20,'_weight',''),
(506,20,'_length',''),
(507,20,'_width',''),
(508,20,'_height',''),
(509,20,'_upsell_ids','a:0:{}'),
(510,20,'_crosssell_ids','a:0:{}'),
(511,20,'_purchase_note',''),
(512,20,'_default_attributes','a:0:{}'),
(513,20,'_virtual','no'),
(514,20,'_downloadable','no'),
(515,20,'_product_image_gallery',''),
(516,20,'_download_limit','0'),
(517,20,'_download_expiry','0'),
(518,20,'_stock',''),
(519,20,'_stock_status','instock'),
(520,20,'_wc_average_rating','0'),
(521,20,'_wc_rating_count','a:0:{}'),
(522,20,'_wc_review_count','0'),
(523,20,'_downloadable_files','a:0:{}'),
(524,20,'_product_attributes','a:0:{}'),
(525,20,'_product_version','3.5.3'),
(526,20,'_price','20'),
(527,20,'_variation_description','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum sagittis orci ac odio dictum tincidunt. Donec ut metus leo. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Sed luctus, dui eu sagittis sodales, nulla nibh sagittis augue, vel porttitor diam enim non metus. Vestibulum aliquam augue neque. Phasellus tincidunt odio eget ullamcorper efficitur. Cras placerat ut turpis pellentesque vulputate. Nam sed consequat tortor. Curabitur finibus sapien dolor. Ut eleifend tellus nec erat pulvinar dignissim. Nam non arcu purus. Vivamus et massa massa.'),
(528,20,'_thumbnail_id','31'),
(529,20,'attribute_pa_color','red'),
(530,20,'attribute_pa_size',''),
(531,21,'_sku','woo-vneck-tee-green'),
(532,21,'_regular_price','20'),
(533,21,'_sale_price',''),
(534,21,'_sale_price_dates_from',''),
(535,21,'_sale_price_dates_to',''),
(536,21,'total_sales','0'),
(537,21,'_tax_status','taxable'),
(538,21,'_tax_class',''),
(539,21,'_manage_stock','no'),
(540,21,'_backorders','no'),
(541,21,'_low_stock_amount',''),
(542,21,'_sold_individually','no'),
(543,21,'_weight',''),
(544,21,'_length',''),
(545,21,'_width',''),
(546,21,'_height',''),
(547,21,'_upsell_ids','a:0:{}'),
(548,21,'_crosssell_ids','a:0:{}'),
(549,21,'_purchase_note',''),
(550,21,'_default_attributes','a:0:{}'),
(551,21,'_virtual','no'),
(552,21,'_downloadable','no'),
(553,21,'_product_image_gallery',''),
(554,21,'_download_limit','0'),
(555,21,'_download_expiry','0'),
(556,21,'_stock',''),
(557,21,'_stock_status','instock'),
(558,21,'_wc_average_rating','0'),
(559,21,'_wc_rating_count','a:0:{}'),
(560,21,'_wc_review_count','0'),
(561,21,'_downloadable_files','a:0:{}'),
(562,21,'_product_attributes','a:0:{}'),
(563,21,'_product_version','3.5.3'),
(564,21,'_price','20'),
(565,21,'_variation_description','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum sagittis orci ac odio dictum tincidunt. Donec ut metus leo. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Sed luctus, dui eu sagittis sodales, nulla nibh sagittis augue, vel porttitor diam enim non metus. Vestibulum aliquam augue neque. Phasellus tincidunt odio eget ullamcorper efficitur. Cras placerat ut turpis pellentesque vulputate. Nam sed consequat tortor. Curabitur finibus sapien dolor. Ut eleifend tellus nec erat pulvinar dignissim. Nam non arcu purus. Vivamus et massa massa.'),
(566,21,'_thumbnail_id','32'),
(567,21,'attribute_pa_color','green'),
(568,21,'attribute_pa_size',''),
(569,22,'_sku','woo-vneck-tee-blue'),
(570,22,'_regular_price','15'),
(571,22,'_sale_price',''),
(572,22,'_sale_price_dates_from',''),
(573,22,'_sale_price_dates_to',''),
(574,22,'total_sales','0'),
(575,22,'_tax_status','taxable'),
(576,22,'_tax_class',''),
(577,22,'_manage_stock','no'),
(578,22,'_backorders','no'),
(579,22,'_low_stock_amount',''),
(580,22,'_sold_individually','no'),
(581,22,'_weight',''),
(582,22,'_length',''),
(583,22,'_width',''),
(584,22,'_height',''),
(585,22,'_upsell_ids','a:0:{}'),
(586,22,'_crosssell_ids','a:0:{}'),
(587,22,'_purchase_note',''),
(588,22,'_default_attributes','a:0:{}'),
(589,22,'_virtual','no'),
(590,22,'_downloadable','no'),
(591,22,'_product_image_gallery',''),
(592,22,'_download_limit','0'),
(593,22,'_download_expiry','0'),
(594,22,'_stock',''),
(595,22,'_stock_status','instock'),
(596,22,'_wc_average_rating','0'),
(597,22,'_wc_rating_count','a:0:{}'),
(598,22,'_wc_review_count','0'),
(599,22,'_downloadable_files','a:0:{}'),
(600,22,'_product_attributes','a:0:{}'),
(601,22,'_product_version','3.5.3'),
(602,22,'_price','15'),
(603,22,'_wpcom_is_markdown',''),
(604,22,'_wp_old_slug','import-placeholder-for-78'),
(605,22,'_variation_description','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum sagittis orci ac odio dictum tincidunt. Donec ut metus leo. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Sed luctus, dui eu sagittis sodales, nulla nibh sagittis augue, vel porttitor diam enim non metus. Vestibulum aliquam augue neque. Phasellus tincidunt odio eget ullamcorper efficitur. Cras placerat ut turpis pellentesque vulputate. Nam sed consequat tortor. Curabitur finibus sapien dolor. Ut eleifend tellus nec erat pulvinar dignissim. Nam non arcu purus. Vivamus et massa massa.'),
(606,22,'_thumbnail_id','33'),
(607,22,'attribute_pa_color','blue'),
(608,22,'attribute_pa_size',''),
(609,23,'_sku','woo-hoodie-red'),
(610,23,'_regular_price','45'),
(611,23,'_sale_price','42'),
(612,23,'_sale_price_dates_from',''),
(613,23,'_sale_price_dates_to',''),
(614,23,'total_sales','0'),
(615,23,'_tax_status','taxable'),
(616,23,'_tax_class',''),
(617,23,'_manage_stock','no'),
(618,23,'_backorders','no'),
(619,23,'_low_stock_amount',''),
(620,23,'_sold_individually','no'),
(621,23,'_weight',''),
(622,23,'_length',''),
(623,23,'_width',''),
(624,23,'_height',''),
(625,23,'_upsell_ids','a:0:{}'),
(626,23,'_crosssell_ids','a:0:{}'),
(627,23,'_purchase_note',''),
(628,23,'_default_attributes','a:0:{}'),
(629,23,'_virtual','no'),
(630,23,'_downloadable','no'),
(631,23,'_product_image_gallery',''),
(632,23,'_download_limit','0'),
(633,23,'_download_expiry','0'),
(634,23,'_stock',''),
(635,23,'_stock_status','instock'),
(636,23,'_wc_average_rating','0'),
(637,23,'_wc_rating_count','a:0:{}'),
(638,23,'_wc_review_count','0'),
(639,23,'_downloadable_files','a:0:{}'),
(640,23,'_product_attributes','a:0:{}'),
(641,23,'_product_version','3.5.3'),
(642,23,'_price','42'),
(643,23,'_variation_description','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum sagittis orci ac odio dictum tincidunt. Donec ut metus leo. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Sed luctus, dui eu sagittis sodales, nulla nibh sagittis augue, vel porttitor diam enim non metus. Vestibulum aliquam augue neque. Phasellus tincidunt odio eget ullamcorper efficitur. Cras placerat ut turpis pellentesque vulputate. Nam sed consequat tortor. Curabitur finibus sapien dolor. Ut eleifend tellus nec erat pulvinar dignissim. Nam non arcu purus. Vivamus et massa massa.'),
(644,23,'_thumbnail_id','34'),
(645,23,'attribute_pa_color','red'),
(646,23,'attribute_logo','No'),
(647,24,'_sku','woo-hoodie-green'),
(648,24,'_regular_price','45'),
(649,24,'_sale_price',''),
(650,24,'_sale_price_dates_from',''),
(651,24,'_sale_price_dates_to',''),
(652,24,'total_sales','0'),
(653,24,'_tax_status','taxable'),
(654,24,'_tax_class',''),
(655,24,'_manage_stock','no'),
(656,24,'_backorders','no'),
(657,24,'_low_stock_amount',''),
(658,24,'_sold_individually','no'),
(659,24,'_weight',''),
(660,24,'_length',''),
(661,24,'_width',''),
(662,24,'_height',''),
(663,24,'_upsell_ids','a:0:{}'),
(664,24,'_crosssell_ids','a:0:{}'),
(665,24,'_purchase_note',''),
(666,24,'_default_attributes','a:0:{}'),
(667,24,'_virtual','no'),
(668,24,'_downloadable','no'),
(669,24,'_product_image_gallery',''),
(670,24,'_download_limit','0'),
(671,24,'_download_expiry','0'),
(672,24,'_stock',''),
(673,24,'_stock_status','instock'),
(674,24,'_wc_average_rating','0'),
(675,24,'_wc_rating_count','a:0:{}'),
(676,24,'_wc_review_count','0'),
(677,24,'_downloadable_files','a:0:{}'),
(678,24,'_product_attributes','a:0:{}'),
(679,24,'_product_version','3.5.3'),
(680,24,'_price','45'),
(681,24,'_variation_description','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum sagittis orci ac odio dictum tincidunt. Donec ut metus leo. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Sed luctus, dui eu sagittis sodales, nulla nibh sagittis augue, vel porttitor diam enim non metus. Vestibulum aliquam augue neque. Phasellus tincidunt odio eget ullamcorper efficitur. Cras placerat ut turpis pellentesque vulputate. Nam sed consequat tortor. Curabitur finibus sapien dolor. Ut eleifend tellus nec erat pulvinar dignissim. Nam non arcu purus. Vivamus et massa massa.'),
(682,24,'_thumbnail_id','36'),
(683,24,'attribute_pa_color','green'),
(684,24,'attribute_logo','No'),
(685,25,'_sku','woo-hoodie-blue'),
(686,25,'_regular_price','45'),
(687,25,'_sale_price',''),
(688,25,'_sale_price_dates_from',''),
(689,25,'_sale_price_dates_to',''),
(690,25,'total_sales','0'),
(691,25,'_tax_status','taxable'),
(692,25,'_tax_class',''),
(693,25,'_manage_stock','no'),
(694,25,'_backorders','no'),
(695,25,'_low_stock_amount',''),
(696,25,'_sold_individually','no'),
(697,25,'_weight',''),
(698,25,'_length',''),
(699,25,'_width',''),
(700,25,'_height',''),
(701,25,'_upsell_ids','a:0:{}'),
(702,25,'_crosssell_ids','a:0:{}'),
(703,25,'_purchase_note',''),
(704,25,'_default_attributes','a:0:{}'),
(705,25,'_virtual','no'),
(706,25,'_downloadable','no'),
(707,25,'_product_image_gallery',''),
(708,25,'_download_limit','0'),
(709,25,'_download_expiry','0'),
(710,25,'_stock',''),
(711,25,'_stock_status','instock'),
(712,25,'_wc_average_rating','0'),
(713,25,'_wc_rating_count','a:0:{}'),
(714,25,'_wc_review_count','0'),
(715,25,'_downloadable_files','a:0:{}'),
(716,25,'_product_attributes','a:0:{}'),
(717,25,'_product_version','3.5.3'),
(718,25,'_price','45'),
(719,25,'_variation_description','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum sagittis orci ac odio dictum tincidunt. Donec ut metus leo. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Sed luctus, dui eu sagittis sodales, nulla nibh sagittis augue, vel porttitor diam enim non metus. Vestibulum aliquam augue neque. Phasellus tincidunt odio eget ullamcorper efficitur. Cras placerat ut turpis pellentesque vulputate. Nam sed consequat tortor. Curabitur finibus sapien dolor. Ut eleifend tellus nec erat pulvinar dignissim. Nam non arcu purus. Vivamus et massa massa.'),
(720,25,'_thumbnail_id','35'),
(721,25,'attribute_pa_color','blue'),
(722,25,'attribute_logo','No'),
(723,26,'_sku','Woo-tshirt-logo'),
(724,26,'_regular_price','18'),
(725,26,'_sale_price',''),
(726,26,'_sale_price_dates_from',''),
(727,26,'_sale_price_dates_to',''),
(728,26,'total_sales','0'),
(729,26,'_tax_status','taxable'),
(730,26,'_tax_class',''),
(731,26,'_manage_stock','no'),
(732,26,'_backorders','no'),
(733,26,'_low_stock_amount',''),
(734,26,'_sold_individually','no'),
(735,26,'_weight','0.5'),
(736,26,'_length','10'),
(737,26,'_width','12'),
(738,26,'_height','0.5'),
(739,26,'_upsell_ids','a:0:{}'),
(740,26,'_crosssell_ids','a:0:{}'),
(741,26,'_purchase_note',''),
(742,26,'_default_attributes','a:0:{}'),
(743,26,'_virtual','no'),
(744,26,'_downloadable','no'),
(745,26,'_product_image_gallery',''),
(746,26,'_download_limit','0'),
(747,26,'_download_expiry','0'),
(748,26,'_stock',''),
(749,26,'_stock_status','outofstock'),
(750,26,'_wc_average_rating','0'),
(751,26,'_wc_rating_count','a:0:{}'),
(752,26,'_wc_review_count','0'),
(753,26,'_downloadable_files','a:0:{}'),
(754,26,'_product_attributes','a:1:{s:8:\"pa_color\";a:6:{s:4:\"name\";s:8:\"pa_color\";s:5:\"value\";s:0:\"\";s:8:\"position\";i:0;s:10:\"is_visible\";i:1;s:12:\"is_variation\";i:0;s:11:\"is_taxonomy\";i:1;}}'),
(755,26,'_product_version','8.9.0'),
(756,26,'_price','18'),
(757,26,'_thumbnail_id','49'),
(758,27,'_sku','Woo-beanie-logo'),
(759,27,'_regular_price','20'),
(760,27,'_sale_price','18'),
(761,27,'_sale_price_dates_from',''),
(762,27,'_sale_price_dates_to',''),
(763,27,'total_sales','0'),
(764,27,'_tax_status','taxable'),
(765,27,'_tax_class',''),
(766,27,'_manage_stock','no'),
(767,27,'_backorders','no'),
(768,27,'_low_stock_amount',''),
(769,27,'_sold_individually','no'),
(770,27,'_weight','0.2'),
(771,27,'_length','6'),
(772,27,'_width','4'),
(773,27,'_height','1'),
(774,27,'_upsell_ids','a:0:{}'),
(775,27,'_crosssell_ids','a:0:{}'),
(776,27,'_purchase_note',''),
(777,27,'_default_attributes','a:0:{}'),
(778,27,'_virtual','no'),
(779,27,'_downloadable','no'),
(780,27,'_product_image_gallery',''),
(781,27,'_download_limit','0'),
(782,27,'_download_expiry','0'),
(783,27,'_stock',''),
(784,27,'_stock_status','instock'),
(785,27,'_wc_average_rating','0'),
(786,27,'_wc_rating_count','a:0:{}'),
(787,27,'_wc_review_count','0'),
(788,27,'_downloadable_files','a:0:{}'),
(789,27,'_product_attributes','a:1:{s:8:\"pa_color\";a:6:{s:4:\"name\";s:8:\"pa_color\";s:5:\"value\";s:0:\"\";s:8:\"position\";i:0;s:10:\"is_visible\";i:1;s:12:\"is_variation\";i:0;s:11:\"is_taxonomy\";i:1;}}'),
(790,27,'_product_version','3.5.3'),
(791,27,'_price','18'),
(792,27,'_thumbnail_id','50'),
(793,28,'_sku','logo-collection'),
(794,28,'_sale_price_dates_from',''),
(795,28,'_sale_price_dates_to',''),
(796,28,'total_sales','0'),
(797,28,'_tax_status','taxable'),
(798,28,'_tax_class',''),
(799,28,'_manage_stock','no'),
(800,28,'_backorders','no'),
(801,28,'_low_stock_amount',''),
(802,28,'_sold_individually','no'),
(803,28,'_weight',''),
(804,28,'_length',''),
(805,28,'_width',''),
(806,28,'_height',''),
(807,28,'_upsell_ids','a:0:{}'),
(808,28,'_crosssell_ids','a:0:{}'),
(809,28,'_purchase_note',''),
(810,28,'_default_attributes','a:0:{}'),
(811,28,'_virtual','no'),
(812,28,'_downloadable','no'),
(813,28,'_product_image_gallery','50,49,37'),
(814,28,'_download_limit','0'),
(815,28,'_download_expiry','0'),
(816,28,'_stock',''),
(817,28,'_stock_status','instock'),
(818,28,'_wc_average_rating','0'),
(819,28,'_wc_rating_count','a:0:{}'),
(820,28,'_wc_review_count','0'),
(821,28,'_downloadable_files','a:0:{}'),
(822,28,'_product_attributes','a:0:{}'),
(823,28,'_product_version','3.5.3'),
(824,28,'_children','a:3:{i:0;i:8;i:1;i:9;i:2;i:10;}'),
(825,28,'_thumbnail_id','51'),
(826,28,'_price','18'),
(827,28,'_price','45'),
(828,29,'_sku','wp-pennant'),
(829,29,'_regular_price','11.05'),
(830,29,'_sale_price',''),
(831,29,'_sale_price_dates_from',''),
(832,29,'_sale_price_dates_to',''),
(833,29,'total_sales','0'),
(834,29,'_tax_status','taxable'),
(835,29,'_tax_class',''),
(836,29,'_manage_stock','no'),
(837,29,'_backorders','no'),
(838,29,'_low_stock_amount',''),
(839,29,'_sold_individually','no'),
(840,29,'_weight',''),
(841,29,'_length',''),
(842,29,'_width',''),
(843,29,'_height',''),
(844,29,'_upsell_ids','a:0:{}'),
(845,29,'_crosssell_ids','a:0:{}'),
(846,29,'_purchase_note',''),
(847,29,'_default_attributes','a:0:{}'),
(848,29,'_virtual','no'),
(849,29,'_downloadable','no'),
(850,29,'_product_image_gallery',''),
(851,29,'_download_limit','0'),
(852,29,'_download_expiry','0'),
(853,29,'_stock',''),
(854,29,'_stock_status','instock'),
(855,29,'_wc_average_rating','0'),
(856,29,'_wc_rating_count','a:0:{}'),
(857,29,'_wc_review_count','0'),
(858,29,'_downloadable_files','a:0:{}'),
(859,29,'_product_attributes','a:0:{}'),
(860,29,'_product_version','3.5.3'),
(861,29,'_price','11.05'),
(862,29,'_thumbnail_id','52'),
(863,29,'_product_url','https://mercantile.wordpress.org/product/wordpress-pennant/'),
(864,29,'_button_text','Buy on the WordPress swag store!'),
(865,30,'_sku','woo-hoodie-blue-logo'),
(866,30,'_regular_price','45'),
(867,30,'_sale_price',''),
(868,30,'_sale_price_dates_from',''),
(869,30,'_sale_price_dates_to',''),
(870,30,'total_sales','0'),
(871,30,'_tax_status','taxable'),
(872,30,'_tax_class',''),
(873,30,'_manage_stock','no'),
(874,30,'_backorders','no'),
(875,30,'_low_stock_amount',''),
(876,30,'_sold_individually','no'),
(877,30,'_weight',''),
(878,30,'_length',''),
(879,30,'_width',''),
(880,30,'_height',''),
(881,30,'_upsell_ids','a:0:{}'),
(882,30,'_crosssell_ids','a:0:{}'),
(883,30,'_purchase_note',''),
(884,30,'_default_attributes','a:0:{}'),
(885,30,'_virtual','no'),
(886,30,'_downloadable','no'),
(887,30,'_product_image_gallery',''),
(888,30,'_download_limit','0'),
(889,30,'_download_expiry','0'),
(890,30,'_stock',''),
(891,30,'_stock_status','instock'),
(892,30,'_wc_average_rating','0'),
(893,30,'_wc_rating_count','a:0:{}'),
(894,30,'_wc_review_count','0'),
(895,30,'_downloadable_files','a:0:{}'),
(896,30,'_product_attributes','a:0:{}'),
(897,30,'_product_version','3.5.3'),
(898,30,'_price','45'),
(899,30,'_variation_description','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum sagittis orci ac odio dictum tincidunt. Donec ut metus leo. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Sed luctus, dui eu sagittis sodales, nulla nibh sagittis augue, vel porttitor diam enim non metus. Vestibulum aliquam augue neque. Phasellus tincidunt odio eget ullamcorper efficitur. Cras placerat ut turpis pellentesque vulputate. Nam sed consequat tortor. Curabitur finibus sapien dolor. Ut eleifend tellus nec erat pulvinar dignissim. Nam non arcu purus. Vivamus et massa massa.'),
(900,30,'_thumbnail_id','37'),
(901,30,'attribute_pa_color','blue'),
(902,30,'attribute_logo','Yes'),
(903,31,'_wp_attached_file','2019/01/vneck-tee-2.jpg'),
(904,31,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:801;s:6:\"height\";i:800;s:4:\"file\";s:23:\"2019/01/vneck-tee-2.jpg\";s:8:\"filesize\";i:49497;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:23:\"vneck-tee-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7860;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:23:\"vneck-tee-2-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:3139;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:23:\"vneck-tee-2-768x767.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:767;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:29326;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:23:\"vneck-tee-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7880;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:23:\"vneck-tee-2-600x599.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:599;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:20713;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:23:\"vneck-tee-2-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:1982;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(905,31,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/vneck-tee-2.jpg'),
(906,32,'_wp_attached_file','2019/01/vnech-tee-green-1.jpg'),
(907,32,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:800;s:6:\"height\";i:800;s:4:\"file\";s:29:\"2019/01/vnech-tee-green-1.jpg\";s:8:\"filesize\";i:102362;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:29:\"vnech-tee-green-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7280;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:29:\"vnech-tee-green-1-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:2833;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:29:\"vnech-tee-green-1-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:28489;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:29:\"vnech-tee-green-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7280;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:29:\"vnech-tee-green-1-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:20267;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:29:\"vnech-tee-green-1-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:1814;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(908,32,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/vnech-tee-green-1.jpg'),
(909,33,'_wp_attached_file','2019/01/vnech-tee-blue-1.jpg'),
(910,33,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:800;s:6:\"height\";i:800;s:4:\"file\";s:28:\"2019/01/vnech-tee-blue-1.jpg\";s:8:\"filesize\";i:120226;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:28:\"vnech-tee-blue-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7672;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:28:\"vnech-tee-blue-1-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:2987;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:28:\"vnech-tee-blue-1-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:30141;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:28:\"vnech-tee-blue-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7672;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:28:\"vnech-tee-blue-1-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:21436;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:28:\"vnech-tee-blue-1-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:1879;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(911,33,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/vnech-tee-blue-1.jpg'),
(912,34,'_wp_attached_file','2019/01/hoodie-2.jpg'),
(913,34,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:801;s:6:\"height\";i:801;s:4:\"file\";s:20:\"2019/01/hoodie-2.jpg\";s:8:\"filesize\";i:46079;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:20:\"hoodie-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7951;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:20:\"hoodie-2-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:3121;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:20:\"hoodie-2-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:29085;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:20:\"hoodie-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7951;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:20:\"hoodie-2-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:20490;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:20:\"hoodie-2-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:1974;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(914,34,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/hoodie-2.jpg'),
(915,35,'_wp_attached_file','2019/01/hoodie-blue-1.jpg'),
(916,35,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:800;s:6:\"height\";i:800;s:4:\"file\";s:25:\"2019/01/hoodie-blue-1.jpg\";s:8:\"filesize\";i:101298;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:25:\"hoodie-blue-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7678;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:25:\"hoodie-blue-1-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:2916;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:25:\"hoodie-blue-1-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:29067;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:25:\"hoodie-blue-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7678;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:25:\"hoodie-blue-1-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:20750;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:25:\"hoodie-blue-1-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:1805;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(917,35,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/hoodie-blue-1.jpg'),
(918,36,'_wp_attached_file','2019/01/hoodie-green-1.jpg'),
(919,36,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:800;s:6:\"height\";i:800;s:4:\"file\";s:26:\"2019/01/hoodie-green-1.jpg\";s:8:\"filesize\";i:98498;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:26:\"hoodie-green-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7570;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:26:\"hoodie-green-1-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:2902;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:26:\"hoodie-green-1-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:28529;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:26:\"hoodie-green-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7570;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:26:\"hoodie-green-1-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:20387;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:26:\"hoodie-green-1-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:1823;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(920,36,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/hoodie-green-1.jpg'),
(921,37,'_wp_attached_file','2019/01/hoodie-with-logo-2.jpg'),
(922,37,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:801;s:6:\"height\";i:801;s:4:\"file\";s:30:\"2019/01/hoodie-with-logo-2.jpg\";s:8:\"filesize\";i:46969;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:30:\"hoodie-with-logo-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:8250;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:30:\"hoodie-with-logo-2-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:3091;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:30:\"hoodie-with-logo-2-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:30122;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:30:\"hoodie-with-logo-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:8250;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:30:\"hoodie-with-logo-2-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:21581;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:30:\"hoodie-with-logo-2-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:1913;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(923,37,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/hoodie-with-logo-2.jpg'),
(924,38,'_wp_attached_file','2019/01/tshirt-2.jpg'),
(925,38,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:801;s:6:\"height\";i:801;s:4:\"file\";s:20:\"2019/01/tshirt-2.jpg\";s:8:\"filesize\";i:41155;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:20:\"tshirt-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7134;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:20:\"tshirt-2-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:2793;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:20:\"tshirt-2-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:26448;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:20:\"tshirt-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7134;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:20:\"tshirt-2-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:18798;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:20:\"tshirt-2-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:1766;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(926,38,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/tshirt-2.jpg'),
(927,39,'_wp_attached_file','2019/01/beanie-2.jpg'),
(928,39,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:801;s:6:\"height\";i:801;s:4:\"file\";s:20:\"2019/01/beanie-2.jpg\";s:8:\"filesize\";i:31568;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:20:\"beanie-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:5698;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:20:\"beanie-2-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:2447;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:20:\"beanie-2-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:21231;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:20:\"beanie-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:5698;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:20:\"beanie-2-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:15022;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:20:\"beanie-2-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:1703;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(929,39,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/beanie-2.jpg'),
(930,40,'_wp_attached_file','2019/01/belt-2.jpg'),
(931,40,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:801;s:6:\"height\";i:801;s:4:\"file\";s:18:\"2019/01/belt-2.jpg\";s:8:\"filesize\";i:37339;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:18:\"belt-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:6738;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:18:\"belt-2-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:2681;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:18:\"belt-2-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:25625;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:18:\"belt-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:6738;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:18:\"belt-2-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:17802;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:18:\"belt-2-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:1713;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(932,40,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/belt-2.jpg'),
(933,41,'_wp_attached_file','2019/01/cap-2.jpg'),
(934,41,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:801;s:6:\"height\";i:801;s:4:\"file\";s:17:\"2019/01/cap-2.jpg\";s:8:\"filesize\";i:37675;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:17:\"cap-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:6656;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:17:\"cap-2-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:2559;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:17:\"cap-2-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:25713;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:17:\"cap-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:6656;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:17:\"cap-2-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:17984;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:17:\"cap-2-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:1654;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(935,41,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/cap-2.jpg'),
(936,42,'_wp_attached_file','2019/01/sunglasses-2.jpg'),
(937,42,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:801;s:6:\"height\";i:801;s:4:\"file\";s:24:\"2019/01/sunglasses-2.jpg\";s:8:\"filesize\";i:24691;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:24:\"sunglasses-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:5341;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:24:\"sunglasses-2-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:2242;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:24:\"sunglasses-2-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:20643;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:24:\"sunglasses-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:5341;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:24:\"sunglasses-2-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:14479;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:24:\"sunglasses-2-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:1509;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(938,42,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/sunglasses-2.jpg'),
(939,43,'_wp_attached_file','2019/01/hoodie-with-pocket-2.jpg'),
(940,43,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:801;s:6:\"height\";i:801;s:4:\"file\";s:32:\"2019/01/hoodie-with-pocket-2.jpg\";s:8:\"filesize\";i:43268;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:32:\"hoodie-with-pocket-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7984;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:32:\"hoodie-with-pocket-2-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:3018;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:32:\"hoodie-with-pocket-2-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:28839;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:32:\"hoodie-with-pocket-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7984;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:32:\"hoodie-with-pocket-2-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:20468;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:32:\"hoodie-with-pocket-2-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:1890;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(941,43,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/hoodie-with-pocket-2.jpg'),
(942,44,'_wp_attached_file','2019/01/hoodie-with-zipper-2.jpg'),
(943,44,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:800;s:6:\"height\";i:800;s:4:\"file\";s:32:\"2019/01/hoodie-with-zipper-2.jpg\";s:8:\"filesize\";i:56609;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:32:\"hoodie-with-zipper-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:9277;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:32:\"hoodie-with-zipper-2-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:3607;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:32:\"hoodie-with-zipper-2-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:33934;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:32:\"hoodie-with-zipper-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:9277;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:32:\"hoodie-with-zipper-2-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:24415;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:32:\"hoodie-with-zipper-2-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:2148;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(944,44,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/hoodie-with-zipper-2.jpg'),
(945,45,'_wp_attached_file','2019/01/long-sleeve-tee-2.jpg'),
(946,45,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:801;s:6:\"height\";i:801;s:4:\"file\";s:29:\"2019/01/long-sleeve-tee-2.jpg\";s:8:\"filesize\";i:51118;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:29:\"long-sleeve-tee-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:8080;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:29:\"long-sleeve-tee-2-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:3300;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:29:\"long-sleeve-tee-2-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:29718;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:29:\"long-sleeve-tee-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:8080;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:29:\"long-sleeve-tee-2-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:20965;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:29:\"long-sleeve-tee-2-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:1988;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(947,45,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/long-sleeve-tee-2.jpg'),
(948,46,'_wp_attached_file','2019/01/polo-2.jpg'),
(949,46,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:801;s:6:\"height\";i:800;s:4:\"file\";s:18:\"2019/01/polo-2.jpg\";s:8:\"filesize\";i:44409;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:18:\"polo-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7285;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:18:\"polo-2-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:2871;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:18:\"polo-2-768x767.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:767;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:27584;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:18:\"polo-2-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:7271;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:18:\"polo-2-600x599.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:599;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:19448;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:18:\"polo-2-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:1814;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(950,46,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/polo-2.jpg'),
(951,47,'_wp_attached_file','2019/01/album-1.jpg'),
(952,47,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:800;s:6:\"height\";i:800;s:4:\"file\";s:19:\"2019/01/album-1.jpg\";s:8:\"filesize\";i:120010;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:19:\"album-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:9470;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:19:\"album-1-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:3671;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:19:\"album-1-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:33648;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:19:\"album-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:9470;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:19:\"album-1-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:24377;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:19:\"album-1-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:2219;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(953,47,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2022/05/album-1.jpg'),
(954,48,'_wp_attached_file','2019/01/single-1.jpg'),
(955,48,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:800;s:6:\"height\";i:800;s:4:\"file\";s:20:\"2019/01/single-1.jpg\";s:8:\"filesize\";i:124720;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:20:\"single-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:9592;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:20:\"single-1-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:3734;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:20:\"single-1-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:34135;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:20:\"single-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:9592;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:20:\"single-1-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:24445;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:20:\"single-1-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:2274;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(956,48,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/single-1.jpg'),
(957,49,'_wp_attached_file','2019/01/t-shirt-with-logo-1.jpg'),
(958,49,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:800;s:6:\"height\";i:800;s:4:\"file\";s:31:\"2019/01/t-shirt-with-logo-1.jpg\";s:8:\"filesize\";i:67833;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:31:\"t-shirt-with-logo-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:8142;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:31:\"t-shirt-with-logo-1-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:3150;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:31:\"t-shirt-with-logo-1-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:30504;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:31:\"t-shirt-with-logo-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:8142;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:31:\"t-shirt-with-logo-1-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:21865;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:31:\"t-shirt-with-logo-1-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:1963;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(959,49,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/t-shirt-with-logo-1.jpg'),
(960,50,'_wp_attached_file','2019/01/beanie-with-logo-1.jpg'),
(961,50,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:800;s:6:\"height\";i:800;s:4:\"file\";s:30:\"2019/01/beanie-with-logo-1.jpg\";s:8:\"filesize\";i:45371;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:30:\"beanie-with-logo-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:5810;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:30:\"beanie-with-logo-1-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:2429;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:30:\"beanie-with-logo-1-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:21612;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:30:\"beanie-with-logo-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:5810;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:30:\"beanie-with-logo-1-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:15335;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:30:\"beanie-with-logo-1-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:1672;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(962,50,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/beanie-with-logo-1.jpg'),
(963,51,'_wp_attached_file','2019/01/logo-1.jpg'),
(964,51,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:800;s:6:\"height\";i:799;s:4:\"file\";s:18:\"2019/01/logo-1.jpg\";s:8:\"filesize\";i:139907;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:18:\"logo-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:16167;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:18:\"logo-1-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:5876;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:18:\"logo-1-768x767.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:767;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:56876;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:18:\"logo-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:16214;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:18:\"logo-1-600x599.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:599;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:41239;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:18:\"logo-1-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:3353;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(965,51,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/logo-1.jpg'),
(966,52,'_wp_attached_file','2019/01/pennant-1.jpg'),
(967,52,'_wp_attachment_metadata','a:6:{s:5:\"width\";i:800;s:6:\"height\";i:800;s:4:\"file\";s:21:\"2019/01/pennant-1.jpg\";s:8:\"filesize\";i:56755;s:5:\"sizes\";a:6:{s:6:\"medium\";a:5:{s:4:\"file\";s:21:\"pennant-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:6926;}s:9:\"thumbnail\";a:5:{s:4:\"file\";s:21:\"pennant-1-150x150.jpg\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:2582;}s:12:\"medium_large\";a:5:{s:4:\"file\";s:21:\"pennant-1-768x768.jpg\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:28247;}s:21:\"woocommerce_thumbnail\";a:6:{s:4:\"file\";s:21:\"pennant-1-300x300.jpg\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:6926;s:9:\"uncropped\";b:0;}s:18:\"woocommerce_single\";a:5:{s:4:\"file\";s:21:\"pennant-1-600x600.jpg\";s:5:\"width\";i:600;s:6:\"height\";i:600;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:19849;}s:29:\"woocommerce_gallery_thumbnail\";a:5:{s:4:\"file\";s:21:\"pennant-1-100x100.jpg\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:10:\"image/jpeg\";s:8:\"filesize\";i:1608;}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}'),
(968,52,'_wc_attachment_source','https://woocommercecore.mystagingwebsite.com/wp-content/uploads/2017/12/pennant-1.jpg'),
(969,53,'_pingme','1'),
(970,53,'_encloseme','1'),
(971,54,'discount_type','fixed_cart'),
(972,54,'coupon_amount','10'),
(973,54,'individual_use','no'),
(974,54,'usage_limit','0'),
(975,54,'usage_limit_per_user','0'),
(976,54,'limit_usage_to_x_items',NULL),
(977,54,'usage_count','0'),
(978,54,'date_expires',NULL),
(979,54,'free_shipping','no'),
(980,54,'exclude_sale_items','no');
/*!40000 ALTER TABLE `wp_postmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_posts`
--

DROP TABLE IF EXISTS `wp_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_posts` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_author` bigint(20) unsigned NOT NULL DEFAULT 0,
  `post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content` longtext NOT NULL,
  `post_title` text NOT NULL,
  `post_excerpt` text NOT NULL,
  `post_status` varchar(20) NOT NULL DEFAULT 'publish',
  `comment_status` varchar(20) NOT NULL DEFAULT 'open',
  `ping_status` varchar(20) NOT NULL DEFAULT 'open',
  `post_password` varchar(255) NOT NULL DEFAULT '',
  `post_name` varchar(200) NOT NULL DEFAULT '',
  `to_ping` text NOT NULL,
  `pinged` text NOT NULL,
  `post_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content_filtered` longtext NOT NULL,
  `post_parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `guid` varchar(255) NOT NULL DEFAULT '',
  `menu_order` int(11) NOT NULL DEFAULT 0,
  `post_type` varchar(20) NOT NULL DEFAULT 'post',
  `post_mime_type` varchar(100) NOT NULL DEFAULT '',
  `comment_count` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ID`),
  KEY `post_name` (`post_name`(191)),
  KEY `type_status_date` (`post_type`,`post_status`,`post_date`,`ID`),
  KEY `post_parent` (`post_parent`),
  KEY `post_author` (`post_author`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_posts`
--

LOCK TABLES `wp_posts` WRITE;
/*!40000 ALTER TABLE `wp_posts` DISABLE KEYS */;
INSERT INTO `wp_posts` VALUES
(6,0,'2019-01-16 13:01:52','2019-01-16 13:01:52','Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.','V-Neck T-Shirt','This is a variable product.','publish','open','closed','','v-neck-t-shirt','','','2019-01-16 13:01:52','2019-01-16 13:01:52','',0,'https://woocommercecore.mystagingwebsite.com/product/v-neck-t-shirt/',0,'product','',0),
(7,0,'2019-01-16 13:01:52','2019-01-16 13:01:52','Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.','Hoodie','This is a variable product.','publish','open','closed','','hoodie','','','2024-04-09 08:13:08','2024-04-09 08:13:08','',0,'https://woocommercecore.mystagingwebsite.com/product/hoodie/',0,'product','',2),
(8,0,'2019-01-16 13:01:52','2019-01-16 13:01:52','Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.','Hoodie with Logo','This is a simple product.','publish','open','closed','','hoodie-with-logo','','','2019-01-16 13:01:52','2019-01-16 13:01:52','',0,'https://woocommercecore.mystagingwebsite.com/product/hoodie-with-logo/',0,'product','',0),
(9,0,'2019-01-16 13:01:52','2019-01-16 13:01:52','Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.','T-Shirt','This is a simple product.','publish','open','closed','','t-shirt','','','2019-01-16 13:01:52','2019-01-16 13:01:52','',0,'https://woocommercecore.mystagingwebsite.com/product/t-shirt/',0,'product','',0),
(10,0,'2019-01-16 13:01:52','2019-01-16 13:01:52','Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.','Beanie','This is a simple product.','publish','open','closed','','beanie','','','2024-04-09 08:12:59','2024-04-09 08:12:59','',0,'https://woocommercecore.mystagingwebsite.com/product/beanie/',0,'product','',0),
(11,0,'2019-01-16 13:01:52','2019-01-16 13:01:52','Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.','Belt','This is a simple product.','publish','open','closed','','belt','','','2019-01-16 13:01:52','2019-01-16 13:01:52','',0,'https://woocommercecore.mystagingwebsite.com/product/belt/',0,'product','',0),
(12,0,'2019-01-16 13:01:53','2019-01-16 13:01:53','Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.','Cap','This is a simple product.','publish','open','closed','','cap','','','2024-04-09 08:13:11','2024-04-09 08:13:11','',0,'https://woocommercecore.mystagingwebsite.com/product/cap/',0,'product','',2),
(13,0,'2019-01-16 13:01:53','2019-01-16 13:01:53','Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.','Sunglasses','This is a simple product.','publish','open','closed','password','sunglasses','','','2024-04-09 08:13:03','2024-04-09 08:13:03','',0,'https://woocommercecore.mystagingwebsite.com/product/sunglasses/',0,'product','',0),
(14,0,'2019-01-16 13:01:53','2019-01-16 13:01:53','Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.','Hoodie with Pocket','This is a simple product.','publish','open','closed','','hoodie-with-pocket','','','2019-01-16 13:01:53','2019-01-16 13:01:53','',0,'https://woocommercecore.mystagingwebsite.com/product/hoodie-with-pocket/',0,'product','',0),
(15,0,'2019-01-16 13:01:53','2019-01-16 13:01:53','Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.','Hoodie with Zipper','This is a simple product.','publish','open','closed','','hoodie-with-zipper','','','2019-01-16 13:01:53','2019-01-16 13:01:53','',0,'https://woocommercecore.mystagingwebsite.com/product/hoodie-with-zipper/',0,'product','',0),
(16,0,'2019-01-16 13:01:53','2019-01-16 13:01:53','Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.','Long Sleeve Tee','This is a simple product.','publish','open','closed','','long-sleeve-tee','','','2019-01-16 13:01:53','2019-01-16 13:01:53','',0,'https://woocommercecore.mystagingwebsite.com/product/long-sleeve-tee/',0,'product','',0),
(17,0,'2019-01-16 13:01:53','2019-01-16 13:01:53','Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.','Polo','This is a simple product.','publish','open','closed','','polo','','','2019-01-16 13:01:53','2019-01-16 13:01:53','',0,'https://woocommercecore.mystagingwebsite.com/product/polo/',0,'product','',0),
(18,0,'2019-01-16 13:01:54','2019-01-16 13:01:54','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum sagittis orci ac odio dictum tincidunt. Donec ut metus leo. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Sed luctus, dui eu sagittis sodales, nulla nibh sagittis augue, vel porttitor diam enim non metus. Vestibulum aliquam augue neque. Phasellus tincidunt odio eget ullamcorper efficitur. Cras placerat ut turpis pellentesque vulputate. Nam sed consequat tortor. Curabitur finibus sapien dolor. Ut eleifend tellus nec erat pulvinar dignissim. Nam non arcu purus. Vivamus et massa massa.','Album','This is a simple, virtual product.','publish','open','closed','','album','','','2019-01-16 13:01:54','2019-01-16 13:01:54','',0,'https://woocommercecore.mystagingwebsite.com/product/album/',0,'product','',0),
(19,0,'2019-01-16 13:01:54','2019-01-16 13:01:54','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum sagittis orci ac odio dictum tincidunt. Donec ut metus leo. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Sed luctus, dui eu sagittis sodales, nulla nibh sagittis augue, vel porttitor diam enim non metus. Vestibulum aliquam augue neque. Phasellus tincidunt odio eget ullamcorper efficitur. Cras placerat ut turpis pellentesque vulputate. Nam sed consequat tortor. Curabitur finibus sapien dolor. Ut eleifend tellus nec erat pulvinar dignissim. Nam non arcu purus. Vivamus et massa massa.','Single','This is a simple, virtual product.','publish','open','closed','','single','','','2019-01-16 13:01:54','2019-01-16 13:01:54','',0,'https://woocommercecore.mystagingwebsite.com/product/single/',0,'product','',0),
(20,0,'2019-01-16 13:01:54','2019-01-16 13:01:54','','V-Neck T-Shirt - Red','','publish','closed','closed','','v-neck-t-shirt-red','','','2019-01-16 13:01:54','2019-01-16 13:01:54','',6,'https://woocommercecore.mystagingwebsite.com/product/v-neck-t-shirt-red/',0,'product_variation','',0),
(21,0,'2019-01-16 13:01:54','2019-01-16 13:01:54','','V-Neck T-Shirt - Green','','publish','closed','closed','','v-neck-t-shirt-green','','','2019-01-16 13:01:54','2019-01-16 13:01:54','',6,'https://woocommercecore.mystagingwebsite.com/product/v-neck-t-shirt-green/',0,'product_variation','',0),
(22,0,'2019-01-16 13:01:54','2019-01-16 13:01:54','','V-Neck T-Shirt - Blue','','publish','closed','closed','','v-neck-t-shirt-blue','','','2019-01-16 13:01:54','2019-01-16 13:01:54','',6,'https://woocommercecore.mystagingwebsite.com/product/v-neck-t-shirt-blue/',0,'product_variation','',0),
(23,0,'2019-01-16 13:01:54','2019-01-16 13:01:54','','Hoodie - Red, No','Color: Red, Logo: No','publish','closed','closed','','hoodie-red-no','','','2019-01-16 13:01:54','2019-01-16 13:01:54','',7,'https://woocommercecore.mystagingwebsite.com/product/hoodie-red-no',1,'product_variation','',0),
(24,0,'2019-01-16 13:01:54','2019-01-16 13:01:54','','Hoodie - Green, No','Color: Green, Logo: No','publish','closed','closed','','hoodie-green-no','','','2019-01-16 13:01:54','2019-01-16 13:01:54','',7,'https://woocommercecore.mystagingwebsite.com/product/hoodie-green-no/',2,'product_variation','',0),
(25,0,'2019-01-16 13:01:55','2019-01-16 13:01:55','','Hoodie - Blue, No','Color: Blue, Logo: No','publish','closed','closed','','hoodie-blue-no','','','2019-01-16 13:01:55','2019-01-16 13:01:55','',7,'https://woocommercecore.mystagingwebsite.com/product/hoodie-blue-no',3,'product_variation','',0),
(26,0,'2019-01-16 13:01:55','2019-01-16 13:01:55','Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.','T-Shirt with Logo','This is a simple product.','publish','open','closed','','t-shirt-with-logo','','','2024-04-09 08:13:02','2024-04-09 08:13:02','',0,'https://woocommercecore.mystagingwebsite.com/product/t-shirt-with-logo/',0,'product','',0),
(27,0,'2019-01-16 13:01:55','2019-01-16 13:01:55','Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.','Beanie with Logo','This is a simple product.','publish','open','closed','','beanie-with-logo','','','2019-01-16 13:01:55','2019-01-16 13:01:55','',0,'https://woocommercecore.mystagingwebsite.com/product/beanie-with-logo/',0,'product','',0),
(28,0,'2019-01-16 13:01:55','2019-01-16 13:01:55','Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.','Logo Collection','This is a grouped product.','publish','open','closed','','logo-collection','','','2019-01-16 13:01:55','2019-01-16 13:01:55','',0,'https://woocommercecore.mystagingwebsite.com/product/logo-collection/',0,'product','',0),
(29,0,'2019-01-16 13:01:55','2019-01-16 13:01:55','Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.','WordPress Pennant','This is an external product.','publish','open','closed','','wordpress-pennant','','','2019-01-16 13:01:55','2019-01-16 13:01:55','',0,'https://woocommercecore.mystagingwebsite.com/product/wordpress-pennant/',0,'product','',0),
(30,0,'2019-01-16 13:01:55','2019-01-16 13:01:55','','Hoodie - Blue, Yes','Color: Blue, Logo: Yes','publish','closed','closed','','hoodie-blue-yes','','','2019-01-16 13:01:55','2019-01-16 13:01:55','',7,'https://woocommercecore.mystagingwebsite.com/product/hoodie-blue-yes/',0,'product_variation','',0),
(31,0,'2019-01-16 13:01:56','2019-01-16 13:01:56','','vneck-tee-2.jpg','','inherit','open','closed','','vneck-tee-2-jpg','','','2019-01-16 13:01:56','2019-01-16 13:01:56','',6,'http://localhost:8889/wp-content/uploads/2019/01/vneck-tee-2.jpg',0,'attachment','image/jpeg',0),
(32,0,'2019-01-16 13:01:57','2019-01-16 13:01:57','','vnech-tee-green-1.jpg','','inherit','open','closed','','vnech-tee-green-1-jpg','','','2019-01-16 13:01:57','2019-01-16 13:01:57','',6,'http://localhost:8889/wp-content/uploads/2019/01/vnech-tee-green-1.jpg',0,'attachment','image/jpeg',0),
(33,0,'2019-01-16 13:01:58','2019-01-16 13:01:58','','vnech-tee-blue-1.jpg','','inherit','open','closed','','vnech-tee-blue-1-jpg','','','2019-01-16 13:01:58','2019-01-16 13:01:58','',6,'http://localhost:8889/wp-content/uploads/2019/01/vnech-tee-blue-1.jpg',0,'attachment','image/jpeg',0),
(34,0,'2019-01-16 13:01:58','2019-01-16 13:01:58','','hoodie-2.jpg','','inherit','open','closed','','hoodie-2-jpg','','','2019-01-16 13:01:58','2019-01-16 13:01:58','',7,'http://localhost:8889/wp-content/uploads/2019/01/hoodie-2.jpg',0,'attachment','image/jpeg',0),
(35,0,'2019-01-16 13:01:59','2019-01-16 13:01:59','','hoodie-blue-1.jpg','','inherit','open','closed','','hoodie-blue-1-jpg','','','2019-01-16 13:01:59','2019-01-16 13:01:59','',7,'http://localhost:8889/wp-content/uploads/2019/01/hoodie-blue-1.jpg',0,'attachment','image/jpeg',0),
(36,0,'2019-01-16 13:02:00','2019-01-16 13:02:00','','hoodie-green-1.jpg','','inherit','open','closed','','hoodie-green-1-jpg','','','2019-01-16 13:02:00','2019-01-16 13:02:00','',7,'http://localhost:8889/wp-content/uploads/2019/01/hoodie-green-1.jpg',0,'attachment','image/jpeg',0),
(37,0,'2019-01-16 13:02:01','2019-01-16 13:02:01','','hoodie-with-logo-2.jpg','','inherit','open','closed','','hoodie-with-logo-2-jpg','','','2019-01-16 13:02:01','2019-01-16 13:02:01','',7,'http://localhost:8889/wp-content/uploads/2019/01/hoodie-with-logo-2.jpg',0,'attachment','image/jpeg',0),
(38,0,'2019-01-16 13:02:02','2019-01-16 13:02:02','','tshirt-2.jpg','','inherit','open','closed','','tshirt-2-jpg','','','2019-01-16 13:02:02','2019-01-16 13:02:02','',9,'http://localhost:8889/wp-content/uploads/2019/01/tshirt-2.jpg',0,'attachment','image/jpeg',0),
(39,0,'2019-01-16 13:02:02','2019-01-16 13:02:02','','beanie-2.jpg','','inherit','open','closed','','beanie-2-jpg','','','2019-01-16 13:02:02','2019-01-16 13:02:02','',10,'http://localhost:8889/wp-content/uploads/2019/01/beanie-2.jpg',0,'attachment','image/jpeg',0),
(40,0,'2019-01-16 13:02:03','2019-01-16 13:02:03','','belt-2.jpg','','inherit','open','closed','','belt-2-jpg','','','2019-01-16 13:02:03','2019-01-16 13:02:03','',11,'http://localhost:8889/wp-content/uploads/2019/01/belt-2.jpg',0,'attachment','image/jpeg',0),
(41,0,'2019-01-16 13:02:04','2019-01-16 13:02:04','','cap-2.jpg','','inherit','open','closed','','cap-2-jpg','','','2019-01-16 13:02:04','2019-01-16 13:02:04','',12,'http://localhost:8889/wp-content/uploads/2019/01/cap-2.jpg',0,'attachment','image/jpeg',0),
(42,0,'2019-01-16 13:02:05','2019-01-16 13:02:05','','sunglasses-2.jpg','','inherit','open','closed','','sunglasses-2-jpg','','','2019-01-16 13:02:05','2019-01-16 13:02:05','',13,'http://localhost:8889/wp-content/uploads/2019/01/sunglasses-2.jpg',0,'attachment','image/jpeg',0),
(43,0,'2019-01-16 13:02:06','2019-01-16 13:02:06','','hoodie-with-pocket-2.jpg','','inherit','open','closed','','hoodie-with-pocket-2-jpg','','','2019-01-16 13:02:06','2019-01-16 13:02:06','',14,'http://localhost:8889/wp-content/uploads/2019/01/hoodie-with-pocket-2.jpg',0,'attachment','image/jpeg',0),
(44,0,'2019-01-16 13:02:06','2019-01-16 13:02:06','','hoodie-with-zipper-2.jpg','','inherit','open','closed','','hoodie-with-zipper-2-jpg','','','2019-01-16 13:02:06','2019-01-16 13:02:06','',15,'http://localhost:8889/wp-content/uploads/2019/01/hoodie-with-zipper-2.jpg',0,'attachment','image/jpeg',0),
(45,0,'2019-01-16 13:02:07','2019-01-16 13:02:07','','long-sleeve-tee-2.jpg','','inherit','open','closed','','long-sleeve-tee-2-jpg','','','2019-01-16 13:02:07','2019-01-16 13:02:07','',16,'http://localhost:8889/wp-content/uploads/2019/01/long-sleeve-tee-2.jpg',0,'attachment','image/jpeg',0),
(46,0,'2019-01-16 13:02:08','2019-01-16 13:02:08','','polo-2.jpg','','inherit','open','closed','','polo-2-jpg','','','2019-01-16 13:02:08','2019-01-16 13:02:08','',17,'http://localhost:8889/wp-content/uploads/2019/01/polo-2.jpg',0,'attachment','image/jpeg',0),
(47,0,'2019-01-16 13:02:09','2019-01-16 13:02:09','','album-1.jpg','','inherit','open','closed','','album-1-jpg','','','2019-01-16 13:02:09','2019-01-16 13:02:09','',18,'http://localhost:8889/wp-content/uploads/2019/01/album-1.jpg',0,'attachment','image/jpeg',0),
(48,0,'2019-01-16 13:02:10','2019-01-16 13:02:10','','single-1.jpg','','inherit','open','closed','','single-1-jpg','','','2019-01-16 13:02:10','2019-01-16 13:02:10','',19,'http://localhost:8889/wp-content/uploads/2019/01/single-1.jpg',0,'attachment','image/jpeg',0),
(49,0,'2019-01-16 13:02:11','2019-01-16 13:02:11','','t-shirt-with-logo-1.jpg','','inherit','open','closed','','t-shirt-with-logo-1-jpg','','','2019-01-16 13:02:11','2019-01-16 13:02:11','',26,'http://localhost:8889/wp-content/uploads/2019/01/t-shirt-with-logo-1.jpg',0,'attachment','image/jpeg',0),
(50,0,'2019-01-16 13:02:12','2019-01-16 13:02:12','','beanie-with-logo-1.jpg','','inherit','open','closed','','beanie-with-logo-1-jpg','','','2019-01-16 13:02:12','2019-01-16 13:02:12','',27,'http://localhost:8889/wp-content/uploads/2019/01/beanie-with-logo-1.jpg',0,'attachment','image/jpeg',0),
(51,0,'2019-01-16 13:02:13','2019-01-16 13:02:13','','logo-1.jpg','','inherit','open','closed','','logo-1-jpg','','','2019-01-16 13:02:13','2019-01-16 13:02:13','',28,'http://localhost:8889/wp-content/uploads/2019/01/logo-1.jpg',0,'attachment','image/jpeg',0),
(52,0,'2019-01-16 13:02:13','2019-01-16 13:02:13','','pennant-1.jpg','','inherit','open','closed','','pennant-1-jpg','','','2019-01-16 13:02:13','2019-01-16 13:02:13','',29,'http://localhost:8889/wp-content/uploads/2019/01/pennant-1.jpg',0,'attachment','image/jpeg',0),
(53,1,'2024-04-09 08:13:06','2024-04-09 08:13:06','<!-- wp:woocommerce/product-collection {\"queryId\":0,\"query\":{\"perPage\":16,\"pages\":0,\"offset\":0,\"postType\":\"product\",\"order\":\"asc\",\"orderBy\":\"title\",\"search\":\"\",\"exclude\":[],\"inherit\":false,\"taxQuery\":{},\"isProductCollectionBlock\":true,\"featured\":false,\"woocommerceOnSale\":false,\"woocommerceStockStatus\":[\"instock\",\"outofstock\",\"onbackorder\"],\"woocommerceAttributes\":[],\"woocommerceHandPickedProducts\":[]},\"tagName\":\"div\",\"displayLayout\":{\"type\":\"flex\",\"columns\":3,\"shrinkColumns\":true}} -->\n<div class=\"wp-block-woocommerce-product-collection\"><!-- wp:woocommerce/product-template -->\n	<!-- wp:woocommerce/product-image {\"imageSizing\":\"thumbnail\",\"isDescendentOfQueryLoop\":true} /-->\n\n	<!-- wp:post-title {\"textAlign\":\"center\",\"level\":3,\"isLink\":true,\"style\":{\"spacing\":{\"margin\":{\"bottom\":\"0.75rem\",\"top\":\"0\"}}},\"fontSize\":\"medium\",\"__woocommerceNamespace\":\"woocommerce/product-collection/product-title\"} /-->\n\n	<!-- wp:woocommerce/product-price {\"isDescendentOfQueryLoop\":true,\"textAlign\":\"center\",\"fontSize\":\"small\"} /-->\n\n	<!-- wp:woocommerce/product-button {\"textAlign\":\"center\",\"isDescendentOfQueryLoop\":true,\"fontSize\":\"small\"} /-->\n	<!-- /wp:woocommerce/product-template -->\n\n	<!-- wp:query-pagination {\"layout\":{\"type\":\"flex\",\"justifyContent\":\"center\"}} -->\n	<!-- wp:query-pagination-previous /-->\n\n	<!-- wp:query-pagination-numbers /-->\n\n	<!-- wp:query-pagination-next /-->\n	<!-- /wp:query-pagination -->\n\n	<!-- wp:woocommerce/product-collection-no-results -->\n	<!-- wp:group {\"layout\":{\"type\":\"flex\",\"orientation\":\"vertical\",\"justifyContent\":\"center\",\"flexWrap\":\"wrap\"}} -->\n	<div class=\"wp-block-group\"><!-- wp:paragraph {\"fontSize\":\"medium\"} -->\n		<p class=\"has-medium-font-size\"><strong>No results found</strong></p>\n		<!-- /wp:paragraph -->\n\n		<!-- wp:paragraph -->\n		<p>You can try <a class=\"wc-link-clear-any-filters\" href=\"#\">clearing any filters</a> or head to our <a\n				class=\"wc-link-stores-home\" href=\"#\">store\'s home</a></p>\n		<!-- /wp:paragraph -->\n	</div>\n	<!-- /wp:group -->\n	<!-- /wp:woocommerce/product-collection-no-results -->\n</div>\n<!-- /wp:woocommerce/product-collection -->','Product Collection block','','publish','open','open','','product-collection-block','','','2024-04-09 08:13:06','2024-04-09 08:13:06','',0,'http://localhost:8889/?p=53',0,'post','',0),
(54,1,'2024-04-09 08:13:06','2024-04-09 08:13:06','','testcoupon','','publish','closed','closed','','testcoupon','','','2024-04-09 08:13:06','2024-04-09 08:13:06','',0,'http://localhost:8889/?post_type=shop_coupon&p=54',0,'shop_coupon','',0),
(55,1,'2024-04-09 08:13:06','2024-04-09 08:13:06','','Shop','','publish','closed','closed','','shop','','','2024-04-09 08:13:06','2024-04-09 08:13:06','',0,'http://localhost:8889/?page_id=55',1,'page','',0),
(56,1,'2024-04-09 08:13:08','2024-04-09 08:13:08','<!-- wp:woocommerce/cart -->\n<div class=\"wp-block-woocommerce-cart alignwide is-loading\"><!-- wp:woocommerce/filled-cart-block -->\n<div class=\"wp-block-woocommerce-filled-cart-block\"><!-- wp:woocommerce/cart-items-block -->\n<div class=\"wp-block-woocommerce-cart-items-block\"><!-- wp:woocommerce/cart-line-items-block -->\n<div class=\"wp-block-woocommerce-cart-line-items-block\"></div>\n<!-- /wp:woocommerce/cart-line-items-block -->\n\n<!-- wp:woocommerce/cart-cross-sells-block -->\n<div class=\"wp-block-woocommerce-cart-cross-sells-block\"><!-- wp:heading {\"fontSize\":\"large\"} -->\n<h2 class=\"wp-block-heading has-large-font-size\">You may be interested in…</h2>\n<!-- /wp:heading -->\n\n<!-- wp:woocommerce/cart-cross-sells-products-block -->\n<div class=\"wp-block-woocommerce-cart-cross-sells-products-block\"></div>\n<!-- /wp:woocommerce/cart-cross-sells-products-block --></div>\n<!-- /wp:woocommerce/cart-cross-sells-block --></div>\n<!-- /wp:woocommerce/cart-items-block -->\n\n<!-- wp:woocommerce/cart-totals-block -->\n<div class=\"wp-block-woocommerce-cart-totals-block\"><!-- wp:woocommerce/cart-order-summary-block -->\n<div class=\"wp-block-woocommerce-cart-order-summary-block\"><!-- wp:woocommerce/cart-order-summary-heading-block -->\n<div class=\"wp-block-woocommerce-cart-order-summary-heading-block\"></div>\n<!-- /wp:woocommerce/cart-order-summary-heading-block -->\n\n<!-- wp:woocommerce/cart-order-summary-coupon-form-block -->\n<div class=\"wp-block-woocommerce-cart-order-summary-coupon-form-block\"></div>\n<!-- /wp:woocommerce/cart-order-summary-coupon-form-block -->\n\n<!-- wp:woocommerce/cart-order-summary-subtotal-block -->\n<div class=\"wp-block-woocommerce-cart-order-summary-subtotal-block\"></div>\n<!-- /wp:woocommerce/cart-order-summary-subtotal-block -->\n\n<!-- wp:woocommerce/cart-order-summary-fee-block -->\n<div class=\"wp-block-woocommerce-cart-order-summary-fee-block\"></div>\n<!-- /wp:woocommerce/cart-order-summary-fee-block -->\n\n<!-- wp:woocommerce/cart-order-summary-discount-block -->\n<div class=\"wp-block-woocommerce-cart-order-summary-discount-block\"></div>\n<!-- /wp:woocommerce/cart-order-summary-discount-block -->\n\n<!-- wp:woocommerce/cart-order-summary-shipping-block -->\n<div class=\"wp-block-woocommerce-cart-order-summary-shipping-block\"></div>\n<!-- /wp:woocommerce/cart-order-summary-shipping-block -->\n\n<!-- wp:woocommerce/cart-order-summary-taxes-block -->\n<div class=\"wp-block-woocommerce-cart-order-summary-taxes-block\"></div>\n<!-- /wp:woocommerce/cart-order-summary-taxes-block --></div>\n<!-- /wp:woocommerce/cart-order-summary-block -->\n\n<!-- wp:woocommerce/cart-express-payment-block -->\n<div class=\"wp-block-woocommerce-cart-express-payment-block\"></div>\n<!-- /wp:woocommerce/cart-express-payment-block -->\n\n<!-- wp:woocommerce/proceed-to-checkout-block -->\n<div class=\"wp-block-woocommerce-proceed-to-checkout-block\"></div>\n<!-- /wp:woocommerce/proceed-to-checkout-block -->\n\n<!-- wp:woocommerce/cart-accepted-payment-methods-block -->\n<div class=\"wp-block-woocommerce-cart-accepted-payment-methods-block\"></div>\n<!-- /wp:woocommerce/cart-accepted-payment-methods-block --></div>\n<!-- /wp:woocommerce/cart-totals-block --></div>\n<!-- /wp:woocommerce/filled-cart-block -->\n\n<!-- wp:woocommerce/empty-cart-block -->\n<div class=\"wp-block-woocommerce-empty-cart-block\"><!-- wp:heading {\"textAlign\":\"center\",\"className\":\"with-empty-cart-icon wc-block-cart__empty-cart__title\"} -->\n<h2 class=\"wp-block-heading has-text-align-center with-empty-cart-icon wc-block-cart__empty-cart__title\">Your cart is currently empty!</h2>\n<!-- /wp:heading -->\n\n<!-- wp:separator {\"className\":\"is-style-dots\"} -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity is-style-dots\"/>\n<!-- /wp:separator -->\n\n<!-- wp:heading {\"textAlign\":\"center\"} -->\n<h2 class=\"wp-block-heading has-text-align-center\">New in store</h2>\n<!-- /wp:heading -->\n\n<!-- wp:woocommerce/product-new {\"rows\":1} /--></div>\n<!-- /wp:woocommerce/empty-cart-block --></div>\n<!-- /wp:woocommerce/cart -->\n','Cart','','publish','closed','closed','','cart','','','2024-04-09 08:13:08','2024-04-09 08:13:08','',0,'http://localhost:8889/?page_id=56',2,'page','',0),
(57,1,'2024-04-09 08:13:10','2024-04-09 08:13:10','<!-- wp:woocommerce/checkout -->\n<div class=\"wp-block-woocommerce-checkout alignwide wc-block-checkout is-loading\"><!-- wp:woocommerce/checkout-fields-block -->\n<div class=\"wp-block-woocommerce-checkout-fields-block\"><!-- wp:woocommerce/checkout-express-payment-block -->\n<div class=\"wp-block-woocommerce-checkout-express-payment-block\"></div>\n<!-- /wp:woocommerce/checkout-express-payment-block -->\n\n<!-- wp:woocommerce/checkout-contact-information-block -->\n<div class=\"wp-block-woocommerce-checkout-contact-information-block\"></div>\n<!-- /wp:woocommerce/checkout-contact-information-block -->\n\n<!-- wp:woocommerce/checkout-shipping-method-block -->\n<div class=\"wp-block-woocommerce-checkout-shipping-method-block\"></div>\n<!-- /wp:woocommerce/checkout-shipping-method-block -->\n\n<!-- wp:woocommerce/checkout-pickup-options-block -->\n<div class=\"wp-block-woocommerce-checkout-pickup-options-block\"></div>\n<!-- /wp:woocommerce/checkout-pickup-options-block -->\n\n<!-- wp:woocommerce/checkout-shipping-address-block -->\n<div class=\"wp-block-woocommerce-checkout-shipping-address-block\"></div>\n<!-- /wp:woocommerce/checkout-shipping-address-block -->\n\n<!-- wp:woocommerce/checkout-billing-address-block -->\n<div class=\"wp-block-woocommerce-checkout-billing-address-block\"></div>\n<!-- /wp:woocommerce/checkout-billing-address-block -->\n\n<!-- wp:woocommerce/checkout-shipping-methods-block -->\n<div class=\"wp-block-woocommerce-checkout-shipping-methods-block\"></div>\n<!-- /wp:woocommerce/checkout-shipping-methods-block -->\n\n<!-- wp:woocommerce/checkout-payment-block -->\n<div class=\"wp-block-woocommerce-checkout-payment-block\"></div>\n<!-- /wp:woocommerce/checkout-payment-block -->\n\n<!-- wp:woocommerce/checkout-additional-information-block -->\n<div class=\"wp-block-woocommerce-checkout-additional-information-block\"></div>\n<!-- /wp:woocommerce/checkout-additional-information-block -->\n\n<!-- wp:woocommerce/checkout-order-note-block -->\n<div class=\"wp-block-woocommerce-checkout-order-note-block\"></div>\n<!-- /wp:woocommerce/checkout-order-note-block -->\n\n<!-- wp:woocommerce/checkout-terms-block -->\n<div class=\"wp-block-woocommerce-checkout-terms-block\"></div>\n<!-- /wp:woocommerce/checkout-terms-block -->\n\n<!-- wp:woocommerce/checkout-actions-block -->\n<div class=\"wp-block-woocommerce-checkout-actions-block\"></div>\n<!-- /wp:woocommerce/checkout-actions-block --></div>\n<!-- /wp:woocommerce/checkout-fields-block -->\n\n<!-- wp:woocommerce/checkout-totals-block -->\n<div class=\"wp-block-woocommerce-checkout-totals-block\"><!-- wp:woocommerce/checkout-order-summary-block -->\n<div class=\"wp-block-woocommerce-checkout-order-summary-block\"><!-- wp:woocommerce/checkout-order-summary-cart-items-block -->\n<div class=\"wp-block-woocommerce-checkout-order-summary-cart-items-block\"></div>\n<!-- /wp:woocommerce/checkout-order-summary-cart-items-block -->\n\n<!-- wp:woocommerce/checkout-order-summary-coupon-form-block -->\n<div class=\"wp-block-woocommerce-checkout-order-summary-coupon-form-block\"></div>\n<!-- /wp:woocommerce/checkout-order-summary-coupon-form-block -->\n\n<!-- wp:woocommerce/checkout-order-summary-subtotal-block -->\n<div class=\"wp-block-woocommerce-checkout-order-summary-subtotal-block\"></div>\n<!-- /wp:woocommerce/checkout-order-summary-subtotal-block -->\n\n<!-- wp:woocommerce/checkout-order-summary-fee-block -->\n<div class=\"wp-block-woocommerce-checkout-order-summary-fee-block\"></div>\n<!-- /wp:woocommerce/checkout-order-summary-fee-block -->\n\n<!-- wp:woocommerce/checkout-order-summary-discount-block -->\n<div class=\"wp-block-woocommerce-checkout-order-summary-discount-block\"></div>\n<!-- /wp:woocommerce/checkout-order-summary-discount-block -->\n\n<!-- wp:woocommerce/checkout-order-summary-shipping-block -->\n<div class=\"wp-block-woocommerce-checkout-order-summary-shipping-block\"></div>\n<!-- /wp:woocommerce/checkout-order-summary-shipping-block -->\n\n<!-- wp:woocommerce/checkout-order-summary-taxes-block -->\n<div class=\"wp-block-woocommerce-checkout-order-summary-taxes-block\"></div>\n<!-- /wp:woocommerce/checkout-order-summary-taxes-block --></div>\n<!-- /wp:woocommerce/checkout-order-summary-block --></div>\n<!-- /wp:woocommerce/checkout-totals-block --></div>\n<!-- /wp:woocommerce/checkout -->\n','Checkout','','publish','closed','closed','','checkout','','','2024-04-09 08:13:10','2024-04-09 08:13:10','',0,'http://localhost:8889/?page_id=57',3,'page','',0),
(58,1,'2024-04-09 08:13:12','2024-04-09 08:13:12','<!-- wp:shortcode -->[woocommerce_my_account]<!-- /wp:shortcode -->\n','My Account','','publish','closed','closed','','my-account','','','2024-04-09 08:13:12','2024-04-09 08:13:12','',0,'http://localhost:8889/?page_id=58',4,'page','',0),
(59,1,'2024-04-09 08:13:13','2024-04-09 08:13:13','','Terms','','publish','closed','closed','','terms','','','2024-04-09 08:13:13','2024-04-09 08:13:13','',0,'http://localhost:8889/?page_id=59',5,'page','',0),
(60,1,'2024-04-09 08:13:15','2024-04-09 08:13:15','','Privacy','','publish','closed','closed','','privacy','','','2024-04-09 08:13:15','2024-04-09 08:13:15','',0,'http://localhost:8889/?page_id=60',6,'page','',0),
(61,1,'2024-04-09 08:13:16','2024-04-09 08:13:16','<div data-testid=\"mini-cart\">\n	<!-- wp:woocommerce/mini-cart {\"hasHiddenPrice\":false} /-->\n	<div>','Mini Cart','','publish','closed','closed','','mini-cart','','','2024-04-09 08:13:16','2024-04-09 08:13:16','',0,'http://localhost:8889/?page_id=61',7,'page','',0),
(62,1,'2024-04-09 08:13:17','2024-04-09 08:13:17','<!-- wp:shortcode -->\n[woocommerce_cart]\n<!-- /wp:shortcode -->','Cart Shortcode','','publish','closed','closed','','cart-shortcode','','','2024-04-09 08:13:17','2024-04-09 08:13:17','',0,'http://localhost:8889/?page_id=62',8,'page','',0),
(63,1,'2024-04-09 08:13:17','2024-04-09 08:13:17','<!-- wp:shortcode -->\n[woocommerce_checkout]\n<!-- /wp:shortcode -->','Checkout Shortcode','','publish','closed','closed','','checkout-shortcode','','','2024-04-09 08:13:17','2024-04-09 08:13:17','',0,'http://localhost:8889/?page_id=63',8,'page','',0),
(64,1,'2024-04-09 08:13:18','0000-00-00 00:00:00','<!-- wp:paragraph -->\n<p><b>This is a sample page.</b></p>\n<!-- /wp:paragraph -->\n\n<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Overview</h2>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>Our refund and returns policy lasts 30 days. If 30 days have passed since your purchase, we can’t offer you a full refund or exchange.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>To be eligible for a return, your item must be unused and in the same condition that you received it. It must also be in the original packaging.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Several types of goods are exempt from being returned. Perishable goods such as food, flowers, newspapers or magazines cannot be returned. We also do not accept products that are intimate or sanitary goods, hazardous materials, or flammable liquids or gases.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Additional non-returnable items:</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:list -->\n<ul>\n<li>Gift cards</li>\n<li>Downloadable software products</li>\n<li>Some health and personal care items</li>\n</ul>\n<!-- /wp:list -->\n\n<!-- wp:paragraph -->\n<p>To complete your return, we require a receipt or proof of purchase.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Please do not send your purchase back to the manufacturer.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>There are certain situations where only partial refunds are granted:</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:list -->\n<ul>\n<li>Book with obvious signs of use</li>\n<li>CD, DVD, VHS tape, software, video game, cassette tape, or vinyl record that has been opened.</li>\n<li>Any item not in its original condition, is damaged or missing parts for reasons not due to our error.</li>\n<li>Any item that is returned more than 30 days after delivery</li>\n</ul>\n<!-- /wp:list -->\n\n<!-- wp:paragraph -->\n<h2>Refunds</h2>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Once your return is received and inspected, we will send you an email to notify you that we have received your returned item. We will also notify you of the approval or rejection of your refund.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>If you are approved, then your refund will be processed, and a credit will automatically be applied to your credit card or original method of payment, within a certain amount of days.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:heading -->\n<h3 class=\"wp-block-heading\">Late or missing refunds</h3>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>If you haven’t received a refund yet, first check your bank account again.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Then contact your credit card company, it may take some time before your refund is officially posted.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Next contact your bank. There is often some processing time before a refund is posted.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>If you’ve done all of this and you still have not received your refund yet, please contact us at {email address}.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:heading -->\n<h3 class=\"wp-block-heading\">Sale items</h3>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>Only regular priced items may be refunded. Sale items cannot be refunded.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<h2>Exchanges</h2>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>We only replace items if they are defective or damaged. If you need to exchange it for the same item, send us an email at {email address} and send your item to: {physical address}.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<h2>Gifts</h2>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>If the item was marked as a gift when purchased and shipped directly to you, you’ll receive a gift credit for the value of your return. Once the returned item is received, a gift certificate will be mailed to you.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>If the item wasn’t marked as a gift when purchased, or the gift giver had the order shipped to themselves to give to you later, we will send a refund to the gift giver and they will find out about your return.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<h2>Shipping returns</h2>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>To return your product, you should mail your product to: {physical address}.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>You will be responsible for paying for your own shipping costs for returning your item. Shipping costs are non-refundable. If you receive a refund, the cost of return shipping will be deducted from your refund.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Depending on where you live, the time it may take for your exchanged product to reach you may vary.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>If you are returning more expensive items, you may consider using a trackable shipping service or purchasing shipping insurance. We don’t guarantee that we will receive your returned item.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<h2>Need help?</h2>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Contact us at {email} for questions related to refunds and returns.</p>\n<!-- /wp:paragraph -->','Refund and Returns Policy','','draft','closed','closed','','refund_returns','','','2024-04-09 08:13:18','0000-00-00 00:00:00','',0,'http://localhost:8889/?page_id=64',0,'page','',0);
/*!40000 ALTER TABLE `wp_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_term_relationships`
--

DROP TABLE IF EXISTS `wp_term_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_term_relationships` (
  `object_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `term_taxonomy_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `term_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`object_id`,`term_taxonomy_id`),
  KEY `term_taxonomy_id` (`term_taxonomy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_term_relationships`
--

LOCK TABLES `wp_term_relationships` WRITE;
/*!40000 ALTER TABLE `wp_term_relationships` DISABLE KEYS */;
INSERT INTO `wp_term_relationships` VALUES
(6,2,0),
(6,3,0),
(6,4,0),
(6,5,0),
(6,6,0),
(6,7,0),
(6,8,0),
(6,9,0),
(6,10,0),
(7,2,0),
(7,4,0),
(7,7,0),
(7,9,0),
(7,11,0),
(7,23,0),
(7,25,0),
(8,2,0),
(8,11,0),
(8,12,0),
(9,10,0),
(9,12,0),
(9,13,0),
(10,7,0),
(10,12,0),
(10,14,0),
(10,23,0),
(11,12,0),
(11,14,0),
(12,3,0),
(12,12,0),
(12,14,0),
(12,15,0),
(12,26,0),
(13,3,0),
(13,12,0),
(13,14,0),
(14,3,0),
(14,11,0),
(14,12,0),
(14,13,0),
(14,16,0),
(14,17,0),
(15,3,0),
(15,11,0),
(15,12,0),
(16,4,0),
(16,10,0),
(16,12,0),
(17,2,0),
(17,10,0),
(17,12,0),
(18,12,0),
(18,18,0),
(19,12,0),
(19,18,0),
(26,10,0),
(26,12,0),
(26,13,0),
(26,24,0),
(27,7,0),
(27,12,0),
(27,14,0),
(28,19,0),
(28,20,0),
(29,21,0),
(29,22,0),
(53,1,0);
/*!40000 ALTER TABLE `wp_term_relationships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_term_taxonomy`
--

DROP TABLE IF EXISTS `wp_term_taxonomy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_term_taxonomy` (
  `term_taxonomy_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `taxonomy` varchar(32) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `count` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`term_taxonomy_id`),
  UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
  KEY `taxonomy` (`taxonomy`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_term_taxonomy`
--

LOCK TABLES `wp_term_taxonomy` WRITE;
/*!40000 ALTER TABLE `wp_term_taxonomy` DISABLE KEYS */;
INSERT INTO `wp_term_taxonomy` VALUES
(1,1,'category','',0,1),
(2,2,'pa_color','',0,4),
(3,3,'product_visibility','',0,5),
(4,4,'pa_color','',0,3),
(5,5,'pa_size','',0,1),
(6,6,'pa_size','',0,1),
(7,7,'pa_color','',0,4),
(8,8,'pa_size','',0,1),
(9,9,'product_type','',0,2),
(10,10,'product_cat','',20,5),
(11,11,'product_cat','',20,4),
(12,12,'product_type','',0,14),
(13,13,'pa_color','',0,3),
(14,14,'product_cat','',0,5),
(15,15,'pa_color','',0,1),
(16,16,'product_visibility','',0,1),
(17,17,'product_visibility','',0,1),
(18,18,'product_cat','',0,2),
(19,19,'product_type','',0,1),
(20,20,'product_cat','',0,1),
(21,21,'product_type','',0,1),
(22,22,'product_cat','',0,1),
(23,23,'product_tag','Curated products selected by our experts',0,2),
(24,24,'product_visibility','',0,1),
(25,25,'product_visibility','',0,1),
(26,26,'product_visibility','',0,1);
/*!40000 ALTER TABLE `wp_term_taxonomy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_termmeta`
--

DROP TABLE IF EXISTS `wp_termmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_termmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `term_id` (`term_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_termmeta`
--

LOCK TABLES `wp_termmeta` WRITE;
/*!40000 ALTER TABLE `wp_termmeta` DISABLE KEYS */;
INSERT INTO `wp_termmeta` VALUES
(1,10,'product_count_product_cat','5'),
(2,11,'product_count_product_cat','3'),
(3,14,'product_count_product_cat','5'),
(4,18,'product_count_product_cat','2'),
(5,20,'product_count_product_cat','9'),
(6,22,'product_count_product_cat','1'),
(7,23,'product_count_product_tag','2');
/*!40000 ALTER TABLE `wp_termmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_terms`
--

DROP TABLE IF EXISTS `wp_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_terms` (
  `term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `slug` varchar(200) NOT NULL DEFAULT '',
  `term_group` bigint(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`term_id`),
  KEY `slug` (`slug`(191)),
  KEY `name` (`name`(191))
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_terms`
--

LOCK TABLES `wp_terms` WRITE;
/*!40000 ALTER TABLE `wp_terms` DISABLE KEYS */;
INSERT INTO `wp_terms` VALUES
(1,'Uncategorized','uncategorized',0),
(2,'Blue','blue',0),
(3,'featured','featured',0),
(4,'Green','green',0),
(5,'Large','large',0),
(6,'Medium','medium',0),
(7,'Red','red',0),
(8,'Small','small',0),
(9,'variable','variable',0),
(10,'Tshirts','tshirts',0),
(11,'Hoodies','hoodies',0),
(12,'simple','simple',0),
(13,'Gray','gray',0),
(14,'Accessories','accessories',0),
(15,'Yellow','yellow',0),
(16,'exclude-from-catalog','exclude-from-catalog',0),
(17,'exclude-from-search','exclude-from-search',0),
(18,'Music','music',0),
(19,'grouped','grouped',0),
(20,'Clothing','clothing',0),
(21,'external','external',0),
(22,'Decor','decor',0),
(23,'Recommended','recommended',0),
(24,'outofstock','outofstock',0),
(25,'rated-5','rated-5',0),
(26,'rated-1','rated-1',0);
/*!40000 ALTER TABLE `wp_terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_usermeta`
--

DROP TABLE IF EXISTS `wp_usermeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_usermeta` (
  `umeta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`umeta_id`),
  KEY `user_id` (`user_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_usermeta`
--

LOCK TABLES `wp_usermeta` WRITE;
/*!40000 ALTER TABLE `wp_usermeta` DISABLE KEYS */;
INSERT INTO `wp_usermeta` VALUES
(1,1,'nickname','admin'),
(2,1,'first_name',''),
(3,1,'last_name',''),
(4,1,'description',''),
(5,1,'rich_editing','true'),
(6,1,'syntax_highlighting','true'),
(7,1,'comment_shortcuts','false'),
(8,1,'admin_color','fresh'),
(9,1,'use_ssl','0'),
(10,1,'show_admin_bar_front','true'),
(11,1,'locale',''),
(12,1,'wp_capabilities','a:1:{s:13:\"administrator\";b:1;}'),
(13,1,'wp_user_level','10'),
(14,1,'dismissed_wp_pointers',''),
(15,1,'show_welcome_panel','1'),
(16,2,'nickname','customer'),
(17,2,'first_name','Jane'),
(18,2,'last_name','Smith'),
(19,2,'description',''),
(20,2,'rich_editing','true'),
(21,2,'syntax_highlighting','true'),
(22,2,'comment_shortcuts','false'),
(23,2,'admin_color','fresh'),
(24,2,'use_ssl','0'),
(25,2,'show_admin_bar_front','true'),
(26,2,'locale',''),
(27,2,'wp_capabilities','a:1:{s:10:\"subscriber\";b:1;}'),
(28,2,'wp_user_level','0'),
(29,2,'dismissed_wp_pointers','');
/*!40000 ALTER TABLE `wp_usermeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_users`
--

DROP TABLE IF EXISTS `wp_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_users` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_login` varchar(60) NOT NULL DEFAULT '',
  `user_pass` varchar(255) NOT NULL DEFAULT '',
  `user_nicename` varchar(50) NOT NULL DEFAULT '',
  `user_email` varchar(100) NOT NULL DEFAULT '',
  `user_url` varchar(100) NOT NULL DEFAULT '',
  `user_registered` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_activation_key` varchar(255) NOT NULL DEFAULT '',
  `user_status` int(11) NOT NULL DEFAULT 0,
  `display_name` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `user_login_key` (`user_login`),
  KEY `user_nicename` (`user_nicename`),
  KEY `user_email` (`user_email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_users`
--

LOCK TABLES `wp_users` WRITE;
/*!40000 ALTER TABLE `wp_users` DISABLE KEYS */;
INSERT INTO `wp_users` VALUES
(1,'admin','$P$BZxi4Hoa.N.WWD3nSe7OBsITf0N1vg/','admin','wordpress@example.com','http://localhost:8889','2024-04-09 08:12:35','',0,'admin'),
(2,'customer','$P$B8yHoIBJPNYD7pWLp/a4cZbqwKCcxI.','customer','customer@woocommerceblockse2etestsuite.com','','2022-01-01 12:23:45','',0,'Jane Smith');
/*!40000 ALTER TABLE `wp_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wc_admin_note_actions`
--

DROP TABLE IF EXISTS `wp_wc_admin_note_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_wc_admin_note_actions` (
  `action_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `note_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `query` longtext NOT NULL,
  `status` varchar(255) NOT NULL,
  `actioned_text` varchar(255) NOT NULL,
  `nonce_action` varchar(255) DEFAULT NULL,
  `nonce_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`action_id`),
  KEY `note_id` (`note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wc_admin_note_actions`
--

LOCK TABLES `wp_wc_admin_note_actions` WRITE;
/*!40000 ALTER TABLE `wp_wc_admin_note_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_wc_admin_note_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wc_admin_notes`
--

DROP TABLE IF EXISTS `wp_wc_admin_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_wc_admin_notes` (
  `note_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(20) NOT NULL,
  `locale` varchar(20) NOT NULL,
  `title` longtext NOT NULL,
  `content` longtext NOT NULL,
  `content_data` longtext DEFAULT NULL,
  `status` varchar(200) NOT NULL,
  `source` varchar(200) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_reminder` datetime DEFAULT NULL,
  `is_snoozable` tinyint(1) NOT NULL DEFAULT 0,
  `layout` varchar(20) NOT NULL DEFAULT '',
  `image` varchar(200) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `icon` varchar(200) NOT NULL DEFAULT 'info',
  PRIMARY KEY (`note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wc_admin_notes`
--

LOCK TABLES `wp_wc_admin_notes` WRITE;
/*!40000 ALTER TABLE `wp_wc_admin_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_wc_admin_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wc_category_lookup`
--

DROP TABLE IF EXISTS `wp_wc_category_lookup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_wc_category_lookup` (
  `category_tree_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`category_tree_id`,`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wc_category_lookup`
--

LOCK TABLES `wp_wc_category_lookup` WRITE;
/*!40000 ALTER TABLE `wp_wc_category_lookup` DISABLE KEYS */;
INSERT INTO `wp_wc_category_lookup` VALUES
(10,10),
(11,11),
(14,14),
(18,18),
(20,10),
(20,11),
(20,20),
(22,22);
/*!40000 ALTER TABLE `wp_wc_category_lookup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wc_customer_lookup`
--

DROP TABLE IF EXISTS `wp_wc_customer_lookup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_wc_customer_lookup` (
  `customer_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `username` varchar(60) NOT NULL DEFAULT '',
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `date_last_active` timestamp NULL DEFAULT NULL,
  `date_registered` timestamp NULL DEFAULT NULL,
  `country` char(2) NOT NULL DEFAULT '',
  `postcode` varchar(20) NOT NULL DEFAULT '',
  `city` varchar(100) NOT NULL DEFAULT '',
  `state` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wc_customer_lookup`
--

LOCK TABLES `wp_wc_customer_lookup` WRITE;
/*!40000 ALTER TABLE `wp_wc_customer_lookup` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_wc_customer_lookup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wc_download_log`
--

DROP TABLE IF EXISTS `wp_wc_download_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_wc_download_log` (
  `download_log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_ip_address` varchar(100) DEFAULT '',
  PRIMARY KEY (`download_log_id`),
  KEY `permission_id` (`permission_id`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wc_download_log`
--

LOCK TABLES `wp_wc_download_log` WRITE;
/*!40000 ALTER TABLE `wp_wc_download_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_wc_download_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wc_order_coupon_lookup`
--

DROP TABLE IF EXISTS `wp_wc_order_coupon_lookup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_wc_order_coupon_lookup` (
  `order_id` bigint(20) unsigned NOT NULL,
  `coupon_id` bigint(20) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `discount_amount` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`order_id`,`coupon_id`),
  KEY `coupon_id` (`coupon_id`),
  KEY `date_created` (`date_created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wc_order_coupon_lookup`
--

LOCK TABLES `wp_wc_order_coupon_lookup` WRITE;
/*!40000 ALTER TABLE `wp_wc_order_coupon_lookup` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_wc_order_coupon_lookup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wc_order_product_lookup`
--

DROP TABLE IF EXISTS `wp_wc_order_product_lookup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_wc_order_product_lookup` (
  `order_item_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `variation_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `product_qty` int(11) NOT NULL,
  `product_net_revenue` double NOT NULL DEFAULT 0,
  `product_gross_revenue` double NOT NULL DEFAULT 0,
  `coupon_amount` double NOT NULL DEFAULT 0,
  `tax_amount` double NOT NULL DEFAULT 0,
  `shipping_amount` double NOT NULL DEFAULT 0,
  `shipping_tax_amount` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  KEY `customer_id` (`customer_id`),
  KEY `date_created` (`date_created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wc_order_product_lookup`
--

LOCK TABLES `wp_wc_order_product_lookup` WRITE;
/*!40000 ALTER TABLE `wp_wc_order_product_lookup` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_wc_order_product_lookup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wc_order_stats`
--

DROP TABLE IF EXISTS `wp_wc_order_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_wc_order_stats` (
  `order_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_created_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_paid` datetime DEFAULT '0000-00-00 00:00:00',
  `date_completed` datetime DEFAULT '0000-00-00 00:00:00',
  `num_items_sold` int(11) NOT NULL DEFAULT 0,
  `total_sales` double NOT NULL DEFAULT 0,
  `tax_total` double NOT NULL DEFAULT 0,
  `shipping_total` double NOT NULL DEFAULT 0,
  `net_total` double NOT NULL DEFAULT 0,
  `returning_customer` tinyint(1) DEFAULT NULL,
  `status` varchar(200) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`order_id`),
  KEY `date_created` (`date_created`),
  KEY `customer_id` (`customer_id`),
  KEY `status` (`status`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wc_order_stats`
--

LOCK TABLES `wp_wc_order_stats` WRITE;
/*!40000 ALTER TABLE `wp_wc_order_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_wc_order_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wc_order_tax_lookup`
--

DROP TABLE IF EXISTS `wp_wc_order_tax_lookup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_wc_order_tax_lookup` (
  `order_id` bigint(20) unsigned NOT NULL,
  `tax_rate_id` bigint(20) unsigned NOT NULL,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `shipping_tax` double NOT NULL DEFAULT 0,
  `order_tax` double NOT NULL DEFAULT 0,
  `total_tax` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`order_id`,`tax_rate_id`),
  KEY `tax_rate_id` (`tax_rate_id`),
  KEY `date_created` (`date_created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wc_order_tax_lookup`
--

LOCK TABLES `wp_wc_order_tax_lookup` WRITE;
/*!40000 ALTER TABLE `wp_wc_order_tax_lookup` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_wc_order_tax_lookup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wc_product_attributes_lookup`
--

DROP TABLE IF EXISTS `wp_wc_product_attributes_lookup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_wc_product_attributes_lookup` (
  `product_id` bigint(20) NOT NULL,
  `product_or_parent_id` bigint(20) NOT NULL,
  `taxonomy` varchar(32) NOT NULL,
  `term_id` bigint(20) NOT NULL,
  `is_variation_attribute` tinyint(1) NOT NULL,
  `in_stock` tinyint(1) NOT NULL,
  PRIMARY KEY (`product_or_parent_id`,`term_id`,`product_id`,`taxonomy`),
  KEY `is_variation_attribute_term_id` (`is_variation_attribute`,`term_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wc_product_attributes_lookup`
--

LOCK TABLES `wp_wc_product_attributes_lookup` WRITE;
/*!40000 ALTER TABLE `wp_wc_product_attributes_lookup` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_wc_product_attributes_lookup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wc_product_download_directories`
--

DROP TABLE IF EXISTS `wp_wc_product_download_directories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_wc_product_download_directories` (
  `url_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(256) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`url_id`),
  KEY `url` (`url`(191))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wc_product_download_directories`
--

LOCK TABLES `wp_wc_product_download_directories` WRITE;
/*!40000 ALTER TABLE `wp_wc_product_download_directories` DISABLE KEYS */;
INSERT INTO `wp_wc_product_download_directories` VALUES
(1,'file:///var/www/html/wp-content/uploads/woocommerce_uploads/',1),
(2,'http://localhost:8889/wp-content/uploads/woocommerce_uploads/',1);
/*!40000 ALTER TABLE `wp_wc_product_download_directories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wc_product_meta_lookup`
--

DROP TABLE IF EXISTS `wp_wc_product_meta_lookup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_wc_product_meta_lookup` (
  `product_id` bigint(20) NOT NULL,
  `sku` varchar(100) DEFAULT '',
  `virtual` tinyint(1) DEFAULT 0,
  `downloadable` tinyint(1) DEFAULT 0,
  `min_price` decimal(19,4) DEFAULT NULL,
  `max_price` decimal(19,4) DEFAULT NULL,
  `onsale` tinyint(1) DEFAULT 0,
  `stock_quantity` double DEFAULT NULL,
  `stock_status` varchar(100) DEFAULT 'instock',
  `rating_count` bigint(20) DEFAULT 0,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `total_sales` bigint(20) DEFAULT 0,
  `tax_status` varchar(100) DEFAULT 'taxable',
  `tax_class` varchar(100) DEFAULT '',
  PRIMARY KEY (`product_id`),
  KEY `virtual` (`virtual`),
  KEY `downloadable` (`downloadable`),
  KEY `stock_status` (`stock_status`),
  KEY `stock_quantity` (`stock_quantity`),
  KEY `onsale` (`onsale`),
  KEY `min_max_price` (`min_price`,`max_price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wc_product_meta_lookup`
--

LOCK TABLES `wp_wc_product_meta_lookup` WRITE;
/*!40000 ALTER TABLE `wp_wc_product_meta_lookup` DISABLE KEYS */;
INSERT INTO `wp_wc_product_meta_lookup` VALUES
(6,'woo-vneck-tee',0,0,15.0000,20.0000,0,NULL,'instock',0,0.00,0,'taxable',''),
(7,'woo-hoodie',0,0,42.0000,45.0000,0,NULL,'instock',1,5.00,0,'taxable',''),
(8,'woo-hoodie-with-logo',0,0,45.0000,45.0000,0,NULL,'instock',0,0.00,0,'taxable',''),
(9,'woo-tshirt',0,0,18.0000,18.0000,0,NULL,'instock',0,0.00,0,'taxable',''),
(10,'woo-beanie',0,0,18.0000,18.0000,1,NULL,'instock',0,0.00,0,'taxable',''),
(11,'woo-belt',0,0,55.0000,55.0000,1,NULL,'instock',0,0.00,0,'taxable',''),
(12,'woo-cap',0,0,16.0000,16.0000,1,NULL,'instock',1,1.00,0,'taxable',''),
(13,'woo-sunglasses',0,0,90.0000,90.0000,0,NULL,'instock',0,0.00,0,'taxable',''),
(14,'woo-hoodie-with-pocket',0,0,35.0000,35.0000,1,NULL,'instock',0,0.00,0,'taxable',''),
(15,'woo-hoodie-with-zipper',0,0,45.0000,45.0000,0,NULL,'instock',0,0.00,0,'taxable',''),
(16,'woo-long-sleeve-tee',0,0,25.0000,25.0000,0,NULL,'instock',0,0.00,0,'taxable',''),
(17,'woo-polo',0,0,20.0000,20.0000,0,NULL,'instock',0,0.00,0,'taxable',''),
(18,'woo-album',1,1,15.0000,15.0000,0,NULL,'instock',0,0.00,0,'taxable',''),
(19,'woo-single',1,1,2.0000,2.0000,1,NULL,'instock',0,0.00,0,'taxable',''),
(20,'woo-vneck-tee-red',0,0,20.0000,20.0000,0,NULL,'instock',0,0.00,0,'taxable',''),
(21,'woo-vneck-tee-green',0,0,20.0000,20.0000,0,NULL,'instock',0,0.00,0,'taxable',''),
(22,'woo-vneck-tee-blue',0,0,15.0000,15.0000,0,NULL,'instock',0,0.00,0,'taxable',''),
(23,'woo-hoodie-red',0,0,42.0000,42.0000,1,NULL,'instock',0,0.00,0,'taxable',''),
(24,'woo-hoodie-green',0,0,45.0000,45.0000,0,NULL,'instock',0,0.00,0,'taxable',''),
(25,'woo-hoodie-blue',0,0,45.0000,45.0000,0,NULL,'instock',0,0.00,0,'taxable',''),
(26,'Woo-tshirt-logo',0,0,18.0000,18.0000,0,NULL,'outofstock',0,0.00,0,'taxable',''),
(27,'Woo-beanie-logo',0,0,18.0000,18.0000,1,NULL,'instock',0,0.00,0,'taxable',''),
(28,'logo-collection',0,0,18.0000,45.0000,0,NULL,'instock',0,0.00,0,'taxable',''),
(29,'wp-pennant',0,0,11.0500,11.0500,0,NULL,'instock',0,0.00,0,'taxable',''),
(30,'woo-hoodie-blue-logo',0,0,45.0000,45.0000,0,NULL,'instock',0,0.00,0,'taxable','');
/*!40000 ALTER TABLE `wp_wc_product_meta_lookup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wc_rate_limits`
--

DROP TABLE IF EXISTS `wp_wc_rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_wc_rate_limits` (
  `rate_limit_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rate_limit_key` varchar(200) NOT NULL,
  `rate_limit_expiry` bigint(20) unsigned NOT NULL,
  `rate_limit_remaining` smallint(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`rate_limit_id`),
  UNIQUE KEY `rate_limit_key` (`rate_limit_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wc_rate_limits`
--

LOCK TABLES `wp_wc_rate_limits` WRITE;
/*!40000 ALTER TABLE `wp_wc_rate_limits` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_wc_rate_limits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wc_reserved_stock`
--

DROP TABLE IF EXISTS `wp_wc_reserved_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_wc_reserved_stock` (
  `order_id` bigint(20) NOT NULL,
  `product_id` bigint(20) NOT NULL,
  `stock_quantity` double NOT NULL DEFAULT 0,
  `timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `expires` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`order_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wc_reserved_stock`
--

LOCK TABLES `wp_wc_reserved_stock` WRITE;
/*!40000 ALTER TABLE `wp_wc_reserved_stock` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_wc_reserved_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wc_tax_rate_classes`
--

DROP TABLE IF EXISTS `wp_wc_tax_rate_classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_wc_tax_rate_classes` (
  `tax_rate_class_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `slug` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`tax_rate_class_id`),
  UNIQUE KEY `slug` (`slug`(191))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wc_tax_rate_classes`
--

LOCK TABLES `wp_wc_tax_rate_classes` WRITE;
/*!40000 ALTER TABLE `wp_wc_tax_rate_classes` DISABLE KEYS */;
INSERT INTO `wp_wc_tax_rate_classes` VALUES
(1,'Reduced rate','reduced-rate'),
(2,'Zero rate','zero-rate');
/*!40000 ALTER TABLE `wp_wc_tax_rate_classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_wc_webhooks`
--

DROP TABLE IF EXISTS `wp_wc_webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_wc_webhooks` (
  `webhook_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(200) NOT NULL,
  `name` text NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `delivery_url` text NOT NULL,
  `secret` text NOT NULL,
  `topic` varchar(200) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_created_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `api_version` smallint(4) NOT NULL,
  `failure_count` smallint(10) NOT NULL DEFAULT 0,
  `pending_delivery` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`webhook_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_wc_webhooks`
--

LOCK TABLES `wp_wc_webhooks` WRITE;
/*!40000 ALTER TABLE `wp_wc_webhooks` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_wc_webhooks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_woocommerce_api_keys`
--

DROP TABLE IF EXISTS `wp_woocommerce_api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_woocommerce_api_keys` (
  `key_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `permissions` varchar(10) NOT NULL,
  `consumer_key` char(64) NOT NULL,
  `consumer_secret` char(43) NOT NULL,
  `nonces` longtext DEFAULT NULL,
  `truncated_key` char(7) NOT NULL,
  `last_access` datetime DEFAULT NULL,
  PRIMARY KEY (`key_id`),
  KEY `consumer_key` (`consumer_key`),
  KEY `consumer_secret` (`consumer_secret`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_woocommerce_api_keys`
--

LOCK TABLES `wp_woocommerce_api_keys` WRITE;
/*!40000 ALTER TABLE `wp_woocommerce_api_keys` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_woocommerce_api_keys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_woocommerce_attribute_taxonomies`
--

DROP TABLE IF EXISTS `wp_woocommerce_attribute_taxonomies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_woocommerce_attribute_taxonomies` (
  `attribute_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attribute_name` varchar(200) NOT NULL,
  `attribute_label` varchar(200) DEFAULT NULL,
  `attribute_type` varchar(20) NOT NULL,
  `attribute_orderby` varchar(20) NOT NULL,
  `attribute_public` int(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`attribute_id`),
  KEY `attribute_name` (`attribute_name`(20))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_woocommerce_attribute_taxonomies`
--

LOCK TABLES `wp_woocommerce_attribute_taxonomies` WRITE;
/*!40000 ALTER TABLE `wp_woocommerce_attribute_taxonomies` DISABLE KEYS */;
INSERT INTO `wp_woocommerce_attribute_taxonomies` VALUES
(1,'color','Color','select','menu_order',1),
(2,'size','Size','select','menu_order',1);
/*!40000 ALTER TABLE `wp_woocommerce_attribute_taxonomies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_woocommerce_downloadable_product_permissions`
--

DROP TABLE IF EXISTS `wp_woocommerce_downloadable_product_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_woocommerce_downloadable_product_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `download_id` varchar(36) NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `order_key` varchar(200) NOT NULL,
  `user_email` varchar(200) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `downloads_remaining` varchar(9) DEFAULT NULL,
  `access_granted` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `access_expires` datetime DEFAULT NULL,
  `download_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`permission_id`),
  KEY `download_order_key_product` (`product_id`,`order_id`,`order_key`(16),`download_id`),
  KEY `download_order_product` (`download_id`,`order_id`,`product_id`),
  KEY `order_id` (`order_id`),
  KEY `user_order_remaining_expires` (`user_id`,`order_id`,`downloads_remaining`,`access_expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_woocommerce_downloadable_product_permissions`
--

LOCK TABLES `wp_woocommerce_downloadable_product_permissions` WRITE;
/*!40000 ALTER TABLE `wp_woocommerce_downloadable_product_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_woocommerce_downloadable_product_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_woocommerce_log`
--

DROP TABLE IF EXISTS `wp_woocommerce_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_woocommerce_log` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `level` smallint(4) NOT NULL,
  `source` varchar(200) NOT NULL,
  `message` longtext NOT NULL,
  `context` longtext DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_woocommerce_log`
--

LOCK TABLES `wp_woocommerce_log` WRITE;
/*!40000 ALTER TABLE `wp_woocommerce_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_woocommerce_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_woocommerce_order_itemmeta`
--

DROP TABLE IF EXISTS `wp_woocommerce_order_itemmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_woocommerce_order_itemmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `order_item_id` (`order_item_id`),
  KEY `meta_key` (`meta_key`(32))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_woocommerce_order_itemmeta`
--

LOCK TABLES `wp_woocommerce_order_itemmeta` WRITE;
/*!40000 ALTER TABLE `wp_woocommerce_order_itemmeta` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_woocommerce_order_itemmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_woocommerce_order_items`
--

DROP TABLE IF EXISTS `wp_woocommerce_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_woocommerce_order_items` (
  `order_item_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_item_name` text NOT NULL,
  `order_item_type` varchar(200) NOT NULL DEFAULT '',
  `order_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_woocommerce_order_items`
--

LOCK TABLES `wp_woocommerce_order_items` WRITE;
/*!40000 ALTER TABLE `wp_woocommerce_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_woocommerce_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_woocommerce_payment_tokenmeta`
--

DROP TABLE IF EXISTS `wp_woocommerce_payment_tokenmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_woocommerce_payment_tokenmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_token_id` bigint(20) unsigned NOT NULL,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `payment_token_id` (`payment_token_id`),
  KEY `meta_key` (`meta_key`(32))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_woocommerce_payment_tokenmeta`
--

LOCK TABLES `wp_woocommerce_payment_tokenmeta` WRITE;
/*!40000 ALTER TABLE `wp_woocommerce_payment_tokenmeta` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_woocommerce_payment_tokenmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_woocommerce_payment_tokens`
--

DROP TABLE IF EXISTS `wp_woocommerce_payment_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_woocommerce_payment_tokens` (
  `token_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `gateway_id` varchar(200) NOT NULL,
  `token` text NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `type` varchar(200) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`token_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_woocommerce_payment_tokens`
--

LOCK TABLES `wp_woocommerce_payment_tokens` WRITE;
/*!40000 ALTER TABLE `wp_woocommerce_payment_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_woocommerce_payment_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_woocommerce_sessions`
--

DROP TABLE IF EXISTS `wp_woocommerce_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_woocommerce_sessions` (
  `session_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_key` char(32) NOT NULL,
  `session_value` longtext NOT NULL,
  `session_expiry` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `session_key` (`session_key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_woocommerce_sessions`
--

LOCK TABLES `wp_woocommerce_sessions` WRITE;
/*!40000 ALTER TABLE `wp_woocommerce_sessions` DISABLE KEYS */;
INSERT INTO `wp_woocommerce_sessions` VALUES
(1,'1','a:7:{s:4:\"cart\";s:6:\"a:0:{}\";s:11:\"cart_totals\";s:367:\"a:15:{s:8:\"subtotal\";i:0;s:12:\"subtotal_tax\";i:0;s:14:\"shipping_total\";i:0;s:12:\"shipping_tax\";i:0;s:14:\"shipping_taxes\";a:0:{}s:14:\"discount_total\";i:0;s:12:\"discount_tax\";i:0;s:19:\"cart_contents_total\";i:0;s:17:\"cart_contents_tax\";i:0;s:19:\"cart_contents_taxes\";a:0:{}s:9:\"fee_total\";i:0;s:7:\"fee_tax\";i:0;s:9:\"fee_taxes\";a:0:{}s:5:\"total\";i:0;s:9:\"total_tax\";i:0;}\";s:15:\"applied_coupons\";s:6:\"a:0:{}\";s:22:\"coupon_discount_totals\";s:6:\"a:0:{}\";s:26:\"coupon_discount_tax_totals\";s:6:\"a:0:{}\";s:21:\"removed_cart_contents\";s:6:\"a:0:{}\";s:8:\"customer\";s:767:\"a:28:{s:2:\"id\";s:1:\"1\";s:13:\"date_modified\";s:0:\"\";s:10:\"first_name\";s:0:\"\";s:9:\"last_name\";s:0:\"\";s:7:\"company\";s:0:\"\";s:5:\"phone\";s:0:\"\";s:5:\"email\";s:21:\"wordpress@example.com\";s:7:\"address\";s:0:\"\";s:9:\"address_1\";s:0:\"\";s:9:\"address_2\";s:0:\"\";s:4:\"city\";s:0:\"\";s:5:\"state\";s:2:\"CA\";s:8:\"postcode\";s:0:\"\";s:7:\"country\";s:2:\"US\";s:19:\"shipping_first_name\";s:0:\"\";s:18:\"shipping_last_name\";s:0:\"\";s:16:\"shipping_company\";s:0:\"\";s:14:\"shipping_phone\";s:0:\"\";s:16:\"shipping_address\";s:0:\"\";s:18:\"shipping_address_1\";s:0:\"\";s:18:\"shipping_address_2\";s:0:\"\";s:13:\"shipping_city\";s:0:\"\";s:14:\"shipping_state\";s:2:\"CA\";s:17:\"shipping_postcode\";s:0:\"\";s:16:\"shipping_country\";s:2:\"US\";s:13:\"is_vat_exempt\";s:0:\"\";s:19:\"calculated_shipping\";s:0:\"\";s:9:\"meta_data\";s:2:\"[]\";}\";}',1712823160);
/*!40000 ALTER TABLE `wp_woocommerce_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_woocommerce_shipping_zone_locations`
--

DROP TABLE IF EXISTS `wp_woocommerce_shipping_zone_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_woocommerce_shipping_zone_locations` (
  `location_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `zone_id` bigint(20) unsigned NOT NULL,
  `location_code` varchar(200) NOT NULL,
  `location_type` varchar(40) NOT NULL,
  PRIMARY KEY (`location_id`),
  KEY `zone_id` (`zone_id`),
  KEY `location_type_code` (`location_type`(10),`location_code`(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_woocommerce_shipping_zone_locations`
--

LOCK TABLES `wp_woocommerce_shipping_zone_locations` WRITE;
/*!40000 ALTER TABLE `wp_woocommerce_shipping_zone_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_woocommerce_shipping_zone_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_woocommerce_shipping_zone_methods`
--

DROP TABLE IF EXISTS `wp_woocommerce_shipping_zone_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_woocommerce_shipping_zone_methods` (
  `zone_id` bigint(20) unsigned NOT NULL,
  `instance_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `method_id` varchar(200) NOT NULL,
  `method_order` bigint(20) unsigned NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`instance_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_woocommerce_shipping_zone_methods`
--

LOCK TABLES `wp_woocommerce_shipping_zone_methods` WRITE;
/*!40000 ALTER TABLE `wp_woocommerce_shipping_zone_methods` DISABLE KEYS */;
INSERT INTO `wp_woocommerce_shipping_zone_methods` VALUES
(0,1,'flat_rate',1,1),
(0,2,'free_shipping',2,1);
/*!40000 ALTER TABLE `wp_woocommerce_shipping_zone_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_woocommerce_shipping_zones`
--

DROP TABLE IF EXISTS `wp_woocommerce_shipping_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_woocommerce_shipping_zones` (
  `zone_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `zone_name` varchar(200) NOT NULL,
  `zone_order` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`zone_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_woocommerce_shipping_zones`
--

LOCK TABLES `wp_woocommerce_shipping_zones` WRITE;
/*!40000 ALTER TABLE `wp_woocommerce_shipping_zones` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_woocommerce_shipping_zones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_woocommerce_tax_rate_locations`
--

DROP TABLE IF EXISTS `wp_woocommerce_tax_rate_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_woocommerce_tax_rate_locations` (
  `location_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `location_code` varchar(200) NOT NULL,
  `tax_rate_id` bigint(20) unsigned NOT NULL,
  `location_type` varchar(40) NOT NULL,
  PRIMARY KEY (`location_id`),
  KEY `tax_rate_id` (`tax_rate_id`),
  KEY `location_type_code` (`location_type`(10),`location_code`(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_woocommerce_tax_rate_locations`
--

LOCK TABLES `wp_woocommerce_tax_rate_locations` WRITE;
/*!40000 ALTER TABLE `wp_woocommerce_tax_rate_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_woocommerce_tax_rate_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_woocommerce_tax_rates`
--

DROP TABLE IF EXISTS `wp_woocommerce_tax_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_woocommerce_tax_rates` (
  `tax_rate_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tax_rate_country` varchar(2) NOT NULL DEFAULT '',
  `tax_rate_state` varchar(200) NOT NULL DEFAULT '',
  `tax_rate` varchar(8) NOT NULL DEFAULT '',
  `tax_rate_name` varchar(200) NOT NULL DEFAULT '',
  `tax_rate_priority` bigint(20) unsigned NOT NULL,
  `tax_rate_compound` int(1) NOT NULL DEFAULT 0,
  `tax_rate_shipping` int(1) NOT NULL DEFAULT 1,
  `tax_rate_order` bigint(20) unsigned NOT NULL,
  `tax_rate_class` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`tax_rate_id`),
  KEY `tax_rate_country` (`tax_rate_country`),
  KEY `tax_rate_state` (`tax_rate_state`(2)),
  KEY `tax_rate_class` (`tax_rate_class`(10)),
  KEY `tax_rate_priority` (`tax_rate_priority`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_woocommerce_tax_rates`
--

LOCK TABLES `wp_woocommerce_tax_rates` WRITE;
/*!40000 ALTER TABLE `wp_woocommerce_tax_rates` DISABLE KEYS */;
INSERT INTO `wp_woocommerce_tax_rates` VALUES
(1,'','','20.0000','',1,0,1,0,''),
(2,'','','10.0000','',1,0,1,0,'reduced-rate'),
(3,'','','0.0000','',1,0,1,0,'zero-rate');
/*!40000 ALTER TABLE `wp_woocommerce_tax_rates` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-04-09  8:13:25
