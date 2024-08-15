<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * MiniCartInteractivity class.
 */
class MiniCartInteractivity extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'mini-cart-interactivity';

	/**
	 * Get the editor script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return array|string;
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
	 * Get the cart instance.
	 *
	 * @return \WC_Cart
	 *
	 * @throws \Exception If the cart is not available.
	 */
	private function get_cart_instance() {
		$cart = wc()->cart;

		if ( ! $cart || ! $cart instanceof \WC_Cart ) {
			throw new \Exception( 'Unable to retrieve cart.' );
		}

		return $cart;
	}

	private function get_interactivity_namespace_attribute() {
		return wp_json_encode(
			array( 'namespace' => $this->get_full_block_name() ),
			JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
		);
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		// don't render if its admin, or ajax in progress.
		if ( is_admin() || wp_doing_ajax() ) {
			return '';
		}

		$cart            = $this->get_cart_instance();
		$cart_item_count = $cart->get_cart_contents_count();

		$cart_context = array(
			'cartItemCount' => $cart_item_count,
		);

		ob_start();
		?>
		<div class="wc-block-mini-cart-interactivity" data-wc-interactive='<?php echo esc_attr( $this->get_interactivity_namespace_attribute() ); ?>'>
			<button class="wc-block-mini-cart__button " aria-label="" data-wc-init="callbacks.initialize" data-wc-context='<?php echo wp_json_encode( $cart_context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ); ?>' >
				<span class="wc-block-mini-cart__quantity-badge">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="none" width="20" height="20" class="wc-block-mini-cart__icon" aria-hidden="true" focusable="false"><circle cx="12.6667" cy="24.6667" r="2" fill="currentColor"></circle><circle cx="23.3333" cy="24.6667" r="2" fill="currentColor"></circle><path fill-rule="evenodd" clip-rule="evenodd" d="M9.28491 10.0356C9.47481 9.80216 9.75971 9.66667 10.0606 9.66667H25.3333C25.6232 9.66667 25.8989 9.79247 26.0888 10.0115C26.2787 10.2305 26.3643 10.5211 26.3233 10.8081L24.99 20.1414C24.9196 20.6341 24.4977 21 24 21H12C11.5261 21 11.1173 20.6674 11.0209 20.2034L9.08153 10.8701C9.02031 10.5755 9.09501 10.269 9.28491 10.0356ZM11.2898 11.6667L12.8136 19H23.1327L24.1803 11.6667H11.2898Z" fill="currentColor"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M5.66669 6.66667C5.66669 6.11438 6.1144 5.66667 6.66669 5.66667H9.33335C9.81664 5.66667 10.2308 6.01229 10.3172 6.48778L11.0445 10.4878C11.1433 11.0312 10.7829 11.5517 10.2395 11.6505C9.69614 11.7493 9.17555 11.3889 9.07676 10.8456L8.49878 7.66667H6.66669C6.1144 7.66667 5.66669 7.21895 5.66669 6.66667Z" fill="currentColor"></path></svg>
					<?php if ( $cart_item_count > 0 ) : ?>
						<span class="wc-block-mini-cart__badge" data-wc-text="context.cartItemCount">
							<?php echo esc_html( $cart_item_count ); ?>
						</span>
					<?php endif; ?>
				</span>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}
}
