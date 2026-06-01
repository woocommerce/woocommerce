<?php
/**
 * MaxWidth prototype improvement class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Prototype\Improvements;

use Automattic\WooCommerce\Internal\Prototype\DevPanel;

defined( 'ABSPATH' ) || exit;

/**
 * Caps the classic product editor at 1200 px with horizontal centering.
 * Mirrors the approach used for the order detail page in OrderDetailRedesign\Init.
 * Activated via the 'max_width' dev panel flag.
 */
class MaxWidth {

	/**
	 * Register hooks. No-ops if the dev panel flag is off.
	 *
	 * @internal
	 */
	final public static function init(): void {
		if ( ! DevPanel::is_flag_enabled( 'max_width' ) ) {
			return;
		}
		add_action( 'admin_head', array( self::class, 'inject_css' ) );
	}

	/**
	 * Inject the max-width CSS on the product edit screen.
	 */
	public static function inject_css(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base || 'product' !== $screen->post_type ) {
			return;
		}
		echo '<style id="wc-proto-max-width">.post-type-product .wrap:has(#poststuff){max-width:1200px;margin-inline:auto;}</style>';
	}
}
