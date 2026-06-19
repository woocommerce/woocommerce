<?php
/**
 * Server-side rendering of the `woocommerce/product-filter-attribute` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Utils as ProductCollectionUtils;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Internal\ProductAttributes\VisualAttributeTermMeta;
use Automattic\WooCommerce\Internal\ProductFilters\FilterDataProvider;
use Automattic\WooCommerce\Internal\ProductFilters\QueryClauses;

/**
 * Get the attribute with the most terms closest to 30 terms.
 *
 * @since 11.0.0
 *
 * @return object Default attribute object.
 */
function woocommerce_product_filter_attribute_get_default_product_attribute(): object {
	static $cached = null;

	if ( $cached ) {
		return $cached;
	}

	$cached = get_transient( 'wc_block_product_filter_attribute_default_attribute' );

	if (
		$cached &&
		isset( $cached->attribute_id ) &&
		isset( $cached->attribute_name ) &&
		isset( $cached->attribute_label ) &&
		isset( $cached->attribute_type ) &&
		isset( $cached->attribute_orderby ) &&
		isset( $cached->attribute_public ) &&
		'0' !== $cached->attribute_id
	) {
		return $cached;
	}

	$attributes = wc_get_attribute_taxonomies();

	$attributes_count = array_map(
		function ( $attribute ): int {
			$term_count = wp_count_terms(
				array(
					'taxonomy'   => 'pa_' . $attribute->attribute_name,
					'hide_empty' => false,
				)
			);

			return is_wp_error( $term_count ) ? 0 : intval( $term_count );
		},
		$attributes
	);

	asort( $attributes_count );

	$search       = 30;
	$closest      = null;
	$attribute_id = null;

	foreach ( $attributes_count as $id => $count ) {
		if ( null === $closest || abs( $search - $closest ) > abs( $count - $search ) ) {
			$closest      = $count;
			$attribute_id = $id;
		}

		if ( $closest && $count >= $search ) {
			break;
		}
	}

	$default_attribute = (object) array(
		'attribute_id'      => '0',
		'attribute_name'    => 'attribute',
		'attribute_label'   => __( 'Attribute', 'woocommerce' ),
		'attribute_type'    => 'select',
		'attribute_orderby' => 'menu_order',
		'attribute_public'  => 0,
	);

	if ( $attribute_id ) {
		$default_attribute = $attributes[ $attribute_id ];
		set_transient( 'wc_block_product_filter_attribute_default_attribute', $default_attribute, DAY_IN_SECONDS );
	}

	return $default_attribute;
}

/**
 * Delete the cached default attribute ID transient when attribute taxonomies change.
 *
 * @since 11.0.0
 *
 * @param string $transient The transient name.
 */
function woocommerce_product_filter_attribute_delete_default_attribute_id_transient( string $transient ): void {
	if ( 'wc_attribute_taxonomies' === $transient ) {
		delete_transient( 'wc_block_product_filter_attribute_default_attribute' );
	}
}

add_action( 'deleted_transient', 'woocommerce_product_filter_attribute_delete_default_attribute_id_transient' );

/**
 * Prepare the active attribute filter items.
 *
 * @since 11.0.0
 *
 * @param array $items  The active filter items.
 * @param array $params The query param parsed from the URL.
 * @return array Active filter items.
 */
