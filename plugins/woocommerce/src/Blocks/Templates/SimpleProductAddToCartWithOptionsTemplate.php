<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * SimpleProductAddToCartWithOptionsTemplate class.
 *
 * @internal
 */
class SimpleProductAddToCartWithOptionsTemplate extends AbstractTemplatePart {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'simple-product-add-to-cart-with-options';

	/**
	 * The template part area where the template part belongs.
	 *
	 * @var string
	 */
	public $template_area = 'add-to-cart-with-options';

	/**
	 * Initialization method.
	 */
	public function init() {
	}

	/**
	 * Returns the title of the template.
	 *
	 * @return string
	 */
	public function get_template_title() {
		return _x( 'Simple Product Add to Cart with Options', 'Template name', 'woocommerce' );
	}

	/**
	 * Returns the description of the template.
	 *
	 * @return string
	 */
	public function get_template_description() {
		return __( 'Template used to display the Add to Cart with Options form for Simple Products.', 'woocommerce' );
	}
}
