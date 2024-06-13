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
	public $template_area = 'product-filters-overlay';

	/**
	 * Initialization method.
	 */
	public function init() {
		add_filter( 'default_wp_template_part_areas', array( $this, 'register_product_filters_overlay_template_part_area' ), 10, 1 );
	}

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

	/**
	 * Add Filters Overlay to the default template part areas.
	 *
	 * @param array $default_area_definitions An array of supported area objects.
	 * @return array The supported template part areas including the Filters Overlay one.
	 */
	public function register_product_filters_overlay_template_part_area( $default_area_definitions ) {
		$product_filters_overlay_template_part_area = array(
			'area'        => 'product-filters-overlay',
			'label'       => $this->get_template_title(),
			'description' => $this->get_template_description(),
			'icon'        => 'filter',
			'area_tag'    => 'product-filters-overlay',
		);
		return array_merge( $default_area_definitions, array( $product_filters_overlay_template_part_area ) );
	}
}
