<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * MiniCartCartButtonBlock class.
 */
class MiniCartCartButtonBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'mini-cart-cart-button-block';

	/**
	 * Render experimental iAPI block markup.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render_experimental_iapi_markup( $attributes, $content, $block ) {
		$default_view_cart_text = __( 'View my cart', 'woocommerce' );
		$view_cart_text         = $attributes['cartButtonLabel'] ? $attributes['cartButtonLabel'] : $default_view_cart_text;
		$cart_page_id           = wc_get_page_id( 'cart' );
		$cart_page_url          = get_permalink( $cart_page_id );
		$classes                = implode(
			' ',
			array_filter(
				array(
					'wc-block-components-button',
					'wp-element-button wc-block-mini-cart__footer-cart',
					// Default style class is not added by default, so it needs to be added manually if it doesn't exist.
					( ! isset( $attributes['className'] ) || strpos( $attributes['className'], 'is-style-' ) === false ) ? 'is-style-outline' : '',
				)
			)
		);
		$wrapper_attributes     = get_block_wrapper_attributes(
			array(
				'href'  => esc_url( $cart_page_url ),
				'class' => $classes,
			)
		);

		ob_start();

		?>
		<a <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="wc-block-components-button__text">
				<?php echo esc_html( $view_cart_text ); ?>
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
