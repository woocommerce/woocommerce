<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\SharedStores;

use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;
use Automattic\WooCommerce\Blocks\Package;
use InvalidArgumentException;

/**
 * Shared store that hydrates the `woocommerce` Interactivity API namespace
 * with product and variation data in Store API format, and resolves the
 * product a scope element is showing.
 *
 * The store exposes two planes:
 * - Raw data (`products`, `productVariations`) populated by the `load_*`
 *   methods below, each keyed by ID.
 * - The `productScope` envelope, registered by `register_getters()`, whose
 *   `productId`, `variation`, `baseProduct`, `productVariation`, `product`,
 *   `cartItem` and `draftCartItem` members resolve for the scope element
 *   the markup declares, from a `productScopes` record first, then the
 *   element's `woocommerce` context, then the seeded `template`.
 *
 * The envelope is mirrored in the JS store
 * (client/blocks/assets/js/base/stores/woocommerce/products.ts) so that
 * directive bindings like `state.productScope.product.sku` resolve during
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
	private static string $store_namespace = 'woocommerce';

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
	 * Register the `productScope` derived-state getter once.
	 *
	 * The closure mirrors the JS `productScope` getter in
	 * client/blocks/assets/js/base/stores/woocommerce/products.ts so that
	 * directives referencing state.productScope.* resolve during SSR.
	 * Because it reads wp_interactivity_get_context() and
	 * wp_interactivity_state() at call time, it only needs to be
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
				'productScope' => function () {
					return self::build_product_scope_envelope();
				},
			)
		);
	}

	/**
	 * Build the `productScope` envelope for the element currently rendering.
	 *
	 * Every entry is a closure so the Interactivity API's path walker
	 * resolves `state.productScope.<member>` and, when the member composes
	 * another one, invokes it by hand the way the entries below do.
	 *
	 * @return array<string, \Closure> The envelope, keyed by member name.
	 */
	private static function build_product_scope_envelope(): array {
		$context  = wp_interactivity_get_context( self::$store_namespace );
		$state    = wp_interactivity_state( self::$store_namespace );
		$record   = ( $state['productScopes'] ?? array() )[ $context['scopeName'] ?? '_default' ] ?? array();
		$template = $state['template'] ?? array();

		$resolve = function ( string $key, $fallback ) use ( $record, $context, $template ) {
			if ( array_key_exists( $key, $record ) ) {
				return $record[ $key ];
			}
			if ( array_key_exists( $key, $context ) ) {
				return $context[ $key ];
			}
			return array_key_exists( $key, $template ) ? $template[ $key ] : $fallback;
		};

		$scope_name = function () use ( $context ) {
			return $context['scopeName'] ?? '_default';
		};

		$product_id = function () use ( $resolve ) {
			return $resolve( 'productId', null );
		};

		$variation = function () use ( $resolve ) {
			return $resolve( 'variation', array() );
		};

		$base_product = function () use ( $state, $product_id ) {
			$id = $product_id();
			return $id ? ( $state['products'][ $id ] ?? null ) : null;
		};

		$product_variation = function () use ( $state, $base_product, $variation ) {
			return self::find_matching_variation( $base_product(), $state['productVariations'] ?? array(), $variation() );
		};

		$product = function () use ( $product_variation, $base_product ) {
			return $product_variation() ?? $base_product();
		};

		$cart_item = function () use ( $state, $context, $product, $variation ) {
			$items = $state['cart']['items'] ?? array();
			$key   = $context['cartItemKey'] ?? null;

			if ( $key ) {
				foreach ( $items as $item ) {
					if ( ( $item['key'] ?? null ) === $key ) {
						return $item;
					}
				}
				return null;
			}

			$resolved_product = $product();
			$id               = $resolved_product['id'] ?? null;

			return null === $id ? null : self::find_matching_cart_item( $items, $state, $id, $variation() );
		};

		$draft_cart_item = function () use ( $record, $product_id, $variation ) {
			if ( array_key_exists( 'draftCartItem', $record ) ) {
				return $record['draftCartItem'];
			}

			return array(
				'id'        => $product_id(),
				'variation' => $variation(),
				'quantity'  => 1,
			);
		};

		return array(
			'scopeName'        => $scope_name,
			'productId'        => $product_id,
			'variation'        => $variation,
			'baseProduct'      => $base_product,
			'productVariation' => $product_variation,
			'product'          => $product,
			'cartItem'         => $cart_item,
			'draftCartItem'    => $draft_cart_item,
		);
	}

	/**
	 * Find the loaded variation matching a resolved selection, the way
	 * `findProduct` does in products.ts: the base product's own
	 * `variations` summary (each entry's `attributes` a list of
	 * `{ name, value }`) is matched against the selection to find the
	 * variation's ID, which is then looked up in the full loaded
	 * variations — `state.productVariations` itself carries no attributes
	 * to match against.
	 *
	 * @param array|null $base_product       The base product, or null when it has not been loaded.
	 * @param array      $product_variations Variations keyed by ID, as held in state.
	 * @param array      $variation          The resolved selection, as `{ attribute, value }` entries.
	 * @return array|null The matching variation, or null when none matches.
	 */
	private static function find_matching_variation( ?array $base_product, array $product_variations, array $variation ): ?array {
		foreach ( $base_product['variations'] ?? array() as $summary ) {
			// The Store API schema builds each variation summary as an object.
			$candidate = (array) $summary;

			if ( self::variation_attributes_match( $candidate['attributes'] ?? array(), $variation ) ) {
				return $product_variations[ $candidate['id'] ?? null ] ?? null;
			}
		}

		return null;
	}

	/**
	 * Check a variation summary's `attributes` (each `{ name, value }`)
	 * against a resolved selection: every attribute needs a selected entry
	 * with the same value, except a null attribute value, which only needs
	 * any selected entry for that attribute to be present.
	 *
	 * @param array $candidate_attributes The variation summary's attributes, each `{ name, value }`.
	 * @param array $variation            The resolved selection, as `{ attribute, value }` entries.
	 * @return bool Whether every attribute is satisfied by the selection.
	 */
	private static function variation_attributes_match( array $candidate_attributes, array $variation ): bool {
		foreach ( $candidate_attributes as $attribute ) {
			$selected = null;

			foreach ( $variation as $entry ) {
				if ( self::attribute_names_match( $attribute['name'] ?? '', $entry['attribute'] ?? '' ) ) {
					$selected = $entry;
					break;
				}
			}

			if ( null === ( $attribute['value'] ?? null ) ) {
				if ( null === $selected || null === ( $selected['value'] ?? null ) ) {
					return false;
				}
				continue;
			}

			if ( null === $selected || ( $selected['value'] ?? null ) !== $attribute['value'] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Find the cart line matching a resolved product ID and variation
	 * selection, the way `findItemInCart` does in cart.ts.
	 *
	 * @param array      $items     The seeded cart lines.
	 * @param array      $state     The full `woocommerce` state, for attribute matching.
	 * @param int|string $id        The resolved product's ID (a variation ID when one is selected).
	 * @param array      $variation The resolved selection, as `{ attribute, value }` entries.
	 * @return array|null The matching cart line, or null when none matches.
	 */
	private static function find_matching_cart_item( array $items, array $state, $id, array $variation ): ?array {
		foreach ( $items as $item ) {
			if ( 'variation' === ( $item['type'] ?? null ) ) {
				$item_variation = $item['variation'] ?? null;

				if (
					(int) ( $item['id'] ?? 0 ) !== (int) $id
					|| empty( $item_variation )
					|| empty( $variation )
					|| count( $item_variation ) !== count( $variation )
				) {
					continue;
				}

				if ( self::does_cart_item_match_attributes( $item, $variation, $state ) ) {
					return $item;
				}

				continue;
			}

			if ( (int) ( $item['id'] ?? 0 ) === (int) $id ) {
				return $item;
			}
		}

		return null;
	}

	/**
	 * Check a cart line's `variation` (label values) against a resolved
	 * selection (slug values), resolving each label to the parent
	 * product's matching term slug, the way
	 * base/utils/variations/does-cart-item-match-attributes.ts does.
	 *
	 * @param array $cart_item           The cart line, carrying a `variation` list of `{ attribute, value }` labels.
	 * @param array $selected_attributes The resolved selection, as `{ attribute, value }` entries.
	 * @param array $state               The full `woocommerce` state, to look up the parent product's attributes.
	 * @return bool Whether every one of the cart line's attributes is satisfied by the selection.
	 */
	private static function does_cart_item_match_attributes( array $cart_item, array $selected_attributes, array $state ): bool {
		$cart_item_variation = $cart_item['variation'] ?? null;

		if ( ! is_array( $cart_item_variation ) || count( $cart_item_variation ) !== count( $selected_attributes ) ) {
			return false;
		}

		$parent_id          = $state['productVariations'][ $cart_item['id'] ?? null ]['parent'] ?? null;
		$product_attributes = $state['products'][ $parent_id ]['attributes'] ?? array();

		foreach ( $cart_item_variation as $entry ) {
			$attribute = $entry['attribute'] ?? '';
			$term_name = $entry['value'] ?? '';
			$term_slug = self::resolve_term_slug( $product_attributes, $attribute, $term_name );
			$matched   = false;

			foreach ( $selected_attributes as $selected ) {
				if (
					self::attribute_names_match( $selected['attribute'] ?? '', $attribute )
					&& strtolower( (string) ( $selected['value'] ?? '' ) ) === strtolower( (string) $term_slug )
				) {
					$matched = true;
					break;
				}
			}

			if ( ! $matched ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Resolve a term's display name to its slug, using a product's
	 * `attributes` (Store API format, each carrying a `terms` list).
	 *
	 * @param array  $product_attributes The base product's attributes.
	 * @param string $attribute          The attribute name to look the term up under.
	 * @param string $term_name          The term's display name.
	 * @return string The term's slug, or $term_name when no matching term is found.
	 */
	private static function resolve_term_slug( array $product_attributes, string $attribute, string $term_name ): string {
		foreach ( $product_attributes as $raw_attribute ) {
			// The Store API schema builds each attribute, and each of its terms, as an object.
			$product_attribute = (array) $raw_attribute;

			if ( ! self::attribute_names_match( $attribute, $product_attribute['name'] ?? '' ) ) {
				continue;
			}

			foreach ( $product_attribute['terms'] ?? array() as $raw_term ) {
				$term = (array) $raw_term;

				if ( ( $term['name'] ?? null ) === $term_name ) {
					return $term['slug'] ?? $term_name;
				}
			}

			break;
		}

		return $term_name;
	}

	/**
	 * Check whether two attribute names refer to the same attribute,
	 * matching a Store API label (e.g. "Color") against a meta-style slug
	 * (e.g. "attribute_pa_color"), the way attribute-matching.ts does.
	 *
	 * @param string $a The first attribute name.
	 * @param string $b The second attribute name.
	 * @return bool Whether the names match once normalized.
	 */
	private static function attribute_names_match( string $a, string $b ): bool {
		return self::normalize_attribute_name( $a ) === self::normalize_attribute_name( $b );
	}

	/**
	 * Normalize an attribute name: strip a leading `attribute_` or
	 * `attribute_pa_` prefix, replace hyphens with spaces, and lowercase
	 * it, so a slug and a label for the same attribute compare equal.
	 *
	 * @param string $name The attribute name.
	 * @return string The normalized name.
	 */
	private static function normalize_attribute_name( string $name ): string {
		$name = (string) preg_replace( '/^attribute_(pa_)?/', '', $name );
		$name = str_replace( '-', ' ', $name );

		return strtolower( $name );
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
			array( 'products' => array( $product_id => self::$products[ $product_id ] ) )
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
			array( 'products' => $keyed_products )
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
			array( 'productVariations' => $keyed_variations )
		);

		return $keyed_variations;
	}
}
