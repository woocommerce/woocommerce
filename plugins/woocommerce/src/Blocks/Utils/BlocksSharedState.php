<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\Utils;

use InvalidArgumentException;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;

/**
 * Manages the registration of interactivity config and state that is commonly shared by WooCommerce blocks.
 * Initialization only happens on the first call to load_store_config.
 *
 * This is a private API and may change in future versions.
 */
class BlocksSharedState {

	/**
	 * The consent statement for using private APIs of this class.
	 *
	 * @var string
	 */
	private static string $consent_statement = 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce';

	/**
	 * The namespace for interactivity config and shared (non-cart) state.
	 *
	 * @var string
	 */
	private static string $settings_namespace = 'woocommerce';

	/**
	 * The namespace for the shared cart interactivity state.
	 *
	 * @var string
	 */
	private static string $cart_namespace = 'woocommerce/cart';

	/**
	 * Whether the core config has been registered.
	 *
	 * @var bool
	 */
	private static bool $core_config_registered = false;

	/**
	 * Cart state.
	 *
	 * @var array|null
	 */
	private static ?array $blocks_shared_cart_state = null;

	/**
	 * Whether the cart derived-state getters have been registered.
	 *
	 * @var bool
	 */
	private static bool $cart_getters_registered = false;

	/**
	 * Prevent caching on certain pages.
	 *
	 * @return void
	 */
	private static function prevent_cache(): void {
		\WC_Cache_Helper::set_nocache_constants();
		nocache_headers();
	}

	/**
	 * Check that the consent statement was passed.
	 *
	 * @param string $consent_statement The consent statement string.
	 * @return true
	 * @throws InvalidArgumentException If the statement does not match.
	 */
	private static function check_consent( string $consent_statement ): bool {
		if ( $consent_statement !== self::$consent_statement ) {
			throw new InvalidArgumentException( 'This method cannot be called without consenting the API may change.' );
		}

		return true;
	}

	/**
	 * Load store config (currency, locale, core data) into interactivity config.
	 *
	 * @param string $consent_statement The consent statement string.
	 * @return void
	 * @throws InvalidArgumentException If consent statement doesn't match.
	 */
	public static function load_store_config( string $consent_statement ): void {
		self::check_consent( $consent_statement );

		if ( self::$core_config_registered ) {
			return;
		}

		self::$core_config_registered = true;

		wp_interactivity_config( self::$settings_namespace, self::get_currency_data() );
		wp_interactivity_config( self::$settings_namespace, self::get_locale_data() );
	}

	/**
	 * Load cart state into interactivity state.
	 *
	 * @param string $consent_statement The consent statement string.
	 * @return void
	 * @throws InvalidArgumentException If consent statement doesn't match.
	 */
	public static function load_cart_state( string $consent_statement ): void {
		self::check_consent( $consent_statement );

		if ( null === self::$blocks_shared_cart_state ) {
			$cart_exists       = isset( WC()->cart );
			$cart_has_contents = $cart_exists && ! WC()->cart->is_empty();
			if ( $cart_exists ) {
				$cart_response                  = Package::container()->get( Hydration::class )->get_rest_api_response_data( '/wc/store/v1/cart' );
				self::$blocks_shared_cart_state = $cart_response['body'] ?? array();
			} else {
				self::$blocks_shared_cart_state = array();
			}

			if ( $cart_has_contents ) {
				self::prevent_cache();
			}

			// `nonOptimisticProperties` and `restUrl` are infra config, not
			// commerce state, so they live under `wp_interactivity_config`.
			// `restUrl` moved out of reactive state (previously seeded under
			// `state.restUrl`) as part of moving the cart store to the
			// `woocommerce/cart` namespace.
			wp_interactivity_config(
				self::$settings_namespace,
				array(
					'nonOptimisticProperties' => self::get_non_optimistic_properties(),
					'restUrl'                 => get_rest_url(),
				)
			);

			// Cart state now lives under the `woocommerce/cart` namespace. The
			// bare `woocommerce` namespace holds only the shared context and
			// interactivity config.
			//
			// `draftItems` seeds an empty array: server-side there are no drafts
			// yet in general (surfaces seed their own drafts during render — see
			// the draft "birth" section of the shared-stores schema; that lands
			// with the Add to Cart + Options refactor in T6). Seeding the empty
			// array here guarantees the reactive slot exists at first paint so
			// directives reading `state.draftItems` / `state.itemInContext` don't
			// resolve against `undefined` before hydration.
			wp_interactivity_state(
				self::$cart_namespace,
				array(
					'cart'       => self::$blocks_shared_cart_state,
					'draftItems' => array(),
					'noticeId'   => '',
				)
			);

			// Derived-state closures for the `itemInContext` envelope, so
			// server-rendered directives that read the envelope resolve at first
			// paint (parity with the JS getters in cart.ts).
			self::register_cart_getters();
		}
	}

