<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutAdditionalInformationBlock class.
 */
class CheckoutAdditionalInformationBlock extends AbstractInnerBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-additional-information-block';
}