function woocommerce_product_filter_attribute_prepare_selected_filters( array $items, array $params ): array {
	$product_attributes_map = array_reduce(
		wc_get_attribute_taxonomies(),
		function ( array $acc, object $attribute_object ): array {
			$attribute = (array) $attribute_object;

			if ( ! isset( $attribute['attribute_name'], $attribute['attribute_label'] ) ) {
				return $acc;
			}

			$acc[ $attribute['attribute_name'] ] = $attribute['attribute_label'];
			return $acc;
		},
		array()
	);

	$active_attributes = array();
	$all_term_slugs    = array();
	$query_types       = array();

	foreach ( array_keys( $product_attributes_map ) as $attribute_name ) {
		$param_key = "filter_{$attribute_name}";

		if ( empty( $params[ $param_key ] ) || ! is_string( $params[ $param_key ] ) ) {
			continue;
		}

		$term_slugs = array_filter(
			array_map( 'trim', explode( ',', $params[ $param_key ] ) )
		);

		if ( empty( $term_slugs ) ) {
			continue;
		}

		$active_attributes[ "pa_{$attribute_name}" ] = $term_slugs;
		$query_types[ $attribute_name ]              = is_string( $params[ 'query_type_' . $attribute_name ] ?? null ) ? $params[ 'query_type_' . $attribute_name ] : 'or';
		$all_term_slugs                              = array_merge( $all_term_slugs, $term_slugs );
	}

	if ( empty( $active_attributes ) ) {
		return $items;
	}

	$attribute_terms = get_terms(
		array(
			'taxonomy'   => array_keys( $active_attributes ),
			'slug'       => $all_term_slugs,
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $attribute_terms ) || empty( $attribute_terms ) ) {
		return $items;
	}

	foreach ( $attribute_terms as $term_object ) {
		$attribute_name = str_replace( 'pa_', '', $term_object->taxonomy );
		$items[]        = array(
			'type'               => 'attribute/' . $attribute_name,
			'value'              => $term_object->slug,
			'activeLabel'        => sprintf( '%s: %s', $product_attributes_map[ $attribute_name ], $term_object->name ),
			'attributeQueryType' => $query_types[ $attribute_name ] ?? 'or',
		);
	}

	return $items;
}

add_filter( 'woocommerce_blocks_product_filters_selected_items', 'woocommerce_product_filter_attribute_prepare_selected_filters', 10, 2 );

/**
 * Add attribute filter settings to the editor asset data registry.
 *
 * @since 11.0.0
 */
function add_block_woocommerce_product_filter_attribute_asset_data(): void {
	$asset_data_registry = Package::container()->get( AssetDataRegistry::class );

	if ( ! $asset_data_registry->exists( 'defaultProductFilterAttribute' ) ) {
		$asset_data_registry->add( 'defaultProductFilterAttribute', woocommerce_product_filter_attribute_get_default_product_attribute() );
	}
}

add_action( 'enqueue_block_editor_assets', 'add_block_woocommerce_product_filter_attribute_asset_data' );

/**
 * Retrieve the attribute counts for the current block.
 *
 * @since 11.0.0
 *
 * @param WP_Block $block      Block instance.
 * @param string   $slug       Attribute slug.
 * @param string   $query_type Query type, accepting `and` or `or`.
 * @return array<int, int> Attribute counts keyed by term ID.
 */
function woocommerce_product_filter_attribute_get_counts( WP_Block $block, string $slug, string $query_type ): array {
	if ( ! isset( $block->context['filterParams'] ) ) {
		return array();
	}

	$query_vars = ProductCollectionUtils::get_query_vars( $block, 1 );

	if ( 'and' !== strtolower( $query_type ) ) {
		unset( $query_vars[ 'filter_' . str_replace( 'pa_', '', $slug ) ] );
	}

	if ( isset( $query_vars['taxonomy'] ) && false !== strpos( (string) $query_vars['taxonomy'], 'pa_' ) ) {
		unset(
			$query_vars['taxonomy'],
			$query_vars['term']
		);
	}

	if ( ! empty( $query_vars['tax_query'] ) ) {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		$query_vars['tax_query'] = ProductCollectionUtils::remove_query_array( $query_vars['tax_query'], 'taxonomy', $slug );
	}

	$container = wc_get_container();
	$counts    = $container->get( FilterDataProvider::class )->with( $container->get( QueryClauses::class ) )->get_attribute_counts( $query_vars, $slug );
	$data      = array();

	foreach ( $counts as $key => $value ) {
		$data[ $key ] = intval( $value );
	}

	return $data;
}

