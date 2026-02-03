<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\SharedStores;

use InvalidArgumentException;

/**
 * Manages the registration of interactivity state that provides product context
 * to interactive blocks. This tracks which product/variation is currently being
 * viewed or interacted with.
 *
 * This is an experimental API and may change in future versions.
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
	 * Whether context has been loaded.
	 *
	 * @var bool
	 */
	private static bool $context_loaded = false;

	/**
	 * The current product context state.
	 *
	 * @var array
	 */
	private static array $context_state = array(
		'productId'   => 0,
		'variationId' => null,
	);

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
	 * Register the interactivity state if context has been loaded.
	 *
	 * @return void
	 */
	private static function register_state(): void {
		if ( ! self::$context_loaded ) {
			return;
		}

		wp_interactivity_state( self::$store_namespace, self::$context_state );
	}

	/**
	 * Load product context into state.
	 *
	 * This sets up the product context and ensures the product data is also
	 * loaded via ProductsStore.
	 *
	 * Note: selectedAttributes is NOT loaded here because it's form-specific
	 * state managed by the add-to-cart-with-options context.
	 *
	 * @param string   $consent_statement The consent statement string.
	 * @param int      $product_id        The product ID.
	 * @param int|null $variation_id      The variation ID (optional).
	 * @return void
	 * @throws InvalidArgumentException If consent statement doesn't match.
	 */
	public static function load_context(
		string $consent_statement,
		int $product_id,
		?int $variation_id = null
	): void {
		self::check_consent( $consent_statement );

		// Ensure the product is loaded into the products store.
		ProductsStore::load_product( $consent_statement, $product_id );

		// If a variation is specified, load its parent's variations.
		if ( null !== $variation_id ) {
			ProductsStore::load_variations( $consent_statement, $product_id );
		}

		// Update the context state.
		self::$context_state['productId']   = $product_id;
		self::$context_state['variationId'] = $variation_id;

		self::$context_loaded = true;
		self::register_state();
	}

	/**
	 * Get the context data array for use in data-wp-context attribute.
	 *
	 * This provides the per-block context data that enables multiple
	 * Add to Cart blocks on the same page to operate independently.
	 *
	 * Note: selectedAttributes is intentionally NOT included here because it's
	 * managed by the add-to-cart-with-options form context. The product-context
	 * store only tracks which product/variation is being viewed, not form state.
	 *
	 * @param string   $consent_statement The consent statement string.
	 * @param int      $product_id        The product ID.
	 * @param int|null $variation_id      The variation ID if pre-selected.
	 * @return array The context data for data-wp-context.
	 * @throws InvalidArgumentException If consent statement doesn't match.
	 */
	public static function get_context_data(
		string $consent_statement,
		int $product_id,
		?int $variation_id = null
	): array {
		self::check_consent( $consent_statement );

		return array(
			'productId'   => $product_id,
			'variationId' => $variation_id,
		);
	}

}
