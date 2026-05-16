<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutPickupOptionsBlock class.
 */
class CheckoutPickupOptionsBlock extends AbstractInnerBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-pickup-options-block';
}