/**
 * Render parsed inner blocks with the selectable items context.
 *
 * @since 11.0.0
 *
 * @param array $parsed_blocks  Parsed inner blocks.
 * @param array $filter_context Selectable items context.
 * @return string Rendered inner blocks.
 */
function woocommerce_product_filter_attribute_render_inner_blocks( array $parsed_blocks, array $filter_context ): string {
	return array_reduce(
		$parsed_blocks,
		function ( string $carry, $parsed_block ) use ( $filter_context ): string {
			if ( ! is_array( $parsed_block ) ) {
				return $carry;
			}

			$carry .= ( new WP_Block( $parsed_block, array( 'woocommerce/selectableItems' => $filter_context ) ) )->render();
			return $carry;
		},
		''
	);
}

/**
 * Renders the `woocommerce/product-filter-attribute` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block output.
 */
function render_block_woocommerce_product_filter_attribute( array $attributes, string $content, $block ): string {
	if ( empty( $attributes['attributeId'] ) ) {
		$default_product_attribute = (array) woocommerce_product_filter_attribute_get_default_product_attribute();
		$attributes['attributeId'] = $default_product_attribute['attribute_id'] ?? 0;
	}

	if ( is_admin() || wp_doing_ajax() || ! $block instanceof WP_Block || empty( $attributes['attributeId'] ) ) {
		return '';
	}

	$product_attribute = wc_get_attribute( intval( $attributes['attributeId'] ) );

	if ( ! $product_attribute ) {
		return '';
	}

	$query_type       = is_string( $attributes['queryType'] ?? null ) ? $attributes['queryType'] : 'or';
	$attribute_counts = woocommerce_product_filter_attribute_get_counts( $block, $product_attribute->slug, $query_type );
	$hide_empty       = isset( $attributes['hideEmpty'] ) ? (bool) $attributes['hideEmpty'] : true;
	$sort_order       = is_string( $attributes['sortOrder'] ?? null ) ? $attributes['sortOrder'] : 'count-desc';
	$sort_parts       = explode( '-', $sort_order );
	$orderby          = $sort_parts[0] ?? 'name';
	$order            = isset( $sort_parts[1] ) ? strtoupper( $sort_parts[1] ) : 'DESC';

	$args = array(
		'taxonomy' => $product_attribute->slug,
		'orderby'  => $orderby,
		'order'    => $order,
	);

	if ( $hide_empty ) {
		$args['include'] = array_keys( $attribute_counts );
	} else {
		$args['hide_empty'] = false;
	}

	$attribute_terms = get_terms( $args );

	if ( is_wp_error( $attribute_terms ) ) {
		$attribute_terms = array();
	}

	$filter_param_key = 'filter_' . str_replace( 'pa_', '', $product_attribute->slug );
	$filter_params    = is_array( $block->context['filterParams'] ?? null ) ? $block->context['filterParams'] : array();
	$selected_terms   = array();

	if ( $filter_params && ! empty( $filter_params[ $filter_param_key ] ) && is_string( $filter_params[ $filter_param_key ] ) ) {
		$selected_terms = array_filter( explode( ',', $filter_params[ $filter_param_key ] ) );
	}

	$filter_context = array(
		'items'          => array(),
		'selectionMode'  => is_string( $attributes['selectType'] ?? null ) ? $attributes['selectType'] : 'multiple',
		'storeNamespace' => 'woocommerce/product-filters',
		'groupLabel'     => $product_attribute->name,
	);

	if ( ! empty( $attribute_counts ) ) {
		$show_counts         = isset( $attributes['showCounts'] ) ? (bool) $attributes['showCounts'] : false;
		$is_visual_attribute = VisualAttributeTermMeta::is_visual_attribute_taxonomy( $product_attribute->slug );
		$visual_values       = array();

		if ( $is_visual_attribute ) {
			$visual_values = VisualAttributeTermMeta::get_term_visuals( wp_list_pluck( $attribute_terms, 'term_id' ) );
		}

		$attribute_options = array_map(
			function ( $term ) use ( $attribute_counts, $selected_terms, $product_attribute, $show_counts, $is_visual_attribute, $visual_values, $query_type ) {
				$term          = (array) $term;
				$term['count'] = $attribute_counts[ $term['term_id'] ] ?? 0;

				$type = 'attribute/' . str_replace( 'pa_', '', $product_attribute->slug );
				$item = array(
					'id'                 => $type . '-' . $term['slug'],
					'label'              => $term['name'],
					'ariaLabel'          => $term['name'],
					'value'              => $term['slug'],
					'selected'           => in_array( $term['slug'], $selected_terms, true ),
					'type'               => $type,
					'attributeQueryType' => $query_type,
				);

				if ( $show_counts ) {
					$item['count'] = $term['count'];
				}

				if ( $is_visual_attribute ) {
					$item['visual'] = $visual_values[ $term['term_id'] ] ?? VisualAttributeTermMeta::get_empty_visual();
				}

				return $item;
			},
			$attribute_terms
		);

		$filter_context['items'] = array_values( $attribute_options );
	}

	$context_json = wp_json_encode(
		array(
			'activeLabelTemplate' => "$product_attribute->name: {{label}}",
			'filterType'          => 'attribute/' . str_replace( 'pa_', '', $product_attribute->slug ),
			'items'               => $filter_context['items'],
		),
		JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
	);

	$wrapper_attributes = array(
		'data-wp-interactive' => 'woocommerce/product-filters',
		'data-wp-key'         => wp_unique_prefixed_id( 'woocommerce/product-filter-attribute' ),
		'data-wp-context'     => false === $context_json ? '{}' : $context_json,
	);

	if ( empty( $filter_context['items'] ) ) {
		$wrapper_attributes['hidden'] = 'hidden';
		$wrapper_attributes['class']  = 'wc-block-product-filter--hidden';
	}

	$inner_blocks = is_array( $block->parsed_block['innerBlocks'] ?? null ) ? $block->parsed_block['innerBlocks'] : array();

	return sprintf(
		'<div %1$s>%2$s</div>',
		get_block_wrapper_attributes( $wrapper_attributes ),
		woocommerce_product_filter_attribute_render_inner_blocks( $inner_blocks, $filter_context )
	);
}