	/**
	 * Register the cart derived-state getters once.
	 *
	 * These closures mirror the JS `itemInContext` envelope getter and its
	 * resolution ladder in
	 * client/blocks/assets/js/base/stores/woocommerce/cart.ts, so that
	 * server-rendered directives that read the envelope resolve at first paint
	 * (matching hydration). Because they read from wp_interactivity_state() and
	 * wp_interactivity_get_context() at call time, they only need to be
	 * registered once.
	 *
	 * Domain-scoped contexts (T12): the line key and filter come from the cart
	 * store's OWN context (`woocommerce/cart`) — an explicit `cartItemKey`, or the
	 * each-item `cartItem.key` a `data-wp-each--cart-item` directive keys under
	 * this namespace (step 1 accepts either, so cart rows resolve their line
	 * server-side). The context product id is resolved through derived state (the
	 * products store's `mainProductInContext`), never by reading the products
	 * context namespace directly.
	 *
	 * Boundary notes — where PHP cannot fully mirror the JS ladder:
	 *
	 * - **Drafts**: server-side `draftItems` is empty in general (surfaces seed
	 *   their own drafts during render; that is T6). With no draft, `draft` is
	 *   null and generic narrowing has nothing to compare, so the envelope
	 *   resolves conservatively: a line key (explicit or each-item) still yields
	 *   an exact line (step 1), but without a draft the id+variation path cannot
	 *   pair a line and `cart` stays null. This matches the JS behavior for the
	 *   same (draft-less) inputs.
	 * - **Purchasable-id resolution**: the JS ladder resolves a draft's parent
	 *   id + variation to the purchasable id via the products store's
	 *   `findProduct`. Server-side we only have a draft to resolve when one was
	 *   seeded; when a draft exists we match on its `id` directly (already the
	 *   purchasable id for simple products). Deterministic variation resolution
	 *   server-side is deferred to the surface that seeds the draft (T6).
	 * - **`cartItemFilter`**: a JS predicate cannot run during PHP seeding, and
	 *   in JS the filter REPLACES generic narrowing (it is the sole narrowing
	 *   authority). Applying generic narrowing server-side when a filter is set
	 *   could render a pairing the filter would reject, so when the context
	 *   carries a `cartItemFilter` the closure resolves conservatively: `cart`
	 *   null, `isInCart` false, draft still resolved. The exact filtered pairing
	 *   is deferred to hydration. See the CONSERVATIVE FILTER FALLBACK comment in
	 *   the closure. There is intentionally no PHP callback mechanism for the
	 *   predicate (out of scope).
	 *
	 * @return void
	 */
	private static function register_cart_getters(): void {
		if ( self::$cart_getters_registered ) {
			return;
		}

		self::$cart_getters_registered = true;

		$cart_namespace = self::$cart_namespace;

		wp_interactivity_state(
			$cart_namespace,
			array(
				'itemInContext' => function () use ( $cart_namespace ) {
					// The cart store reads its OWN context for the line key and
					// filter (T12). The context product id is resolved through the
					// products store's `mainProductInContext` derived state.
					$context = wp_interactivity_get_context( $cart_namespace );
					$state   = wp_interactivity_state( $cart_namespace );
					$items   = $state['cart']['items'] ?? array();

					$draft = self::find_context_draft( $state );

					// Ladder step 1: a line key — an explicit `cartItemKey`, or the
					// each-item context's `cartItem.key` a `data-wp-each--cart-item`
					// directive keys under the `woocommerce/cart` namespace — yields
					// that exact line. Filters never run; cart surfaces are always
					// exact. Resolving the each-item key here is what gives cart rows
					// SSR envelope parity (no client-side key bridge).
					$key = $context['cartItemKey'] ?? ( $context['cartItem']['key'] ?? null );
					if ( $key ) {
						$line = self::find_line_by_key( $items, $key );
						return array(
							'cart'     => $line,
							'draft'    => $draft,
							'isInCart' => null !== $line,
						);
					}

					// CONSERVATIVE FILTER FALLBACK (first-paint, T5):
					// `context.cartItemFilter` is a JS predicate reference that
					// CANNOT run during PHP directive processing. In JS the filter
					// REPLACES generic narrowing and is the sole narrowing
					// authority, so applying generic narrowing here (as if no
					// filter existed) could server-render a pairing the filter
					// would reject (e.g. pair a plain line a bundle-editor filter
					// excludes) — a hydration mismatch and a wrong mutation
					// target. We therefore resolve conservatively when a filter is
					// present: no exact cart line, not in cart. The draft is still
					// resolved (so the surface has its editable draft), and step 1
					// above is unaffected (a keyed surface is always exact). The
					// exact filtered pairing is left to hydration, when the JS
					// predicate can finally run. There is deliberately no PHP
					// callback mechanism for the predicate (out of scope).
					if ( isset( $context['cartItemFilter'] ) ) {
						return array(
							'cart'     => null,
							'draft'    => $draft,
							'isInCart' => false,
						);
					}

					// Without a draft there is nothing to pair against (see the
					// boundary notes on register_cart_getters). Resolve
					// conservatively.
					if ( null === $draft ) {
						return array(
							'cart'     => null,
							'draft'    => null,
							'isInCart' => false,
						);
					}

					// Ladder step 2/3: id-matched candidates, generic narrowing.
					// Both envelope values derive from the SAME survivor set:
					// `cart` needs exactly one survivor (never first-match);
					// `isInCart` needs at least one — pre-narrowing candidates
					// do NOT count, so a product present only as lines the
					// draft cannot account for (e.g. a decorated bundle child)
					// yields isInCart false.
					$candidates = self::find_id_candidates( $items, $draft );
					$survivors  = self::narrow_candidates( $candidates, $draft );
					$cart       = 1 === count( $survivors ) ? $survivors[0] : null;

					return array(
						'cart'     => $cart,
						'draft'    => $draft,
						'isInCart' => count( $survivors ) > 0,
					);
				},
			)
		);
	}

