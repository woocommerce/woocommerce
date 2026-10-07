<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

use Automattic\WooCommerce\StoreApi\Utilities\UcpUtils;

/**
 * UCP `POST /catalog/product` route.
 */
class CatalogProduct extends AbstractCatalogRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'ucp-catalog-product';

	/**
	 * Maximum number of `selected` options honoured per product request.
	 *
	 * Every option is compared against every attribute of every variation.
	 */
	const MAX_SELECTED_OPTIONS = 25;

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
		return '/catalog/product';
	}

	/**
	 * Request body arguments accepted by this route.
	 *
	 * `id` is deliberately not marked required: a missing id answers with the UCP
	 * `VALIDATION_ERROR` envelope rather than `rest_missing_callback_param`.
	 *
	 * @return array
	 */
	protected function get_request_args(): array {
		return array_merge(
			parent::get_request_args(),
			array(
				'id'       => array(
					'description' => __( 'Product, variant or SKU identifier.', 'woocommerce' ),
					'type'        => array( 'string', 'integer' ),
				),
				'selected' => array(
					'description' => __( 'Option selections used to narrow the returned variants.', 'woocommerce' ),
					'type'        => 'array',
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
		$id = trim( strval( $request->get_param( 'id' ) ) );

		if ( '' === $id ) {
			return $this->ucp_error( 'VALIDATION_ERROR', __( 'id is required.', 'woocommerce' ), 'recoverable', 400 );
		}

		$selected = (array) ( $request->get_param( 'selected' ) ?? array() );

		// Checked before the product is loaded: an over-long selection is refused
		// without paying for the lookup it would have been applied to.
		if ( count( $selected ) > self::MAX_SELECTED_OPTIONS ) {
			return $this->ucp_error(
				'request_too_large',
				sprintf(
					/* translators: %d: maximum number of selected options accepted per request. */
					__( 'selected accepts at most %d options per request.', 'woocommerce' ),
					self::MAX_SELECTED_OPTIONS
				),
				'recoverable',
				400
			);
		}

		$resolved = $this->resolve_product( $id );

		if ( null === $resolved ) {
			// A missing product is reported in the envelope, not as a 404: UCP
			// clients read `ucp.status` and `messages`, not the HTTP status.
			return new \WP_REST_Response(
				array(
					'ucp'      => UcpUtils::response_metadata( 'error' ),
					'messages' => array(
						array(
							'type'         => 'error',
							'code'         => 'not_found',
							'content'      => __( 'Product not found.', 'woocommerce' ),
							'severity'     => 'unrecoverable',
							'path'         => '$.id',
							'content_type' => 'plain',
						),
					),
				),
				200
			);
		}

		$this->mapper()->begin_response();
		$product = $this->mapper()->map_detail_product( $resolved['product'], $this->normalize_selected( $selected ) );

		$response = array(
			'ucp'     => UcpUtils::response_metadata( 'success' ),
			'product' => $product,
		);

		$messages = $this->truncation_messages( $this->mapper()->take_truncations(), array( $product ), '$.product' );

		$currency_warning = $this->currency_warning( $this->get_context( $request ), $this->store_currency() );
		if ( null !== $currency_warning ) {
			array_unshift( $messages, $currency_warning );
		}

		if ( ! empty( $messages ) ) {
			$response['messages'] = $messages;
		}

		return new \WP_REST_Response( $response, 200 );
	}

	/**
	 * Normalize selected options from the request payload.
	 *
	 * @param array $selected Raw selected options.
	 * @return array
	 */
	private function normalize_selected( array $selected ): array {
		$normalized = array();

		foreach ( $selected as $option ) {
			if ( ! is_array( $option ) || ! isset( $option['name'], $option['label'] ) ) {
				continue;
			}

			$entry = array(
				'name'  => strval( $option['name'] ),
				'label' => strval( $option['label'] ),
			);

			if ( isset( $option['id'] ) ) {
				$entry['id'] = strval( $option['id'] );
			}

			$normalized[] = $entry;
		}

		return $normalized;
	}
}
