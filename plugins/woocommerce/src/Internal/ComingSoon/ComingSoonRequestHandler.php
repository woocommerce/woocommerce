<?php
namespace Automattic\WooCommerce\Internal\ComingSoon;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * Handles the parse_request hook to determine whether the current page needs
 * to be replaced with a comiing soon screen.
 */
class ComingSoonRequestHandler {

	/**
	 * Coming soon helper.
	 *
	 * @var ComingSoonHelper
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
	 * @param \WP $wp Current WordPress environment instance (passed by reference).
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
		$url = $this->coming_soon_helper->get_url_from_wp( $wp );

		if ( ! $this->coming_soon_helper->is_url_coming_soon( $url ) ) {
			return $wp;
		}

		// Exclude users with a private link.
		if ( isset( $_GET['woo-share'] ) && get_option( 'woocommerce_share_key' ) === $_GET['woo-share'] ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// Persist the share link with a cookie for 90 days.
			setcookie( 'woo-share', sanitize_text_field( wp_unslash( $_GET['woo-share'] ) ), time() + 60 * 60 * 24 * 90, '/' ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $wp;
		}
		if ( isset( $_COOKIE['woo-share'] ) && get_option( 'woocommerce_share_key' ) === $_COOKIE['woo-share'] ) {
			return $wp;
		}

		// A coming soon page needs to be displayed. Don't cache this response.
		nocache_headers();

		$template = get_query_template( 'coming-soon' );
		include $template;

		die();
	}
}
