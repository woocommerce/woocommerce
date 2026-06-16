<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Caches\OrderCache;
use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCompatibilityController;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilderFactory;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyCompatibilityController class.
 */
class MultiCurrencyCompatibilityControllerTest extends WC_Unit_Test_Case {

	/**
	 * Hooks touched by the compatibility controller.
	 *
	 * @var string[]
	 */
	private array $hooks = array(
		'init',
		'woocommerce_admin_sales_record_milestone_enabled',
		'woocommerce_order_query',
		'wcpay_multi_currency_override_selected_currency',
		'wcpay_multi_currency_should_hide_widgets',
		'wcpay_multi_currency_should_disable_currency_switching',
		'wcpay_multi_currency_should_convert_coupon_amount',
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

		unset( $_GET['pay_for_order'] );

		parent::tearDown();
	}

	/**
	 * @testdox Should not register hooks when plugin owns runtime.
	 */
	public function test_does_not_register_hooks_when_plugin_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN, true );

		$sut->register();

		$this->assertFalse( has_action( 'init', array( $sut, 'init_compatibility_classes' ) ) );
		$this->assertFalse( has_filter( 'woocommerce_admin_sales_record_milestone_enabled', array( $sut, 'attach_order_modifier' ) ) );
	}

	/**
	 * @testdox Should register compatibility hooks when core owns runtime.
	 */
	public function test_registers_compatibility_hooks_when_core_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false );

		$sut->register();
		$sut->register();

		$this->assertSame( 11, has_action( 'init', array( $sut, 'init_compatibility_classes' ) ) );
	}

	/**
	 * @testdox Should register sales record filter only for cron requests.
	 */
	public function test_registers_sales_record_filter_only_for_cron_requests(): void {
		$non_cron = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false );
		$cron     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, true );

		$non_cron->register();
		$cron->register();

		$this->assertFalse( has_filter( 'woocommerce_admin_sales_record_milestone_enabled', array( $non_cron, 'attach_order_modifier' ) ) );
		$this->assertSame( 10, has_filter( 'woocommerce_admin_sales_record_milestone_enabled', array( $cron, 'attach_order_modifier' ) ) );
	}

	/**
	 * @testdox Should project compatibility integrations when multiple currencies are enabled.
	 */
	public function test_projects_compatibility_integrations_when_multiple_currencies_are_enabled(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false, true, 2 );

		$sut->init_compatibility_classes();

		$this->assertSame(
			array(
				'WooCommerceBookings',
				'WooCommerceFedEx',
				'WooCommerceNameYourPrice',
				'WooCommercePreOrders',
				'WooCommerceProductAddOns',
				'WooCommerceSubscriptions',
				'WooCommerceUPS',
				'WooCommerceDeposits',
				'WooCommercePointsAndRewards',
			),
			$sut->get_compatibility_integrations()
		);
	}

	/**
	 * @testdox Should project no compatibility integrations with one currency.
	 */
	public function test_projects_no_compatibility_integrations_with_one_currency(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false, true, 1 );

		$sut->init_compatibility_classes();

		$this->assertSame( array(), $sut->get_compatibility_integrations() );
	}

	/**
	 * @testdox Should apply public compatibility decision filters.
	 */
	public function test_applies_public_compatibility_decision_filters(): void {
		$sut     = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );
		$product = (object) array( 'id' => 1 );
		$coupon  = (object) array( 'id' => 2 );

		add_filter(
			'wcpay_multi_currency_override_selected_currency',
			static function () {
				return 'EUR';
			}
		);
		add_filter( 'wcpay_multi_currency_should_convert_product_price', '__return_false' );
		add_filter( 'wcpay_multi_currency_should_convert_coupon_amount', '__return_false' );
		add_filter( 'wcpay_multi_currency_should_return_store_currency', '__return_true' );

		$this->assertSame( 'EUR', $sut->override_selected_currency() );
		$this->assertTrue( $sut->should_convert_product_price(), 'Empty product arguments should preserve the WooPayments true default.' );
		$this->assertTrue( $sut->should_convert_coupon_amount(), 'Empty coupon arguments should preserve the WooPayments true default.' );
		$this->assertFalse( $sut->should_convert_product_price( $product ) );
		$this->assertFalse( $sut->should_convert_coupon_amount( $coupon ) );
		$this->assertTrue( $sut->should_return_store_currency() );
	}

	/**
	 * @testdox Should disable currency switching for pay-for-order and external filters.
	 */
	public function test_disables_currency_switching_for_pay_for_order_and_external_filters(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$this->assertFalse( $sut->should_disable_currency_switching() );

		$_GET['pay_for_order'] = '1';

		$this->assertTrue( $sut->should_disable_currency_switching() );

		unset( $_GET['pay_for_order'] );
		add_filter( 'wcpay_multi_currency_should_disable_currency_switching', '__return_true' );

		$this->assertTrue( $sut->should_disable_currency_switching() );

		$this->setExpectedDeprecated( 'should_hide_widgets' );

		$this->assertTrue( $sut->should_hide_widgets() );
	}

	/**
	 * @testdox Should attach order modifier and return original value.
	 */
	public function test_attach_order_modifier_adds_order_query_filter_and_returns_original_value(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );

		$this->assertSame( 'enabled', $sut->attach_order_modifier( 'enabled' ) );

		$this->assertSame( 10, has_filter( 'woocommerce_order_query', array( $sut, 'convert_order_prices' ) ) );
	}

	/**
	 * @testdox Should convert sales record order totals to default currency.
	 */
	public function test_converts_sales_record_order_totals_to_default_currency(): void {
		$sut   = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false, true );
		$order = wc_create_order();
		$order->set_currency( 'GBP' );
		$order->set_total( 1000 );
		$order->update_meta_data( MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE, 0.5 );
		$order->update_meta_data( MultiCurrencyPriceProjectionService::META_KEY_ORDER_DEFAULT_CURRENCY, 'USD' );
		$order->save();

		add_filter( 'woocommerce_order_query', array( $sut, 'convert_order_prices' ) );

		$result = $sut->convert_order_prices( array( $order ) );

		wc_get_container()->get( OrderCache::class )->remove( $order->get_id() );
		$stored_order = wc_get_order( $order->get_id() );

		$this->assertSame( 2000.0, (float) $result[0]->get_total() );
		$this->assertInstanceOf( \WC_Order::class, $stored_order );
		$this->assertSame( 1000.0, (float) $stored_order->get_total(), 'The compatibility conversion should not persist the adjusted total.' );
		$this->assertFalse( has_filter( 'woocommerce_order_query', array( $sut, 'convert_order_prices' ) ) );
	}

	/**
	 * @testdox Should skip sales record conversion when context or meta do not match.
	 */
	public function test_skips_sales_record_conversion_when_context_or_meta_do_not_match(): void {
		$wrong_context = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false, false );
		$missing_meta  = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE, false, true );
		$order         = wc_create_order();
		$order->set_currency( 'GBP' );
		$order->set_total( 1000 );
		$order->save();
		$object_results = (object) array( $order );

		$this->assertSame( array( $order ), $wrong_context->convert_order_prices( array( $order ) ) );
		$this->assertSame( array( $order ), $missing_meta->convert_order_prices( array( $order ) ) );
		$this->assertSame( $object_results, $missing_meta->convert_order_prices( $object_results ) );
		$this->assertSame( 1000.0, (float) $order->get_total() );
	}

	/**
	 * @testdox Should bootstrap compatibility controller.
	 */
	public function test_bootstrap_registers_compatibility_controller(): void {
		$controller = wc_get_container()->get( MultiCurrencyCompatibilityController::class );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local source assertion for bootstrap registration.
		$bootstrap_source = file_get_contents( WC()->plugin_path() . '/includes/class-woocommerce.php' );

		$this->assertInstanceOf( MultiCurrencyCompatibilityController::class, $controller );
		$this->assertIsString( $bootstrap_source );
		$this->assertStringContainsString( 'Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCompatibilityController::class )->register()', $bootstrap_source );
	}

	/**
	 * Create a compatibility controller with deterministic runtime context.
	 *
	 * @param string $owner                 Runtime owner.
	 * @param bool   $is_cron               Whether the request is cron.
	 * @param bool   $is_expected_backtrace Whether the sales-record backtrace is present.
	 * @param int    $enabled_count         Enabled currency count.
	 * @return MultiCurrencyCompatibilityController
	 */
	private function create_controller(
		string $owner,
		bool $is_cron = false,
		bool $is_expected_backtrace = true,
		int $enabled_count = 2
	): MultiCurrencyCompatibilityController {
		$controller = new class( $is_cron, $is_expected_backtrace ) extends MultiCurrencyCompatibilityController {
			/**
			 * Whether the request is cron.
			 *
			 * @var bool
			 */
			private bool $is_cron;

			/**
			 * Whether the expected sales-record backtrace is present.
			 *
			 * @var bool
			 */
			private bool $is_expected_backtrace;

			/**
			 * Constructor.
			 *
			 * @param bool $is_cron               Whether the request is cron.
			 * @param bool $is_expected_backtrace Whether the expected sales-record backtrace is present.
			 */
			public function __construct( bool $is_cron, bool $is_expected_backtrace ) {
				$this->is_cron               = $is_cron;
				$this->is_expected_backtrace = $is_expected_backtrace;
			}

			/**
			 * Tell whether the current request is cron.
			 *
			 * @return bool
			 */
			protected function is_cron_request(): bool {
				return $this->is_cron;
			}

			/**
			 * Tell whether expected calls are present in the backtrace.
			 *
			 * @param string[] $expected_calls Expected call strings.
			 * @return bool
			 */
			protected function is_call_in_backtrace( array $expected_calls ): bool {
				unset( $expected_calls );

				return $this->is_expected_backtrace;
			}
		};
		$controller->init(
			$this->create_arbiter( $owner ),
			wc_get_container()->get( MultiCurrencyStateBuilderFactory::class )
		);
		$controller->set_state_builder( $this->create_state_builder( $enabled_count ) );

		return $controller;
	}

	/**
	 * Create a static multi-currency runtime arbiter.
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
			 * Get the multi-currency runtime owner for the current site.
			 *
			 * @return string
			 */
			public function get_runtime_owner(): string {
				return $this->owner;
			}

			/**
			 * Tell whether core multi-currency may register hooks.
			 *
			 * @return bool
			 */
			public function should_core_register(): bool {
				return MultiCurrencyRuntimeArbiter::OWNER_CORE === $this->owner;
			}
		};
	}

	/**
	 * Create a deterministic state builder.
	 *
	 * @param int $enabled_count Enabled currency count.
	 * @return MultiCurrencyStateBuilder
	 */
	private function create_state_builder( int $enabled_count ): MultiCurrencyStateBuilder {
		return new class( $enabled_count, $this->create_localization() ) extends MultiCurrencyStateBuilder {
			/**
			 * Enabled currency count.
			 *
			 * @var int
			 */
			private int $enabled_count;

			/**
			 * Localization service.
			 *
			 * @var MultiCurrencyLocalizationInterface
			 */
			private MultiCurrencyLocalizationInterface $localization_service;

			/**
			 * Constructor.
			 *
			 * @param int                                $enabled_count         Enabled currency count.
			 * @param MultiCurrencyLocalizationInterface $localization_service Localization service.
			 */
			public function __construct( int $enabled_count, MultiCurrencyLocalizationInterface $localization_service ) {
				$this->enabled_count        = $enabled_count;
				$this->localization_service = $localization_service;
			}

			/**
			 * Build a deterministic multi-currency state snapshot.
			 *
			 * @return MultiCurrencyState
			 */
			public function build(): MultiCurrencyState {
				$usd       = new MultiCurrencyCurrency( $this->localization_service, 'USD', 1.0, true );
				$gbp       = new MultiCurrencyCurrency( $this->localization_service, 'GBP', 0.5, false );
				$available = array(
					'USD' => $usd,
					'GBP' => $gbp,
				);
				$enabled   = 1 === $this->enabled_count ? array( 'USD' => $usd ) : $available;

				return new MultiCurrencyState( $available, $enabled, $usd, 1 === $this->enabled_count ? $usd : $gbp );
			}
		};
	}

	/**
	 * Create a localization service test double.
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

				return array( 'num_decimals' => 2 );
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
