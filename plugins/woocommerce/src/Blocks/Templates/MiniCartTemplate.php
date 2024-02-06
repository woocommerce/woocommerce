<?php
namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\BlockTemplatesRegistry;

/**
 * MiniCartTemplate class.
 *
 * @internal
 */
class MiniCartTemplate extends AbstractTemplatePart {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'mini-cart';

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
	public $template_area = 'mini-cart';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->template_title       = _x( 'Mini-Cart', 'Template name', 'woocommerce' );
		$this->template_description = __( 'Template used to display the Mini-Cart drawer.', 'woocommerce' );
		BlockTemplatesRegistry::register_template( $this );
		$this->init();
	}

	/**
	 * Initialization method.
	 */
	public function init() {
		add_filter( 'default_wp_template_part_areas', array( $this, 'register_mini_cart_template_part_area' ), 10, 1 );
	}

	/**
	 * Add Mini-Cart to the default template part areas.
	 *
	 * @param array $default_area_definitions An array of supported area objects.
	 * @return array The supported template part areas including the Mini-Cart one.
	 */
	public function register_mini_cart_template_part_area( $default_area_definitions ) {
		$mini_cart_template_part_area = [
			'area'        => 'mini-cart',
			'label'       => __( 'Mini-Cart', 'woocommerce' ),
			'description' => __( 'The Mini-Cart template allows shoppers to see their cart items and provides access to the Cart and Checkout pages.', 'woocommerce' ),
			'icon'        => 'mini-cart',
			'area_tag'    => 'mini-cart',
		];
		return array_merge( $default_area_definitions, [ $mini_cart_template_part_area ] );
	}
}
