<?php
namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * ProductFilters Template class.
 *
 * @internal
 */
class ProductFiltersTemplate extends AbstractTemplatePart {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'product-filters';

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
		return _x( 'Product Filters', 'Template name', 'woocommerce' );
	}

	/**
	 * Returns the description of the template.
	 *
	 * @return string
	 */
	public function get_template_description() {
		return __( 'Template used to display the simplified Product Filters.', 'woocommerce' );
	}
}
