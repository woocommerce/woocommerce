<?php
/**
 * Installation related functions and actions.
 */

namespace Automattic\WooCommerce\Admin;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\Cache;
use \Automattic\WooCommerce\Admin\Notes\Notes;

/**
 * Install Class.
 */
class Install {
	/**
	 * Plugin version option name.
	 */
	const VERSION_OPTION = 'woocommerce_admin_version';

	/**
	 * DB updates and callbacks that need to be run per version.
	 *
	 * @var array
	 */
	protected static $db_updates = array(
		'0.20.1' => array(
			'wc_admin_update_0201_order_status_index',
			'wc_admin_update_0201_db_version',
		),
		'0.23.0' => array(
			'wc_admin_update_0230_rename_gross_total',
			'wc_admin_update_0230_db_version',
		),
		'0.25.1' => array(
			'wc_admin_update_0251_remove_unsnooze_action',
			'wc_admin_update_0251_db_version',
		),
		'1.1.0'  => array(
			'wc_admin_update_110_remove_facebook_note',
			'wc_admin_update_110_db_version',
		),
		'1.3.0'  => array(
			'wc_admin_update_130_remove_dismiss_action_from_tracking_opt_in_note',
			'wc_admin_update_130_db_version',
		),
		'1.4.0'  => array(
			'wc_admin_update_140_change_deactivate_plugin_note_type',
			'wc_admin_update_140_db_version',
		),
		'1.6.0'  => array(
			'wc_admin_update_160_remove_facebook_note',
			'wc_admin_update_160_db_version',
		),
		'1.7.0'  => array(
			'wc_admin_update_170_homescreen_layout',
			'wc_admin_update_170_db_version',
		),
		'2.7.0'  => array(
			'wc_admin_update_270_delete_report_downloads',
			'wc_admin_update_270_db_version',
		),
		'2.7.1'  => array(
			'wc_admin_update_271_update_task_list_options',
			'wc_admin_update_271_db_version',
		),
		'2.8.0'  => array(
			'wc_admin_update_280_order_status',
			'wc_admin_update_280_db_version',
		),
		'2.9.0'  => array(
			'wc_admin_update_290_update_apperance_task_option',
			'wc_admin_update_290_delete_default_homepage_layout_option',
			'wc_admin_update_290_db_version',
		),
		'3.0.0'  => array(
			'wc_admin_update_300_update_is_read_from_last_read',
			'wc_admin_update_300_db_version',
		),
	);

	/**
	 * Migrated option names mapping. New => old.
	 *
	 * @var array
	 */
	protected static $migrated_options = array(
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

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
		add_filter( 'wpmu_drop_tables', array( __CLASS__, 'wpmu_drop_tables' ) );

		// Add wc-admin report tables to list of WooCommerce tables.
		add_filter( 'woocommerce_install_get_tables', array( __CLASS__, 'add_tables' ) );
	}

