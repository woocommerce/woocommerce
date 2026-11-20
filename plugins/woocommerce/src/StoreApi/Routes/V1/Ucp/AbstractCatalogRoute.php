<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Ucp;

use Automattic\WooCommerce\Enums\CatalogVisibility;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Internal\ProductFeed\Mapping\UcpProductMapper;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractRoute;
use Automattic\WooCommerce\StoreApi\Schemas\V1\Ucp\UcpProductSchema;
use Automattic\WooCommerce\StoreApi\Utilities\Ucp\Utils;

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
	 * The UCP capability this route serves, one of the Utils::CAPABILITY_* constants.
	 *
	 * @return string
	 */
	abstract protected function get_capability(): string;

	/**
	 * Build the `ucp` envelope for a response from this route.
	 *
	 * @param string $status Response status, `success` or `error`.
	 * @return array
	 */
	protected function ucp_metadata( string $status ): array {
		return Utils::response_metadata( $this->get_capability(), $status );
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
	 * Refuse a non-JSON payload before the handler reads anything.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return \WP_REST_Response
	 */
	public function get_response( \WP_REST_Request $request ) {
		return Utils::refuse_non_json_payload( $request, $this->get_capability() ) ?? parent::get_response( $request );
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
	 * Validate `filters.price`, refusing values that would silently degrade to something meaningless.
	 *
	 * @param array $filters Request filters.
	 * @return \WP_REST_Response|null Error response, or null when the input is acceptable.
	 */
	protected function validate_price_filter( array $filters ) {
		$bounds = array();

		foreach ( array( 'min', 'max' ) as $bound ) {
			if ( ! is_array( $filters['price'] ?? null ) || ! isset( $filters['price'][ $bound ] ) ) {
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
	 * Read the validated `filters.price` bounds, in minor units.
	 *
	 * @param array $filters Request filters.
	 * @return array Optional `min` and `max` keys.
	 */
	protected function price_bounds( array $filters ): array {
		$bounds = array();

		foreach ( array( 'min', 'max' ) as $bound ) {
			if ( is_array( $filters['price'] ?? null ) && isset( $filters['price'][ $bound ] ) ) {
				$bounds[ $bound ] = (int) $filters['price'][ $bound ];
			}
		}

		return $bounds;
	}

	/**
	 * Get the product mapper for the response being built.
	 *
	 * @return UcpProductMapper
	 */
	protected function mapper(): UcpProductMapper {
		if ( null === $this->mapper ) {
			/**
			 * The controller returns the registered subclass; typed for static analysis.
			 *
			 * @var UcpProductSchema $schema
			 */
			$schema       = $this->schema_controller->get( UcpProductSchema::IDENTIFIER );
			$this->mapper = new UcpProductMapper( $this->store_currency(), $schema );
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
		$raw = Utils::json_param( $request, 'context' );

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
		return Utils::error_response( $this->get_capability(), $code, $message, $severity, $status );
	}

	/**
	 * Resolve a catalog product by product or variation ID.
	 *
	 * The lookup schema requires variant identifiers to be resolvable, so an
	 * identifier naming a variation resolves to its parent product — the variation
	 * is returned as one of that product's variants, not as a self-parented product
	 * of its own. Callers are told which variation was named so they can correlate
	 * it back to the request.
	 *
	 * @param string $identifier Product ID or variation ID.
	 * @return array|null `{ product, variation_id }`, where `variation_id` is 0 when the identifier
	 *                    named the product itself, or null when nothing catalog-visible matched.
	 */
	protected function resolve_product( string $identifier ) {
		$product_id   = ctype_digit( $identifier ) ? (int) $identifier : 0;
		$product      = $product_id > 0 ? wc_get_product( $product_id ) : null;
		$variation_id = 0;

		if ( $product instanceof \WC_Product_Variation ) {
			$variation_id = (int) $product->get_id();
			$product      = wc_get_product( $product->get_parent_id() );
		}

		// Visibility as catalog search applies it: published, not "Hidden", and in
		// stock when the store hides out-of-stock items.
		if ( ! $product instanceof \WC_Product || ! self::is_reachable( $product ) ) {
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
	 * Whether an agent can open the product by its ID. is_visible() accepts "Search results
	 * only" products only on a storefront search page, but catalog search returns them, so
	 * they are checked as if shown everywhere, keeping the other rules and the filter. The
	 * filter gets the ID, so a callback that reloads the product sees its saved setting.
	 *
	 * Does not call set_catalog_visibility(): an extension can remove `visible` from
	 * woocommerce_product_visibility_options, and that setter would throw on the temporary value.
	 *
	 * @param \WC_Product $product Product, not a variation.
	 * @return bool
	 */
	private static function is_reachable( \WC_Product $product ): bool {
		if ( CatalogVisibility::SEARCH !== $product->get_catalog_visibility() ) {
			return $product->is_visible();
		}
		// is_viewable() is true for trash when the current user can edit the product.
		// A catalog lookup is a public read, so an unpublished product is never reachable.
		if ( ProductStatus::PUBLISH !== $product->get_status() ) {
			return false;
		}
		// Same core rules as a Shop and search results product, then the public visibility filter.
		$visible = $product->is_viewable();
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $product->is_in_stock() ) {
			$visible = false;
		}
		/**
		 * Filters the product visibility for catalog lookup (same hook as WC_Product::is_visible).
		 *
		 * @since 2.1.0 Filter introduced on WC_Product::is_visible.
		 * @param bool $visible    Whether the product is considered visible.
		 * @param int  $product_id Product ID.
		 */
		return (bool) apply_filters( 'woocommerce_product_is_visible', $visible, $product->get_id() );
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
