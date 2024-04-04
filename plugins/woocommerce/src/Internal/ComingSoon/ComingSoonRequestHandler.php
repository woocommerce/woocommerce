<?php
namespace Automattic\WooCommerce\Internal\ComingSoon;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * Handles the parse_request hook to determine whether the current page needs
 * to be replaced with a comiing soon screen.
 */
class ComingSoonRequestHandler {

	/**
	 * NHelper dependency.
	 *
	 * @var string
	 */
	private $coming_soon_helper = null;

	/**
	 * Sets up the hook.
	 *
	 * @internal
	 *
	 * @param ComingSoonHelper $coming_soon_helper Dependency.
	 */
	final public function init( ComingSoonHelper $coming_soon_helper ) {
		$this->coming_soon_helper = $coming_soon_helper;
		add_action( 'parse_request', array( $this, 'handle_parse_request' ) );
	}

	/**
	 * Parses the current request and sets the page ID to the coming soon page if it
	 * needs to be shown in place of the normal page.
	 *
	 * @internal
	 *
	 * @param \WP $wp Current WordPress environment t instance (passed by reference).
	 */
	public function handle_parse_request( \WP &$wp ) {
		// Early exit if LYS feature is disabled.
		if ( ! Features::is_enabled( 'launch-your-store' ) ) {
			return $wp;
		}

		// Early exit if the user is logged in as administrator / shop manager.
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return $wp;
		}

		// Early exit if the URL doesn't need a coming soon screen.
		$url = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
		if ( ! $this->coming_soon_helper->is_url_coming_soon( $url ) ) {
			return $wp;
		}

		// Replace the query page_id with the coming soon page.
		nocache_headers();
		$coming_soon_page_id = get_option( 'woocommerce_coming_soon_page_id' ) ?? null;
		if ( isset( $coming_soon_page_id ) ) {
			$wp->query_vars['page_id'] = $coming_soon_page_id;
		} else { // phpcs:ignore -- TODO: render a default template.
		}

		return $wp;
	}
}
