<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutFieldsBlock class.
 */
class CheckoutFieldsBlock extends AbstractInnerBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-fields-block';
}
