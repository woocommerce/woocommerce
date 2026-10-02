<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutOrderSummaryShippingBlock class.
 */
class CheckoutOrderSummaryShippingBlock extends AbstractInnerBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-order-summary-shipping-block';
}
