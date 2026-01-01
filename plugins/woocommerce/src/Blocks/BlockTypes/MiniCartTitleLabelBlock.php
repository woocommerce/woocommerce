<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Admin\Features\Features;

/**
 * MiniCartTitleLabelBlock class.
 */
class MiniCartTitleLabelBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'mini-cart-title-label-block';

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
			return $this->render_experimental_iapi_title_label_block( $attributes, $content, $block );
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
	protected function render_experimental_iapi_title_label_block( $attributes, $content, $block ) {
		$default_cart_label = __( 'Your cart', 'woocommerce' );
		$cart_label         = $attributes['label'] ? $attributes['label'] : $default_cart_label;
		$wrapper_attributes = get_block_wrapper_attributes();

		ob_start();
		?>
		<span <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php echo esc_html( $cart_label ); ?>
		</span>
		<?php
		return ob_get_clean();
	}
}
