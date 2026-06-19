<?php
/**
 * Server-side rendering of the `woocommerce/product-filter-price` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Utils as ProductCollectionUtils;
use Automattic\WooCommerce\Internal\ProductFilters\FilterDataProvider;
use Automattic\WooCommerce\Internal\ProductFilters\QueryClauses;

const WOOCOMMERCE_PRODUCT_FILTER_PRICE_MIN_QUERY_VAR = 'min_price';
const WOOCOMMERCE_PRODUCT_FILTER_PRICE_MAX_QUERY_VAR = 'max_price';

/**
 * Prepare the active price filter item.
 *
 * @since 11.0.0
 *
 * @param array $items  The active filter items.
 * @param array $params The query params parsed from the URL.
 * @return array Active filter items.
 */
function woocommerce_product_filter_price_prepare_selected_filters( array $items, array $params ): array {
	$min_price           = intval( $params[ WOOCOMMERCE_PRODUCT_FILTER_PRICE_MIN_QUERY_VAR ] ?? 0 );
	$max_price           = intval( $params[ WOOCOMMERCE_PRODUCT_FILTER_PRICE_MAX_QUERY_VAR ] ?? 0 );
	$formatted_min_price = $min_price ? html_entity_decode( wp_strip_all_tags( wc_price( $min_price, array( 'decimals' => 0 ) ) ) ) : null;
	$formatted_max_price = $max_price ? html_entity_decode( wp_strip_all_tags( wc_price( $max_price, array( 'decimals' => 0 ) ) ) ) : null;

	if ( null === $formatted_min_price && null === $formatted_max_price ) {
		return $items;
	}

	$item = array(
		'type' => 'price',
	);

	if ( null !== $formatted_min_price && null !== $formatted_max_price ) {
		$item['activeLabel'] = sprintf(
			/* translators: %1$s and %2$s are the formatted minimum and maximum prices respectively. */
			__( 'Price: %1$s - %2$s', 'woocommerce' ),
			$formatted_min_price,
			$formatted_max_price
		);
		$item['value']       = "{$min_price}|{$max_price}";
	}

	if ( null === $formatted_min_price && null !== $formatted_max_price ) {
		/* translators: %s is the formatted maximum price. */
		$item['activeLabel'] = sprintf( __( 'Price: Up to %s', 'woocommerce' ), $formatted_max_price );
		$item['value']       = "|{$max_price}";
	}

	if ( null !== $formatted_min_price && null === $formatted_max_price ) {
		/* translators: %s is the formatted minimum price. */
		$item['activeLabel'] = sprintf( __( 'Price: From %s', 'woocommerce' ), $formatted_min_price );
		$item['value']       = "{$min_price}|";
	}

	$items[] = $item;

	return $items;
}

add_filter( 'woocommerce_blocks_product_filters_selected_items', 'woocommerce_product_filter_price_prepare_selected_filters', 10, 2 );

/**
 * Retrieve the price filter data for the current block.
 *
 * @since 11.0.0
 *
 * @param WP_Block $block Block instance.
 * @return array{min_price?: int, max_price?: int} Price range data.
 */
function woocommerce_product_filter_price_get_filtered_price( WP_Block $block ): array {
	if ( ! isset( $block->context['filterParams'] ) ) {
		return array();
	}

	$query_vars = ProductCollectionUtils::get_query_vars( $block, 1 );

	unset( $query_vars['min_price'], $query_vars['max_price'] );

	if ( ! empty( $query_vars['meta_query'] ) ) {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$query_vars['meta_query'] = ProductCollectionUtils::remove_query_array( $query_vars['meta_query'], 'key', '_price' );
	}

	if ( isset( $query_vars['taxonomy'] ) && false !== strpos( (string) $query_vars['taxonomy'], 'pa_' ) ) {
		unset(
			$query_vars['taxonomy'],
			$query_vars['term']
		);
	}

	$container     = wc_get_container();
	$price_results = $container->get( FilterDataProvider::class )->with( $container->get( QueryClauses::class ) )->get_filtered_price( $query_vars );

	return array(
		'min_price' => intval( floor( floatval( $price_results['min_price'] ?? 0 ) ) ),
		'max_price' => intval( ceil( floatval( $price_results['max_price'] ?? 0 ) ) ),
	);
}

/**
 * Render parsed inner blocks with the optional range input context.
 *
 * @param array      $parsed_blocks  Parsed inner blocks.
 * @param array|null $filter_context Range input context.
 * @return string Rendered inner blocks.
 */
