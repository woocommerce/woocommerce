<?php
/**
 * Installation related functions and actions.
 */

namespace Automattic\WooCommerce\Internal\Admin;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\Cache;
use Automattic\WooCommerce\Admin\Notes\Notes;
/**
 * Install Class.
 */
class Install {
	/**
	 * Plugin version option name.
	 */
	const VERSION_OPTION = 'woocommerce_admin_version';

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
	 * Get list of DB update callbacks.
	 *
	 * @return array
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/**
	 * Schedule cron events.
	 */
	public static function create_cron_jobs() {
		if ( ! wp_next_scheduled( 'wc_admin_daily' ) ) {
			wp_schedule_event( time(), 'daily', 'wc_admin_daily' );
		}
		// Note: this is potentially redundant when the core package exists.
		wp_schedule_single_event( time() + 10, 'generate_category_lookup_table' );
	}

	/**
	 * Delete obsolete notes.
	 */
	public static function delete_obsolete_notes() {
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
}
