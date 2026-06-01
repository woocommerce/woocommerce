<?php
/**
 * SavePublishClarity prototype improvement class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Prototype\Improvements;

use Automattic\WooCommerce\Internal\Prototype\DevPanel;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a fixed action bar (Back to products, Preview, Save draft, Update/Publish, kebab menu)
 * below the WordPress and WooCommerce admin headers. Strips the Publish metabox down to
 * status and visibility fields only.
 */
class SavePublishClarity {

	const FLAG_KEY   = 'save_publish_clarity';
	const HEADER_H   = 46; // px — height of our action bar.
	const ADMINBAR_H = 32; // px — standard WP admin bar.
	const WC_HDR_H   = 33; // px — .woocommerce-layout__header.

	/**
	 * Register hooks. No-ops if the dev panel flag is off.
	 *
	 * @internal
	 */
	final public static function init(): void {
		if ( ! DevPanel::is_flag_enabled( self::FLAG_KEY ) ) {
			return;
		}
		add_action( 'admin_head', array( self::class, 'output_styles' ) );
		add_action( 'admin_footer', array( self::class, 'output_header_html' ) );
	}

	/**
	 * Output inline CSS for the action bar and the publish metabox cleanup.
	 */
	public static function output_styles(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		// CSS implemented in Task 3.
	}

	/**
	 * Output the action bar HTML and inline JS.
	 */
	public static function output_header_html(): void {
		if ( ! DevPanel::is_supported_screen() ) {
			return;
		}
		// HTML/JS implemented in Task 4.
	}
}
