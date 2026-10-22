<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WC_Admin_Menus;
use WC_Install;

/**
 * Decides whether the legacy WooCommerce > Reports menu item is shown, and hides it when it isn't.
 *
 * The page itself stays registered, so admin.php?page=wc-reports keeps working either way.
 *
 * @internal
 *
 * @since 11.3.0
 */
final class LegacyReportsMenu {

	/**
	 * Stores first installed on this version or later hide the menu by default.
	 */
	private const NEW_STORE_VERSION = '11.3.0-dev';

	/**
	 * The report groups and reports that WC_Admin_Reports::get_reports() returns without extensions.
	 */
	private const CORE_REPORTS = array(
		'orders'    => array( 'sales_by_date', 'sales_by_product', 'sales_by_category', 'coupon_usage', 'downloads' ),
		'customers' => array( 'customers', 'customer_list' ),
		'stock'     => array( 'low_in_stock', 'out_of_stock', 'most_stocked' ),
		'taxes'     => array( 'taxes_by_code', 'taxes_by_date' ),
	);

	/**
	 * The callback every core legacy report uses.
	 */
	private const CORE_CALLBACK = array( 'WC_Admin_Reports', 'get_report' );

	/**
	 * Hide the Reports menu item at the end of admin_menu.
	 *
	 * Runs on admin_menu rather than admin_head, since requests that only build the menu, such as the Calypso
	 * sidebar's /wpcom/v2/admin-menu endpoint, never reach admin_head.
	 *
	 * @internal
	 */
	public function handle_admin_menu(): void {
		if ( ! current_user_can( 'view_woocommerce_reports' ) || $this->should_show() ) {
			return;
		}

		global $menu, $submenu;

		if ( ! empty( $submenu['woocommerce'] ) && is_array( $submenu['woocommerce'] ) ) {
			$first_index = array_key_first( $submenu['woocommerce'] );
			foreach ( $submenu['woocommerce'] as $index => $item ) {
				// WordPress links the WooCommerce parent item to its first submenu entry, hidden or not.
				if ( 'wc-reports' === ( $item[2] ?? null ) && $index !== $first_index ) {
					$submenu['woocommerce'][ $index ][4] = self::add_hide_class( $item[4] ?? '' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				}
			}
		}

		if ( ! empty( $menu ) && is_array( $menu ) ) {
			foreach ( $menu as $index => $item ) {
				if ( 'wc-reports' === ( $item[2] ?? null ) ) {
					$menu[ $index ][4] = self::add_hide_class( $item[4] ?? '' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				}
			}
		}
	}

	/**
	 * Whether the legacy Reports menu item should be shown.
	 *
	 * @return bool
	 */
	private function should_show(): bool {
		$show = ! $this->is_new_store()
			|| ! FeaturesUtil::feature_is_enabled( 'analytics' )
			|| $this->has_extension_reports();

		/**
		 * Filters whether to show the legacy WooCommerce > Reports menu item.
		 *
		 * The page stays reachable at admin.php?page=wc-reports either way. By default the item is hidden on
		 * stores first installed on WooCommerce 11.3.0 or later, unless Analytics is disabled or an extension
		 * adds legacy reports. Runs at the end of admin_menu, so add callbacks before then.
		 *
		 * @since 11.3.0
		 *
		 * @param bool $show Whether to show the menu item.
		 */
		$filtered = apply_filters( 'woocommerce_show_legacy_reports_menu', $show );

		$filtered = is_scalar( $filtered ) ? filter_var( $filtered, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) : null;

		return $filtered ?? $show;
	}

	/**
	 * Whether the store was first installed on the version that hides the menu, or later.
	 *
	 * Until admin_init records the initial version on a fresh install, the current version is used. A missing
	 * or malformed initial version (e.g. stores installed before 9.2.0) counts as an existing store.
	 *
	 * @return bool
	 */
	private function is_new_store(): bool {
		$initial_version = get_option( WC_Install::INITIAL_INSTALLED_VERSION );

		if ( false === $initial_version && 'yes' === get_option( WC_Install::NEWLY_INSTALLED_OPTION ) ) {
			$initial_version = WC()->version;
		}

		if ( ! is_string( $initial_version ) || ! preg_match( '/^\d+\.\d+\.\d+(-(dev|alpha|beta|rc)(\.?\d+)?)?$/i', $initial_version ) ) {
			return false;
		}

		return version_compare( $initial_version, self::NEW_STORE_VERSION, '>=' );
	}

	/**
	 * Whether an extension adds to or changes the legacy reports.
	 *
	 * Analytics also applies woocommerce_admin_reports, so hooking it isn't enough on its own: the filtered
	 * legacy reports must contain a report core doesn't define, or a core report with a different callback.
	 *
	 * @return bool
	 */
	private function has_extension_reports(): bool {
		if ( has_action( 'wc_reports_tabs' ) || has_filter( 'wc_admin_reports_path' ) ) {
			return true;
		}

		if ( ! has_filter( 'woocommerce_admin_reports' ) && ! has_filter( 'woocommerce_reports_charts' ) ) {
			return false;
		}

		if ( ! class_exists( 'WC_Admin_Reports', false ) ) {
			return false;
		}

		foreach ( \WC_Admin_Reports::get_reports() as $group_key => $group ) {
			if ( ! is_array( $group ) || ! is_array( $group['reports'] ?? null ) ) {
				continue;
			}

			foreach ( $group['reports'] as $report_key => $report ) {
				if ( ! in_array( $report_key, self::CORE_REPORTS[ $group_key ] ?? array(), true ) ) {
					return true;
				}

				if ( ! is_array( $report ) || self::CORE_CALLBACK !== ( $report['callback'] ?? null ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Append the class WordPress uses to hide admin elements when JavaScript is enabled.
	 *
	 * @param mixed $classes Existing menu item classes.
	 * @return string
	 */
	private static function add_hide_class( $classes ): string {
		$classes = is_string( $classes ) ? trim( $classes ) : '';

		return '' === $classes ? WC_Admin_Menus::HIDE_CSS_CLASS : $classes . ' ' . WC_Admin_Menus::HIDE_CSS_CLASS;
	}
}
