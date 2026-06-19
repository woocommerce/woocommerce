<?php
/**
 * Server-side rendering of the `woocommerce/product-filter-active` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

/**
 * Renders the `woocommerce/product-filter-active` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block output.
 */
function render_block_woocommerce_product_filter_active( array $attributes, string $content, $block ): string {
	if ( ! $block instanceof WP_Block ) {
		return $content;
	}

	$active_filters = $block->context['activeFilters'] ?? array();

	if ( ! is_array( $active_filters ) ) {
		return $content;
	}

	$removable_items = array_values(
		array_filter(
			array_map(
				function ( $item ) {
					if ( ! is_array( $item ) ) {
						return null;
					}

					$raw_type  = $item['type'] ?? '';
					$raw_value = $item['value'] ?? '';
					$raw_label = $item['activeLabel'] ?? ( $item['label'] ?? '' );

					if ( ! is_scalar( $raw_type ) || ! is_scalar( $raw_value ) || ! is_scalar( $raw_label ) ) {
						return null;
					}

					$type  = (string) $raw_type;
					$value = (string) $raw_value;
					$label = (string) $raw_label;

					if ( '' === $type || '' === $value ) {
						return null;
					}

					return array(
						'id'    => $type . '_' . $value,
						'type'  => $type,
						'value' => $value,
						'label' => $label,
					);
				},
				$active_filters
			)
		)
	);

	$filter_context = array(
		'items'          => $removable_items,
		'storeNamespace' => 'woocommerce/product-filters',
	);

	$context_json = wp_json_encode(
		array(
			'filterType' => 'active',
		),
		JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
	);

	$wrapper_attributes = array(
		'data-wp-interactive'  => 'woocommerce/product-filters',
		'data-wp-key'          => wp_unique_prefixed_id( 'woocommerce/product-filter-active' ),
		'data-wp-context'      => false === $context_json ? '{}' : $context_json,
		'data-wp-bind--hidden' => '!state.hasActiveFilters',
		'data-wp-class--wc-block-product-filter--hidden' => '!state.hasActiveFilters',
	);

	wp_interactivity_state(
		'woocommerce/product-filters',
		array(
			'hasActiveFilters' => ! empty( $removable_items ),
		)
	);

	wp_interactivity_config(
		'woocommerce/product-filters',
		array(
			/* translators:  {{label}} is the label of the active filter item. */
			'removeLabelTemplate' => __( 'Remove filter: {{label}}', 'woocommerce' ),
		)
	);

	$inner_blocks = is_array( $block->parsed_block['innerBlocks'] ?? null ) ? $block->parsed_block['innerBlocks'] : array();
	$inner_html   = array_reduce(
		$inner_blocks,
		function ( string $carry, $parsed_block ) use ( $filter_context ): string {
			if ( ! is_array( $parsed_block ) ) {
				return $carry;
			}

			$carry .= ( new WP_Block( $parsed_block, array( 'woocommerce/removableItems' => $filter_context ) ) )->render();
			return $carry;
		},
		''
	);

	return sprintf(
		'<div %1$s>%2$s</div>',
		get_block_wrapper_attributes( $wrapper_attributes ),
		$inner_html
	);
}

/**
 * Registers the `woocommerce/product-filter-active` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_filter_active(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_filter_active',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_filter_active' );
