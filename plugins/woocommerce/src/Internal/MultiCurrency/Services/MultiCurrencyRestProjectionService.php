<?php
/**
 * MultiCurrencyRestProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects multi-currency REST route metadata without registering routes.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyRestProjectionService {

	private const REST_NAMESPACE              = 'wc/v3';
	private const REST_BASE                   = 'payments/multi-currency';
	private const METHOD_GET                  = 'GET';
	private const METHOD_POST                 = 'POST';
	private const PERMISSION_PUBLIC           = 'public';
	private const PERMISSION_MANAGE_WC        = 'manage_woocommerce';
	private const PUBLIC_CONFIG_CACHE_CONTROL = 'private, max-age=300';

	/**
	 * Project the WooPayments multi-currency REST route manifest.
	 *
	 * @param bool $include_public_config Whether to include the public config route.
	 * @return array<string,mixed>
	 *
	 * @since 11.0.0
	 */
	public static function get_route_manifest( bool $include_public_config = false ): array {
		$routes = array();

		if ( $include_public_config ) {
			$routes['public_config'] = self::route(
				'/public/config',
				array( self::METHOD_GET ),
				self::PERMISSION_PUBLIC,
				'get_public_config'
			);
		}

		$routes['currencies']                = self::route(
			'/currencies',
			array( self::METHOD_GET ),
			self::PERMISSION_MANAGE_WC,
			'get_store_currencies'
		);
		$routes['update_enabled_currencies'] = self::route(
			'/update-enabled-currencies',
			array( self::METHOD_POST ),
			self::PERMISSION_MANAGE_WC,
			'update_enabled_currencies',
			array(
				'enabled' => array(
					'type'     => 'array',
					'required' => true,
				),
			)
		);
		$routes['single_currency_settings']  = self::route(
			'/currencies/(?P<currency_code>[A-Za-z]{3})',
			array( self::METHOD_GET ),
			self::PERMISSION_MANAGE_WC,
			'get_single_currency_settings',
			self::get_currency_code_args()
		);
		$routes['get_settings']              = self::route(
			'/get-settings',
			array( self::METHOD_GET ),
			self::PERMISSION_MANAGE_WC,
			'get_settings'
		);
		$routes['single_currency_update']    = self::route(
			'/currencies/(?P<currency_code>[A-Za-z]{3})',
			array( self::METHOD_POST ),
			self::PERMISSION_MANAGE_WC,
			'update_single_currency_settings',
			self::get_single_currency_update_args()
		);
		$routes['update_settings']           = self::route(
			'/update-settings',
			array( self::METHOD_POST ),
			self::PERMISSION_MANAGE_WC,
			'update_settings',
			self::get_update_settings_args()
		);

		return array(
			'namespace' => self::REST_NAMESPACE,
			'base'      => self::REST_BASE,
			'routes'    => $routes,
		);
	}

	/**
	 * Project public config response headers.
	 *
	 * @return array<string,string>
	 *
	 * @since 11.0.0
	 */
	public static function get_public_config_headers(): array {
		return array(
			'Cache-Control' => self::PUBLIC_CONFIG_CACHE_CONTROL,
		);
	}

	/**
	 * Build a route metadata entry.
	 *
	 * @param string                     $path_suffix Route suffix after the REST base.
	 * @param string[]                   $methods     HTTP methods.
	 * @param string                     $permission  Permission marker.
	 * @param string                     $callback    Callback marker.
	 * @param array<string,array<mixed>> $args        Argument schema.
	 * @return array<string,mixed>
	 */
	private static function route(
		string $path_suffix,
		array $methods,
		string $permission,
		string $callback,
		array $args = array()
	): array {
		return array(
			'path'       => '/' . self::REST_BASE . $path_suffix,
			'methods'    => $methods,
			'permission' => $permission,
			'callback'   => $callback,
			'args'       => $args,
		);
	}

	/**
	 * Get the currency code REST argument schema.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function get_currency_code_args(): array {
		return array(
			'currency_code' => array(
				'type'     => 'string',
				'format'   => 'text-field',
				'required' => true,
			),
		);
	}

	/**
	 * Get the single-currency update REST argument schema.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function get_single_currency_update_args(): array {
		return array(
			'currency_code'      => array(
				'type'     => 'string',
				'format'   => 'text-field',
				'required' => true,
			),
			'exchange_rate_type' => array(
				'type'     => 'string',
				'format'   => 'text-field',
				'required' => true,
			),
			'manual_rate'        => array(
				'type'     => 'number',
				'required' => false,
			),
			'price_rounding'     => array(
				'type'     => 'number',
				'required' => true,
			),
			'price_charm'        => array(
				'type'     => 'number',
				'required' => true,
			),
		);
	}

	/**
	 * Get the store settings update REST argument schema.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function get_update_settings_args(): array {
		return array(
			'wcpay_multi_currency_enable_auto_currency' => array(
				'type'     => 'string',
				'format'   => 'text-field',
				'required' => true,
			),
			'wcpay_multi_currency_enable_storefront_switcher' => array(
				'type'     => 'string',
				'format'   => 'text-field',
				'required' => true,
			),
			'wcpay_multi_currency_rendering_mode'       => array(
				'type'     => 'string',
				'required' => false,
				'enum'     => array( 'speed', 'cache' ),
			),
		);
	}
}
