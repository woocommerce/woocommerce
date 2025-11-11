<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * MiniCartCheckoutButtonBlock class.
 */
class MiniCartCheckoutButtonBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'mini-cart-checkout-button-block';

	/**
	 * Render experimental iAPI block markup.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render_experimental_iapi_markup( $attributes, $content, $block ) {
		$default_go_to_checkout_text = __( 'Go to checkout', 'woocommerce' );
		$go_to_checkout_text         = $attributes['checkoutButtonLabel'] ? $attributes['checkoutButtonLabel'] : $default_go_to_checkout_text;
		$checkout_page_id            = wc_get_page_id( 'checkout' );
		$checkout_page_url           = get_permalink( $checkout_page_id );
		$wrapper_attributes          = get_block_wrapper_attributes(
			array(
				'href'  => esc_url( $checkout_page_url ),
				'class' => 'wc-block-components-button wp-element-button wc-block-mini-cart__footer-checkout',
			)
		);

		ob_start();
		?>
		<a <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="wc-block-components-button__text">
				<?php echo esc_html( $go_to_checkout_text ); ?>
			</div>
		</a>
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
			return $this->render_experimental_iapi_markup( $attributes, $content, $block );
		}

		return $content;
	}
}
