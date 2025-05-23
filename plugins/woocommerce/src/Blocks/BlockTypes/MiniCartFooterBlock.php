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

		$subtotal_label    = __( 'Subtotal', 'woocommerce' );
		$other_costs_label = __( 'Shipping, taxes, and discounts calculated at checkout.', 'woocommerce' );

		?>
		<div class="wp-block-woocommerce-mini-cart-footer-block wc-block-mini-cart__footer">
			<div class="wc-block-components-totals-item wc-block-mini-cart__footer-subtotal">
				<span class="wc-block-components-totals-item__label">
					<?php echo esc_html( $subtotal_label ); ?>
				</span>
				<span class="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-totals-item__value">
					$86.00
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
