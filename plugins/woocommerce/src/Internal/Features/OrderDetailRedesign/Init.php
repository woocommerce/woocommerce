<?php
/**
 * WooCommerce Order Detail Redesign feature loader.
 *
 * @package WooCommerce
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Features\OrderDetailRedesign;

/**
 * Loads support for the redesigned WooCommerce order detail page.
 *
 * Manually instantiated from `Features::load_features()` when the
 * `order-detail-redesign` feature flag is enabled (see
 * `client/admin/config/core.json`). Lives in the `Internal` namespace
 * because the feature class is not part of the public API surface.
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
	 * Adds the feature body class to every admin page while the feature is enabled.
	 *
	 * Screen-level scoping is handled by the CSS selectors, which chain this
	 * class with the WP-provided page classes (`.woocommerce_page_wc-orders`,
	 * `.post-type-shop_order`) and qualifiers like `:has(#poststuff)` so the
	 * styles only apply on the actual order edit/new screens.
	 *
	 * @internal
	 *
	 * @param string $classes Existing space-separated body classes.
	 * @return string
	 */
	public function handle_admin_body_class( string $classes ): string {
		return $classes . ' woocommerce-feature-enabled-' . self::FEATURE_ID;
	}
}
