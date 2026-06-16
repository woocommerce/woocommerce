<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyAnalyticsController;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\Providers\CurrencyRateProviderRegistry;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyAnalyticsProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyDatabaseCache;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyLocalizationService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRateService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilderFactory;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyAnalyticsController class.
 */
class MultiCurrencyAnalyticsControllerTest extends WC_Unit_Test_Case {

	/**
	 * Hooks touched by the analytics controller.
	 *
	 * @var string[]
	 */
	private array $hooks = array(
		'woocommerce_analytics_report_should_use_cache',
		'woocommerce_analytics_update_order_stats_data',
		'woocommerce_analytics_orders_query_args',
		'woocommerce_analytics_orders_stats_query_args',
		'woocommerce_analytics_clauses_select',
		'woocommerce_analytics_clauses_join',
		'woocommerce_analytics_clauses_where_orders_subquery',
		'woocommerce_analytics_clauses_where_orders_stats_total',
		'woocommerce_analytics_clauses_where_orders_stats_interval',
		'woocommerce_analytics_clauses_select_orders_subquery',
		'woocommerce_analytics_clauses_select_orders_stats_total',
		'wcpay_multi_currency_disable_filter_select_clauses',
		'wcpay_multi_currency_filter_select_clauses',
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
	 * @testdox Should not register analytics hooks when plugin owns runtime.
	 */
	public function test_does_not_register_analytics_hooks_when_plugin_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_PLUGIN );
		$sut->set_dev_mode_resolver( static fn(): bool => true );
		$sut->set_rest_request_resolver( static fn(): bool => true );
		$sut->set_multi_currency_orders_resolver( static fn(): bool => true );

		$sut->register();

