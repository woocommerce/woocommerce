<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * MiniCartItemsBlock class.
 */
class MiniCartItemsBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'mini-cart-items-block';

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

	/**
	 * Render experimental iAPI block markup.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render_experimental_iapi_markup( $attributes, $content, $block ) {
		ob_start();
		?>
		<div data-wp-interactive="<?php echo esc_attr( $this->get_full_block_name() ); ?>" class="wp-block-woocommerce-mini-cart-items-block wc-block-mini-cart__items" tabindex="-1">
			<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $content;
			?>
		</div>
		<?php
		return ob_get_clean();
	}
}
