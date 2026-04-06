<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;
use Automattic\WooCommerce\Admin\Features\Features;

/**
 * ProceedToCheckoutBlock class.
 */
class ProceedToCheckoutBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'proceed-to-checkout-block';

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes Any attributes that currently are available from the block.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		$this->asset_data_registry->register_page_id( isset( $attributes['checkoutPageId'] ) ? $attributes['checkoutPageId'] : 0 );
	}

	/**
	 * Enable interactivity support when the feature flag is on.
	 */
	protected function initialize() {
		parent::initialize();

		if ( Features::is_enabled( 'experimental-iapi-cart' ) ) {
			add_action( 'init', array( $this, 'enable_interactivity_support' ), 20 );
		}
	}

	/**
	 * Dynamically enable interactivity support on the block type.
	 */
	public function enable_interactivity_support() {
		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( 'woocommerce/' . $this->block_name );
		if ( $block_type ) {
			$block_type->supports['interactivity'] = true;
		}
	}

	/**
	 * Render the block. When the IAPI flag is enabled, render interactive markup.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param \WP_Block $block     Block instance.
	 * @return string Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( ! Features::is_enabled( 'experimental-iapi-cart' ) ) {
			return $content;
		}

		return $this->render_iapi( $attributes, $content, $block );
	}

	/**
	 * Render the IAPI version of the proceed-to-checkout block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param \WP_Block $block     Block instance.
	 * @return string Rendered block output.
	 */
	private function render_iapi( $attributes, $content, $block ) {
		wp_enqueue_script_module( 'woocommerce/proceed-to-checkout' );

		$consent = 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce';
		BlocksSharedState::load_cart_state( $consent );

		// Resolve checkout URL.
		$checkout_page_id = isset( $attributes['checkoutPageId'] ) ? $attributes['checkoutPageId'] : 0;
		$checkout_url     = $checkout_page_id ? get_permalink( $checkout_page_id ) : wc_get_checkout_url();
		if ( ! $checkout_url ) {
			$checkout_url = wc_get_checkout_url();
		}

		// Resolve button label.
		$button_label = isset( $attributes['buttonLabel'] ) && ! empty( $attributes['buttonLabel'] )
			? $attributes['buttonLabel']
			: __( 'Proceed to Checkout', 'woocommerce' );

		$context = array(
			'checkoutUrl' => $checkout_url,
			'buttonLabel' => $button_label,
			'isLoading'   => false,
		);

		$context_attr = wp_interactivity_data_wp_context( $context );

		return sprintf(
			'<div
				class="wc-block-cart__submit"
				data-wp-interactive="woocommerce/proceed-to-checkout"
				data-wp-init="callbacks.onPageShow"
				%1$s
			>
				<div aria-hidden="true" style="height:0;overflow:hidden;position:relative" data-wp-init="callbacks.initStickyObserver"></div>
				<div
					class="wc-block-cart__submit-container"
					data-wp-class--wc-block-cart__submit-container--sticky="state.isStickyVisible"
					data-wp-style--background-color="state.stickyBackgroundColor"
				>
					<a
						class="wc-block-cart__submit-button wc-block-components-button wp-element-button"
						data-wp-bind--href="context.checkoutUrl"
						data-wp-bind--aria-disabled="state.isDisabled"
						data-wp-class--wc-block-cart__submit-button--loading="context.isLoading"
						data-wp-on--click="actions.handleClick"
						href="%2$s"
					>
						<span class="wc-block-components-button__text" data-wp-text="context.buttonLabel">%3$s</span>
					</a>
				</div>
			</div>',
			$context_attr,
			esc_url( $checkout_url ),
			esc_html( $button_label )
		);
	}
}
