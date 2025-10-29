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
			<div class="wc-block-mini-cart__footer-discounts-meta-slot" data-wp-init="callbacks.renderDiscountsMetaSlot"></div>
			<div class="wc-block-mini-cart__footer-tabs">
				<div class="wc-block-mini-cart__footer-tabs-header">
			<button
					class="wc-block-mini-cart__footer-tab-button"
					data-tab="shipping"
					data-wp-on--click="actions.switchTab"
					data-wp-class--wc-block-mini-cart__footer-tab-button--active="state.isShippingTabActive"
					data-wp-bind--disabled="state.isShippingTabActive">
						<?php echo esc_html__( 'Shipping', 'woocommerce' ); ?>
					</button>
			<button
					class="wc-block-mini-cart__footer-tab-button"
					data-tab="local-pickup"
					data-wp-on--click="actions.switchTab"
					data-wp-class--wc-block-mini-cart__footer-tab-button--active="state.isLocalPickupTabActive"
					data-wp-bind--disabled="state.isLocalPickupTabActive">
						<?php echo esc_html__( 'Local pickup', 'woocommerce' ); ?>
					</button>
				</div>
				<div class="wc-block-mini-cart__footer-tabs-content">
					<div
					class="wc-block-mini-cart__footer-tab-panel"
					data-tab-panel="shipping"
					data-wp-bind--hidden="state.isShippingPanelHidden">
						<div class="wc-block-mini-cart__footer-shipping-packages-slot" data-wp-init="callbacks.renderShippingPackagesSlot"></div>
					</div>
					<div
					class="wc-block-mini-cart__footer-tab-panel"
					data-tab-panel="local-pickup"
					data-wp-bind--hidden="state.isLocalPickupPanelHidden">
						<div class="wc-block-mini-cart__footer-local-pickup-packages-slot" data-wp-init="callbacks.renderLocalPickupPackagesSlot"></div>
					</div>
				</div>
			</div>
			<div class="wc-block-mini-cart__footer-debug">
				<span class="wc-block-components-totals-item__discount-label" data-wp-text="state.totalDiscountLabel"></span>
				<span class="wc-block-components-totals-item__discount" data-wp-text="state.totalDiscount"></span>
				<br />
				<span class="wc-block-components-totals-item__shipping-method" data-wp-text="state.selectedShippingMethod"></span>
				<br />
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
