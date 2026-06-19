<?php
/**
 * Server-side rendering of the `woocommerce/product-filter-rating` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Utils as ProductCollectionUtils;
use Automattic\WooCommerce\Internal\ProductFilters\FilterDataProvider;
use Automattic\WooCommerce\Internal\ProductFilters\QueryClauses;

const WOOCOMMERCE_PRODUCT_FILTER_RATING_QUERY_VAR = 'rating_filter';

/**
 * Prepare the active rating filter items.
 *
 * @since 11.0.0
 *
 * @param array $items  The active filter items.
 * @param array $params The query params parsed from the URL.
 * @return array Active filter items.
 */
function woocommerce_product_filter_rating_prepare_selected_filters( array $items, array $params ): array {
	if ( empty( $params[ WOOCOMMERCE_PRODUCT_FILTER_RATING_QUERY_VAR ] ) ) {
		return $items;
	}

	$active_ratings = array_map( 'absint', explode( ',', (string) $params[ WOOCOMMERCE_PRODUCT_FILTER_RATING_QUERY_VAR ] ) );
	$active_ratings = array_filter(
		$active_ratings,
		function ( int $rating ): bool {
			return $rating > 0 && $rating < 6;
		}
	);
	$active_ratings = array_unique( $active_ratings );

	if ( empty( $active_ratings ) ) {
		return $items;
	}

	foreach ( $active_ratings as $rating ) {
		$items[] = array(
			'type'        => 'rating',
			'value'       => (string) $rating,
			/* translators: %s is referring to rating value. Example: Rated 4 out of 5. */
			'activeLabel' => sprintf( __( 'Rating: Rated %d out of 5', 'woocommerce' ), $rating ),
		);
	}

	return $items;
}

add_filter( 'woocommerce_blocks_product_filters_selected_items', 'woocommerce_product_filter_rating_prepare_selected_filters', 10, 2 );

/**
 * Retrieve the rating filter data for the current block.
 *
 * @since 11.0.0
 *
 * @param WP_Block $block Block instance.
 * @return array<int, array{rating: int, count: int}> Rating count data.
 */
function woocommerce_product_filter_rating_get_counts( WP_Block $block ): array {
	if ( ! isset( $block->context['filterParams'] ) ) {
		return array();
	}

	$query_vars = ProductCollectionUtils::get_query_vars( $block, 1 );

	if ( ! empty( $query_vars['tax_query'] ) ) {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		$query_vars['tax_query'] = ProductCollectionUtils::remove_query_array( $query_vars['tax_query'], WOOCOMMERCE_PRODUCT_FILTER_RATING_QUERY_VAR, true );
	}

	if ( isset( $query_vars['taxonomy'] ) && false !== strpos( (string) $query_vars['taxonomy'], 'pa_' ) ) {
		unset(
			$query_vars['taxonomy'],
			$query_vars['term']
		);
	}

	$container = wc_get_container();
	$counts    = $container->get( FilterDataProvider::class )->with( $container->get( QueryClauses::class ) )->get_rating_counts( $query_vars );
	$data      = array();

	foreach ( $counts as $key => $value ) {
		$data[] = array(
			'rating' => (int) $key,
			'count'  => intval( $value ),
		);
	}

	return $data;
}

/**
 * Renders the `woocommerce/product-filter-rating` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block output.
 */
function render_block_woocommerce_product_filter_rating( array $attributes, string $content, $block ): string {
	if ( is_admin() || wp_doing_ajax() || ! $block instanceof WP_Block ) {
		return '';
	}

	$min_rating    = (float) ( $attributes['minRating'] ?? 0 );
	$rating_counts = woocommerce_product_filter_rating_get_counts( $block );

	$rating_counts_with_min = array_filter(
		$rating_counts,
		function ( array $rating ) use ( $min_rating ): bool {
			return $rating['rating'] >= $min_rating && $rating['rating'] < 6;
		}
	);

	$filter_params = $block->context['filterParams'] ?? array();
	$filter_params = is_array( $filter_params ) ? $filter_params : array();
	$rating_query  = (string) ( $filter_params[ WOOCOMMERCE_PRODUCT_FILTER_RATING_QUERY_VAR ] ?? '' );

	$selected_rating = array_filter( array_map( 'absint', explode( ',', $rating_query ) ) );
	$show_counts     = (bool) ( $attributes['showCounts'] ?? false );
	$filter_options  = array_map(
		function ( array $rating ) use ( $selected_rating, $show_counts ): array {
			$rating_value = (int) $rating['rating'];
			$aria_label   = sprintf(
				/* translators: %1$d is referring to rating value. Example: Rated 4 out of 5. */
				__( 'Rated %1$d out of 5', 'woocommerce' ),
				$rating_value
			);

			$item = array(
				'id'        => 'rating-' . $rating_value,
				'label'     => '',
				'ariaLabel' => $aria_label,
				'value'     => (string) $rating_value,
				'selected'  => in_array( $rating_value, $selected_rating, true ),
				'type'      => 'rating',
			);

			if ( $show_counts ) {
				$item['count'] = $rating['count'];
			}

			return $item;
		},
		$rating_counts_with_min
	);

	$filter_context = array(
		'items'          => array_values( $filter_options ),
		'selectionMode'  => 'multiple',
		'storeNamespace' => 'woocommerce/product-filters',
		'groupLabel'     => __( 'Rating', 'woocommerce' ),
		'filterType'     => 'rating',
	);

	$context_json = wp_json_encode(
		array(
			/* translators: {{label}} is the rating filter item label. */
			'activeLabelTemplate' => __( 'Rating: {{label}}', 'woocommerce' ),
			'filterType'          => 'rating',
			'items'               => array_values( $filter_options ),
		),
		JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
	);

	$wrapper_attributes = array(
		'data-wp-interactive' => 'woocommerce/product-filters',
		'data-wp-key'         => wp_unique_prefixed_id( 'woocommerce/product-filter-rating' ),
		'data-wp-context'     => false === $context_json ? '{}' : $context_json,
	);

	if ( empty( $filter_options ) ) {
		$wrapper_attributes['hidden'] = 'hidden';
		$wrapper_attributes['class']  = 'wc-block-product-filter--hidden';
	}

	$inner_blocks = isset( $block->parsed_block['innerBlocks'] ) && is_array( $block->parsed_block['innerBlocks'] ) ? $block->parsed_block['innerBlocks'] : array();
	$inner_html   = array_reduce(
		$inner_blocks,
		function ( string $carry, array $parsed_block ) use ( $filter_context ): string {
			$carry .= ( new WP_Block( $parsed_block, array( 'woocommerce/selectableItems' => $filter_context ) ) )->render();
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
 * Registers the `woocommerce/product-filter-rating` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_filter_rating(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_filter_rating',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_filter_rating' );
