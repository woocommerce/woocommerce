<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutTotalsBlock class.
 */
class CheckoutTotalsBlock extends AbstractInnerBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-totals-block';
}
