<?php
/**
 * Webflow Platform Registration
 *
 * @package Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Webflow
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Webflow;

defined( 'ABSPATH' ) || exit;

/**
 * WebflowPlatform class.
 *
 * Registers the Webflow source platform with the WooCommerce Migrator's
 * platform registry. Users authenticate with a Webflow Site Access Token.
 *
 * @internal
 */
class WebflowPlatform {

	/**
	 * Initializes the Webflow platform registration.
	 *
	 * @internal
	 */
	final public static function init(): void {
		add_filter( 'woocommerce_migrator_platforms', array( self::class, 'register_platform' ) );
	}

	/**
	 * Registers the Webflow platform with the migrator system.
	 *
	 * @param array $platforms Array of registered platforms.
	 * @return array Updated array of platforms including Webflow.
	 */
	public static function register_platform( array $platforms ): array {
		$platforms['webflow'] = array(
			'name'        => 'Webflow',
			'description' => 'Import products and data from Webflow eCommerce sites',
			'fetcher'     => WebflowFetcher::class,
			'mapper'      => WebflowMapper::class,
			'credentials' => array(
				'site_id'      => 'Enter Webflow site ID:',
				'access_token' => 'Enter Webflow Site Access Token (must include ecommerce:read and cms:read scopes):',
			),
		);

		return $platforms;
	}
}
