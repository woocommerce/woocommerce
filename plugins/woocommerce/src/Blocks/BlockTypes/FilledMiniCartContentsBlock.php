<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * FilledMiniCartContentsBlock class.
 */
class FilledMiniCartContentsBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'filled-mini-cart-contents-block';

	/**
	 * Render the markup for the Filled Mini-Cart Contents block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( Features::is_enabled( 'experimental-iapi-mini-cart' ) ) {
			return $this->render_experimental_filled_mini_cart_contents( $attributes, $content, $block );
		}

		return $content;
	}

	/**
	 * Render the experimental interactivity API powered Filled Mini-Cart Contents block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render_experimental_filled_mini_cart_contents( $attributes, $content, $block ) {
		ob_start();
		?>
		<div data-wp-bind--hidden="state.cartIsEmpty" data-wp-interactive="woocommerce/mini-cart" class="wp-block-woocommerce-filled-mini-cart-contents-block">
			<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $content;
			?>
		</div>
		<?php
		return ob_get_clean();
	}
}
