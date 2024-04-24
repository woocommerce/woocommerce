<?php

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use WP_Query;
use WC_Tax;

/**
 * ProductCollection class.
 */
class ProductCollection extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-collection';

	/**
	 * The Block with its attributes before it gets rendered
	 *
	 * @var array
	 */
	protected $parsed_block;

	/**
	 * All query args from WP_Query.
	 *
	 * @var array
	 */
	protected $valid_query_vars;

	/**
	 * All the query args related to the filter by attributes block.
	 *
	 * @var array
	 */
	protected $attributes_filter_query_args = array();

	/**
	 * Orderby options not natively supported by WordPress REST API
	 *
	 * @var array
	 */
	protected $custom_order_opts = array( 'popularity', 'rating' );


	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 * - Hook into pre_render_block to update the query.
	 */
	protected function initialize() {
		parent::initialize();
		// Update query for frontend rendering.
		add_filter(
			'query_loop_block_query_vars',
			array( $this, 'build_frontend_query' ),
			10,
			3
		);

		add_filter(
			'pre_render_block',
			array( $this, 'add_support_for_filter_blocks' ),
			10,
			2
		);

		// Update the query for Editor.
		add_filter( 'rest_product_query', array( $this, 'update_rest_query_in_editor' ), 10, 2 );

		// Extend allowed `collection_params` for the REST API.
		add_filter( 'rest_product_collection_params', array( $this, 'extend_rest_query_allowed_params' ), 10, 1 );

		// Interactivity API: Add navigation directives to the product collection block.
		add_filter( 'render_block_woocommerce/product-collection', array( $this, 'enhance_product_collection_with_interactivity' ), 10, 2 );
		add_filter( 'render_block_core/query-pagination', array( $this, 'add_navigation_link_directives' ), 10, 3 );

		add_filter( 'posts_clauses', array( $this, 'add_price_range_filter_posts_clauses' ), 10, 2 );

		// Disable client-side-navigation if incompatible blocks are detected.
		add_filter( 'render_block_data', array( $this, 'disable_enhanced_pagination' ), 10, 1 );
	}

	/**
	 * Enhances the Product Collection block with client-side pagination.
	 *
	 * This function identifies Product Collection blocks and adds necessary data attributes
	 * to enable client-side navigation and animation effects. It also enqueues the Interactivity API runtime.
	 *
	 * @param string $block_content The HTML content of the block.
	 * @param array  $block         Block details, including its attributes.
	 *
	 * @return string Updated block content with added interactivity attributes.
	 */
	public function enhance_product_collection_with_interactivity( $block_content, $block ) {
		$is_product_collection_block    = $block['attrs']['query']['isProductCollectionBlock'] ?? false;
		$is_enhanced_pagination_enabled = ! ( $block['attrs']['forcePageReload'] ?? false );
		if ( $is_product_collection_block && $is_enhanced_pagination_enabled ) {
			// Enqueue the Interactivity API runtime.
			wp_enqueue_script( 'wc-interactivity' );

			$p = new \WP_HTML_Tag_Processor( $block_content );

			// Add `data-wc-navigation-id to the product collection block.
			if ( $p->next_tag( array( 'class_name' => 'wp-block-woocommerce-product-collection' ) ) ) {
				$p->set_attribute(
					'data-wc-navigation-id',
					'wc-product-collection-' . $this->parsed_block['attrs']['queryId']
				);
				$p->set_attribute( 'data-wc-interactive', wp_json_encode( array( 'namespace' => 'woocommerce/product-collection' ) ) );
				$p->set_attribute(
					'data-wc-context',
					wp_json_encode(
						array(
							// The message to be announced by the screen reader when the page is loading or loaded.
							'accessibilityLoadingMessage'  => __( 'Loading page, please wait.', 'woocommerce' ),
							'accessibilityLoadedMessage'   => __( 'Page Loaded.', 'woocommerce' ),
							// We don't prefetch the links if user haven't clicked on pagination links yet.
							// This way we avoid prefetching when the page loads.
							'isPrefetchNextOrPreviousLink' => false,
						),
						JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP
					)
				);
				$block_content = $p->get_updated_html();
			}

			/**
			 * Add two div's:
			 * 1. Pagination animation for visual users.
			 * 2. Accessibility div for screen readers, to announce page load states.
			 */
			$last_tag_position                = strripos( $block_content, '</div>' );
			$accessibility_and_animation_html = '
				<div
					data-wc-interactive="{&quot;namespace&quot;:&quot;woocommerce/product-collection&quot;}"
					class="wc-block-product-collection__pagination-animation"
					data-wc-class--start-animation="state.startAnimation"
					data-wc-class--finish-animation="state.finishAnimation">
				</div>
				<div
					data-wc-interactive="{&quot;namespace&quot;:&quot;woocommerce/product-collection&quot;}"
					class="screen-reader-text"
					aria-live="polite"
					data-wc-text="context.accessibilityMessage">
				</div>
			';
			$block_content                    = substr_replace(
				$block_content,
				$accessibility_and_animation_html,
				$last_tag_position,
				0
			);
		}

		return $block_content;
	}

	/**
	 * Add interactive links to all anchors inside the Query Pagination block.
	 * This enabled client-side navigation for the product collection block.
	 *
	 * @param string    $block_content The block content.
	 * @param array     $block         The full block, including name and attributes.
	 * @param \WP_Block $instance      The block instance.
	 */
	public function add_navigation_link_directives( $block_content, $block, $instance ) {
		$query_context                  = $instance->context['query'] ?? array();
		$is_product_collection_block    = $query_context['isProductCollectionBlock'] ?? false;
		$query_id                       = $instance->context['queryId'] ?? null;
		$parsed_query_id                = $this->parsed_block['attrs']['queryId'] ?? null;
		$is_enhanced_pagination_enabled = ! ( $this->parsed_block['attrs']['forcePageReload'] ?? false );

		// Only proceed if the block is a product collection block,
		// enhaced pagination is enabled and query IDs match.
		if ( $is_product_collection_block && $is_enhanced_pagination_enabled && $query_id === $parsed_query_id ) {
			$block_content = $this->process_pagination_links( $block_content );
		}

		return $block_content;
	}

	/**
	 * Process pagination links within the block content.
	 *
	 * @param string $block_content The block content.
	 * @return string The updated block content.
	 */
	private function process_pagination_links( $block_content ) {
		if ( ! $block_content ) {
			return $block_content;
		}

		$p = new \WP_HTML_Tag_Processor( $block_content );
		$p->next_tag( array( 'class_name' => 'wp-block-query-pagination' ) );

		// This will help us to find the start of the block content using the `seek` method.
		$p->set_bookmark( 'start' );

		$this->update_pagination_anchors( $p, 'page-numbers', 'product-collection-pagination-numbers' );
		$this->update_pagination_anchors( $p, 'wp-block-query-pagination-next', 'product-collection-pagination--next' );
		$this->update_pagination_anchors( $p, 'wp-block-query-pagination-previous', 'product-collection-pagination--previous' );

		return $p->get_updated_html();
	}

	/**
	 * Sets up data attributes required for interactivity and client-side navigation.
	 *
	 * @param \WP_HTML_Tag_Processor $processor The HTML tag processor.
	 * @param string                 $class_name The class name of the anchor tags.
	 * @param string                 $key_prefix The prefix for the data-wc-key attribute.
	 */
	private function update_pagination_anchors( $processor, $class_name, $key_prefix ) {
		// Start from the beginning of the block content.
		$processor->seek( 'start' );

		while ( $processor->next_tag(
			array(
				'tag_name'   => 'a',
				'class_name' => $class_name,
			)
		) ) {
			$processor->set_attribute( 'data-wc-interactive', wp_json_encode( array( 'namespace' => 'woocommerce/product-collection' ) ) );
			$processor->set_attribute( 'data-wc-on--click', 'actions.navigate' );
			$processor->set_attribute( 'data-wc-key', $key_prefix . '--' . esc_attr( wp_rand() ) );

			if ( in_array( $class_name, array( 'wp-block-query-pagination-next', 'wp-block-query-pagination-previous' ), true ) ) {
				$processor->set_attribute( 'data-wc-watch', 'callbacks.prefetch' );
				$processor->set_attribute( 'data-wc-on--mouseenter', 'actions.prefetchOnHover' );
			}
		}
	}

	/**
	 * Verifies if the inner block is compatible with Interactivity API.
	 *
	 * @param string $block_name Name of the block to verify.
	 * @return boolean
	 */
	private function is_block_compatible( $block_name ) {
		// Check for explicitly unsupported blocks.
		if ( 'core/post-content' === $block_name ||
			'woocommerce/mini-cart' === $block_name ||
			'woocommerce/featured-product' === $block_name ) {
			return false;
		}

		// Check for supported prefixes.
		if (
			str_starts_with( $block_name, 'core/' ) ||
			str_starts_with( $block_name, 'woocommerce/' )
		) {
			return true;
		}

		// Otherwise block is unsupported.
		return false;
	}

	/**
	 * Check inner blocks of Product Collection block if there's one
	 * incompatible with Interactivity API and if so, disable client-side
	 * naviagtion.
	 *
	 * @param array $parsed_block The block being rendered.
	 * @return string Returns the parsed block, unmodified.
	 */
	public function disable_enhanced_pagination( $parsed_block ) {
		static $enhanced_query_stack               = array();
		static $dirty_enhanced_queries             = array();
		static $render_product_collection_callback = null;

		$block_name               = $parsed_block['blockName'];
		$force_page_reload_global =
			$parsed_block['attrs']['forcePageReload'] ?? false &&
			isset( $block['attrs']['queryId'] );

		if (
			'woocommerce/product-collection' === $block_name &&
			! $force_page_reload_global
		) {
			$enhanced_query_stack[] = $parsed_block['attrs']['queryId'];

			if ( ! isset( $render_product_collection_callback ) ) {
				/**
				 * Filter that disables the enhanced pagination feature during block
				 * rendering when a plugin block has been found inside. It does so
				 * by adding an attribute called `data-wp-navigation-disabled` which
				 * is later handled by the front-end logic.
				 *
				 * @param string   $content  The block content.
				 * @param array    $block    The full block, including name and attributes.
				 * @return string Returns the modified output of the query block.
				 */
				$render_product_collection_callback = static function ( $content, $block ) use ( &$enhanced_query_stack, &$dirty_enhanced_queries, &$render_product_collection_callback ) {
					$force_page_reload =
						$parsed_block['attrs']['forcePageReload'] ?? false &&
						isset( $block['attrs']['queryId'] );

					if ( $force_page_reload ) {
						return $content;
					}

					if ( isset( $dirty_enhanced_queries[ $block['attrs']['queryId'] ] ) ) {
						$p = new \WP_HTML_Tag_Processor( $content );
						if ( $p->next_tag() ) {
							$p->set_attribute( 'data-wc-navigation-disabled', 'true' );
						}
						$content = $p->get_updated_html();
						$dirty_enhanced_queries[ $block['attrs']['queryId'] ] = null;
					}

					array_pop( $enhanced_query_stack );

					if ( empty( $enhanced_query_stack ) ) {
						remove_filter( 'render_block_woocommerce/product-collection', $render_product_collection_callback );
						$render_product_collection_callback = null;
					}

					return $content;
				};

				add_filter( 'render_block_woocommerce/product-collection', $render_product_collection_callback, 10, 2 );
			}
		} elseif (
			! empty( $enhanced_query_stack ) &&
			isset( $block_name ) &&
			! $this->is_block_compatible( $block_name )
		) {
			foreach ( $enhanced_query_stack as $query_id ) {
				$dirty_enhanced_queries[ $query_id ] = true;
			}
		}

		return $parsed_block;
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

		// The `loop_shop_per_page` filter can be found in WC_Query::product_query().
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$this->asset_data_registry->add( 'loopShopPerPage', apply_filters( 'loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page() ) );
	}

	/**
	 * Update the query for the product query block in Editor.
	 *
	 * @param array           $args    Query args.
	 * @param WP_REST_Request $request Request.
	 */
	public function update_rest_query_in_editor( $args, $request ): array {
		// Only update the query if this is a product collection block.
		$is_product_collection_block = $request->get_param( 'isProductCollectionBlock' );
		if ( ! $is_product_collection_block ) {
			return $args;
		}

		// Is this a preview mode request?
		// If yes, short-circuit the query and return the preview query args.
		$product_collection_query_context = $request->get_param( 'productCollectionQueryContext' );
		$is_preview                       = $product_collection_query_context['previewState']['isPreview'] ?? false;
		if ( 'true' === $is_preview ) {
			return $this->get_preview_query_args( $args, $request );
		}

		$orderby             = $request->get_param( 'orderBy' );
		$on_sale             = $request->get_param( 'woocommerceOnSale' ) === 'true';
		$stock_status        = $request->get_param( 'woocommerceStockStatus' );
		$product_attributes  = $request->get_param( 'woocommerceAttributes' );
		$handpicked_products = $request->get_param( 'woocommerceHandPickedProducts' );
		$featured            = $request->get_param( 'featured' );
		$time_frame          = $request->get_param( 'timeFrame' );
		$price_range         = $request->get_param( 'priceRange' );
		// This argument is required for the tests to PHP Unit Tests to run correctly.
		// Most likely this argument is being accessed in the test environment image.
		$args['author'] = '';

		return $this->get_final_query_args(
			$args,
			array(
				'orderby'             => $orderby,
				'on_sale'             => $on_sale,
				'stock_status'        => $stock_status,
				'product_attributes'  => $product_attributes,
				'handpicked_products' => $handpicked_products,
				'featured'            => $featured,
				'timeFrame'           => $time_frame,
				'priceRange'          => $price_range,
			)
		);
	}

	/**
	 * Add support for filter blocks:
	 * - Price filter block
	 * - Attributes filter block
	 * - Rating filter block
	 * - In stock filter block etc.
	 *
	 * @param array $pre_render   The pre-rendered block.
	 * @param array $parsed_block The parsed block.
	 */
	public function add_support_for_filter_blocks( $pre_render, $parsed_block ) {
		$is_product_collection_block = $parsed_block['attrs']['query']['isProductCollectionBlock'] ?? false;

		if ( ! $is_product_collection_block ) {
			return $pre_render;
		}

		$this->parsed_block = $parsed_block;
		$this->asset_data_registry->add( 'hasFilterableProducts', true );
		/**
		 * It enables the page to refresh when a filter is applied, ensuring that the product collection block,
		 * which is a server-side rendered (SSR) block, retrieves the products that match the filters.
		 */
		$this->asset_data_registry->add( 'isRenderingPhpTemplate', true );

		return $pre_render;
	}

	/**
	 * Return a custom query based on attributes, filters and global WP_Query.
	 *
	 * @param WP_Query $query The WordPress Query.
	 * @param WP_Block $block The block being rendered.
	 * @param int      $page  The page number.
	 *
	 * @return array
	 */
	public function build_frontend_query( $query, $block, $page ) {
		// If not in context of product collection block, return the query as is.
		$is_product_collection_block = $block->context['query']['isProductCollectionBlock'] ?? false;
		if ( ! $is_product_collection_block ) {
			return $query;
		}

		$block_context_query = $block->context['query'];
		// phpcs:ignore WordPress.DB.SlowDBQuery
		$block_context_query['tax_query'] = ! empty( $query['tax_query'] ) ? $query['tax_query'] : array();

		return $this->get_final_frontend_query( $block_context_query, $page );
	}


	/**
	 * Get the final query arguments for the frontend.
	 *
	 * @param array $query The query arguments.
	 * @param int   $page  The page number.
	 * @param bool  $is_exclude_applied_filters Whether to exclude the applied filters or not.
	 */
	private function get_final_frontend_query( $query, $page = 1, $is_exclude_applied_filters = false ) {
		$offset   = $query['offset'] ?? 0;
		$per_page = $query['perPage'] ?? 9;

		$common_query_values = array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'     => array(),
			'posts_per_page' => $query['perPage'],
			'order'          => $query['order'],
			'offset'         => ( $per_page * ( $page - 1 ) ) + $offset,
			'post__in'       => array(),
			'post_status'    => 'publish',
			'post_type'      => 'product',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query'      => array(),
			'paged'          => $page,
			's'              => $query['search'],
		);

		$is_on_sale          = $query['woocommerceOnSale'] ?? false;
		$product_attributes  = $query['woocommerceAttributes'] ?? array();
		$taxonomies_query    = $this->get_filter_by_taxonomies_query( $query['tax_query'] ?? array() );
		$handpicked_products = $query['woocommerceHandPickedProducts'] ?? array();
		$time_frame          = $query['timeFrame'] ?? null;
		$price_range         = $query['priceRange'] ?? null;

		$final_query = $this->get_final_query_args(
			$common_query_values,
			array(
				'on_sale'             => $is_on_sale,
				'stock_status'        => $query['woocommerceStockStatus'],
				'orderby'             => $query['orderBy'],
				'product_attributes'  => $product_attributes,
				'taxonomies_query'    => $taxonomies_query,
				'handpicked_products' => $handpicked_products,
				'featured'            => $query['featured'] ?? false,
				'timeFrame'           => $time_frame,
				'priceRange'          => $price_range,
			),
			$is_exclude_applied_filters
		);

		return $final_query;
	}

	/**
	 * Get final query args based on provided values
	 *
	 * @param array $common_query_values Common query values.
	 * @param array $query               Query from block context.
	 * @param bool  $is_exclude_applied_filters Whether to exclude the applied filters or not.
	 */
	private function get_final_query_args( $common_query_values, $query, $is_exclude_applied_filters = false ) {
		$handpicked_products = $query['handpicked_products'] ?? array();
		$orderby_query       = $query['orderby'] ? $this->get_custom_orderby_query( $query['orderby'] ) : array();
		$on_sale_query       = $this->get_on_sale_products_query( $query['on_sale'] );
		$stock_query         = $this->get_stock_status_query( $query['stock_status'] );
		$visibility_query    = is_array( $query['stock_status'] ) ? $this->get_product_visibility_query( $stock_query, $query['stock_status'] ) : array();
		$featured_query      = $this->get_featured_query( $query['featured'] ?? false );
		$attributes_query    = $this->get_product_attributes_query( $query['product_attributes'] );
		$taxonomies_query    = $query['taxonomies_query'] ?? array();
		$tax_query           = $this->merge_tax_queries( $visibility_query, $attributes_query, $taxonomies_query, $featured_query );
		$date_query          = $this->get_date_query( $query['timeFrame'] ?? array() );
		$price_query_args    = $this->get_price_range_query_args( $query['priceRange'] ?? array() );

		// We exclude applied filters to generate product ids for the filter blocks.
		$applied_filters_query = $is_exclude_applied_filters ? array() : $this->get_queries_by_applied_filters();

		$merged_query = $this->merge_queries( $common_query_values, $orderby_query, $on_sale_query, $stock_query, $tax_query, $applied_filters_query, $date_query, $price_query_args );

		$result = $this->filter_query_to_only_include_ids( $merged_query, $handpicked_products );

		return $result;
	}

	/**
	 * Modifies the query arguments based on the collection name for preview requests.
	 * For instance, for the 'On-sale' collection, products on sale should always be displayed
	 * in preview mode, regardless of whether the intention is to display random products.
	 *
	 * @param array           $args    Query args.
	 * @param WP_REST_Request $request Request.
	 */
	private function get_preview_query_args( $args, $request ) {
		$collection_query = array();

		/**
		 * In future, Here we will modify the preview query based on the collection name. For example:
		 *
		 * $product_collection_query_context = $request->get_param( 'productCollectionQueryContext' );
		 * $collection_name                  = $product_collection_query_context['collection'] ?? '';
		 * if ( 'woocommerce/product-collection/on-sale' === $collection_name ) {
		 *      $collection_query = $this->get_on_sale_products_query( true );
		 * }.
		 */

		$args = $this->merge_queries( $args, $collection_query );
		return $args;
	}

	/**
	 * Extends allowed `collection_params` for the REST API
	 *
	 * By itself, the REST API doesn't accept custom `orderby` values,
	 * even if they are supported by a custom post type.
	 *
	 * @param array $params  A list of allowed `orderby` values.
	 *
	 * @return array
	 */
	public function extend_rest_query_allowed_params( $params ) {
		$original_enum             = isset( $params['orderby']['enum'] ) ? $params['orderby']['enum'] : array();
		$params['orderby']['enum'] = array_unique( array_merge( $original_enum, $this->custom_order_opts ) );
		return $params;
	}

	/**
	 * Merge in the first parameter the keys "post_in", "meta_query" and "tax_query" of the second parameter.
	 *
	 * @param array[] ...$queries Query arrays to be merged.
	 * @return array
	 */
	private function merge_queries( ...$queries ) {
		$merged_query = array_reduce(
			$queries,
			function ( $acc, $query ) {
				if ( ! is_array( $query ) ) {
					return $acc;
				}
				// If the $query doesn't contain any valid query keys, we unpack/spread it then merge.
				if ( empty( array_intersect( $this->get_valid_query_vars(), array_keys( $query ) ) ) ) {
					return $this->merge_queries( $acc, ...array_values( $query ) );
				}
				return $this->array_merge_recursive_replace_non_array_properties( $acc, $query );
			},
			array()
		);

		/**
		 * If there are duplicated items in post__in, it means that we need to
		 * use the intersection of the results, which in this case, are the
		 * duplicated items.
		 */
		if (
			! empty( $merged_query['post__in'] ) &&
			is_array( $merged_query['post__in'] ) &&
			count( $merged_query['post__in'] ) > count( array_unique( $merged_query['post__in'] ) )
		) {
			$merged_query['post__in'] = array_unique(
				array_diff(
					$merged_query['post__in'],
					array_unique( $merged_query['post__in'] )
				)
			);
		}

		return $merged_query;
	}

	/**
	 * Return query params to support custom sort values
	 *
	 * @param string $orderby  Sort order option.
	 *
	 * @return array
	 */
	private function get_custom_orderby_query( $orderby ) {
		if ( ! in_array( $orderby, $this->custom_order_opts, true ) ) {
			return array( 'orderby' => $orderby );
		}

		$meta_keys = array(
			'popularity' => 'total_sales',
			'rating'     => '_wc_average_rating',
		);

		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_key' => $meta_keys[ $orderby ],
			'orderby'  => 'meta_value_num',
		);
	}

	/**
	 * Return a query for on sale products.
	 *
	 * @param bool $is_on_sale Whether to query for on sale products.
	 *
	 * @return array
	 */
	private function get_on_sale_products_query( $is_on_sale ) {
		if ( ! $is_on_sale ) {
			return array();
		}

		return array(
			'post__in' => wc_get_product_ids_on_sale(),
		);
	}

	/**
	 * Return or initialize $valid_query_vars.
	 *
	 * @return array
	 */
	private function get_valid_query_vars() {
		if ( ! empty( $this->valid_query_vars ) ) {
			return $this->valid_query_vars;
		}

		$valid_query_vars       = array_keys( ( new WP_Query() )->fill_query_vars( array() ) );
		$this->valid_query_vars = array_merge(
			$valid_query_vars,
			// fill_query_vars doesn't include these vars so we need to add them manually.
			array(
				'date_query',
				'exact',
				'ignore_sticky_posts',
				'lazy_load_term_meta',
				'meta_compare_key',
				'meta_compare',
				'meta_query',
				'meta_type_key',
				'meta_type',
				'nopaging',
				'offset',
				'order',
				'orderby',
				'page',
				'post_type',
				'posts_per_page',
				'suppress_filters',
				'tax_query',
				'isProductCollection',
				'priceRange',
			)
		);

		return $this->valid_query_vars;
	}

	/**
	 * Merge two array recursively but replace the non-array values instead of
	 * merging them. The merging strategy:
	 *
	 * - If keys from merge array doesn't exist in the base array, create them.
	 * - For array items with numeric keys, we merge them as normal.
	 * - For array items with string keys:
	 *
	 *   - If the value isn't array, we'll use the value comming from the merge array.
	 *     $base = ['orderby' => 'date']
	 *     $new  = ['orderby' => 'meta_value_num']
	 *     Result: ['orderby' => 'meta_value_num']
	 *
	 *   - If the value is array, we'll use recursion to merge each key.
	 *     $base = ['meta_query' => [
	 *       [
	 *         'key'     => '_stock_status',
	 *         'compare' => 'IN'
	 *         'value'   =>  ['instock', 'onbackorder']
	 *       ]
	 *     ]]
	 *     $new  = ['meta_query' => [
	 *       [
	 *         'relation' => 'AND',
	 *         [...<max_price_query>],
	 *         [...<min_price_query>],
	 *       ]
	 *     ]]
	 *     Result: ['meta_query' => [
	 *       [
	 *         'key'     => '_stock_status',
	 *         'compare' => 'IN'
	 *         'value'   =>  ['instock', 'onbackorder']
	 *       ],
	 *       [
	 *         'relation' => 'AND',
	 *         [...<max_price_query>],
	 *         [...<min_price_query>],
	 *       ]
	 *     ]]
	 *
	 *     $base = ['post__in' => [1, 2, 3, 4, 5]]
	 *     $new  = ['post__in' => [3, 4, 5, 6, 7]]
	 *     Result: ['post__in' => [1, 2, 3, 4, 5, 3, 4, 5, 6, 7]]
	 *
	 * @param array $base First array.
	 * @param array $new  Second array.
	 */
	private function array_merge_recursive_replace_non_array_properties( $base, $new ) {
		foreach ( $new as $key => $value ) {
			if ( is_numeric( $key ) ) {
				$base[] = $value;
			} elseif ( is_array( $value ) ) {
				if ( ! isset( $base[ $key ] ) ) {
					$base[ $key ] = array();
				}
					$base[ $key ] = $this->array_merge_recursive_replace_non_array_properties( $base[ $key ], $value );
			} else {
				$base[ $key ] = $value;
			}
		}

		return $base;
	}

	/**
	 * Return a query for products depending on their stock status.
	 *
	 * @param array $stock_statuses An array of acceptable stock statuses.
	 * @return array
	 */
	private function get_stock_status_query( $stock_statuses ) {
		if ( ! is_array( $stock_statuses ) ) {
			return array();
		}

		$stock_status_options = array_keys( wc_get_product_stock_status_options() );

		/**
		 * If all available stock status are selected, we don't need to add the
		 * meta query for stock status.
		 */
		if (
			count( $stock_statuses ) === count( $stock_status_options ) &&
			array_diff( $stock_statuses, $stock_status_options ) === array_diff( $stock_status_options, $stock_statuses )
		) {
			return array();
		}

		/**
		 * If all stock statuses are selected except 'outofstock', we use the
		 * product visibility query to filter out out of stock products.
		 *
		 * @see get_product_visibility_query()
		 */
		$diff = array_diff( $stock_status_options, $stock_statuses );
		if ( count( $diff ) === 1 && in_array( 'outofstock', $diff, true ) ) {
			return array();
		}

		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => array(
				array(
					'key'     => '_stock_status',
					'value'   => (array) $stock_statuses,
					'compare' => 'IN',
				),
			),
		);
	}

	/**
	 * Return a query for product visibility depending on their stock status.
	 *
	 * @param array $stock_query  Stock status query.
	 * @param array $stock_status Selected stock status.
	 *
	 * @return array Tax query for product visibility.
	 */
	private function get_product_visibility_query( $stock_query, $stock_status ) {
		$product_visibility_terms  = wc_get_product_visibility_term_ids();
		$product_visibility_not_in = array( is_search() ? $product_visibility_terms['exclude-from-search'] : $product_visibility_terms['exclude-from-catalog'] );

		// Hide out of stock products.
		if ( empty( $stock_query ) && ! in_array( 'outofstock', $stock_status, true ) ) {
			$product_visibility_not_in[] = $product_visibility_terms['outofstock'];
		}

		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query' => array(
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $product_visibility_not_in,
					'operator' => 'NOT IN',
				),
			),
		);
	}

	/**
	 * Generates a tax query to filter products based on their "featured" status.
	 * If the `$featured` parameter is true, the function will return a tax query
	 * that filters products to only those marked as featured.
	 * If `$featured` is false, an empty array is returned, meaning no filtering will be applied.
	 *
	 * @param bool $featured A flag indicating whether to filter products based on featured status.
	 *
	 * @return array A tax query for fetching featured products if `$featured` is true; otherwise, an empty array.
	 */
	private function get_featured_query( $featured ) {
		if ( true !== $featured && 'true' !== $featured ) {
			return array();
		}

		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query' => array(
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'featured',
					'operator' => 'IN',
				),
			),
		);
	}


	/**
	 * Merge tax_queries from various queries.
	 *
	 * @param array ...$queries Query arrays to be merged.
	 * @return array
	 */
	private function merge_tax_queries( ...$queries ) {
		$tax_query = array();
		foreach ( $queries as $query ) {
			if ( ! empty( $query['tax_query'] ) ) {
				$tax_query = array_merge( $tax_query, $query['tax_query'] );
			}
		}
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		return array( 'tax_query' => $tax_query );
	}

	/**
	 * Return the `tax_query` for the requested attributes
	 *
	 * @param array $attributes  Attributes and their terms.
	 *
	 * @return array
	 */
	private function get_product_attributes_query( $attributes = array() ) {
		if ( empty( $attributes ) ) {
			return array();
		}

		$grouped_attributes = array_reduce(
			$attributes,
			function ( $carry, $item ) {
				$taxonomy = sanitize_title( $item['taxonomy'] );

				if ( ! key_exists( $taxonomy, $carry ) ) {
					$carry[ $taxonomy ] = array(
						'field'    => 'term_id',
						'operator' => 'IN',
						'taxonomy' => $taxonomy,
						'terms'    => array( $item['termId'] ),
					);
				} else {
					$carry[ $taxonomy ]['terms'][] = $item['termId'];
				}

				return $carry;
			},
			array()
		);

		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query' => array_values( $grouped_attributes ),
		);
	}

	/**
	 * Return a query to filter products by taxonomies (product categories, product tags, etc.)
	 *
	 * For example:
	 * User could provide "Product Categories" using "Filters" ToolsPanel available in Inspector Controls.
	 * We use this function to extract its query from $tax_query.
	 *
	 * For example, this is how the query for product categories will look like in $tax_query array:
	 * Array
	 *    (
	 *        [taxonomy] => product_cat
	 *        [terms] => Array
	 *            (
	 *                [0] => 36
	 *            )
	 *    )
	 *
	 * For product tags, taxonomy would be "product_tag"
	 *
	 * @param array $tax_query Query to filter products by taxonomies.
	 * @return array Query to filter products by taxonomies.
	 */
	private function get_filter_by_taxonomies_query( $tax_query ): array {
		if ( ! is_array( $tax_query ) ) {
			return array();
		}

		/**
		 * Get an array of taxonomy names associated with the "product" post type because
		 * we also want to include custom taxonomies associated with the "product" post type.
		 */
		$product_taxonomies = array_diff( get_object_taxonomies( 'product', 'names' ), array( 'product_visibility', 'product_shipping_class' ) );
		$result             = array_filter(
			$tax_query,
			function ( $item ) use ( $product_taxonomies ) {
				return isset( $item['taxonomy'] ) && in_array( $item['taxonomy'], $product_taxonomies, true );
			}
		);

		// phpcs:ignore WordPress.DB.SlowDBQuery
		return ! empty( $result ) ? array( 'tax_query' => $result ) : array();
	}

	/**
	 * Apply the query only to a subset of products
	 *
	 * @param array $query  The query.
	 * @param array $ids  Array of selected product ids.
	 *
	 * @return array
	 */
	private function filter_query_to_only_include_ids( $query, $ids ) {
		if ( ! empty( $ids ) ) {
			$query['post__in'] = empty( $query['post__in'] ) ?
				$ids : array_intersect( $ids, $query['post__in'] );
		}

		return $query;
	}

	/**
	 * Return queries that are generated by query args.
	 *
	 * @return array
	 */
	private function get_queries_by_applied_filters() {
		return array(
			'price_filter'        => $this->get_filter_by_price_query(),
			'attributes_filter'   => $this->get_filter_by_attributes_query(),
			'stock_status_filter' => $this->get_filter_by_stock_status_query(),
			'rating_filter'       => $this->get_filter_by_rating_query(),
		);
	}

	/**
	 * Return a query that filters products by price.
	 *
	 * @return array
	 */
	private function get_filter_by_price_query() {
		$min_price = get_query_var( PriceFilter::MIN_PRICE_QUERY_VAR );
		$max_price = get_query_var( PriceFilter::MAX_PRICE_QUERY_VAR );

		$max_price_query = empty( $max_price ) ? array() : array(
			'key'     => '_price',
			'value'   => $max_price,
			'compare' => '<',
			'type'    => 'numeric',
		);

		$min_price_query = empty( $min_price ) ? array() : array(
			'key'     => '_price',
			'value'   => $min_price,
			'compare' => '>=',
			'type'    => 'numeric',
		);

		if ( empty( $min_price_query ) && empty( $max_price_query ) ) {
			return array();
		}

		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => array(
				array(
					'relation' => 'AND',
					$max_price_query,
					$min_price_query,
				),
			),
		);
	}

	/**
	 * Return a query that filters products by attributes.
	 *
	 * @return array
	 */
	private function get_filter_by_attributes_query() {
		$attributes_filter_query_args = $this->get_filter_by_attributes_query_vars();

		$queries = array_reduce(
			$attributes_filter_query_args,
			function ( $acc, $query_args ) {
				$attribute_name       = $query_args['filter'];
				$attribute_query_type = $query_args['query_type'];

				$attribute_value = get_query_var( $attribute_name );
				$attribute_query = get_query_var( $attribute_query_type );

				if ( empty( $attribute_value ) ) {
					return $acc;
				}

				// It is necessary explode the value because $attribute_value can be a string with multiple values (e.g. "red,blue").
				$attribute_value = explode( ',', $attribute_value );

				$acc[] = array(
					'taxonomy' => str_replace( AttributeFilter::FILTER_QUERY_VAR_PREFIX, 'pa_', $attribute_name ),
					'field'    => 'slug',
					'terms'    => $attribute_value,
					'operator' => 'and' === $attribute_query ? 'AND' : 'IN',
				);

				return $acc;
			},
			array()
		);

		if ( empty( $queries ) ) {
			return array();
		}

		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery
			'tax_query' => array(
				array(
					'relation' => 'AND',
					$queries,
				),
			),
		);
	}

	/**
	 * Get all the query args related to the filter by attributes block.
	 *
	 * @return array
	 * [color] => Array
	 *   (
	 *        [filter] => filter_color
	 *        [query_type] => query_type_color
	 *    )
	 *
	 * [size] => Array
	 *    (
	 *        [filter] => filter_size
	 *        [query_type] => query_type_size
	 *    )
	 * )
	 */
	private function get_filter_by_attributes_query_vars() {
		if ( ! empty( $this->attributes_filter_query_args ) ) {
			return $this->attributes_filter_query_args;
		}

		$this->attributes_filter_query_args = array_reduce(
			wc_get_attribute_taxonomies(),
			function ( $acc, $attribute ) {
				$acc[ $attribute->attribute_name ] = array(
					'filter'     => AttributeFilter::FILTER_QUERY_VAR_PREFIX . $attribute->attribute_name,
					'query_type' => AttributeFilter::QUERY_TYPE_QUERY_VAR_PREFIX . $attribute->attribute_name,
				);
				return $acc;
			},
			array()
		);

		return $this->attributes_filter_query_args;
	}

	/**
	 * Return a query that filters products by stock status.
	 *
	 * @return array
	 */
	private function get_filter_by_stock_status_query() {
		$filter_stock_status_values = get_query_var( StockFilter::STOCK_STATUS_QUERY_VAR );

		if ( empty( $filter_stock_status_values ) ) {
			return array();
		}

		$filtered_stock_status_values = array_filter(
			explode( ',', $filter_stock_status_values ),
			function ( $stock_status ) {
				return in_array( $stock_status, StockFilter::get_stock_status_query_var_values(), true );
			}
		);

		if ( empty( $filtered_stock_status_values ) ) {
			return array();
		}

		return array(
			// Ignoring the warning of not using meta queries.
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => array(
				array(
					'key'      => '_stock_status',
					'value'    => $filtered_stock_status_values,
					'operator' => 'IN',
				),
			),
		);
	}

	/**
	 * Return a query that filters products by rating.
	 *
	 * @return array
	 */
	private function get_filter_by_rating_query() {
		$filter_rating_values = get_query_var( RatingFilter::RATING_QUERY_VAR );
		if ( empty( $filter_rating_values ) ) {
			return array();
		}

		$parsed_filter_rating_values = explode( ',', $filter_rating_values );
		$product_visibility_terms    = wc_get_product_visibility_term_ids();

		if ( empty( $parsed_filter_rating_values ) || empty( $product_visibility_terms ) ) {
			return array();
		}

		$rating_terms = array_map(
			function ( $rating ) use ( $product_visibility_terms ) {
				return $product_visibility_terms[ 'rated-' . $rating ];
			},
			$parsed_filter_rating_values
		);

		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery
			'tax_query' => array(
				array(
					'field'         => 'term_taxonomy_id',
					'taxonomy'      => 'product_visibility',
					'terms'         => $rating_terms,
					'operator'      => 'IN',
					'rating_filter' => true,
				),
			),
		);
	}

	/**
	 * Constructs a date query for product filtering based on a specified time frame.
	 *
	 * @param array $time_frame {
	 *     Associative array with 'operator' (in or not-in) and 'value' (date string).
	 *
	 *     @type string $operator Determines the inclusion or exclusion of the date range.
	 *     @type string $value    The date around which the range is applied.
	 * }
	 * @return array Date query array; empty if parameters are invalid.
	 */
	private function get_date_query( array $time_frame ): array {
		// Validate time_frame elements.
		if ( empty( $time_frame['operator'] ) || empty( $time_frame['value'] ) ) {
			return array();
		}

		// Determine the query operator based on the 'operator' value.
		$query_operator = 'in' === $time_frame['operator'] ? 'after' : 'before';

		// Construct and return the date query.
		return array(
			'date_query' => array(
				array(
					'column'        => 'post_date_gmt',
					$query_operator => $time_frame['value'],
					'inclusive'     => true,
				),
			),
		);
	}

	/**
	 * Get query arguments for price range filter.
	 * We are adding these extra query arguments to be used in `posts_clauses`
	 * because there are 2 special edge cases we wanna handle for Price range filter:
	 * Case 1: Prices excluding tax are displayed including tax
	 * Case 2: Prices including tax are displayed excluding tax
	 *
	 * Both of these cases require us to modify SQL query to get the correct results.
	 *
	 * See add_price_range_filter_posts_clauses function in this file for more details.
	 *
	 * @param array $price_range Price range with min and max values.
	 * @return array Query arguments.
	 */
	public function get_price_range_query_args( $price_range ) {
		if ( empty( $price_range ) ) {
			return array();
		}

		return array(
			'isProductCollection' => true,
			'priceRange'          => $price_range,
		);
	}

	/**
	 * Add the `posts_clauses` filter to the main query.
	 *
	 * @param array    $clauses The query clauses.
	 * @param WP_Query $query   The WP_Query instance.
	 */
	public function add_price_range_filter_posts_clauses( $clauses, $query ) {
		$query_vars                  = $query->query_vars;
		$is_product_collection_block = $query_vars['isProductCollection'] ?? false;
		if ( ! $is_product_collection_block ) {
			return $clauses;
		}

		$price_range = $query_vars['priceRange'] ?? null;
		if ( empty( $price_range ) ) {
			return $clauses;
		}

		global $wpdb;
		$adjust_for_taxes = $this->should_adjust_price_range_for_taxes();
		$clauses['join']  = $this->append_product_sorting_table_join( $clauses['join'] );

		$min_price = $price_range['min'] ?? null;
		if ( $min_price ) {
			if ( $adjust_for_taxes ) {
				$clauses['where'] .= $this->get_price_filter_query_for_displayed_taxes( $min_price, 'min_price', '>=' );
			} else {
				$clauses['where'] .= $wpdb->prepare( ' AND wc_product_meta_lookup.min_price >= %f ', $min_price );
			}
		}

		$max_price = $price_range['max'] ?? null;
		if ( $max_price ) {
			if ( $adjust_for_taxes ) {
				$clauses['where'] .= $this->get_price_filter_query_for_displayed_taxes( $max_price, 'max_price', '<=' );
			} else {
				$clauses['where'] .= $wpdb->prepare( ' AND wc_product_meta_lookup.max_price <= %f ', $max_price );
			}
		}

		return $clauses;
	}

	/**
	 * Determines if price filters need adjustment based on the tax display settings.
	 *
	 * This function checks if there's a discrepancy between how prices are stored in the database
	 * and how they are displayed to the user, specifically with respect to tax inclusion or exclusion.
	 * It returns true if an adjustment is needed, indicating that the price filters should account for this
	 * discrepancy to display accurate prices.
	 *
	 * @return bool True if the price filters need to be adjusted for tax display settings, false otherwise.
	 */
	private function should_adjust_price_range_for_taxes() {
		$display_setting      = get_option( 'woocommerce_tax_display_shop' ); // Tax display setting ('incl' or 'excl').
		$price_storage_method = wc_prices_include_tax() ? 'incl' : 'excl';

		return $display_setting !== $price_storage_method;
	}

	/**
	 * Join wc_product_meta_lookup to posts if not already joined.
	 *
	 * @param string $sql SQL join.
	 * @return string
	 */
	protected function append_product_sorting_table_join( $sql ) {
		global $wpdb;

		if ( ! strstr( $sql, 'wc_product_meta_lookup' ) ) {
			$sql .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON $wpdb->posts.ID = wc_product_meta_lookup.product_id ";
		}
		return $sql;
	}

	/**
	 * Get query for price filters when dealing with displayed taxes.
	 *
	 * @param float  $price_filter Price filter to apply.
	 * @param string $column Price being filtered (min or max).
	 * @param string $operator Comparison operator for column.
	 * @return string Constructed query.
	 */
	protected function get_price_filter_query_for_displayed_taxes( $price_filter, $column = 'min_price', $operator = '>=' ) {
		global $wpdb;

		// Select only used tax classes to avoid unwanted calculations.
		$product_tax_classes = $wpdb->get_col( "SELECT DISTINCT tax_class FROM {$wpdb->wc_product_meta_lookup};" );

		if ( empty( $product_tax_classes ) ) {
			return '';
		}

		$or_queries = array();

		// We need to adjust the filter for each possible tax class and combine the queries into one.
		foreach ( $product_tax_classes as $tax_class ) {
			$adjusted_price_filter = $this->adjust_price_filter_for_tax_class( $price_filter, $tax_class );
			$or_queries[]          = $wpdb->prepare(
				'( wc_product_meta_lookup.tax_class = %s AND wc_product_meta_lookup.`' . esc_sql( $column ) . '` ' . esc_sql( $operator ) . ' %f )',
				$tax_class,
				$adjusted_price_filter
			);
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->prepare(
			' AND (
				wc_product_meta_lookup.tax_status = "taxable" AND ( 0=1 OR ' . implode( ' OR ', $or_queries ) . ')
				OR ( wc_product_meta_lookup.tax_status != "taxable" AND wc_product_meta_lookup.`' . esc_sql( $column ) . '` ' . esc_sql( $operator ) . ' %f )
			) ',
			$price_filter
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Adjusts a price filter based on a tax class and whether or not the amount includes or excludes taxes.
	 *
	 * This calculation logic is based on `wc_get_price_excluding_tax` and `wc_get_price_including_tax` in core.
	 *
	 * @param float  $price_filter Price filter amount as entered.
	 * @param string $tax_class Tax class for adjustment.
	 * @return float
	 */
	protected function adjust_price_filter_for_tax_class( $price_filter, $tax_class ) {
		$tax_display    = get_option( 'woocommerce_tax_display_shop' );
		$tax_rates      = WC_Tax::get_rates( $tax_class );
		$base_tax_rates = WC_Tax::get_base_tax_rates( $tax_class );

		// If prices are shown incl. tax, we want to remove the taxes from the filter amount to match prices stored excl. tax.
		if ( 'incl' === $tax_display ) {
			/**
			 * Filters if taxes should be removed from locations outside the store base location.
			 *
			 * The woocommerce_adjust_non_base_location_prices filter can stop base taxes being taken off when dealing
			 * with out of base locations. e.g. If a product costs 10 including tax, all users will pay 10
			 * regardless of location and taxes.
			 *
			 * @since 2.6.0
			 *
			 * @internal Matches filter name in WooCommerce core.
			 *
			 * @param boolean $adjust_non_base_location_prices True by default.
			 * @return boolean
			 */
			$taxes = apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ? WC_Tax::calc_tax( $price_filter, $base_tax_rates, true ) : WC_Tax::calc_tax( $price_filter, $tax_rates, true );
			return $price_filter - array_sum( $taxes );
		}

		// If prices are shown excl. tax, add taxes to match the prices stored in the DB.
		$taxes = WC_Tax::calc_tax( $price_filter, $tax_rates, false );

		return $price_filter + array_sum( $taxes );
	}
}
