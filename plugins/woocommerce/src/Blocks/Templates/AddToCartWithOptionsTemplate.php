<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * AddToCartWithOptionsTemplate class.
 *
 * @internal
 */
class AddToCartWithOptionsTemplate extends AbstractTemplatePart {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'add-to-cart-with-options';

	/**
	 * The template part area where the template part belongs.
	 *
	 * @var string
	 */
	public $template_area = 'add-to-cart-with-options';

	/**
	 * The arguments of the template.
	 *
	 * @var array
	 */
	public array $args;

	/**
	 * Constructor.
	 *
	 * @param array $args The arguments of the template.
	 */
	public function __construct( array $args ) {
		$this->args = $args;
	}

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
		// translators: %s is the product type label.
		return sprintf( _x( '%s Add to Cart + Options', 'Template name', 'woocommerce' ), $this->args['product_type_label'] );
	}

	/**
	 * Returns the description of the template.
	 *
	 * @return string
	 */
	public function get_template_description() {
		// translators: %s is the product type label.
		return sprintf( __( 'Template used to display the Add to Cart + Options form for: %s.', 'woocommerce' ), $this->args['product_type_label'] );
	}
}
