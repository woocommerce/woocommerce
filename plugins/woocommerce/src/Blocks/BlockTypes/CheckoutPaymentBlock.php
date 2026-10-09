<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutPaymentBlock class.
 */
class CheckoutPaymentBlock extends AbstractInnerBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-payment-block';
}
