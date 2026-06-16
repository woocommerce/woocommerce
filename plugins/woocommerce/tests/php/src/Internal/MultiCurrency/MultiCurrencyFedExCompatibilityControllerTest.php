<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyFedExCompatibilityController;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyFedExCompatibilityController class.
 */
class MultiCurrencyFedExCompatibilityControllerTest extends WC_Unit_Test_Case {

	/**
	 * FedEx shipping calls that should force store-currency behavior.
	 *
	 * @var string[]
	 */
	private array $fedex_calls = array(
		'WC_Shipping_Fedex->set_settings',
		'WC_Shipping_Fedex->per_item_shipping',
		'WC_Shipping_Fedex->box_shipping',
		'WC_Shipping_Fedex->get_fedex_api_request',
		'WC_Shipping_Fedex->get_fedex_requests',
		'WC_Shipping_Fedex->process_result',
	);

	/**
	 * Hooks touched by the FedEx compatibility controller.
	 *
	 * @var string[]
	 */
	private array $hooks = array(
		'plugins_loaded',
		'wcpay_multi_currency_should_convert_product_price',
		'wcpay_multi_currency_should_return_store_currency',
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
	 * @testdox Should register FedEx compatibility hooks for core runtime.
	 */
	public function test_registers_fedex_compatibility_hooks_for_core_runtime(): void {
		$sut = $this->create_controller();

		$sut->register();
		$sut->register();

		$this->assertSame( 10, has_filter( 'wcpay_multi_currency_should_convert_product_price', array( $sut, 'should_convert_product_price' ) ) );
		$this->assertSame( 10, has_filter( 'wcpay_multi_currency_should_return_store_currency', array( $sut, 'should_return_store_currency' ) ) );
	}

	/**
	 * @testdox Should not register FedEx hooks when runtime guards block.
	 */
	public function test_does_not_register_fedex_hooks_when_guards_block(): void {
		$plugin_owned  = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN );
		$missing_fedex = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false );

		$plugin_owned->register();
		$missing_fedex->register();

		$this->assert_fedex_hooks_not_registered( $plugin_owned );
		$this->assert_fedex_hooks_not_registered( $missing_fedex );
	}

	/**
	 * @testdox Should defer FedEx hook registration until plugins load.
	 */
	public function test_defers_fedex_registration_until_plugins_load(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false, false );

		$sut->register();

		$this->assertFalse( has_filter( 'wcpay_multi_currency_should_convert_product_price', array( $sut, 'should_convert_product_price' ) ) );
		$this->assertSame( 20, has_action( 'plugins_loaded', array( $sut, 'register_fedex_filters' ) ) );

		$sut->set_fedex_available( true );
		$sut->register_fedex_filters();

		$this->assertSame( 10, has_filter( 'wcpay_multi_currency_should_convert_product_price', array( $sut, 'should_convert_product_price' ) ) );
		$this->assertSame( 10, has_filter( 'wcpay_multi_currency_should_return_store_currency', array( $sut, 'should_return_store_currency' ) ) );
	}

	/**
	 * @testdox Should suppress product conversion during FedEx shipping calculations.
	 */
	public function test_suppresses_product_conversion_during_fedex_shipping_calculations(): void {
		$sut = $this->create_controller();

		$this->assertTrue( $sut->should_convert_product_price( true ) );
		$this->assertFalse( $sut->should_convert_product_price( false ) );

		foreach ( $this->fedex_calls as $fedex_call ) {
			$sut->set_backtrace_calls( array( $fedex_call ) );

			$this->assertFalse( $sut->should_convert_product_price( true ), "Expected {$fedex_call} to suppress conversion." );
		}
	}

	/**
	 * @testdox Should force store currency during FedEx shipping calculations.
	 */
	public function test_forces_store_currency_during_fedex_shipping_calculations(): void {
		$sut = $this->create_controller();

		$this->assertTrue( $sut->should_return_store_currency( true ) );
		$this->assertFalse( $sut->should_return_store_currency( false ) );

		foreach ( $this->fedex_calls as $fedex_call ) {
			$sut->set_backtrace_calls( array( $fedex_call ) );

			$this->assertTrue( $sut->should_return_store_currency( false ), "Expected {$fedex_call} to force store currency." );
		}
	}

	/**
	 * @testdox Should bootstrap FedEx compatibility controller.
	 */
	public function test_bootstrap_registers_fedex_compatibility_controller(): void {
		$controller = wc_get_container()->get( MultiCurrencyFedExCompatibilityController::class );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local source assertion for bootstrap registration.
		$bootstrap_source = file_get_contents( WC()->plugin_path() . '/includes/class-woocommerce.php' );

		$this->assertInstanceOf( MultiCurrencyFedExCompatibilityController::class, $controller );
		$this->assertIsString( $bootstrap_source );
		$this->assertStringContainsString( 'Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyFedExCompatibilityController::class )->register()', $bootstrap_source );
	}

	/**
	 * Assert FedEx hooks are not registered for a controller.
	 *
	 * @param MultiCurrencyFedExCompatibilityController $sut The controller.
	 */
	private function assert_fedex_hooks_not_registered( MultiCurrencyFedExCompatibilityController $sut ): void {
		$this->assertFalse( has_filter( 'wcpay_multi_currency_should_convert_product_price', array( $sut, 'should_convert_product_price' ) ) );
		$this->assertFalse( has_filter( 'wcpay_multi_currency_should_return_store_currency', array( $sut, 'should_return_store_currency' ) ) );
	}

	/**
	 * Create a FedEx compatibility controller with deterministic runtime context.
	 *
	 * @param string $owner          Runtime owner.
	 * @param bool   $fedex_available Whether FedEx runtime is available.
	 * @param bool   $plugins_loaded Whether plugins have loaded.
	 * @return MultiCurrencyFedExCompatibilityController
	 */
	private function create_controller(
		string $owner = MultiCurrencyRuntimeArbiter::OWNER_CORE,
		bool $fedex_available = true,
		bool $plugins_loaded = true
	): MultiCurrencyFedExCompatibilityController {
		$controller = new class( $fedex_available, $plugins_loaded ) extends MultiCurrencyFedExCompatibilityController {
			/**
			 * Whether FedEx runtime is available.
			 *
			 * @var bool
			 */
			private bool $fedex_available;

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
			 * @param bool $fedex_available Whether FedEx runtime is available.
			 * @param bool $plugins_loaded  Whether plugins have loaded.
			 */
			public function __construct( bool $fedex_available, bool $plugins_loaded ) {
				$this->fedex_available = $fedex_available;
				$this->plugins_loaded  = $plugins_loaded;
			}

			/**
			 * Set whether FedEx runtime is available.
			 *
			 * @param bool $fedex_available Whether FedEx runtime is available.
			 */
			public function set_fedex_available( bool $fedex_available ): void {
				$this->fedex_available = $fedex_available;
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
			 * Check if FedEx runtime is available.
			 *
			 * @return bool
			 */
			protected function is_fedex_runtime_available(): bool {
				return $this->fedex_available;
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

		$controller->init( $this->create_arbiter( $owner ) );

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
}