	/**
	 * Migrate option values to their new keys/names.
	 */
	public static function migrate_options() {
		wc_maybe_define_constant( 'WC_ADMIN_MIGRATING_OPTIONS', true );

		foreach ( self::$migrated_options as $new_option => $old_option ) {
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
	 * Check WC Admin version and run the updater is required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if ( defined( 'IFRAME_REQUEST' ) ) {
			return;
		}

		$version_option  = get_option( self::VERSION_OPTION );
		$requires_update = version_compare( get_option( self::VERSION_OPTION ), WC_ADMIN_VERSION_NUMBER, '<' );

		/*
		 * When included as part of Core, no `on_activation` hook as been called
		 * so there is no version in options. Make sure install gets called in this
		 * case as well as a regular version update
		 */
		if ( ! $version_option || $requires_update ) {
			self::install();
			/**
			 * WooCommerce Admin has been installed or updated.
			 */
			do_action( 'woocommerce_admin_updated' );

			if ( ! $version_option ) {
				/**
				 * WooCommerce Admin has been installed.
				 */
				do_action( 'woocommerce_admin_newly_installed' );
			}

			if ( $requires_update ) {
				/**
				 * An existing installation of WooCommerce Admin has been
				 * updated.
				 */
				do_action( 'woocommerce_admin_updated_existing' );
			}
		}

		/*
		 * Add the version option if none is found, as would be the case when
		 * initialized via Core for the first time.
		 */
		if ( ! $version_option ) {
			add_option( self::VERSION_OPTION, WC_ADMIN_VERSION_NUMBER );
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
		if ( self::is_installing() ) {
			return;
		}

		// If we made it till here nothing is running yet, lets set the transient now.
		set_transient( 'wc_admin_installing', 'yes', MINUTE_IN_SECONDS * 10 );

		self::migrate_options();
		self::create_tables();
		self::create_events();
		self::delete_obsolete_notes();
		self::maybe_update_db_version();

		delete_transient( 'wc_admin_installing' );

		// Use add_option() here to avoid overwriting this value with each
		// plugin version update. We base plugin age off of this value.
		add_option( 'woocommerce_admin_install_timestamp', time() );
		do_action( 'woocommerce_admin_installed' );
	}

	/**
	 * Check if the installer is installing.
	 *
	 * @return bool
	 */
	public static function is_installing() {
		return 'yes' === get_transient( 'wc_admin_installing' );
	}

	/**
	 * Get database schema.
	 *
	 * @return string
	 */
	protected static function get_schema() {
		global $wpdb;

		$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		// Max DB index length. See wp_get_db_schema().
		$max_index_length = 191;

		$tables = "
		CREATE TABLE {$wpdb->prefix}wc_order_stats (
			order_id bigint(20) unsigned NOT NULL,
			parent_id bigint(20) unsigned DEFAULT 0 NOT NULL,
			date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			date_created_gmt datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			num_items_sold int(11) DEFAULT 0 NOT NULL,
			total_sales double DEFAULT 0 NOT NULL,
			tax_total double DEFAULT 0 NOT NULL,
			shipping_total double DEFAULT 0 NOT NULL,
			net_total double DEFAULT 0 NOT NULL,
			returning_customer boolean DEFAULT NULL,
			status varchar(200) NOT NULL,
			customer_id BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY (order_id),
			KEY date_created (date_created),
			KEY customer_id (customer_id),
			KEY status (status({$max_index_length}))
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
			coupon_id BIGINT NOT NULL,
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
			content_data longtext NULL default null,
			status varchar(200) NOT NULL,
			source varchar(200) NOT NULL,
			date_created datetime NOT NULL default '0000-00-00 00:00:00',
			date_reminder datetime NULL default null,
			is_snoozable boolean DEFAULT 0 NOT NULL,
			layout varchar(20) DEFAULT '' NOT NULL,
			image varchar(200) NULL DEFAULT NULL,
			is_deleted boolean DEFAULT 0 NOT NULL,
			is_read boolean DEFAULT 0 NOT NULL,
			icon varchar(200) NOT NULL default 'info',
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
			actioned_text varchar(255) NOT NULL,
			nonce_action varchar(255) NULL DEFAULT NULL,
			nonce_name varchar(255) NULL DEFAULT NULL,
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
			state varchar(100) DEFAULT '' NOT NULL,
			PRIMARY KEY (customer_id),
			UNIQUE KEY user_id (user_id),
			KEY email (email)
		) $collate;
		CREATE TABLE {$wpdb->prefix}wc_category_lookup (
			category_tree_id BIGINT UNSIGNED NOT NULL,
			category_id BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY (category_tree_id,category_id)
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
			"{$wpdb->prefix}wc_category_lookup",
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
	 * Get list of DB update callbacks.
	 *
	 * @return array
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/**
	 * Is a DB update needed?
	 *
	 * @return boolean
	 */
	public static function needs_db_update() {
		$current_db_version = get_option( self::VERSION_OPTION, null );
		$updates            = self::get_db_update_callbacks();
		$update_versions    = array_keys( $updates );
		usort( $update_versions, 'version_compare' );

		return ! is_null( $current_db_version ) && version_compare( $current_db_version, end( $update_versions ), '<' );
	}

	/**
	 * See if we need to show or run database updates during install.
	 */
	private static function maybe_update_db_version() {
		if ( self::needs_db_update() ) {
			self::update();
		} else {
			self::update_db_version();
		}
	}

	/**
	 * Push all needed DB updates to the queue for processing.
	 */
	private static function update() {
		$current_db_version = get_option( self::VERSION_OPTION );
		$loop               = 0;

		foreach ( self::get_db_update_callbacks() as $version => $update_callbacks ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				$completed_version_updates = 0;

				foreach ( $update_callbacks as $update_callback ) {
					$pending_jobs = WC()->queue()->search(
						array(
							'per_page' => 1,
							'hook'     => 'woocommerce_run_update_callback',
							'search'   => wp_json_encode( array( $update_callback ) ),
							'group'    => 'woocommerce-db-updates',
							'status'   => 'pending',
						)
					);

					$complete_jobs = WC()->queue()->search(
						array(
							'per_page' => 1,
							'hook'     => 'woocommerce_run_update_callback',
							'search'   => wp_json_encode( array( $update_callback ) ),
							'group'    => 'woocommerce-db-updates',
							'status'   => 'complete',
						)
					);

					$completed_version_updates += count( $complete_jobs );

					if ( empty( $pending_jobs ) && empty( $complete_jobs ) ) {
						WC()->queue()->schedule_single(
							time() + $loop,
							'woocommerce_run_update_callback',
							array( $update_callback ),
							'woocommerce-db-updates'
						);
						Cache::invalidate();
					}

					$loop++;

				}

				// Users have experienced concurrency issues where all update callbacks
				// have run but the version option hasn't been updated. If all the updates
				// for a version are complete, update the version option to reflect that.
				// See: https:// github.com/woocommerce/woocommerce-admin/issues/5058.
				if ( count( $update_callbacks ) === $completed_version_updates ) {
					self::update_db_version( $version );
				}
			}
		}
	}

	/**
	 * Update WC Admin version to current.
	 *
	 * @param string|null $version New WooCommerce Admin DB version or null.
	 */
	public static function update_db_version( $version = null ) {
		delete_option( self::VERSION_OPTION );
		add_option( self::VERSION_OPTION, is_null( $version ) ? WC_ADMIN_VERSION_NUMBER : $version );
	}

	/**
	 * Schedule cron events.
	 */
	public static function create_events() {
		if ( ! wp_next_scheduled( 'wc_admin_daily' ) ) {
			wp_schedule_event( time(), 'daily', 'wc_admin_daily' );
		}
		// Note: this is potentially redundant when the core package exists.
		wp_schedule_single_event( time() + 10, 'generate_category_lookup_table' );
	}

	/**
	 * Delete obsolete notes.
	 */
	protected static function delete_obsolete_notes() {
		$obsolete_notes_names = array(
			'wc-admin-welcome-note',
			'wc-admin-store-notice-setting-moved',
			'wc-admin-store-notice-giving-feedback',
			'wc-admin-learn-more-about-product-settings',
			'wc-admin-onboarding-profiler-reminder',
			'wc-admin-historical-data',
			'wc-admin-review-shipping-settings',
			'wc-admin-home-screen-feedback',
			'wc-admin-effortless-payments-by-mollie',
			'wc-admin-google-ads-and-marketing',
			'wc-admin-marketing-intro',
			'wc-admin-draw-attention',
			'wc-admin-need-some-inspiration',
			'wc-admin-choose-niche',
			'wc-admin-start-dropshipping-business',
			'wc-admin-filter-by-product-variations-in-reports',
			'wc-admin-learn-more-about-variable-products',
			'wc-admin-getting-started-ecommerce-webinar',
			'wc-admin-navigation-feedback',
			'wc-admin-navigation-feedback-follow-up',
		);

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

		Notes::delete_notes_with_name( $obsolete_notes_names );
	}

	/**
	 * Drop WooCommerce Admin tables.
	 *
	 * @return void
	 */
	public static function drop_tables() {
		global $wpdb;

		$tables = self::get_tables();

		foreach ( $tables as $table ) {
			/* phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared */
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
			/* phpcs:enable */
		}
	}
}
