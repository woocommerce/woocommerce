<?php
/**
 * Server-side rendering of the `woocommerce/product-filter-taxonomy` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Utils as ProductCollectionUtils;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Internal\ProductFilters\FilterDataProvider;
use Automattic\WooCommerce\Internal\ProductFilters\Params;
use Automattic\WooCommerce\Internal\ProductFilters\QueryClauses;
use Automattic\WooCommerce\Internal\ProductFilters\TaxonomyHierarchyData;

/**
 * Prepare the active taxonomy filter items.
 *
 * @since 11.0.0
 *
 * @param array $items  The active filter items.
 * @param array $params The query param parsed from the URL.
 * @return array Active filters items.
 */
function woocommerce_product_filter_taxonomy_prepare_selected_filters( array $items, array $params ): array {
	$container      = wc_get_container();
	$params_handler = $container->get( Params::class );

	$taxonomy_params   = $params_handler->get_param( 'taxonomy' );
	$active_taxonomies = array();
	$all_term_slugs    = array();

	foreach ( $taxonomy_params as $taxonomy_slug => $param_key ) {
		if ( ! empty( $params[ $param_key ] ) && is_string( $params[ $param_key ] ) ) {
			$term_slugs                          = array_map( 'sanitize_title', explode( ',', $params[ $param_key ] ) );
			$active_taxonomies[ $taxonomy_slug ] = $term_slugs;
			$all_term_slugs                      = array_merge( $all_term_slugs, $term_slugs );
		}
	}

	if ( empty( $active_taxonomies ) ) {
		return $items;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => array_keys( $active_taxonomies ),
			'slug'       => array_unique( $all_term_slugs ),
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return $items;
	}

	foreach ( $terms as $term ) {
		$taxonomy_object = get_taxonomy( $term->taxonomy );
		if ( $taxonomy_object ) {
			$items[] = array(
				'type'        => 'taxonomy/' . $term->taxonomy,
				'value'       => $term->slug,
				'activeLabel' => $taxonomy_object->labels->singular_name . ': ' . $term->name,
			);
		}
	}

	return $items;
}

add_filter( 'woocommerce_blocks_product_filters_selected_items', 'woocommerce_product_filter_taxonomy_prepare_selected_filters', 10, 2 );

/**
 * Register the REST field exposing term menu order for sortable taxonomies.
 *
 * @since 11.0.0
 */
