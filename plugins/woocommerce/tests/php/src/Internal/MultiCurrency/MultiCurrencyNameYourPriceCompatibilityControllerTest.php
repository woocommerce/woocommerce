<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyNameYourPriceCompatibilityController;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceCalculator;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyProjectionServiceFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilderFactory;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyNameYourPriceCompatibilityController class.
 */
class MultiCurrencyNameYourPriceCompatibilityControllerTest extends WC_Unit_Test_Case {

	/**
	 * Hooks touched by the Name Your Price compatibility controller.
	 *
	 * @var string[]
	 */
	private array $hooks = array(
		'plugins_loaded',
		'wc_nyp_raw_minimum_price',
		'wc_nyp_raw_maximum_price',
		'wc_nyp_raw_suggested_price',
		'woocommerce_add_cart_item_data',
		'woocommerce_get_cart_item_from_session',
		'wcpay_multi_currency_should_convert_product_price',
		'wc_nyp_edit_in_cart_args',
		'wc_nyp_get_initial_price',
	);

	/**
	 * Original request data.
	 *
	 * @var array<mixed>
	 */
	private array $original_request = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_request = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Captures request fixture state for restoration.
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->hooks as $hook ) {
			remove_all_filters( $hook );
		}

		$_REQUEST = $this->original_request; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Restores request fixture state.

		parent::tearDown();
	}

	/**
	 * @testdox Should register Name Your Price compatibility hooks for core runtime.
	 */
	public function test_registers_name_your_price_compatibility_hooks_for_core_runtime(): void {
		$sut = $this->create_controller();

		$sut->register();
		$sut->register();

		$this->assertSame( 10, has_filter( 'wc_nyp_raw_minimum_price', array( $sut, 'get_nyp_prices' ) ) );
		$this->assertSame( 10, has_filter( 'wc_nyp_raw_maximum_price', array( $sut, 'get_nyp_prices' ) ) );
		$this->assertSame( 10, has_filter( 'wc_nyp_raw_suggested_price', array( $sut, 'get_nyp_prices' ) ) );
		$this->assertSame( 20, has_action( 'woocommerce_add_cart_item_data', array( $sut, 'add_initial_currency' ) ) );
		$this->assertSame( 20, has_filter( 'woocommerce_get_cart_item_from_session', array( $sut, 'convert_cart_currency' ) ) );
		$this->assertSame( 50, has_filter( 'wcpay_multi_currency_should_convert_product_price', array( $sut, 'should_convert_product_price' ) ) );
		$this->assertSame( 10, has_filter( 'wc_nyp_edit_in_cart_args', array( $sut, 'edit_in_cart_args' ) ) );
		$this->assertSame( 10, has_filter( 'wc_nyp_get_initial_price', array( $sut, 'get_initial_price' ) ) );
	}

	/**
	 * @testdox Should not register Name Your Price hooks when runtime guards block.
	 */
	public function test_does_not_register_name_your_price_hooks_when_guards_block(): void {
		$plugin_owned = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN );
		$missing_nyp  = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false );

		$plugin_owned->register();
		$missing_nyp->register();

		$this->assert_name_your_price_hooks_not_registered( $plugin_owned );
		$this->assert_name_your_price_hooks_not_registered( $missing_nyp );
	}

	/**
	 * @testdox Should defer Name Your Price hook registration until plugins load.
	 */
	public function test_defers_name_your_price_registration_until_plugins_load(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false, true, false );

		$sut->register();

		$this->assertFalse( has_filter( 'wc_nyp_raw_minimum_price', array( $sut, 'get_nyp_prices' ) ) );
		$this->assertSame( 20, has_action( 'plugins_loaded', array( $sut, 'register_name_your_price_hooks' ) ) );

		$sut->set_name_your_price_available( true );
		$sut->register_name_your_price_hooks();

		$this->assertSame( 10, has_filter( 'wc_nyp_raw_minimum_price', array( $sut, 'get_nyp_prices' ) ) );
		$this->assertSame( 20, has_action( 'woocommerce_add_cart_item_data', array( $sut, 'add_initial_currency' ) ) );
	}

	/**
	 * @testdox Should convert raw NYP prices as product prices.
	 */
	public function test_converts_raw_nyp_prices_as_product_prices(): void {
		$sut = $this->create_controller();

		$this->assertSame( 8.4, $sut->get_nyp_prices( '10.00' ) );
		$this->assertSame( 0, $sut->get_nyp_prices( 0 ) );
		$this->assertSame( '', $sut->get_nyp_prices( '' ) );
	}

	/**
	 * @testdox Should store selected currency and original NYP price when a NYP item is added.
	 */
	public function test_stores_selected_currency_and_original_nyp_price_when_nyp_item_is_added(): void {
		$sut = $this->create_controller();
		$sut->set_name_your_price_product_ids( array( 10, 20 ) );

		$this->assertSame(
			array(
				'nyp'          => '12.00',
				'nyp_currency' => 'GBP',
				'nyp_original' => '12.00',
			),
			$sut->add_initial_currency( array( 'nyp' => '12.00' ), 10, 0 )
		);
		$this->assertSame(
			array(
				'nyp'          => '14.00',
				'nyp_currency' => 'GBP',
				'nyp_original' => '14.00',
			),
			$sut->add_initial_currency( array( 'nyp' => '14.00' ), 10, 20 )
		);
		$this->assertSame( array( 'nyp' => '12.00' ), $sut->add_initial_currency( array( 'nyp' => '12.00' ), 99, 0 ) );
		$this->assertSame( array(), $sut->add_initial_currency( array(), 10, 0 ) );
	}

	/**
	 * @testdox Should restore original NYP amount when cart currency matches the selected currency.
	 */
	public function test_restores_original_nyp_amount_when_cart_currency_matches_selected_currency(): void {
		$sut     = $this->create_controller();
		$product = $this->create_product();

		$cart_item = $sut->convert_cart_currency(
			array(
				'data'         => $product,
				'nyp'          => '99.00',
				'nyp_original' => '12.00',
				'nyp_currency' => 'GBP',
			),
			array()
		);

		$this->assertSame( '12.00', $cart_item['nyp'] );
		$this->assertSame( 'GBP', $product->get_meta( '_wcpay_multi_currency_nyp_currency' ) );
	}

	/**
	 * @testdox Should convert original NYP amount when cart currency differs from selected currency.
	 */
	public function test_converts_original_nyp_amount_when_cart_currency_differs_from_selected_currency(): void {
		$sut     = $this->create_controller();
		$product = $this->create_product();

		$cart_item = $sut->convert_cart_currency(
			array(
				'data'         => $product,
				'nyp'          => '99.00',
				'nyp_original' => '10.00',
				'nyp_currency' => 'USD',
			),
			array()
		);

		$this->assertSame( 8.2, $cart_item['nyp'] );
		$this->assertSame( 'USD', $product->get_meta( '_wcpay_multi_currency_nyp_currency' ) );
	}

	/**
	 * @testdox Should preserve cart item when NYP runtime function is unavailable.
	 */
	public function test_preserves_cart_item_when_name_your_price_function_is_unavailable(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, true, false );

		$cart_item = array(
			'nyp_original' => '10.00',
			'nyp_currency' => 'USD',
		);

		$this->assertSame( $cart_item, $sut->convert_cart_currency( $cart_item, array() ) );
	}

	/**
	 * @testdox Should suppress product price conversion for NYP product contexts.
	 */
	public function test_suppresses_product_price_conversion_for_nyp_product_contexts(): void {
		$sut               = $this->create_controller();
		$product_with_meta = $this->create_product( 'GBP' );
		$nyp_product       = $this->create_product( '', true );

		$this->assertFalse( $sut->should_convert_product_price( true, $product_with_meta ) );
		$this->assertFalse( $sut->should_convert_product_price( true, $nyp_product ) );
		$this->assertFalse( $sut->should_convert_product_price( false, $this->create_product() ) );
		$this->assertTrue( $sut->should_convert_product_price( true, $this->create_product() ) );
	}

	/**
	 * @testdox Should add selected currency to cart edit args.
	 */
	public function test_adds_selected_currency_to_cart_edit_args(): void {
		$sut = $this->create_controller();

		$this->assertSame(
			array(
				'existing'     => 'value',
				'nyp_currency' => 'GBP',
			),
			$sut->edit_in_cart_args( array( 'existing' => 'value' ), array() )
		);
	}

	/**
	 * @testdox Should convert initial edit price from request currency.
	 */
	public function test_converts_initial_edit_price_from_request_currency(): void {
		$sut                        = $this->create_controller();
		$_REQUEST['nyp_raw_bundle'] = '10.00';
		$_REQUEST['nyp_currency']   = 'USD';

		$this->assertSame( 8.2, $sut->get_initial_price( '99.00', $this->create_product(), '_bundle' ) );

		$_REQUEST['nyp_currency'] = 'GBP';

		$this->assertSame( '99.00', $sut->get_initial_price( '99.00', $this->create_product(), '_bundle' ) );
	}

	/**
	 * @testdox Should bootstrap Name Your Price compatibility controller.
	 */
	public function test_bootstrap_registers_name_your_price_compatibility_controller(): void {
		$controller = wc_get_container()->get( MultiCurrencyNameYourPriceCompatibilityController::class );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local source assertion for bootstrap registration.
		$bootstrap_source = file_get_contents( WC()->plugin_path() . '/includes/class-woocommerce.php' );

		$this->assertInstanceOf( MultiCurrencyNameYourPriceCompatibilityController::class, $controller );
		$this->assertIsString( $bootstrap_source );
		$this->assertStringContainsString( 'Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyNameYourPriceCompatibilityController::class )->register()', $bootstrap_source );
	}

	/**
	 * Assert Name Your Price hooks are not registered for a controller.
	 *
	 * @param MultiCurrencyNameYourPriceCompatibilityController $sut The controller.
	 */
	private function assert_name_your_price_hooks_not_registered( MultiCurrencyNameYourPriceCompatibilityController $sut ): void {
		$this->assertFalse( has_filter( 'wc_nyp_raw_minimum_price', array( $sut, 'get_nyp_prices' ) ) );
		$this->assertFalse( has_action( 'woocommerce_add_cart_item_data', array( $sut, 'add_initial_currency' ) ) );
		$this->assertFalse( has_filter( 'woocommerce_get_cart_item_from_session', array( $sut, 'convert_cart_currency' ) ) );
		$this->assertFalse( has_filter( 'wcpay_multi_currency_should_convert_product_price', array( $sut, 'should_convert_product_price' ) ) );
	}

	/**
	 * Create a Name Your Price compatibility controller with deterministic runtime context.
	 *
	 * @param string $owner                              Runtime owner.
	 * @param bool   $name_your_price_available          Whether Name Your Price runtime is available.
	 * @param bool   $name_your_price_function_available Whether the Name Your Price function is available.
	 * @param bool   $plugins_loaded                     Whether plugins have loaded.
	 * @return MultiCurrencyNameYourPriceCompatibilityController
	 */
	private function create_controller(
		string $owner = MultiCurrencyRuntimeArbiter::OWNER_CORE,
		bool $name_your_price_available = true,
		bool $name_your_price_function_available = true,
		bool $plugins_loaded = true
	): MultiCurrencyNameYourPriceCompatibilityController {
		$controller = new class( $name_your_price_available, $name_your_price_function_available, $plugins_loaded ) extends MultiCurrencyNameYourPriceCompatibilityController {
			/**
			 * Whether Name Your Price runtime is available.
			 *
			 * @var bool
			 */
			private bool $name_your_price_available;

			/**
			 * Whether the Name Your Price function is available.
			 *
			 * @var bool
			 */
			private bool $name_your_price_function_available;

			/**
			 * Whether plugins have loaded.
			 *
			 * @var bool
			 */
			private bool $plugins_loaded;

			/**
			 * NYP-enabled product IDs.
			 *
			 * @var int[]
			 */
			private array $name_your_price_product_ids = array();

			/**
			 * Constructor.
			 *
			 * @param bool $name_your_price_available          Whether Name Your Price runtime is available.
			 * @param bool $name_your_price_function_available Whether the Name Your Price function is available.
			 * @param bool $plugins_loaded                     Whether plugins have loaded.
			 */
			public function __construct( bool $name_your_price_available, bool $name_your_price_function_available, bool $plugins_loaded ) {
				$this->name_your_price_available          = $name_your_price_available;
				$this->name_your_price_function_available = $name_your_price_function_available;
				$this->plugins_loaded                     = $plugins_loaded;
			}

			/**
			 * Set whether Name Your Price runtime is available.
			 *
			 * @param bool $name_your_price_available Whether Name Your Price runtime is available.
			 */
			public function set_name_your_price_available( bool $name_your_price_available ): void {
				$this->name_your_price_available = $name_your_price_available;
			}

			/**
			 * Set NYP-enabled product IDs.
			 *
			 * @param int[] $product_ids Product IDs.
			 */
			public function set_name_your_price_product_ids( array $product_ids ): void {
				$this->name_your_price_product_ids = $product_ids;
			}

			/**
			 * Check if Name Your Price runtime is available.
			 *
			 * @return bool
			 */
			protected function is_name_your_price_runtime_available(): bool {
				return $this->name_your_price_available;
			}

			/**
			 * Check if the Name Your Price function is available.
			 *
			 * @return bool
			 */
			protected function is_name_your_price_function_available(): bool {
				return $this->name_your_price_function_available;
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
			 * Tell whether a product is NYP-enabled.
			 *
			 * @param mixed $product Product or product ID.
			 * @return bool
			 */
			protected function is_name_your_price_product( $product ): bool {
				if ( is_int( $product ) ) {
					return in_array( $product, $this->name_your_price_product_ids, true );
				}

				return is_object( $product ) && ! empty( $product->name_your_price_enabled );
			}

			/**
			 * Run the Name Your Price cart item normalizer.
			 *
			 * @param array<mixed> $cart_item Cart item.
			 * @return array<mixed>
			 */
			protected function set_name_your_price_cart_item( array $cart_item ): array {
				return $cart_item;
			}
		};

		$controller->init(
			$this->create_arbiter( $owner ),
			wc_get_container()->get( MultiCurrencyStateBuilderFactory::class ),
			wc_get_container()->get( MultiCurrencyProjectionServiceFactory::class )
		);
		$controller->set_price_projection_service( $this->create_price_projection_service() );
		$controller->set_state_builder( $this->create_state_builder( $this->create_state() ) );

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
	 * Create a product test double.
	 *
	 * @param string $nyp_currency             Stored NYP currency.
	 * @param bool   $name_your_price_enabled Whether the product is NYP-enabled.
	 * @return object
	 */
	private function create_product( string $nyp_currency = '', bool $name_your_price_enabled = false ): object {
		return new class( $nyp_currency, $name_your_price_enabled ) {
			/**
			 * Whether the product is NYP-enabled.
			 *
			 * @var bool
			 */
			public bool $name_your_price_enabled;

			/**
			 * Product meta data.
			 *
			 * @var array<string,mixed>
			 */
			private array $meta = array();

			/**
			 * Constructor.
			 *
			 * @param string $nyp_currency             Stored NYP currency.
			 * @param bool   $name_your_price_enabled Whether the product is NYP-enabled.
			 */
			public function __construct( string $nyp_currency, bool $name_your_price_enabled ) {
				$this->name_your_price_enabled = $name_your_price_enabled;
				if ( '' !== $nyp_currency ) {
					$this->meta['_wcpay_multi_currency_nyp_currency'] = $nyp_currency;
				}
			}

			/**
			 * Update product meta.
			 *
			 * @param string $key   Meta key.
			 * @param mixed  $value Meta value.
			 */
			public function update_meta_data( string $key, $value ): void {
				$this->meta[ $key ] = $value;
			}

			/**
			 * Get product meta.
			 *
			 * @param string $key Meta key.
			 * @return mixed
			 */
			public function get_meta( string $key ) {
				return $this->meta[ $key ] ?? '';
			}
		};
	}
}
