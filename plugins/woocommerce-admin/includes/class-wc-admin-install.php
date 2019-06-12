<?php
/**
 * Installation related functions and actions.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Install Class.
 */
class WC_Admin_Install {
	/**
	 * Plugin version option name.
	 */
	const VERSION_OPTION = 'wc_admin_version';

	/**
	 * DB updates and callbacks that need to be run per version.
	 *
	 * @var array
	 */
	protected static $db_updates = array();

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'check_version' ), 5 );
		add_filter( 'wpmu_drop_tables', array( __CLASS__, 'wpmu_drop_tables' ) );

		// Add wc-admin report tables to list of WooCommerce tables.
		add_filter( 'woocommerce_install_get_tables', array( __CLASS__, 'add_tables' ) );
	}

	/**
	 * Check WC Admin version and run the updater is required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if (
			! defined( 'IFRAME_REQUEST' ) &&
			version_compare( get_option( self::VERSION_OPTION ), WC_ADMIN_VERSION_NUMBER, '<' )
		) {
			self::install();
			do_action( 'wc_admin_updated' );
		}
	}

	/**
	 * Install WC Admin.
	 */
	public static function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine.
		if ( 'yes' === get_transient( 'wc_admin_installing' ) ) {
			return;
		}

		// If we made it till here nothing is running yet, lets set the transient now.
		set_transient( 'wc_admin_installing', 'yes', MINUTE_IN_SECONDS * 10 );
		wc_maybe_define_constant( 'WC_ADMIN_INSTALLING', true );

		self::create_tables();
		self::create_events();
		self::create_notes();
		self::update_db_version();

		delete_transient( 'wc_admin_installing' );

		update_option( 'wc_admin_install_timestamp', time() );
		do_action( 'wc_admin_installed' );
	}

	/**
	 * Get database schema.
	 *
	 * @return string
	 */
	protected static function get_schema() {
		global $wpdb;

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables = "
		CREATE TABLE {$wpdb->prefix}wc_order_stats (
			order_id bigint(20) unsigned NOT NULL,
			parent_id bigint(20) unsigned DEFAULT 0 NOT NULL,
			date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			date_created_gmt datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			num_items_sold int(11) DEFAULT 0 NOT NULL,
			gross_total double DEFAULT 0 NOT NULL,
			tax_total double DEFAULT 0 NOT NULL,
			shipping_total double DEFAULT 0 NOT NULL,
			net_total double DEFAULT 0 NOT NULL,
			returning_customer boolean DEFAULT NULL,
			status varchar(200) NOT NULL,
			customer_id BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY (order_id),
			KEY date_created (date_created),
			KEY customer_id (customer_id),
			KEY status (status)
		) $collate;
		CREATE TABLE {$wpdb->prefix}wc_order_product_lookup (
			order_item_id BIGINT UNSIGNED NOT NULL,
			order_id BIGINT UNSIGNED NOT NULL,
			product_id BIGINT UNSIGNED NOT NULL,
			variation_id BIGINT UNSIGNED NOT NULL,
			customer_id BIGINT UNSIGNED NULL,
			date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			product_qty INT NOT NULL,
			product_net_revenue double DEFAULT 0 NOT NULL,
			product_gross_revenue double DEFAULT 0 NOT NULL,
			coupon_amount double DEFAULT 0 NOT NULL,
			tax_amount double DEFAULT 0 NOT NULL,
			shipping_amount double DEFAULT 0 NOT NULL,
			shipping_tax_amount double DEFAULT 0 NOT NULL,
			PRIMARY KEY  (order_item_id),
			KEY order_id (order_id),
			KEY product_id (product_id),
			KEY customer_id (customer_id),
			KEY date_created (date_created)
		) $collate;
		CREATE TABLE {$wpdb->prefix}wc_order_tax_lookup (
			order_id BIGINT UNSIGNED NOT NULL,
			tax_rate_id BIGINT UNSIGNED NOT NULL,
			date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			shipping_tax double DEFAULT 0 NOT NULL,
			order_tax double DEFAULT 0 NOT NULL,
			total_tax double DEFAULT 0 NOT NULL,
			PRIMARY KEY (order_id, tax_rate_id),
			KEY tax_rate_id (tax_rate_id),
			KEY date_created (date_created)
		) $collate;
		CREATE TABLE {$wpdb->prefix}wc_order_coupon_lookup (
			order_id BIGINT UNSIGNED NOT NULL,
			coupon_id BIGINT UNSIGNED NOT NULL,
			date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			discount_amount double DEFAULT 0 NOT NULL,
			PRIMARY KEY (order_id, coupon_id),
			KEY coupon_id (coupon_id),
			KEY date_created (date_created)
		) $collate;
		CREATE TABLE {$wpdb->prefix}wc_admin_notes (
			note_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			type varchar(20) NOT NULL,
			locale varchar(20) NOT NULL,
			title longtext NOT NULL,
			content longtext NOT NULL,
			icon varchar(200) NOT NULL,
			content_data longtext NULL default null,
			status varchar(200) NOT NULL,
			source varchar(200) NOT NULL,
			date_created datetime NOT NULL default '0000-00-00 00:00:00',
			date_reminder datetime NULL default null,
			is_snoozable boolean DEFAULT 0 NOT NULL,
			PRIMARY KEY (note_id)
		) $collate;
		CREATE TABLE {$wpdb->prefix}wc_admin_note_actions (
			action_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			note_id BIGINT UNSIGNED NOT NULL,
			name varchar(255) NOT NULL,
			label varchar(255) NOT NULL,
			query longtext NOT NULL,
			status varchar(255) NOT NULL,
			is_primary boolean DEFAULT 0 NOT NULL,
			PRIMARY KEY (action_id),
			KEY note_id (note_id)
		) $collate;
		CREATE TABLE {$wpdb->prefix}wc_customer_lookup (
			customer_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED DEFAULT NULL,
			username varchar(60) DEFAULT '' NOT NULL,
			first_name varchar(255) NOT NULL,
			last_name varchar(255) NOT NULL,
			email varchar(100) NULL default NULL,
			date_last_active timestamp NULL default null,
			date_registered timestamp NULL default null,
			country char(2) DEFAULT '' NOT NULL,
			postcode varchar(20) DEFAULT '' NOT NULL,
			city varchar(100) DEFAULT '' NOT NULL,
			PRIMARY KEY (customer_id),
			UNIQUE KEY user_id (user_id),
			KEY email (email)
		) $collate;
		";

		return $tables;
	}

	/**
	 * Create database tables.
	 */
	public static function create_tables() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( self::get_schema() );
	}

	/**
	 * Return a list of tables. Used to make sure all WC Admin tables are dropped
	 * when uninstalling the plugin in a single site or multi site environment.
	 *
	 * @return array WC tables.
	 */
	public static function get_tables() {
		global $wpdb;

		return array(
			"{$wpdb->prefix}wc_order_stats",
			"{$wpdb->prefix}wc_order_product_lookup",
			"{$wpdb->prefix}wc_order_tax_lookup",
			"{$wpdb->prefix}wc_order_coupon_lookup",
			"{$wpdb->prefix}wc_admin_notes",
			"{$wpdb->prefix}wc_admin_note_actions",
			"{$wpdb->prefix}wc_customer_lookup",
		);
	}

	/**
	 * Adds new tables.
	 *
	 * @param array $wc_tables List of WooCommerce tables.
	 * @return array
	 */
	public static function add_tables( $wc_tables ) {
		return array_merge(
			$wc_tables,
			self::get_tables()
		);
	}

	/**
	 * Uninstall tables when MU blog is deleted.
	 *
	 * @param array $tables List of tables that will be deleted by WP.
	 *
	 * @return string[]
	 */
	public static function wpmu_drop_tables( $tables ) {
		return array_merge( $tables, self::get_tables() );
	}

	/**
	 * Update WC Admin version to current.
	 */
	protected static function update_db_version() {
		delete_option( self::VERSION_OPTION );
		add_option( self::VERSION_OPTION, WC_ADMIN_VERSION_NUMBER );
	}

	/**
	 * Schedule cron events.
	 */
	public static function create_events() {
		if ( ! wp_next_scheduled( 'wc_admin_daily' ) ) {
			wp_schedule_event( time(), 'daily', 'wc_admin_daily' );
		}
	}

	/**
	 * Create notes.
	 */
	protected static function create_notes() {
		WC_Admin_Notes_Historical_Data::add_note();
		WC_Admin_Notes_Welcome_Message::add_welcome_note();
	}

	/**
	 * Delete all data from tables.
	 */
	public static function delete_table_data() {
		global $wpdb;

		$tables = self::get_tables();

		foreach ( $tables as $table ) {
			$wpdb->query( "TRUNCATE TABLE {$table}" ); // WPCS: unprepared SQL ok.
		}
	}
}

WC_Admin_Install::init();
