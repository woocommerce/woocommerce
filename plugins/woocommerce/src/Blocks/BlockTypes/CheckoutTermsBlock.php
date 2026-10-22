<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutTermsBlock class.
 *
 * Terms are now settings on the Checkout Actions block. Existing checkouts keep this block, and merchants can switch
 * from it in the editor.
 *
 * @deprecated 11.4.0 Use the terms settings on the Checkout Actions block.
 */
class CheckoutTermsBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-terms-block';
}
