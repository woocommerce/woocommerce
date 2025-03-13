<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Block type for variation selector attribute name in add to cart with options.
 * It's responsible to render the attribute name.
 */
class AddToCartWithOptionsVariationSelectorAttributeName extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options-variation-selector-attribute-name';

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}
}
