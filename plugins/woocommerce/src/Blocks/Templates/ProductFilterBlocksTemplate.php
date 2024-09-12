<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\Templates;

/**
 * ProductFilterBlocksTemplate class.
 *
 * @internal
 */
class ProductFilterBlocksTemplate extends AbstractTemplatePart {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = 'product-filter-blocks';

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
		return _x( 'Product Filter Blocks', 'Template name', 'woocommerce' );
	}

	/**
	 * Returns the description of the template.
	 *
	 * @return string
	 */
	public function get_template_description() {
		return __( 'Template used to display the Product Filter Blocks.', 'woocommerce' );
	}
}
