<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Utils as ProductCollectionUtils;
use Automattic\WooCommerce\Internal\ProductFilters\FilterDataProvider;
use Automattic\WooCommerce\Internal\ProductFilters\QueryClauses;

/**
 * Product Filter: Attribute Block.
 */
final class ProductFilterAttribute extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-attribute';

	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 */
	protected function initialize() {
		parent::initialize();

		add_filter( 'woocommerce_blocks_product_filters_selected_items', array( $this, 'prepare_selected_filters' ), 10, 2 );
		add_action( 'deleted_transient', array( $this, 'delete_default_attribute_id_transient' ) );
		add_action( 'wp_loaded', array( $this, 'register_block_patterns' ) );
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = array() ) {
		parent::enqueue_data( $attributes );

		if ( is_admin() ) {
			$this->asset_data_registry->add( 'defaultProductFilterAttribute', $this->get_default_product_attribute() );
		}
	}

	/**
	 * Delete the default attribute id transient when the attribute taxonomies are deleted.
	 *
	 * @param string $transient The transient name.
	 */
	public function delete_default_attribute_id_transient( $transient ) {
		if ( 'wc_attribute_taxonomies' === $transient ) {
			delete_transient( 'wc_block_product_filter_attribute_default_attribute' );
		}
	}



	/**
	 * Prepare the active filter items.
	 *
	 * @param array $items  The active filter items.
	 * @param array $params The query param parsed from the URL.
	 * @return array Active filters items.
	 */
	public function prepare_selected_filters( $items, $params ) {
		$product_attributes_map = array_reduce(
			wc_get_attribute_taxonomies(),
			function ( $acc, $attribute_object ) {
				$acc[ $attribute_object->attribute_name ] = $attribute_object->attribute_label;
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

			// Filter out empty slugs and trim whitespace.
			$term_slugs = array_filter(
				array_map( 'trim', explode( ',', $params[ $param_key ] ) ),
			);

			if ( empty( $term_slugs ) ) {
				continue;
			}

			$active_attributes[ "pa_{$attribute_name}" ] = $term_slugs;
			$query_types[ $attribute_name ]              = $params[ 'query_type_' . $attribute_name ] ?? 'or';
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

	/**
	 * Render the block.
	 *
	 * @param array    $block_attributes Block attributes.
	 * @param string   $content          Block content.
	 * @param WP_Block $block            Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $block_attributes, $content, $block ) {
		if ( empty( $block_attributes['attributeId'] ) ) {
			$default_product_attribute       = $this->get_default_product_attribute();
			$block_attributes['attributeId'] = $default_product_attribute->attribute_id;
		}

		// don't render if its admin, or ajax in progress.
		if ( is_admin() || wp_doing_ajax() || empty( $block_attributes['attributeId'] ) ) {
			return '';
		}

		$product_attribute = wc_get_attribute( $block_attributes['attributeId'] );
		$attribute_counts  = $this->get_attribute_counts( $block, $product_attribute->slug, $block_attributes['queryType'] );
		$hide_empty        = $block_attributes['hideEmpty'] ?? true;
		$orderby           = $block_attributes['sortOrder'] ? explode( '-', $block_attributes['sortOrder'] )[0] : 'name';
		$order             = $block_attributes['sortOrder'] ? strtoupper( explode( '-', $block_attributes['sortOrder'] )[1] ) : 'DESC';

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

		$filter_param_key = 'filter_' . str_replace( 'pa_', '', $product_attribute->slug );
		$filter_params    = $block->context['filterParams'] ?? array();
		$selected_terms   = array();

		if ( $filter_params && ! empty( $filter_params[ $filter_param_key ] ) && is_string( $filter_params[ $filter_param_key ] ) ) {
			$selected_terms = array_filter( explode( ',', $filter_params[ $filter_param_key ] ) );
		}

		$filter_context = array(
			'showCounts' => $block_attributes['showCounts'] ?? false,
			'items'      => array(),
			'groupLabel' => $product_attribute->name,
		);

		if ( ! empty( $attribute_counts ) ) {
			$attribute_options = array_map(
				function ( $term ) use ( $block_attributes, $attribute_counts, $selected_terms, $product_attribute ) {
					$term          = (array) $term;
					$term['count'] = $attribute_counts[ $term['term_id'] ] ?? 0;

					return array(
						'label'              => $term['name'],
						'value'              => $term['slug'],
						'selected'           => in_array( $term['slug'], $selected_terms, true ),
						'count'              => $term['count'],
						'type'               => 'attribute/' . str_replace( 'pa_', '', $product_attribute->slug ),
						'attributeQueryType' => $block_attributes['queryType'],
					);
				},
				$attribute_terms
			);

			$filter_context['items'] = $attribute_options;
		}

		$wrapper_attributes = array(
			'data-wp-interactive' => 'woocommerce/product-filters',
			'data-wp-key'         => wp_unique_prefixed_id( $this->get_full_block_name() ),
			'data-wp-context'     => wp_json_encode(
				array(
					'activeLabelTemplate' => "$product_attribute->name: {{label}}",
					'filterType'          => 'attribute/' . str_replace( 'pa_', '', $product_attribute->slug ),
				),
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
			),
		);

		if ( empty( $filter_context['items'] ) ) {
			$wrapper_attributes['hidden'] = true;
			$wrapper_attributes['class']  = 'wc-block-product-filter--hidden';
		}

		return sprintf(
			'<div %1$s>%2$s</div>',
			get_block_wrapper_attributes( $wrapper_attributes ),
			array_reduce(
				$block->parsed_block['innerBlocks'],
				function ( $carry, $parsed_block ) use ( $filter_context ) {
					$carry .= ( new \WP_Block( $parsed_block, array( 'filterData' => $filter_context ) ) )->render();
					return $carry;
				},
				''
			)
		);
	}

	/**
	 * Retrieve the attribute count for current block.
	 *
	 * @param WP_Block $block      Block instance.
	 * @param string   $slug       Attribute slug.
	 * @param string   $query_type Query type, accept 'and' or 'or'.
	 */
	private function get_attribute_counts( $block, $slug, $query_type ) {
		if ( ! isset( $block->context['filterParams'] ) ) {
			return array();
		}

		$query_vars = ProductCollectionUtils::get_query_vars( $block, 1 );

		if ( 'and' !== strtolower( $query_type ) ) {
			unset( $query_vars[ 'filter_' . str_replace( 'pa_', '', $slug ) ] );
		}

		if ( isset( $query_vars['taxonomy'] ) && false !== strpos( $query_vars['taxonomy'], 'pa_' ) ) {
			unset(
				$query_vars['taxonomy'],
				$query_vars['term']
			);
		}

		if ( ! empty( $query_vars['tax_query'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			$query_vars['tax_query'] = ProductCollectionUtils::remove_query_array( $query_vars['tax_query'], 'taxonomy', $slug );
		}

		$container        = wc_get_container();
		$counts           = $container->get( FilterDataProvider::class )->with( $container->get( QueryClauses::class ) )->get_attribute_counts( $query_vars, $slug );
		$attribute_counts = array();

		foreach ( $counts as $key => $value ) {
			$attribute_counts[] = array(
				'term'  => $key,
				'count' => intval( $value ),
			);
		}

		$attribute_counts = array_reduce(
			$attribute_counts,
			function ( $acc, $count ) {
				$acc[ $count['term'] ] = $count['count'];
				return $acc;
			},
			array()
		);

		return $attribute_counts;
	}

	/**
	 * Get the attribute if with most term but closest to 30 terms.
	 *
	 * @return object
	 */
	private function get_default_product_attribute() {
		// Cache this variable in memory to prevent repeated database queries to check
		// for transient in the same request.
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
			function ( $attribute ) {
				return intval(
					wp_count_terms(
						array(
							'taxonomy'   => 'pa_' . $attribute->attribute_name,
							'hide_empty' => false,
						)
					)
				);
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
	 * Register pattern for default product attribute.
	 */
	public function register_block_patterns() {
		$default_attribute = $this->get_default_product_attribute();
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
						'{{attribute_id}}'    => intval( $default_attribute->attribute_id ),
						'{{attribute_label}}' => esc_html( $default_attribute->attribute_label ),
					)
				),
			)
		);
	}

	/**
	 * Disable the editor style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_editor_style() {
		return null;
	}

	/**
	 * Disable the script handle for this block type. We use block.json to load the script.
	 *
	 * @param string|null $key The key of the script to get.
	 * @return null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}
}
