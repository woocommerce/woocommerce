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
	 * The consent statement for using private APIs of this class.
	 *
	 * @var string
	 */
	private static $consent_statement = 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce';

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

		$product_state = Package::container()->get( Hydration::class )->get_rest_api_response_data( '/wc/store/v1/products/' . $product_id );

		$state['products'][ $product_id ] = $product_state;
		return wp_interactivity_state( self::$store_namespace, $state );
	}
}
