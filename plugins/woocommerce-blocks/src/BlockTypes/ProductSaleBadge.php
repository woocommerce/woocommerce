<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ProductTag class.
 */
class ProductSaleBadge extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-sale-badge';

	/**
	 * API version name.
	 *
	 * @var string
	 */
	protected $api_version = '2';

	/**
	 * Get block attributes.
	 *
	 * @return array
	 */
	protected function get_block_type_supports() {
		return array(
			'color'                  =>
			array(
				'gradients'  => true,
				'background' => true,
				'link'       => true,
			),
			'typography'             =>
			array(
				'fontSize'   => true,
				'lineHeight' => true,
			),
			'__experimentalBorder'   =>
			array(
				'color'  => true,
				'radius' => true,
				'width'  => true,
			),
			'spacing'                =>
			array(
				'padding'                         => true,
				'__experimentalSkipSerialization' => true,
			),
			'__experimentalSelector' => '.wc-block-components-product-sale-badge',
		);
	}

}
