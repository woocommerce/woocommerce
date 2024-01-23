<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\QueryFilters;
use Automattic\WooCommerce\Blocks\Package;

/**
 * Product Filter Block.
 */
final class ProductFilter extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter';

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
		global $pagenow;
		parent::enqueue_data( $attributes );

		$this->asset_data_registry->add( 'isBlockTheme', wc_current_theme_is_fse_theme(), true );
		$this->asset_data_registry->add( 'isProductArchive', is_shop() || is_product_taxonomy(), true );
		$this->asset_data_registry->add( 'isSiteEditor', 'site-editor.php' === $pagenow, true );
		$this->asset_data_registry->add( 'isWidgetEditor', 'widgets.php' === $pagenow || 'customize.php' === $pagenow, true );
	}

	/**
	 * Check if the collection data is empty.
	 *
	 * @param mixed $attributes - Block attributes.
	 * @return bool - Whether the collection data is empty.
	 */
	private function collection_data_is_empty( $attributes ) {
		$filter_type = $attributes['filterType'];

		if ( 'active-filters' !== $filter_type && empty( $this->current_response ) ) {
			return true;
		}

		if ( 'attribute-filter' === $filter_type ) {
			return empty( $this->current_response['attribute_counts'] );
		}

		if ( 'rating-filter' === $filter_type ) {
			return empty( $this->current_response['rating_counts'] );
		}

		if ( 'price-filter' === $filter_type ) {
			return empty( $this->current_response['price_range'] ) || ( $this->current_response['price_range']['min_price'] === $this->current_response['price_range']['max_price'] );
		}

		if ( 'stock-filter' === $filter_type ) {
			return empty( $this->current_response['stock_status_counts'] );
		}

		if ( 'active-filters' === $filter_type ) {
			// Duplicate query param logic from ProductFilterActive block, to determine if we should
			// display the ProductFilter block or not.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
			$parsed_url  = wp_parse_url( esc_url_raw( $request_uri ) );

			$url_query_params = [];

			if ( isset( $parsed_url['query'] ) ) {
				parse_str( $parsed_url['query'], $url_query_params );
			}

			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			return empty( array_unique( apply_filters( 'collection_filter_query_param_keys', array(), array_keys( $url_query_params ) ) ) );
		}

		return false;
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

		if ( $this->collection_data_is_empty( $attributes ) ) {
			return $this->render_empty_block( $block );
		}

		return $this->render_filter_block( $content, $block );
	}

	/**
	 * Reset the current response, must be done before rendering.
	 *
	 * @return void
	 */
	private function reset_current_response() {
		/**
		 * When WP starts rendering the Product Filters block,
		 * we can safely unset the current response.
		 */
		$this->current_response = null;
	}

	/**
	 * Render the block when it's empty.
	 *
	 * @param mixed $block  - Block instance.
	 * @return string - Rendered block type output.
	 */
	private function render_empty_block( $block ) {
		$this->reset_current_response();

		$attributes = array(
			'data-wc-interactive' => wp_json_encode( array( 'namespace' => $this->get_full_block_name() ) ),
			'class'               => 'wc-block-product-filters',
		);

		if ( ! isset( $block->context['queryId'] ) ) {
			$attributes['data-wc-navigation-id'] = $this->generate_navigation_id( $block );
		}

		return sprintf(
			'<nav %1$s></nav>',
			get_block_wrapper_attributes(
				$attributes
			)
		);
	}

	/**
	 * Generate a unique navigation ID for the block.
	 *
	 * @param mixed $block - Block instance.
	 * @return string - Unique navigation ID.
	 */
	private function generate_navigation_id( $block ) {
		return sprintf(
			'wc-product-filter-%s',
			md5( wp_json_encode( $block->parsed_block['innerBlocks'] ) )
		);
	}

	/**
	 * Render the block when it's not empty.
	 *
	 * @param string   $content - Block content.
	 * @param WP_Block $block   - Block instance.
	 * @return string - Rendered block type output.
	 */
	private function render_filter_block( $content, $block ) {
		$this->reset_current_response();

		$attributes_data = array(
			'data-wc-interactive' => wp_json_encode( array( 'namespace' => $this->get_full_block_name() ) ),
			'class'               => 'wc-block-product-filters',
		);

		if ( ! isset( $block->context['queryId'] ) ) {
			$attributes_data['data-wc-navigation-id'] = $this->generate_navigation_id( $block );
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
		 * When the first direct child of Product Filters is rendering, we
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

		$data = array(
			'min_price'           => null,
			'max_price'           => null,
			'attribute_counts'    => null,
			'stock_status_counts' => null,
			'rating_counts'       => null,
		);

		$filters = Package::container()->get( QueryFilters::class );

		if ( ! empty( $block->context['query'] ) && ! $block->context['query']['inherit'] ) {
			$query_vars = build_query_vars_from_query_block( $block, 1 );
		} else {
			global $wp_query;
			$query_vars = array_filter( $wp_query->query_vars );
		}

		if ( ! empty( $collection_data_params['calculate_price_range'] ) ) {
			$filter_query_vars = $query_vars;

			unset( $filter_query_vars['min_price'], $filter_query_vars['max_price'] );

			if ( ! empty( $filter_query_vars['meta_query'] ) ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				$filter_query_vars['meta_query'] = $this->remove_query_array( $filter_query_vars['meta_query'], 'key', '_price' );
			}

			$price_results       = $filters->get_filtered_price( $filter_query_vars );
			$data['price_range'] = array(
				'min_price' => intval( floor( $price_results->min_price ?? 0 ) ),
				'max_price' => intval( ceil( $price_results->max_price ?? 0 ) ),
			);
		}

		if ( ! empty( $collection_data_params['calculate_stock_status_counts'] ) ) {
			$filter_query_vars = $query_vars;

			unset( $filter_query_vars['filter_stock_status'] );

			if ( ! empty( $filter_query_vars['meta_query'] ) ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				$filter_query_vars['meta_query'] = $this->remove_query_array( $filter_query_vars['meta_query'], 'key', '_stock_status' );
			}

			$counts = $filters->get_stock_status_counts( $filter_query_vars );

			$data['stock_status_counts'] = array();

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
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				$filter_query_vars['tax_query'] = $this->remove_query_array( $filter_query_vars['tax_query'], 'rating_filter', true );
			}

			$counts                = $filters->get_rating_counts( $filter_query_vars );
			$data['rating_counts'] = array();

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

				if ( 'and' !== strtolower( $attributes_to_count['queryType'] ) ) {
					unset( $filter_query_vars[ 'filter_' . str_replace( 'pa_', '', $attributes_to_count['taxonomy'] ) ] );
				}

				unset(
					$filter_query_vars['taxonomy'],
					$filter_query_vars['term']
				);

				if ( ! empty( $filter_query_vars['tax_query'] ) ) {
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					$filter_query_vars['tax_query'] = $this->remove_query_array( $filter_query_vars['tax_query'], 'taxonomy', $attributes_to_count['taxonomy'] );
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
	 * Remove query array from tax or meta query by searching for arrays that
	 * contain exact key => value pair.
	 *
	 * @param array  $queries tax_query or meta_query.
	 * @param string $key     Array key to search for.
	 * @param mixed  $value   Value to compare with search result.
	 *
	 * @return array
	 */
	private function remove_query_array( $queries, $key, $value ) {
		if ( empty( $queries ) ) {
			return $queries;
		}

		foreach ( $queries as $query_key => $query ) {
			if ( isset( $query[ $key ] ) && $query[ $key ] === $value ) {
				unset( $queries[ $query_key ] );
			}

			if ( isset( $query['relation'] ) ) {
				$queries[ $query_key ] = $this->remove_query_array( $query, $key, $value );
			}
		}

		return $queries;
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

}
