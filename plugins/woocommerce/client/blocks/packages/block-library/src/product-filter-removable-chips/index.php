<?php
/**
 * Server-side rendering of the `woocommerce/product-filter-removable-chips` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

/**
 * Renders the `woocommerce/product-filter-removable-chips` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block output.
 */
function render_block_woocommerce_product_filter_removable_chips( $attributes, $content, $block ): string {
	unset( $attributes );

	if ( ! $block instanceof WP_Block || empty( $block->context['woocommerce/removableItems'] ) ) {
		return '';
	}

	$filter_items = $block->context['woocommerce/removableItems']['items'] ?? array();
	if ( ! is_array( $filter_items ) ) {
		$filter_items = array();
	}

	$classes   = '';
	$style     = '';
	$processor = new WP_HTML_Tag_Processor( $content );
	if ( $processor->next_tag( array( 'class_name' => 'wc-block-product-filter-removable-chips' ) ) ) {
		$raw_classes = $processor->get_attribute( 'class' );
		$raw_style   = $processor->get_attribute( 'style' );
		$classes     = is_string( $raw_classes ) ? $raw_classes : '';
		$style       = is_string( $raw_style ) ? $raw_style : '';
	}

	$wrapper_attributes = array(
		'data-wp-interactive' => 'woocommerce/product-filters',
		'data-wp-key'         => wp_unique_prefixed_id( 'woocommerce/product-filter-removable-chips' ),
		'class'               => esc_attr( $classes ),
		'style'               => esc_attr( $style ),
	);

	ob_start();
	?>
	<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<ul class="wc-block-product-filter-removable-chips__items">
			<template
				data-wp-each--item="state.removableItems"
				data-wp-each-key="context.item.id"
			>
				<li class="wc-block-product-filter-removable-chips__item">
					<span class="wc-block-product-filter-removable-chips__label" data-wp-text="context.item.label"></span>
					<button
						type="button"
						class="wc-block-product-filter-removable-chips__remove"
						data-wp-bind--aria-label="state.removeItemLabel"
						data-wp-on--click="actions.remove"
					>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="25" height="25" class="wc-block-product-filter-removable-chips__remove-icon" aria-hidden="true" focusable="false"><path d="M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z"></path></svg>
						<span class="screen-reader-text" data-wp-text="state.removeItemLabel"></span>
					</button>
				</li>
			</template>
			<?php foreach ( $filter_items as $item ) : ?>
				<?php
				if ( ! is_array( $item ) || ! isset( $item['label'] ) ) {
					continue;
				}
				$item_label   = (string) $item['label'];
				$remove_label = sprintf(
					/* translators: %s: item label. */
					__( 'Remove filter: %s', 'woocommerce' ),
					$item_label
				);
				?>
				<li class="wc-block-product-filter-removable-chips__item" data-wp-each-child
					<?php echo wp_interactivity_data_wp_context( array( 'item' => $item ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				>
					<span class="wc-block-product-filter-removable-chips__label">
						<?php echo esc_html( $item_label ); ?>
					</span>
					<button
						type="button"
						class="wc-block-product-filter-removable-chips__remove"
						aria-label="<?php echo esc_attr( $remove_label ); ?>"
						data-wp-on--click="actions.remove"
					>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="25" height="25" class="wc-block-product-filter-removable-chips__remove-icon" aria-hidden="true" focusable="false"><path d="M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z"></path></svg>
						<span class="screen-reader-text"><?php echo esc_html( $remove_label ); ?></span>
					</button>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
	$output = ob_get_clean();

	return is_string( $output ) ? $output : '';
}

/**
 * Registers the `woocommerce/product-filter-removable-chips` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_filter_removable_chips(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_filter_removable_chips',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_filter_removable_chips' );
