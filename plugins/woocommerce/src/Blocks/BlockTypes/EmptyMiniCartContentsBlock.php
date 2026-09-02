<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * EmptyMiniCartContentsBlock class.
 */
class EmptyMiniCartContentsBlock extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'empty-mini-cart-contents-block';

	/**
	 * Render the markup for the Empty Mini-Cart Contents block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'data-wp-bind--aria-hidden' => '!state.cartIsEmpty',
				'data-wp-bind--hidden'      => '!state.cartIsEmpty',
				'data-wp-interactive'       => 'woocommerce/mini-cart',
			)
		);

		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="wc-block-mini-cart__empty-cart-wrapper">
				<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $content;
				?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
