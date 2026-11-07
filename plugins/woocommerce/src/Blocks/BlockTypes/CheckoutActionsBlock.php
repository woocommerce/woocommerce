<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutActionsBlock class.
 */
class CheckoutActionsBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-actions-block';

	/**
	 * Register style variations for the block.
	 *
	 * @deprecated 11.4.0 Style variations are now declared in block.json.
	 */
	public function register_style_variations() {
		_deprecated_function( __METHOD__, '11.4.0' );
	}
}
