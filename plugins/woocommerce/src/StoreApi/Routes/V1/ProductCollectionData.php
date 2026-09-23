<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Utilities\ProductQueryFilters;

/**
 * ProductCollectionData route.
 * Get aggregate data from a collection of products.
 *
 * Supports the same parameters as /products, but returns a different response.
 */
class ProductCollectionData extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'product-collection-data';

	/**
	 * The routes schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'product-collection-data';

	/**
	 * Default maximum number of entries accepted in the `calculate_attribute_counts` and
	 * `calculate_taxonomy_counts` parameters. Each entry triggers a full-collection aggregate
	 * query, so this bounds the per-request query fan-out. Matches the batch route's request cap.
	 *
	 * @var int
	 */
	const COUNTS_MAX_ITEMS = 25;

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return self::get_path_regex();
	}

	/**
	 * Get the path of this rest route.
	 *
	 * @return string
	 */
	public static function get_path_regex() {
		return '/products/collection-data';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => $this->get_collection_params(),
				'allow_batch'         => [ 'v1' => true ],
			],
			'schema' => [ $this->schema, 'get_public_item_schema' ],
		];
	}

	/**
	 * Get a collection of posts and add the post title filter option to \WP_Query.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$data    = [
			'min_price'           => null,
			'max_price'           => null,
			'attribute_counts'    => null,
			'stock_status_counts' => null,
			'rating_counts'       => null,
			'taxonomy_counts'     => null,
		];
		$filters = new ProductQueryFilters();

		if ( ! empty( $request['calculate_price_range'] ) ) {
			$filter_request = clone $request;
			$filter_request->set_param( 'min_price', null );
			$filter_request->set_param( 'max_price', null );

			$price_results     = $filters->get_filtered_price( $filter_request );
			$data['min_price'] = $price_results->min_price;
			$data['max_price'] = $price_results->max_price;
		}

		if ( ! empty( $request['calculate_stock_status_counts'] ) ) {
			$filter_request = clone $request;
			$counts         = $filters->get_stock_status_counts( $filter_request );

			$data['stock_status_counts'] = [];

			foreach ( $counts as $key => $value ) {
				$data['stock_status_counts'][] = (object) [
					'status' => $key,
					'count'  => $value,
				];
			}
		}

		if ( ! empty( $request['calculate_attribute_counts'] ) ) {
			$taxonomy__or_queries  = [];
			$taxonomy__and_queries = [];

			foreach ( $request['calculate_attribute_counts'] as $attributes_to_count ) {
				if ( empty( $attributes_to_count['taxonomy'] ) ) {
					continue;
				}

				// Normalize to the canonical taxonomy name before deduping so textual variants
				// (e.g. differing case or surrounding whitespace) collapse to a single query.
				$taxonomy = wc_sanitize_taxonomy_name( $attributes_to_count['taxonomy'] );

				// Resolve numeric attribute IDs (e.g. "3") to their taxonomy name (e.g. "pa_color").
				if ( is_numeric( $taxonomy ) ) {
					$taxonomy = wc_attribute_taxonomy_name_by_id( (int) $taxonomy );
				}

				// Skip anything that is not a registered product attribute taxonomy so non-existent
				// or non-attribute taxonomies do not trigger wasted full-collection queries.
				if ( ! taxonomy_is_product_attribute( $taxonomy ) ) {
					continue;
				}

				if ( empty( $attributes_to_count['query_type'] ) || 'or' === $attributes_to_count['query_type'] ) {
					$taxonomy__or_queries[] = $taxonomy;
				} else {
					$taxonomy__and_queries[] = $taxonomy;
				}
			}

			// Deduplicate within each query type so the same taxonomy requested multiple times with
			// the same query type is counted with a single query. The "or" and "and" query types are
			// counted independently and are not merged across types: the "or" branch removes the active
			// attribute filter before counting while the "and" branch keeps it, so for the same taxonomy
			// the two counts can legitimately differ.
			$taxonomy__or_queries  = array_unique( $taxonomy__or_queries );
			$taxonomy__and_queries = array_unique( $taxonomy__and_queries );

			$data['attribute_counts'] = [];
			// Or type queries need special handling because the attribute, if set, needs removing from the query first otherwise counts would not be correct.
			if ( $taxonomy__or_queries ) {
				foreach ( $taxonomy__or_queries as $taxonomy ) {
					$filter_request    = clone $request;
					$filter_attributes = $filter_request->get_param( 'attributes' );

					if ( ! empty( $filter_attributes ) ) {
						$filter_attributes = array_filter(
							$filter_attributes,
							function ( $query ) use ( $taxonomy ) {
								// $taxonomy is already sanitized, so sanitize the active attribute too for a like-for-like comparison.
								return wc_sanitize_taxonomy_name( $query['attribute'] ) !== $taxonomy;
							}
						);
					}

					$filter_request->set_param( 'attributes', $filter_attributes );
					$counts = $filters->get_attribute_counts( $filter_request, [ $taxonomy ] );

					foreach ( $counts as $key => $value ) {
						$data['attribute_counts'][] = (object) [
							'term'  => $key,
							'count' => $value,
						];
					}
				}
			}

			if ( $taxonomy__and_queries ) {
				$counts = $filters->get_attribute_counts( $request, $taxonomy__and_queries );

				foreach ( $counts as $key => $value ) {
					$data['attribute_counts'][] = (object) [
						'term'  => $key,
						'count' => $value,
					];
				}
			}
		}

		if ( ! empty( $request['calculate_rating_counts'] ) ) {
			$filter_request        = clone $request;
			$counts                = $filters->get_rating_counts( $filter_request );
			$data['rating_counts'] = [];

			foreach ( $counts as $key => $value ) {
				$data['rating_counts'][] = (object) [
					'rating' => $key,
					'count'  => $value,
				];
			}
		}

		if ( ! empty( $request['calculate_taxonomy_counts'] ) ) {
			// Normalize to the canonical taxonomy name before deduping so textual variants
			// (e.g. differing case or surrounding whitespace) collapse to a single query, and keep
			// only registered taxonomies so non-existent ones do not trigger wasted queries.
			$taxonomies              = array_unique( array_filter( array_map( 'wc_sanitize_taxonomy_name', $request['calculate_taxonomy_counts'] ), 'taxonomy_exists' ) );
			$data['taxonomy_counts'] = [];

			if ( $taxonomies ) {
				$counts = $filters->get_taxonomy_counts( $request, $taxonomies );

				foreach ( $counts as $key => $value ) {
					$data['taxonomy_counts'][] = (object) [
						'term'  => $key,
						'count' => $value,
					];
				}
			}
		}

		return rest_ensure_response( $this->schema->get_item_response( $data ) );
	}

	/**
	 * Get the query params for collections of products.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = ( new Products( $this->schema_controller, $this->schema ) )->get_collection_params();

		$params['calculate_price_range'] = [
			'description' => __( 'If true, calculates the minimum and maximum product prices for the collection.', 'woocommerce' ),
			'type'        => 'boolean',
			'default'     => false,
		];

		$params['calculate_stock_status_counts'] = [
			'description' => __( 'If true, calculates stock counts for products in the collection.', 'woocommerce' ),
			'type'        => 'boolean',
			'default'     => false,
		];

		$params['calculate_attribute_counts'] = [
			'description' => __( 'If requested, calculates attribute term counts for products in the collection.', 'woocommerce' ),
			'type'        => 'array',
			'items'       => [
				'type'       => 'object',
				'properties' => [
					'taxonomy'   => [
						'description' => __( 'Taxonomy name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
					'query_type' => [
						'description' => __( 'Filter condition	 being performed which may affect counts. Valid values include "and" and "or".', 'woocommerce' ),
						'type'        => 'string',
						'enum'        => [ 'and', 'or' ],
						'context'     => [ 'view', 'edit' ],
						'readonly'    => true,
					],
				],
			],
			'default'     => [],
			'maxItems'    => self::COUNTS_MAX_ITEMS,
		];

		$params['calculate_rating_counts'] = [
			'description' => __( 'If true, calculates rating counts for products in the collection.', 'woocommerce' ),
			'type'        => 'boolean',
			'default'     => false,
		];

		$params['calculate_taxonomy_counts'] = [
			'description' => __( 'If requested, calculates taxonomy term counts for products in the collection.', 'woocommerce' ),
			'type'        => 'array',
			'items'       => [
				'type'        => 'string',
				'description' => __( 'Taxonomy name.', 'woocommerce' ),
			],
			'default'     => [],
			'maxItems'    => self::COUNTS_MAX_ITEMS,
		];

		return $params;
	}
}
