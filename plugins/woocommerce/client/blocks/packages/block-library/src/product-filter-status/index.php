<?php
/**
 * Server-side rendering of the `woocommerce/product-filter-status` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Utils as ProductCollectionUtils;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Internal\ProductFilters\FilterDataProvider;
use Automattic\WooCommerce\Internal\ProductFilters\QueryClauses;

const WOOCOMMERCE_PRODUCT_FILTER_STATUS_QUERY_VAR = 'filter_stock_status';

/**
 * Prepare the active stock status filter items.
 *
 * @since 11.0.0
 *
 * @param array $items  The active filter items.
 * @param array $params The query params parsed from the URL.
 * @return array Active filter items.
 */
function woocommerce_product_filter_status_prepare_selected_filters( array $items, array $params ): array {
	$status_options = wc_get_product_stock_status_options();

	if ( empty( $params[ WOOCOMMERCE_PRODUCT_FILTER_STATUS_QUERY_VAR ] ) ) {
		return $items;
	}

	$active_statuses = array_filter(
		array_map( 'trim', explode( ',', (string) $params[ WOOCOMMERCE_PRODUCT_FILTER_STATUS_QUERY_VAR ] ) ),
		function ( string $status ) use ( $status_options ): bool {
			return array_key_exists( $status, $status_options );
		}
	);

	if ( empty( $active_statuses ) ) {
		return $items;
	}

	foreach ( $active_statuses as $status ) {
		$items[] = array(
			'type'        => 'status',
			'value'       => $status,
			// translators: %s: status.
			'activeLabel' => sprintf( __( 'Status: %s', 'woocommerce' ), $status_options[ $status ] ),
		);
	}

	return $items;
}

add_filter( 'woocommerce_blocks_product_filters_selected_items', 'woocommerce_product_filter_status_prepare_selected_filters', 10, 2 );

/**
 * Adds stock status settings to the editor asset data registry.
 *
 * @since 11.0.0
 */
function add_block_woocommerce_product_filter_status_asset_data(): void {
	$asset_data_registry = Package::container()->get( AssetDataRegistry::class );

	if ( ! $asset_data_registry->exists( 'stockStatusOptions' ) ) {
		$asset_data_registry->add( 'stockStatusOptions', wc_get_product_stock_status_options() );
	}

	if ( ! $asset_data_registry->exists( 'hideOutOfStockItems' ) ) {
		$asset_data_registry->add( 'hideOutOfStockItems', 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) );
	}
}

add_action( 'enqueue_block_editor_assets', 'add_block_woocommerce_product_filter_status_asset_data' );

/**
 * Retrieve the stock status filter data for the current block.
 *
 * @since 11.0.0
 *
 * @param WP_Block $block Block instance.
 * @return array<int, array{status: string, count: int}> Stock status count data.
 */
function woocommerce_product_filter_status_get_counts( WP_Block $block ): array {
	if ( ! isset( $block->context['filterParams'] ) ) {
		return array();
	}

	$query_vars = ProductCollectionUtils::get_query_vars( $block, 1 );

	unset( $query_vars[ WOOCOMMERCE_PRODUCT_FILTER_STATUS_QUERY_VAR ] );

	if ( isset( $query_vars['taxonomy'] ) && false !== strpos( (string) $query_vars['taxonomy'], 'pa_' ) ) {
		unset(
			$query_vars['taxonomy'],
			$query_vars['term']
		);
	}

	if ( ! empty( $query_vars['meta_query'] ) ) {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$query_vars['meta_query'] = ProductCollectionUtils::remove_query_array( $query_vars['meta_query'], 'key', '_stock_status' );
	}

	$container = wc_get_container();
	$counts    = $container->get( FilterDataProvider::class )->with( $container->get( QueryClauses::class ) )->get_stock_status_counts( $query_vars, array_keys( wc_get_product_stock_status_options() ) );
	$data      = array();

	foreach ( $counts as $key => $value ) {
		$data[] = array(
			'status' => (string) $key,
			'count'  => intval( $value ),
		);
	}

	return array_values(
		array_filter(
			$data,
			function ( array $stock_count ): bool {
				return $stock_count['count'] > 0;
			}
		)
	);
}

/**
 * Renders the `woocommerce/product-filter-status` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block output.
 */
function render_block_woocommerce_product_filter_status( array $attributes, string $content, $block ): string {
	if ( is_admin() || wp_doing_ajax() || ! $block instanceof WP_Block ) {
		return '';
	}

	$stock_status_data       = woocommerce_product_filter_status_get_counts( $block );
	$stock_statuses          = wc_get_product_stock_status_options();
	$filter_params           = $block->context['filterParams'] ?? array();
	$filter_params           = is_array( $filter_params ) ? $filter_params : array();
	$query                   = (string) ( $filter_params[ WOOCOMMERCE_PRODUCT_FILTER_STATUS_QUERY_VAR ] ?? '' );
	$selected_stock_statuses = array_filter( explode( ',', $query ) );
	$show_counts             = (bool) ( $attributes['showCounts'] ?? false );

	$filter_options = array_map(
		function ( array $item ) use ( $stock_statuses, $selected_stock_statuses, $show_counts ): array {
			$status = $item['status'];
			$label  = $stock_statuses[ $status ] ?? $status;
			$option = array(
				'id'        => 'status-' . $status,
				'label'     => $label,
				'ariaLabel' => $label,
				'value'     => $status,
				'selected'  => in_array( $status, $selected_stock_statuses, true ),
				'type'      => 'status',
			);

			if ( $show_counts ) {
				$option['count'] = $item['count'];
			}

			return $option;
		},
		$stock_status_data
	);

	$filter_context = array(
		'items'          => array_values( $filter_options ),
		'selectionMode'  => 'multiple',
		'storeNamespace' => 'woocommerce/product-filters',
		'groupLabel'     => __( 'Status', 'woocommerce' ),
	);

	$context_json = wp_json_encode(
		array(
			/* translators: {{label}} is the status filter item label. */
			'activeLabelTemplate' => __( 'Status: {{label}}', 'woocommerce' ),
			'filterType'          => 'status',
			'items'               => $filter_context['items'],
		),
		JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
	);

	$wrapper_attributes = array(
		'data-wp-interactive' => 'woocommerce/product-filters',
		'data-wp-key'         => wp_unique_prefixed_id( 'woocommerce/product-filter-status' ),
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
 * Registers the `woocommerce/product-filter-status` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_filter_status(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_filter_status',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_filter_status' );
