<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRestController;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyFrontendProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use WC_Unit_Test_Case;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Tests for the MultiCurrencyRestController class.
 */
class MultiCurrencyRestControllerTest extends WC_Unit_Test_Case {

	/**
	 * Controllers created during a test.
	 *
	 * @var MultiCurrencyRestController[]
	 */
	private array $controllers = array();

	/**
	 * Options touched by the controller.
	 *
	 * @var string[]
	 */
	private array $options = array(
		'wcpay_multi_currency_enabled_currencies',
		'wcpay_multi_currency_exchange_rate_eur',
		'wcpay_multi_currency_manual_rate_eur',
		'wcpay_multi_currency_price_rounding_eur',
		'wcpay_multi_currency_price_charm_eur',
		'wcpay_multi_currency_exchange_rate_gbp',
		'wcpay_multi_currency_manual_rate_gbp',
		'wcpay_multi_currency_price_rounding_gbp',
		'wcpay_multi_currency_price_charm_gbp',
		'wcpay_multi_currency_enable_auto_currency',
		'wcpay_multi_currency_enable_storefront_switcher',
		'wcpay_multi_currency_rendering_mode',
	);

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->controllers as $controller ) {
			remove_action( 'rest_api_init', array( $controller, 'handle_rest_api_init' ) );
		}

		foreach ( $this->options as $option ) {
			delete_option( $option );
		}

		wp_set_current_user( 0 );

		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tearDown();
	}

	/**
	 * @testdox Should not register REST hooks when plugin owns runtime.
	 */
	public function test_does_not_register_rest_hooks_when_plugin_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN );

		$sut->register();

		$this->assertFalse( has_action( 'rest_api_init', array( $sut, 'handle_rest_api_init' ) ) );
	}

	/**
	 * @testdox Should register REST hooks and routes when core owns runtime.
	 */
	public function test_registers_rest_hooks_and_routes_when_core_owns_runtime(): void {
		$sut = $this->create_controller();

		$sut->register();
		$sut->register();
		/**
		 * Fires REST API route registration for the controller under test.
		 *
		 * @since 11.0.0
		 */
		do_action( 'rest_api_init' );

		$routes = rest_get_server()->get_routes();

		$this->assertSame( 10, has_action( 'rest_api_init', array( $sut, 'handle_rest_api_init' ) ) );
		$this->assertArrayHasKey( '/wc/v3/payments/multi-currency/public/config', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/multi-currency/currencies', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/multi-currency/update-enabled-currencies', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/multi-currency/currencies/(?P<currency_code>[A-Za-z]{3})', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/multi-currency/get-settings', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/multi-currency/update-settings', $routes );
	}

	/**
	 * @testdox Should omit public config route when cache mode is inactive.
	 */
	public function test_omits_public_config_route_when_cache_mode_is_inactive(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, null, false );

		$sut->register();
		/**
		 * Fires REST API route registration for the controller under test.
		 *
		 * @since 11.0.0
		 */
		do_action( 'rest_api_init' );

		$routes = rest_get_server()->get_routes();

		$this->assertArrayNotHasKey( '/wc/v3/payments/multi-currency/public/config', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/multi-currency/currencies', $routes );
	}

	/**
	 * @testdox Should require manage WooCommerce capability.
	 */
	public function test_check_permission_requires_manage_woocommerce(): void {
		$sut = $this->create_controller();

		wp_set_current_user( 0 );
		$this->assertFalse( $sut->check_permission() );

		$user_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$this->assertTrue( $sut->check_permission() );
	}

	/**
	 * @testdox Should return store currencies from state snapshot.
	 */
	public function test_returns_store_currencies_from_state_snapshot(): void {
		$sut = $this->create_controller();

		$response = $sut->get_store_currencies();
		$data     = $response->get_data();

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( array( 'USD', 'EUR', 'GBP' ), array_keys( $data['available'] ) );
		$this->assertSame( array( 'USD', 'EUR' ), array_keys( $data['enabled'] ) );
		$this->assertSame( 'USD', $data['default']->get_code() );
	}

	/**
	 * @testdox Should update enabled currencies and remove removed currency settings.
	 */
	public function test_updates_enabled_currencies_and_removes_removed_currency_settings(): void {
		update_option( 'wcpay_multi_currency_manual_rate_gbp', '0.72' );
		update_option( 'wcpay_multi_currency_exchange_rate_gbp', 'manual' );
		update_option( 'wcpay_multi_currency_price_rounding_gbp', '1.00' );
		update_option( 'wcpay_multi_currency_price_charm_gbp', '0.99' );
		$sut     = $this->create_controller(
			MultiCurrencyRuntimeArbiter::OWNER_CORE,
			$this->create_state_builder( array( 'USD', 'EUR', 'GBP' ), array( 'USD', 'EUR', 'GBP' ) )
		);
		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/multi-currency/update-enabled-currencies' );
		$request->set_param( 'enabled', array( 'USD', 'EUR' ) );

		$response = $sut->update_enabled_currencies( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( array( 'USD', 'EUR' ), get_option( 'wcpay_multi_currency_enabled_currencies' ) );
		$this->assertFalse( get_option( 'wcpay_multi_currency_manual_rate_gbp' ) );
		$this->assertFalse( get_option( 'wcpay_multi_currency_exchange_rate_gbp' ) );
		$this->assertFalse( get_option( 'wcpay_multi_currency_price_rounding_gbp' ) );
		$this->assertFalse( get_option( 'wcpay_multi_currency_price_charm_gbp' ) );
	}

	/**
	 * @testdox Should reject invalid enabled currency.
	 */
	public function test_rejects_invalid_enabled_currency(): void {
		$sut     = $this->create_controller();
		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/multi-currency/update-enabled-currencies' );
		$request->set_param( 'enabled', array( 'USD', 'XYZ' ) );

		$response = $sut->update_enabled_currencies( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertStringContainsString( 'XYZ', $response->get_error_message() );
	}

	/**
	 * @testdox Should read and update single currency settings.
	 */
	public function test_reads_and_updates_single_currency_settings(): void {
		$sut     = $this->create_controller();
		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/multi-currency/currencies/EUR' );
		$request->set_param( 'currency_code', 'EUR' );
		$request->set_param( 'exchange_rate_type', 'manual' );
		$request->set_param( 'manual_rate', 1.23 );
		$request->set_param( 'price_rounding', 1.0 );
		$request->set_param( 'price_charm', 0.99 );

		$response = $sut->update_single_currency_settings( $request );
		$data     = $response->get_data();

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 'manual', get_option( 'wcpay_multi_currency_exchange_rate_eur' ) );
		$this->assertSame( 1.23, get_option( 'wcpay_multi_currency_manual_rate_eur' ) );
		$this->assertSame( 1.0, get_option( 'wcpay_multi_currency_price_rounding_eur' ) );
		$this->assertSame( 0.99, get_option( 'wcpay_multi_currency_price_charm_eur' ) );
		$this->assertSame( 'manual', $data['exchange_rate_type'] );
		$this->assertSame( 1.23, $data['manual_rate'] );
	}

	/**
	 * @testdox Should reject invalid manual rate for single currency settings.
	 */
	public function test_rejects_invalid_manual_rate_for_single_currency_settings(): void {
		$sut     = $this->create_controller();
		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/multi-currency/currencies/EUR' );
		$request->set_param( 'currency_code', 'EUR' );
		$request->set_param( 'exchange_rate_type', 'manual' );
		$request->set_param( 'manual_rate', 0 );
		$request->set_param( 'price_rounding', 1.0 );
		$request->set_param( 'price_charm', 0.99 );

		$response = $sut->update_single_currency_settings( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertStringContainsString( 'Invalid manual currency rate', $response->get_error_message() );
	}

	/**
	 * @testdox Should read and update store settings.
	 */
	public function test_reads_and_updates_store_settings(): void {
		update_option( 'wcpay_multi_currency_rendering_mode', 'speed' );
		$sut     = $this->create_controller();
		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/multi-currency/update-settings' );
		$request->set_param( 'wcpay_multi_currency_enable_auto_currency', 'yes' );
		$request->set_param( 'wcpay_multi_currency_enable_storefront_switcher', 'no' );
		$request->set_param( 'wcpay_multi_currency_rendering_mode', 'not-valid' );

		$response = $sut->update_settings( $request );
		$data     = $response->get_data();

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 'yes', get_option( 'wcpay_multi_currency_enable_auto_currency' ) );
		$this->assertSame( 'no', get_option( 'wcpay_multi_currency_enable_storefront_switcher' ) );
		$this->assertSame( 'speed', get_option( 'wcpay_multi_currency_rendering_mode' ) );
		$this->assertSame( 'yes', $data['wcpay_multi_currency_enable_auto_currency'] );
		$this->assertSame( 'no', $data['wcpay_multi_currency_enable_storefront_switcher'] );
		$this->assertSame( 'speed', $data['wcpay_multi_currency_rendering_mode'] );
	}

	/**
	 * @testdox Should return public config with cache control header.
	 */
	public function test_returns_public_config_with_cache_control_header(): void {
		$sut = $this->create_controller();

		$response = $sut->get_public_config();
		$headers  = $response->get_headers();
		$data     = $response->get_data();

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 'private, max-age=300', $headers['Cache-Control'] );
		$this->assertSame( 'USD', $data['default_currency'] );
		$this->assertSame( 'EUR', $data['selected_currency'] );
	}

	/**
	 * Create a REST controller.
	 *
	 * @param string                         $owner                 Runtime owner.
	 * @param MultiCurrencyStateBuilder|null $state_builder         State builder.
	 * @param bool                           $cache_optimized_mode  Whether cache mode is active.
	 * @return MultiCurrencyRestController
	 */
	private function create_controller(
		string $owner = MultiCurrencyRuntimeArbiter::OWNER_CORE,
		?MultiCurrencyStateBuilder $state_builder = null,
		bool $cache_optimized_mode = true
	): MultiCurrencyRestController {
		$controller = new MultiCurrencyRestController();
		$controller->init( $this->create_arbiter( $owner ) );
		$controller->set_state_builder( $state_builder ?? $this->create_state_builder() );
		$controller->set_frontend_projection_service( $this->create_frontend_projection_service( $cache_optimized_mode ) );

		$this->controllers[] = $controller;

		return $controller;
	}

	/**
	 * Create a static runtime arbiter.
	 *
	 * @param string $owner Runtime owner.
	 * @return MultiCurrencyRuntimeArbiter
	 */
	private function create_arbiter( string $owner ): MultiCurrencyRuntimeArbiter {
		return new class( $owner ) extends MultiCurrencyRuntimeArbiter {
			/**
			 * Runtime owner.
			 *
			 * @var string
			 */
			private string $owner;

			/**
			 * Constructor.
			 *
			 * @param string $owner Runtime owner.
			 */
			public function __construct( string $owner ) {
				$this->owner = $owner;
			}

			/**
			 * Tell whether core should register.
			 *
			 * @return bool
			 */
			public function should_core_register(): bool {
				return MultiCurrencyRuntimeArbiter::OWNER_CORE === $this->owner;
			}
		};
	}

	/**
	 * Create a state builder test double.
	 *
	 * @param string[] $available_codes Available currency codes.
	 * @param string[] $enabled_codes   Enabled currency codes.
	 * @return MultiCurrencyStateBuilder
	 */
	private function create_state_builder(
		array $available_codes = array( 'USD', 'EUR', 'GBP' ),
		array $enabled_codes = array( 'USD', 'EUR' )
	): MultiCurrencyStateBuilder {
		$localization = $this->create_localization_service();
		$available    = array();

		foreach ( $available_codes as $currency_code ) {
			$available[ $currency_code ] = new MultiCurrencyCurrency( $localization, $currency_code, $this->get_rate_for_currency( $currency_code ), 'USD' === $currency_code );
		}

		$enabled = array();
		foreach ( $enabled_codes as $currency_code ) {
			$enabled[ $currency_code ] = $available[ $currency_code ];
		}

		$state = new MultiCurrencyState( $available, $enabled, $available['USD'], $enabled['EUR'] ?? $available['USD'] );

		return new class( $state ) extends MultiCurrencyStateBuilder {
			/**
			 * State snapshot.
			 *
			 * @var MultiCurrencyState
			 */
			private MultiCurrencyState $state;

			/**
			 * Constructor.
			 *
			 * @param MultiCurrencyState $state State snapshot.
			 */
			public function __construct( MultiCurrencyState $state ) {
				$this->state = $state;
			}

			/**
			 * Build the state.
			 *
			 * @return MultiCurrencyState
			 */
			public function build(): MultiCurrencyState {
				return $this->state;
			}
		};
	}

	/**
	 * Create a frontend projection service test double.
	 *
	 * @param bool $cache_optimized_mode Whether cache mode is active.
	 * @return MultiCurrencyFrontendProjectionService
	 */
	private function create_frontend_projection_service( bool $cache_optimized_mode ): MultiCurrencyFrontendProjectionService {
		return new class( $cache_optimized_mode ) extends MultiCurrencyFrontendProjectionService {
			/**
			 * Whether cache mode is active.
			 *
			 * @var bool
			 */
			private bool $cache_optimized_mode;

			/**
			 * Constructor.
			 *
			 * @param bool $cache_optimized_mode Whether cache mode is active.
			 */
			public function __construct( bool $cache_optimized_mode ) {
				$this->cache_optimized_mode = $cache_optimized_mode;
			}

			/**
			 * Tell whether cache mode is active.
			 *
			 * @return bool
			 */
			public function is_cache_optimized_mode(): bool {
				return $this->cache_optimized_mode;
			}

			/**
			 * Get single-currency settings from preserved option keys.
			 *
			 * @param string $currency_code Currency code.
			 * @return array<string,mixed>
			 */
			public function get_single_currency_settings( string $currency_code ): array {
				$currency_id = strtolower( $currency_code );

				return array(
					'exchange_rate_type' => get_option( 'wcpay_multi_currency_exchange_rate_' . $currency_id, 'automatic' ),
					'manual_rate'        => get_option( 'wcpay_multi_currency_manual_rate_' . $currency_id, null ),
					'price_rounding'     => get_option( 'wcpay_multi_currency_price_rounding_' . $currency_id, null ),
					'price_charm'        => get_option( 'wcpay_multi_currency_price_charm_' . $currency_id, null ),
				);
			}

			/**
			 * Get store settings from preserved option keys.
			 *
			 * @return array<string,mixed>
			 */
			public function get_settings(): array {
				return array(
					'wcpay_multi_currency_enable_auto_currency' => get_option( 'wcpay_multi_currency_enable_auto_currency', 'no' ),
					'wcpay_multi_currency_enable_storefront_switcher' => get_option( 'wcpay_multi_currency_enable_storefront_switcher', 'no' ),
					'wcpay_multi_currency_rendering_mode' => get_option( 'wcpay_multi_currency_rendering_mode', 'speed' ),
				);
			}

			/**
			 * Get deterministic public config.
			 *
			 * @return array<string,mixed>
			 */
			public function get_public_config(): array {
				return array(
					'default_currency'    => 'USD',
					'selected_currency'   => 'EUR',
					'charm_only_products' => true,
					'currencies'          => array(
						'USD' => array( 'code' => 'USD' ),
						'EUR' => array( 'code' => 'EUR' ),
					),
				);
			}
		};
	}

	/**
	 * Create a localization test double.
	 *
	 * @return MultiCurrencyLocalizationInterface
	 */
	private function create_localization_service(): MultiCurrencyLocalizationInterface {
		return new class() implements MultiCurrencyLocalizationInterface {
			/**
			 * Get a currency format.
			 *
			 * @param string $currency_code Currency code.
			 * @return array<string,mixed>
			 */
			public function get_currency_format( $currency_code ): array {
				unset( $currency_code );

				return array(
					'currency_pos' => 'left',
					'thousand_sep' => ',',
					'decimal_sep'  => '.',
					'num_decimals' => 2,
				);
			}

			/**
			 * Get locale data for a country.
			 *
			 * @param string $country Country code.
			 * @return array<string,mixed>
			 */
			public function get_country_locale_data( $country ): array {
				unset( $country );

				return array();
			}
		};
	}

	/**
	 * Get a deterministic rate for a currency code.
	 *
	 * @param string $currency_code Currency code.
	 * @return float
	 */
	private function get_rate_for_currency( string $currency_code ): float {
		return array(
			'USD' => 1.0,
			'EUR' => 0.91,
			'GBP' => 0.78,
		)[ $currency_code ];
	}
}
