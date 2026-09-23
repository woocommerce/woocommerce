<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutTermsBlock class.
 */
class CheckoutTermsBlock extends AbstractInnerBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-terms-block';
}
