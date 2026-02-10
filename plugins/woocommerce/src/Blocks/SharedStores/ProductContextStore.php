<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\SharedStores;

use InvalidArgumentException;

/**
 * Manages the registration of interactivity state that provides product context
 * (product ID and variation ID) to interactive blocks. This replaces the
 * context-based approach used by product-data with direct state hydration.
 *
 * This is an experimental API and may change in future versions.
 *
 * @since 10.6.0
 */
class ProductContextStore {

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
	private static string $store_namespace = 'woocommerce/product-context';

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
	 * Load product context into interactivity state.
	 *
	 * Sets up the product context and ensures the product data is also loaded
	 * into the woocommerce/products store via ProductsStore. If a variation ID
	 * is provided, all variations for the product are loaded as well.
	 *
	 * @since 10.6.0
	 *
	 * @param string   $consent_statement The consent statement string.
	 * @param int      $product_id        The product ID.
	 * @param int|null $variation_id      The variation ID, or null if not a variation.
	 * @return void
	 * @throws InvalidArgumentException If consent statement doesn't match.
	 */
	public static function load_context( string $consent_statement, int $product_id, ?int $variation_id = null ): void {
		self::check_consent( $consent_statement );

		// Ensure the product is loaded into the products store.
		ProductsStore::load_product( $consent_statement, $product_id );

		// If a variation is specified, load its parent's variations.
		if ( null !== $variation_id ) {
			ProductsStore::load_variations( $consent_statement, $product_id );
		}

		wp_interactivity_state(
			self::$store_namespace,
			array(
				'productId'   => $product_id,
				'variationId' => $variation_id,
			)
		);
	}
}
