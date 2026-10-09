<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

use Automattic\WooCommerce\Internal\ProductFeed\Mapping\UcpProductMapper;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractRoute;
use Automattic\WooCommerce\StoreApi\Utilities\UcpUtils;

/**
 * Shared behaviour of the UCP catalog routes.
 */
abstract class AbstractCatalogRoute extends AbstractRoute {
	/**
	 * The routes schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'ucp-catalog';

	/**
	 * Product mapper, holding per-response state.
	 *
	 * @var UcpProductMapper|null
	 */
	private $mapper;

	/**
	 * The UCP capability this route serves, one of the UcpUtils::CAPABILITY_* constants.
	 *
	 * @return string
	 */
	abstract protected function get_capability(): string;

	/**
	 * Build the `ucp` envelope for a response from this route.
	 *
	 * @param string|null $status Response status, omitted from the envelope when null.
	 * @return array
	 */
	protected function ucp_metadata( ?string $status = null ): array {
		return UcpUtils::response_metadata( $this->get_capability(), $status );
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return array(
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_request_args(),
			),
			'schema' => array( $this->schema, 'get_public_item_schema' ),
		);
	}

	/**
	 * Request body arguments accepted by this route.
	 *
	 * @return array
	 */
	protected function get_request_args(): array {
		return array(
			'context' => array(
				'description' => __( 'Provisional buyer signals for relevance and localization.', 'woocommerce' ),
				'type'        => 'object',
			),
		);
	}

	/**
	 * Get the product mapper for the response being built.
	 *
	 * @return UcpProductMapper
	 */
	protected function mapper(): UcpProductMapper {
		if ( null === $this->mapper ) {
			$this->mapper = new UcpProductMapper( $this->store_currency() );
		}

		return $this->mapper;
	}

	/**
	 * Get the store's configured currency code.
	 *
	 * @return string
	 */
	protected function store_currency(): string {
		return (string) get_woocommerce_currency();
	}

	/**
	 * Extract the recognized context fields from a request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return array
	 */
	protected function get_context( \WP_REST_Request $request ): array {
		$raw = $request->get_param( 'context' );

		if ( ! is_array( $raw ) ) {
			return array();
		}

		$context = array();

		foreach ( array( 'address_country', 'address_region', 'postal_code', 'intent', 'language', 'currency' ) as $key ) {
			if ( isset( $raw[ $key ] ) && is_scalar( $raw[ $key ] ) && '' !== (string) $raw[ $key ] ) {
				$context[ $key ] = (string) $raw[ $key ];
			}
		}

		return $context;
	}

	/**
	 * Build a transport-level error response.
	 *
	 * @param string $code Error code.
	 * @param string $message Error message.
	 * @param string $severity Error severity.
	 * @param int    $status HTTP status.
	 * @return \WP_REST_Response
	 */
	protected function ucp_error( string $code, string $message, string $severity, int $status ): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'ucp'      => $this->ucp_metadata( 'error' ),
				'messages' => array(
					array(
						'type'         => 'error',
						'code'         => $code,
						'content'      => $message,
						'severity'     => $severity,
						'path'         => '$',
						'content_type' => 'plain',
					),
				),
			),
			$status
		);
	}

	/**
	 * Resolve a catalog product by variant/product ID or SKU.
	 *
	 * The lookup schema requires variant identifiers to be resolvable, so an
	 * identifier naming a variation resolves to its parent product — the variation
	 * is returned as one of that product's variants, not as a self-parented product
	 * of its own. Callers are told which variation was named so they can correlate
	 * it back to the request.
	 *
	 * @param string $identifier Product ID, variation ID or SKU.
	 * @return array|null `{ product, variation_id }`, where `variation_id` is 0 when the identifier
	 *                    named the product itself, or null when nothing catalog-visible matched.
	 */
	protected function resolve_product( string $identifier ) {
		$product_id   = ctype_digit( $identifier ) ? (int) $identifier : (int) wc_get_product_id_by_sku( $identifier );
		$product      = $product_id > 0 ? wc_get_product( $product_id ) : null;
		$variation_id = 0;

		if ( $product instanceof \WC_Product_Variation ) {
			$variation_id = (int) $product->get_id();
			$product      = wc_get_product( $product->get_parent_id() );
		}

		// Catalog visibility as the storefront applies it: published, not hidden
		// from the catalog, and in stock when the store hides out-of-stock items.
		if ( ! $product instanceof \WC_Product || ! $product->is_visible() ) {
			return null;
		}

		if ( ! $this->mapper()->has_catalog_variants( $product ) ) {
			return null;
		}

		// A named variation the storefront would not offer — disabled, or out of
		// stock while the store hides out-of-stock items — is a miss even when
		// its parent is otherwise fine.
		if ( $variation_id > 0 && ( ! $product instanceof \WC_Product_Variable || ! in_array( $variation_id, array_map( 'intval', $product->get_visible_children() ), true ) ) ) {
			return null;
		}

		return array(
			'product'      => $product,
			'variation_id' => $variation_id,
		);
	}

	/**
	 * Build `messages` entries for products whose variant list was cut short.
	 *
	 * UCP has no variant pagination, so this message is the only disclosure of a
	 * shortened list — without it an agent reports the missing variants as
	 * nonexistent. `path` is the RFC 9535 JSONPath of the affected list.
	 *
	 * @param array  $truncations Keyed by product id, from UcpProductMapper::take_truncations().
	 * @param array  $products Mapped products, in response order.
	 * @param string $path_prefix JSONPath of the products container.
	 * @return array
	 */
	protected function truncation_messages( array $truncations, array $products, string $path_prefix ): array {
		if ( empty( $truncations ) ) {
			return array();
		}

		$messages = array();

		foreach ( $products as $index => $product ) {
			$product_id = isset( $product['id'] ) ? (int) $product['id'] : 0;

			if ( ! isset( $truncations[ $product_id ] ) ) {
				continue;
			}

			$truncation = $truncations[ $product_id ];

			$messages[] = array(
				'type'         => 'info',
				'code'         => 'variants_truncated',
				'content'      => sprintf(
					/* translators: 1: number of variants returned, 2: total published variants, 3: product title. */
					__( 'Showing %1$d of %2$d variants for "%3$s". Request this product directly, or name a variant by id, to reach the others.', 'woocommerce' ),
					$truncation['returned'],
					$truncation['total'],
					$truncation['title']
				),
				'path'         => '$.product' === $path_prefix ? '$.product.variants' : sprintf( '%s[%d].variants', $path_prefix, $index ),
				'content_type' => 'plain',
			);
		}

		return $messages;
	}

	/**
	 * Build a non-fatal warning when the requested context currency differs from the store currency.
	 *
	 * Catalog amounts are always expressed in the store's own currency; the store performs no FX
	 * conversion. When an agent requests a different currency we ignore it for labeling and surface
	 * an informational message so the difference is not silently dropped.
	 *
	 * @param array  $context Request context.
	 * @param string $store_currency Store currency code used for labeling.
	 * @return array|null
	 */
	protected function currency_warning( array $context, string $store_currency ) {
		if ( ! isset( $context['currency'] ) || 0 === strcasecmp( $context['currency'], $store_currency ) ) {
			return null;
		}

		return array(
			'type'         => 'warning',
			'code'         => 'currency_not_supported',
			'content'      => sprintf(
				/* translators: 1: requested currency code, 2: store currency code. */
				__( 'This store only transacts in %2$s and does not convert currencies. Prices are returned in %2$s; the requested currency %1$s was ignored.', 'woocommerce' ),
				$context['currency'],
				$store_currency
			),
			'severity'     => 'recoverable',
			'path'         => '$.context.currency',
			'content_type' => 'plain',
		);
	}
}
