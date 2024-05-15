<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CheckoutExpressPaymentBlock class.
 */
class CheckoutExpressPaymentBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-express-payment-block';

	/**
	 * Extra data passed through from server to client for block.
	 * We need to make some of the theme styles available to the ExpressPayment Block
	 * on the front end to pass to the express payment buttons
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );

		$global_styles                          = wp_get_global_styles( array( 'elements' ), array( 'transforms' => array( 'resolve-variables' ) ) );
		$express_payment_styles['borderRadius'] = $global_styles['button']['border']['radius'];

		$this->asset_data_registry->add( 'expressPaymentStyles', $express_payment_styles );
	}
}
