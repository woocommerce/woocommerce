<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Product Filter: Checkbox List Block.
 */
final class ProductFilterCheckboxList extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-checkbox-list';

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( empty( $block->context['filterData'] ) ) {
			return '';
		}

		$block_context = $block->context['filterData'];
		$items         = $block_context['items'] ?? array();
		$show_counts   = $block_context['showCounts'] ?? false;
		$classes       = '';
		$style         = '';

		$tags = new \WP_HTML_Tag_Processor( $content );
		if ( $tags->next_tag( array( 'class_name' => 'wc-block-product-filter-checkbox-list' ) ) ) {
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
			<ul class="wc-block-product-filter-checkbox-list__list" aria-label="<?php echo esc_attr__( 'Filter Options', 'woocommerce' ); ?>">
				<?php foreach ( $items as $item ) { ?>
					<?php $item_id = $item['type'] . '-' . $item['value']; ?>
					<li
						data-wp-key="<?php echo esc_attr( $item_id ); ?>"
						class="wc-block-product-filter-checkbox-list__item"
						<?php if ( ! $item['selected'] ) : ?>
							<?php if ( $count >= $remaining_initial_unchecked ) : ?>
								data-wp-bind--hidden="!context.showAll"
								hidden
							<?php else : ?>
								<?php ++$count; ?>
							<?php endif; ?>
						<?php endif; ?>
					>
						<label
							class="wc-block-product-filter-checkbox-list__label"
							for="<?php echo esc_attr( $item_id ); ?>"
						>
							<span class="wc-block-product-filter-checkbox-list__input-wrapper">
								<input
									id="<?php echo esc_attr( $item_id ); ?>"
									class="wc-block-product-filter-checkbox-list__input"
									type="checkbox"
									aria-label="<?php echo esc_attr( $item['ariaLabel'] ); ?>"
									data-wp-on--change="actions.toggleFilter"
									value="<?php echo esc_attr( $item['value'] ); ?>"
									data-wp-bind--checked="state.isFilterSelected"
									<?php echo wp_interactivity_data_wp_context( array( 'item' => $item ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								>
								<svg class="wc-block-product-filter-checkbox-list__mark" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M9.25 1.19922L3.75 6.69922L1 3.94922" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</span>
							<span class="wc-block-product-filter-checkbox-list__text-wrapper">
								<span class="wc-block-product-filter-checkbox-list__text">
									<?php echo wp_kses_post( $item['label'] ); ?>
								</span>
								<?php if ( $show_counts ) : ?>
									<span class="wc-block-product-filter-checkbox-list__count">
										(<?php echo esc_html( $item['count'] ); ?>)
									</span>
								<?php endif; ?>
							</span>
						</label>
					</li>
				<?php } ?>
			</ul>
			<?php if ( count( $items ) > $show_initially ) : ?>
				<button
					class="wc-block-product-filter-checkbox-list__show-more"
					data-wp-bind--hidden="context.showAll"
					data-wp-on--click="actions.showAllListItems"
					hidden
				>
					<?php echo esc_html__( 'Show more...', 'woocommerce' ); ?>
				</button>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Disable the style handle for this block type. We use block.json to load the style.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}
}
