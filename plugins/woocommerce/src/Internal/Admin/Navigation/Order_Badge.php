<?php
/**
 * Order-count badge for navigation_v2.
 *
 * @package WooCommerce\Internal\Admin\Navigation
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.Classes.ValidClassName.NotCamelCaps -- Stamps badges onto the WP $menu/$submenu globals by design; underscore class name is intentional.

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
	 * Stamp the per-item badges, then — if any menu item under the Woo
	 * rail (ours or third-party) carries a `menu-counter` bubble —
	 * stamp a plain "attention" dot on the WooCommerce top-level entry.
	 *
	 * The dot lives on the top-level because a numeric counter there
	 * would run into WP's hover-flyout arrow at the right edge of the
	 * rail row, and because the actual number lives on whichever
	 * drill-down item triggered the attention.
	 *
	 * @internal
	 */
	public function apply(): void {
		$this->maybe_apply_orders_badge();

		if ( $this->any_woo_item_has_badge() ) {
			$this->append_to_menu_entry( 'woocommerce', $this->build_dot_html() );
		}
	}

	/**
	 * Apply the numeric processing-order bubble to the Orders rail-root
	 * and to the Orders entry inside the WooCommerce flyout.
	 */
	private function maybe_apply_orders_badge(): void {
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
		 * @param int $count Processing-order count.
		 *
		 * @since 10.9.0
		 */
		$count = (int) apply_filters( 'woocommerce_menu_order_count', wc_processing_order_count() );
		if ( $count < 1 ) {
			return;
		}

		$badge = $this->build_badge_html( $count );
		$this->append_to_menu_entry( 'wc-orders', $badge );

		// Orders item in the WooCommerce flyout. `Menu_Reconciler::replace_woocommerce_submenu`
		// overwrites `$entry[0]` with the tree node's clean title, which
		// strips the badge that the legacy `menu_order_count()` callback
		// adds at admin_menu priority 10 — re-apply here.
		$this->append_to_submenu_entry( 'woocommerce', 'wc-orders', $badge );
	}

	/**
	 * True when any menu entry that belongs to the WooCommerce rail
	 * (flyout child of `woocommerce` in `$submenu`, or a tree rail-root
	 * spliced into `$menu`) carries a `menu-counter` bubble.
	 *
	 * Looks for the bare `menu-counter` class so it picks up bubbles
	 * added by third-party plugins as well as our own
	 * `wc-order-attention menu-counter` markup.
	 */
	private function any_woo_item_has_badge(): bool {
		global $menu, $submenu;

		if ( isset( $submenu['woocommerce'] ) && is_array( $submenu['woocommerce'] ) ) {
			foreach ( $submenu['woocommerce'] as $entry ) {
				if ( false !== strpos( (string) ( $entry[0] ?? '' ), 'menu-counter' ) ) {
					return true;
				}
			}
		}

		$tree = Menu_Reconciler::get_tree();
		if ( null !== $tree && is_array( $menu ) ) {
			foreach ( $menu as $entry ) {
				$slug = $entry[2] ?? null;
				if ( ! is_string( $slug ) || 'woocommerce' === $slug ) {
					continue;
				}
				$node = $tree[ $slug ] ?? null;
				if ( null === $node || 'woocommerce' !== ( $node['parent'] ?? null ) ) {
					continue;
				}
				if ( false !== strpos( (string) ( $entry[0] ?? '' ), 'menu-counter' ) ) {
					return true;
				}
			}
		}

		return false;
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
			' <span class="wc-order-attention menu-counter count-%1$s"><span class="processing-count">%2$s</span></span>',
			esc_attr( (string) $count ),
			esc_html( $display )
		);
	}

	/**
	 * Build the dot-only variant. Used on the WooCommerce top-level entry,
	 * where a numeric counter would collide with WP's right-edge flyout
	 * arrow. Indicates "attention needed" without spending the width.
	 */
	private function build_dot_html(): string {
		// `menu-counter` piggybacks on WP's badge styling — the active
		// admin color scheme sets the background color on that class, so
		// the dot picks up whatever accent the scheme provides. Our own
		// `wc-attention-dot` class overrides the dimensions to render as
		// a small dot rather than a number pill.
		return ' <span class="wc-order-attention wc-attention-dot menu-counter" aria-hidden="true"></span>';
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
			if ( false !== strpos( $title, 'wc-order-attention' ) ) {
				continue;
			}
			$menu[ $key ][0] = $title . $badge;
		}
	}

	/**
	 * Like `append_to_menu_entry()` but for a child entry inside
	 * `$submenu[$parent_slug]`.
	 *
	 * @param string $parent_slug Parent slug.
	 * @param string $slug   Child slug to match (`$entry[2]`).
	 * @param string $badge  Badge HTML to append.
	 */
	private function append_to_submenu_entry( string $parent_slug, string $slug, string $badge ): void {
		global $submenu;
		if ( ! isset( $submenu[ $parent_slug ] ) || ! is_array( $submenu[ $parent_slug ] ) ) {
			return;
		}
		foreach ( $submenu[ $parent_slug ] as $key => $entry ) {
			if ( ! isset( $entry[2] ) || $entry[2] !== $slug ) {
				continue;
			}
			$title = (string) ( $entry[0] ?? '' );
			if ( false !== strpos( $title, 'wc-order-attention' ) ) {
				continue;
			}
			$submenu[ $parent_slug ][ $key ][0] = $title . $badge;
		}
	}
}
