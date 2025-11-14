<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * MiniCartExpressCheckoutBlock class.
 */
class MiniCartExpressCheckoutBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'mini-cart-express-checkout-block';

	/**
	 * Render experimental iAPI block markup.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render_experimental_iapi_markup( $attributes, $content, $block ) {
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => 'wc-block-mini-cart__express-checkout',
			)
		);

		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="wc-block-mini-cart__express-checkout-title">
				<span><?php esc_html_e( 'Express Checkout', 'woocommerce' ); ?></span>
			</div>
			<!-- React island container: MiniCartExpressPaymentWrapper renders here via iAPI frontend -->
			<div class="wc-block-mini-cart__express-checkout-slot"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the markup for the Mini-Cart Express Checkout block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( Features::is_enabled( 'experimental-iapi-mini-cart' ) ) {
			return $this->render_experimental_iapi_markup( $attributes, $content, $block );
		}

		return $content;
	}
}