/**
 * Register pattern for the default product attribute.
 *
 * @since 11.0.0
 */
function woocommerce_product_filter_attribute_register_block_patterns(): void {
	$default_attribute = (array) woocommerce_product_filter_attribute_get_default_product_attribute();
	register_block_pattern(
		'woocommerce/default-attribute-filter',
		array(
			'title'    => '',
			'inserter' => false,
			'content'  => strtr(
				'
<!-- wp:woocommerce/product-filter-attribute {"attributeId":{{attribute_id}}} -->
<div class="wp-block-woocommerce-product-filter-attribute">
	<!-- wp:group {"metadata":{"name":"Header"},"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"level":3} -->
		<h3 class="wp-block-heading">{{attribute_label}}</h3>
		<!-- /wp:heading -->
	<!-- /wp:group -->

	<!-- wp:woocommerce/product-filter-checkbox-list {"lock":{"remove":true}} -->
	<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
	<!-- /wp:woocommerce/product-filter-checkbox-list -->

</div>
<!-- /wp:woocommerce/product-filter-attribute -->
				',
				array(
					'{{attribute_id}}'    => intval( $default_attribute['attribute_id'] ?? 0 ),
					'{{attribute_label}}' => esc_html( (string) ( $default_attribute['attribute_label'] ?? __( 'Attribute', 'woocommerce' ) ) ),
				)
			),
		)
	);
}

add_action( 'wp_loaded', 'woocommerce_product_filter_attribute_register_block_patterns' );

/**
 * Registers the `woocommerce/product-filter-attribute` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_filter_attribute(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_filter_attribute',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_filter_attribute' );
