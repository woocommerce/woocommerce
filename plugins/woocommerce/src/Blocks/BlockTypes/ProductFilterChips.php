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
		if (
			empty( $block->context['filterData'] ) ||
			empty( $block->context['filterData']['parent'] )
		) {
			return '';
		}

		wp_enqueue_script_module( $this->get_full_block_name() );

		$block_context = $block->context['filterData'];
		$parent        = $block_context['parent'];
		$items         = $block_context['items'] ?? array();
		$classes       = '';
		$style         = '';

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
			'data-wp-interactive' => $this->get_full_block_name(),
			'data-wp-key'         => wp_unique_prefixed_id( $this->get_full_block_name() ),
			'class'               => esc_attr( $classes ),
			'style'               => esc_attr( $style ),
		);

		ob_start();
		?>
		<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="wc-block-product-filter-chips__items" aria-label="<?php echo esc_attr__( 'Filter Options', 'woocommerce' ); ?>">
				<?php foreach ( $items as $item ) { ?>
					<?php
					$item['id'] = $item['id'] ?? uniqid( 'chips-' );
					unset( $item['data'] );
					// translators: %s: item label.
					$aria_label = sprintf( __( 'Filter item: %s', 'woocommerce' ), $item['ariaLabel'] ?? $item['label'] );
					?>
					<button
						data-wp-key="<?php echo esc_attr( $item['id'] ); ?>"
						id="<?php echo esc_attr( $item['id'] ); ?>"
						class="wc-block-product-filter-chips__item"
						type="button"
						aria-label="<?php echo esc_attr( $aria_label ); ?>"
						data-wp-on--click--parent-action="<?php echo esc_attr( $parent . '::actions.toggleFilter' ); ?>"
						value="<?php echo esc_attr( $item['value'] ); ?>"
						data-wp-bind--aria-checked="<?php echo esc_attr( $parent . '::state.isItemSelected' ); ?>"
						data-filter-item="<?php echo esc_attr( wp_json_encode( $item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ) ); ?>"
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
							<?php echo wp_kses_post( $item['label'] ); ?>
						</span>
					</button>
				<?php } ?>
			</div>
			<?php if ( count( $items ) > $show_initially ) : ?>
				<button
					class="wc-block-product-filter-chips__show-more"
					data-wp-bind--hidden="context.showAll"
					data-wp-on--click="actions.showAllItems"
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
	 * Disable the block type script, this uses script modules.
	 *
	 * @param string|null $key The key.
	 *
	 * @return null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}
}
