<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

use Automattic\WooCommerce\StoreApi\Utilities\UcpUtils;

/**
 * UCP `POST /catalog/lookup` route.
 */
class CatalogLookup extends AbstractCatalogRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ucp-catalog-lookup';

	/**
	 * Maximum number of `ids` honoured per lookup request.
	 *
	 * Each id costs a product load (and a SKU lookup when non-numeric) plus a full
	 * transform on an unauthenticated endpoint. Unlike category filters, an
	 * over-long list is rejected rather than truncated: silently dropping requested
	 * ids would report existing products as missing.
	 */
	const MAX_LOOKUP_IDS = 50;

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
		return '/catalog/lookup';
	}

	/**
	 * Request body arguments accepted by this route.
	 *
	 * `ids` is deliberately not marked required: a missing list answers with the
	 * UCP `VALIDATION_ERROR` envelope rather than `rest_missing_callback_param`.
	 *
	 * @return array
	 */
	protected function get_request_args(): array {
		return array_merge(
			parent::get_request_args(),
			array(
				'ids' => array(
					'description' => __( 'Product, variant or SKU identifiers to look up.', 'woocommerce' ),
					'type'        => 'array',
					'items'       => array( 'type' => array( 'string', 'integer' ) ),
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
		$raw_ids = $request->get_param( 'ids' );

		if ( ! is_array( $raw_ids ) || empty( $raw_ids ) ) {
			return $this->ucp_error( 'VALIDATION_ERROR', __( 'ids is required.', 'woocommerce' ), 'recoverable', 400 );
		}

		// Deduplicate before counting: repeating one id is not a reason to reject,
		// and each distinct id is what actually costs a product load.
		$ids = array_values( array_unique( array_map( 'strval', $raw_ids ) ) );

		if ( count( $ids ) > self::MAX_LOOKUP_IDS ) {
			// `request_too_large` is the code UCP conformance names for a request
			// over a batch size limit, which is exactly what an over-long id list is.
			return $this->ucp_error(
				'request_too_large',
				sprintf(
					/* translators: %d: maximum number of ids accepted per lookup request. */
					__( 'ids accepts at most %d entries per request.', 'woocommerce' ),
					self::MAX_LOOKUP_IDS
				),
				'recoverable',
				400
			);
		}

		$messages = array();

		$currency_warning = $this->currency_warning( $this->get_context( $request ), $this->store_currency() );
		if ( null !== $currency_warning ) {
			$messages[] = $currency_warning;
		}

		// Several identifiers can resolve to the same product (a product id and one
		// of its variation ids, say), so group them and emit one entry per product
		// carrying every identifier that resolved to it.
		$groups = array();

		foreach ( $ids as $id ) {
			$resolved = $this->resolve_product( $id );

			if ( null === $resolved ) {
				$messages[] = array(
					'type'         => 'info',
					'code'         => 'not_found',
					'content'      => sprintf(
						/* translators: %s: product identifier. */
						__( 'Product "%s" not found.', 'woocommerce' ),
						$id
					),
					'path'         => '$.ids',
					'content_type' => 'plain',
				);
				continue;
			}

			$product_id = (int) $resolved['product']->get_id();

			if ( ! isset( $groups[ $product_id ] ) ) {
				$groups[ $product_id ] = array(
					'product' => $resolved['product'],
					'inputs'  => array(),
				);
			}

			$groups[ $product_id ]['inputs'][] = array(
				'id'           => $id,
				'variation_id' => $resolved['variation_id'],
			);
		}

		// One response spans several mapping calls here, so the per-response state
		// is started explicitly rather than by the mapper.
		$this->mapper()->begin_response();

		$products = array();
		foreach ( $groups as $group ) {
			$products[] = $this->mapper()->map_lookup_product( $group['product'], $group['inputs'] );
		}

		$messages = array_merge(
			$messages,
			$this->truncation_messages( $this->mapper()->take_truncations(), $products, '$.products' )
		);

		$response = array(
			'ucp'      => UcpUtils::response_metadata(),
			'products' => $products,
		);

		if ( ! empty( $messages ) ) {
			$response['messages'] = $messages;
		}

		return new \WP_REST_Response( $response, 200 );
	}
}