	/**
	 * Find the draft for the current context product id (identity rule 3: one
	 * draft per product context, keyed by the main/context product id).
	 *
	 * Cross-domain resolution (T12): the context product id comes from the
	 * products store's `mainProductInContext` derived state — the cart store never
	 * reads the products context namespace. On the server that closure reads the
	 * `woocommerce/products` context (per-element) or the store's global
	 * `state.productId`, exactly as its JS counterpart does.
	 *
	 * @param array $state The cart store state.
	 * @return array|null The matching draft, or null.
	 */
	private static function find_context_draft( array $state ): ?array {
		$product_id = self::get_context_product_id();
		if ( null === $product_id ) {
			return null;
		}

		foreach ( $state['draftItems'] ?? array() as $draft ) {
			if ( isset( $draft['id'] ) && $draft['id'] === $product_id ) {
				return $draft;
			}
		}

		return null;
	}

	/**
	 * Resolve the current context product id through the products store's
	 * `mainProductInContext` derived state (T12 — cross-domain via derived state,
	 * never a foreign context read). Returns null out of product scope or when the
	 * products store is not populated on this surface.
	 *
	 * @return int|null The context product id, or null.
	 */
	private static function get_context_product_id(): ?int {
		$products_state = wp_interactivity_state( 'woocommerce/products' );
		$main           = $products_state['mainProductInContext'] ?? null;

		$product = $main instanceof \Closure ? $main() : $main;
		if ( is_array( $product ) && isset( $product['id'] ) ) {
			return (int) $product['id'];
		}

		return null;
	}

	/**
	 * Find a cart line by its server key (ladder step 1).
	 *
	 * @param array  $items Cart line items.
	 * @param string $key   The cart item key.
	 * @return array|null The matching line, or null.
	 */
	private static function find_line_by_key( array $items, string $key ): ?array {
		foreach ( $items as $item ) {
			if ( ( $item['key'] ?? null ) === $key ) {
				return $item;
			}
		}

		return null;
	}

