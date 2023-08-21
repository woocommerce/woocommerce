<?php
namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\Utils\BlockTemplateMigrationUtils;

/**
 * CartTemplate class.
 *
 * @internal
 */
class CartTemplate extends AbstractPageTemplate {
	/**
	 * Template slug.
	 *
	 * @return string
	 */
	public static function get_slug() {
		return 'cart';
	}

	/**
	 * Returns the page object assigned to this template/page.
	 *
	 * @return \WP_Post|null Post object or null.
	 */
	public static function get_placeholder_page() {
		$page_id = wc_get_page_id( 'cart' );
		return $page_id ? get_post( $page_id ) : null;
	}

	/**
	 * True when viewing the cart page or cart endpoint.
	 *
	 * @return boolean
	 */
	protected function is_active_template() {

		if ( ! BlockTemplateMigrationUtils::has_migrated_page( 'cart' ) ) {
			return false;
		}

		global $post;
		$placeholder = $this->get_placeholder_page();
		return null !== $placeholder && $post instanceof \WP_Post && $placeholder->post_name === $post->post_name;
	}

	/**
	 * Should return the title of the page.
	 *
	 * @return string
	 */
	public static function get_template_title() {
		return __( 'Cart', 'woo-gutenberg-products-block' );
	}
}
