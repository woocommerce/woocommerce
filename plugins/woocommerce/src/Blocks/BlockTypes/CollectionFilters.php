<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;

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
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );

		if ( ! is_admin() ) {
			/**
			 * At this point, WP starts rendering the Collection Filters block,
			 * we can safely unset the current response.
			 */
			$this->current_response = null;
		}
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

		$response = Package::container()->get( Hydration::class )->get_rest_api_response_data(
			add_query_arg(
				array_merge(
					$this->get_formatted_products_params( $block->context['query'] ?? array() ),
					$collection_data_params,
				),
				'/wc/store/v1/products/collection-data'
			)
		);

		if ( ! empty( $response['body'] ) ) {
			return json_decode( wp_json_encode( $response['body'] ), true );
		}

		return array();
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
