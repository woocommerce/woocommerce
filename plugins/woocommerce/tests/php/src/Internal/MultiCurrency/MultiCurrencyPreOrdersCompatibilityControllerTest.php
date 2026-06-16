<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyPreOrdersCompatibilityController;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceCalculator;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyProjectionServiceFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyPreOrdersCompatibilityController class.
 */
class MultiCurrencyPreOrdersCompatibilityControllerTest extends WC_Unit_Test_Case {

	/**
	 * Hooks touched by the Pre-Orders compatibility controller.
	 *
	 * @var string[]
	 */
	private array $hooks = array(
		'plugins_loaded',
		'wc_pre_orders_fee',
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
	 * @testdox Should register Pre-Orders compatibility hooks for core runtime.
	 */
	public function test_registers_pre_orders_compatibility_hooks_for_core_runtime(): void {
		$sut = $this->create_controller();

		$sut->register();
		$sut->register();

		$this->assertSame( 10, has_filter( 'wc_pre_orders_fee', array( $sut, 'convert_pre_orders_fee' ) ) );
	}

	/**
	 * @testdox Should not register Pre-Orders hooks when runtime guards block.
	 */
	public function test_does_not_register_pre_orders_hooks_when_guards_block(): void {
		$plugin_owned       = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN );
		$missing_pre_orders = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false );

		$plugin_owned->register();
		$missing_pre_orders->register();

		$this->assert_pre_orders_hooks_not_registered( $plugin_owned );
		$this->assert_pre_orders_hooks_not_registered( $missing_pre_orders );
	}

	/**
	 * @testdox Should defer Pre-Orders hook registration until plugins load.
	 */
	public function test_defers_pre_orders_registration_until_plugins_load(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false, false );

		$sut->register();

		$this->assertFalse( has_filter( 'wc_pre_orders_fee', array( $sut, 'convert_pre_orders_fee' ) ) );
		$this->assertSame( 20, has_action( 'plugins_loaded', array( $sut, 'register_pre_orders_filters' ) ) );

		$sut->set_pre_orders_available( true );
		$sut->register_pre_orders_filters();

		$this->assertSame( 10, has_filter( 'wc_pre_orders_fee', array( $sut, 'convert_pre_orders_fee' ) ) );
	}

	/**
	 * @testdox Should convert Pre-Orders fee amount as a product price.
	 */
	public function test_converts_pre_orders_fee_amount_as_product_price(): void {
		$sut = $this->create_controller();

		$this->assertSame(
			array(
				'amount' => 8.4,
				'label'  => 'Pre-order fee',
			),
			$sut->convert_pre_orders_fee(
				array(
					'amount' => '10.00',
					'label'  => 'Pre-order fee',
				)
			)
		);
		$this->assertSame(
			array( 'label' => 'Pre-order fee' ),
			$sut->convert_pre_orders_fee( array( 'label' => 'Pre-order fee' ) )
		);
	}

	/**
	 * @testdox Should bootstrap Pre-Orders compatibility controller.
	 */
	public function test_bootstrap_registers_pre_orders_compatibility_controller(): void {
		$controller = wc_get_container()->get( MultiCurrencyPreOrdersCompatibilityController::class );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local source assertion for bootstrap registration.
		$bootstrap_source = file_get_contents( WC()->plugin_path() . '/includes/class-woocommerce.php' );

		$this->assertInstanceOf( MultiCurrencyPreOrdersCompatibilityController::class, $controller );
		$this->assertIsString( $bootstrap_source );
		$this->assertStringContainsString( 'Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyPreOrdersCompatibilityController::class )->register()', $bootstrap_source );
	}

	/**
	 * Assert Pre-Orders hooks are not registered for a controller.
	 *
	 * @param MultiCurrencyPreOrdersCompatibilityController $sut The controller.
	 */
	private function assert_pre_orders_hooks_not_registered( MultiCurrencyPreOrdersCompatibilityController $sut ): void {
		$this->assertFalse( has_filter( 'wc_pre_orders_fee', array( $sut, 'convert_pre_orders_fee' ) ) );
	}

	/**
	 * Create a Pre-Orders compatibility controller with deterministic runtime context.
	 *
	 * @param string $owner                Runtime owner.
	 * @param bool   $pre_orders_available Whether Pre-Orders runtime is available.
	 * @param bool   $plugins_loaded       Whether plugins have loaded.
	 * @return MultiCurrencyPreOrdersCompatibilityController
	 */
	private function create_controller(
		string $owner = MultiCurrencyRuntimeArbiter::OWNER_CORE,
		bool $pre_orders_available = true,
		bool $plugins_loaded = true
	): MultiCurrencyPreOrdersCompatibilityController {
		$controller = new class( $pre_orders_available, $plugins_loaded ) extends MultiCurrencyPreOrdersCompatibilityController {
			/**
			 * Whether Pre-Orders runtime is available.
			 *
			 * @var bool
			 */
			private bool $pre_orders_available;

			/**
			 * Whether plugins have loaded.
			 *
			 * @var bool
			 */
			private bool $plugins_loaded;

			/**
			 * Constructor.
			 *
			 * @param bool $pre_orders_available Whether Pre-Orders runtime is available.
			 * @param bool $plugins_loaded       Whether plugins have loaded.
			 */
			public function __construct( bool $pre_orders_available, bool $plugins_loaded ) {
				$this->pre_orders_available = $pre_orders_available;
				$this->plugins_loaded       = $plugins_loaded;
			}

			/**
			 * Set whether Pre-Orders runtime is available.
			 *
			 * @param bool $pre_orders_available Whether Pre-Orders runtime is available.
			 */
			public function set_pre_orders_available( bool $pre_orders_available ): void {
				$this->pre_orders_available = $pre_orders_available;
			}

			/**
			 * Check if Pre-Orders runtime is available.
			 *
			 * @return bool
			 */
			protected function is_pre_orders_runtime_available(): bool {
				return $this->pre_orders_available;
			}

			/**
			 * Check if plugins have loaded.
			 *
			 * @return bool
			 */
			protected function have_plugins_loaded(): bool {
				return $this->plugins_loaded;
			}
		};

		$controller->init(
			$this->create_arbiter( $owner ),
			wc_get_container()->get( MultiCurrencyProjectionServiceFactory::class )
		);
		$controller->set_price_projection_service( $this->create_price_projection_service() );

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
}
