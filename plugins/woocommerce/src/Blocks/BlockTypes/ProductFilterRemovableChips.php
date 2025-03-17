<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Product Filter: Removable Chips Block.
 */
final class ProductFilterRemovableChips extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-removable-chips';

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

		$context      = $block->context['filterData'];
		$filter_items = $context['items'] ?? array();
		$parent_block = $context['parent'];

		$style = '';

		$tags = new \WP_HTML_Tag_Processor( $content );
		if ( $tags->next_tag( array( 'class_name' => 'wc-block-product-filter-removable-chips' ) ) ) {
			$classes = $tags->get_attribute( 'class' );
			$style   = $tags->get_attribute( 'style' );
		}

		$wrapper_attributes = array(
			'data-wp-interactive' => $this->get_full_block_name(),
			'data-wp-key'         => wp_unique_prefixed_id( $this->get_full_block_name() ),
			'class'               => esc_attr( $classes ),
			'style'               => esc_attr( $style ),
		);

		ob_start();
		?>

		<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<ul class="wc-block-product-filter-removable-chips__items">
				<template
					data-wp-each="state.items"
					data-wp-each-key="context.item.uid"
				>
					<li class="wc-block-product-filter-removable-chips__item">
						<span class="wc-block-product-filter-removable-chips__label" data-wp-text="context.item.label"></span>
						<button
							type="button"
							class="wc-block-product-filter-removable-chips__remove"
							data-wp-bind--aria-label="context.item.removeLabel"
							data-wp-on--click="<?php echo esc_attr( $parent_block . '::actions.removeFilter' ); ?>"
							data-wp-bind--data-filter-item="context.item"
						>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="25" height="25" class="wc-block-product-filter-removable-chips__remove-icon" aria-hidden="true" focusable="false"><path d="M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z"></path></svg>
							<span class="screen-reader-text" data-wp-text="context.item.removeLabel"></span>
						</button>
					</li>
				</template>
				<?php foreach ( $filter_items as $item ) : ?>
					<?php // translators: %s: item label. ?>
					<?php $remove_label = sprintf( __( 'Remove filter: %s', 'woocommerce' ), $item['label'] ); ?>
					<li class="wc-block-product-filter-removable-chips__item" data-wp-each-child>
						<span class="wc-block-product-filter-removable-chips__label">
							<?php echo esc_html( $item['label'] ); ?>
						</span>
						<button
							type="button"
							class="wc-block-product-filter-removable-chips__remove"
							aria-label="<?php echo esc_attr( $remove_label ); ?>"
							data-wp-on--click="<?php echo esc_attr( $parent_block . '::actions.removeFilter' ); ?>"
							data-filter-item="<?php echo esc_attr( wp_json_encode( $item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ) ); ?>"
						>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="25" height="25" class="wc-block-product-filter-removable-chips__remove-icon" aria-hidden="true" focusable="false"><path d="M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z"></path></svg>
							<span class="screen-reader-text"><?php echo esc_html( $remove_label ); ?></span>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
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
