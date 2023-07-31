<?php
namespace Automattic\WooCommerce\Blocks\Templates;

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
		global $post;
		return $post instanceof \WP_Post && get_option( 'woocommerce_cart_page_endpoint' ) === $post->post_name;
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