	/**
	 * Candidate lines whose purchasable id matches the draft's id (ladder step
	 * 2). Server-side we match on the draft's own id — the purchasable id for
	 * simple products; deterministic variation resolution for a seeded draft is
	 * the seeding surface's responsibility (T6).
	 *
	 * @param array $items Cart line items.
	 * @param array $draft The context draft.
	 * @return array Candidate lines.
	 */
	private static function find_id_candidates( array $items, array $draft ): array {
		$draft_id = $draft['id'] ?? null;
		if ( null === $draft_id ) {
			return array();
		}

		return array_values(
			array_filter(
				$items,
				static function ( $item ) use ( $draft_id ) {
					return isset( $item['key'] ) && ( $item['id'] ?? null ) === $draft_id;
				}
			)
		);
	}

	/**
	 * Narrow id-matched candidates to the generic-exact-pair survivors —
	 * mirrors `narrowCandidates` + `isGenericExactPair` in
	 * cart-item-matching.ts. The envelope derives BOTH cart-side values from
	 * this survivor set: `cart` (exactly one survivor, never first-match) and
	 * `isInCart` (survivors > 0 — "THIS configuration is in the cart"; lines
	 * the draft cannot account for are not survivors and never count).
	 *
	 * @param array $candidates Id-matched candidate lines.
	 * @param array $draft      The context draft.
	 * @return array The surviving lines (re-indexed).
	 */
	private static function narrow_candidates( array $candidates, array $draft ): array {
		$draft_props = self::get_draft_extension_props( $draft );

		return array_values(
			array_filter(
				$candidates,
				static function ( $line ) use ( $draft_props ) {
					return self::is_generic_exact_pair( $draft_props, $line );
				}
			)
		);
	}

	/**
	 * Extract the namespaced extension request params from a draft — every key
	 * that is not a reserved add-item envelope key. Mirrors
	 * `getDraftExtensionProps` in cart-item-matching.ts.
	 *
	 * @param array $draft The draft.
	 * @return array Namespace => value map.
	 */
	private static function get_draft_extension_props( array $draft ): array {
		$reserved = array( 'id', 'quantity', 'variation', 'key', 'type' );
		$props    = array();

		foreach ( $draft as $draft_key => $value ) {
			if ( ! in_array( $draft_key, $reserved, true ) ) {
				$props[ $draft_key ] = $value;
			}
		}

		return $props;
	}

	/**
	 * Whether a draft is a generic-exact-pair with a line: every non-empty
	 * draft prop deep-matches the line's `extensions[ns]` AND the line carries
	 * no unaccounted visible content. Mirrors `isGenericExactPair` in
	 * cart-item-matching.ts.
	 *
	 * @param array $draft_props Draft extension props.
	 * @param array $line        Candidate line.
	 * @return bool True on an exact pair.
	 */
	private static function is_generic_exact_pair( array $draft_props, array $line ): bool {
		return self::draft_props_match_line_extensions( $draft_props, $line )
			&& ! self::line_has_unaccounted_content( $draft_props, $line );
	}

