<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ExpressCheckoutIapiBlock class.
 */
class ExpressCheckoutIapiBlock extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'express-checkout-iapi-block';

	/**
	 * Get the editor script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return array|string
	 */
	protected function get_block_type_editor_script( $key = null ) {
		$script = [
			'handle'       => 'wc-' . $this->block_name,
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name ),
			'dependencies' => [ 'wc-blocks' ],
		];
		return $key ? $script[ $key ] : $script;
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );

		// Register and enqueue the React bridge script that exposes ExpressPaymentWrapper
		if ( ! is_admin() && ! WC()->is_rest_api_request() ) {
			/**
			 * Fires before express checkout iAPI payment scripts are enqueued.
			 * This allows payment gateways to enqueue their express payment method scripts.
			 *
			 * @since 11.8.0
			 */
			do_action( 'woocommerce_blocks_enqueue_cart_block_scripts_before' );

			// Register and enqueue the React bridge that exposes ExpressPaymentWrapper to window.
			$this->asset_api->register_script( 'wc-express-checkout-iapi-react-bridge', 'assets/client/blocks/express-checkout-iapi-react-bridge-frontend.js', array() );
			wp_enqueue_script( 'wc-express-checkout-iapi-react-bridge' );

			// Explicitly enqueue payment method scripts to ensure they register with wcBlocksRegistry.
			// This is needed because payment gateways may not enqueue their scripts on non-cart/checkout pages.
			$payment_method_registry = \Automattic\WooCommerce\Blocks\Package::container()->get( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry::class );
			$payment_methods         = $payment_method_registry->get_all_active_payment_method_script_dependencies();

			foreach ( $payment_methods as $payment_method ) {
				wp_enqueue_script( $payment_method );
			}

			/**
			 * Fires after express checkout iAPI payment scripts are enqueued.
			 *
			 * @since 11.8.0
			 */
			do_action( 'woocommerce_blocks_enqueue_cart_block_scripts_after' );

			// Hydrate the cart data so express payment methods can be registered
			$this->asset_data_registry->hydrate_api_request( '/wc/store/v1/cart' );

			/**
			 * Fires after express checkout iAPI block data is registered.
			 * This allows payment integrations to register their express payment methods.
			 *
			 * @since 11.8.0
			 */
			do_action( 'woocommerce_blocks_express_checkout_iapi_enqueue_data' );
		}
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => 'wp-block-woocommerce-express-checkout-iapi-block',
			)
		);

		return sprintf(
			'<div %s><div class="wc-block-express-checkout-iapi__express-checkout-slot" data-wp-interactive="woocommerce/express-checkout-iapi-block" data-wp-init="callbacks.renderExpressPaymentMethodsIsland"></div></div>',
			$wrapper_attributes
		);
	}
}
