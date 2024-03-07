<?php
namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * CheckoutHeader Template class.
 *
 * @internal
 */
class CheckoutHeaderTemplate extends AbstractTemplatePart {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'checkout-header';

	/**
	 * The template part area where the template part belongs.
	 *
	 * @var string
	 */
	public $template_area = 'header';

	/**
	 * Initialization method.
	 */
	public function init() {}

	/**
	 * Returns the title of the template.
	 *
	 * @return string
	 */
	public function get_template_title() {
		return _x( 'Checkout Header', 'Template name', 'woocommerce' );
	}

	/**
	 * Returns the description of the template.
	 *
	 * @return string
	 */
	public function get_template_description() {
		return __( 'Template used to display the simplified Checkout header.', 'woocommerce' );
	}
}
