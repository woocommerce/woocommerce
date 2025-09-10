<?php
/**
 * Shopify Platform Registration
 *
 * @package Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify;

defined( 'ABSPATH' ) || exit;

/**
 * ShopifyPlatform class.
 *
 * This class handles the registration of the Shopify platform with the
 * WooCommerce Migrator's platform registry system.
 */
class ShopifyPlatform {

	/**
	 * Initializes the Shopify platform registration.
	 *
	 * @internal
	 */
	final public static function init(): void {
		add_filter( 'woocommerce_migrator_platforms', array( self::class, 'register_platform' ) );
	}

	/**
	 * Registers the Shopify platform with the migrator system.
	 *
	 * @param array $platforms Array of registered platforms.
	 *
	 * @return array Updated array of platforms including Shopify.
	 */
	public static function register_platform( array $platforms ): array {
		$platforms['shopify'] = array(
			'name'        => 'Shopify',
			'description' => 'Import products and data from Shopify stores',
			'fetcher'     => ShopifyFetcher::class,
			'mapper'      => ShopifyMapper::class,
			'credentials' => array(
				'shop_url'     => 'Enter shop URL (e.g., mystore.myshopify.com):',
				'access_token' => 'Enter access token:',
			),
		);

		return $platforms;
	}
}
