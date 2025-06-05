<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * MiniCartFooterBlock class.
 */
class MiniCartFooterBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'mini-cart-footer-block';

	/**
	 * Render experimental iAPI powered Mini-Cart Footer block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render_experimental_iapi_mini_cart_footer( $attributes, $content, $block ) {
		ob_start();

		$cart                             = $this->get_cart_instance();
		$subtotal_label                   = __( 'Subtotal', 'woocommerce' );
		$other_costs_label                = __( 'Shipping, taxes, and discounts calculated at checkout.', 'woocommerce' );
		$display_cart_price_including_tax = get_option( 'woocommerce_tax_display_cart' ) === 'incl';
		$subtotal                         = $display_cart_price_including_tax ? $cart->get_subtotal_tax() : $cart->get_subtotal();
		$formatted_subtotal               = '';
		$html                             = new \WP_HTML_Tag_Processor( wc_price( $subtotal ) );

		if ( $html->next_tag( 'bdi' ) ) {
			while ( $html->next_token() ) {
				if ( '#text' === $html->get_token_name() ) {
						$formatted_subtotal .= $html->get_modifiable_text();
				}
			}
		}

		wp_interactivity_config(
			$this->get_full_block_name(),
			array(
				'displayCartPriceIncludingTax' => $display_cart_price_including_tax,
			)
		);

		wp_interactivity_state(
			$this->get_full_block_name(),
			array(
				'formattedSubtotal' => $formatted_subtotal,
			)
		);

		?>
		<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div data-wp-interactive="woocommerce/mini-cart-footer-block" class="wp-block-woocommerce-mini-cart-footer-block wc-block-mini-cart__footer">
			<div class="wc-block-components-totals-item wc-block-mini-cart__footer-subtotal">
				<span class="wc-block-components-totals-item__label">
					<?php echo esc_html( $subtotal_label ); ?>
				</span>
				<span data-wp-text="state.formattedSubtotal" class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-totals-item__value">
				</span>
				<div class="wc-block-components-totals-item__description">
					<?php echo esc_html( $other_costs_label ); ?>
				</div>
			</div>
			<div class="wc-block-mini-cart__footer-actions">
				<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $content;
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Return the main instance of WC_Cart class.
	 *
	 * @return \WC_Cart CartController class instance.
	 */
	protected function get_cart_instance() {
		$cart = WC()->cart;

		if ( $cart && $cart instanceof \WC_Cart ) {
			return $cart;
		}

		return null;
	}

	/**
	 * Render the markup for the Mini-Cart Contents block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( Features::is_enabled( 'experimental-iapi-mini-cart' ) ) {
			return $this->render_experimental_iapi_mini_cart_footer( $attributes, $content, $block );
		}

		return $content;
	}
}
