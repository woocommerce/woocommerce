<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutContactInformationBlock class.
 */
class CheckoutContactInformationBlock extends AbstractInnerBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-contact-information-block';
}
