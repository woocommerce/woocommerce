<?php
/**
 * WooCommerce Order Detail Redesign feature loader.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\Features\OrderDetailRedesign;

/**
 * Loads support for the redesigned WooCommerce order detail page.
 *
 * Auto-instantiated by `Features::load_features()` when the
 * `order-detail-redesign` feature flag is enabled (see
 * `client/admin/config/core.json`).
 *
 * @since 10.9.0
 */
class Init {

	const FEATURE_ID = 'order-detail-redesign';

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		add_filter( 'admin_body_class', array( $this, 'handle_admin_body_class' ) );
	}

	/**
	 * Adds the feature body class on the order edit/new screens.
	 *
	 * The framework's auto-added `woocommerce-feature-enabled-*` body class
	 * (in `Features::add_admin_body_classes()`) only fires on wc-admin and
	 * embedded pages; the HPOS order edit screen and the legacy `shop_order`
	 * CPT edit screen are neither, so the class is added here for those
	 * screens.
	 *
	 * @internal
	 *
	 * @param string $classes Existing space-separated body classes.
	 * @return string
	 */
	public function handle_admin_body_class( string $classes ): string {
		if ( ! self::is_order_edit_screen() ) {
			return $classes;
		}

		return $classes . ' woocommerce-feature-enabled-' . self::FEATURE_ID;
	}

	/**
	 * Returns true when the order detail redesign is active on the current screen.
	 *
	 * Use this from rendering code paths to decide whether to emit the
	 * redesigned UI. Combines a screen check with the feature flag check so
	 * call sites do not need both.
	 *
	 * @internal
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return self::is_order_edit_screen()
			&& \Automattic\WooCommerce\Admin\Features\Features::is_enabled( self::FEATURE_ID );
	}

	/**
	 * Returns true on the HPOS order edit/new screen or the legacy `shop_order` CPT edit/new screen.
	 *
	 * @internal
	 *
	 * @return bool
	 */
	public static function is_order_edit_screen(): bool {
		// HPOS edit/new: admin.php?page=wc-orders&action=edit|new.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && 'wc-orders' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
			if ( in_array( $action, array( 'edit', 'new' ), true ) ) {
				return true;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Legacy CPT shop_order edit/new screens.
		$screen = get_current_screen();
		if ( $screen && 'shop_order' === $screen->id && 'post' === $screen->base ) {
			return true;
		}

		return false;
	}
}
