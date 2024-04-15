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
		add_filter( 'template_include', array( $this, 'handle_template_include' ) );
	}

	/**
	 * Replaces the template with a coming soon screen if needed.
	 *
	 * @internal
	 *
	 * @param string $template Current template.
	 */
	public function handle_template_include( $template ) {
		// Early exit if LYS feature is disabled.
		if ( ! Features::is_enabled( 'launch-your-store' ) ) {
			return $template;
		}

		if ( is_404() ) {
			return $template;
		}

		// Early exit if the user is logged in as administrator / shop manager.
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return $template;
		}

		// Early exit if the URL doesn't need a coming soon screen.
		global $wp;
		$url = $this->coming_soon_helper->get_url_from_wp( $wp );

		if ( ! $this->coming_soon_helper->is_url_coming_soon( $url ) ) {
			return $template;
		}

		$coming_soon_template = get_query_template( 'coming-soon' );
		if ( $coming_soon_template ) {
			// A coming soon page needs to be displayed. Don't cache this response.
			nocache_headers();
			return $coming_soon_template;
		};

		return $template;
	}
}
