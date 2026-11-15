<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

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
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$default_cart_label = __( 'Your cart', 'woocommerce' );
		$cart_label         = $attributes['label'] ? $attributes['label'] : $default_cart_label;
		$wrapper_attributes = get_block_wrapper_attributes();

		ob_start();
		?>
		<span <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php echo esc_html( $cart_label ); ?>
		</span>
		<?php
		return (string) ob_get_clean();
	}
}
