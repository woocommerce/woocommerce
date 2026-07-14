<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\SharedStores;

use InvalidArgumentException;

/**
 * Shared store providing the scope service for the `woocommerce/cart`
 * Interactivity API store.
 *
 * A scope is an opaque id that groups the purchase-UI surfaces sharing one
 * set of draft cart items. This class:
 * - Mints a deterministic page scope per request (derived from the queried
 *   entity, e.g. `page/<queried-object-id>`) and seeds it into state via
 *   `wp_interactivity_state( 'woocommerce/cart', … )`.
 * - Maintains a render-time scope stack: the page scope sits at the bottom;
 *   container blocks (a Product Collection loop item, a Single Product
 *   block) `push_scope()` their own scope before rendering inner blocks and
 *   `pop_scope()` afterward.
 * - Exposes `get_current_scope()`, the innermost scope in effect, as the PHP
 *   symmetric of the client `state.currentScope` getter.
 *
 * The page scope is derived deterministically from the queried entity —
 * never randomized per render — so router-region re-renders of the same
 * page reproduce the same scope and never orphan a live draft.
 *
 * See client/blocks/assets/js/base/stores/woocommerce/README.md for the
 * full model and consumer examples.
 *
 * This is an experimental API and may change in future versions.
 */
class CartStore {

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
	private static string $store_namespace = 'woocommerce/cart';

	/**
	 * The page scope minted for the current request, once computed.
	 *
	 * @var string|null
	 */
	private static ?string $page_scope = null;

	/**
	 * The render-time stack of container-pushed scope overrides. The page
	 * scope is not stored here: an empty stack means the page scope is in
	 * effect.
	 *
	 * @var array<int, string>
	 */
	private static array $scope_stack = array();

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
	 * Mint the page scope for the current request, if not already minted,
	 * and seed it into state.
	 *
	 * Memoized for the lifetime of the request: repeated calls return the
	 * same value without re-seeding.
	 *
	 * @return string The page scope.
	 */
	private static function get_or_mint_page_scope(): string {
		if ( null === self::$page_scope ) {
			self::$page_scope = 'page/' . get_queried_object_id();

			wp_interactivity_state(
				self::$store_namespace,
				array( 'pageScope' => self::$page_scope )
			);
		}

		return self::$page_scope;
	}

	/**
	 * Mint the page scope for the current request and seed it into state.
	 *
	 * @param string $consent_statement The consent statement string.
	 * @return string The page scope.
	 * @throws InvalidArgumentException If consent statement doesn't match.
	 */
	public static function mint_page_scope( string $consent_statement ): string {
		self::check_consent( $consent_statement );

		return self::get_or_mint_page_scope();
	}

	/**
	 * Push a scope onto the render-time scope stack.
	 *
	 * Call before rendering the inner blocks of a container that overrides
	 * scope (a Product Collection loop item, a Single Product block), and
	 * pair with a matching `pop_scope()` call afterward.
	 *
	 * @param string $consent_statement The consent statement string.
	 * @param string $scope             The scope to push.
	 * @return void
	 * @throws InvalidArgumentException If consent statement doesn't match.
	 */
	public static function push_scope( string $consent_statement, string $scope ): void {
		self::check_consent( $consent_statement );

		self::$scope_stack[] = $scope;
	}

	/**
	 * Pop the innermost scope off the render-time scope stack.
	 *
	 * A no-op once the stack is empty: the page scope, which is not part of
	 * the stack, is always the floor.
	 *
	 * @param string $consent_statement The consent statement string.
	 * @return void
	 * @throws InvalidArgumentException If consent statement doesn't match.
	 */
	public static function pop_scope( string $consent_statement ): void {
		self::check_consent( $consent_statement );

		array_pop( self::$scope_stack );
	}

	/**
	 * Get the scope in effect at the current point in rendering: the
	 * innermost pushed scope, or the page scope when nothing is pushed.
	 *
	 * @param string $consent_statement The consent statement string.
	 * @return string The current scope.
	 * @throws InvalidArgumentException If consent statement doesn't match.
	 */
	public static function get_current_scope( string $consent_statement ): string {
		self::check_consent( $consent_statement );

		$page_scope = self::get_or_mint_page_scope();

		return empty( self::$scope_stack ) ? $page_scope : end( self::$scope_stack );
	}
}
