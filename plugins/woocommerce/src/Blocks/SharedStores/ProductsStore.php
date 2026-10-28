<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\SharedStores;

use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;
use Automattic\WooCommerce\Blocks\Package;
use InvalidArgumentException;

/**
 * Shared store that hydrates the `woocommerce/products` Interactivity API
 * store with product and variation data in Store API format.
 *
 * The store exposes two planes:
 * - Raw data (`products`, `productVariations`) populated by the `load_*`
 *   methods below, each keyed by ID.
 * - Selection (`productId`, `variationId`) — set by callers via
 *   `wp_interactivity_state` (global) or `data-wp-context` (per-element) —
 *   plus the derived getters (`mainProductInContext`,
 *   `productVariationInContext`, `productInContext`) registered by
 *   `register_getters()`.
 *
 * The derived getters are mirrored in the JS store
 * (client/blocks/assets/js/base/stores/woocommerce/products.ts) so that
 * directive bindings like `state.productInContext.sku` resolve during
 * server-side rendering as well as on the client.
 *
 * See client/blocks/assets/js/base/stores/woocommerce/README.md for the
 * full model and consumer examples.
 *
 * This is an experimental API and may change in future versions.
 */
class ProductsStore {

	/**
	 * The consent statement for using this experimental API.
	 *
	 * @var string
	 */
	private static string $consent_statement = 'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce';

	/**
	 * The namespace for the store.
	 *
	 * @var string
	 */
	private static string $store_namespace = 'woocommerce/products';

	/**
	 * Products that have been loaded into state.
	 *
	 * @var array
	 */
	private static array $products = array();

	/**
	 * Product variations that have been loaded into state.
	 *
	 * @var array
	 */
	private static array $product_variations = array();

	/**
	 * Parent product IDs whose variations have already been loaded.
	 *
	 * @var array<int, true>
	 */
	private static array $loaded_variation_parents = array();

	/**
	 * Whether the derived-state getters have been registered.
	 *
	 * @var bool
	 */
	private static bool $getters_registered = false;

	/**
	 * Check that the consent statement was passed.
	 *
	 * @param string $consent_statement The consent statement string.
	 * @return true
	 * @throws InvalidArgumentException If the statement does not match.
	 */
	private static function check_consent( string $consent_statement ): bool {
		if ( $consent_statement !== self::$consent_statement ) {
			throw new InvalidArgumentException( 'This method cannot be called without consenting that the API may change.' );
		}

		return true;
	}

	/**
	 * Register the derived-state getters once.
	 *
	 * These closures mirror the JS getters in
	 * client/blocks/assets/js/base/stores/woocommerce/products.ts so that
	 * directives referencing state.mainProductInContext /
	 * state.productVariationInContext / state.productInContext resolve
	 * during SSR. Because they read from
	 * wp_interactivity_state() at call time, they only need to be
	 * registered once regardless of how many products are added.
	 *
	 * @return void
	 */
	private static function register_getters(): void {
		if ( self::$getters_registered ) {
			return;
		}

		self::$getters_registered = true;

		wp_interactivity_state(
			self::$store_namespace,
			array(
				'mainProductInContext'      => function () {
					$context    = wp_interactivity_get_context();
					$state      = wp_interactivity_state( self::$store_namespace );
					$product_id = array_key_exists( 'productId', $context )
						? $context['productId']
						: ( $state['productId'] ?? null );

					if ( ! $product_id ) {
						return null;
					}

					return $state['products'][ $product_id ] ?? null;
				},
				'productVariationInContext' => function () {
					$context      = wp_interactivity_get_context();
					$state        = wp_interactivity_state( self::$store_namespace );
					$variation_id = array_key_exists( 'variationId', $context )
						? $context['variationId']
						: ( $state['variationId'] ?? null );

					if ( ! $variation_id ) {
						return null;
					}

					return $state['productVariations'][ $variation_id ] ?? null;
				},
				'productInContext'          => function () {
					$state    = wp_interactivity_state( self::$store_namespace );
					$selected = $state['productVariationInContext'] instanceof \Closure
						? $state['productVariationInContext']()
						: $state['productVariationInContext'];

					if ( $selected ) {
						return $selected;
					}

					return $state['mainProductInContext'] instanceof \Closure
						? $state['mainProductInContext']()
						: $state['mainProductInContext'];
				},
			)
		);
	}

