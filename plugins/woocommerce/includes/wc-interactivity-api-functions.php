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

use Automattic\WooCommerce\Blocks\SharedStores\ProductsStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load a product into the interactivity API state.
 *
 * This is an experimental API and may change in future versions.
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
 * @param string $consent_statement The consent statement acknowledging this is an experimental API.
 * @param int    $parent_id         The parent product ID.
 * @return array The variations keyed by ID.
 * @throws InvalidArgumentException If consent statement doesn't match.
 */
function wc_interactivity_api_load_variations( string $consent_statement, int $parent_id ): array {
	return ProductsStore::load_variations( $consent_statement, $parent_id );
}
