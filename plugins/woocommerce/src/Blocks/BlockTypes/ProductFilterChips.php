<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Product Filter: Chips Block.
 */
final class ProductFilterChips extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

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
		if (
			empty( $block->context['filterData'] )
		) {
			return '';
		}

		$items       = $block->context['filterData']['items'] ?? array();
		$show_counts = $block->context['filterData']['showCounts'] ?? false;
		$classes     = '';
		$style       = '';

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
		$show_initially              = 15;
		$remaining_initial_unchecked = count( $checked_items ) > $show_initially ? count( $checked_items ) : $show_initially - count( $checked_items );
		$count                       = 0;

		$wrapper_attributes = array(
			'data-wp-interactive' => 'woocommerce/product-filters',
			'data-wp-key'         => wp_unique_prefixed_id( $this->get_full_block_name() ),
			'data-wp-context'     => '{}',
			'class'               => esc_attr( $classes ),
			'style'               => esc_attr( $style ),
		);

		ob_start();
		?>
		<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="wc-block-product-filter-chips__items" aria-label="<?php echo esc_attr__( 'Filter Options', 'woocommerce' ); ?>">
				<?php foreach ( $items as $item ) { ?>
					<?php $item_id = $item['type'] . '-' . $item['value']; ?>
					<button
						data-wp-key="<?php echo esc_attr( $item_id ); ?>"
						id="<?php echo esc_attr( $item_id ); ?>"
						class="wc-block-product-filter-chips__item"
						type="button"
						aria-label="<?php echo esc_attr( $item['ariaLabel'] ); ?>"
						data-wp-on--click="actions.toggleFilter"
						value="<?php echo esc_attr( $item['value'] ); ?>"
						data-wp-bind--aria-checked="state.isFilterSelected"
						<?php echo wp_interactivity_data_wp_context( array( 'item' => $item ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( ! $item['selected'] ) : ?>
							<?php if ( $count >= $remaining_initial_unchecked ) : ?>
								data-wp-bind--hidden="!context.showAll"
								hidden
							<?php else : ?>
								<?php ++$count; ?>
							<?php endif; ?>
						<?php endif; ?>
					>
						<span class="wc-block-product-filter-chips__label">
							<span class="wc-block-product-filter-chips__text">
								<?php echo wp_kses_post( $item['label'] ); ?>
							</span>
							<?php if ( $show_counts ) : ?>
								<span class="wc-block-product-filter-chips__count">
									(<?php echo esc_html( $item['count'] ); ?>)
								</span>
							<?php endif; ?>
						</span>
					</button>
				<?php } ?>
			</div>
			<?php if ( count( $items ) > $show_initially ) : ?>
				<button
					class="wc-block-product-filter-chips__show-more"
					data-wp-on--click="actions.showAllChips"
					data-wp-bind--hidden="context.showAll"
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
