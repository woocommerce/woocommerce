<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * MiniCartShoppingButtonBlock class.
 */
class MiniCartShoppingButtonBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'mini-cart-shopping-button-block';

	/**
	 * Render the markup for the Mini-Cart Shopping Button block.
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

	/**
	 * Render experimental iAPI powered  markup for the Mini-Cart Contents block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render_experimental_iapi_markup( $attributes, $content, $block ) {
		ob_start();
		$shop_url = get_permalink( wc_get_page_id( 'shop' ) );
		?>
		<div class="wp-block-button has-text-align-center">
			<a
				data-wp-interactive="woocommerce/mini-cart-shopping-button-block"
				href="<?php echo esc_attr( $shop_url ); ?>"
				class="wc-block-components-button wp-element-button wp-block-woocommerce-mini-cart-shopping-button-block wc-block-mini-cart__shopping-button contained"
			>
				<div class="wc-block-components-button__text">
					<?php esc_html_e( 'Start shopping', 'woocommerce' ); ?>
				</div>
			</a>
		</div>
		<?php
		return ob_get_clean();
	}
}
