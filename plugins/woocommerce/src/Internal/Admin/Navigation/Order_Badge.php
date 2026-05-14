<?php

declare( strict_types = 1 );

/**
 * Order-count badge for navigation_v2.
 *
 * @package WooCommerce\Internal\Admin\Navigation
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Appends a notification bubble carrying the count of orders that need
 * attention (currently: `processing` status) to the Orders menu entry and
 * the WooCommerce top-level entry.
 *
 * Counts above 99 display as "99+" to keep the bubble narrow.
 */
class Order_Badge {

	/**
	 * Cap for the visible number. Counts above this threshold render as
	 * "99+" instead of the raw number.
	 */
	private const MAX_DISPLAY = 99;

	/**
	 * Register hooks.
	 *
	 * `admin_menu` priority `PHP_INT_MAX` fires after Menu_Reconciler has
	 * rebuilt `$submenu['woocommerce']` and the splicer has inserted the
	 * Woo rail roots. Bootstrap instantiates this class after
	 * Menu_Reconciler, so within the same priority bucket WordPress runs
	 * our callback last.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'apply' ), PHP_INT_MAX );
	}

	/**
	 * Compute the processing-order count and stamp a `menu-counter` bubble
	 * on every menu entry that should carry it: the WooCommerce top-level,
	 * the spliced Orders rail-root (when nav-v2 is active on a Woo page),
	 * and the Orders entry inside the WooCommerce flyout.
	 */
	public function apply(): void {
		if ( ! current_user_can( 'edit_others_shop_orders' ) ) {
			return;
		}
		if ( ! function_exists( 'wc_processing_order_count' ) ) {
			return;
		}
		/**
		 * Filter the number used for the order-attention bubble. Kept for
		 * parity with the legacy `WC_Admin_Menus::menu_order_count()` hook
		 * so existing customizers keep working.
		 *
		 * @since 11.1.0
		 *
		 * @param int $count Processing-order count.
		 */
		$count = (int) apply_filters( 'woocommerce_menu_order_count', wc_processing_order_count() );
		if ( $count < 1 ) {
			return;
		}

		$badge = $this->build_badge_html( $count );

		// Top-level entries: the WooCommerce item (visible in the WP rail
		// on non-Woo pages; `hide-if-js` on Woo pages) and the spliced
		// Orders rail-root (visible in the Woo rail).
		$this->append_to_menu_entry( 'woocommerce', $badge );
		$this->append_to_menu_entry( 'wc-orders', $badge );

		// Orders item in the WooCommerce flyout. `Menu_Reconciler::replace_woocommerce_submenu`
		// overwrites `$entry[0]` with the tree node's clean title, which
		// strips the badge that the legacy `menu_order_count()` callback
		// adds at admin_menu priority 10 — re-apply here.
		$this->append_to_submenu_entry( 'woocommerce', 'wc-orders', $badge );
	}

	/**
	 * Build the badge span. Matches WordPress's `menu-counter` convention
	 * so the standard admin-menu CSS styles it as a red bubble without
	 * extra styling.
	 *
	 * @param int $count Raw count.
	 * @return string Badge HTML.
	 */
	private function build_badge_html( int $count ): string {
		$display = $count > self::MAX_DISPLAY
			? self::MAX_DISPLAY . '+'
			: number_format_i18n( $count );

		return sprintf(
			' <span class="menu-counter count-%1$s"><span class="processing-count">%2$s</span></span>',
			esc_attr( (string) $count ),
			esc_html( $display )
		);
	}

	/**
	 * Append the badge HTML to the title of the `$menu` entry that
	 * matches `$slug`. Idempotent: skips entries that already carry a
	 * `menu-counter` span (avoids stacking badges when the legacy
	 * callback has already run).
	 *
	 * @param string $slug  Slug to look up (`$entry[2]`).
	 * @param string $badge Badge HTML to append.
	 */
	private function append_to_menu_entry( string $slug, string $badge ): void {
		global $menu;
		if ( ! is_array( $menu ) ) {
			return;
		}
		foreach ( $menu as $key => $entry ) {
			if ( ! isset( $entry[2] ) || $entry[2] !== $slug ) {
				continue;
			}
			$title = (string) ( $entry[0] ?? '' );
			if ( false !== strpos( $title, 'menu-counter' ) ) {
				continue;
			}
			$menu[ $key ][0] = $title . $badge;
		}
	}

	/**
	 * Like `append_to_menu_entry()` but for a child entry inside
	 * `$submenu[$parent]`.
	 *
	 * @param string $parent Parent slug.
	 * @param string $slug   Child slug to match (`$entry[2]`).
	 * @param string $badge  Badge HTML to append.
	 */
	private function append_to_submenu_entry( string $parent, string $slug, string $badge ): void {
		global $submenu;
		if ( ! isset( $submenu[ $parent ] ) || ! is_array( $submenu[ $parent ] ) ) {
			return;
		}
		foreach ( $submenu[ $parent ] as $key => $entry ) {
			if ( ! isset( $entry[2] ) || $entry[2] !== $slug ) {
				continue;
			}
			$title = (string) ( $entry[0] ?? '' );
			if ( false !== strpos( $title, 'menu-counter' ) ) {
				continue;
			}
			$submenu[ $parent ][ $key ][0] = $title . $badge;
		}
	}
}
