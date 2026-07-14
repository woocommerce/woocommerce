<?php
/**
 * WooCommerce Interactivity API Functions
 *
 * Procedural wrappers for interactivity API shared stores.
 * These are experimental APIs and may change in future versions.
 *
 * @package WooCommerce\Functions
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\SharedStores\CartStore;
use Automattic\WooCommerce\Blocks\SharedStores\ProductsStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load a product into the interactivity API state.
 *
 * This is an experimental API and may change in future versions.
 *
 * @see plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/README.md
 *
 * @param string $consent_statement The consent statement acknowledging this is an experimental API.
 * @param int    $product_id        The product ID to load.
 * @return array The product data.
 * @throws InvalidArgumentException If consent statement doesn't match.
 */
function wc_interactivity_api_load_product( string $consent_statement, int $product_id ): array {
	return ProductsStore::load_product( $consent_statement, $product_id );
}

/**
 * Load all purchasable child products of a parent product into the interactivity API state.
 *
 * This is an experimental API and may change in future versions.
 *
 * @see plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/README.md
 *
 * @param string $consent_statement The consent statement acknowledging this is an experimental API.
 * @param int    $parent_id         The parent product ID.
 * @return array The purchasable child products keyed by ID.
 * @throws InvalidArgumentException If consent statement doesn't match.
 */
function wc_interactivity_api_load_purchasable_child_products( string $consent_statement, int $parent_id ): array {
	return ProductsStore::load_purchasable_child_products( $consent_statement, $parent_id );
}

/**
 * Load all variations of a variable product into the interactivity API state.
 *
 * This is an experimental API and may change in future versions.
 *
 * @see plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/README.md
 *
 * @param string $consent_statement The consent statement acknowledging this is an experimental API.
 * @param int    $parent_id         The parent product ID.
 * @return array The variations keyed by ID.
 * @throws InvalidArgumentException If consent statement doesn't match.
 */
function wc_interactivity_api_load_variations( string $consent_statement, int $parent_id ): array {
	return ProductsStore::load_variations( $consent_statement, $parent_id );
}

/**
 * Mint the page scope for the current request and seed it into the
 * `woocommerce/cart` interactivity API state.
 *
 * This is an experimental API and may change in future versions.
 *
 * @see plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/README.md
 *
 * @param string $consent_statement The consent statement acknowledging this is an experimental API.
 * @return string The page scope.
 * @throws InvalidArgumentException If consent statement doesn't match.
 */
function wc_interactivity_api_mint_page_scope( string $consent_statement ): string {
	return CartStore::mint_page_scope( $consent_statement );
}

/**
 * Push a scope onto the render-time scope stack, overriding the effective
 * scope while a container's inner blocks render.
 *
 * This is an experimental API and may change in future versions.
 *
 * @see plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/README.md
 *
 * @param string $consent_statement The consent statement acknowledging this is an experimental API.
 * @param string $scope             The scope to push.
 * @return void
 * @throws InvalidArgumentException If consent statement doesn't match.
 */
function wc_interactivity_api_push_scope( string $consent_statement, string $scope ): void {
	CartStore::push_scope( $consent_statement, $scope );
}

/**
 * Pop the innermost scope off the render-time scope stack.
 *
 * This is an experimental API and may change in future versions.
 *
 * @see plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/README.md
 *
 * @param string $consent_statement The consent statement acknowledging this is an experimental API.
 * @return void
 * @throws InvalidArgumentException If consent statement doesn't match.
 */
function wc_interactivity_api_pop_scope( string $consent_statement ): void {
	CartStore::pop_scope( $consent_statement );
}

/**
 * Get the scope in effect at the current point in rendering: the innermost
 * pushed scope, or the page scope when nothing is pushed.
 *
 * This is an experimental API and may change in future versions.
 *
 * @see plugins/woocommerce/client/blocks/assets/js/base/stores/woocommerce/README.md
 *
 * @param string $consent_statement The consent statement acknowledging this is an experimental API.
 * @return string The current scope.
 * @throws InvalidArgumentException If consent statement doesn't match.
 */
function wc_interactivity_api_get_current_scope( string $consent_statement ): string {
	return CartStore::get_current_scope( $consent_statement );
}
