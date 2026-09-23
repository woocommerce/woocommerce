<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutOrderSummaryDiscountBlock class.
 */
class CheckoutOrderSummaryDiscountBlock extends AbstractInnerBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-order-summary-discount-block';
}