	/**
	 * Store API fields that first-party blocks read from client state, per state key.
	 *
	 * `true` keeps a field whole; an array keeps only the listed keys of an object, or of each item in a list.
	 * A field a directive binding reads must be listed for every kind it can resolve to; the client
	 * re-evaluates bindings on hydration and blanks values missing from state.
	 *
	 * @var array<string, array>
	 */
	private const CLIENT_STATE_FIELDS = array(
		'products'          => array(
			'id'                   => true,
			'type'                 => true,
			'is_purchasable'       => true,
			'is_in_stock'          => true,
			'sold_individually'    => true,
			'add_to_cart'          => array(
				'minimum'     => true,
				'maximum'     => true,
				'multiple_of' => true,
			),
			'variations'           => array(
				'id'         => true,
				'attributes' => array(
					'name'  => true,
					'value' => true,
				),
			),
			'attributes'           => array(
				'name'     => true,
				'taxonomy' => true,
				'terms'    => array(
					'name' => true,
					'slug' => true,
				),
			),
			'price_html'           => true,
			'sku'                  => true,
			'stock_availability'   => array( 'text' => true ),
			'formatted_weight'     => true,
			'formatted_dimensions' => true,
		),
		'productVariations' => array(
			'id'                   => true,
			'type'                 => true,
			'parent'               => true,
			'is_purchasable'       => true,
			'is_in_stock'          => true,
			'sold_individually'    => true,
			'add_to_cart'          => array(
				'minimum'     => true,
				'maximum'     => true,
				'multiple_of' => true,
			),
			'price_html'           => true,
			'description'          => true,
			'sku'                  => true,
			'stock_availability'   => array( 'text' => true ),
			'formatted_weight'     => true,
			'formatted_dimensions' => true,
			'on_sale'              => true,
			'prices'               => array(
				'price'                       => true,
				'regular_price'               => true,
				'currency_code'               => true,
				'currency_symbol'             => true,
				'currency_minor_unit'         => true,
				'currency_decimal_separator'  => true,
				'currency_thousand_separator' => true,
				'currency_prefix'             => true,
				'currency_suffix'             => true,
			),
		),
	);

	/**
	 * Reduce products or variations to the fields client state needs.
	 *
	 * Loader return values keep the full Store API response; only the state write is reduced.
	 *
	 * @param array  $items Products or variations keyed by ID.
	 * @param string $kind  State key: 'products' or 'productVariations'.
	 * @return array Items keyed by ID with only CLIENT_STATE_FIELDS[ $kind ].
	 */
	private static function to_client_state( array $items, string $kind ): array {
		return array_map(
			static fn( array $item ): array => self::pick_fields( $item, self::CLIENT_STATE_FIELDS[ $kind ] ),
			$items
		);
	}

	/**
	 * Keep only the listed keys of $data, recursing into objects and lists of objects.
	 *
	 * @param array $data   Store API object.
	 * @param array $fields Field spec, see CLIENT_STATE_FIELDS.
	 * @return array
	 */
	private static function pick_fields( array $data, array $fields ): array {
		$picked = array();
		foreach ( array_intersect_key( $data, $fields ) as $key => $value ) {
			$nested = $fields[ $key ];
			if ( true === $nested || ! is_array( $value ) ) {
				$picked[ $key ] = $value;
			} elseif ( array_values( $value ) === $value ) {
				$picked[ $key ] = array_map(
					static fn( $entry ) => is_array( $entry ) ? self::pick_fields( $entry, $nested ) : $entry,
					$value
				);
			} else {
				$picked[ $key ] = self::pick_fields( $value, $nested );
			}
		}
		return $picked;
	}

