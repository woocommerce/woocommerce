<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\SharedStores;

use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;
use Automattic\WooCommerce\Blocks\Package;
use InvalidArgumentException;

/**
 * Shared store that hydrates the unified `woocommerce` Interactivity API
 * store with product and variation data in Store API format, and resolves
 * the product a scope element is showing.
 *
 * The store exposes three planes:
 * - Raw data (`products`, `productVariations`) — populated into the
 *   `woocommerce` namespace by the `load_*` methods below, each keyed by
 *   ID.
 * - The `productScope` envelope, registered on `woocommerce` by
 *   `register_getters()`, whose `productId`, `variation`, `baseProduct`,
 *   `productVariation`, `product`, `cartItem` and `draftCartItem` members
 *   resolve for the scope element the markup declares, from a
 *   `productScopes` record first, then the element's `woocommerce`
 *   context, then the seeded `template`.
 * - Selection (`productId`, `variationId`) — set by callers via
 *   `wp_interactivity_state` (global) or `data-wp-context` (per-element) on
 *   the `woocommerce/products` namespace — plus the derived getters
 *   (`mainProductInContext`, `productVariationInContext`,
 *   `productInContext`), also registered on `woocommerce/products` by
 *   `register_getters()`, which read the raw data from `woocommerce`. This
 *   plane is temporary: it reads through to the same raw data as
 *   `productScope` and is removed once every consumer has moved over.
 *
 * The `productScope` envelope is mirrored in the JS store
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
	 * The namespace the raw product and variation data is seeded into.
	 *
	 * @var string
	 */
	private static string $data_namespace = 'woocommerce';

	/**
	 * The namespace for the selection state and derived getters.
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
	 * Returns $value when it is an array, or $fallback otherwise.
	 *
	 * Treats a value read out of the externally writable `woocommerce`
	 * state or the element's context as absent when its shape does not
	 * match, instead of handing it to an array base or a strictly typed
	 * array parameter.
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
	 * Guards a resolved product ID, cart item ID and scope name before any
	 * of them indexes into state: PHP throws when an array or object is
	 * used as an array offset, and a non-integral float offset is
	 * deprecated, so a float is rejected here too.
	 *
	 * @param mixed $value The value to check.
	 * @return bool Whether $value is a string or an int.
	 */
	private static function is_usable_as_array_key( $value ): bool {
		return is_string( $value ) || is_int( $value );
	}

	/**
	 * Returns $value when it is a string, or $fallback otherwise.
	 *
	 * Treats a value read out of externally writable state as absent when
	 * its shape does not match a strictly typed string parameter or
	 * return, or would otherwise trigger an "Array to string conversion"
	 * warning.
	 *
	 * @param mixed  $value    The value read from seeded state or context.
	 * @param string $fallback The value to use when $value is not a string.
	 * @return string $value when it is a string, otherwise $fallback.
	 */
	private static function as_string( $value, string $fallback = '' ): string {
		return is_string( $value ) ? $value : $fallback;
	}

	/**
	 * Register the derived-state getters once.
	 *
	 * Registers the `productScope` envelope on `woocommerce` and the
	 * legacy derived getters on `woocommerce/products`. The legacy closures
	 * mirror the JS getters in
	 * client/blocks/assets/js/base/stores/woocommerce/products.ts so that
	 * directives referencing state.mainProductInContext /
	 * state.productVariationInContext / state.productInContext resolve
	 * during SSR. They resolve the selection from `woocommerce/products`
	 * but read the raw product and variation data from `woocommerce`, the
	 * namespace `load_*` seeds it into. Because they read from
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
			self::$data_namespace,
			array(
				'productScope' => function () {
					return self::build_product_scope_envelope();
				},
			)
		);

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

					$data = wp_interactivity_state( self::$data_namespace );

					return $data['products'][ $product_id ] ?? null;
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

					$data = wp_interactivity_state( self::$data_namespace );

					return $data['productVariations'][ $variation_id ] ?? null;
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
		$context        = wp_interactivity_get_context( self::$data_namespace );
		$state          = wp_interactivity_state( self::$data_namespace );
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
			return $resolve( 'productId', 0, array( self::class, 'is_usable_as_array_key' ) );
		};

		$variation = function () use ( $record_draft, $resolve ) {
			if ( array_key_exists( 'variation', $record_draft ) && is_array( $record_draft['variation'] ) ) {
				return $record_draft['variation'];
			}
			return $resolve( 'variation', array(), 'is_array' );
		};

		$base_product = function () use ( $state, $product_id, $variation ) {
			return self::resolve_product_members( $state, $product_id(), $variation() )['baseProduct'];
		};

		$product_variation = function () use ( $state, $product_id, $variation ) {
			return self::resolve_product_members( $state, $product_id(), $variation() )['productVariation'];
		};

		$product = function () use ( $state, $product_id, $variation ) {
			return self::resolve_product_members( $state, $product_id(), $variation() )['product'];
		};

		$cart_item = function () use ( $state, $context, $product, $product_id, $variation ) {
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

			if ( ! self::is_usable_as_array_key( $id ) ) {
				$id = $product_id();
			}

			return self::find_matching_cart_item( $items, $state, $id, $variation() );
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
	 * Resolve a scope's `baseProduct`, `productVariation` and `product`
	 * together, the way `scope.ts`'s `resolveProductMembers` does: when
	 * `productId` is itself a loaded variation's id, that variation is
	 * `productVariation` and `product`, and its parent is `baseProduct`.
	 * Otherwise `productId` is looked up as a top-level product and, when a
	 * selection is given, matched against its variations.
	 *
	 * @param array      $state      The full `woocommerce` state.
	 * @param int|string $product_id The scope's resolved product id.
	 * @param array      $variation  The scope's resolved selected variation attributes.
	 * @return array{baseProduct: array|null, productVariation: array|null, product: array|null} The three resolved product members.
	 */
	private static function resolve_product_members( array $state, $product_id, array $variation ): array {
		$product_variations = self::as_array( $state['productVariations'] ?? array() );
		$products           = self::as_array( $state['products'] ?? array() );

		$direct_variation = self::is_usable_as_array_key( $product_id ) ? ( $product_variations[ $product_id ] ?? null ) : null;
		$direct_variation = is_array( $direct_variation ) ? $direct_variation : null;

		if ( $direct_variation ) {
			$parent_id    = $direct_variation['parent'] ?? null;
			$base_product = self::is_usable_as_array_key( $parent_id ) ? ( $products[ $parent_id ] ?? null ) : null;

			return array(
				'baseProduct'      => is_array( $base_product ) ? $base_product : null,
				'productVariation' => $direct_variation,
				'product'          => $direct_variation,
			);
		}

		$base_product = self::is_usable_as_array_key( $product_id ) ? ( $products[ $product_id ] ?? null ) : null;
		$base_product = is_array( $base_product ) ? $base_product : null;

		if ( ! $base_product || empty( $variation ) ) {
			return array(
				'baseProduct'      => $base_product,
				'productVariation' => null,
				'product'          => $base_product,
			);
		}

		$product_variation = self::find_matching_variation( $base_product, $product_variations, $variation );

		return array(
			'baseProduct'      => $base_product,
			'productVariation' => $product_variation,
			'product'          => $product_variation ?? $base_product,
		);
	}

	/**
	 * Find the loaded variation matching a resolved selection, the way
	 * `scope.ts`'s `findMatchingVariationId` does: a candidate summary
	 * matches when it has the same attribute count as the selection and,
	 * for each of its attributes, the selection carries an entry whose
	 * value equals the attribute's term slug, case-insensitively (an "Any"
	 * attribute needs only a non-null selected value). The matched
	 * summary's ID is then looked up in the full loaded variations —
	 * `state.productVariations` itself carries no attributes to match
	 * against.
	 *
	 * @param array $base_product       The base product.
	 * @param array $product_variations Variations keyed by ID, as held in state.
	 * @param array $variation          The resolved selection, as `{ attribute, value }` entries.
	 * @return array|null The matching variation, or null when none matches.
	 */
	private static function find_matching_variation( array $base_product, array $product_variations, array $variation ): ?array {
		$product_attributes = self::as_array( $base_product['attributes'] ?? array() );

		foreach ( self::as_array( $base_product['variations'] ?? array() ) as $summary ) {
			// The Store API schema builds each variation summary as an object.
			$candidate            = (array) $summary;
			$candidate_attributes = self::as_array( $candidate['attributes'] ?? array() );

			if ( ! self::variation_attributes_match( $product_attributes, $candidate_attributes, $variation ) ) {
				continue;
			}

			$candidate_id = $candidate['id'] ?? null;
			if ( ! self::is_usable_as_array_key( $candidate_id ) ) {
				return null;
			}

			$matched_variation = $product_variations[ $candidate_id ] ?? null;
			return is_array( $matched_variation ) ? $matched_variation : null;
		}

		return null;
	}

	/**
	 * Check a variation summary's `attributes` (each `{ name, value }`)
	 * against a resolved selection: the counts must match, and every
	 * attribute needs a selected entry whose value equals the attribute's
	 * term slug (resolved via `resolve_term_slug()`), case-insensitively —
	 * except a null attribute value, which only needs any selected entry
	 * for that attribute to be present.
	 *
	 * @param array $product_attributes   The base product's attributes, for slug resolution.
	 * @param array $candidate_attributes The variation summary's attributes, each `{ name, value }`.
	 * @param array $variation            The resolved selection, as `{ attribute, value }` entries.
	 * @return bool Whether every attribute is satisfied by the selection.
	 */
	private static function variation_attributes_match( array $product_attributes, array $candidate_attributes, array $variation ): bool {
		if ( count( $candidate_attributes ) !== count( $variation ) ) {
			return false;
		}

		foreach ( $candidate_attributes as $attribute ) {
			$attribute = self::as_array( $attribute );
			$selected  = null;

			foreach ( $variation as $entry ) {
				$entry = self::as_array( $entry );
				if ( self::attribute_names_match( self::as_string( $attribute['name'] ?? '' ), self::as_string( $entry['attribute'] ?? '' ) ) ) {
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

			if ( null === $selected ) {
				return false;
			}

			$slug = self::resolve_term_slug( $product_attributes, self::as_string( $attribute['name'] ?? '' ), self::as_string( $attribute['value'] ) );

			if ( strtolower( self::as_string( $selected['value'] ?? '' ) ) !== strtolower( $slug ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Find the cart line matching a resolved product ID and variation
	 * selection, the way `cart.ts`'s `findItemInCart` does.
	 *
	 * @param array      $items     The seeded cart lines.
	 * @param array      $state     The full `woocommerce` state, for attribute matching.
	 * @param int|string $id        The resolved product's ID (a variation ID when one is selected), already usable as an array key.
	 * @param array      $variation The resolved selection, as `{ attribute, value }` entries.
	 * @return array|null The matching cart line, or null when none matches.
	 */
	private static function find_matching_cart_item( array $items, array $state, $id, array $variation ): ?array {
		$id_int = (int) $id;

		foreach ( $items as $item ) {
			$item = self::as_array( $item );

			if ( ! self::is_usable_as_array_key( $item['id'] ?? null ) ) {
				continue;
			}

			$item_id = (int) $item['id'];

			if ( 'variation' === ( $item['type'] ?? null ) ) {
				$item_variation = $item['variation'] ?? null;

				if (
					! is_array( $item_variation )
					|| $item_id !== $id_int
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

			if ( $item_id === $id_int ) {
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

		$product_variations = self::as_array( $state['productVariations'] ?? array() );
		$item_id            = $cart_item['id'] ?? null;
		$variation_entry    = self::is_usable_as_array_key( $item_id ) ? self::as_array( $product_variations[ $item_id ] ?? array() ) : array();
		$parent_id          = $variation_entry['parent'] ?? null;

		$products           = self::as_array( $state['products'] ?? array() );
		$parent_entry       = self::is_usable_as_array_key( $parent_id ) ? self::as_array( $products[ $parent_id ] ?? array() ) : array();
		$product_attributes = self::as_array( $parent_entry['attributes'] ?? array() );

		foreach ( $cart_item_variation as $entry ) {
			$entry     = self::as_array( $entry );
			$attribute = self::as_string( $entry['attribute'] ?? '' );
			$term_name = self::as_string( $entry['value'] ?? '' );
			$term_slug = self::resolve_term_slug( $product_attributes, $attribute, $term_name );
			$matched   = false;

			foreach ( $selected_attributes as $selected ) {
				$selected = self::as_array( $selected );
				if (
					self::attribute_names_match( self::as_string( $selected['attribute'] ?? '' ), $attribute )
					&& strtolower( self::as_string( $selected['value'] ?? '' ) ) === strtolower( $term_slug )
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

			if ( ! self::attribute_names_match( $attribute, self::as_string( $product_attribute['name'] ?? '' ) ) ) {
				continue;
			}

			foreach ( self::as_array( $product_attribute['terms'] ?? array() ) as $raw_term ) {
				$term = (array) $raw_term;

				if ( ( $term['name'] ?? null ) === $term_name ) {
					$slug = $term['slug'] ?? $term_name;
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
			self::$data_namespace,
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
			self::$data_namespace,
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
			self::$data_namespace,
			array( 'productVariations' => $keyed_variations )
		);

		return $keyed_variations;
	}
}
