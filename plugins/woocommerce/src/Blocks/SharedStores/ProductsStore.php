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
 * (client/blocks/assets/js/base/stores/woocommerce/scope.ts) so that
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
	 * client/blocks/assets/js/base/stores/woocommerce/scope.ts so that
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
	 * The `woocommerce` state is writable by any plugin, theme or block, so
	 * a value of the wrong shape at any depth is treated as absent, and
	 * resolution continues in the usual order (record, then context, then
	 * template).
	 *
	 * @return array<string, \Closure> The envelope, keyed by member name.
	 */
	private static function build_product_scope_envelope(): array {
		$context = wp_interactivity_get_context( self::$store_namespace );
		$state   = wp_interactivity_state( self::$store_namespace );

		$product_scopes = self::as_array( $state['productScopes'] ?? array() );
		$scope_key      = $context['scopeName'] ?? '_default';
		if ( ! self::is_usable_as_array_key( $scope_key ) ) {
			$scope_key = '_default';
		}
		$record   = self::as_array( $product_scopes[ $scope_key ] ?? array() );
		$template = self::as_array( $state['template'] ?? array() );

		$resolve = function ( string $key, $fallback, callable $is_valid ) use ( $context, $template ) {
			if ( array_key_exists( $key, $context ) && $is_valid( $context[ $key ] ) ) {
				return $context[ $key ];
			}
			if ( array_key_exists( $key, $template ) && $is_valid( $template[ $key ] ) ) {
				return $template[ $key ];
			}
			return $fallback;
		};

		$scope_name = function () use ( $scope_key ) {
			return $scope_key;
		};

		$record_draft = self::as_array( $record['draftCartItem'] ?? array() );

		$product_id = function () use ( $record_draft, $resolve ) {
			if ( array_key_exists( 'id', $record_draft ) && self::is_usable_as_array_key( $record_draft['id'] ) ) {
				return $record_draft['id'];
			}
			return $resolve( 'productId', null, array( self::class, 'is_usable_as_array_key' ) );
		};

		$variation = function () use ( $record_draft, $resolve ) {
			if ( array_key_exists( 'variation', $record_draft ) && is_array( $record_draft['variation'] ) ) {
				return $record_draft['variation'];
			}
			return $resolve( 'variation', array(), 'is_array' );
		};

		$base_product = function () use ( $state, $product_id ) {
			$id = $product_id();
			if ( ! $id ) {
				return null;
			}
			return self::as_array_or_null( self::as_array( $state['products'] ?? array() )[ $id ] ?? null );
		};

		$product_variation = function () use ( $state, $base_product, $variation ) {
			return self::find_matching_variation( $base_product(), self::as_array( $state['productVariations'] ?? array() ), $variation() );
		};

		$product = function () use ( $product_variation, $base_product ) {
			return $product_variation() ?? $base_product();
		};

		$cart_item = function () use ( $state, $context, $product, $variation ) {
			$items = self::as_array( self::as_array( $state['cart'] ?? array() )['items'] ?? array() );
			$key   = $context['cartItemKey'] ?? null;

			if ( $key ) {
				foreach ( $items as $item ) {
					$item = self::as_array( $item );
					if ( ( $item['key'] ?? null ) === $key ) {
						return $item;
					}
				}
				return null;
			}

			$resolved_product = $product();
			$id               = $resolved_product['id'] ?? null;

			return self::is_usable_as_array_key( $id ) ? self::find_matching_cart_item( $items, $state, $id, $variation() ) : null;
		};

		$draft_cart_item = function () use ( $record_draft, $product_id, $variation ) {
			$draft = $record_draft;

			if ( ! array_key_exists( 'id', $draft ) || ! self::is_usable_as_array_key( $draft['id'] ) ) {
				$draft['id'] = $product_id();
			}
			if ( ! array_key_exists( 'variation', $draft ) || ! is_array( $draft['variation'] ) ) {
				$draft['variation'] = $variation();
			}
			if ( ! array_key_exists( 'quantity', $draft ) ) {
				$draft['quantity'] = 1;
			}

			return $draft;
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
	 * Returns $value when it is an array, or $fallback otherwise.
	 *
	 * Used to treat a value read out of the externally writable
	 * `woocommerce` Interactivity API state as absent when its shape does
	 * not match what the envelope requires, instead of handing it to a
	 * strictly typed array parameter.
	 *
	 * @param mixed $value    The value read from seeded state or context.
	 * @param array $fallback The value to use when $value is not an array.
	 * @return array $value when it is an array, otherwise $fallback.
	 */
	private static function as_array( $value, array $fallback = array() ): array {
		return is_array( $value ) ? $value : $fallback;
	}

	/**
	 * Whether $value can be used as an array offset (a string or an int).
	 *
	 * Guards a resolved product ID, and the resolved scope name, before
	 * either indexes into state: PHP throws when an array is used as an
	 * array offset.
	 *
	 * @param mixed $value The value to check.
	 * @return bool Whether $value is a string or an int.
	 */
	private static function is_usable_as_array_key( $value ): bool {
		return is_string( $value ) || is_int( $value );
	}

	/**
	 * Returns $value when it is an array, or null otherwise.
	 *
	 * Used for an entry keyed out of externally writable state whose
	 * malformed shape resolves to no entry at all, rather than to an empty
	 * one — an offset into `products` or `productVariations`, handed to a
	 * `?array` parameter or offset.
	 *
	 * @param mixed $value The value read from seeded state.
	 * @return array|null $value when it is an array, otherwise null.
	 */
	private static function as_array_or_null( $value ): ?array {
		return is_array( $value ) ? $value : null;
	}

	/**
	 * Returns $value when it is a string, or $fallback otherwise.
	 *
	 * Used to treat a value read out of externally writable state as absent
	 * when its shape does not match a strictly typed string parameter.
	 *
	 * @param mixed  $value    The value read from seeded state or context.
	 * @param string $fallback The value to use when $value is not a string.
	 * @return string $value when it is a string, otherwise $fallback.
	 */
	private static function as_string( $value, string $fallback = '' ): string {
		return is_string( $value ) ? $value : $fallback;
	}

	/**
	 * Returns $value when it is a string, or null otherwise.
	 *
	 * Used for a selected attribute's value, which is compared for identity
	 * against `null` before it is compared for equality, so a malformed
	 * value has to normalize to `null` rather than to an empty string.
	 *
	 * @param mixed $value The value read from seeded state or context.
	 * @return string|null $value when it is a string, otherwise null.
	 */
	private static function as_string_or_null( $value ): ?string {
		return is_string( $value ) ? $value : null;
	}

	/**
	 * Find the loaded variation matching a resolved selection, the way
	 * `scope.ts`'s `findMatchingVariationId` does: the base product's own
	 * `variations` summary (each entry's `attributes` a list of
	 * `{ name, value }`) is matched against the selection to find the
	 * variation's ID, which is then looked up in the full loaded
	 * variations — `state.productVariations` itself carries no attributes
	 * to match against.
	 *
	 * A candidate summary matches only when its `attributes` is itself an
	 * array; a summary carrying anything else does not match, and the
	 * search continues with the next summary.
	 *
	 * @param array|null $base_product       The base product, or null when it has not been loaded.
	 * @param array      $product_variations Variations keyed by ID, as held in state.
	 * @param array      $variation          The resolved selection, as `{ attribute, value }` entries.
	 * @return array|null The matching variation, or null when none matches.
	 */
	private static function find_matching_variation( ?array $base_product, array $product_variations, array $variation ): ?array {
		foreach ( self::as_array( $base_product['variations'] ?? array() ) as $summary ) {
			// The Store API schema builds each variation summary as an object.
			$candidate            = (array) $summary;
			$candidate_attributes = $candidate['attributes'] ?? null;

			if ( ! is_array( $candidate_attributes ) || ! self::variation_attributes_match( $candidate_attributes, $variation ) ) {
				continue;
			}

			$variation_id = $candidate['id'] ?? null;
			if ( ! self::is_usable_as_array_key( $variation_id ) ) {
				return null;
			}

			return self::as_array_or_null( $product_variations[ $variation_id ] ?? null );
		}

		return null;
	}

	/**
	 * Check a variation summary's `attributes` (each `{ name, value }`)
	 * against a resolved selection: every attribute needs a selected entry
	 * with the same value, except a null attribute value, which only needs
	 * any selected entry for that attribute to be present. A non-array
	 * attribute or selection entry is treated as carrying no name and no
	 * value.
	 *
	 * @param array $candidate_attributes The variation summary's attributes, each `{ name, value }`.
	 * @param array $variation            The resolved selection, as `{ attribute, value }` entries.
	 * @return bool Whether every attribute is satisfied by the selection.
	 */
	private static function variation_attributes_match( array $candidate_attributes, array $variation ): bool {
		foreach ( $candidate_attributes as $attribute ) {
			$attribute = self::as_array( $attribute );
			$selected  = null;

			foreach ( $variation as $entry ) {
				$entry = self::as_array( $entry );
				if ( self::attribute_names_match( self::as_string( $attribute['name'] ?? null ), self::as_string( $entry['attribute'] ?? null ) ) ) {
					$selected = $entry;
					break;
				}
			}

			$selected_value = null === $selected ? null : self::as_string_or_null( $selected['value'] ?? null );

			if ( null === ( $attribute['value'] ?? null ) ) {
				if ( null === $selected_value ) {
					return false;
				}
				continue;
			}

			if ( $selected_value !== $attribute['value'] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Find the cart line matching a resolved product ID and variation
	 * selection, the way `cart-actions.ts`'s `findCartLine` does. A line
	 * whose own `id` is not usable as an array key never matches and the
	 * search continues with the next line.
	 *
	 * @param array      $items     The seeded cart lines.
	 * @param array      $state     The full `woocommerce` state, for attribute matching.
	 * @param int|string $id        The resolved product's ID (a variation ID when one is selected).
	 * @param array      $variation The resolved selection, as `{ attribute, value }` entries.
	 * @return array|null The matching cart line, or null when none matches.
	 */
	private static function find_matching_cart_item( array $items, array $state, $id, array $variation ): ?array {
		foreach ( $items as $item ) {
			$item    = self::as_array( $item );
			$item_id = $item['id'] ?? null;

			if ( ! self::is_usable_as_array_key( $item_id ) ) {
				continue;
			}

			if ( 'variation' === ( $item['type'] ?? null ) ) {
				$item_variation = $item['variation'] ?? null;

				if (
					(int) $item_id !== (int) $id
					|| ! is_array( $item_variation )
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

			if ( (int) $item_id === (int) $id ) {
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

		$item_id            = $cart_item['id'] ?? null;
		$product_variations = self::as_array( $state['productVariations'] ?? array() );
		$variation_entry    = self::is_usable_as_array_key( $item_id ) ? ( $product_variations[ $item_id ] ?? null ) : null;
		$parent_id          = self::as_array( $variation_entry )['parent'] ?? null;
		$parent_products    = self::as_array( $state['products'] ?? array() );
		$parent_entry       = self::is_usable_as_array_key( $parent_id ) ? ( $parent_products[ $parent_id ] ?? null ) : null;
		$product_attributes = self::as_array( self::as_array( $parent_entry )['attributes'] ?? array() );

		foreach ( $cart_item_variation as $entry ) {
			$entry     = self::as_array( $entry );
			$attribute = self::as_string( $entry['attribute'] ?? null );
			$term_name = self::as_string( $entry['value'] ?? null );
			$term_slug = self::resolve_term_slug( $product_attributes, $attribute, $term_name );
			$matched   = false;

			foreach ( $selected_attributes as $selected ) {
				$selected = self::as_array( $selected );
				if (
					self::attribute_names_match( self::as_string( $selected['attribute'] ?? null ), $attribute )
					&& strtolower( self::as_string( $selected['value'] ?? null ) ) === strtolower( $term_slug )
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
	 * @return string The term's slug, or $term_name when no matching term is found or its slug is not a string.
	 */
	private static function resolve_term_slug( array $product_attributes, string $attribute, string $term_name ): string {
		foreach ( $product_attributes as $raw_attribute ) {
			// The Store API schema builds each attribute, and each of its terms, as an object.
			$product_attribute = (array) $raw_attribute;

			if ( ! self::attribute_names_match( $attribute, self::as_string( $product_attribute['name'] ?? null ) ) ) {
				continue;
			}

			foreach ( self::as_array( $product_attribute['terms'] ?? array() ) as $raw_term ) {
				$term = (array) $raw_term;

				if ( ( $term['name'] ?? null ) === $term_name ) {
					$slug = $term['slug'] ?? null;
					return is_string( $slug ) ? $slug : $term_name;
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
