<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutShippingMethodBlock class.
 */
class CheckoutShippingMethodBlock extends AbstractInnerBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-shipping-method-block';
}