	/**
	 * Load a product into state.
	 *
	 * @param string $consent_statement The consent statement string.
	 * @param int    $product_id        The product ID.
	 * @return array The product data.
	 * @throws InvalidArgumentException If consent statement doesn't match.
	 */
	public static function load_product( string $consent_statement, int $product_id ): array {
		self::check_consent( $consent_statement );

		// Skip loading if product is already in state.
		if ( isset( self::$products[ $product_id ] ) ) {
			return self::$products[ $product_id ];
		}

		$response = Package::container()->get( Hydration::class )->get_rest_api_response_data( '/wc/store/v1/products/' . $product_id );

		self::$products[ $product_id ] = $response['body'] ?? array();
		self::register_getters();
		wp_interactivity_state(
			self::$store_namespace,
			array( 'products' => self::to_client_state( array( $product_id => self::$products[ $product_id ] ), 'products' ) )
		);

		return self::$products[ $product_id ];
	}

	/**
	 * Load all purchasable child products of a parent product into state.
	 *
	 * @param string $consent_statement The consent statement string.
	 * @param int    $parent_id         The parent product ID.
	 * @return array The purchasable child products keyed by ID.
	 * @throws InvalidArgumentException If consent statement doesn't match.
	 */
	public static function load_purchasable_child_products( string $consent_statement, int $parent_id ): array {
		self::check_consent( $consent_statement );

		// Get the parent product to retrieve child IDs.
		$parent_product = wc_get_product( $parent_id );
		if ( ! $parent_product ) {
			return array();
		}

		// Get child product IDs (for grouped products, these are linked products).
		$child_ids = $parent_product->get_children();
		if ( empty( $child_ids ) ) {
			return array();
		}

		// Query child products using include[] filter.
		// The parent[] filter doesn't work for grouped products because
		// their children are standalone products, not variations.
		$include_params = array_map(
			fn( $id ) => 'include[]=' . $id,
			$child_ids
		);
		$query_string   = implode( '&', $include_params );

		$response = Package::container()->get( Hydration::class )->get_rest_api_response_data( '/wc/store/v1/products?' . $query_string );

		if ( empty( $response['body'] ) ) {
			return array();
		}

		// Filter to only purchasable products.
		$purchasable_products = array_filter(
			$response['body'],
			fn( $product ) => $product['is_purchasable']
		);

		// Re-key array by product ID and merge into state.
		// Use array_replace instead of array_merge to preserve numeric keys.
		$keyed_products = array_column( $purchasable_products, null, 'id' );
		self::$products = array_replace( self::$products, $keyed_products );
		self::register_getters();
		wp_interactivity_state(
			self::$store_namespace,
			array( 'products' => self::to_client_state( $keyed_products, 'products' ) )
		);

		return $keyed_products;
	}

	/**
	 * Load all variations of a variable product into state.
	 *
	 * @param string $consent_statement The consent statement string.
	 * @param int    $parent_id         The parent product ID.
	 * @return array The variations keyed by ID.
	 * @throws InvalidArgumentException If consent statement doesn't match.
	 */
	public static function load_variations( string $consent_statement, int $parent_id ): array {
		self::check_consent( $consent_statement );

		// Skip loading if variations for this parent have already been loaded.
		if ( isset( self::$loaded_variation_parents[ $parent_id ] ) ) {
			return array_filter(
				self::$product_variations,
				fn( $variation ) => ( $variation['parent'] ?? 0 ) === $parent_id
			);
		}

		$response = Package::container()->get( Hydration::class )->get_rest_api_response_data( '/wc/store/v1/products?parent[]=' . $parent_id . '&type=variation' );

		self::$loaded_variation_parents[ $parent_id ] = true;

		if ( empty( $response['body'] ) ) {
			return array();
		}

		// Re-key array by variation ID and merge into state.
		// Use array_replace instead of array_merge to preserve numeric keys.
		$keyed_variations         = array_column( $response['body'], null, 'id' );
		self::$product_variations = array_replace( self::$product_variations, $keyed_variations );
		self::register_getters();
		wp_interactivity_state(
			self::$store_namespace,
			array( 'productVariations' => self::to_client_state( $keyed_variations, 'productVariations' ) )
		);

		return $keyed_variations;
	}
}
