<?php
namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * OrderConfirmationTemplate class.
 *
 * @internal
 */
class OrderConfirmationTemplate extends AbstractPageTemplate {
	/**
	 * Template slug.
	 *
	 * @return string
	 */
	public static function get_slug() {
		return 'order-confirmation';
	}

	/**
	 * Returns the page object assigned to this template/page.
	 *
	 * @return \WP_Post|null Post object or null.
	 */
	public static function get_placeholder_page() {
		return null;
	}

	/**
	 * True when viewing the Order Received endpoint.
	 *
	 * @return boolean
	 */
	protected function is_active_template() {
		return is_wc_endpoint_url( 'order-received' );
	}

	/**
	 * Should return the title of the page.
	 *
	 * @return string
	 */
	public static function get_template_title() {
		return __( 'Order Confirmation', 'woo-gutenberg-products-block' );
	}
}
