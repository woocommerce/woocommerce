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
