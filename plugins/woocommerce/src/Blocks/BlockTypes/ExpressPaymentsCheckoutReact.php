<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * ExpressPaymentsCheckoutReact class.
 */
class ExpressPaymentsCheckoutReact extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'express-payments-checkout-react';

	/**
	 * Get the editor script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return array|string
	 */
	protected function get_block_type_editor_script( $key = null ) {
		$script = [
			'handle'       => 'wc-' . $this->block_name . '-block',
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name ),
			'dependencies' => [ 'wc-blocks' ],
		];
		return $key ? $script[ $key ] : $script;
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return array|string
	 */
	protected function get_block_type_script( $key = null ) {
		$script = [
			'handle'       => 'wc-' . $this->block_name . '-block-frontend',
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name . '-frontend' ),
			'dependencies' => [],
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

		// Hydrate the cart data so express payment methods can be registered
		if ( ! is_admin() && ! WC()->is_rest_api_request() ) {
			$this->asset_data_registry->hydrate_api_request( '/wc/store/v1/cart' );
		}

		/**
		 * Fires after express payments block data is registered.
		 * This allows payment integrations to register their express payment methods.
		 *
		 * @since 11.8.0
		 */
		do_action( 'woocommerce_blocks_express_payments_enqueue_data' );
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
		// Return a placeholder div that React will hydrate
		return sprintf(
			'<div class="wp-block-woocommerce-express-payments-checkout-react" data-block-name="woocommerce/express-payments-checkout-react">%s</div>',
			$content
		);
	}
}