		$this->assertFalse( has_filter( 'woocommerce_analytics_update_order_stats_data', array( $sut, 'handle_woocommerce_analytics_update_order_stats_data' ) ) );
		$this->assertFalse( has_filter( 'woocommerce_analytics_clauses_select', array( $sut, 'handle_woocommerce_analytics_clauses_select' ) ) );
		$this->assertFalse( has_filter( 'woocommerce_analytics_report_should_use_cache', array( $sut, 'handle_woocommerce_analytics_report_should_use_cache' ) ) );
	}

	/**
	 * @testdox Should register baseline analytics hooks when core owns runtime.
	 */
	public function test_registers_baseline_analytics_hooks_when_core_owns_runtime(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );
		$sut->set_rest_request_resolver( static fn(): bool => false );
		$sut->set_multi_currency_orders_resolver( static fn(): bool => true );

		$sut->register();
		$sut->register();

		$this->assertSame( 99999, has_filter( 'woocommerce_analytics_update_order_stats_data', array( $sut, 'handle_woocommerce_analytics_update_order_stats_data' ) ) );
		$this->assertSame( 10, has_filter( 'woocommerce_analytics_orders_query_args', array( $sut, 'handle_woocommerce_analytics_orders_query_args' ) ) );
		$this->assertSame( 10, has_filter( 'woocommerce_analytics_orders_stats_query_args', array( $sut, 'handle_woocommerce_analytics_orders_query_args' ) ) );
		$this->assertFalse( has_filter( 'woocommerce_analytics_clauses_select', array( $sut, 'handle_woocommerce_analytics_clauses_select' ) ) );
		$this->assertFalse( has_filter( 'woocommerce_analytics_report_should_use_cache', array( $sut, 'handle_woocommerce_analytics_report_should_use_cache' ) ) );
	}

	/**
	 * @testdox Should register SQL hooks only for REST requests with multi-currency orders.
	 */
	public function test_registers_sql_hooks_only_for_rest_requests_with_multi_currency_orders(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );
		$sut->set_rest_request_resolver( static fn(): bool => true );
		$sut->set_multi_currency_orders_resolver( static fn(): bool => true );

		$sut->register();

		$this->assertSame( 20, has_filter( 'woocommerce_analytics_clauses_select', array( $sut, 'handle_woocommerce_analytics_clauses_select' ) ) );
		$this->assertSame( 20, has_filter( 'woocommerce_analytics_clauses_join', array( $sut, 'handle_woocommerce_analytics_clauses_join' ) ) );
		$this->assertSame( 10, has_filter( 'woocommerce_analytics_clauses_where_orders_subquery', array( $sut, 'handle_woocommerce_analytics_clauses_where' ) ) );
	}

	/**
	 * @testdox Should register selected-currency SQL hooks only for non-default currency.
	 */
	public function test_registers_selected_currency_sql_hooks_only_for_non_default_currency(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );
		$sut->set_rest_request_resolver( static fn(): bool => true );
		$sut->set_multi_currency_orders_resolver( static fn(): bool => true );
		$sut->set_default_currency_resolver( static fn(): string => 'USD' );
		$sut->set_request_args_resolver( static fn(): array => array( 'currency' => 'EUR' ) );

		$sut->register();

		$this->assertSame( 10, has_filter( 'woocommerce_analytics_clauses_select_orders_subquery', array( $sut, 'handle_woocommerce_analytics_clauses_select_orders' ) ) );
		$this->assertSame( 10, has_filter( 'woocommerce_analytics_clauses_select_orders_stats_total', array( $sut, 'handle_woocommerce_analytics_clauses_select_orders' ) ) );
	}

	/**
	 * @testdox Should disable analytics cache in dev mode.
	 */
	public function test_disables_analytics_cache_in_dev_mode(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );
		$sut->set_dev_mode_resolver( static fn(): bool => true );

		$sut->register();

		/**
		 * Filters whether analytics report cache should be used.
		 *
		 * @param bool $should_use_cache Whether analytics report cache should be used.
		 *
		 * @since 11.0.0
		 */
		$this->assertFalse( apply_filters( 'woocommerce_analytics_report_should_use_cache', true ) );
	}

	/**
	 * @testdox Should apply customer currency request args.
	 */
	public function test_applies_customer_currency_request_args(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );
		$sut->set_request_args_resolver(
			static fn(): array => array(
				'currency_is' => array( ' EUR ', 'GBP' ),
				'currency'    => ' CAD ',
			)
		);

		$result = $sut->handle_woocommerce_analytics_orders_query_args( array( 'status' => 'completed' ) );

		$this->assertSame( array( 'EUR', 'GBP' ), $result['currency_is'] );
		$this->assertSame( array(), $result['currency_is_not'] );
		$this->assertSame( 'CAD', $result['currency'] );
		$this->assertSame( 'completed', $result['status'] );
	}

	/**
	 * @testdox Should update order stats data through projection.
	 */
	public function test_updates_order_stats_data_through_projection(): void {
		update_option( 'woocommerce_currency', 'USD' );
		$order = wc_create_order();
		$this->assertInstanceOf( \WC_Order::class, $order );
		$order->set_currency( 'EUR' );
		$order->update_meta_data( '_wcpay_multi_currency_order_exchange_rate', 2 );
		$order->update_meta_data( '_wcpay_multi_currency_order_default_currency', 'USD' );
		$order->save();
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );
		$sut->set_analytics_projection_service(
			new MultiCurrencyAnalyticsProjectionService( $this->create_state_builder() )
		);

		$result = $sut->handle_woocommerce_analytics_update_order_stats_data(
			array(
				'net_total'      => 10.0,
				'shipping_total' => 4.0,
				'tax_total'      => 2.0,
			),
			$order
		);

		$this->assertSame( 5.0, $result['net_total'] );
		$this->assertSame( 2.0, $result['shipping_total'] );
		$this->assertSame( 1.0, $result['tax_total'] );
		$this->assertSame( 8.0, $result['total_sales'] );
	}

	/**
	 * @testdox Should respect SQL clause disable and extension filters.
	 */
	public function test_respects_sql_clause_disable_and_extension_filters(): void {
		$sut = $this->create_controller( MultiCurrencyRuntimeArbiter::OWNER_CORE );
		$sut->set_hpos_resolver( static fn(): bool => false );
		$clauses = array( 'discount_amount' );
		add_filter( 'wcpay_multi_currency_disable_filter_select_clauses', '__return_true' );

		$this->assertSame( $clauses, $sut->handle_woocommerce_analytics_clauses_select( $clauses, 'orders_stats' ) );

		remove_filter( 'wcpay_multi_currency_disable_filter_select_clauses', '__return_true' );
		add_filter(
			'wcpay_multi_currency_filter_select_clauses',
			static fn(): array => array( 'filtered' )
		);

		$this->assertSame( array( 'filtered' ), $sut->handle_woocommerce_analytics_clauses_select( $clauses, 'orders_stats' ) );
	}

	/**
	 * Create an analytics controller.
	 *
	 * @param string $owner Runtime owner.
	 * @return MultiCurrencyAnalyticsController
	 */
	private function create_controller( string $owner ): MultiCurrencyAnalyticsController {
		$controller = new MultiCurrencyAnalyticsController();
		$controller->init(
			$this->create_arbiter( $owner ),
			wc_get_container()->get( MultiCurrencyStateBuilderFactory::class )
		);
		$controller->set_dev_mode_resolver( static fn(): bool => false );
		$controller->set_rest_request_resolver( static fn(): bool => false );
		$controller->set_multi_currency_orders_resolver( static fn(): bool => false );
		$controller->set_hpos_resolver( static fn(): bool => false );
		$controller->set_default_currency_resolver( static fn(): string => 'USD' );
		$controller->set_request_args_resolver( static fn(): array => array() );

		return $controller;
	}

	/**
	 * Create a state builder.
	 *
	 * @return MultiCurrencyStateBuilder
	 */
	private function create_state_builder(): MultiCurrencyStateBuilder {
		$localization_service = new MultiCurrencyLocalizationService();

		return new MultiCurrencyStateBuilder(
			$localization_service,
			new MultiCurrencyRateService( new CurrencyRateProviderRegistry() ),
			new MultiCurrencyDatabaseCache()
		);
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
}
