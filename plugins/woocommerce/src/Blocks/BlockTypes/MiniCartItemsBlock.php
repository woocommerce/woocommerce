<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * MiniCartItemsBlock class.
 */
class MiniCartItemsBlock extends AbstractInnerBlock {
	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'mini-cart-items-block';

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block.
	 */
	protected function render( $attributes, $content, $block ) {
		ob_start();
		?>
		<div class="wc-block-mini-cart__items" data-wp-interactive="<?php echo esc_attr( $this->get_full_block_name() ); ?>">
			<p>Hello World</p>
			<?php echo $content; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
