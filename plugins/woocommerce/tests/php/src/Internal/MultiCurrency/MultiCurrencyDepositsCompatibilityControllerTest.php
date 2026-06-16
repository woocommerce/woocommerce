<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyDepositsCompatibilityController;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceCalculator;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyDepositsCompatibilityController class.
 */
class MultiCurrencyDepositsCompatibilityControllerTest extends WC_Unit_Test_Case {

	/**
	 * Hooks touched by the Deposits compatibility controller.
	 *
	 * @var string[]
	 */
	private array $hooks = array(
		'plugins_loaded',
		'woocommerce_deposits_create_order',
		'woocommerce_get_cart_contents',
		'woocommerce_product_get__wc_deposit_amount',
		'wcpay_multi_currency_should_convert_product_price',
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
	 * @testdox Should register Deposits compatibility hooks for core runtime.
	 */
	public function test_registers_deposits_compatibility_hooks_for_core_runtime(): void {
		$sut = $this->create_controller();

		$sut->register();
		$sut->register();

		$this->assertSame( 10, has_action( 'woocommerce_deposits_create_order', array( $sut, 'modify_order_currency' ) ) );
		$this->assertSame( 10, has_filter( 'woocommerce_get_cart_contents', array( $sut, 'modify_cart_item_deposit_amounts' ) ) );
		$this->assertSame( 10, has_filter( 'woocommerce_product_get__wc_deposit_amount', array( $sut, 'modify_cart_item_deposit_amount_meta' ) ) );
		$this->assertSame( 10, has_filter( 'wcpay_multi_currency_should_convert_product_price', array( $sut, 'maybe_convert_product_prices_for_deposits' ) ) );
	}

	/**
	 * @testdox Should not register Deposits hooks when runtime guards block.
	 */
	public function test_does_not_register_deposits_hooks_when_guards_block(): void {
		$plugin_owned     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN );
		$missing_deposits = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false );
		$supported        = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, true, '2.0.1' );

		$plugin_owned->register();
		$missing_deposits->register();
		$supported->register();

		$this->assert_deposits_hooks_not_registered( $plugin_owned );
		$this->assert_deposits_hooks_not_registered( $missing_deposits );
		$this->assert_deposits_hooks_not_registered( $supported );
	}

	/**
	 * @testdox Should defer Deposits hook registration until plugins load.
	 */
	public function test_defers_deposits_registration_until_plugins_load(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false, '2.0.0', false );

		$sut->register();

		$this->assertFalse( has_filter( 'woocommerce_get_cart_contents', array( $sut, 'modify_cart_item_deposit_amounts' ) ) );
		$this->assertSame( 20, has_action( 'plugins_loaded', array( $sut, 'register_deposits_hooks' ) ) );

		$sut->set_deposits_available( true );
		$sut->register_deposits_hooks();

		$this->assertSame( 10, has_filter( 'woocommerce_get_cart_contents', array( $sut, 'modify_cart_item_deposit_amounts' ) ) );
		$this->assertSame( 10, has_action( 'woocommerce_deposits_create_order', array( $sut, 'modify_order_currency' ) ) );
	}

	/**
	 * @testdox Should convert deposit cart item amounts as product prices.
	 */
	public function test_converts_deposit_cart_item_amounts_as_product_prices(): void {
		$sut = $this->create_controller();

		$cart_contents = $sut->modify_cart_item_deposit_amounts(
			array(
				'deposit' => array(
					'is_deposit'     => true,
					'deposit_amount' => '10.00',
				),
				'regular' => array(
					'deposit_amount' => '10.00',
				),
			)
		);

		$this->assertSame( 8.4, $cart_contents['deposit']['deposit_amount'] );
		$this->assertSame( '10.00', $cart_contents['regular']['deposit_amount'] );
	}

	/**
	 * @testdox Should convert percent deposit amount meta while Deposits form output is rendered.
	 */
	public function test_converts_percent_deposit_amount_meta_while_deposits_form_output_is_rendered(): void {
		$sut = $this->create_controller();
		$sut->set_backtrace_calls( array( 'WC_Deposits_Cart_Manager->deposits_form_output' ) );

		$this->assertSame( 8.4, $sut->modify_cart_item_deposit_amount_meta( '10.00', $this->create_deposit_product( 'percent' ) ) );
		$this->assertSame( '10.00', $sut->modify_cart_item_deposit_amount_meta( '10.00', $this->create_deposit_product( 'plan' ) ) );

		$sut->set_backtrace_calls( array() );

		$this->assertSame( '10.00', $sut->modify_cart_item_deposit_amount_meta( '10.00', $this->create_deposit_product( 'percent' ) ) );
	}

	/**
	 * @testdox Should suppress product price conversion for payment plans during cart totals.
	 */
	public function test_suppresses_product_price_conversion_for_payment_plans_during_cart_totals(): void {
		$sut = $this->create_controller();
		$sut->set_backtrace_calls( array( 'WC_Cart->calculate_totals' ) );

		$this->assertFalse( $sut->maybe_convert_product_prices_for_deposits( true, $this->create_deposit_product( 'plan' ) ) );
		$this->assertTrue( $sut->maybe_convert_product_prices_for_deposits( true, $this->create_deposit_product( 'percent' ) ) );
		$this->assertFalse( $sut->maybe_convert_product_prices_for_deposits( false, $this->create_deposit_product( 'plan' ) ) );
	}

	/**
	 * @testdox Should align remaining payment order currency to original deposited order currency.
	 */
	public function test_aligns_remaining_payment_order_currency_to_original_deposited_order_currency(): void {
		$sut      = $this->create_controller();
		$original = wc_create_order();
		$original->set_currency( 'GBP' );
		$original->save();
		$remaining = $this->create_order_with_first_item( 'USD', $original->get_id() );

		$sut->modify_order_currency( $remaining->get_id() );

		$updated = wc_get_order( $remaining->get_id() );
		$this->assertSame( 'GBP', $updated->get_currency( 'view' ) );
	}

	/**
	 * @testdox Should preserve remaining payment order currency without original order meta.
	 */
	public function test_preserves_remaining_payment_order_currency_without_original_order_meta(): void {
		$sut       = $this->create_controller();
		$remaining = $this->create_order_with_first_item( 'USD' );

		$sut->modify_order_currency( $remaining->get_id() );

		$updated = wc_get_order( $remaining->get_id() );
		$this->assertSame( 'USD', $updated->get_currency( 'view' ) );
	}

	/**
	 * @testdox Should bootstrap Deposits compatibility controller.
	 */
	public function test_bootstrap_registers_deposits_compatibility_controller(): void {
		$controller = wc_get_container()->get( MultiCurrencyDepositsCompatibilityController::class );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local source assertion for bootstrap registration.
		$bootstrap_source = file_get_contents( WC()->plugin_path() . '/includes/class-woocommerce.php' );

		$this->assertInstanceOf( MultiCurrencyDepositsCompatibilityController::class, $controller );
		$this->assertIsString( $bootstrap_source );
		$this->assertStringContainsString( 'Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyDepositsCompatibilityController::class )->register()', $bootstrap_source );
	}

	/**
	 * Assert Deposits hooks are not registered for a controller.
	 *
	 * @param MultiCurrencyDepositsCompatibilityController $sut The controller.
	 */
	private function assert_deposits_hooks_not_registered( MultiCurrencyDepositsCompatibilityController $sut ): void {
		$this->assertFalse( has_action( 'woocommerce_deposits_create_order', array( $sut, 'modify_order_currency' ) ) );
		$this->assertFalse( has_filter( 'woocommerce_get_cart_contents', array( $sut, 'modify_cart_item_deposit_amounts' ) ) );
		$this->assertFalse( has_filter( 'woocommerce_product_get__wc_deposit_amount', array( $sut, 'modify_cart_item_deposit_amount_meta' ) ) );
		$this->assertFalse( has_filter( 'wcpay_multi_currency_should_convert_product_price', array( $sut, 'maybe_convert_product_prices_for_deposits' ) ) );
	}

	/**
	 * Create a Deposits compatibility controller with deterministic runtime context.
	 *
	 * @param string      $owner              Runtime owner.
	 * @param bool        $deposits_available Whether Deposits runtime is available.
	 * @param string|null $deposits_version   Deposits version.
	 * @param bool        $plugins_loaded     Whether plugins have loaded.
	 * @return MultiCurrencyDepositsCompatibilityController
	 */
	private function create_controller(
		string $owner = MultiCurrencyRuntimeArbiter::OWNER_CORE,
		bool $deposits_available = true,
		?string $deposits_version = '2.0.0',
		bool $plugins_loaded = true
	): MultiCurrencyDepositsCompatibilityController {
		$controller = new class( $deposits_available, $deposits_version, $plugins_loaded ) extends MultiCurrencyDepositsCompatibilityController {
			/**
			 * Whether Deposits runtime is available.
			 *
			 * @var bool
			 */
			private bool $deposits_available;

			/**
			 * Deposits version.
			 *
			 * @var string|null
			 */
			private ?string $deposits_version;

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
			 * @param bool        $deposits_available Whether Deposits runtime is available.
			 * @param string|null $deposits_version   Deposits version.
			 * @param bool        $plugins_loaded     Whether plugins have loaded.
			 */
			public function __construct( bool $deposits_available, ?string $deposits_version, bool $plugins_loaded ) {
				$this->deposits_available = $deposits_available;
				$this->deposits_version   = $deposits_version;
				$this->plugins_loaded     = $plugins_loaded;
			}

			/**
			 * Set whether Deposits runtime is available.
			 *
			 * @param bool $deposits_available Whether Deposits runtime is available.
			 */
			public function set_deposits_available( bool $deposits_available ): void {
				$this->deposits_available = $deposits_available;
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
			 * Check if Deposits runtime is available.
			 *
			 * @return bool
			 */
			protected function is_deposits_runtime_available(): bool {
				return $this->deposits_available;
			}

			/**
			 * Get Deposits version.
			 *
			 * @return string|null
			 */
			protected function get_deposits_version(): ?string {
				return $this->deposits_version;
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

			/**
			 * Gets the deposit type of a product.
			 *
			 * @param object $product Product.
			 * @return string|false
			 */
			protected function get_product_deposit_type( $product ) {
				return is_object( $product ) && isset( $product->deposit_type ) ? $product->deposit_type : false;
			}
		};

		$controller->init( $this->create_arbiter( $owner ) );
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

	/**
	 * Create a product test double with a deposit type.
	 *
	 * @param string $deposit_type Deposit type.
	 * @return object
	 */
	private function create_deposit_product( string $deposit_type ): object {
		return new class( $deposit_type ) {
			/**
			 * Deposit type.
			 *
			 * @var string
			 */
			public string $deposit_type;

			/**
			 * Constructor.
			 *
			 * @param string $deposit_type Deposit type.
			 */
			public function __construct( string $deposit_type ) {
				$this->deposit_type = $deposit_type;
			}
		};
	}

	/**
	 * Create an order with a first product item.
	 *
	 * @param string   $currency          Order currency.
	 * @param int|null $original_order_id Original order ID.
	 * @return \WC_Order
	 */
	private function create_order_with_first_item( string $currency, ?int $original_order_id = null ): \WC_Order {
		$order = wc_create_order();
		$order->set_currency( $currency );

		$item = new \WC_Order_Item_Product();
		$item->set_name( 'Remaining payment' );
		$item->set_quantity( 1 );
		$item->set_subtotal( 10 );
		$item->set_total( 10 );

		$order->add_item( $item );
		$order->save();

		if ( null !== $original_order_id ) {
			wc_add_order_item_meta( $item->get_id(), '_original_order_id', $original_order_id, true );
		}

		return $order;
	}
}
