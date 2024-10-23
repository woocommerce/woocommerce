<?php
/**
 * Installation related functions and actions.
 *
 * @package WooCommerce\Classes
 * @version 3.0.0
 */

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Admin\Notes\Notes;
use Automattic\WooCommerce\Internal\TransientFiles\TransientFilesEngine;
use Automattic\WooCommerce\Internal\DataStores\Orders\{ CustomOrdersTableController, DataSynchronizer, OrdersTableDataStore };
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\ProductAttributesLookup\DataRegenerator;
use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as Download_Directories;
use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Synchronize as Download_Directories_Sync;
use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;
use Automattic\WooCommerce\Internal\WCCom\ConnectionHelper as WCConnectionHelper;
use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;
use Automattic\WooCommerce\Utilities\{ OrderUtil, PluginUtil };
use Automattic\WooCommerce\Internal\Utilities\PluginInstaller;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Install Class.
 */
class WC_Install {
	use AccessiblePrivateMethods;

	/**
	 * DB updates and callbacks that need to be run per version.
	 *
	 * Please note that these functions are invoked when WooCommerce is updated from a previous version,
	 * but NOT when WooCommerce is newly installed.
	 *
	 * Database schema changes must be incorporated to the SQL returned by get_schema, which is applied
	 * via dbDelta at both install and update time. If any other kind of database change is required
	 * at install time (e.g. populating tables), use the 'woocommerce_installed' hook.
	 *
	 * @var array
	 */
	private static $db_updates = array(
		'2.0.0' => array(
			'wc_update_200_file_paths',
			'wc_update_200_permalinks',
			'wc_update_200_subcat_display',
			'wc_update_200_taxrates',
			'wc_update_200_line_items',
			'wc_update_200_images',
			'wc_update_200_db_version',
		),
		'2.0.9' => array(
			'wc_update_209_brazillian_state',
			'wc_update_209_db_version',
		),
		'2.1.0' => array(
			'wc_update_210_remove_pages',
			'wc_update_210_file_paths',
			'wc_update_210_db_version',
		),
		'2.2.0' => array(
			'wc_update_220_shipping',
			'wc_update_220_order_status',
			'wc_update_220_variations',
			'wc_update_220_attributes',
			'wc_update_220_db_version',
		),
		'2.3.0' => array(
			'wc_update_230_options',
			'wc_update_230_db_version',
		),
		'2.4.0' => array(
			'wc_update_240_options',
			'wc_update_240_shipping_methods',
			'wc_update_240_api_keys',
			'wc_update_240_refunds',
			'wc_update_240_db_version',
		),
		'2.4.1' => array(
			'wc_update_241_variations',
			'wc_update_241_db_version',
		),
		'2.5.0' => array(
			'wc_update_250_currency',
			'wc_update_250_db_version',
		),
		'2.6.0' => array(
			'wc_update_260_options',
			'wc_update_260_termmeta',
			'wc_update_260_zones',
			'wc_update_260_zone_methods',
			'wc_update_260_refunds',
			'wc_update_260_db_version',
		),
		'3.0.0' => array(
			'wc_update_300_grouped_products',
			'wc_update_300_settings',
			'wc_update_300_product_visibility',
			'wc_update_300_db_version',
		),
		'3.1.0' => array(
			'wc_update_310_downloadable_products',
			'wc_update_310_old_comments',
			'wc_update_310_db_version',
		),
		'3.1.2' => array(
			'wc_update_312_shop_manager_capabilities',
			'wc_update_312_db_version',
		),
		'3.2.0' => array(
			'wc_update_320_mexican_states',
			'wc_update_320_db_version',
		),
		'3.3.0' => array(
			'wc_update_330_image_options',
			'wc_update_330_webhooks',
			'wc_update_330_product_stock_status',
			'wc_update_330_set_default_product_cat',
			'wc_update_330_clear_transients',
			'wc_update_330_set_paypal_sandbox_credentials',
			'wc_update_330_db_version',
		),
		'3.4.0' => array(
			'wc_update_340_states',
			'wc_update_340_state',
			'wc_update_340_last_active',
			'wc_update_340_db_version',
		),
		'3.4.3' => array(
			'wc_update_343_cleanup_foreign_keys',
			'wc_update_343_db_version',
		),
		'3.4.4' => array(
			'wc_update_344_recreate_roles',
			'wc_update_344_db_version',
		),
		'3.5.0' => array(
			'wc_update_350_reviews_comment_type',
			'wc_update_350_db_version',
		),
		'3.5.2' => array(
			'wc_update_352_drop_download_log_fk',
		),
		'3.5.4' => array(
			'wc_update_354_modify_shop_manager_caps',
			'wc_update_354_db_version',
		),
		'3.6.0' => array(
			'wc_update_360_product_lookup_tables',
			'wc_update_360_term_meta',
			'wc_update_360_downloadable_product_permissions_index',
			'wc_update_360_db_version',
		),
		'3.7.0' => array(
			'wc_update_370_tax_rate_classes',
			'wc_update_370_mro_std_currency',
			'wc_update_370_db_version',
		),
		'3.9.0' => array(
			'wc_update_390_move_maxmind_database',
			'wc_update_390_change_geolocation_database_update_cron',
			'wc_update_390_db_version',
		),
		'4.0.0' => array(
			'wc_update_product_lookup_tables',
			'wc_update_400_increase_size_of_column',
			'wc_update_400_reset_action_scheduler_migration_status',
			'wc_admin_update_0201_order_status_index',
			'wc_admin_update_0230_rename_gross_total',
			'wc_admin_update_0251_remove_unsnooze_action',
			'wc_update_400_db_version',
		),
		'4.4.0' => array(
			'wc_update_440_insert_attribute_terms_for_variable_products',
			'wc_admin_update_110_remove_facebook_note',
			'wc_admin_update_130_remove_dismiss_action_from_tracking_opt_in_note',
			'wc_update_440_db_version',
		),
		'4.5.0' => array(
			'wc_update_450_sanitize_coupons_code',
			'wc_update_450_db_version',
		),
		'5.0.0' => array(
			'wc_update_500_fix_product_review_count',
			'wc_admin_update_160_remove_facebook_note',
			'wc_admin_update_170_homescreen_layout',
			'wc_update_500_db_version',
		),
		'5.6.0' => array(
			'wc_update_560_create_refund_returns_page',
			'wc_update_560_db_version',
		),
		'6.0.0' => array(
			'wc_update_600_migrate_rate_limit_options',
			'wc_admin_update_270_delete_report_downloads',
			'wc_admin_update_271_update_task_list_options',
			'wc_admin_update_280_order_status',
			'wc_admin_update_290_update_apperance_task_option',
			'wc_admin_update_290_delete_default_homepage_layout_option',
			'wc_update_600_db_version',
		),
		'6.3.0' => array(
			'wc_update_630_create_product_attributes_lookup_table',
			'wc_admin_update_300_update_is_read_from_last_read',
			'wc_update_630_db_version',
		),
		'6.4.0' => array(
			'wc_update_640_add_primary_key_to_product_attributes_lookup_table',
			'wc_admin_update_340_remove_is_primary_from_note_action',
			'wc_update_640_db_version',
		),
		'6.5.0' => array(
			'wc_update_650_approved_download_directories',
		),
		'6.5.1' => array(
			'wc_update_651_approved_download_directories',
		),
		'6.7.0' => array(
			'wc_update_670_purge_comments_count_cache',
			'wc_update_670_delete_deprecated_remote_inbox_notifications_option',
		),
		'7.0.0' => array(
			'wc_update_700_remove_download_log_fk',
			'wc_update_700_remove_recommended_marketing_plugins_transient',
		),
		'7.2.1' => array(
			'wc_update_721_adjust_new_zealand_states',
			'wc_update_721_adjust_ukraine_states',
		),
		'7.2.2' => array(
			'wc_update_722_adjust_new_zealand_states',
			'wc_update_722_adjust_ukraine_states',
		),
		'7.5.0' => array(
			'wc_update_750_add_columns_to_order_stats_table',
			'wc_update_750_disable_new_product_management_experience',
		),
		'7.7.0' => array(
			'wc_update_770_remove_multichannel_marketing_feature_options',
		),
		'8.1.0' => array(
			'wc_update_810_migrate_transactional_metadata_for_hpos',
		),
		'8.6.0' => array(
			'wc_update_860_remove_recommended_marketing_plugins_transient',
		),
		'8.7.0' => array(
			'wc_update_870_prevent_listing_of_transient_files_directory',
		),
		'8.9.0' => array(
			'wc_update_890_update_connect_to_woocommerce_note',
			'wc_update_890_update_paypal_standard_load_eligibility',
		),
		'8.9.1' => array(
			'wc_update_891_create_plugin_autoinstall_history_option',
		),
		'9.1.0' => array(
			'wc_update_910_add_launch_your_store_tour_option',
			'wc_update_910_remove_obsolete_user_meta',
		),
		'9.2.0' => array(
			'wc_update_920_add_wc_hooked_blocks_version_option',
		),
		'9.3.0' => array(
			'wc_update_930_add_woocommerce_coming_soon_option',
			'wc_update_930_migrate_user_meta_for_launch_your_store_tour',
		),
		'9.4.0' => array(
			'wc_update_940_add_phone_to_order_address_fts_index',
			'wc_update_940_remove_help_panel_highlight_shown',
		),
		'9.5.0' => array(
			'wc_update_950_add_brands_enabled_option',
		),
	);

	/**
	 * Option name used to track new installations of WooCommerce.
	 *
	 * @var string
	 */
	const NEWLY_INSTALLED_OPTION = 'woocommerce_newly_installed';

	/**
	 * Option name used to track new installation versions of WooCommerce.
	 *
	 * @var string
	 */
	const INITIAL_INSTALLED_VERSION = 'woocommerce_initial_installed_version';

