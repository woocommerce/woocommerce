<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * MiniCartTitleBlock class.
 */
class MiniCartTitleBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'mini-cart-title-block';

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( Features::is_enabled( 'experimental-iapi-mini-cart' ) ) {
			return $this->render_experimental_iapi_title_block( $attributes, $content, $block );
		}
		return $content;
	}

	/**
	 * Render the interactivity API powered experimental title block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render_experimental_iapi_title_block( $attributes, $content, $block ) {
		$cart            = $this->get_cart_instance();
		$cart_item_count = $cart ? $cart->get_cart_contents_count() : 0;
		// translators: %d number of items in the cart.
		$cart_item_text = _n( '(%d item)', '(%d items)', $cart_item_count, 'woocommerce' );

		ob_start();
		?>
		<div class="wp-block-woocommerce-mini-cart-title-block">
			<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $content;
			?>
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
}
