<?php
namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * CheckoutTemplate class.
 *
 * @internal
 */
class CheckoutTemplate extends AbstractPageTemplate {
	/**
	 * Template slug.
	 *
	 * @return string
	 */
	public static function get_slug() {
		return 'checkout';
	}

	/**
	 * Returns the page object assigned to this template/page.
	 *
	 * @return \WP_Post|null Post object or null.
	 */
	public static function get_placeholder_page() {
		$page_id = wc_get_page_id( 'checkout' );
		return $page_id ? get_post( $page_id ) : null;
	}

	/**
	 * Should return the title of the page.
	 *
	 * @return string
	 */
	public static function get_template_title() {
		return __( 'Checkout', 'woo-gutenberg-products-block' );
	}

	/**
	 * True when viewing the checkout page or checkout endpoint.
	 *
	 * @return boolean
	 */
	public function is_active_template() {
		global $post;
		return $post instanceof \WP_Post && get_option( 'woocommerce_checkout_page_endpoint' ) === $post->post_name;
	}
}