	/**
	 * Option name used to uniquely identify installations of WooCommerce.
	 *
	 * @var string
	 */
	const STORE_ID_OPTION = 'woocommerce_store_id';

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'init', array( __CLASS__, 'manual_database_update' ), 20 );
		add_action( 'woocommerce_newly_installed', array( __CLASS__, 'maybe_enable_hpos' ), 20 );
		add_action( 'woocommerce_newly_installed', array( __CLASS__, 'add_coming_soon_option' ), 20 );
		add_action( 'admin_init', array( __CLASS__, 'wc_admin_db_update_notice' ) );
		add_action( 'admin_init', array( __CLASS__, 'add_admin_note_after_page_created' ) );
		add_action( 'woocommerce_run_update_callback', array( __CLASS__, 'run_update_callback' ) );
		add_action( 'woocommerce_update_db_to_current_version', array( __CLASS__, 'update_db_version' ) );
		add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
		add_action( 'woocommerce_page_created', array( __CLASS__, 'page_created' ), 10, 2 );
		add_filter( 'plugin_action_links_' . WC_PLUGIN_BASENAME, array( __CLASS__, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
		add_filter( 'wpmu_drop_tables', array( __CLASS__, 'wpmu_drop_tables' ) );
		add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );
		self::add_action( 'admin_init', array( __CLASS__, 'newly_installed' ) );
		self::add_action( 'woocommerce_activate_legacy_rest_api_plugin', array( __CLASS__, 'maybe_install_legacy_api_plugin' ) );
	}

	/**
	 * Trigger `woocommerce_newly_installed` action for new installations.
	 *
	 * @since 8.0.0
	 */
	private static function newly_installed() {
		if ( 'yes' === get_option( self::NEWLY_INSTALLED_OPTION, false ) ) {
			/**
			 * Run when WooCommerce has been installed for the first time.
			 *
			 * @since 6.5.0
			 */
			do_action( 'woocommerce_newly_installed' );
			do_action_deprecated( 'woocommerce_admin_newly_installed', array(), '6.5.0', 'woocommerce_newly_installed' );

			update_option( self::NEWLY_INSTALLED_OPTION, 'no' );

			/**
			 * This option is used to track the initial version of WooCommerce that was installed.
			 *
			 * @since 9.2.0
			 */
			add_option( self::INITIAL_INSTALLED_VERSION, WC()->version, '', false );
		}
	}

	/**
	 * Check WooCommerce version and run the updater is required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		$wc_version      = get_option( 'woocommerce_version' );
		$wc_code_version = WC()->version;
		$requires_update = version_compare( $wc_version, $wc_code_version, '<' );
		if ( ! Constants::is_defined( 'IFRAME_REQUEST' ) && $requires_update ) {
			self::install();
			/**
			 * Run after WooCommerce has been updated.
			 *
			 * @since 2.2.0
			 */
			do_action( 'woocommerce_updated' );
			do_action_deprecated( 'woocommerce_admin_updated', array(), $wc_code_version, 'woocommerce_updated' );
		}
	}

	/**
	 * Performan manual database update when triggered by WooCommerce System Tools.
	 *
	 * @since 3.6.5
	 */
	public static function manual_database_update() {
		$blog_id = get_current_blog_id();

		add_action( 'wp_' . $blog_id . '_wc_updater_cron', array( __CLASS__, 'run_manual_database_update' ) );
	}

	/**
	 * Add WC Admin based db update notice.
	 *
	 * @since 4.0.0
	 */
	public static function wc_admin_db_update_notice() {
		if (
			WC()->is_wc_admin_active() &&
			false !== get_option( 'woocommerce_admin_install_timestamp' )
		) {
			new WC_Notes_Run_Db_Update();
		}
	}

	/**
	 * Run manual database update.
	 */
	public static function run_manual_database_update() {
		self::update();
	}

	/**
	 * Run an update callback when triggered by ActionScheduler.
	 *
	 * @param string $update_callback Callback name.
	 *
	 * @since 3.6.0
	 */
	public static function run_update_callback( $update_callback ) {
		include_once __DIR__ . '/wc-update-functions.php';

		if ( is_callable( $update_callback ) ) {
			self::run_update_callback_start( $update_callback );
			$result = (bool) call_user_func( $update_callback );
			self::run_update_callback_end( $update_callback, $result );
		}
	}

	/**
	 * Triggered when a callback will run.
	 *
	 * @since 3.6.0
	 * @param string $callback Callback name.
	 */
	protected static function run_update_callback_start( $callback ) {
		wc_maybe_define_constant( 'WC_UPDATING', true );
	}

	/**
	 * Triggered when a callback has ran.
	 *
	 * @since 3.6.0
	 * @param string $callback Callback name.
	 * @param bool   $result Return value from callback. Non-false need to run again.
	 */
	protected static function run_update_callback_end( $callback, $result ) {
		if ( $result ) {
			WC()->queue()->add(
				'woocommerce_run_update_callback',
				array(
					'update_callback' => $callback,
				),
				'woocommerce-db-updates'
			);
		}
	}

	/**
	 * Install actions when a update button is clicked within the admin area.
	 *
	 * This function is hooked into admin_init to affect admin only.
	 */
	public static function install_actions() {
		if ( ! empty( $_GET['do_update_woocommerce'] ) ) { // WPCS: input var ok.
			check_admin_referer( 'wc_db_update', 'wc_db_update_nonce' );
			self::update();
			WC_Admin_Notices::add_notice( 'update', true );

			if ( ! empty( $_GET['return_url'] ) ) { // WPCS: input var ok.
				$return_url = esc_url_raw( wp_unslash( $_GET['return_url'] ) );
				wp_safe_redirect( $return_url ); // WPCS: input var ok.
			}
		}
	}

	/**
	 * Install WC.
	 */
	public static function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine.
		if ( self::is_installing() ) {
			return;
		}

		// If we made it till here nothing is running yet, lets set the transient now.
		set_transient( 'wc_installing', 'yes', MINUTE_IN_SECONDS * 10 );
		wc_maybe_define_constant( 'WC_INSTALLING', true );

		try {
			self::install_core();
		} finally {
			delete_transient( 'wc_installing' );
		}

		// Use add_option() here to avoid overwriting this value with each
		// plugin version update. We base plugin age off of this value.
		add_option( 'woocommerce_admin_install_timestamp', time() );

		// Force a flush of rewrite rules even if the corresponding hook isn't initialized yet.
		if ( ! has_action( 'woocommerce_flush_rewrite_rules' ) ) {
			flush_rewrite_rules();
		}

		/**
		 * Flush the rewrite rules after install or update.
		 *
		 * @since 2.7.0
		 */
		do_action( 'woocommerce_flush_rewrite_rules' );
		/**
		 * Run after WooCommerce has been installed or updated.
		 *
		 * @since 3.2.0
		 */
		do_action( 'woocommerce_installed' );
		/**
		 * Run after WooCommerce Admin has been installed or updated.
		 *
		 * @since 6.5.0
		 */
		do_action( 'woocommerce_admin_installed' );
	}

	/**
	 * Core function that performs the WooCommerce install.
	 */
	private static function install_core() {
		if ( self::is_new_install() && ! get_option( self::NEWLY_INSTALLED_OPTION, false ) ) {
			update_option( self::NEWLY_INSTALLED_OPTION, 'yes' );
		}

		WC()->wpdb_table_fix();
		self::remove_admin_notices();
		self::create_tables();
		self::verify_base_tables();
		self::create_options();
		self::migrate_options();
		self::create_roles();
		self::setup_environment();
		self::create_terms();
		self::create_cron_jobs();
		self::delete_obsolete_notes();
		self::create_files();
		self::maybe_create_pages();
		self::maybe_set_activation_transients();
		self::set_paypal_standard_load_eligibility();
		self::update_wc_version();
		self::maybe_update_db_version();
		self::maybe_set_store_id();
		self::maybe_install_legacy_api_plugin();
		self::maybe_activate_legacy_api_enabled_option();
	}

	/**
	 * Returns true if we're installing.
	 *
	 * @return bool
	 */
	private static function is_installing() {
		return 'yes' === get_transient( 'wc_installing' );
	}

	/**
	 * Check if all the base tables are present.
	 *
	 * @param bool $modify_notice Whether to modify notice based on if all tables are present.
	 * @param bool $execute       Whether to execute get_schema queries as well.
	 *
	 * @return array List of queries.
	 */
	public static function verify_base_tables( $modify_notice = true, $execute = false ) {
		if ( $execute ) {
			self::create_tables();
		}

		$schema = self::get_schema();

		$hpos_settings = filter_var_array(
			array(
				'cot'       => get_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION ),
				'data_sync' => get_option( DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION ),
			),
			array(
				'cot'       => FILTER_VALIDATE_BOOLEAN,
				'data_sync' => FILTER_VALIDATE_BOOLEAN,
			)
		);
		if ( in_array( true, $hpos_settings, true ) ) {
			$schema .= wc_get_container()
				->get( OrdersTableDataStore::class )
				->get_database_schema();
		}

		$missing_tables = wc_get_container()
			->get( DatabaseUtil::class )
			->get_missing_tables( $schema );

		if ( 0 < count( $missing_tables ) ) {
			if ( $modify_notice ) {
				WC_Admin_Notices::add_notice( 'base_tables_missing' );
			}
			update_option( 'woocommerce_schema_missing_tables', $missing_tables );
		} else {
			if ( $modify_notice ) {
				WC_Admin_Notices::remove_notice( 'base_tables_missing' );
			}
			update_option( 'woocommerce_schema_version', WC()->db_version );
			delete_option( 'woocommerce_schema_missing_tables' );
		}
		return $missing_tables;
	}

	/**
	 * Reset any notices added to admin.
	 *
	 * @since 3.2.0
	 */
	private static function remove_admin_notices() {
		include_once __DIR__ . '/admin/class-wc-admin-notices.php';

		WC_Admin_Notices::remove_all_notices();
	}

	/**
	 * Setup WC environment - post types, taxonomies, endpoints.
	 *
	 * @since 3.2.0
	 */
	private static function setup_environment() {
		WC_Post_types::register_post_types();
		WC_Post_types::register_taxonomies();
		WC()->query->init_query_vars();
		WC()->query->add_endpoints();
		WC_Auth::add_endpoint();
		TransientFilesEngine::add_endpoint();
	}

	/**
	 * Is this a brand new WC install?
	 *
	 * A brand new install has no version yet. Also treat empty installs as 'new'.
	 *
	 * @since  3.2.0
	 * @return boolean
	 */
	public static function is_new_install() {
		return is_null( get_option( 'woocommerce_version', null ) )
			|| (
				-1 === wc_get_page_id( 'shop' )
				&& 0 === array_sum( (array) wp_count_posts( 'product' ) )
			);
	}

	/**
	 * Is a DB update needed?
	 *
	 * @since  3.2.0
	 * @return boolean
	 */
	public static function needs_db_update() {
		$current_db_version = get_option( 'woocommerce_db_version', null );
		$updates            = self::get_db_update_callbacks();
		$update_versions    = array_keys( $updates );
		usort( $update_versions, 'version_compare' );

		return ! is_null( $current_db_version ) && version_compare( $current_db_version, end( $update_versions ), '<' );
	}

	/**
	 * See if we need to set redirect transients for activation or not.
	 *
	 * @since 4.6.0
	 */
	private static function maybe_set_activation_transients() {
		if ( self::is_new_install() ) {
			set_transient( '_wc_activation_redirect', 1, 30 );
		}
	}

	/**
	 * See if we need to show or run database updates during install.
	 *
	 * @since 3.2.0
	 */
	private static function maybe_update_db_version() {
		if ( self::needs_db_update() ) {
			/**
			 * Allow WooCommerce to auto-update without prompting the user.
			 *
			 * @since 3.2.0
			 */
			if ( apply_filters( 'woocommerce_enable_auto_update_db', false ) ) {
				self::update();
			} else {
				WC_Admin_Notices::add_notice( 'update', true );
			}
		} else {
			self::update_db_version();
		}
	}

	/**
	 * Set the Store ID if not already present.
	 *
	 * @since 8.4.0
	 */
	public static function maybe_set_store_id() {
		if ( ! get_option( self::STORE_ID_OPTION, false ) ) {
			add_option( self::STORE_ID_OPTION, wp_generate_uuid4() );
		}
	}

	/**
	 * Update WC version to current.
	 */
	private static function update_wc_version() {
		update_option( 'woocommerce_version', WC()->version );
	}

	/**
	 * Get list of DB update callbacks.
	 *
	 * @since  3.0.0
	 * @return array
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/**
	 * Push all needed DB updates to the queue for processing.
	 */
	private static function update() {
		$current_db_version = get_option( 'woocommerce_db_version' );
		$loop               = 0;

		foreach ( self::get_db_update_callbacks() as $version => $update_callbacks ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				foreach ( $update_callbacks as $update_callback ) {
					WC()->queue()->schedule_single(
						time() + $loop,
						'woocommerce_run_update_callback',
						array(
							'update_callback' => $update_callback,
						),
						'woocommerce-db-updates'
					);
					++$loop;
				}
			}
		}

		// After the callbacks finish, update the db version to the current WC version.
		$current_wc_version = WC()->version;
		if ( version_compare( $current_db_version, $current_wc_version, '<' ) &&
			! WC()->queue()->get_next( 'woocommerce_update_db_to_current_version' ) ) {
			WC()->queue()->schedule_single(
				time() + $loop,
				'woocommerce_update_db_to_current_version',
				array(
					'version' => $current_wc_version,
				),
				'woocommerce-db-updates'
			);
		}
	}

	/**
	 * Update DB version to current.
	 *
	 * @param string|null $version New WooCommerce DB version or null.
	 */
	public static function update_db_version( $version = null ) {
		update_option( 'woocommerce_db_version', is_null( $version ) ? WC()->version : $version );
	}

	/**
	 * Add more cron schedules.
	 *
	 * @param array $schedules List of WP scheduled cron jobs.
	 *
	 * @return array
	 */
	public static function cron_schedules( $schedules ) {
		$schedules['monthly']     = array(
			'interval' => 2635200,
			'display'  => __( 'Monthly', 'woocommerce' ),
		);
		$schedules['fifteendays'] = array(
			'interval' => 1296000,
			'display'  => __( 'Every 15 Days', 'woocommerce' ),
		);
		return $schedules;
	}

	/**
	 * Create cron jobs (clear them first).
	 */
	private static function create_cron_jobs() {
		wp_clear_scheduled_hook( 'woocommerce_scheduled_sales' );
		wp_clear_scheduled_hook( 'woocommerce_cancel_unpaid_orders' );
		wp_clear_scheduled_hook( 'woocommerce_cleanup_sessions' );
		wp_clear_scheduled_hook( 'woocommerce_cleanup_personal_data' );
		wp_clear_scheduled_hook( 'woocommerce_cleanup_logs' );
		wp_clear_scheduled_hook( 'woocommerce_geoip_updater' );
		wp_clear_scheduled_hook( 'woocommerce_tracker_send_event' );
		wp_clear_scheduled_hook( 'woocommerce_cleanup_rate_limits' );

		$ve = get_option( 'gmt_offset' ) > 0 ? '-' : '+';

		wp_schedule_event( strtotime( '00:00 tomorrow ' . $ve . absint( get_option( 'gmt_offset' ) ) . ' HOURS' ), 'daily', 'woocommerce_scheduled_sales' );

		$held_duration = get_option( 'woocommerce_hold_stock_minutes', '60' );

		if ( '' !== $held_duration ) {
			/**
			 * Determines the interval at which to cancel unpaid orders in minutes.
			 *
			 * @since 5.1.0
			 */
			$cancel_unpaid_interval = apply_filters( 'woocommerce_cancel_unpaid_orders_interval_minutes', absint( $held_duration ) );
			wp_schedule_single_event( time() + ( absint( $cancel_unpaid_interval ) * 60 ), 'woocommerce_cancel_unpaid_orders' );
		}

		// Delay the first run of `woocommerce_cleanup_personal_data` by 10 seconds
		// so it doesn't occur in the same request. WooCommerce Admin also schedules
		// a daily cron that gets lost due to a race condition. WC_Privacy's background
		// processing instance updates the cron schedule from within a cron job.
		wp_schedule_event( time() + 10, 'daily', 'woocommerce_cleanup_personal_data' );
		wp_schedule_event( time() + ( 3 * HOUR_IN_SECONDS ), 'daily', 'woocommerce_cleanup_logs' );
		wp_schedule_event( time() + ( 6 * HOUR_IN_SECONDS ), 'twicedaily', 'woocommerce_cleanup_sessions' );
		wp_schedule_event( time() + MINUTE_IN_SECONDS, 'fifteendays', 'woocommerce_geoip_updater' );
		/**
		 * How frequent to schedule the tracker send event.
		 *
		 * @since 2.3.0
		 */
		wp_schedule_event( time() + 10, apply_filters( 'woocommerce_tracker_event_recurrence', 'daily' ), 'woocommerce_tracker_send_event' );
		wp_schedule_event( time() + ( 3 * HOUR_IN_SECONDS ), 'daily', 'woocommerce_cleanup_rate_limits' );

		if ( ! wp_next_scheduled( 'wc_admin_daily' ) ) {
			wp_schedule_event( time(), 'daily', 'wc_admin_daily' );
		}
		// Note: this is potentially redundant when the core package exists.
		wp_schedule_single_event( time() + 10, 'generate_category_lookup_table' );
	}

	/**
	 * Create pages on installation.
	 */
	public static function maybe_create_pages() {
		if ( empty( get_option( 'woocommerce_db_version' ) ) ) {
			self::create_pages();
		}
	}

	/**
	 * Create pages that the plugin relies on, storing page IDs in variables.
	 */
	public static function create_pages() {
		// WordPress sets fresh_site to 0 after a page gets published.
		// Prevent fresh_site option from being set to 0 so that we can use it for further customizations.
		remove_action( 'publish_page', '_delete_option_fresh_site', 0 );

		// Set the locale to the store locale to ensure pages are created in the correct language.
		wc_switch_to_site_locale();

		include_once __DIR__ . '/admin/wc-admin-functions.php';

		/**
		 * Determines the cart shortcode tag used for the cart page.
		 *
		 * @since 2.1.0
		 * @deprecated 8.3.0 This filter is deprecated and will be removed in future versions.
		 */
		$cart_shortcode = apply_filters_deprecated( 'woocommerce_cart_shortcode_tag', array( '' ), '8.3.0', 'woocommerce_create_pages' );

		$cart_page_content = empty( $cart_shortcode ) ? self::get_cart_block_content() : '<!-- wp:shortcode -->[' . $cart_shortcode . ']<!-- /wp:shortcode -->';

		/**
		 * Determines the checkout shortcode tag used on the checkout page.
		 *
		 * @since 2.1.0
		 * @deprecated 8.3.0 This filter is deprecated and will be removed in future versions.
		 */
		$checkout_shortcode = apply_filters_deprecated( 'woocommerce_checkout_shortcode_tag', array( '' ), '8.3.0', 'woocommerce_create_pages' );

		$checkout_page_content = empty( $checkout_shortcode ) ? self::get_checkout_block_content() : '<!-- wp:shortcode -->[' . $checkout_shortcode . ']<!-- /wp:shortcode -->';

		/**
		 * Determines the my account shortcode tag used on the my account page.
		 *
		 * @since 2.1.0
		 */
		$my_account_shortcode = apply_filters( 'woocommerce_my_account_shortcode_tag', 'woocommerce_my_account' );

		/**
		 * Determines which pages are created during install.
		 *
		 * @since 2.1.0
		 */
		$pages = apply_filters(
			'woocommerce_create_pages',
			array(
				'shop'           => array(
					'name'    => _x( 'shop', 'Page slug', 'woocommerce' ),
					'title'   => _x( 'Shop', 'Page title', 'woocommerce' ),
					'content' => '',
				),
				'cart'           => array(
					'name'    => _x( 'cart', 'Page slug', 'woocommerce' ),
					'title'   => _x( 'Cart', 'Page title', 'woocommerce' ),
					'content' => $cart_page_content,
				),
				'checkout'       => array(
					'name'    => _x( 'checkout', 'Page slug', 'woocommerce' ),
					'title'   => _x( 'Checkout', 'Page title', 'woocommerce' ),
					'content' => $checkout_page_content,
				),
				'myaccount'      => array(
					'name'    => _x( 'my-account', 'Page slug', 'woocommerce' ),
					'title'   => _x( 'My account', 'Page title', 'woocommerce' ),
					'content' => '<!-- wp:shortcode -->[' . $my_account_shortcode . ']<!-- /wp:shortcode -->',
				),
				'refund_returns' => array(
					'name'        => _x( 'refund_returns', 'Page slug', 'woocommerce' ),
					'title'       => _x( 'Refund and Returns Policy', 'Page title', 'woocommerce' ),
					'content'     => self::get_refunds_return_policy_page_content(),
					'post_status' => 'draft',
				),
			)
		);

		foreach ( $pages as $key => $page ) {
			wc_create_page(
				esc_sql( $page['name'] ),
				'woocommerce_' . $key . '_page_id',
				$page['title'],
				$page['content'],
				! empty( $page['parent'] ) ? wc_get_page_id( $page['parent'] ) : '',
				! empty( $page['post_status'] ) ? $page['post_status'] : 'publish'
			);
		}

		// Restore the locale to the default locale.
		wc_restore_locale();
	}

	/**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function create_options() {
		// Include settings so that we can run through defaults.
		include_once __DIR__ . '/admin/class-wc-admin-settings.php';

		$settings = WC_Admin_Settings::get_settings_pages();

		foreach ( $settings as $section ) {
			if ( ! is_a( $section, 'WC_Settings_Page' ) || ! method_exists( $section, 'get_settings' ) ) {
				continue;
			}
			$subsections = array_unique( array_merge( array( '' ), array_keys( $section->get_sections() ) ) );

			/**
			 * We are using 'WC_Settings_Page::get_settings' on purpose even thought it's deprecated.
			 * See the method documentation for an explanation.
			 */

			foreach ( $subsections as $subsection ) {
				foreach ( $section->get_settings( $subsection ) as $value ) {
					if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
						$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
						add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
					}
				}
			}
		}

		// Define other defaults if not in setting screens.
		add_option( 'woocommerce_single_image_width', '600', '', 'yes' );
		add_option( 'woocommerce_thumbnail_image_width', '300', '', 'yes' );
		add_option( 'woocommerce_checkout_highlight_required_fields', 'yes', '', 'yes' );
		add_option( 'woocommerce_demo_store', 'no', '', 'no' );

		if ( self::is_new_install() ) {
			// Define initial tax classes.
			WC_Tax::create_tax_class( __( 'Reduced rate', 'woocommerce' ) );
			WC_Tax::create_tax_class( __( 'Zero rate', 'woocommerce' ) );

			// For new installs, setup and enable Approved Product Download Directories.
			wc_get_container()->get( Download_Directories_Sync::class )->init_feature( false, true );
		}
	}

	/**
	 * Enable HPOS by default for new shops.
	 *
	 * @since 8.2.0
	 */
	public static function maybe_enable_hpos() {
		if ( self::should_enable_hpos_for_new_shop() ) {
			$feature_controller = wc_get_container()->get( FeaturesController::class );
			$feature_controller->change_feature_enable( 'custom_order_tables', true );
		}
	}

	/**
	 * Add the woocommerce_coming_soon option for new shops.
	 *
	 * Ensure that the option is set for all shops, even if core profiler is disabled on the host.
	 *
	 * @since 9.3.0
	 */
	public static function add_coming_soon_option() {
		add_option( 'woocommerce_coming_soon', 'no' );
	}

	/**
	 * Checks whether HPOS should be enabled for new shops.
	 *
	 * @return bool
	 */
	private static function should_enable_hpos_for_new_shop() {
		if ( ! did_action( 'woocommerce_init' ) && ! doing_action( 'woocommerce_init' ) ) {
			return false;
		}

		$feature_controller = wc_get_container()->get( FeaturesController::class );

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return true;
		}

		if ( ! empty( wc_get_orders( array( 'limit' => 1 ) ) ) ) {
			return false;
		}

		$plugin_compat_info = $feature_controller->get_compatible_plugins_for_feature( 'custom_order_tables', true );
		if ( ! empty( $plugin_compat_info['incompatible'] ) || ! empty( $plugin_compat_info['uncertain'] ) ) {
			return false;
		}

		/**
		 * Filter to enable HPOS by default for new shops.
		 *
		 * @since 8.2.0
		 */
		return apply_filters( 'woocommerce_enable_hpos_by_default_for_new_shops', true );
	}

	/**
	 * Delete obsolete notes.
	 */
	public static function delete_obsolete_notes() {
		global $wpdb;
		$obsolete_notes_names = array(
			'wc-admin-welcome-note',
			'wc-admin-insight-first-product-and-payment',
			'wc-admin-store-notice-setting-moved',
			'wc-admin-store-notice-giving-feedback',
			'wc-admin-first-downloadable-product',
			'wc-admin-learn-more-about-product-settings',
			'wc-admin-adding-and-managing-products',
			'wc-admin-onboarding-profiler-reminder',
			'wc-admin-historical-data',
			'wc-admin-manage-store-activity-from-home-screen',
			'wc-admin-review-shipping-settings',
			'wc-admin-home-screen-feedback',
			'wc-admin-update-store-details',
			'wc-admin-effortless-payments-by-mollie',
			'wc-admin-google-ads-and-marketing',
			'wc-admin-insight-first-sale',
			'wc-admin-marketing-intro',
			'wc-admin-draw-attention',
			'wc-admin-welcome-to-woocommerce-for-store-users',
			'wc-admin-need-some-inspiration',
			'wc-admin-choose-niche',
			'wc-admin-start-dropshipping-business',
			'wc-admin-filter-by-product-variations-in-reports',
			'wc-admin-learn-more-about-variable-products',
			'wc-admin-getting-started-ecommerce-webinar',
			'wc-admin-navigation-feedback',
			'wc-admin-navigation-feedback-follow-up',
			'wc-admin-set-up-additional-payment-types',
			'wc-admin-deactivate-plugin',
			'wc-admin-complete-store-details',
		);

		/**
		 * An array of deprecated notes to delete on update.
		 *
		 * @since 6.5.0
		 */
		$additional_obsolete_notes_names = apply_filters(
			'woocommerce_admin_obsolete_notes_names',
			array()
		);

		if ( is_array( $additional_obsolete_notes_names ) ) {
			$obsolete_notes_names = array_merge(
				$obsolete_notes_names,
				$additional_obsolete_notes_names
			);
		}

		$note_names_placeholder = substr( str_repeat( ',%s', count( $obsolete_notes_names ) ), 1 );

		$note_ids = $wpdb->get_results(
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Ignored for allowing interpolation in the IN statement.
			$wpdb->prepare(
				"SELECT note_id FROM {$wpdb->prefix}wc_admin_notes WHERE name IN ( $note_names_placeholder )",
				$obsolete_notes_names
			),
			ARRAY_N
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare.
		);

		if ( ! $note_ids ) {
			return;
		}

		$note_ids             = array_column( $note_ids, 0 );
		$note_ids_placeholder = substr( str_repeat( ',%d', count( $note_ids ) ), 1 );

		$wpdb->query(
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Ignored for allowing interpolation in the IN statement.
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}wc_admin_notes WHERE note_id IN ( $note_ids_placeholder )",
				$note_ids
			)
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare.
		);

		$wpdb->query(
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Ignored for allowing interpolation in the IN statement.
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}wc_admin_note_actions WHERE note_id IN ( $note_ids_placeholder )",
				$note_ids
			)
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare.
		);
	}

	/**
	 * Migrate option values to their new keys/names.
	 */
	public static function migrate_options() {

		$migrated_options = array(
			'woocommerce_onboarding_profile'           => 'wc_onboarding_profile',
			'woocommerce_admin_install_timestamp'      => 'wc_admin_install_timestamp',
			'woocommerce_onboarding_opt_in'            => 'wc_onboarding_opt_in',
			'woocommerce_admin_import_stats'           => 'wc_admin_import_stats',
			'woocommerce_admin_version'                => 'wc_admin_version',
			'woocommerce_admin_last_orders_milestone'  => 'wc_admin_last_orders_milestone',
			'woocommerce_admin-wc-helper-last-refresh' => 'wc-admin-wc-helper-last-refresh',
			'woocommerce_admin_report_export_status'   => 'wc_admin_report_export_status',
			'woocommerce_task_list_complete'           => 'woocommerce_task_list_complete',
			'woocommerce_task_list_hidden'             => 'woocommerce_task_list_hidden',
			'woocommerce_extended_task_list_complete'  => 'woocommerce_extended_task_list_complete',
			'woocommerce_extended_task_list_hidden'    => 'woocommerce_extended_task_list_hidden',
		);

		wc_maybe_define_constant( 'WC_ADMIN_MIGRATING_OPTIONS', true );

		foreach ( $migrated_options as $new_option => $old_option ) {
			$old_option_value = get_option( $old_option, false );

			// Continue if no option value was previously set.
			if ( false === $old_option_value ) {
				continue;
			}

			if ( '1' === $old_option_value ) {
				$old_option_value = 'yes';
			} elseif ( '0' === $old_option_value ) {
				$old_option_value = 'no';
			}

			update_option( $new_option, $old_option_value );
			if ( $new_option !== $old_option ) {
				delete_option( $old_option );
			}
		}
	}
	/**
	 * Add the default terms for WC taxonomies - product types and order statuses. Modify this at your own risk.
	 */
	public static function create_terms() {
		$taxonomies = array(
			'product_type'       => array(
				'simple',
				'grouped',
				'variable',
				'external',
			),
			'product_visibility' => array(
				'exclude-from-search',
				'exclude-from-catalog',
				'featured',
				'outofstock',
				'rated-1',
				'rated-2',
				'rated-3',
				'rated-4',
				'rated-5',
			),
		);

		foreach ( $taxonomies as $taxonomy => $terms ) {
			foreach ( $terms as $term ) {
				if ( ! get_term_by( 'name', $term, $taxonomy ) ) { // @codingStandardsIgnoreLine.
					wp_insert_term( $term, $taxonomy );
				}
			}
		}

		$woocommerce_default_category = (int) get_option( 'default_product_cat', 0 );

		if ( ! $woocommerce_default_category || ! term_exists( $woocommerce_default_category, 'product_cat' ) ) {
			$default_product_cat_id   = 0;
			$default_product_cat_slug = sanitize_title( _x( 'Uncategorized', 'Default category slug', 'woocommerce' ) );
			$default_product_cat      = get_term_by( 'slug', $default_product_cat_slug, 'product_cat' ); // @codingStandardsIgnoreLine.

			if ( $default_product_cat ) {
				$default_product_cat_id = absint( $default_product_cat->term_taxonomy_id );
			} else {
				$result = wp_insert_term( _x( 'Uncategorized', 'Default category slug', 'woocommerce' ), 'product_cat', array( 'slug' => $default_product_cat_slug ) );

				if ( ! is_wp_error( $result ) && ! empty( $result['term_taxonomy_id'] ) ) {
					$default_product_cat_id = absint( $result['term_taxonomy_id'] );
				}
			}

			if ( $default_product_cat_id ) {
				update_option( 'default_product_cat', $default_product_cat_id );
			}
		}
	}

	/**
	 * Install and activate the WooCommerce Legacy REST API plugin from the WordPress.org directory if all the following is true:
	 *
	 * 1. We are in a WooCommerce upgrade process (not a new install).
	 * 2. The 'woocommerce_skip_legacy_rest_api_plugin_auto_install' filter returns false (which is the default).
	 * 3. The plugin is not installed and active already (but see note about multisite below).
	 * 4. The Legacy REST API is enabled in the site OR the site has at least one webhook defined that uses the Legacy REST API payload format (disabled webhooks also count).
	 *
	 * In multisite setups it could happen that the plugin was installed by an installation process performed in another site.
	 * In this case we check if the plugin was autoinstalled in such a way, and if so we activate it if the conditions are fulfilled.
	 */
	private static function maybe_install_legacy_api_plugin() {
		if ( self::is_new_install() ) {
			return;
		}

		// Did we previously install this plugin?
		// We check both the woocommerce_history_of_autoinstalled_plugins options (introduced in 9.0)
		// and the woocommerce_autoinstalled_plugins option (info should still exist here if the plugin has been uninstalled but not manually reinstalled).
		$legacy_api_plugin          = 'woocommerce-legacy-rest-api/woocommerce-legacy-rest-api.php';
		$autoinstalled_plugins      = (array) get_site_option( 'woocommerce_history_of_autoinstalled_plugins', array() );
		$previously_installed_by_us = isset( $autoinstalled_plugins[ $legacy_api_plugin ] );
		if ( ! $previously_installed_by_us ) {
			$autoinstalled_plugins      = (array) get_site_option( 'woocommerce_autoinstalled_plugins', array() );
			$previously_installed_by_us = isset( $autoinstalled_plugins[ $legacy_api_plugin ] );
		}

		/**
		 * Filter to skip the automatic installation of the WooCommerce Legacy REST API plugin
		 * from the WordPress.org plugins directory.
		 *
		 * By default, this is true (skip installation) if we have a record of previously installing the legacy plugin,
		 * and false (do not skip) if we have no record of previously installing the plugin.
		 *
		 * @since 8.8.0
		 *
		 * @param bool $skip_auto_install False, defaulting to "don't skip the plugin automatic installation".
		 * @returns bool True to skip the plugin automatic installation, false to install the plugin if necessary.
		 */
		if ( apply_filters( 'woocommerce_skip_legacy_rest_api_plugin_auto_install', $previously_installed_by_us ) ) {
			return;
		}

		if ( ( 'yes' !== get_option( 'woocommerce_api_enabled' ) &&
			0 === wc_get_container()->get( Automattic\WooCommerce\Internal\Utilities\WebhookUtil::class )->get_legacy_webhooks_count( true ) ) ) {
			return;
		}

		wp_clean_plugins_cache();
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( isset( get_plugins()[ $legacy_api_plugin ] ) ) {
			if ( ! $previously_installed_by_us ) {
				// The plugin was installed manually so let's not interfere.
				return;
			}

			$active_valid_plugins = wc_get_container()->get( PluginUtil::class )->get_all_active_valid_plugins();
			if ( in_array( $legacy_api_plugin, $active_valid_plugins, true ) ) {
				return;
			}

			// The plugin was automatically installed in a different installation process - can happen in multisite.
			$install_ok = true;
		} else {
			try {
				$install_result = wc_get_container()->get( PluginInstaller::class )->install_plugin(
					'https://downloads.wordpress.org/plugin/woocommerce-legacy-rest-api.latest-stable.zip',
					array(
						'info_link' => 'https://developer.woocommerce.com/2023/10/03/the-legacy-rest-api-will-move-to-a-dedicated-extension-in-woocommerce-9-0/',
					)
				);

				if ( $install_result['already_installing'] ?? null ) {
					// The plugin is in the process of being installed already (can happen in multisite),
					// but we still need to activate it for ourselves once it's installed.
					as_schedule_single_action( time() + 10, 'woocommerce_activate_legacy_rest_api_plugin' );
					return;
				}

				$install_ok = $install_result['install_ok'];
			} catch ( \Exception $ex ) {
				wc_get_logger()->error(
					'The autoinstall of the WooCommerce Legacy REST API plugin failed: ' . $ex->getMessage(),
					array(
						'source'    => 'plugin_auto_installs',
						'exception' => $ex,
					)
				);
				$install_ok = false;
			}
		}

		$plugin_page_url              = 'https://wordpress.org/plugins/woocommerce-legacy-rest-api/';
		$blog_post_url                = 'https://developer.woocommerce.com/2023/10/03/the-legacy-rest-api-will-move-to-a-dedicated-extension-in-woocommerce-9-0/';
		$site_legacy_api_settings_url = get_admin_url( null, '/admin.php?page=wc-settings&tab=advanced&section=legacy_api' );
		$site_webhooks_settings_url   = get_admin_url( null, '/admin.php?page=wc-settings&tab=advanced&section=webhooks' );
		$site_logs_url                = get_admin_url( null, '/admin.php?page=wc-status&tab=logs' );

		if ( $install_ok ) {
			$activation_result = activate_plugin( $legacy_api_plugin );
			if ( $activation_result instanceof \WP_Error ) {
				$message = sprintf(
				/* translators: 1 = URL of Legacy REST API plugin page, 2 = URL of Legacy API settings in current site, 3 = URL of webhooks settings in current site, 4 = URL of logs page in current site, 5 = URL of plugins page in current site, 6 = URL of blog post about the Legacy REST API removal */
					__( '⚠️ WooCommerce installed <a href="%1$s">the Legacy REST API plugin</a> because this site has <a href="%2$s">the Legacy REST API enabled</a> or has <a href="%3$s">legacy webhooks defined</a>, but it failed to activate it (see error details in <a href="%4$s">the WooCommerce logs</a>). Please go to <a href="%5$s">the plugins page</a> and activate it manually. <a href="%6$s">More information</a>', 'woocommerce' ),
					$plugin_page_url,
					$site_legacy_api_settings_url,
					$site_webhooks_settings_url,
					$site_logs_url,
					get_admin_url( null, '/plugins.php' ),
					$blog_post_url
				);
				$notice_name = 'woocommerce_legacy_rest_api_plugin_activation_failed';
				wc_get_logger()->error(
					__( 'WooCommerce installed the Legacy REST API plugin but failed to activate it, see context for more details.', 'woocommerce' ),
					array(
						'source' => 'plugin_auto_installs',
						'error'  => $activation_result,
					)
				);
			} else {
				$message = sprintf(
				/* translators: 1 = URL of Legacy REST API plugin page, 2 = URL of Legacy API settings in current site, 3 = URL of webhooks settings in current site, 4 = URL of blog post about the Legacy REST API removal */
					__( 'ℹ️ WooCommerce installed and activated <a href="%1$s">the Legacy REST API plugin</a> because this site has <a href="%2$s">the Legacy REST API enabled</a> or has <a href="%3$s">legacy webhooks defined</a>. <a href="%4$s">More information</a>', 'woocommerce' ),
					$plugin_page_url,
					$site_legacy_api_settings_url,
					$site_webhooks_settings_url,
					$blog_post_url
				);
				$notice_name = 'woocommerce_legacy_rest_api_plugin_activated';
				wc_get_logger()->info( 'WooCommerce activated the Legacy REST API plugin in this site.', array( 'source' => 'plugin_auto_installs' ) );
			}

			\WC_Admin_Notices::add_custom_notice( $notice_name, $message );
		} else {
			$message = sprintf(
				/* translators: 1 = URL of Legacy REST API plugin page, 2 = URL of Legacy API settings in current site, 3 = URL of webhooks settings in current site, 4 = URL of logs page in current site, 5 = URL of blog post about the Legacy REST API removal */
				__( '⚠️ WooCommerce attempted to install <a href="%1$s">the Legacy REST API plugin</a> because this site has <a href="%2$s">the Legacy REST API enabled</a> or has <a href="%3$s">legacy webhooks defined</a>, but the installation failed (see error details in <a href="%4$s">the WooCommerce logs</a>). Please install and activate the plugin manually. <a href="%5$s">More information</a>', 'woocommerce' ),
				$plugin_page_url,
				$site_legacy_api_settings_url,
				$site_webhooks_settings_url,
				$site_logs_url,
				$blog_post_url
			);

			\WC_Admin_Notices::add_custom_notice( 'woocommerce_legacy_rest_api_plugin_install_failed', $message );

			// Note that we aren't adding an entry to the error log because PluginInstaller->install_plugin will have done that already.
		}

		\WC_Admin_Notices::store_notices();
	}

	/**
	 * If in a previous version of WooCommerce the Legacy REST API plugin was installed manually but the core Legacy REST API was kept disabled,
	 * now the Legacy API is still disabled and can't be manually enabled from settings UI (the plugin, which is now in control, won't allow that),
	 * which is weird and confusing. So we detect this case and explicitly enable it.
	 */
	private static function maybe_activate_legacy_api_enabled_option() {
		if ( ! self::is_new_install() && is_plugin_active( 'woocommerce-legacy-rest-api/woocommerce-legacy-rest-api.php' ) && 'yes' !== get_option( 'woocommerce_api_enabled' ) ) {
			update_option( 'woocommerce_api_enabled', 'yes' );
		}
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 * WARNING: If you are modifying this method, make sure that its safe to call regardless of the state of database.
	 *
	 * This is called from `install` method and is executed in-sync when WC is installed or updated. This can also be called optionally from `verify_base_tables`.
	 *
	 * TODO: Add all crucial tables that we have created from workers in the past.
	 *
	 * Tables:
	 *      woocommerce_attribute_taxonomies - Table for storing attribute taxonomies - these are user defined
	 *      woocommerce_downloadable_product_permissions - Table for storing user and guest download permissions.
	 *          KEY(order_id, product_id, download_id) used for organizing downloads on the My Account page
	 *      woocommerce_order_items - Order line items are stored in a table to make them easily queryable for reports
	 *      woocommerce_order_itemmeta - Order line item meta is stored in a table for storing extra data.
	 *      woocommerce_tax_rates - Tax Rates are stored inside 2 tables making tax queries simple and efficient.
	 *      woocommerce_tax_rate_locations - Each rate can be applied to more than one postcode/city hence the second table.
	 *
	 * @return array Strings containing the results of the various update queries as returned by dbDelta.
	 */
	public static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		/**
		 * Before updating with DBDELTA, remove any primary keys which could be
		 * modified due to schema updates.
		 */
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_downloadable_product_permissions';" ) ) {
			if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}woocommerce_downloadable_product_permissions` LIKE 'permission_id';" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}woocommerce_downloadable_product_permissions DROP PRIMARY KEY, ADD `permission_id` bigint(20) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT;" );
			}
		}

		/**
		 * Change wp_woocommerce_sessions schema to use a bigint auto increment field instead of char(32) field as
		 * the primary key as it is not a good practice to use a char(32) field as the primary key of a table and as
		 * there were reports of issues with this table (see https://github.com/woocommerce/woocommerce/issues/20912).
		 *
		 * This query needs to run before dbDelta() as this WP function is not able to handle primary key changes
		 * (see https://github.com/woocommerce/woocommerce/issues/21534 and https://core.trac.wordpress.org/ticket/40357).
		 */
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_sessions'" ) ) {
			if ( ! $wpdb->get_var( "SHOW KEYS FROM {$wpdb->prefix}woocommerce_sessions WHERE Key_name = 'PRIMARY' AND Column_name = 'session_id'" ) ) {
				$wpdb->query(
					"ALTER TABLE `{$wpdb->prefix}woocommerce_sessions` DROP PRIMARY KEY, DROP KEY `session_id`, ADD PRIMARY KEY(`session_id`), ADD UNIQUE KEY(`session_key`)"
				);
			}
		}

		$db_delta_result = dbDelta( self::get_schema() );

		$index_exists = $wpdb->get_row( "SHOW INDEX FROM {$wpdb->comments} WHERE column_name = 'comment_type' and key_name = 'woo_idx_comment_type'" );

		if ( is_null( $index_exists ) ) {
			// Add an index to the field comment_type to improve the response time of the query
			// used by WC_Comments::wp_count_comments() to get the number of comments by type.
			$wpdb->query( "ALTER TABLE {$wpdb->comments} ADD INDEX woo_idx_comment_type (comment_type)" );
		}

		// Clear table caches.
		delete_transient( 'wc_attribute_taxonomies' );

		return $db_delta_result;
	}

	/**
	 * Get Table schema.
	 *
	 * See https://github.com/woocommerce/woocommerce/wiki/Database-Description/
	 *
	 * A note on indexes; Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
	 * As of WordPress 4.2, however, we moved to utf8mb4, which uses 4 bytes per character. This means that an index which
	 * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
	 *
	 * Changing indexes may cause duplicate index notices in logs due to https://core.trac.wordpress.org/ticket/34870 but dropping
	 * indexes first causes too much load on some servers/larger DB.
	 *
	 * When adding or removing a table, make sure to update the list of tables in WC_Install::get_tables().
	 *
	 * @return string
	 */
	private static function get_schema() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$max_index_length = wc_get_container()->get( DatabaseUtil::class )->get_max_index_length();

		$product_attributes_lookup_table_creation_sql = wc_get_container()->get( DataRegenerator::class )->get_table_creation_sql();

		$feature_controller = wc_get_container()->get( FeaturesController::class );
		$hpos_enabled       =
			$feature_controller->feature_is_enabled( DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION ) || $feature_controller->feature_is_enabled( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION ) ||
			self::should_enable_hpos_for_new_shop();
		$hpos_table_schema  = $hpos_enabled ? wc_get_container()->get( OrdersTableDataStore::class )->get_database_schema() : '';

		$tables = "
CREATE TABLE {$wpdb->prefix}woocommerce_sessions (
  session_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  session_key char(32) NOT NULL,
  session_value longtext NOT NULL,
  session_expiry bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (session_id),
  UNIQUE KEY session_key (session_key)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_api_keys (
  key_id bigint(20) unsigned NOT NULL auto_increment,
  user_id bigint(20) unsigned NOT NULL,
  description varchar(200) NULL,
  permissions varchar(10) NOT NULL,
  consumer_key char(64) NOT NULL,
  consumer_secret char(43) NOT NULL,
  nonces longtext NULL,
  truncated_key char(7) NOT NULL,
  last_access datetime NULL default null,
  PRIMARY KEY  (key_id),
  KEY consumer_key (consumer_key),
  KEY consumer_secret (consumer_secret)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_attribute_taxonomies (
  attribute_id bigint(20) unsigned NOT NULL auto_increment,
  attribute_name varchar(200) NOT NULL,
  attribute_label varchar(200) NULL,
  attribute_type varchar(20) NOT NULL,
  attribute_orderby varchar(20) NOT NULL,
  attribute_public int(1) NOT NULL DEFAULT 1,
  PRIMARY KEY  (attribute_id),
  KEY attribute_name (attribute_name(20))
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_downloadable_product_permissions (
  permission_id bigint(20) unsigned NOT NULL auto_increment,
  download_id varchar(36) NOT NULL,
  product_id bigint(20) unsigned NOT NULL,
  order_id bigint(20) unsigned NOT NULL DEFAULT 0,
  order_key varchar(200) NOT NULL,
  user_email varchar(200) NOT NULL,
  user_id bigint(20) unsigned NULL,
  downloads_remaining varchar(9) NULL,
  access_granted datetime NOT NULL default '0000-00-00 00:00:00',
  access_expires datetime NULL default null,
  download_count bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (permission_id),
  KEY download_order_key_product (product_id,order_id,order_key(16),download_id),
  KEY download_order_product (download_id,order_id,product_id),
  KEY order_id (order_id),
  KEY user_order_remaining_expires (user_id,order_id,downloads_remaining,access_expires)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_order_items (
  order_item_id bigint(20) unsigned NOT NULL auto_increment,
  order_item_name text NOT NULL,
  order_item_type varchar(200) NOT NULL DEFAULT '',
  order_id bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (order_item_id),
  KEY order_id (order_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_order_itemmeta (
  meta_id bigint(20) unsigned NOT NULL auto_increment,
  order_item_id bigint(20) unsigned NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY order_item_id (order_item_id),
  KEY meta_key (meta_key(32))
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_tax_rates (
  tax_rate_id bigint(20) unsigned NOT NULL auto_increment,
  tax_rate_country varchar(2) NOT NULL DEFAULT '',
  tax_rate_state varchar(200) NOT NULL DEFAULT '',
  tax_rate varchar(8) NOT NULL DEFAULT '',
  tax_rate_name varchar(200) NOT NULL DEFAULT '',
  tax_rate_priority bigint(20) unsigned NOT NULL,
  tax_rate_compound int(1) NOT NULL DEFAULT 0,
  tax_rate_shipping int(1) NOT NULL DEFAULT 1,
  tax_rate_order bigint(20) unsigned NOT NULL,
  tax_rate_class varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY  (tax_rate_id),
  KEY tax_rate_country (tax_rate_country),
  KEY tax_rate_state (tax_rate_state(2)),
  KEY tax_rate_class (tax_rate_class(10)),
  KEY tax_rate_priority (tax_rate_priority)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_tax_rate_locations (
  location_id bigint(20) unsigned NOT NULL auto_increment,
  location_code varchar(200) NOT NULL,
  tax_rate_id bigint(20) unsigned NOT NULL,
  location_type varchar(40) NOT NULL,
  PRIMARY KEY  (location_id),
  KEY tax_rate_id (tax_rate_id),
  KEY location_type_code (location_type(10),location_code(20))
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_shipping_zones (
  zone_id bigint(20) unsigned NOT NULL auto_increment,
  zone_name varchar(200) NOT NULL,
  zone_order bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (zone_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_shipping_zone_locations (
  location_id bigint(20) unsigned NOT NULL auto_increment,
  zone_id bigint(20) unsigned NOT NULL,
  location_code varchar(200) NOT NULL,
  location_type varchar(40) NOT NULL,
  PRIMARY KEY  (location_id),
  KEY zone_id (zone_id),
  KEY location_type_code (location_type(10),location_code(20))
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_shipping_zone_methods (
  zone_id bigint(20) unsigned NOT NULL,
  instance_id bigint(20) unsigned NOT NULL auto_increment,
  method_id varchar(200) NOT NULL,
  method_order bigint(20) unsigned NOT NULL,
  is_enabled tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY  (instance_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_payment_tokens (
  token_id bigint(20) unsigned NOT NULL auto_increment,
  gateway_id varchar(200) NOT NULL,
  token text NOT NULL,
  user_id bigint(20) unsigned NOT NULL DEFAULT '0',
  type varchar(200) NOT NULL,
  is_default tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY  (token_id),
  KEY user_id (user_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_payment_tokenmeta (
  meta_id bigint(20) unsigned NOT NULL auto_increment,
  payment_token_id bigint(20) unsigned NOT NULL,
  meta_key varchar(255) NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY payment_token_id (payment_token_id),
  KEY meta_key (meta_key(32))
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_log (
  log_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  timestamp datetime NOT NULL,
  level smallint(4) NOT NULL,
  source varchar(200) NOT NULL,
  message longtext NOT NULL,
  context longtext NULL,
  PRIMARY KEY (log_id),
  KEY level (level)
) $collate;
CREATE TABLE {$wpdb->prefix}wc_webhooks (
  webhook_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  status varchar(200) NOT NULL,
  name text NOT NULL,
  user_id bigint(20) unsigned NOT NULL,
  delivery_url text NOT NULL,
  secret text NOT NULL,
  topic varchar(200) NOT NULL,
  date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  date_created_gmt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  date_modified datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  date_modified_gmt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  api_version smallint(4) NOT NULL,
  failure_count smallint(10) NOT NULL DEFAULT '0',
  pending_delivery tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY  (webhook_id),
  KEY user_id (user_id)
) $collate;
CREATE TABLE {$wpdb->prefix}wc_download_log (
  download_log_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  timestamp datetime NOT NULL,
  permission_id bigint(20) unsigned NOT NULL,
  user_id bigint(20) unsigned NULL,
  user_ip_address varchar(100) NULL DEFAULT '',
  PRIMARY KEY  (download_log_id),
  KEY permission_id (permission_id),
  KEY timestamp (timestamp)
) $collate;
CREATE TABLE {$wpdb->prefix}wc_product_meta_lookup (
  `product_id` bigint(20) NOT NULL,
  `sku` varchar(100) NULL default '',
  `global_unique_id` varchar(100) NULL default '',
  `virtual` tinyint(1) NULL default 0,
  `downloadable` tinyint(1) NULL default 0,
  `min_price` decimal(19,4) NULL default NULL,
  `max_price` decimal(19,4) NULL default NULL,
  `onsale` tinyint(1) NULL default 0,
  `stock_quantity` double NULL default NULL,
  `stock_status` varchar(100) NULL default 'instock',
  `rating_count` bigint(20) NULL default 0,
  `average_rating` decimal(3,2) NULL default 0.00,
  `total_sales` bigint(20) NULL default 0,
  `tax_status` varchar(100) NULL default 'taxable',
  `tax_class` varchar(100) NULL default '',
  PRIMARY KEY  (`product_id`),
  KEY `virtual` (`virtual`),
  KEY `downloadable` (`downloadable`),
  KEY `stock_status` (`stock_status`),
  KEY `stock_quantity` (`stock_quantity`),
  KEY `onsale` (`onsale`),
  KEY min_max_price (`min_price`, `max_price`),
  KEY sku (sku(50))
) $collate;
CREATE TABLE {$wpdb->prefix}wc_tax_rate_classes (
  tax_rate_class_id bigint(20) unsigned NOT NULL auto_increment,
  name varchar(200) NOT NULL DEFAULT '',
  slug varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY  (tax_rate_class_id),
  UNIQUE KEY slug (slug($max_index_length))
) $collate;
CREATE TABLE {$wpdb->prefix}wc_reserved_stock (
	`order_id` bigint(20) NOT NULL,
	`product_id` bigint(20) NOT NULL,
	`stock_quantity` double NOT NULL DEFAULT 0,
	`timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`expires` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY  (`order_id`, `product_id`)
) $collate;
CREATE TABLE {$wpdb->prefix}wc_rate_limits (
  rate_limit_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  rate_limit_key varchar(200) NOT NULL,
  rate_limit_expiry bigint(20) unsigned NOT NULL,
  rate_limit_remaining smallint(10) NOT NULL DEFAULT '0',
  PRIMARY KEY  (rate_limit_id),
  UNIQUE KEY rate_limit_key (rate_limit_key($max_index_length))
) $collate;
$product_attributes_lookup_table_creation_sql
CREATE TABLE {$wpdb->prefix}wc_product_download_directories (
	url_id bigint(20) unsigned NOT NULL auto_increment,
	url varchar(256) NOT NULL,
	enabled tinyint(1) NOT NULL DEFAULT 0,
	PRIMARY KEY (url_id),
	KEY url (url($max_index_length))
) $collate;
CREATE TABLE {$wpdb->prefix}wc_order_stats (
	order_id bigint(20) unsigned NOT NULL,
	parent_id bigint(20) unsigned DEFAULT 0 NOT NULL,
	date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	date_created_gmt datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	date_paid datetime DEFAULT '0000-00-00 00:00:00',
	date_completed datetime DEFAULT '0000-00-00 00:00:00',
	num_items_sold int(11) DEFAULT 0 NOT NULL,
	total_sales double DEFAULT 0 NOT NULL,
	tax_total double DEFAULT 0 NOT NULL,
	shipping_total double DEFAULT 0 NOT NULL,
	net_total double DEFAULT 0 NOT NULL,
	returning_customer tinyint(1) DEFAULT NULL,
	status varchar(200) NOT NULL,
	customer_id bigint(20) unsigned NOT NULL,
	PRIMARY KEY (order_id),
	KEY date_created (date_created),
	KEY customer_id (customer_id),
	KEY status (status({$max_index_length}))
) $collate;
CREATE TABLE {$wpdb->prefix}wc_order_product_lookup (
	order_item_id bigint(20) unsigned NOT NULL,
	order_id bigint(20) unsigned NOT NULL,
	product_id bigint(20) unsigned NOT NULL,
	variation_id bigint(20) unsigned NOT NULL,
	customer_id bigint(20) unsigned NULL,
	date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	product_qty int(11) NOT NULL,
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
	order_id bigint(20) unsigned NOT NULL,
	tax_rate_id bigint(20) unsigned NOT NULL,
	date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	shipping_tax double DEFAULT 0 NOT NULL,
	order_tax double DEFAULT 0 NOT NULL,
	total_tax double DEFAULT 0 NOT NULL,
	PRIMARY KEY (order_id, tax_rate_id),
	KEY tax_rate_id (tax_rate_id),
	KEY date_created (date_created)
) $collate;
CREATE TABLE {$wpdb->prefix}wc_order_coupon_lookup (
	order_id bigint(20) unsigned NOT NULL,
	coupon_id bigint(20) NOT NULL,
	date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	discount_amount double DEFAULT 0 NOT NULL,
	PRIMARY KEY (order_id, coupon_id),
	KEY coupon_id (coupon_id),
	KEY date_created (date_created)
) $collate;
CREATE TABLE {$wpdb->prefix}wc_admin_notes (
	note_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	name varchar(255) NOT NULL,
	type varchar(20) NOT NULL,
	locale varchar(20) NOT NULL,
	title longtext NOT NULL,
	content longtext NOT NULL,
	content_data longtext NULL default null,
	status varchar(200) NOT NULL,
	source varchar(200) NOT NULL,
	date_created datetime NOT NULL default '0000-00-00 00:00:00',
	date_reminder datetime NULL default null,
	is_snoozable tinyint(1) DEFAULT 0 NOT NULL,
	layout varchar(20) DEFAULT '' NOT NULL,
	image varchar(200) NULL DEFAULT NULL,
	is_deleted tinyint(1) DEFAULT 0 NOT NULL,
	is_read tinyint(1) DEFAULT 0 NOT NULL,
	icon varchar(200) NOT NULL default 'info',
	PRIMARY KEY (note_id)
) $collate;
CREATE TABLE {$wpdb->prefix}wc_admin_note_actions (
	action_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	note_id bigint(20) unsigned NOT NULL,
	name varchar(255) NOT NULL,
	label varchar(255) NOT NULL,
	query longtext NOT NULL,
	status varchar(255) NOT NULL,
	actioned_text varchar(255) NOT NULL,
	nonce_action varchar(255) NULL DEFAULT NULL,
	nonce_name varchar(255) NULL DEFAULT NULL,
	PRIMARY KEY (action_id),
	KEY note_id (note_id)
) $collate;
CREATE TABLE {$wpdb->prefix}wc_customer_lookup (
	customer_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	user_id bigint(20) unsigned DEFAULT NULL,
	username varchar(60) DEFAULT '' NOT NULL,
	first_name varchar(255) NOT NULL,
	last_name varchar(255) NOT NULL,
	email varchar(100) NULL default NULL,
	date_last_active timestamp NULL default null,
	date_registered timestamp NULL default null,
	country char(2) DEFAULT '' NOT NULL,
	postcode varchar(20) DEFAULT '' NOT NULL,
	city varchar(100) DEFAULT '' NOT NULL,
	state varchar(100) DEFAULT '' NOT NULL,
	PRIMARY KEY (customer_id),
	UNIQUE KEY user_id (user_id),
	KEY email (email)
) $collate;
CREATE TABLE {$wpdb->prefix}wc_category_lookup (
	category_tree_id bigint(20) unsigned NOT NULL,
	category_id bigint(20) unsigned NOT NULL,
	PRIMARY KEY (category_tree_id,category_id)
) $collate;
$hpos_table_schema;
		";

		return $tables;
	}

	/**
	 * Return a list of WooCommerce tables. Used to make sure all WC tables are dropped when uninstalling the plugin
	 * in a single site or multi site environment.
	 *
	 * @return array WC tables.
	 */
	public static function get_tables() {
		global $wpdb;

		$tables = array(
			"{$wpdb->prefix}wc_download_log",
			"{$wpdb->prefix}wc_product_download_directories",
			"{$wpdb->prefix}wc_product_meta_lookup",
			"{$wpdb->prefix}wc_tax_rate_classes",
			"{$wpdb->prefix}wc_webhooks",
			"{$wpdb->prefix}woocommerce_api_keys",
			"{$wpdb->prefix}woocommerce_attribute_taxonomies",
			"{$wpdb->prefix}woocommerce_downloadable_product_permissions",
			"{$wpdb->prefix}woocommerce_log",
			"{$wpdb->prefix}woocommerce_order_itemmeta",
			"{$wpdb->prefix}woocommerce_order_items",
			"{$wpdb->prefix}woocommerce_payment_tokenmeta",
			"{$wpdb->prefix}woocommerce_payment_tokens",
			"{$wpdb->prefix}woocommerce_sessions",
			"{$wpdb->prefix}woocommerce_shipping_zone_locations",
			"{$wpdb->prefix}woocommerce_shipping_zone_methods",
			"{$wpdb->prefix}woocommerce_shipping_zones",
			"{$wpdb->prefix}woocommerce_tax_rate_locations",
			"{$wpdb->prefix}woocommerce_tax_rates",
			"{$wpdb->prefix}wc_reserved_stock",
			"{$wpdb->prefix}wc_rate_limits",
			wc_get_container()->get( DataRegenerator::class )->get_lookup_table_name(),

			// WCA Tables.
			"{$wpdb->prefix}wc_order_stats",
			"{$wpdb->prefix}wc_order_product_lookup",
			"{$wpdb->prefix}wc_order_tax_lookup",
			"{$wpdb->prefix}wc_order_coupon_lookup",
			"{$wpdb->prefix}wc_admin_notes",
			"{$wpdb->prefix}wc_admin_note_actions",
			"{$wpdb->prefix}wc_customer_lookup",
			"{$wpdb->prefix}wc_category_lookup",
		);

		/**
		 * Filter the list of known WooCommerce tables.
		 *
		 * If WooCommerce plugins need to add new tables, they can inject them here.
		 *
		 * @param array $tables An array of WooCommerce-specific database table names.
		 * @since 3.4.0
		 */
		$tables = apply_filters( 'woocommerce_install_get_tables', $tables );

		return $tables;
	}

	/**
	 * Drop WooCommerce tables.
	 *
	 * @return void
	 */
	public static function drop_tables() {
		global $wpdb;

		$tables = self::get_tables();

		foreach ( $tables as $table ) {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
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
	 * Create roles and capabilities.
	 */
	public static function create_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		// Dummy gettext calls to get strings in the catalog.
		/* translators: user role */
		_x( 'Customer', 'User role', 'woocommerce' );
		/* translators: user role */
		_x( 'Shop manager', 'User role', 'woocommerce' );

		// Customer role.
		add_role(
			'customer',
			'Customer',
			array(
				'read' => true,
			)
		);

		// Shop manager role.
		add_role(
			'shop_manager',
			'Shop manager',
			array(
				'level_9'                => true,
				'level_8'                => true,
				'level_7'                => true,
				'level_6'                => true,
				'level_5'                => true,
				'level_4'                => true,
				'level_3'                => true,
				'level_2'                => true,
				'level_1'                => true,
				'level_0'                => true,
				'read'                   => true,
				'read_private_pages'     => true,
				'read_private_posts'     => true,
				'edit_posts'             => true,
				'edit_pages'             => true,
				'edit_published_posts'   => true,
				'edit_published_pages'   => true,
				'edit_private_pages'     => true,
				'edit_private_posts'     => true,
				'edit_others_posts'      => true,
				'edit_others_pages'      => true,
				'publish_posts'          => true,
				'publish_pages'          => true,
				'delete_posts'           => true,
				'delete_pages'           => true,
				'delete_private_pages'   => true,
				'delete_private_posts'   => true,
				'delete_published_pages' => true,
				'delete_published_posts' => true,
				'delete_others_posts'    => true,
				'delete_others_pages'    => true,
				'manage_categories'      => true,
				'manage_links'           => true,
				'moderate_comments'      => true,
				'upload_files'           => true,
				'export'                 => true,
				'import'                 => true,
				'list_users'             => true,
				'edit_theme_options'     => true,
			)
		);

		$capabilities = self::get_core_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->add_cap( 'shop_manager', $cap );
				$wp_roles->add_cap( 'administrator', $cap );
			}
		}
	}

	/**
	 * Get capabilities for WooCommerce - these are assigned to admin/shop manager during installation or reset.
	 *
	 * @return array
	 */
	public static function get_core_capabilities() {
		$capabilities = array();

		$capabilities['core'] = array(
			'manage_woocommerce',
			'view_woocommerce_reports',
		);

		$capability_types = array( 'product', 'shop_order', 'shop_coupon' );

		foreach ( $capability_types as $capability_type ) {

			$capabilities[ $capability_type ] = array(
				// Post type.
				"edit_{$capability_type}",
				"read_{$capability_type}",
				"delete_{$capability_type}",
				"edit_{$capability_type}s",
				"edit_others_{$capability_type}s",
				"publish_{$capability_type}s",
				"read_private_{$capability_type}s",
				"delete_{$capability_type}s",
				"delete_private_{$capability_type}s",
				"delete_published_{$capability_type}s",
				"delete_others_{$capability_type}s",
				"edit_private_{$capability_type}s",
				"edit_published_{$capability_type}s",

				// Terms.
				"manage_{$capability_type}_terms",
				"edit_{$capability_type}_terms",
				"delete_{$capability_type}_terms",
				"assign_{$capability_type}_terms",
			);
		}

		return $capabilities;
	}

	/**
	 * Remove WooCommerce roles.
	 */
	public static function remove_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		$capabilities = self::get_core_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->remove_cap( 'shop_manager', $cap );
				$wp_roles->remove_cap( 'administrator', $cap );
			}
		}

		remove_role( 'customer' );
		remove_role( 'shop_manager' );
	}

	/**
	 * Create files/directories.
	 */
	private static function create_files() {
		/**
		 * Bypass if filesystem is read-only and/or non-standard upload system is used.
		 *
		 * @since 3.2.0
		 */
		if ( apply_filters( 'woocommerce_install_skip_create_files', false ) ) {
			return;
		}

		// Install files and folders for uploading files and prevent hotlinking.
		$upload_dir      = wp_get_upload_dir();
		$download_method = get_option( 'woocommerce_file_download_method', 'force' );

		$files = array(
			array(
				'base'    => $upload_dir['basedir'] . '/woocommerce_uploads',
				'file'    => 'index.html',
				'content' => '',
			),
			array(
				'base'    => $upload_dir['basedir'] . '/woocommerce_uploads',
				'file'    => '.htaccess',
				'content' => 'redirect' === $download_method ? 'Options -Indexes' : 'deny from all',
			),
		);

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'wb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen
				if ( $file_handle ) {
					fwrite( $file_handle, $file['content'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
					fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
				}
			}
		}

		// Create attachment for placeholders.
		self::create_placeholder_image();
	}

	/**
	 * Create a placeholder image in the media library.
	 *
	 * @since 3.5.0
	 */
	private static function create_placeholder_image() {
		$placeholder_image = get_option( 'woocommerce_placeholder_image', 0 );

		// Validate current setting if set. If set, return.
		if ( ! empty( $placeholder_image ) ) {
			if ( ! is_numeric( $placeholder_image ) ) {
				return;
			} elseif ( $placeholder_image && wp_attachment_is_image( $placeholder_image ) ) {
				return;
			}
		}

		$upload_dir = wp_upload_dir();
		$source     = WC()->plugin_path() . '/assets/images/placeholder-attachment.png';
		$filename   = $upload_dir['basedir'] . '/woocommerce-placeholder.png';

		if ( ! file_exists( $filename ) ) {
			copy( $source, $filename ); // @codingStandardsIgnoreLine.
		}

		if ( ! file_exists( $filename ) ) {
			update_option( 'woocommerce_placeholder_image', 0 );
			return;
		}

		$filetype   = wp_check_filetype( basename( $filename ), null );
		$attachment = array(
			'guid'           => $upload_dir['url'] . '/' . basename( $filename ),
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment, $filename );
		if ( is_wp_error( $attach_id ) ) {
			update_option( 'woocommerce_placeholder_image', 0 );
			return;
		}

		update_option( 'woocommerce_placeholder_image', $attach_id );

		// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Generate the metadata for the attachment, and update the database record.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id, $attach_data );
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @param mixed $links Plugin Action links.
	 *
	 * @return array
	 */
	public static function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings' ) . '" aria-label="' . esc_attr__( 'View WooCommerce settings', 'woocommerce' ) . '">' . esc_html__( 'Settings', 'woocommerce' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param mixed $links Plugin Row Meta.
	 * @param mixed $file  Plugin Base file.
	 *
	 * @return array
	 */
	public static function plugin_row_meta( $links, $file ) {
		if ( WC_PLUGIN_BASENAME !== $file ) {
			return $links;
		}

		/**
		 * The WooCommerce documentation URL.
		 *
		 * @since 2.7.0
		 */
		$docs_url = apply_filters( 'woocommerce_docs_url', 'https://woocommerce.com/documentation/plugins/woocommerce/' );

		/**
		 * The WooCommerce API documentation URL.
		 *
		 * @since 2.2.0
		 */
		$api_docs_url = apply_filters( 'woocommerce_apidocs_url', 'https://woocommerce.com/wc-apidocs/' );

		/**
		 * The community WooCommerce support URL.
		 *
		 * @since 2.2.0
		 */
		$community_support_url = apply_filters( 'woocommerce_community_support_url', 'https://wordpress.org/support/plugin/woocommerce/' );

		/**
		 * The premium support URL.
		 *
		 * @since 6.7.0
		 */
		$support_url = apply_filters( 'woocommerce_support_url', 'https://woocommerce.com/my-account/create-a-ticket/' );

		$row_meta = array(
			'docs'    => '<a href="' . esc_url( $docs_url ) . '" aria-label="' . esc_attr__( 'View WooCommerce documentation', 'woocommerce' ) . '">' . esc_html__( 'Docs', 'woocommerce' ) . '</a>',
			'apidocs' => '<a href="' . esc_url( $api_docs_url ) . '" aria-label="' . esc_attr__( 'View WooCommerce API docs', 'woocommerce' ) . '">' . esc_html__( 'API docs', 'woocommerce' ) . '</a>',
			'support' => '<a href="' . esc_url( $community_support_url ) . '" aria-label="' . esc_attr__( 'Visit community forums', 'woocommerce' ) . '">' . esc_html__( 'Community support', 'woocommerce' ) . '</a>',
		);

		if ( WCConnectionHelper::is_connected() ) {
			$row_meta['premium_support'] = '<a href="' . esc_url( $support_url ) . '" aria-label="' . esc_attr__( 'Visit premium customer support', 'woocommerce' ) . '">' . esc_html__( 'Premium support', 'woocommerce' ) . '</a>';
		}

		return array_merge( $links, $row_meta );
	}

	/**
	 * Get slug from path and associate it with the path.
	 *
	 * @param array  $plugins Associative array of plugin files to paths.
	 * @param string $key Plugin relative path. Example: woocommerce/woocommerce.php.
	 */
	private static function associate_plugin_file( $plugins, $key ) {
		$path                 = explode( '/', $key );
		$filename             = end( $path );
		$plugins[ $filename ] = $key;
		return $plugins;
	}

	/**
	 * Install a plugin from .org in the background via a cron job (used by
	 * installer - opt in).
	 *
	 * @param string $plugin_to_install_id Plugin ID.
	 * @param array  $plugin_to_install Plugin information.
	 *
	 * @throws Exception If unable to proceed with plugin installation.
	 * @since  2.6.0
	 */
	public static function background_installer( $plugin_to_install_id, $plugin_to_install ) {
		// Explicitly clear the event.
		$args = func_get_args();

		if ( ! empty( $plugin_to_install['repo-slug'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			WP_Filesystem();

			$skin              = new Automatic_Upgrader_Skin();
			$upgrader          = new WP_Upgrader( $skin );
			$installed_plugins = array_reduce( array_keys( get_plugins() ), array( __CLASS__, 'associate_plugin_file' ) );
			if ( empty( $installed_plugins ) ) {
				$installed_plugins = array();
			}
			$plugin_slug = $plugin_to_install['repo-slug'];
			$plugin_file = isset( $plugin_to_install['file'] ) ? $plugin_to_install['file'] : $plugin_slug . '.php';
			$installed   = false;
			$activate    = false;

			// See if the plugin is installed already.
			if ( isset( $installed_plugins[ $plugin_file ] ) ) {
				$installed = true;
				$activate  = ! is_plugin_active( $installed_plugins[ $plugin_file ] );
			}

			// Install this thing!
			if ( ! $installed ) {
				// Suppress feedback.
				ob_start();

				try {
					$plugin_information = plugins_api(
						'plugin_information',
						array(
							'slug'   => $plugin_slug,
							'fields' => array(
								'short_description' => false,
								'sections'          => false,
								'requires'          => false,
								'rating'            => false,
								'ratings'           => false,
								'downloaded'        => false,
								'last_updated'      => false,
								'added'             => false,
								'tags'              => false,
								'homepage'          => false,
								'donate_link'       => false,
								'author_profile'    => false,
								'author'            => false,
							),
						)
					);

					if ( is_wp_error( $plugin_information ) ) {
						throw new Exception( $plugin_information->get_error_message() );
					}

					$package  = $plugin_information->download_link;
					$download = $upgrader->download_package( $package );

					if ( is_wp_error( $download ) ) {
						throw new Exception( $download->get_error_message() );
					}

					$working_dir = $upgrader->unpack_package( $download, true );

					if ( is_wp_error( $working_dir ) ) {
						throw new Exception( $working_dir->get_error_message() );
					}

					$result = $upgrader->install_package(
						array(
							'source'                      => $working_dir,
							'destination'                 => WP_PLUGIN_DIR,
							'clear_destination'           => false,
							'abort_if_destination_exists' => false,
							'clear_working'               => true,
							'hook_extra'                  => array(
								'type'   => 'plugin',
								'action' => 'install',
							),
						)
					);

					if ( is_wp_error( $result ) ) {
						throw new Exception( $result->get_error_message() );
					}

					$activate = true;

				} catch ( Exception $e ) {
					WC_Admin_Notices::add_custom_notice(
						$plugin_to_install_id . '_install_error',
						sprintf(
							// translators: 1: plugin name, 2: error message, 3: URL to install plugin manually.
							__( '%1$s could not be installed (%2$s). <a href="%3$s">Please install it manually by clicking here.</a>', 'woocommerce' ),
							$plugin_to_install['name'],
							$e->getMessage(),
							esc_url( admin_url( 'index.php?wc-install-plugin-redirect=' . $plugin_slug ) )
						)
					);
				}

				// Discard feedback.
				ob_end_clean();
			}

			wp_clean_plugins_cache();

			// Activate this thing.
			if ( $activate ) {
				try {
					add_action( 'add_option_mailchimp_woocommerce_plugin_do_activation_redirect', array( __CLASS__, 'remove_mailchimps_redirect' ), 10, 2 );
					$result = activate_plugin( $installed ? $installed_plugins[ $plugin_file ] : $plugin_slug . '/' . $plugin_file );

					if ( is_wp_error( $result ) ) {
						throw new Exception( $result->get_error_message() );
					}
				} catch ( Exception $e ) {
					WC_Admin_Notices::add_custom_notice(
						$plugin_to_install_id . '_install_error',
						sprintf(
							// translators: 1: plugin name, 2: URL to WP plugin page.
							__( '%1$s was installed but could not be activated. <a href="%2$s">Please activate it manually by clicking here.</a>', 'woocommerce' ),
							$plugin_to_install['name'],
							admin_url( 'plugins.php' )
						)
					);
				}
			}
		}
	}

	/**
	 * Removes redirect added during MailChimp plugin's activation.
	 *
	 * @param string $option Option name.
	 * @param string $value  Option value.
	 */
	public static function remove_mailchimps_redirect( $option, $value ) {
		// Remove this action to prevent infinite looping.
		remove_action( 'add_option_mailchimp_woocommerce_plugin_do_activation_redirect', array( __CLASS__, 'remove_mailchimps_redirect' ) );

		// Update redirect back to false.
		update_option( 'mailchimp_woocommerce_plugin_do_activation_redirect', false );
	}

	/**
	 * Install a theme from .org in the background via a cron job (used by installer - opt in).
	 *
	 * @param string $theme_slug Theme slug.
	 *
	 * @throws Exception If unable to proceed with theme installation.
	 * @since  3.1.0
	 */
	public static function theme_background_installer( $theme_slug ) {
		// Explicitly clear the event.
		$args = func_get_args();

		if ( ! empty( $theme_slug ) ) {
			// Suppress feedback.
			ob_start();

			try {
				$theme = wp_get_theme( $theme_slug );

				if ( ! $theme->exists() ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
					include_once ABSPATH . 'wp-admin/includes/theme.php';

					WP_Filesystem();

					$skin     = new Automatic_Upgrader_Skin();
					$upgrader = new Theme_Upgrader( $skin );
					$api      = themes_api(
						'theme_information',
						array(
							'slug'   => $theme_slug,
							'fields' => array( 'sections' => false ),
						)
					);
					$result   = $upgrader->install( $api->download_link );

					if ( is_wp_error( $result ) ) {
						throw new Exception( $result->get_error_message() );
					} elseif ( is_wp_error( $skin->result ) ) {
						throw new Exception( $skin->result->get_error_message() );
					} elseif ( is_null( $result ) ) {
						throw new Exception( 'Unable to connect to the filesystem. Please confirm your credentials.' );
					}
				}

				switch_theme( $theme_slug );
			} catch ( Exception $e ) {
				WC_Admin_Notices::add_custom_notice(
					$theme_slug . '_install_error',
					sprintf(
						// translators: 1: theme slug, 2: error message, 3: URL to install theme manually.
						__( '%1$s could not be installed (%2$s). <a href="%3$s">Please install it manually by clicking here.</a>', 'woocommerce' ),
						$theme_slug,
						$e->getMessage(),
						esc_url( admin_url( 'update.php?action=install-theme&theme=' . $theme_slug . '&_wpnonce=' . wp_create_nonce( 'install-theme_' . $theme_slug ) ) )
					)
				);
			}

			// Discard feedback.
			ob_end_clean();
		}
	}

	/**
	 * Sets whether PayPal Standard will be loaded on install.
	 *
	 * @since 5.5.0
	 */
	private static function set_paypal_standard_load_eligibility() {
		// Initiating the payment gateways sets the flag.
		if ( class_exists( 'WC_Gateway_Paypal' ) ) {
			( new WC_Gateway_Paypal() )->should_load();
		}
	}

	/**
	 * Gets the content of the sample refunds and return policy page.
	 *
	 * @since 5.6.0
	 * @return string The content for the page
	 */
	private static function get_refunds_return_policy_page_content() {
		return <<<EOT
<!-- wp:paragraph -->
<p><b>This is a sample page.</b></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Overview</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Our refund and returns policy lasts 30 days. If 30 days have passed since your purchase, we can’t offer you a full refund or exchange.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>To be eligible for a return, your item must be unused and in the same condition that you received it. It must also be in the original packaging.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Several types of goods are exempt from being returned. Perishable goods such as food, flowers, newspapers or magazines cannot be returned. We also do not accept products that are intimate or sanitary goods, hazardous materials, or flammable liquids or gases.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Additional non-returnable items:</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul>
<li>Gift cards</li>
<li>Downloadable software products</li>
<li>Some health and personal care items</li>
</ul>
<!-- /wp:list -->

<!-- wp:paragraph -->
<p>To complete your return, we require a receipt or proof of purchase.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Please do not send your purchase back to the manufacturer.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>There are certain situations where only partial refunds are granted:</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul>
<li>Book with obvious signs of use</li>
<li>CD, DVD, VHS tape, software, video game, cassette tape, or vinyl record that has been opened.</li>
<li>Any item not in its original condition, is damaged or missing parts for reasons not due to our error.</li>
<li>Any item that is returned more than 30 days after delivery</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Refunds</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Once your return is received and inspected, we will send you an email to notify you that we have received your returned item. We will also notify you of the approval or rejection of your refund.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>If you are approved, then your refund will be processed, and a credit will automatically be applied to your credit card or original method of payment, within a certain amount of days.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h3 class="wp-block-heading">Late or missing refunds</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>If you haven’t received a refund yet, first check your bank account again.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Then contact your credit card company, it may take some time before your refund is officially posted.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Next contact your bank. There is often some processing time before a refund is posted.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>If you’ve done all of this and you still have not received your refund yet, please contact us at {email address}.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h3 class="wp-block-heading">Sale items</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Only regular priced items may be refunded. Sale items cannot be refunded.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Exchanges</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>We only replace items if they are defective or damaged. If you need to exchange it for the same item, send us an email at {email address} and send your item to: {physical address}.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Gifts</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>If the item was marked as a gift when purchased and shipped directly to you, you’ll receive a gift credit for the value of your return. Once the returned item is received, a gift certificate will be mailed to you.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>If the item wasn’t marked as a gift when purchased, or the gift giver had the order shipped to themselves to give to you later, we will send a refund to the gift giver and they will find out about your return.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Shipping returns</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>To return your product, you should mail your product to: {physical address}.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>You will be responsible for paying for your own shipping costs for returning your item. Shipping costs are non-refundable. If you receive a refund, the cost of return shipping will be deducted from your refund.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Depending on where you live, the time it may take for your exchanged product to reach you may vary.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>If you are returning more expensive items, you may consider using a trackable shipping service or purchasing shipping insurance. We don’t guarantee that we will receive your returned item.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Need help?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Contact us at {email} for questions related to refunds and returns.</p>
<!-- /wp:paragraph -->
EOT;
	}

	/**
	 * Adds an admin inbox note after a page has been created to notify
	 * user. For example to take action to edit the page such as the
	 * Refund and returns page.
	 *
	 * @since 5.6.0
	 * @return void
	 */
	public static function add_admin_note_after_page_created() {
		if ( ! WC()->is_wc_admin_active() ) {
			return;
		}

		$page_id = get_option( 'woocommerce_refund_returns_page_created', null );

		if ( null === $page_id ) {
			return;
		}

		WC_Notes_Refund_Returns::possibly_add_note( $page_id );
	}

	/**
	 * When pages are created, we might want to take some action.
	 * In this case we want to set an option when refund and returns
	 * page is created.
	 *
	 * @since 5.6.0
	 * @param int   $page_id ID of the page.
	 * @param array $page_data The data of the page created.
	 * @return void
	 */
	public static function page_created( $page_id, $page_data ) {
		if ( 'refund_returns' === $page_data['post_name'] ) {
			delete_option( 'woocommerce_refund_returns_page_created' );
			add_option( 'woocommerce_refund_returns_page_created', $page_id, '', false );
		}
	}

	/**
	 * Get the Cart block content.
	 *
	 * @since 8.3.0
	 * @return string
	 */
	protected static function get_cart_block_content() {
		return '<!-- wp:woocommerce/cart -->
<div class="wp-block-woocommerce-cart alignwide is-loading"><!-- wp:woocommerce/filled-cart-block -->
<div class="wp-block-woocommerce-filled-cart-block"><!-- wp:woocommerce/cart-items-block -->
<div class="wp-block-woocommerce-cart-items-block"><!-- wp:woocommerce/cart-line-items-block -->
<div class="wp-block-woocommerce-cart-line-items-block"></div>
<!-- /wp:woocommerce/cart-line-items-block -->

<!-- wp:woocommerce/cart-cross-sells-block -->
<div class="wp-block-woocommerce-cart-cross-sells-block"><!-- wp:heading {"fontSize":"large"} -->
<h2 class="wp-block-heading has-large-font-size">' . __( 'You may be interested in…', 'woocommerce' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/cart-cross-sells-products-block -->
<div class="wp-block-woocommerce-cart-cross-sells-products-block"></div>
<!-- /wp:woocommerce/cart-cross-sells-products-block --></div>
<!-- /wp:woocommerce/cart-cross-sells-block --></div>
<!-- /wp:woocommerce/cart-items-block -->

<!-- wp:woocommerce/cart-totals-block -->
<div class="wp-block-woocommerce-cart-totals-block"><!-- wp:woocommerce/cart-order-summary-block -->
<div class="wp-block-woocommerce-cart-order-summary-block"><!-- wp:woocommerce/cart-order-summary-heading-block -->
<div class="wp-block-woocommerce-cart-order-summary-heading-block"></div>
<!-- /wp:woocommerce/cart-order-summary-heading-block -->

<!-- wp:woocommerce/cart-order-summary-coupon-form-block -->
<div class="wp-block-woocommerce-cart-order-summary-coupon-form-block"></div>
<!-- /wp:woocommerce/cart-order-summary-coupon-form-block -->

<!-- wp:woocommerce/cart-order-summary-subtotal-block -->
<div class="wp-block-woocommerce-cart-order-summary-subtotal-block"></div>
<!-- /wp:woocommerce/cart-order-summary-subtotal-block -->

<!-- wp:woocommerce/cart-order-summary-fee-block -->
<div class="wp-block-woocommerce-cart-order-summary-fee-block"></div>
<!-- /wp:woocommerce/cart-order-summary-fee-block -->

<!-- wp:woocommerce/cart-order-summary-discount-block -->
<div class="wp-block-woocommerce-cart-order-summary-discount-block"></div>
<!-- /wp:woocommerce/cart-order-summary-discount-block -->

<!-- wp:woocommerce/cart-order-summary-shipping-block -->
<div class="wp-block-woocommerce-cart-order-summary-shipping-block"></div>
<!-- /wp:woocommerce/cart-order-summary-shipping-block -->

<!-- wp:woocommerce/cart-order-summary-taxes-block -->
<div class="wp-block-woocommerce-cart-order-summary-taxes-block"></div>
<!-- /wp:woocommerce/cart-order-summary-taxes-block --></div>
<!-- /wp:woocommerce/cart-order-summary-block -->

<!-- wp:woocommerce/cart-express-payment-block -->
<div class="wp-block-woocommerce-cart-express-payment-block"></div>
<!-- /wp:woocommerce/cart-express-payment-block -->

<!-- wp:woocommerce/proceed-to-checkout-block -->
<div class="wp-block-woocommerce-proceed-to-checkout-block"></div>
<!-- /wp:woocommerce/proceed-to-checkout-block -->

<!-- wp:woocommerce/cart-accepted-payment-methods-block -->
<div class="wp-block-woocommerce-cart-accepted-payment-methods-block"></div>
<!-- /wp:woocommerce/cart-accepted-payment-methods-block --></div>
<!-- /wp:woocommerce/cart-totals-block --></div>
<!-- /wp:woocommerce/filled-cart-block -->

<!-- wp:woocommerce/empty-cart-block -->
<div class="wp-block-woocommerce-empty-cart-block"><!-- wp:heading {"textAlign":"center","className":"with-empty-cart-icon wc-block-cart__empty-cart__title"} -->
<h2 class="wp-block-heading has-text-align-center with-empty-cart-icon wc-block-cart__empty-cart__title">' . __( 'Your cart is currently empty!', 'woocommerce' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:separator {"className":"is-style-dots"} -->
<hr class="wp-block-separator has-alpha-channel-opacity is-style-dots"/>
<!-- /wp:separator -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">' . __( 'New in store', 'woocommerce' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-new {"columns":4,"rows":1} /--></div>
<!-- /wp:woocommerce/empty-cart-block --></div>
<!-- /wp:woocommerce/cart -->';
	}

	/**
	 * Get the Checkout block content.
	 *
	 * @since 8.3.0
	 * @return string
	 */
	protected static function get_checkout_block_content() {
		return '<!-- wp:woocommerce/checkout -->
<div class="wp-block-woocommerce-checkout alignwide wc-block-checkout is-loading"><!-- wp:woocommerce/checkout-fields-block -->
<div class="wp-block-woocommerce-checkout-fields-block"><!-- wp:woocommerce/checkout-express-payment-block -->
<div class="wp-block-woocommerce-checkout-express-payment-block"></div>
<!-- /wp:woocommerce/checkout-express-payment-block -->

<!-- wp:woocommerce/checkout-contact-information-block -->
<div class="wp-block-woocommerce-checkout-contact-information-block"></div>
<!-- /wp:woocommerce/checkout-contact-information-block -->

<!-- wp:woocommerce/checkout-shipping-method-block -->
<div class="wp-block-woocommerce-checkout-shipping-method-block"></div>
<!-- /wp:woocommerce/checkout-shipping-method-block -->

<!-- wp:woocommerce/checkout-pickup-options-block -->
<div class="wp-block-woocommerce-checkout-pickup-options-block"></div>
<!-- /wp:woocommerce/checkout-pickup-options-block -->

<!-- wp:woocommerce/checkout-shipping-address-block -->
<div class="wp-block-woocommerce-checkout-shipping-address-block"></div>
<!-- /wp:woocommerce/checkout-shipping-address-block -->

<!-- wp:woocommerce/checkout-billing-address-block -->
<div class="wp-block-woocommerce-checkout-billing-address-block"></div>
<!-- /wp:woocommerce/checkout-billing-address-block -->

<!-- wp:woocommerce/checkout-shipping-methods-block -->
<div class="wp-block-woocommerce-checkout-shipping-methods-block"></div>
<!-- /wp:woocommerce/checkout-shipping-methods-block -->

<!-- wp:woocommerce/checkout-payment-block -->
<div class="wp-block-woocommerce-checkout-payment-block"></div>
<!-- /wp:woocommerce/checkout-payment-block -->

<!-- wp:woocommerce/checkout-additional-information-block -->
<div class="wp-block-woocommerce-checkout-additional-information-block"></div>
<!-- /wp:woocommerce/checkout-additional-information-block -->

<!-- wp:woocommerce/checkout-order-note-block -->
<div class="wp-block-woocommerce-checkout-order-note-block"></div>
<!-- /wp:woocommerce/checkout-order-note-block -->

<!-- wp:woocommerce/checkout-terms-block -->
<div class="wp-block-woocommerce-checkout-terms-block"></div>
<!-- /wp:woocommerce/checkout-terms-block -->

<!-- wp:woocommerce/checkout-actions-block -->
<div class="wp-block-woocommerce-checkout-actions-block"></div>
<!-- /wp:woocommerce/checkout-actions-block --></div>
<!-- /wp:woocommerce/checkout-fields-block -->

<!-- wp:woocommerce/checkout-totals-block -->
<div class="wp-block-woocommerce-checkout-totals-block"><!-- wp:woocommerce/checkout-order-summary-block -->
<div class="wp-block-woocommerce-checkout-order-summary-block"><!-- wp:woocommerce/checkout-order-summary-cart-items-block -->
<div class="wp-block-woocommerce-checkout-order-summary-cart-items-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-cart-items-block -->

<!-- wp:woocommerce/checkout-order-summary-coupon-form-block -->
<div class="wp-block-woocommerce-checkout-order-summary-coupon-form-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-coupon-form-block -->

<!-- wp:woocommerce/checkout-order-summary-subtotal-block -->
<div class="wp-block-woocommerce-checkout-order-summary-subtotal-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-subtotal-block -->

<!-- wp:woocommerce/checkout-order-summary-fee-block -->
<div class="wp-block-woocommerce-checkout-order-summary-fee-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-fee-block -->

<!-- wp:woocommerce/checkout-order-summary-discount-block -->
<div class="wp-block-woocommerce-checkout-order-summary-discount-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-discount-block -->

<!-- wp:woocommerce/checkout-order-summary-shipping-block -->
<div class="wp-block-woocommerce-checkout-order-summary-shipping-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-shipping-block -->

<!-- wp:woocommerce/checkout-order-summary-taxes-block -->
<div class="wp-block-woocommerce-checkout-order-summary-taxes-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-taxes-block --></div>
<!-- /wp:woocommerce/checkout-order-summary-block --></div>
<!-- /wp:woocommerce/checkout-totals-block --></div>
<!-- /wp:woocommerce/checkout -->';
	}
}

WC_Install::init();