function woocommerce_product_filter_price_render_inner_blocks( array $parsed_blocks, ?array $filter_context = null ): string {
	return array_reduce(
		$parsed_blocks,
		function ( string $carry, $parsed_block ) use ( $filter_context ): string {
			if ( ! is_array( $parsed_block ) ) {
				return $carry;
			}

			$carry .= null === $filter_context
				? render_block( $parsed_block )
				: ( new WP_Block( $parsed_block, array( 'woocommerce/rangeInput' => $filter_context ) ) )->render();
			return $carry;
		},
		''
	);
}

/**
 * Renders the `woocommerce/product-filter-price` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block output.
 */
function render_block_woocommerce_product_filter_price( array $attributes, string $content, $block ): string {
	if ( ! $block instanceof WP_Block || is_admin() || wp_doing_ajax() ) {
		return '';
	}

	$price_range   = woocommerce_product_filter_price_get_filtered_price( $block );
	$min_range     = $price_range['min_price'] ?? 0;
	$max_range     = $price_range['max_price'] ?? 0;
	$filter_params = is_array( $block->context['filterParams'] ?? null ) ? $block->context['filterParams'] : array();
	$min_price     = intval( $filter_params[ WOOCOMMERCE_PRODUCT_FILTER_PRICE_MIN_QUERY_VAR ] ?? $min_range );
	$max_price     = intval( $filter_params[ WOOCOMMERCE_PRODUCT_FILTER_PRICE_MAX_QUERY_VAR ] ?? $max_range );

	$formatted_min_price = html_entity_decode( wp_strip_all_tags( wc_price( $min_price, array( 'decimals' => 0 ) ) ) );
	$formatted_max_price = html_entity_decode( wp_strip_all_tags( wc_price( $max_price, array( 'decimals' => 0 ) ) ) );
	$inner_blocks        = is_array( $block->parsed_block['innerBlocks'] ?? null ) ? $block->parsed_block['innerBlocks'] : array();
	$filter_context      = array(
		'currentMin' => $min_price,
		'currentMax' => $max_price,
		'min'        => $min_range,
		'max'        => $max_range,
	);
	$context_json        = wp_json_encode(
		array(
			'filterType' => 'price',
			'minRange'   => $min_range,
			'maxRange'   => $max_range,
		),
		JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
	);

	$wrapper_attributes = array(
		'data-wp-interactive' => 'woocommerce/product-filters',
		'data-wp-key'         => wp_unique_prefixed_id( 'woocommerce/product-filter-price' ),
		'data-wp-context'     => false === $context_json ? '{}' : $context_json,
	);

	wp_interactivity_config(
		'woocommerce/product-filters',
		array(
			'activePriceLabelTemplates' => array(
				/* translators: {{min}} and {{max}} are the formatted minimum and maximum prices respectively. */
				'minAndMax' => __( 'Price: {{min}} - {{max}}', 'woocommerce' ),
				/* translators: {{max}} is the formatted maximum price. */
				'maxOnly'   => __( 'Price: Up to {{max}}', 'woocommerce' ),
				/* translators: {{min}} is the formatted minimum price. */
				'minOnly'   => __( 'Price: From {{min}}', 'woocommerce' ),
			),
		)
	);

	wp_interactivity_state(
		'woocommerce/product-filters',
		array(
			'formattedMinPrice' => $formatted_min_price,
			'formattedMaxPrice' => $formatted_max_price,
			'minPrice'          => $min_price,
			'maxPrice'          => $max_price,
		)
	);

	if ( $min_range === $max_range || ! $max_range ) {
		$wrapper_attributes['hidden'] = 'hidden';
		$wrapper_attributes['class']  = 'wc-block-product-filter--hidden';

		return sprintf(
			'<div %1$s>%2$s</div>',
			get_block_wrapper_attributes( $wrapper_attributes ),
			woocommerce_product_filter_price_render_inner_blocks( $inner_blocks )
		);
	}

	return sprintf(
		'<div %1$s>%2$s</div>',
		get_block_wrapper_attributes( $wrapper_attributes ),
		woocommerce_product_filter_price_render_inner_blocks( $inner_blocks, $filter_context )
	);
}

/**
 * Registers the `woocommerce/product-filter-price` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_filter_price(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_filter_price',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_filter_price' );