	/**
	 * Per-namespace deep-compare of draft props vs line `extensions[ns]`, with
	 * absent/empty normalization. Mirrors `draftPropsMatchLineExtensions`.
	 *
	 * @param array $draft_props Draft extension props.
	 * @param array $line        Candidate line.
	 * @return bool True when every non-empty draft prop matches.
	 */
	private static function draft_props_match_line_extensions( array $draft_props, array $line ): bool {
		$extensions = $line['extensions'] ?? array();

		foreach ( $draft_props as $ns => $draft_value ) {
			$line_value = $extensions[ $ns ] ?? null;

			if ( self::is_empty_value( $draft_value ) && self::is_empty_value( $line_value ) ) {
				continue;
			}

			if ( $draft_value !== $line_value ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Presence heuristic — mirrors `lineHasUnaccountedContent`. A line with a
	 * non-empty extension the draft has no prop for is always unaccounted;
	 * visible `item_data` counts as unaccounted only when the line exposes no
	 * extension content the draft positively matched.
	 *
	 * @param array $draft_props Draft extension props.
	 * @param array $line        Candidate line.
	 * @return bool True when the line has unaccounted visible content.
	 */
	private static function line_has_unaccounted_content( array $draft_props, array $line ): bool {
		$extensions = $line['extensions'] ?? array();

		$line_namespaces_with_content = array();
		foreach ( $extensions as $ns => $value ) {
			if ( ! self::is_empty_value( $value ) ) {
				$line_namespaces_with_content[] = $ns;
			}
		}

		foreach ( $line_namespaces_with_content as $ns ) {
			if ( self::is_empty_value( $draft_props[ $ns ] ?? null ) ) {
				return true;
			}
		}

		if ( count( $line_namespaces_with_content ) > 0 ) {
			return false;
		}

		foreach ( $line['item_data'] ?? array() as $entry ) {
			if ( empty( $entry['hidden'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a value counts as "empty" for absent/empty normalization: null,
	 * '', or an empty array. Mirrors `isEmptyValue` (the JS `{}`/`[]` cases both
	 * map to PHP empty arrays).
	 *
	 * @param mixed $value The value to test.
	 * @return bool True when empty.
	 */
	private static function is_empty_value( $value ): bool {
		if ( null === $value || '' === $value ) {
			return true;
		}

		return is_array( $value ) && 0 === count( $value );
	}

	/**
	 * Get currency data to include in settings.
	 *
	 * @return array
	 */
	private static function get_currency_data(): array {
		$currency = get_woocommerce_currency();

		return array(
			'currency' => array(
				'code'              => $currency,
				'precision'         => wc_get_price_decimals(),
				'symbol'            => html_entity_decode( get_woocommerce_currency_symbol( $currency ) ),
				'symbolPosition'    => get_option( 'woocommerce_currency_pos' ),
				'decimalSeparator'  => wc_get_price_decimal_separator(),
				'thousandSeparator' => wc_get_price_thousand_separator(),
				'priceFormat'       => html_entity_decode( get_woocommerce_price_format() ),
			),
		);
	}

	/**
	 * Get locale data to include in settings.
	 *
	 * @return array
	 */
	private static function get_locale_data(): array {
		global $wp_locale;

		return array(
			'locale' => array(
				'siteLocale'    => get_locale(),
				'userLocale'    => get_user_locale(),
				'weekdaysShort' => array_values( $wp_locale->weekday_abbrev ),
			),
		);
	}

	/**
	 * Get cart properties that cannot use optimistic UI on the frontend.
	 *
	 * Detects whether third-party code has registered callbacks on filters that
	 * modify cart property values. When callbacks are present, the corresponding
	 * property must use the server-computed value instead of a client-side
	 * optimistic computation.
	 *
	 * `@return` string[] List of cart property paths (dot-delimited) that cannot be optimistic.
	 *
	 * @return string[] List of cart property paths (dot-delimited) that cannot be optimistic.
	 */
	private static function get_non_optimistic_properties(): array {
		$properties = array();

		if ( has_filter( 'woocommerce_cart_contents_count' ) ) {
			$properties[] = 'cart.items_count';
		}

		return $properties;
	}

	/**
	 * Load placeholder image into interactivity config.
	 *
	 * @param string $consent_statement The consent statement string.
	 * @return void
	 * @throws InvalidArgumentException If consent statement doesn't match.
	 */
	public static function load_placeholder_image( string $consent_statement ): void {
		self::check_consent( $consent_statement );

		wp_interactivity_config(
			self::$settings_namespace,
			array( 'placeholderImgSrc' => wc_placeholder_img_src() )
		);
	}

	/**
	 * Get cart errors formatted as notices for the store-notices interactivity store.
	 *
	 * Returns errors from the hydrated cart state in the format expected by
	 * the store-notices store context.
	 *
	 * @param string $consent_statement The consent statement string.
	 * @return array Array of notices with id, notice, type, and dismissible keys.
	 * @throws InvalidArgumentException If consent statement doesn't match.
	 */
	public static function get_cart_error_notices( string $consent_statement ): array {
		self::check_consent( $consent_statement );

		// Ensure cart state is loaded so this method works independently.
		if ( null === self::$blocks_shared_cart_state ) {
			self::load_cart_state( $consent_statement );
		}

		$errors  = self::$blocks_shared_cart_state['errors'] ?? array();
		$notices = array();

		foreach ( $errors as $error ) {
			$notices[] = array(
				'id'          => wp_unique_id( 'store-notice-' ),
				'notice'      => $error['message'] ?? '',
				'type'        => 'error',
				'dismissible' => true,
			);
		}

		return $notices;
	}
}
