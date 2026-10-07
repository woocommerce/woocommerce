<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

use Automattic\WooCommerce\StoreApi\Utilities\UcpUtils;

/**
 * UCP `POST /catalog/search` route.
 */
class CatalogSearch extends AbstractCatalogRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ucp-catalog-search';

	/**
	 * Maximum number of `filters.categories` references honoured per request.
	 *
	 * Each reference costs a term lookup on an unauthenticated endpoint.
	 */
	const MAX_CATEGORY_FILTERS = 25;

	/**
	 * Maximum accepted length of the free-text `query`, in bytes.
	 *
	 * The term reaches a `LIKE` scan over post titles and content, so its length
	 * is paid per row examined.
	 */
	const MAX_QUERY_LENGTH = 256;

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
		return '/catalog/search';
	}

	/**
	 * Request body arguments accepted by this route.
	 *
	 * Bounds the UCP contract names its own error codes for (query length, price
	 * range) stay in the handler, so the response carries those codes rather than
	 * `rest_invalid_param`.
	 *
	 * @return array
	 */
	protected function get_request_args(): array {
		return array_merge(
			parent::get_request_args(),
			array(
				'query'      => array(
					'description' => __( 'Free-text search query.', 'woocommerce' ),
					'type'        => 'string',
				),
				'filters'    => array(
					'description' => __( 'Filter criteria to narrow search results.', 'woocommerce' ),
					'type'        => 'object',
				),
				'pagination' => array(
					'description' => __( 'Requested page size and cursor.', 'woocommerce' ),
					'type'        => 'object',
				),
			)
		);
	}

	/**
	 * Handle the request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$query      = trim( (string) $request->get_param( 'query' ) );
		$filters    = (array) ( $request->get_param( 'filters' ) ?? array() );
		$pagination = (array) ( $request->get_param( 'pagination' ) ?? array() );
		$context    = $this->get_context( $request );

		$invalid = $this->validate_search_input( $query, $filters );
		if ( null !== $invalid ) {
			return $invalid;
		}

		$limit  = isset( $pagination['limit'] ) ? min( max( (int) $pagination['limit'], 1 ), 100 ) : 10;
		$offset = $this->decode_cursor( isset( $pagination['cursor'] ) ? (string) $pagination['cursor'] : '' );

		$query_args = array(
			'status'     => 'publish',
			'limit'      => $limit + 1,
			'offset'     => $offset,
			'orderby'    => 'date',
			'order'      => 'DESC',
			'visibility' => 'catalog',
		);

		if ( '' !== $query ) {
			$query_args['s'] = $query;
		}

		// Page and total come from one bounded query; the total is never obtained
		// by materializing every matching product ID.
		$search_result = $this->query_products_with_total( $this->apply_query_filters( $query_args, $filters ) );
		$products      = $search_result['products'];

		$has_next_page = count( $products ) > $limit;
		if ( $has_next_page ) {
			array_pop( $products );
		}

		$mapped            = $this->mapper()->map_products( $products );
		$pagination_result = array( 'has_next_page' => $has_next_page );

		if ( $has_next_page ) {
			$pagination_result['cursor'] = $this->encode_cursor( $offset + $limit );
		}

		// The total count is inflated by variable products with no published variations.
		// Cleaning them up is an expensive operation which opens the door for DOS-attacks,
		// so we don't do it. Instead, we report the inflated total count, and let the client
		// sort out the mismatch.
		$pagination_result['total_count'] = $search_result['total'];

		$response = array(
			'ucp'        => UcpUtils::response_metadata(),
			'products'   => $mapped,
			'pagination' => $pagination_result,
		);

		$messages = $this->truncation_messages( $this->mapper()->take_truncations(), $mapped, '$.products' );

		$currency_warning = $this->currency_warning( $context, $this->store_currency() );
		if ( null !== $currency_warning ) {
			array_unshift( $messages, $currency_warning );
		}

		if ( ! empty( $messages ) ) {
			$response['messages'] = $messages;
		}

		return new \WP_REST_Response( $response, 200 );
	}

	/**
	 * Validate the search query string and filters before any query runs.
	 *
	 * Values that would silently degrade to something meaningless — a price cast
	 * from a non-numeric string, a range whose minimum exceeds its maximum — are
	 * refused rather than executed, so a caller cannot spend database work on a
	 * request that was never going to match.
	 *
	 * @param string $query Free-text search term.
	 * @param array  $filters Request filters.
	 * @return \WP_REST_Response|null Error response, or null when the input is acceptable.
	 */
	private function validate_search_input( string $query, array $filters ) {
		if ( strlen( $query ) > self::MAX_QUERY_LENGTH ) {
			return $this->ucp_error(
				'VALIDATION_ERROR',
				sprintf(
					/* translators: %d: maximum accepted query length in characters. */
					__( 'query exceeds the maximum length of %d characters.', 'woocommerce' ),
					self::MAX_QUERY_LENGTH
				),
				'recoverable',
				400
			);
		}

		if ( ! isset( $filters['price'] ) || ! is_array( $filters['price'] ) ) {
			return null;
		}

		$bounds = array();

		foreach ( array( 'min', 'max' ) as $bound ) {
			if ( ! isset( $filters['price'][ $bound ] ) ) {
				continue;
			}

			$value = $filters['price'][ $bound ];

			if ( ! is_numeric( $value ) || (float) $value < 0 ) {
				return $this->ucp_error(
					'VALIDATION_ERROR',
					sprintf(
						/* translators: %s: price filter bound name, `min` or `max`. */
						__( 'filters.price.%s must be a non-negative amount in minor units.', 'woocommerce' ),
						$bound
					),
					'recoverable',
					400
				);
			}

			$bounds[ $bound ] = (int) $value;
		}

		if ( isset( $bounds['min'], $bounds['max'] ) && $bounds['min'] > $bounds['max'] ) {
			return $this->ucp_error(
				'VALIDATION_ERROR',
				__( 'filters.price.min must not exceed filters.price.max.', 'woocommerce' ),
				'recoverable',
				400
			);
		}

		return null;
	}

	/**
	 * Apply catalog filters to product query arguments.
	 *
	 * @param array $query_args Current query arguments.
	 * @param array $filters Request filters.
	 * @return array Query arguments, plus a `meta_query` key the caller injects separately.
	 */
	private function apply_query_filters( array $query_args, array $filters ): array {
		if ( ! empty( $filters['categories'] ) && is_array( $filters['categories'] ) ) {
			// Deduplicate and cap: one request must not drive an unbounded number
			// of term lookups on an unauthenticated endpoint.
			$category_refs = array_slice( array_unique( $filters['categories'] ), 0, self::MAX_CATEGORY_FILTERS );

			$slugs = array();
			foreach ( $category_refs as $category_ref ) {
				$category_ref = (string) $category_ref;
				// WC_Product_Query's `category` argument matches term SLUGS, not
				// ids — an id array silently matches nothing. Resolve id refs to
				// their slug and validate that slug refs exist.
				$term = ctype_digit( $category_ref )
					? get_term_by( 'id', (int) $category_ref, 'product_cat' )
					: get_term_by( 'slug', $category_ref, 'product_cat' );
				if ( $term instanceof \WP_Term ) {
					$slugs[] = $term->slug;
				}
			}

			if ( ! empty( $slugs ) ) {
				$query_args['category'] = $slugs;
			}
		}

		$price_clause = $this->build_price_clause( isset( $filters['price'] ) && is_array( $filters['price'] ) ? $filters['price'] : array() );
		if ( null !== $price_clause ) {
			$query_args['meta_query'] = $price_clause; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		return $query_args;
	}

	/**
	 * Build the `_price` meta_query clause for a UCP price filter.
	 *
	 * `min_price`/`max_price` are not valid WC_Product_Query vars and are silently
	 * discarded, and WC_Product_Query also drops any `meta_query` passed as a query
	 * var, so the clause is injected at query time instead (see run_product_query()).
	 * UCP filter values arrive in minor units; they are converted to the store's
	 * decimal price units — the format `_price` is stored in — before comparing.
	 *
	 * @param array $price Price filter, `{ min?, max? }` in minor units.
	 * @return array|null
	 */
	private function build_price_clause( array $price ) {
		$has_min = isset( $price['min'] );
		$has_max = isset( $price['max'] );

		if ( ! $has_min && ! $has_max ) {
			return null;
		}

		$clause  = array(
			'key'  => '_price',
			'type' => 'DECIMAL(10,2)',
		);
		$decimal = static function ( $minor_units ) {
			return absint( $minor_units ) / ( 10 ** wc_get_price_decimals() );
		};

		if ( $has_min && $has_max ) {
			$clause['value']   = array( $decimal( $price['min'] ), $decimal( $price['max'] ) );
			$clause['compare'] = 'BETWEEN';
		} elseif ( $has_min ) {
			$clause['value']   = $decimal( $price['min'] );
			$clause['compare'] = '>=';
		} else {
			$clause['value']   = $decimal( $price['max'] );
			$clause['compare'] = '<=';
		}

		return $clause;
	}

	/**
	 * Query WooCommerce products together with the total number of matches.
	 *
	 * The total is WP_Query's `found_posts`, exposed as `total` by the data store
	 * under `paginate`, so no query here exceeds the requested page size.
	 *
	 * @param array $args WC_Product_Query arguments.
	 * @return array `{ products, total }`.
	 */
	private function query_products_with_total( array $args ): array {
		$page = $this->run_paginated_query( $args );

		// `found_posts` is only computed when the page returned rows, so a cursor
		// past the end reports 0. Recount from the start with a single-row query.
		// An empty page at offset 0 really is empty, so it needs no recount.
		if ( empty( $page['products'] ) && ! empty( $args['offset'] ) ) {
			$count_args           = $args;
			$count_args['limit']  = 1;
			$count_args['offset'] = 0;
			$count_args['return'] = 'ids';

			$count         = $this->run_paginated_query( $count_args );
			$page['total'] = $count['total'];
		}

		return $page;
	}

	/**
	 * Run a paginated WC_Product_Query and normalize its result.
	 *
	 * @param array $args WC_Product_Query arguments.
	 * @return array `{ products, total }`.
	 */
	private function run_paginated_query( array $args ): array {
		$args['paginate'] = true;

		$result = $this->run_product_query( $args );

		// Fall back to the page itself when a filter or replacement data store
		// returns a plain array instead of the documented object.
		if ( is_object( $result ) ) {
			$result   = (array) $result;
			$products = isset( $result['products'] ) && is_array( $result['products'] ) ? $result['products'] : array();

			return array(
				'products' => $products,
				'total'    => isset( $result['total'] ) ? max( 0, (int) $result['total'] ) : count( $products ),
			);
		}

		$products = is_array( $result ) ? $result : array();

		return array(
			'products' => $products,
			'total'    => count( $products ),
		);
	}

	/**
	 * Run a single WC_Product_Query, injecting the private `_price` clause.
	 *
	 * @param array $args WC_Product_Query arguments, optionally carrying `meta_query`.
	 * @return array|object Raw data store result.
	 */
	private function run_product_query( array $args ) {
		// A `_price` meta_query clause cannot be passed through WC_Product_Query as a
		// query var (its data store drops the `meta_query` key), so inject it via the
		// data store filter for the duration of this single query only.
		$price_clause = $args['meta_query'] ?? null;
		unset( $args['meta_query'] );

		$filter = null;
		if ( is_array( $price_clause ) ) {
			$filter = static function ( $wp_query_args ) use ( $price_clause ) {
				if ( ! isset( $wp_query_args['meta_query'] ) || ! is_array( $wp_query_args['meta_query'] ) ) {
					$wp_query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				}
				$wp_query_args['meta_query'][] = $price_clause;
				return $wp_query_args;
			};
			add_filter( 'woocommerce_product_data_store_cpt_get_products_query', $filter );
		}

		try {
			return ( new \WC_Product_Query( $args ) )->get_products();
		} finally {
			if ( null !== $filter ) {
				remove_filter( 'woocommerce_product_data_store_cpt_get_products_query', $filter );
			}
		}
	}

	/**
	 * Encode a pagination offset into an opaque cursor string.
	 *
	 * @param int $offset Numeric offset.
	 * @return string
	 */
	private function encode_cursor( int $offset ): string {
		return base64_encode( 'offset:' . $offset ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decode a cursor string back into a numeric offset.
	 *
	 * @param string $cursor Opaque cursor string.
	 * @return int
	 */
	private function decode_cursor( string $cursor ): int {
		if ( '' === $cursor ) {
			return 0;
		}

		$decoded = base64_decode( $cursor, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $decoded || 0 !== strpos( $decoded, 'offset:' ) ) {
			return 0;
		}

		return max( 0, (int) substr( $decoded, 7 ) );
	}
}
