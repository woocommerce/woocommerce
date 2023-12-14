<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\CollectionFilterer;
use Automattic\WooCommerce\Blocks\Package;

/**
 * CollectionFilters class.
 */
final class CollectionFilters extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'collection-filters';

	/**
	 * Cache the current response from the API.
	 *
	 * @var array
	 */
	private $current_response = null;

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string|null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 */
	protected function initialize() {
		parent::initialize();
		add_action( 'render_block_context', array( $this, 'modify_inner_blocks_context' ), 10, 3 );
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( is_admin() ) {
			return $content;
		}

		/**
		 * At this point, WP starts rendering the Collection Filters block,
		 * we can safely unset the current response.
		 */
		$this->current_response = null;

		$attributes_data = array(
			'data-wc-interactive' => wp_json_encode( array( 'namespace' => 'woocommerce/collection-filters' ) ),
			'class'               => 'wc-block-collection-filters',
		);

		if ( ! isset( $block->context['queryId'] ) ) {
			$attributes_data['data-wc-navigation-id'] = sprintf(
				'wc-collection-filters-%s',
				md5( wp_json_encode( $block->parsed_block['innerBlocks'] ) )
			);
		}

		return sprintf(
			'<nav %1$s>%2$s</nav>',
			get_block_wrapper_attributes( $attributes_data ),
			$content
		);
	}

	/**
	 * Modify the context of inner blocks.
	 *
	 * @param array    $context The block context.
	 * @param array    $parsed_block The parsed block.
	 * @param WP_Block $parent_block The parent block.
	 * @return array
	 */
	public function modify_inner_blocks_context( $context, $parsed_block, $parent_block ) {
		if ( is_admin() || ! is_a( $parent_block, 'WP_Block' ) ) {
			return $context;
		}

		/**
		 * When the first direct child of Collection Filters is rendering, we
		 * hydrate and cache the collection data response.
		 */
		if (
			"woocommerce/{$this->block_name}" === $parent_block->name &&
			! isset( $this->current_response )
		) {
			$this->current_response = $this->get_aggregated_collection_data( $parent_block );
		}

		if ( empty( $this->current_response ) ) {
			return $context;
		}

		/**
		 * Filter blocks use the collectionData context, so we only update that
		 * specific context with fetched data.
		 */
		if ( isset( $context['collectionData'] ) ) {
			$context['collectionData'] = $this->current_response;
		}

		return $context;
	}

	/**
	 * Get the aggregated collection data from the API.
	 * Loop through inner blocks and build a query string to pass to the API.
	 *
	 * @param WP_Block $block The block instance.
	 * @return array
	 */
	private function get_aggregated_collection_data( $block ) {
		$collection_data_params = $this->get_inner_collection_data_params( $block->inner_blocks );

		if ( empty( array_filter( $collection_data_params ) ) ) {
			return array();
		}

		$data    = [
			'min_price'           => null,
			'max_price'           => null,
			'attribute_counts'    => null,
			'stock_status_counts' => null,
			'rating_counts'       => null,
		];

		$filters = Package::container()->get( CollectionFilterer::class );

		$query_vars = array();

		if ( ! empty($block->context['query'] ) && ! $block->context['query']['inherit'] ) {
			$query_vars = build_query_vars_from_query_block( $block, 1 );
		} else {
			global $wp_query;
			$query_vars = array_filter( $wp_query->query_vars );
		}

		$query_vars['fields'] = 'ids';

		if ( ! empty( $collection_data_params['calculate_price_range'] ) ) {
			$filter_query_vars = $query_vars;
			unset( $filter_query_vars['min_price'], $filter_query_vars['max_price'] );

			$price_results     = $filters->get_filtered_price( $query_vars );
			$data['price_range'] = array(
			'min_price' => intval(floor( $price_results->min_price )),
			'max_price' => intval(ceil( $price_results->max_price )),
			);
		}

		if ( ! empty( $collection_data_params['calculate_stock_status_counts'] ) ) {
			$filter_query_vars = $query_vars;
			unset( $filter_query_vars['filter_stock_status'] );

			$counts = $filters->get_stock_status_counts( $filter_query_vars );

			$data['stock_status_counts'] = [];

			foreach ( $counts as $key => $value ) {
				$data['stock_status_counts'][] = array(
					'status' => $key,
					'count'  => $value,
				);
			}
		}

		if ( ! empty( $collection_data_params['calculate_rating_counts'] ) ) {
			// Regenerate the products query vars without rating filter.
			$filter_query_vars = $query_vars;

			if ( ! empty( $filter_query_vars['tax_query'] ) ) {
				foreach ( $filter_query_vars['tax_query'] as $key => $tax_query ) {
					if ( isset( $tax_query['rating_filter'] ) && $tax_query['rating_filter'] ) {
						unset( $filter_query_vars['tax_query'][$key] );
					}
				}
			}

			$counts                = $filters->get_rating_counts( $filter_query_vars );
			$data['rating_counts'] = [];

			foreach ( $counts as $key => $value ) {
				$data['rating_counts'][] = array(
					'rating' => $key,
					'count'  => $value,
				);
			}
		}

		if ( ! empty( $collection_data_params['calculate_attribute_counts'] ) ) {
			foreach ( $collection_data_params['calculate_attribute_counts'] as $attributes_to_count ) {
				if ( ! isset( $attributes_to_count['taxonomy'] ) ) {
					continue;
				}

				$filter_query_vars = $query_vars;
				unset( $filter_query_vars[ 'filter_' . str_replace( 'pa_', '', $attributes_to_count['taxonomy']) ] );
				unset($filter_query_vars['taxonomy']);
				unset($filter_query_vars['terms']);
				foreach( $filter_query_vars['tax_query'] as $key => $tax_query ) {
					if ( is_array( $tax_query ) && $tax_query['taxonomy'] === $attributes_to_count['taxonomy'] ) {
						unset($filter_query_vars['tax_query'][$key]);
					}
				}

				$counts = $filters->get_attribute_counts( $filter_query_vars, $attributes_to_count['taxonomy'] );

				foreach ( $counts as $key => $value ) {
					$data['attribute_counts'][] = array(
						'term'  => $key,
						'count' => $value,
					);
				}
			}

		}

		return $data;
	}

	/**
	 * Get all inner blocks recursively.
	 *
	 * @param WP_Block_List $inner_blocks The block to get inner blocks from.
	 * @param array         $results      The results array.
	 *
	 * @return array
	 */
	private function get_inner_collection_data_params( $inner_blocks, &$results = array() ) {
		if ( is_a( $inner_blocks, 'WP_Block_List' ) ) {
			foreach ( $inner_blocks as $inner_block ) {
				if ( ! empty( $inner_block->attributes['queryParam'] ) ) {
					$query_param = $inner_block->attributes['queryParam'];
					/**
					 * There can be multiple attribute filters so we transform
					 * the query param of each filter into an array to merge
					 * them together.
					 */
					if ( ! empty( $query_param['calculate_attribute_counts'] ) ) {
						$query_param['calculate_attribute_counts'] = array( $query_param['calculate_attribute_counts'] );
					}
					$results = array_merge_recursive( $results, $query_param );
				}
				$this->get_inner_collection_data_params(
					$inner_block->inner_blocks,
					$results
				);
			}
		}

		return $results;
	}

	/**
	 * Get formatted products params for ProductCollectionData route from the
	 * query context.
	 *
	 * @param array $query The query context.
	 * @return array
	 */
	private function get_formatted_products_params( $query ) {
		$params = array();

		if ( empty( $query['isProductCollectionBlock'] ) ) {
			return $params;
		}

		/**
		 * The following params can be passed directly to Store API endpoints.
		 */
		$shared_params = array( 'exclude', 'offset', 'search' );

		/**
		 * The following params just need to transform the key, their value can
		 * be passed as it is to the Store API.
		 */
		$mapped_params = array(
			'woocommerceStockStatus'        => 'stock_status',
			'woocommerceOnSale'             => 'on_sale',
			'woocommerceHandPickedProducts' => 'include',
		);

		$taxonomy_mapper = function( $key ) {
			$mapping = array(
				'product_tag' => 'tag',
				'product_cat' => 'category',
			);

			return $mapping[ $key ] ?? '_unstable_tax_' . $key;
		};

		array_walk(
			$query,
			function( $value, $key ) use ( $shared_params, $mapped_params, $taxonomy_mapper, &$params ) {
				if ( in_array( $key, $shared_params, true ) ) {
					$params[ $key ] = $value;
				}

				if ( in_array( $key, array_keys( $mapped_params ), true ) ) {
					$params[ $mapped_params[ $key ] ] = $value;
				}

				/**
				 * The value of taxQuery and woocommerceAttributes need additional
				 * transformation to the shape that Store API accepts.
				 */
				if ( 'taxQuery' === $key && is_array( $value ) ) {
					array_walk(
						$value,
						function( $terms, $taxonomy ) use ( $taxonomy_mapper, &$params ) {
							$params[ $taxonomy_mapper( $taxonomy ) ] = implode( ',', $terms );
						}
					);
				}

				if ( 'woocommerceAttributes' === $key && is_array( $value ) ) {
					array_walk(
						$value,
						function( $attribute ) use ( &$params ) {
							$params['attributes'][] = array(
								'attribute' => $attribute['taxonomy'],
								'term_id'   => $attribute['termId'],
							);
						}
					);
				}
			}
		);

		/**
		 * Product Collection determines the product visibility based on stock
		 * statuses. We need to pass the catalog_visibility param to the Store
		 * API to make sure the product visibility is correct.
		 */
		$params['catalog_visibility'] = is_search() ? 'search' : 'visible';

		/**
		* `false` values got removed from `add_query_arg`, so we need to convert
		* them to numeric.
		*/
		return array_map(
			function( $param ) {
				return is_bool( $param ) ? +$param : $param;
			},
			$params
		);
	}

}
