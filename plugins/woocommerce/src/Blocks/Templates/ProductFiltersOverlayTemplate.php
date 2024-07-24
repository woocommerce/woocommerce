<?php
namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * ProductFiltersOverlayTemplate class.
 *
 * @internal
 */
class ProductFiltersOverlayTemplate extends AbstractTemplatePart {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'product-filters-overlay';

	/**
	 * The template part area where the template part belongs.
	 *
	 * @var string
	 */
	public $template_area = 'uncategorized';

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
		return _x( 'Filters Overlay', 'Template name', 'woocommerce' );
	}

	/**
	 * Returns the description of the template.
	 *
	 * @return string
	 */
	public function get_template_description() {
		return __( 'Template used to display the Product Filters Overlay.', 'woocommerce' );
	}
}
