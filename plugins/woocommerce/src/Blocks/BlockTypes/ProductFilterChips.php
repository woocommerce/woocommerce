<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Product Filter: Chips Block.
 */
final class ProductFilterChips extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-chips';

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$classes               = '';
		$style                 = '';
		$context               = $block->context['filterData'];
		$items                 = $context['items'] ?? array();
		$checkbox_list_context = array( 'items' => $items );
		$action                = $context['action'] ?? '';
		$namespace             = wp_json_encode( array( 'namespace' => 'woocommerce/product-filter-chips' ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );

		$tags = new \WP_HTML_Tag_Processor( $content );
		if ( $tags->next_tag( array( 'class_name' => 'wc-block-product-filter-chips' ) ) ) {
			$classes = $tags->get_attribute( 'class' );
			$style   = $tags->get_attribute( 'style' );
		}

		$checked_items               = array_filter(
			$items,
			function ( $item ) {
				return $item['selected'];
			}
		);
		$show_initially              = $context['show_initially'] ?? 15;
		$remaining_initial_unchecked = count( $checked_items ) > $show_initially ? count( $checked_items ) : $show_initially - count( $checked_items );
		$count                       = 0;

		$wrapper_attributes = array(
			'data-wc-interactive' => esc_attr( $namespace ),
			'data-wc-context'     => wp_json_encode( $checkbox_list_context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ),
			'class'               => esc_attr( $classes ),
			'style'               => esc_attr( $style ),
		);

		ob_start();
		?>
		<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="wc-block-product-filter-chips__items" aria-label="<?php echo esc_attr__( 'Filter Options', 'woocommerce' ); ?>">
				<?php foreach ( $items as $item ) { ?>
					<?php $item['id'] = $item['id'] ?? uniqid( 'chips-' ); ?>
					<button
						data-wc-key="<?php echo esc_attr( $item['id'] ); ?>"
						<?php
						if ( ! $item['selected'] ) :
							if ( $count >= $remaining_initial_unchecked ) :
								?>
								class="wc-block-product-filter-chips__item"
								data-wc-bind--hidden="!context.showAll"
								hidden
							<?php else : ?>
								<?php ++$count; ?>
							<?php endif; ?>
						<?php endif; ?>
						class="wc-block-product-filter-chips__item"
						data-wc-on--click--select-item="actions.selectItem"
						data-wc-on--click--parent-action="<?php echo esc_attr( $action ); ?>"
						value="<?php echo esc_attr( $item['value'] ); ?>"
						aria-checked="<?php echo $item['selected'] ? 'true' : 'false'; ?>"
					>
						<span class="wc-block-product-filter-chips__label">
							<?php echo wp_kses_post( $item['label'] ); ?>
						</span>
					</button>
				<?php } ?>
			</div>
			<?php if ( count( $items ) > $show_initially ) : ?>
				<button
					class="wc-block-product-filter-chips__show-more"
					data-wc-bind--hidden="context.showAll"
					data-wc-on--click="actions.showAllItems"
					hidden
				>
					<?php echo esc_html__( 'Show more...', 'woocommerce' ); ?>
				</button>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
