<?php
/**
 * TexturizeExclusions class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Stops wptexturize() changing customer details, such as emails like `o'neil--shop@48x17.com`.
 *
 * @internal Just for internal use.
 *
 * @since 11.4.0
 */
class TexturizeExclusions implements RegisterHooksInterface {

	/**
	 * Register hooks and filters.
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'skip_address_texturize_on_order_pages' ) );
	}

	/**
	 * Add `address` to the tags wptexturize() skips on the order received and view order pages.
	 *
	 * Block themes texturize the whole template HTML, including the customer details.
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function skip_address_texturize_on_order_pages(): void {
		if ( wp_is_block_theme() && ( is_order_received_page() || is_view_order_page() ) ) {
			add_filter( 'no_texturize_tags', array( $this, 'add_address_tag' ) );
		}
	}

	/**
	 * Add `address` to the tags wptexturize() skips.
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 *
	 * @param mixed $tags HTML element names.
	 * @return mixed
	 */
	public function add_address_tag( $tags ) {
		if ( ! is_array( $tags ) || in_array( 'address', $tags, true ) ) {
			return $tags;
		}

		$tags[] = 'address';
		return $tags;
	}
}
