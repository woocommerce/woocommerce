<?php
/**
 * Installation related functions and actions.
 *
 * @package WooCommerce/Classes
 * @version 3.0.0
 */

use Automattic\Jetpack\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Install Class.
 */
class WC_Install {

	/**
	 * DB updates and callbacks that need to be run per version.
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
			'wc_update_400_db_version',
			'wc_reset_action_scheduler_migration_status',
		),
	);

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'init', array( __CLASS__, 'manual_database_update' ), 20 );
		add_action( 'admin_init', array( __CLASS__, 'wc_admin_db_update_notice' ) );
		add_action( 'woocommerce_run_update_callback', array( __CLASS__, 'run_update_callback' ) );
		add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
		add_filter( 'plugin_action_links_' . WC_PLUGIN_BASENAME, array( __CLASS__, 'plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );
		add_filter( 'wpmu_drop_tables', array( __CLASS__, 'wpmu_drop_tables' ) );
		add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );
	}

	/**
	 * Check WooCommerce version and run the updater is required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if ( ! Constants::is_defined( 'IFRAME_REQUEST' ) && version_compare( get_option( 'woocommerce_version' ), WC()->version, '<' ) ) {
			self::install();
			do_action( 'woocommerce_updated' );
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
	 * @since 3.6.0
	 * @param string $callback Callback name.
	 */
	public static function run_update_callback( $callback ) {
		include_once dirname( __FILE__ ) . '/wc-update-functions.php';

		if ( is_callable( $callback ) ) {
			self::run_update_callback_start( $callback );
			$result = (bool) call_user_func( $callback );
			self::run_update_callback_end( $callback, $result );
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
		if ( 'yes' === get_transient( 'wc_installing' ) ) {
			return;
		}

		// If we made it till here nothing is running yet, lets set the transient now.
		set_transient( 'wc_installing', 'yes', MINUTE_IN_SECONDS * 10 );
		wc_maybe_define_constant( 'WC_INSTALLING', true );

		WC()->wpdb_table_fix();
		self::remove_admin_notices();
		self::create_tables();
		self::create_options();
		self::create_roles();
		self::setup_environment();
		self::create_terms();
		self::create_cron_jobs();
		self::create_files();
		self::maybe_enable_setup_wizard();
		self::update_wc_version();
		self::maybe_update_db_version();

		delete_transient( 'wc_installing' );

		do_action( 'woocommerce_flush_rewrite_rules' );
		do_action( 'woocommerce_installed' );
	}

	/**
	 * Reset any notices added to admin.
	 *
	 * @since 3.2.0
	 */
	private static function remove_admin_notices() {
		include_once dirname( __FILE__ ) . '/admin/class-wc-admin-notices.php';
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
		WC_API::add_endpoint();
		WC_Auth::add_endpoint();
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
		$product_count = array_sum( (array) wp_count_posts( 'product' ) );

		return is_null( get_option( 'woocommerce_version', null ) ) || ( 0 === $product_count && -1 === wc_get_page_id( 'shop' ) );
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
	 * See if we need the wizard or not.
	 *
	 * @since 3.2.0
	 */
	private static function maybe_enable_setup_wizard() {
		if ( apply_filters( 'woocommerce_enable_setup_wizard', true ) && self::is_new_install() ) {
			WC_Admin_Notices::add_notice( 'install', true );
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
	 * Update WC version to current.
	 */
	private static function update_wc_version() {
		delete_option( 'woocommerce_version' );
		add_option( 'woocommerce_version', WC()->version );
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
					$loop++;
				}
			}
		}
	}

	/**
	 * Update DB version to current.
	 *
	 * @param string|null $version New WooCommerce DB version or null.
	 */
	public static function update_db_version( $version = null ) {
		delete_option( 'woocommerce_db_version' );
		add_option( 'woocommerce_db_version', is_null( $version ) ? WC()->version : $version );
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

		$ve = get_option( 'gmt_offset' ) > 0 ? '-' : '+';

		wp_schedule_event( strtotime( '00:00 tomorrow ' . $ve . absint( get_option( 'gmt_offset' ) ) . ' HOURS' ), 'daily', 'woocommerce_scheduled_sales' );

		$held_duration = get_option( 'woocommerce_hold_stock_minutes', '60' );

		if ( '' !== $held_duration ) {
			wp_schedule_single_event( time() + ( absint( $held_duration ) * 60 ), 'woocommerce_cancel_unpaid_orders' );
		}

		// Delay the first run of `woocommerce_cleanup_personal_data` by 10 seconds
		// so it doesn't occur in the same request. WooCommerce Admin also schedules
		// a daily cron that gets lost due to a race condition. WC_Privacy's background
		// processing instance updates the cron schedule from within a cron job.
		wp_schedule_event( time() + 10, 'daily', 'woocommerce_cleanup_personal_data' );
		wp_schedule_event( time() + ( 3 * HOUR_IN_SECONDS ), 'daily', 'woocommerce_cleanup_logs' );
		wp_schedule_event( time() + ( 6 * HOUR_IN_SECONDS ), 'twicedaily', 'woocommerce_cleanup_sessions' );
		wp_schedule_event( time() + MINUTE_IN_SECONDS, 'fifteendays', 'woocommerce_geoip_updater' );
		wp_schedule_event( time() + 10, apply_filters( 'woocommerce_tracker_event_recurrence', 'daily' ), 'woocommerce_tracker_send_event' );
	}

	/**
	 * Create pages that the plugin relies on, storing page IDs in variables.
	 */
	public static function create_pages() {
		include_once dirname( __FILE__ ) . '/admin/wc-admin-functions.php';

		$pages = apply_filters(
			'woocommerce_create_pages',
			array(
				'shop'      => array(
					'name'    => _x( 'shop', 'Page slug', 'woocommerce' ),
					'title'   => _x( 'Shop', 'Page title', 'woocommerce' ),
					'content' => '',
				),
				'cart'      => array(
					'name'    => _x( 'cart', 'Page slug', 'woocommerce' ),
					'title'   => _x( 'Cart', 'Page title', 'woocommerce' ),
					'content' => '<!-- wp:shortcode -->[' . apply_filters( 'woocommerce_cart_shortcode_tag', 'woocommerce_cart' ) . ']<!-- /wp:shortcode -->',
				),
				'checkout'  => array(
					'name'    => _x( 'checkout', 'Page slug', 'woocommerce' ),
					'title'   => _x( 'Checkout', 'Page title', 'woocommerce' ),
					'content' => '<!-- wp:shortcode -->[' . apply_filters( 'woocommerce_checkout_shortcode_tag', 'woocommerce_checkout' ) . ']<!-- /wp:shortcode -->',
				),
				'myaccount' => array(
					'name'    => _x( 'my-account', 'Page slug', 'woocommerce' ),
					'title'   => _x( 'My account', 'Page title', 'woocommerce' ),
					'content' => '<!-- wp:shortcode -->[' . apply_filters( 'woocommerce_my_account_shortcode_tag', 'woocommerce_my_account' ) . ']<!-- /wp:shortcode -->',
				),
			)
		);

		foreach ( $pages as $key => $page ) {
			wc_create_page( esc_sql( $page['name'] ), 'woocommerce_' . $key . '_page_id', $page['title'], $page['content'], ! empty( $page['parent'] ) ? wc_get_page_id( $page['parent'] ) : '' );
		}
	}

	/**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function create_options() {
		// Include settings so that we can run through defaults.
		include_once dirname( __FILE__ ) . '/admin/class-wc-admin-settings.php';

		$settings = WC_Admin_Settings::get_settings_pages();

		foreach ( $settings as $section ) {
			if ( ! method_exists( $section, 'get_settings' ) ) {
				continue;
			}
			$subsections = array_unique( array_merge( array( '' ), array_keys( $section->get_sections() ) ) );

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

		// Define initial tax classes.
		WC_Tax::create_tax_class( __( 'Reduced rate', 'woocommerce' ) );
		WC_Tax::create_tax_class( __( 'Zero rate', 'woocommerce' ) );
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
	 * Set up the database tables which the plugin needs to function.
	 *
	 * Tables:
	 *      woocommerce_attribute_taxonomies - Table for storing attribute taxonomies - these are user defined
	 *      woocommerce_downloadable_product_permissions - Table for storing user and guest download permissions.
	 *          KEY(order_id, product_id, download_id) used for organizing downloads on the My Account page
	 *      woocommerce_order_items - Order line items are stored in a table to make them easily queryable for reports
	 *      woocommerce_order_itemmeta - Order line item meta is stored in a table for storing extra data.
	 *      woocommerce_tax_rates - Tax Rates are stored inside 2 tables making tax queries simple and efficient.
	 *      woocommerce_tax_rate_locations - Each rate can be applied to more than one postcode/city hence the second table.
	 */
	private static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		/**
		 * Before updating with DBDELTA, remove any primary keys which could be
		 * modified due to schema updates.
		 */
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_downloadable_product_permissions';" ) ) {
			if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}woocommerce_downloadable_product_permissions` LIKE 'permission_id';" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}woocommerce_downloadable_product_permissions DROP PRIMARY KEY, ADD `permission_id` BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT;" );
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

		dbDelta( self::get_schema() );

		$index_exists = $wpdb->get_row( "SHOW INDEX FROM {$wpdb->comments} WHERE column_name = 'comment_type' and key_name = 'woo_idx_comment_type'" );

		if ( is_null( $index_exists ) ) {
			// Add an index to the field comment_type to improve the response time of the query
			// used by WC_Comments::wp_count_comments() to get the number of comments by type.
			$wpdb->query( "ALTER TABLE {$wpdb->comments} ADD INDEX woo_idx_comment_type (comment_type)" );
		}

		// Get tables data types and check it matches before adding constraint.
		$download_log_columns     = $wpdb->get_results( "SHOW COLUMNS FROM {$wpdb->prefix}wc_download_log WHERE Field = 'permission_id'", ARRAY_A );
		$download_log_column_type = '';
		if ( isset( $download_log_columns[0]['Type'] ) ) {
			$download_log_column_type = $download_log_columns[0]['Type'];
		}

		$download_permissions_columns     = $wpdb->get_results( "SHOW COLUMNS FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE Field = 'permission_id'", ARRAY_A );
		$download_permissions_column_type = '';
		if ( isset( $download_permissions_columns[0]['Type'] ) ) {
			$download_permissions_column_type = $download_permissions_columns[0]['Type'];
		}

		// Add constraint to download logs if the columns matches.
		if ( ! empty( $download_permissions_column_type ) && ! empty( $download_log_column_type ) && $download_permissions_column_type === $download_log_column_type ) {
			$fk_result = $wpdb->get_row(
				"SELECT COUNT(*) AS fk_count
				FROM information_schema.TABLE_CONSTRAINTS
				WHERE CONSTRAINT_SCHEMA = '{$wpdb->dbname}'
				AND CONSTRAINT_NAME = 'fk_{$wpdb->prefix}wc_download_log_permission_id'
				AND CONSTRAINT_TYPE = 'FOREIGN KEY'
				AND TABLE_NAME = '{$wpdb->prefix}wc_download_log'"
			); // WPCS: unprepared SQL ok.
			if ( 0 === (int) $fk_result->fk_count ) {
				$wpdb->query(
					"ALTER TABLE `{$wpdb->prefix}wc_download_log`
					ADD CONSTRAINT `fk_{$wpdb->prefix}wc_download_log_permission_id`
					FOREIGN KEY (`permission_id`)
					REFERENCES `{$wpdb->prefix}woocommerce_downloadable_product_permissions` (`permission_id`) ON DELETE CASCADE;"
				); // WPCS: unprepared SQL ok.
			}
		}

		// Clear table caches.
		delete_transient( 'wc_attribute_taxonomies' );
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

		/*
		 * Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
		 * As of WP 4.2, however, they moved to utf8mb4, which uses 4 bytes per character. This means that an index which
		 * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
		 */
		$max_index_length = 191;

		$tables = "
CREATE TABLE {$wpdb->prefix}woocommerce_sessions (
  session_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  session_key char(32) NOT NULL,
  session_value longtext NOT NULL,
  session_expiry BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY  (session_id),
  UNIQUE KEY session_key (session_key)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_api_keys (
  key_id BIGINT UNSIGNED NOT NULL auto_increment,
  user_id BIGINT UNSIGNED NOT NULL,
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
  attribute_id BIGINT UNSIGNED NOT NULL auto_increment,
  attribute_name varchar(200) NOT NULL,
  attribute_label varchar(200) NULL,
  attribute_type varchar(20) NOT NULL,
  attribute_orderby varchar(20) NOT NULL,
  attribute_public int(1) NOT NULL DEFAULT 1,
  PRIMARY KEY  (attribute_id),
  KEY attribute_name (attribute_name(20))
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_downloadable_product_permissions (
  permission_id BIGINT UNSIGNED NOT NULL auto_increment,
  download_id varchar(36) NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  order_key varchar(200) NOT NULL,
  user_email varchar(200) NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  downloads_remaining varchar(9) NULL,
  access_granted datetime NOT NULL default '0000-00-00 00:00:00',
  access_expires datetime NULL default null,
  download_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY  (permission_id),
  KEY download_order_key_product (product_id,order_id,order_key(16),download_id),
  KEY download_order_product (download_id,order_id,product_id),
  KEY order_id (order_id),
  KEY user_order_remaining_expires (user_id,order_id,downloads_remaining,access_expires)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_order_items (
  order_item_id BIGINT UNSIGNED NOT NULL auto_increment,
  order_item_name TEXT NOT NULL,
  order_item_type varchar(200) NOT NULL DEFAULT '',
  order_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY  (order_item_id),
  KEY order_id (order_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_order_itemmeta (
  meta_id BIGINT UNSIGNED NOT NULL auto_increment,
  order_item_id BIGINT UNSIGNED NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY order_item_id (order_item_id),
  KEY meta_key (meta_key(32))
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_tax_rates (
  tax_rate_id BIGINT UNSIGNED NOT NULL auto_increment,
  tax_rate_country varchar(2) NOT NULL DEFAULT '',
  tax_rate_state varchar(200) NOT NULL DEFAULT '',
  tax_rate varchar(8) NOT NULL DEFAULT '',
  tax_rate_name varchar(200) NOT NULL DEFAULT '',
  tax_rate_priority BIGINT UNSIGNED NOT NULL,
  tax_rate_compound int(1) NOT NULL DEFAULT 0,
  tax_rate_shipping int(1) NOT NULL DEFAULT 1,
  tax_rate_order BIGINT UNSIGNED NOT NULL,
  tax_rate_class varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY  (tax_rate_id),
  KEY tax_rate_country (tax_rate_country),
  KEY tax_rate_state (tax_rate_state(2)),
  KEY tax_rate_class (tax_rate_class(10)),
  KEY tax_rate_priority (tax_rate_priority)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_tax_rate_locations (
  location_id BIGINT UNSIGNED NOT NULL auto_increment,
  location_code varchar(200) NOT NULL,
  tax_rate_id BIGINT UNSIGNED NOT NULL,
  location_type varchar(40) NOT NULL,
  PRIMARY KEY  (location_id),
  KEY tax_rate_id (tax_rate_id),
  KEY location_type_code (location_type(10),location_code(20))
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_shipping_zones (
  zone_id BIGINT UNSIGNED NOT NULL auto_increment,
  zone_name varchar(200) NOT NULL,
  zone_order BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY  (zone_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_shipping_zone_locations (
  location_id BIGINT UNSIGNED NOT NULL auto_increment,
  zone_id BIGINT UNSIGNED NOT NULL,
  location_code varchar(200) NOT NULL,
  location_type varchar(40) NOT NULL,
  PRIMARY KEY  (location_id),
  KEY location_id (location_id),
  KEY location_type_code (location_type(10),location_code(20))
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_shipping_zone_methods (
  zone_id BIGINT UNSIGNED NOT NULL,
  instance_id BIGINT UNSIGNED NOT NULL auto_increment,
  method_id varchar(200) NOT NULL,
  method_order BIGINT UNSIGNED NOT NULL,
  is_enabled tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY  (instance_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_payment_tokens (
  token_id BIGINT UNSIGNED NOT NULL auto_increment,
  gateway_id varchar(200) NOT NULL,
  token text NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL DEFAULT '0',
  type varchar(200) NOT NULL,
  is_default tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY  (token_id),
  KEY user_id (user_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_payment_tokenmeta (
  meta_id BIGINT UNSIGNED NOT NULL auto_increment,
  payment_token_id BIGINT UNSIGNED NOT NULL,
  meta_key varchar(255) NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY payment_token_id (payment_token_id),
  KEY meta_key (meta_key(32))
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_log (
  log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  timestamp datetime NOT NULL,
  level smallint(4) NOT NULL,
  source varchar(200) NOT NULL,
  message longtext NOT NULL,
  context longtext NULL,
  PRIMARY KEY (log_id),
  KEY level (level)
) $collate;
CREATE TABLE {$wpdb->prefix}wc_webhooks (
  webhook_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  status varchar(200) NOT NULL,
  name text NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
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
  download_log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  timestamp datetime NOT NULL,
  permission_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  user_ip_address VARCHAR(100) NULL DEFAULT '',
  PRIMARY KEY  (download_log_id),
  KEY permission_id (permission_id),
  KEY timestamp (timestamp)
) $collate;
CREATE TABLE {$wpdb->prefix}wc_product_meta_lookup (
  `product_id` bigint(20) NOT NULL,
  `sku` varchar(100) NULL default '',
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
  KEY min_max_price (`min_price`, `max_price`)
) $collate;
CREATE TABLE {$wpdb->prefix}wc_tax_rate_classes (
  tax_rate_class_id BIGINT UNSIGNED NOT NULL auto_increment,
  name varchar(200) NOT NULL DEFAULT '',
  slug varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY  (tax_rate_class_id),
  UNIQUE KEY slug (slug($max_index_length))
) $collate;
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
		);

		/**
		 * Filter the list of known WooCommerce tables.
		 *
		 * If WooCommerce plugins need to add new tables, they can inject them here.
		 *
		 * @param array $tables An array of WooCommerce-specific database table names.
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
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
			$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
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
	private static function get_core_capabilities() {
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
			$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
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
		// Bypass if filesystem is read-only and/or non-standard upload system is used.
		if ( apply_filters( 'woocommerce_install_skip_create_files', false ) ) {
			return;
		}

		// Install files and folders for uploading files and prevent hotlinking.
		$upload_dir      = wp_upload_dir();
		$download_method = get_option( 'woocommerce_file_download_method', 'force' );

		$files = array(
			array(
				'base'    => $upload_dir['basedir'] . '/woocommerce_uploads',
				'file'    => 'index.html',
				'content' => '',
			),
			array(
				'base'    => WC_LOG_DIR,
				'file'    => '.htaccess',
				'content' => 'deny from all',
			),
			array(
				'base'    => WC_LOG_DIR,
				'file'    => 'index.html',
				'content' => '',
			),
		);

		if ( 'redirect' !== $download_method ) {
			$files[] = array(
				'base'    => $upload_dir['basedir'] . '/woocommerce_uploads',
				'file'    => '.htaccess',
				'content' => 'deny from all',
			);
		}

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen
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
		$attach_id  = wp_insert_attachment( $attachment, $filename );

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
		if ( WC_PLUGIN_BASENAME === $file ) {
			$row_meta = array(
				'docs'    => '<a href="' . esc_url( apply_filters( 'woocommerce_docs_url', 'https://docs.woocommerce.com/documentation/plugins/woocommerce/' ) ) . '" aria-label="' . esc_attr__( 'View WooCommerce documentation', 'woocommerce' ) . '">' . esc_html__( 'Docs', 'woocommerce' ) . '</a>',
				'apidocs' => '<a href="' . esc_url( apply_filters( 'woocommerce_apidocs_url', 'https://docs.woocommerce.com/wc-apidocs/' ) ) . '" aria-label="' . esc_attr__( 'View WooCommerce API docs', 'woocommerce' ) . '">' . esc_html__( 'API docs', 'woocommerce' ) . '</a>',
				'support' => '<a href="' . esc_url( apply_filters( 'woocommerce_support_url', 'https://woocommerce.com/my-account/tickets/' ) ) . '" aria-label="' . esc_attr__( 'Visit premium customer support', 'woocommerce' ) . '">' . esc_html__( 'Premium support', 'woocommerce' ) . '</a>',
			);

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
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
}

WC_Install::init();