function woocommerce_product_filter_taxonomy_register_taxonomy_menu_order_rest_field(): void {
	/**
	 * Filters the list of taxonomies that support custom ordering. Filter was introduced long
	 * ago is only documented in 10.6.0.
	 *
	 * First instance in plugins/woocommerce/includes/admin/class-wc-admin-assets.php.
	 *
	 * @since 1.0
	 *
	 * @param array $sortable_taxonomies List of taxonomy slugs that support custom ordering.
	 * @return array List of taxonomy slugs that support custom ordering.
	 */
	$sortable_taxonomies = apply_filters( 'woocommerce_sortable_taxonomies', array( 'product_cat' ) );

	foreach ( $sortable_taxonomies as $taxonomy ) {
		register_rest_field(
			$taxonomy,
			'menu_order',
			array(
				'get_callback' => function ( $term ) {
					$menu_order = get_term_meta( $term['id'], 'order', true );
					return is_numeric( $menu_order ) ? (int) $menu_order : 0;
				},
				'schema'       => array(
					'description' => __( 'Menu order, used to custom sort the term.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);
	}
}

/**
 * Get product taxonomies for the editor settings payload.
 *
 * @since 11.0.0
 *
 * @return array<int, array{label: string, name: string}> Taxonomy data.
 */
function woocommerce_product_filter_taxonomy_get_taxonomies(): array {
	$container       = wc_get_container();
	$params_handler  = $container->get( Params::class );
	$taxonomy_params = $params_handler->get_param( 'taxonomy' );
	$taxonomy_data   = array();

	foreach ( array_keys( $taxonomy_params ) as $taxonomy_slug ) {
		$taxonomy = get_taxonomy( $taxonomy_slug );

		if ( ! $taxonomy ) {
			continue;
		}

		$taxonomy_data[] = array(
			'label' => $taxonomy->labels->singular_name,
			'name'  => $taxonomy->name,
		);
	}

	return $taxonomy_data;
}

/**
 * Add taxonomy filter settings to the editor asset data registry.
 *
 * @since 11.0.0
 */
function add_block_woocommerce_product_filter_taxonomy_asset_data(): void {
	$asset_data_registry = Package::container()->get( AssetDataRegistry::class );

	if ( ! $asset_data_registry->exists( 'filterableProductTaxonomies' ) ) {
		$asset_data_registry->add( 'filterableProductTaxonomies', woocommerce_product_filter_taxonomy_get_taxonomies() );
	}

	if ( ! $asset_data_registry->exists( 'sortableTaxonomies' ) ) {
		/**
		 * Filters the list of taxonomies that support custom ordering. Filter was introduced long
		 * ago is only documented in 10.6.0.
		 *
		 * First instance in plugins/woocommerce/includes/admin/class-wc-admin-assets.php.
		 *
		 * @since 1.0
		 *
		 * @param array $sortable_taxonomies List of taxonomy slugs that support custom ordering.
		 * @return array List of taxonomy slugs that support custom ordering.
		 */
		$asset_data_registry->add( 'sortableTaxonomies', apply_filters( 'woocommerce_sortable_taxonomies', array( 'product_cat' ) ) );
	}
}

add_action( 'enqueue_block_editor_assets', 'add_block_woocommerce_product_filter_taxonomy_asset_data' );

/**
 * Retrieve taxonomy term counts for the current block.
 *
 * @since 11.0.0
 *
 * @param WP_Block $block    Block instance.
 * @param string   $taxonomy Taxonomy slug.
 * @return array<int, int> Term counts with term ID as key.
 */
function woocommerce_product_filter_taxonomy_get_term_counts( WP_Block $block, string $taxonomy ): array {
	if ( ! isset( $block->context['filterParams'] ) ) {
		return array();
	}

	$query_vars = ProductCollectionUtils::get_query_vars( $block, 1 );

	$container       = wc_get_container();
	$params_handler  = $container->get( Params::class );
	$taxonomy_params = $params_handler->get_param( 'taxonomy' );

	if ( isset( $taxonomy_params[ $taxonomy ] ) ) {
		$param_key = $taxonomy_params[ $taxonomy ];
		unset( $query_vars[ $param_key ] );
	}

	if ( isset( $query_vars['taxonomy'] ) && false !== strpos( (string) $query_vars['taxonomy'], 'pa_' ) ) {
		unset(
			$query_vars['taxonomy'],
			$query_vars['term']
		);
	}

	if ( ! empty( $query_vars['tax_query'] ) ) {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		$query_vars['tax_query'] = ProductCollectionUtils::remove_query_array( $query_vars['tax_query'], 'taxonomy', $taxonomy );
	}

	return $container->get( FilterDataProvider::class )->with( $container->get( QueryClauses::class ) )->get_taxonomy_counts( $query_vars, $taxonomy );
}

/**
 * Sort terms by the specified criteria.
 *
 * @since 11.0.0
 *
 * @param array  $terms           Array of term objects or arrays to sort.
 * @param string $orderby         Sort field.
 * @param string $order           Sort direction.
 * @param array  $taxonomy_counts Context-aware term counts.
 * @return array Sorted terms.
 */
function woocommerce_product_filter_taxonomy_sort_terms_by_criteria( array $terms, string $orderby, string $order, array $taxonomy_counts ): array {
	$sort_order = 'DESC' === strtoupper( $order ) ? -1 : 1;

	usort(
		$terms,
		function ( $a, $b ) use ( $orderby, $sort_order, $taxonomy_counts ) {
			$a = (object) $a;
			$b = (object) $b;

			switch ( $orderby ) {
				case 'count':
					$count_a    = $taxonomy_counts[ $a->term_id ] ?? 0;
					$count_b    = $taxonomy_counts[ $b->term_id ] ?? 0;
					$comparison = $count_a <=> $count_b;
					break;

				case 'menu_order':
					$order_a    = $a->menu_order ?? 0;
					$order_b    = $b->menu_order ?? 0;
					$comparison = $order_a <=> $order_b;
					if ( 0 === $comparison ) {
						$comparison = strcasecmp( $a->name, $b->name );
					}
					break;

				case 'name':
				default:
					$comparison = strcasecmp( $a->name, $b->name );
					break;
			}

			return $comparison * $sort_order;
		}
	);

	return $terms;
}

/**
 * Sort hierarchical terms recursively while keeping parent-child relationships.
 *
 * @since 11.0.0
 *
 * @param array  $terms           Hierarchical terms array with children.
 * @param string $orderby         Sort field.
 * @param string $order           Sort direction.
 * @param array  $taxonomy_counts Context-aware term counts.
 * @return array Sorted hierarchical terms.
 */
function woocommerce_product_filter_taxonomy_sort_hierarchy_terms( array $terms, string $orderby, string $order, array $taxonomy_counts ): array {
	foreach ( $terms as $term ) {
		if ( ! empty( $term['children'] ) && is_array( $term['children'] ) ) {
			$term['children'] = woocommerce_product_filter_taxonomy_sort_terms_by_criteria( $term['children'], $orderby, $order, $taxonomy_counts );
		}
	}

	return woocommerce_product_filter_taxonomy_sort_terms_by_criteria( $terms, $orderby, $order, $taxonomy_counts );
}

/**
 * Flatten a hierarchical term tree into a depth-first array.
 *
 * @since 11.0.0
 *
 * @param array $terms       Hierarchical terms with children structure.
 * @param array $result      Reference to result array being built.
 * @param array $visited_ids Reference to array tracking visited term IDs.
 * @param int   $depth       Current recursion depth.
 */
function woocommerce_product_filter_taxonomy_flatten_terms_list( array $terms, array &$result, array &$visited_ids = array(), int $depth = 0 ): void {
	if ( $depth > 10 ) {
		return;
	}

	foreach ( $terms as $term ) {
		if ( ! is_array( $term ) || ! isset( $term['term_id'] ) ) {
			continue;
		}

		$term_id = $term['term_id'];

		if ( isset( $visited_ids[ $term_id ] ) ) {
			continue;
		}

		$visited_ids[ $term_id ] = true;
		$result[ $term_id ]      = $term;

		if ( ! empty( $term['children'] ) && is_array( $term['children'] ) ) {
			woocommerce_product_filter_taxonomy_flatten_terms_list( $term['children'], $result, $visited_ids, $depth + 1 );
			unset( $result[ $term_id ]['children'] );
		}
	}
}

/**
 * Get taxonomy terms ordered hierarchically.
 *
 * @since 11.0.0
 *
 * @param string $taxonomy        Taxonomy slug.
 * @param array  $taxonomy_counts Term counts with term ID as key.
 * @param bool   $hide_empty      Whether to hide empty terms.
 * @param string $orderby         Sort field for siblings.
 * @param string $order           Sort direction.
 * @return array Hierarchically ordered terms.
 */
function woocommerce_product_filter_taxonomy_get_hierarchical_terms( string $taxonomy, array $taxonomy_counts, bool $hide_empty, string $orderby, string $order ): array {
	$container      = wc_get_container();
	$hierarchy_data = $container->get( TaxonomyHierarchyData::class )->get_hierarchy_map( $taxonomy );
	$tree           = is_array( $hierarchy_data['tree'] ?? null ) ? $hierarchy_data['tree'] : array();
	$sorted_terms   = woocommerce_product_filter_taxonomy_sort_hierarchy_terms( $tree, $orderby, $order, $taxonomy_counts );
	$flat_list      = array();

	woocommerce_product_filter_taxonomy_flatten_terms_list( $sorted_terms, $flat_list );

	if ( ! $hide_empty ) {
		return $flat_list;
	}

	return array_filter(
		$flat_list,
		function ( $term ) use ( $taxonomy_counts ) {
			return is_array( $term ) && ! empty( $taxonomy_counts[ $term['term_id'] ] );
		}
	);
}

/**
 * Get terms sorted based on taxonomy type.
 *
 * @since 11.0.0
 *
 * @param string $taxonomy        Taxonomy slug.
 * @param array  $taxonomy_counts Term counts with term ID as key.
 * @param bool   $hide_empty      Whether to hide empty terms.
 * @param string $orderby         Sort field.
 * @param string $order           Sort direction.
 * @return array Sorted terms array.
 */
function woocommerce_product_filter_taxonomy_get_sorted_terms( string $taxonomy, array $taxonomy_counts, bool $hide_empty, string $orderby, string $order ): array {
	if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
		$args = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		);

		if ( $hide_empty ) {
			$args['include'] = array_keys( $taxonomy_counts );
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		if ( 'menu_order' === $orderby ) {
			update_termmeta_cache( wp_list_pluck( $terms, 'term_id' ) );
			$terms = array_map(
				function ( $term ) {
					$term               = (array) $term;
					$menu_order         = get_term_meta( $term['term_id'], 'order', true );
					$term['menu_order'] = is_numeric( $menu_order ) ? (int) $menu_order : 0;
					return (object) $term;
				},
				$terms
			);
		}

		return woocommerce_product_filter_taxonomy_sort_terms_by_criteria( $terms, $orderby, $order, $taxonomy_counts );
	}

	return woocommerce_product_filter_taxonomy_get_hierarchical_terms( $taxonomy, $taxonomy_counts, $hide_empty, $orderby, $order );
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
function woocommerce_product_filter_taxonomy_render_inner_blocks( array $parsed_blocks, array $filter_context ): string {
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
 * Renders the `woocommerce/product-filter-taxonomy` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block output.
 */
function render_block_woocommerce_product_filter_taxonomy( array $attributes, string $content, $block ): string {
	if ( is_admin() || wp_doing_ajax() || ! $block instanceof WP_Block ) {
		return '';
	}

	$taxonomy = is_string( $attributes['taxonomy'] ?? null ) && '' !== $attributes['taxonomy'] ? $attributes['taxonomy'] : 'product_cat';

	$taxonomy_object = get_taxonomy( $taxonomy );

	if ( ! $taxonomy_object || ! taxonomy_exists( $taxonomy ) ) {
		return '';
	}

	$container       = wc_get_container();
	$params_handler  = $container->get( Params::class );
	$taxonomy_params = $params_handler->get_param( 'taxonomy' );

	if ( ! isset( $taxonomy_params[ $taxonomy ] ) ) {
		return '';
	}

	wp_interactivity_config(
		'woocommerce/product-filters',
		array(
			'taxonomyParamsMap' => $taxonomy_params,
		)
	);

	$filter_context  = array(
		'items'          => array(),
		'selectionMode'  => 'multiple',
		'storeNamespace' => 'woocommerce/product-filters',
		'groupLabel'     => $taxonomy_object->labels->singular_name,
	);
	$taxonomy_counts = woocommerce_product_filter_taxonomy_get_term_counts( $block, $taxonomy );

	if ( ! empty( $taxonomy_counts ) ) {
		$hide_empty = isset( $attributes['hideEmpty'] ) ? (bool) $attributes['hideEmpty'] : true;
		$sort_order = is_string( $attributes['sortOrder'] ?? null ) ? $attributes['sortOrder'] : 'count-desc';
		$sort_parts = explode( '-', $sort_order );
		$orderby    = $sort_parts[0] ?? 'name';
		$order      = isset( $sort_parts[1] ) ? strtoupper( $sort_parts[1] ) : 'DESC';

		$taxonomy_terms = woocommerce_product_filter_taxonomy_get_sorted_terms( $taxonomy, $taxonomy_counts, $hide_empty, $orderby, $order );

		$filter_params  = is_array( $block->context['filterParams'] ?? null ) ? $block->context['filterParams'] : array();
		$selected_terms = array();
		$param_key      = $taxonomy_params[ $taxonomy ];

		if ( $filter_params && ! empty( $filter_params[ $param_key ] ) && is_string( $filter_params[ $param_key ] ) ) {
			$selected_terms = array_filter( array_map( 'sanitize_title', explode( ',', $filter_params[ $param_key ] ) ) );
		}

		$show_counts      = isset( $attributes['showCounts'] ) ? (bool) $attributes['showCounts'] : false;
		$taxonomy_options = array_map(
			function ( $term ) use ( $taxonomy_counts, $selected_terms, $taxonomy, $show_counts ) {
				$term          = (array) $term;
				$term['count'] = $taxonomy_counts[ $term['term_id'] ] ?? 0;

				$type   = 'taxonomy/' . $taxonomy;
				$option = array(
					'id'        => $type . '-' . $term['slug'],
					'label'     => $term['name'],
					'ariaLabel' => $term['name'],
					'value'     => $term['slug'],
					'selected'  => in_array( $term['slug'], $selected_terms, true ),
					'type'      => $type,
				);

				if ( $show_counts ) {
					$option['count'] = $term['count'];
				}

				if ( is_taxonomy_hierarchical( $taxonomy ) ) {
					$option['termId'] = $term['term_id'];

					if ( isset( $term['depth'] ) && $term['depth'] > 0 ) {
						$option['depth'] = $term['depth'];
					}
					if ( isset( $term['parent'] ) && $term['parent'] > 0 ) {
						$option['parent'] = $term['parent'];
					}
				}

				return $option;
			},
			$taxonomy_terms
		);

		$filter_context['items'] = array_values( $taxonomy_options );
	}

	$context_json = wp_json_encode(
		array(
			'activeLabelTemplate' => $taxonomy_object->labels->singular_name . ': {{label}}',
			'filterType'          => 'taxonomy/' . $taxonomy,
			'items'               => $filter_context['items'],
		),
		JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
	);

	$wrapper_attributes = array(
		'data-wp-interactive' => 'woocommerce/product-filters',
		'data-wp-key'         => wp_unique_prefixed_id( 'woocommerce/product-filter-taxonomy' ),
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
		woocommerce_product_filter_taxonomy_render_inner_blocks( $inner_blocks, $filter_context )
	);
}

/**
 * Registers the `woocommerce/product-filter-taxonomy` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_filter_taxonomy(): void {
	woocommerce_product_filter_taxonomy_register_taxonomy_menu_order_rest_field();

	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_filter_taxonomy',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_filter_taxonomy' );
