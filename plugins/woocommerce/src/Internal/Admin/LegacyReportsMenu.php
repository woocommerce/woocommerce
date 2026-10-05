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
	 * Which menus this class hid the Reports item in, keyed by 'menu' or 'submenu'.
	 *
	 * @var array<string, true>
	 */
	private array $hidden_in = array();

	/**
	 * Hide the Reports menu item at the end of admin_menu.
	 *
	 * Requests that only build the menu, such as the Calypso sidebar's /wpcom/v2/admin-menu endpoint, never
	 * reach admin_head.
	 *
	 * @internal
	 */
	public function handle_admin_menu(): void {
		$this->update_menu_items();
	}

	/**
	 * Decide again right before the admin menu is printed.
	 *
	 * By then extensions have had every chance to register their report filters, so this can show an item
	 * that the admin_menu pass hid.
	 *
	 * @internal
	 */
	public function handle_admin_head(): void {
		$this->update_menu_items();
	}

	/**
	 * Hide or restore the Reports menu items according to should_show().
	 */
	private function update_menu_items(): void {
		if ( ! current_user_can( 'view_woocommerce_reports' ) ) {
			return;
		}

		if ( $this->should_show() ) {
			$this->restore_menu_items();
		} else {
			$this->hide_menu_items();
		}
	}

	/**
	 * Add the hide class to the Reports entries in $submenu['woocommerce'] and $menu.
	 */
	private function hide_menu_items(): void {
		global $menu, $submenu;

		if ( ! empty( $submenu['woocommerce'] ) && is_array( $submenu['woocommerce'] ) ) {
			$first_index = array_key_first( $submenu['woocommerce'] );
			foreach ( $submenu['woocommerce'] as $index => $item ) {
				// WordPress links the WooCommerce parent item to its first submenu entry, hidden or not.
				if ( 'wc-reports' === ( $item[2] ?? null ) && $index !== $first_index && ! self::has_hide_class( $item[4] ?? '' ) ) {
					$this->hidden_in['submenu']          = true;
					$submenu['woocommerce'][ $index ][4] = self::add_hide_class( $item[4] ?? '' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				}
			}
		}

		if ( ! empty( $menu ) && is_array( $menu ) ) {
			foreach ( $menu as $index => $item ) {
				if ( 'wc-reports' === ( $item[2] ?? null ) && ! self::has_hide_class( $item[4] ?? '' ) ) {
					$this->hidden_in['menu'] = true;
					$menu[ $index ][4]       = self::add_hide_class( $item[4] ?? '' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				}
			}
		}
	}

	/**
	 * Remove the hide class this class added to the Reports entries.
	 *
	 * Entries are found by slug again, since WordPress can reorder $menu and add classes after admin_menu.
	 */
	private function restore_menu_items(): void {
		global $menu, $submenu;

		if ( isset( $this->hidden_in['submenu'] ) && ! empty( $submenu['woocommerce'] ) && is_array( $submenu['woocommerce'] ) ) {
			foreach ( $submenu['woocommerce'] as $index => $item ) {
				if ( 'wc-reports' === ( $item[2] ?? null ) ) {
					$submenu['woocommerce'][ $index ][4] = self::remove_hide_class( $item[4] ?? '' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				}
			}
		}

		if ( isset( $this->hidden_in['menu'] ) && ! empty( $menu ) && is_array( $menu ) ) {
			foreach ( $menu as $index => $item ) {
				if ( 'wc-reports' === ( $item[2] ?? null ) ) {
					$menu[ $index ][4] = self::remove_hide_class( $item[4] ?? '' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				}
			}
		}

		$this->hidden_in = array();
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
		 * adds legacy reports.
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
	 * Whether the classes already include the class WordPress uses to hide admin elements.
	 *
	 * @param mixed $classes Menu item classes.
	 * @return bool
	 */
	private static function has_hide_class( $classes ): bool {
		return is_string( $classes ) && in_array( WC_Admin_Menus::HIDE_CSS_CLASS, explode( ' ', $classes ), true );
	}

	/**
	 * Remove the last hide class, which is the one add_hide_class() appended.
	 *
	 * @param mixed $classes Menu item classes.
	 * @return string
	 */
	private static function remove_hide_class( $classes ): string {
		$classes = is_string( $classes ) ? explode( ' ', $classes ) : array();
		$index   = array_search( WC_Admin_Menus::HIDE_CSS_CLASS, array_reverse( $classes, true ), true );

		if ( false !== $index ) {
			unset( $classes[ $index ] );
		}

		return implode( ' ', $classes );
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
