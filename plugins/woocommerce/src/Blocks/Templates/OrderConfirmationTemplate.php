<?php
namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * OrderConfirmationTemplate class.
 *
 * @internal
 */
class OrderConfirmationTemplate extends AbstractPageTemplate {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'order-confirmation';

	/**
	 * Initialization method.
	 */
	public function init() {
		add_action( 'wp_before_admin_bar_render', array( $this, 'remove_edit_page_link' ) );

		parent::init();
	}

	/**
	 * Returns the title of the template.
	 *
	 * @return string
	 */
	public function get_template_title() {
		return _x( 'Order Confirmation', 'Template name', 'woocommerce' );
	}

	/**
	 * Returns the description of the template.
	 *
	 * @return string
	 */
	public function get_template_description() {
		return __( 'The Order Confirmation template serves as a receipt and confirmation of a successful purchase. It includes a summary of the ordered items, shipping, billing, and totals.', 'woocommerce' );
	}

	/**
	 * Remove edit page from admin bar.
	 */
	public function remove_edit_page_link() {
		if ( $this->is_active_template() ) {
			global $wp_admin_bar;
			$wp_admin_bar->remove_menu( 'edit' );
		}
	}

	/**
	 * Returns the page object assigned to this template/page.
	 *
	 * @return \WP_Post|null Post object or null.
	 */
	protected function get_placeholder_page() {
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
}
