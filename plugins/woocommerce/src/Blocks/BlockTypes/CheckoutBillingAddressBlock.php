<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutBillingAddressBlock class.
 */
class CheckoutBillingAddressBlock extends AbstractInnerBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-billing-address-block';
}
