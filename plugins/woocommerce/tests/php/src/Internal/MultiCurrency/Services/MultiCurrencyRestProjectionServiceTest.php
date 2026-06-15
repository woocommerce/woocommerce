<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRestProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyRestProjectionService class.
 */
class MultiCurrencyRestProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project REST route manifest with public config when cache optimized.
	 */
	public function test_projects_route_manifest_with_public_config_when_cache_optimized(): void {
		$manifest = MultiCurrencyRestProjectionService::get_route_manifest( true );

		$this->assertSame( 'wc/v3', $manifest['namespace'] );
		$this->assertSame( 'payments/multi-currency', $manifest['base'] );
		$this->assertSame(
			array(
				'public_config',
				'currencies',
				'update_enabled_currencies',
				'single_currency_settings',
				'get_settings',
				'single_currency_update',
				'update_settings',
			),
			array_keys( $manifest['routes'] )
		);
		$this->assertSame(
			array(
				'path'       => '/payments/multi-currency/public/config',
				'methods'    => array( 'GET' ),
				'permission' => 'public',
				'callback'   => 'get_public_config',
				'args'       => array(),
			),
			$manifest['routes']['public_config']
		);
	}

	/**
	 * @testdox Should omit public config route when cache optimized mode is inactive.
	 */
	public function test_omits_public_config_route_when_cache_optimized_mode_is_inactive(): void {
		$manifest = MultiCurrencyRestProjectionService::get_route_manifest( false );

		$this->assertArrayNotHasKey( 'public_config', $manifest['routes'] );
		$this->assertSame(
			array(
				'currencies',
				'update_enabled_currencies',
				'single_currency_settings',
				'get_settings',
				'single_currency_update',
				'update_settings',
			),
			array_keys( $manifest['routes'] )
		);
	}

	/**
	 * @testdox Should project admin route methods permissions and arguments.
	 */
	public function test_projects_admin_route_methods_permissions_and_arguments(): void {
		$routes = MultiCurrencyRestProjectionService::get_route_manifest( false )['routes'];

		$this->assertSame(
			array(
				'path'       => '/payments/multi-currency/currencies',
				'methods'    => array( 'GET' ),
				'permission' => 'manage_woocommerce',
				'callback'   => 'get_store_currencies',
				'args'       => array(),
			),
			$routes['currencies']
		);
		$this->assertSame(
			array(
				'path'       => '/payments/multi-currency/update-enabled-currencies',
				'methods'    => array( 'POST' ),
				'permission' => 'manage_woocommerce',
				'callback'   => 'update_enabled_currencies',
				'args'       => array(
					'enabled' => array(
						'type'     => 'array',
						'required' => true,
					),
				),
			),
			$routes['update_enabled_currencies']
		);
		$this->assertSame( '/payments/multi-currency/currencies/(?P<currency_code>[A-Za-z]{3})', $routes['single_currency_settings']['path'] );
		$this->assertSame( array( 'GET' ), $routes['single_currency_settings']['methods'] );
		$this->assertSame( 'manage_woocommerce', $routes['single_currency_settings']['permission'] );
		$this->assertSame( 'get_single_currency_settings', $routes['single_currency_settings']['callback'] );
		$this->assertSame(
			array(
				'currency_code' => array(
					'type'     => 'string',
					'format'   => 'text-field',
					'required' => true,
				),
			),
			$routes['single_currency_settings']['args']
		);
		$this->assertSame( array( 'POST' ), $routes['single_currency_update']['methods'] );
		$this->assertSame(
			array(
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
			),
			$routes['single_currency_update']['args']
		);
	}

	/**
	 * @testdox Should project settings route schemas and public config headers.
	 */
	public function test_projects_settings_route_schemas_and_public_config_headers(): void {
		$routes = MultiCurrencyRestProjectionService::get_route_manifest( false )['routes'];

		$this->assertSame(
			array(
				'path'       => '/payments/multi-currency/get-settings',
				'methods'    => array( 'GET' ),
				'permission' => 'manage_woocommerce',
				'callback'   => 'get_settings',
				'args'       => array(),
			),
			$routes['get_settings']
		);
		$this->assertSame(
			array(
				'path'       => '/payments/multi-currency/update-settings',
				'methods'    => array( 'POST' ),
				'permission' => 'manage_woocommerce',
				'callback'   => 'update_settings',
				'args'       => array(
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
					'wcpay_multi_currency_rendering_mode' => array(
						'type'     => 'string',
						'required' => false,
						'enum'     => array( 'speed', 'cache' ),
					),
				),
			),
			$routes['update_settings']
		);
		$this->assertSame(
			array( 'Cache-Control' => 'private, max-age=300' ),
			MultiCurrencyRestProjectionService::get_public_config_headers()
		);
	}
}
