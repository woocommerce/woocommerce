<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutTermsBlock class.
 *
 * Terms and conditions are now settings on the Checkout's Actions block, so this block is hidden from the inserter. Existing checkouts
 * keep this block, and merchants can switch from it in the editor.
 */
class CheckoutTermsBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-terms-block';
}
