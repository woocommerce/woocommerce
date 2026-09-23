<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutShippingAddressBlock class.
 */
class CheckoutShippingAddressBlock extends AbstractInnerBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-shipping-address-block';
}
