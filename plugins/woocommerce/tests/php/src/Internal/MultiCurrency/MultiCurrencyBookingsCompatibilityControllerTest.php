<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyBookingsCompatibilityController;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyFrontendProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyGeolocationService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceCalculator;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyProjectionServiceFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyBookingsCompatibilityController class.
 */
class MultiCurrencyBookingsCompatibilityControllerTest extends WC_Unit_Test_Case {

	/**
	 * Hooks touched by the Bookings compatibility controller.
	 *
	 * @var string[]
	 */
	private array $hooks = array(
		'plugins_loaded',
		'woocommerce_bookings_calculated_booking_cost',
		'woocommerce_product_get_block_cost',
		'woocommerce_product_get_cost',
		'woocommerce_product_get_display_cost',
		'woocommerce_product_booking_person_type_get_block_cost',
		'woocommerce_product_booking_person_type_get_cost',
		'woocommerce_product_get_resource_base_costs',
		'woocommerce_product_get_resource_block_costs',
		'wcpay_multi_currency_should_convert_product_price',
		'woocommerce_bookings_process_cost_rules_cost',
		'woocommerce_bookings_process_cost_rules_base_cost',
		'wp_ajax_wc_bookings_calculate_costs',
		'wp_ajax_nopriv_wc_bookings_calculate_costs',
		'wc_price_args',
	);

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->hooks as $hook ) {
			remove_all_filters( $hook );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Should register Bookings compatibility hooks for core frontend runtime.
	 */
	public function test_registers_bookings_compatibility_hooks_for_core_frontend_runtime(): void {
		$sut = $this->create_controller();

		$sut->register();
		$sut->register();

		$this->assertSame( 50, has_filter( 'woocommerce_bookings_calculated_booking_cost', array( $sut, 'adjust_amount_for_calculated_booking_cost' ) ) );
		$this->assertSame( 50, has_filter( 'woocommerce_product_get_block_cost', array( $sut, 'get_price' ) ) );
		$this->assertSame( 50, has_filter( 'woocommerce_product_get_resource_base_costs', array( $sut, 'get_resource_prices' ) ) );
		$this->assertSame( 50, has_filter( 'wcpay_multi_currency_should_convert_product_price', array( $sut, 'should_convert_product_price' ) ) );
		$this->assertSame( 50, has_filter( 'woocommerce_bookings_process_cost_rules_base_cost', array( $sut, 'get_price' ) ) );
		$this->assertSame( 9, has_action( 'wp_ajax_wc_bookings_calculate_costs', array( $sut, 'add_wc_price_args_filter_for_ajax' ) ) );
		$this->assertSame( 9, has_action( 'wp_ajax_nopriv_wc_bookings_calculate_costs', array( $sut, 'add_wc_price_args_filter_for_ajax' ) ) );
	}

	/**
	 * @testdox Should not register Bookings hooks when runtime or request guards block.
	 */
	public function test_does_not_register_bookings_hooks_when_guards_block(): void {
		$plugin_owned     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN );
		$missing_bookings = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false );
		$admin            = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, true, true, false );

		$plugin_owned->register();
		$missing_bookings->register();
		$admin->register();

		$this->assert_bookings_hooks_not_registered( $plugin_owned );
		$this->assert_bookings_hooks_not_registered( $missing_bookings );
		$this->assert_bookings_hooks_not_registered( $admin );
	}

	/**
	 * @testdox Should defer Bookings hook registration until plugins load.
	 */
	public function test_defers_bookings_registration_until_plugins_load(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false, false, false, false );

		$sut->register();

		$this->assertFalse( has_filter( 'woocommerce_bookings_calculated_booking_cost', array( $sut, 'adjust_amount_for_calculated_booking_cost' ) ) );
		$this->assertSame( 20, has_action( 'plugins_loaded', array( $sut, 'register_bookings_filters' ) ) );

		$sut->set_bookings_available( true );
		$sut->register_bookings_filters();

		$this->assertSame( 50, has_filter( 'woocommerce_bookings_calculated_booking_cost', array( $sut, 'adjust_amount_for_calculated_booking_cost' ) ) );
	}

	/**
	 * @testdox Should convert calculated booking costs outside cart add-to-cart.
	 */
	public function test_converts_calculated_booking_costs_outside_cart_add_to_cart(): void {
		$sut = $this->create_controller();

		$this->assertSame( 8.4, $sut->adjust_amount_for_calculated_booking_cost( '10.00' ) );

		$sut->set_backtrace_calls( array( 'WC_Cart->add_to_cart' ) );

		$this->assertSame( '10.00', $sut->adjust_amount_for_calculated_booking_cost( '10.00' ) );
	}

	/**
	 * @testdox Should convert Bookings price hooks with context-specific price types.
	 */
	public function test_converts_bookings_price_hooks_with_context_specific_price_types(): void {
		$sut = $this->create_controller();

		$this->assertSame( 8.2, $sut->get_price( '10.00' ) );

		$sut->set_backtrace_calls( array( 'WC_Product_Booking->get_price_html' ) );

		$this->assertSame( 8.4, $sut->get_price( '10.00' ) );
		$this->assertSame( 0, $sut->get_price( 0 ) );

		$sut->set_backtrace_calls(
			array(
				'WC_Cart->add_to_cart',
				'WC_Bookings_Cost_Calculation::calculate_booking_cost',
			)
		);

		$this->assertSame( '10.00', $sut->get_price( '10.00' ) );
	}

	/**
	 * @testdox Should convert Bookings resource price arrays.
	 */
	public function test_converts_bookings_resource_price_arrays(): void {
		$sut = $this->create_controller();

		$this->assertSame(
			array(
				'room' => 8.2,
				'desk' => 16.4,
			),
			$sut->get_resource_prices(
				array(
					'room' => '10.00',
					'desk' => 20,
				)
			)
		);
		$this->assertSame( 'not-array', $sut->get_resource_prices( 'not-array' ) );
	}

	/**
	 * @testdox Should suppress default product conversion for Bookings price HTML.
	 */
	public function test_suppresses_default_product_conversion_for_bookings_price_html(): void {
		$sut = $this->create_controller();

		$this->assertTrue( $sut->should_convert_product_price( true, $this->create_product_type( 'booking' ) ) );

		$sut->set_backtrace_calls( array( 'WC_Product_Booking->get_price_html' ) );

		$this->assertFalse( $sut->should_convert_product_price( true, $this->create_product_type( 'booking' ) ) );
		$this->assertTrue( $sut->should_convert_product_price( true, $this->create_product_type( 'simple' ) ) );
		$this->assertFalse( $sut->should_convert_product_price( false, $this->create_product_type( 'booking' ) ) );
	}

	/**
	 * @testdox Should add selected-currency price args for Bookings Ajax calculations.
	 */
	public function test_adds_selected_currency_price_args_for_bookings_ajax_calculations(): void {
		$sut = $this->create_controller();

		$sut->add_wc_price_args_filter_for_ajax();

		$this->assertSame( 100, has_filter( 'wc_price_args', array( $sut, 'filter_wc_price_args' ) ) );
		$this->assertSame(
			array(
				'currency'           => 'GBP',
				'decimal_separator'  => ',',
				'thousand_separator' => '.',
				'decimals'           => 3,
				'price_format'       => '%2$s&nbsp;%1$s',
			),
			$sut->filter_wc_price_args(
				array(
					'currency'           => 'USD',
					'decimal_separator'  => '.',
					'thousand_separator' => ',',
					'decimals'           => 2,
					'price_format'       => '%1$s%2$s',
				)
			)
		);
	}

	/**
	 * @testdox Should bootstrap Bookings compatibility controller.
	 */
	public function test_bootstrap_registers_bookings_compatibility_controller(): void {
		$controller = wc_get_container()->get( MultiCurrencyBookingsCompatibilityController::class );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local source assertion for bootstrap registration.
		$bootstrap_source = file_get_contents( WC()->plugin_path() . '/includes/class-woocommerce.php' );

		$this->assertInstanceOf( MultiCurrencyBookingsCompatibilityController::class, $controller );
		$this->assertIsString( $bootstrap_source );
		$this->assertStringContainsString( 'Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyBookingsCompatibilityController::class )->register()', $bootstrap_source );
	}

	/**
	 * Assert Bookings hooks are not registered for a controller.
	 *
	 * @param MultiCurrencyBookingsCompatibilityController $sut The controller.
	 */
	private function assert_bookings_hooks_not_registered( MultiCurrencyBookingsCompatibilityController $sut ): void {
		$this->assertFalse( has_filter( 'woocommerce_bookings_calculated_booking_cost', array( $sut, 'adjust_amount_for_calculated_booking_cost' ) ) );
		$this->assertFalse( has_filter( 'wcpay_multi_currency_should_convert_product_price', array( $sut, 'should_convert_product_price' ) ) );
		$this->assertFalse( has_action( 'wp_ajax_wc_bookings_calculate_costs', array( $sut, 'add_wc_price_args_filter_for_ajax' ) ) );
	}

	/**
	 * Create a Bookings compatibility controller with deterministic runtime context.
	 *
	 * @param string $owner              Runtime owner.
	 * @param bool   $bookings_available Whether Bookings runtime is available.
	 * @param bool   $is_admin           Whether this is an admin request.
	 * @param bool   $is_ajax            Whether this is an Ajax request.
	 * @param bool   $plugins_loaded     Whether plugins have loaded.
	 * @return MultiCurrencyBookingsCompatibilityController
	 */
	private function create_controller(
		string $owner = MultiCurrencyRuntimeArbiter::OWNER_CORE,
		bool $bookings_available = true,
		bool $is_admin = false,
		bool $is_ajax = false,
		bool $plugins_loaded = true
	): MultiCurrencyBookingsCompatibilityController {
		$controller = new class( $bookings_available, $is_admin, $is_ajax, $plugins_loaded ) extends MultiCurrencyBookingsCompatibilityController {
			/**
			 * Whether Bookings runtime is available.
			 *
			 * @var bool
			 */
			private bool $bookings_available;

			/**
			 * Whether this is an admin request.
			 *
			 * @var bool
			 */
			private bool $is_admin;

			/**
			 * Whether this is an Ajax request.
			 *
			 * @var bool
			 */
			private bool $is_ajax;

			/**
			 * Whether plugins have loaded.
			 *
			 * @var bool
			 */
			private bool $plugins_loaded;

			/**
			 * Backtrace summary calls.
			 *
			 * @var string[]
			 */
			private array $backtrace_calls = array();

			/**
			 * Constructor.
			 *
			 * @param bool $bookings_available Whether Bookings runtime is available.
			 * @param bool $is_admin           Whether this is an admin request.
			 * @param bool $is_ajax            Whether this is an Ajax request.
			 * @param bool $plugins_loaded     Whether plugins have loaded.
			 */
			public function __construct( bool $bookings_available, bool $is_admin, bool $is_ajax, bool $plugins_loaded ) {
				$this->bookings_available = $bookings_available;
				$this->is_admin           = $is_admin;
				$this->is_ajax            = $is_ajax;
				$this->plugins_loaded     = $plugins_loaded;
			}

			/**
			 * Set whether Bookings runtime is available.
			 *
			 * @param bool $bookings_available Whether Bookings runtime is available.
			 */
			public function set_bookings_available( bool $bookings_available ): void {
				$this->bookings_available = $bookings_available;
			}

			/**
			 * Set backtrace summary calls.
			 *
			 * @param string[] $backtrace_calls Backtrace summary calls.
			 */
			public function set_backtrace_calls( array $backtrace_calls ): void {
				$this->backtrace_calls = $backtrace_calls;
			}

			/**
			 * Check if Bookings runtime is available.
			 *
			 * @return bool
			 */
			protected function is_bookings_runtime_available(): bool {
				return $this->bookings_available;
			}

			/**
			 * Check if this is an admin request.
			 *
			 * @return bool
			 */
			protected function is_admin_request(): bool {
				return $this->is_admin;
			}

			/**
			 * Check if this is an Ajax request.
			 *
			 * @return bool
			 */
			protected function is_ajax_request(): bool {
				return $this->is_ajax;
			}

			/**
			 * Check if plugins have loaded.
			 *
			 * @return bool
			 */
			protected function have_plugins_loaded(): bool {
				return $this->plugins_loaded;
			}

			/**
			 * Check if any expected call appears in the deterministic backtrace.
			 *
			 * @param string[] $calls Expected calls.
			 * @return bool
			 */
			protected function is_call_in_backtrace( array $calls ): bool {
				return array() !== array_intersect( $calls, $this->backtrace_calls );
			}
		};

		$controller->init(
			$this->create_arbiter( $owner ),
			wc_get_container()->get( MultiCurrencyProjectionServiceFactory::class )
		);
		$controller->set_price_projection_service( $this->create_price_projection_service() );
		$controller->set_frontend_projection_service( $this->create_frontend_projection_service() );

		return $controller;
	}

	/**
	 * Create a runtime arbiter test double.
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
			 * Get the multi-currency runtime owner.
			 *
			 * @return string
			 */
			public function get_runtime_owner(): string {
				return $this->owner;
			}
		};
	}

	/**
	 * Create a price projection service.
	 *
	 * @return MultiCurrencyPriceProjectionService
	 */
	private function create_price_projection_service(): MultiCurrencyPriceProjectionService {
		$localization = $this->create_localization();

		return new MultiCurrencyPriceProjectionService(
			$this->create_state_builder( $this->create_state() ),
			new MultiCurrencyPriceCalculator( $localization )
		);
	}

	/**
	 * Create a frontend projection service.
	 *
	 * @return MultiCurrencyFrontendProjectionService
	 */
	private function create_frontend_projection_service(): MultiCurrencyFrontendProjectionService {
		$localization = $this->create_localization();

		return new MultiCurrencyFrontendProjectionService(
			$this->create_state_builder( $this->create_state() ),
			$localization,
			new MultiCurrencyGeolocationService( $localization )
		);
	}

	/**
	 * Create a state builder test double.
	 *
	 * @param MultiCurrencyState $state Multi-currency state.
	 * @return MultiCurrencyStateBuilder
	 */
	private function create_state_builder( MultiCurrencyState $state ): MultiCurrencyStateBuilder {
		return new class( $state ) extends MultiCurrencyStateBuilder {
			/**
			 * Multi-currency state.
			 *
			 * @var MultiCurrencyState
			 */
			private MultiCurrencyState $state;

			/**
			 * Constructor.
			 *
			 * @param MultiCurrencyState $state Multi-currency state.
			 */
			public function __construct( MultiCurrencyState $state ) {
				$this->state = $state;
			}

			/**
			 * Build a multi-currency state snapshot.
			 *
			 * @return MultiCurrencyState
			 */
			public function build(): MultiCurrencyState {
				return $this->state;
			}
		};
	}

	/**
	 * Create multi-currency state.
	 *
	 * @return MultiCurrencyState
	 */
	private function create_state(): MultiCurrencyState {
		$usd = $this->create_currency( 'USD', 1.0, true );
		$gbp = $this->create_currency( 'GBP', 0.82, false, '0.50', -0.10 );

		$enabled = array(
			'USD' => $usd,
			'GBP' => $gbp,
		);

		return new MultiCurrencyState( $enabled, $enabled, $usd, $gbp );
	}

	/**
	 * Create a currency.
	 *
	 * @param string $code       Currency code.
	 * @param float  $rate       Currency rate.
	 * @param bool   $is_default Whether this is the default currency.
	 * @param string $rounding   Rounding amount.
	 * @param float  $charm      Charm amount.
	 * @return MultiCurrencyCurrency
	 */
	private function create_currency( string $code, float $rate, bool $is_default, string $rounding = '0', float $charm = 0.0 ): MultiCurrencyCurrency {
		$currency = new MultiCurrencyCurrency( $this->create_localization(), $code, $rate, $is_default );
		$currency->set_rounding( $rounding );
		$currency->set_charm( $charm );

		return $currency;
	}

	/**
	 * Create a product type test double.
	 *
	 * @param string $type Product type.
	 * @return object
	 */
	private function create_product_type( string $type ): object {
		return new class( $type ) {
			/**
			 * Product type.
			 *
			 * @var string
			 */
			private string $type;

			/**
			 * Constructor.
			 *
			 * @param string $type Product type.
			 */
			public function __construct( string $type ) {
				$this->type = $type;
			}

			/**
			 * Get the product type.
			 *
			 * @return string
			 */
			public function get_type(): string {
				return $this->type;
			}
		};
	}

	/**
	 * Create a localization test double.
	 *
	 * @return MultiCurrencyLocalizationInterface
	 */
	private function create_localization(): MultiCurrencyLocalizationInterface {
		return new class() implements MultiCurrencyLocalizationInterface {
			/**
			 * Get a currency format.
			 *
			 * @param string $currency_code Currency code.
			 * @return array<string,mixed>
			 */
			public function get_currency_format( $currency_code ): array {
				if ( 'GBP' === strtoupper( (string) $currency_code ) ) {
					return array(
						'currency_pos' => 'right_space',
						'thousand_sep' => '.',
						'decimal_sep'  => ',',
						'num_decimals' => 3,
					);
				}

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
}
