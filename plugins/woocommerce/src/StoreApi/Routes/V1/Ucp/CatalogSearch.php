<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

use Automattic\WooCommerce\Enums\CatalogVisibility;
use Automattic\WooCommerce\StoreApi\Utilities\ProductQuery;
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
	 * The term reaches a `LIKE` scan over product titles and SKUs, so its length
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
	 * The UCP capability this route serves.
	 *
	 * @return string
	 */
	protected function get_capability(): string {
		return UcpUtils::CAPABILITY_CATALOG_SEARCH;
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

		$results = $this->query_products( $this->store_api_params( $query, $filters, $limit, $offset ) );
		$mapped  = $this->mapper()->map_products( $results['objects'] );

		$pagination_result = array( 'has_next_page' => $offset + $limit < $results['total'] );

		if ( $pagination_result['has_next_page'] ) {
			$pagination_result['cursor'] = $this->encode_cursor( $offset + $limit );
		}

		// The total count is inflated by variable products with no published variations.
		// Cleaning them up is an expensive operation which opens the door for DOS-attacks,
		// so we don't do it. Instead, we report the inflated total count, and let the client
		// sort out the mismatch.
		$pagination_result['total_count'] = $results['total'];

		$response = array(
			'ucp'        => $this->ucp_metadata(),
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
	 * Translate the UCP search body into Store API product collection parameters.
	 *
	 * Price bounds pass through unchanged: both UCP and the Store API take them in
	 * minor units, and ProductQuery converts them to the store's decimals and
	 * adjusts them for the shop's tax display mode itself.
	 *
	 * @param string $query Free-text search term.
	 * @param array  $filters Request filters.
	 * @param int    $limit Page size.
	 * @param int    $offset Offset of the first result.
	 * @return array
	 */
	private function store_api_params( string $query, array $filters, int $limit, int $offset ): array {
		$params = array(
			'orderby'            => 'date',
			'order'              => 'desc',
			'per_page'           => $limit,
			'offset'             => $offset,
			'catalog_visibility' => CatalogVisibility::CATALOG,
		);

		if ( '' !== $query ) {
			$params['search'] = $query;
		}

		if ( ! empty( $filters['categories'] ) && is_array( $filters['categories'] ) ) {
			$slugs = $this->resolve_category_slugs( $filters['categories'] );

			if ( ! empty( $slugs ) ) {
				$params['category'] = $slugs;
			}
		}

		if ( isset( $filters['price'] ) && is_array( $filters['price'] ) ) {
			foreach ( array( 'min', 'max' ) as $bound ) {
				if ( isset( $filters['price'][ $bound ] ) ) {
					$params[ $bound . '_price' ] = (int) $filters['price'][ $bound ];
				}
			}
		}

		return $params;
	}

	/**
	 * Resolve category references to the term slugs ProductQuery filters on.
	 *
	 * ProductQuery types a category list from its first element, so a request
	 * mixing ids and slugs is normalized to slugs here. The list is deduplicated
	 * and capped: one request must not drive an unbounded number of term lookups
	 * on an unauthenticated endpoint.
	 *
	 * @param array $references Category term ids or slugs.
	 * @return array
	 */
	private function resolve_category_slugs( array $references ): array {
		$slugs = array();

		foreach ( array_slice( array_unique( $references ), 0, self::MAX_CATEGORY_FILTERS ) as $reference ) {
			$reference = (string) $reference;
			$term      = ctype_digit( $reference )
				? get_term_by( 'id', (int) $reference, 'product_cat' )
				: get_term_by( 'slug', $reference, 'product_cat' );

			if ( $term instanceof \WP_Term ) {
				$slugs[] = $term->slug;
			}
		}

		return $slugs;
	}

	/**
	 * Run the Store API product query, recounting when the cursor is past the end.
	 *
	 * `found_posts` is only computed when the page returned rows, and ProductQuery's
	 * own recount keys off `page` rather than `offset`, so an empty page at a non-zero
	 * offset is recounted here with a single-row query from the start.
	 *
	 * @param array $params Store API product collection parameters.
	 * @return array `{ objects, total }`.
	 */
	private function query_products( array $params ): array {
		$product_query = new ProductQuery();
		$results       = $product_query->get_objects( $this->store_api_request( $params ) );

		if ( empty( $results['objects'] ) && ! empty( $params['offset'] ) ) {
			$params['offset']   = 0;
			$params['per_page'] = 1;
			$results['total']   = $product_query->get_results( $this->store_api_request( $params ) )['total'];
		}

		return $results;
	}

	/**
	 * Wrap collection parameters in the request object ProductQuery reads.
	 *
	 * @param array $params Store API product collection parameters.
	 * @return \WP_REST_Request<array<string, mixed>>
	 */
	private function store_api_request( array $params ): \WP_REST_Request {
		/**
		 * Typed for static analysis; WP_REST_Request is generic in the stubs.
		 *
		 * @var \WP_REST_Request<array<string, mixed>> $request
		 */
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$request->set_query_params( $params );

		return $request;
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
