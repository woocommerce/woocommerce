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
	 * The title of the template.
	 *
	 * @var string
	 */
	public $template_title;

	/**
	 * The description of the template.
	 *
	 * @var string
	 */
	public $template_description;

	/**
	 * The template part area where the template part belongs.
	 *
	 * @var string
	 */
	public $template_area = 'header';

	/**
	 * Class constructor.
	 */
	public function init() {
		$this->template_title       = _x( 'Checkout Header', 'Template name', 'woocommerce' );
		$this->template_description = __( 'Template used to display the simplified Checkout header.', 'woocommerce' );
	}
}
