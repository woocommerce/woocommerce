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
	 * Data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = array() ) {
		parent::enqueue_data( $attributes );
		$description = $this->get_totals_item_description();
		if ( ! empty( $description ) ) {
			$this->asset_data_registry->add( 'miniCartFooterDescription', $description );
		}
	}

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
		$other_costs_label                = $this->get_totals_item_description();
		$display_cart_price_including_tax = get_option( 'woocommerce_tax_display_cart' ) === 'incl';
		$subtotal                         = $display_cart_price_including_tax ? $cart->get_subtotal_tax() : $cart->get_subtotal();
		$formatted_subtotal               = '';
		$html                             = new \WP_HTML_Tag_Processor( wc_price( $subtotal ) );
		$wrapper_attributes               = get_block_wrapper_attributes(
			array(
				'data-wp-interactive' => 'woocommerce/mini-cart-footer-block',
				'class'               => 'wc-block-mini-cart__footer',
			)
		);

		if ( $html->next_tag( 'bdi' ) ) {
			while ( $html->next_token() ) {
				if ( '#text' === $html->get_token_name() ) {
						$formatted_subtotal .= $html->get_modifiable_text();
				}
			}
		}

		wp_interactivity_state(
			$this->get_full_block_name(),
			array(
				'formattedSubtotal' => $formatted_subtotal,
			)
		);

		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="wc-block-components-totals-item wc-block-mini-cart__footer-subtotal">
				<span class="wc-block-components-totals-item__label">
					<?php echo esc_html( $subtotal_label ); ?>
				</span>
				<span data-wp-text="woocommerce/mini-cart::state.formattedSubtotal" class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-totals-item__value">
				</span>
				<div class="wc-block-components-totals-item__description">
					<?php echo esc_html( $other_costs_label ); ?>
				</div>
			</div>
			<div class="wc-block-mini-cart__footer-actions">
				<?php
				if ( empty( $content ) ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo do_blocks( '<!-- wp:woocommerce/mini-cart-cart-button-block /--><!-- wp:woocommerce/mini-cart-checkout-button-block /-->' );
				} else {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $content;
				}
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
	 * Computes the total items description text based on which settings are enabled.
	 *
	 * @return string The description text for the total items, or empty string if none are enabled.
	 */
	private function get_totals_item_description() {
		$taxes_enabled    = wc_tax_enabled();
		$shipping_enabled = wc_shipping_enabled();
		$coupons_enabled  = wc_coupons_enabled();

		// All three enabled.
		if ( $taxes_enabled && $shipping_enabled && $coupons_enabled ) {
			return __(
				'Shipping, taxes, and discounts calculated at checkout.',
				'woocommerce'
			);
		}

		// Shipping + taxes.
		if ( $shipping_enabled && $taxes_enabled ) {
			return __(
				'Shipping and taxes calculated at checkout.',
				'woocommerce'
			);
		}

		// Shipping + discounts.
		if ( $shipping_enabled && $coupons_enabled ) {
			return __(
				'Shipping and discounts calculated at checkout.',
				'woocommerce'
			);
		}

		// Taxes + discounts.
		if ( $taxes_enabled && $coupons_enabled ) {
			return __(
				'Taxes and discounts calculated at checkout.',
				'woocommerce'
			);
		}

		// Only shipping.
		if ( $shipping_enabled ) {
			return __( 'Shipping calculated at checkout.', 'woocommerce' );
		}

		// Only taxes.
		if ( $taxes_enabled ) {
			return __( 'Taxes calculated at checkout.', 'woocommerce' );
		}

		// Only discounts.
		if ( $coupons_enabled ) {
			return __( 'Discounts calculated at checkout.', 'woocommerce' );
		}

		// None enabled.
		return '';
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
