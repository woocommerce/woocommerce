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
	public $template_area = 'products';

	/**
	 * Initialization method.
	 */
	public function init() {
		add_filter( 'default_wp_template_part_areas', array( $this, 'register_products_template_part_area' ), 10, 1 );
	}

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

	/**
	 * Add Product Filters to the default template part areas.
	 *
	 * @param array $default_area_definitions An array of supported area objects.
	 * @return array The supported template part areas including the Product Filters one.
	 */
	public function register_products_template_part_area( $default_area_definitions ) {
		$products_template_part_area = array(
			'area'        => 'products',
			'label'       => __( 'Products', 'woocommerce' ),
			'description' => __( 'This is the area for template parts used to consistently display various product information, such as images and prices, across all of your store pages.', 'woocommerce' ),
			'icon'        => 'products',
			'area_tag'    => 'products',
		);
		return array_merge( $default_area_definitions, array( $products_template_part_area ) );
	}
}
