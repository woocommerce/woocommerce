<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\SharedStores;

use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;
use Automattic\WooCommerce\Blocks\Package;

/**
 * Manages the registration of interactivity state that provides product data
 * to interactive blocks. The idea of this experimental API is to have common
 * store data that is not tied to one specific block.
 *
 * Initialization only happens on the first call to initialize_shared_config.
 * Intended to be used as a singleton.
 */
trait ProductsStore {
	/**
	 * The namespace for the store.
	 *
	 * @var string
	 */
	private static $store_namespace = 'woocommerce/products';

	/**
	 * Load a product into state.
	 *
	 * @param int $product_id The product ID.
	 */
	public function load_product( $product_id ) {
		$state = wp_interactivity_state( self::$store_namespace );

		if ( ! isset( $state['products'] ) ) {
			$state['products'] = array();
		}

		// Skip loading if product is already in state.
		if ( isset( $state['products'][ $product_id ] ) ) {
			return $state;
		}

		$product_state = Package::container()->get( Hydration::class )->get_rest_api_response_data( '/wc/store/v1/products/' . $product_id );

		$state['products'][ $product_id ] = $product_state['body'];
		return wp_interactivity_state( self::$store_namespace, $state );
	}

	/**
	 * Load all purchasable child products of a parent product into state.
	 *
	 * @param int $parent_id The parent product ID.
	 */
	public function load_purchasable_child_products( $parent_id ) {
		$state = wp_interactivity_state( self::$store_namespace );

		if ( ! isset( $state['products'] ) ) {
			$state['products'] = array();
		}

		$response = Package::container()->get( Hydration::class )->get_rest_api_response_data( '/wc/store/v1/products?parent=' . $parent_id );

		if ( empty( $response['body'] ) ) {
			return $state;
		}

		// Filter to only purchasable products.
		$purchasable_products = array_filter(
			$response['body'],
			fn( $product ) => $product['is_purchasable']
		);

		// Re-key array by product ID and merge into state.
		$keyed_products    = array_column( $purchasable_products, null, 'id' );
		$state['products'] = array_merge( $state['products'], $keyed_products );

		return wp_interactivity_state( self::$store_namespace, $state );
	}

	/**
	 * Load all variations of a variable product into state.
	 *
	 * @param int $parent_id The parent product ID.
	 */
	public function load_variations( $parent_id ) {
		$state = wp_interactivity_state( self::$store_namespace );

		if ( ! isset( $state['products'] ) ) {
			$state['products'] = array();
		}

		$response = Package::container()->get( Hydration::class )->get_rest_api_response_data( '/wc/store/v1/products?parent=' . $parent_id . '&type=variation' );

		if ( empty( $response['body'] ) ) {
			return $state;
		}

		// Re-key array by product ID and merge into state.
		$keyed_variations  = array_column( $response['body'], null, 'id' );
		$state['products'] = array_merge( $state['products'], $keyed_variations );

		return wp_interactivity_state( self::$store_namespace, $state );
	}
}
