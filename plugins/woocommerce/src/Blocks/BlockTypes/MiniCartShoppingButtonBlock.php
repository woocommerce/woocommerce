<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

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
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		// The button uses the core Button block classes, so load that block's stylesheet even when the page has no Button block.
		wp_enqueue_style( 'wp-block-button' );

		ob_start();
		$shop_url                     = wc_get_page_permalink( 'shop' );
		$default_start_shopping_label = __( 'Return to shop', 'woocommerce' );
		$start_shopping_label         = $attributes['startShoppingButtonLabel'] ? $attributes['startShoppingButtonLabel'] : $default_start_shopping_label;
		// Same markup and classes as a core Button block, so the theme's button styles apply.
		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'wp-block-button__link wp-element-button wc-block-mini-cart__shopping-button' ) );
		?>
		<div class="wp-block-button has-text-align-center">
			<a
				href="<?php echo esc_attr( $shop_url ); ?>"
				<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			><?php echo esc_html( $start_shopping_label ); ?></a>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
