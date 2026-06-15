<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Shadow;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyRuntimeArbiter;
use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyCacheInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyLocalizationService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use Automattic\WooCommerce\Internal\MultiCurrency\Shadow\MultiCurrencyShadowComparison;
use Automattic\WooCommerce\Internal\MultiCurrency\Shadow\MultiCurrencyShadowMode;
use Automattic\WooCommerce\Internal\MultiCurrency\Shadow\MultiCurrencySurfaceDiffer;
use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WC_Order_Refund;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyShadowMode class.
 */
class MultiCurrencyShadowModeTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var MultiCurrencyShadowMode
	 */
	private $sut;

	/**
	 * Fake price projection service.
	 *
	 * @var MultiCurrencyPriceProjectionService
	 */
	private $projection_service;

	/**
	 * Original store currency.
	 *
	 * @var string
	 */
	private string $original_currency;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_currency  = get_option( 'woocommerce_currency', 'USD' );
		$this->projection_service = new class() extends MultiCurrencyPriceProjectionService {
			/**
			 * Order meta candidates.
			 *
			 * @var array<string,mixed>
			 */
			public $order_meta_candidates = array();

			/**
			 * Refund meta candidates.
			 *
			 * @var array<string,mixed>
			 */
			public $refund_meta_candidates = array();

			/**
			 * Constructor.
			 */
			public function __construct() {}

			/**
			 * Project order meta candidates.
			 *
			 * @param string $order_currency Order currency code.
			 * @return array<string,mixed>
			 */
			public function get_order_meta_candidates( string $order_currency ): array {
				return $this->order_meta_candidates;
			}

			/**
			 * Project refund meta candidates.
			 *
			 * @param \WC_Order $order Order.
			 * @return array<string,mixed>
			 */
			public function get_refund_meta_candidates( \WC_Order $order ): array {
				return $this->refund_meta_candidates;
			}
		};

		$this->sut = new MultiCurrencyShadowMode();
		$this->sut->init(
			wc_get_container()->get( MultiCurrencyRuntimeArbiter::class ),
			new MultiCurrencySurfaceDiffer(),
			wc_get_container()->get( LegacyProxy::class )
		);
		$this->sut->set_projection_service( $this->projection_service );

		$this->remove_shadow_hooks();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->remove_shadow_hooks();
		remove_all_filters( MultiCurrencyShadowMode::FILTER_SHADOW_ENABLED );
		remove_all_filters( MultiCurrencyShadowMode::FILTER_LOG_FULL_SURFACES );
		remove_all_filters( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED );
		$this->delete_shadow_options();
		update_option( 'woocommerce_currency', $this->original_currency );
		$this->reset_legacy_proxy_mocks();
		parent::tearDown();
	}

	/**
	 * Remove shadow hooks for this SUT.
	 */
	private function remove_shadow_hooks(): void {
		remove_action( 'woocommerce_new_order', array( $this->sut, 'handle_woocommerce_new_order' ), 100 );
		remove_action( 'woocommerce_order_refunded', array( $this->sut, 'handle_woocommerce_order_refunded' ), 100 );
	}

	/**
	 * Control every WooPayments-plugin detection signal in a single mock registration.
	 *
	 * @param bool $active                 Whether the WooPayments plugin should appear active.
	 * @param bool $multi_currency_enabled Whether the WooPayments customer multi-currency feature is enabled.
	 */
	private function fake_plugin( bool $active, bool $multi_currency_enabled = true ): void {
		$entry = NativePaymentsRuntimeArbiter::PLUGIN_FILE;
		$this->register_legacy_proxy_function_mocks(
			array(
				'get_option'      => function ( $name, $default_value = false ) use ( $active, $entry, $multi_currency_enabled ) {
					if ( 'active_plugins' === $name ) {
						return $active ? array( $entry ) : array();
					}
					if ( '_wcpay_feature_customer_multi_currency' === $name ) {
						return $multi_currency_enabled ? '1' : '0';
					}
					return get_option( $name, $default_value );
				},
				'get_site_option' => function ( $name, $default_value = false ) {
					if ( 'active_sitewide_plugins' === $name ) {
						return array();
					}
					return get_site_option( $name, $default_value );
				},
				'class_exists'    => function ( $class_name, $autoload = true ) use ( $active ) {
					if ( 'WC_Payments' === ltrim( (string) $class_name, '\\' ) ) {
						return $active;
					}
					return class_exists( $class_name, $autoload );
				},
			)
		);
	}

	/**
	 * Register a fake WC logger.
	 *
	 * @return object
	 */
	private function fake_logger(): object {
		$logger = new class() {
			/**
			 * Logged debug entries.
			 *
			 * @var array<int,array{message:string,context:array<string,mixed>}>
			 */
			public $entries = array();

			/**
			 * Record a debug log entry.
			 *
			 * @param string              $message Log message.
			 * @param array<string,mixed> $context Log context.
			 */
			public function debug( string $message, array $context = array() ): void {
				$this->entries[] = array(
					'message' => $message,
					'context' => $context,
				);
			}
		};

		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_get_logger' => function () use ( $logger ) {
					return $logger;
				},
			)
		);

		return $logger;
	}

	/**
	 * Delete options and transients touched by shadow-mode tests.
	 */
	private function delete_shadow_options(): void {
		foreach (
			array(
				'_wcpay_feature_customer_multi_currency',
				'wcpay_multi_currency_enabled_currencies',
				'wcpay_multi_currency_exchange_rate_gbp',
				'wcpay_multi_currency_manual_rate_gbp',
				'wcpay_multi_currency_price_rounding_gbp',
				'wcpay_multi_currency_price_charm_gbp',
				'wcpay_multi_currency_stored_customer_currencies',
				MultiCurrencyCacheInterface::CURRENCIES_KEY,
			) as $option_key
		) {
			delete_option( $option_key );
		}

		delete_transient( MultiCurrencyLocalizationService::CURRENCY_FORMAT_TRANSIENT );
		delete_transient( MultiCurrencyLocalizationService::LOCALE_INFO_TRANSIENT );
	}

	/**
	 * @testdox Shadow mode registers no hooks by default.
	 */
	public function test_registers_no_hooks_by_default(): void {
		$this->fake_plugin( true );

		$this->sut->register();

		$this->assertFalse( has_action( 'woocommerce_new_order', array( $this->sut, 'handle_woocommerce_new_order' ) ) );
		$this->assertFalse( has_action( 'woocommerce_order_refunded', array( $this->sut, 'handle_woocommerce_order_refunded' ) ) );
	}

	/**
	 * @testdox WooCommerce bootstrap resolves shadow mode but registers no hooks by default.
	 */
	public function test_woocommerce_bootstrap_resolves_shadow_mode_without_default_hooks(): void {
		$this->fake_plugin( true );

		$shadow_mode = wc_get_container()->get( MultiCurrencyShadowMode::class );
		remove_action( 'woocommerce_new_order', array( $shadow_mode, 'handle_woocommerce_new_order' ), 100 );
		remove_action( 'woocommerce_order_refunded', array( $shadow_mode, 'handle_woocommerce_order_refunded' ), 100 );

		$shadow_mode->register();

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local source assertion for bootstrap registration.
		$bootstrap_source = file_get_contents( WC()->plugin_path() . '/includes/class-woocommerce.php' );

		$this->assertInstanceOf( MultiCurrencyShadowMode::class, $shadow_mode );
		$this->assertFalse( has_action( 'woocommerce_new_order', array( $shadow_mode, 'handle_woocommerce_new_order' ) ) );
		$this->assertFalse( has_action( 'woocommerce_order_refunded', array( $shadow_mode, 'handle_woocommerce_order_refunded' ) ) );
		$this->assertIsString( $bootstrap_source );
		$this->assertStringContainsString( 'Automattic\WooCommerce\Internal\MultiCurrency\Shadow\MultiCurrencyShadowMode::class )->register()', $bootstrap_source );
	}

	/**
	 * @testdox Shadow mode hooks only when enabled and plugin owns multi-currency.
	 */
	public function test_registers_hooks_only_when_enabled_and_plugin_owns_multi_currency(): void {
		$this->fake_plugin( true );
		add_filter( MultiCurrencyShadowMode::FILTER_SHADOW_ENABLED, '__return_true' );

		$this->sut->register();

		$this->assertSame( 100, has_action( 'woocommerce_new_order', array( $this->sut, 'handle_woocommerce_new_order' ) ) );
		$this->assertSame( 100, has_action( 'woocommerce_order_refunded', array( $this->sut, 'handle_woocommerce_order_refunded' ) ) );
	}

	/**
	 * @testdox Shadow mode does not hook when plugin customer multi-currency is disabled.
	 */
	public function test_does_not_register_hooks_when_plugin_multi_currency_feature_is_disabled(): void {
		$this->fake_plugin( true, false );
		add_filter( MultiCurrencyShadowMode::FILTER_SHADOW_ENABLED, '__return_true' );

		$this->sut->register();

		$this->assertFalse( has_action( 'woocommerce_new_order', array( $this->sut, 'handle_woocommerce_new_order' ) ) );
		$this->assertFalse( has_action( 'woocommerce_order_refunded', array( $this->sut, 'handle_woocommerce_order_refunded' ) ) );
	}

	/**
	 * @testdox Shadow mode does not hook after core owns multi-currency.
	 */
	public function test_does_not_register_hooks_when_core_owns_multi_currency(): void {
		$this->fake_plugin( false );
		add_filter( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED, '__return_true' );
		add_filter( MultiCurrencyShadowMode::FILTER_SHADOW_ENABLED, '__return_true' );

		$this->sut->register();

		$this->assertFalse( has_action( 'woocommerce_new_order', array( $this->sut, 'handle_woocommerce_new_order' ) ) );
		$this->assertFalse( has_action( 'woocommerce_order_refunded', array( $this->sut, 'handle_woocommerce_order_refunded' ) ) );
	}

	/**
	 * @testdox Should record order meta comparisons without mutating orders.
	 */
	public function test_records_order_meta_comparison_without_mutating_orders(): void {
		$logger = $this->fake_logger();

		$this->projection_service->order_meta_candidates = array(
			MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE    => 0.82,
			MultiCurrencyPriceProjectionService::META_KEY_ORDER_DEFAULT_CURRENCY => 'USD',
		);

		$order = wc_create_order();
		$order->set_currency( 'GBP' );
		$order->update_meta_data( MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE, '0.81' );
		$order->update_meta_data( MultiCurrencyPriceProjectionService::META_KEY_ORDER_DEFAULT_CURRENCY, 'USD' );
		$order->save();

		$before = wc_get_order( $order->get_id() )->get_meta( MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE, true );

		$comparison = $this->sut->record_order_shadow( wc_get_order( $order->get_id() ), 'unit_test' );
		$after      = wc_get_order( $order->get_id() )->get_meta( MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE, true );

		$this->assertInstanceOf( MultiCurrencyShadowComparison::class, $comparison );
		$this->assertSame( 'order', $comparison->get_subject_type() );
		$this->assertSame( $order->get_id(), $comparison->get_subject_id() );
		$this->assertSame( $before, $after );
		$this->assertSame( '0.82', $comparison->get_native_computed()['meta'][ MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE ] );
		$this->assertSame( '0.81', $comparison->get_actual()['meta'][ MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE ] );
		$this->assertArrayHasKey( 'meta.' . MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE, $comparison->get_diff() );
		$this->assertCount( 1, $logger->entries );
		$this->assertSame( MultiCurrencyShadowMode::LOG_SOURCE, $logger->entries[0]['context']['source'] );

		$payload = json_decode( $logger->entries[0]['message'], true );

		$this->assertIsArray( $payload );
		$this->assertSame( MultiCurrencyShadowComparison::COMPARISON_TYPE_ORDER_META, $payload['comparison_type'] );
		$this->assertTrue( $payload['independent_native_computation'] );
		$this->assertTrue( $payload['has_diff'] );
		$this->assertArrayNotHasKey( 'actual', $payload );
		$this->assertArrayNotHasKey( 'native_computed', $payload );
	}

	/**
	 * @testdox Default projection graph records without writing cache or session state.
	 */
	public function test_default_projection_graph_records_without_writing_cache_or_session_state(): void {
		$logger           = $this->fake_logger();
		$original_session = WC()->session;
		$session          = new class() {
			/**
			 * Number of session writes.
			 *
			 * @var int
			 */
			public $writes = 0;

			/**
			 * Get a session value.
			 *
			 * @param string $key Session key.
			 * @return string|null
			 */
			public function get( string $key ) {
				if ( MultiCurrencyStateBuilder::CURRENCY_STORAGE_KEY === $key ) {
					return 'GBP';
				}

				return null;
			}

			/**
			 * Set a session value.
			 *
			 * @param string $key   Session key.
			 * @param mixed  $value Session value.
			 */
			public function set( string $key, $value ): void {
				unset( $key, $value );
				++$this->writes;
			}
		};

		$this->delete_shadow_options();
		update_option( 'woocommerce_currency', 'USD' );
		update_option( 'wcpay_multi_currency_enabled_currencies', array( 'GBP' ) );
		update_option( 'wcpay_multi_currency_exchange_rate_gbp', 'manual' );
		update_option( 'wcpay_multi_currency_manual_rate_gbp', '0.82' );

		$order = wc_create_order();
		$order->set_currency( 'GBP' );
		$order->update_meta_data( MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE, '0.82' );
		$order->update_meta_data( MultiCurrencyPriceProjectionService::META_KEY_ORDER_DEFAULT_CURRENCY, 'USD' );
		$order->save();

		$sut = wc_get_container()->get( MultiCurrencyShadowMode::class );

		WC()->session = $session;
		try {
			$comparison = $sut->record_order_shadow( wc_get_order( $order->get_id() ), 'unit_test' );
		} finally {
			WC()->session = $original_session;
		}

		$this->assertInstanceOf( MultiCurrencyShadowComparison::class, $comparison );
		$this->assertSame( array(), $comparison->get_diff() );
		$this->assertSame( 0, $session->writes );
		$this->assertFalse( get_option( MultiCurrencyCacheInterface::CURRENCIES_KEY, false ) );
		$this->assertFalse( get_transient( MultiCurrencyLocalizationService::CURRENCY_FORMAT_TRANSIENT ) );
		$this->assertFalse( get_transient( MultiCurrencyLocalizationService::LOCALE_INFO_TRANSIENT ) );
		$this->assertCount( 1, $logger->entries );
	}

	/**
	 * @testdox Should record refund meta comparisons without mutating refunds.
	 */
	public function test_records_refund_meta_comparison_without_mutating_refunds(): void {
		$logger = $this->fake_logger();

		$this->projection_service->refund_meta_candidates = array(
			MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE    => '0.82',
			MultiCurrencyPriceProjectionService::META_KEY_ORDER_DEFAULT_CURRENCY => 'USD',
		);

		$order = wc_create_order();
		$order->set_currency( 'GBP' );
		$order->set_total( 1 );
		$order->save();

		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 1,
			)
		);
		$this->assertInstanceOf( WC_Order_Refund::class, $refund );

		$refund->update_meta_data( MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE, '0.81' );
		$refund->update_meta_data( MultiCurrencyPriceProjectionService::META_KEY_ORDER_DEFAULT_CURRENCY, 'USD' );
		$refund->save();

		$before = wc_get_order( $refund->get_id() )->get_meta( MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE, true );

		$comparison = $this->sut->record_refund_shadow( wc_get_order( $order->get_id() ), wc_get_order( $refund->get_id() ), 'unit_test' );
		$after      = wc_get_order( $refund->get_id() )->get_meta( MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE, true );

		$this->assertInstanceOf( MultiCurrencyShadowComparison::class, $comparison );
		$this->assertSame( 'refund', $comparison->get_subject_type() );
		$this->assertSame( $refund->get_id(), $comparison->get_subject_id() );
		$this->assertSame( $before, $after );
		$this->assertSame( '0.82', $comparison->get_native_computed()['meta'][ MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE ] );
		$this->assertSame( '0.81', $comparison->get_actual()['meta'][ MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE ] );
		$this->assertArrayHasKey( 'meta.' . MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE, $comparison->get_diff() );
		$this->assertCount( 1, $logger->entries );
		$this->assertSame( MultiCurrencyShadowMode::LOG_SOURCE, $logger->entries[0]['context']['source'] );
	}

	/**
	 * @testdox Full shadow surfaces are logged only when the diagnostic filter is enabled.
	 */
	public function test_full_shadow_surfaces_are_logged_only_when_diagnostic_filter_is_enabled(): void {
		add_filter( MultiCurrencyShadowMode::FILTER_LOG_FULL_SURFACES, '__return_true' );

		$logger = $this->fake_logger();

		$this->projection_service->order_meta_candidates = array(
			MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE    => '0.81',
			MultiCurrencyPriceProjectionService::META_KEY_ORDER_DEFAULT_CURRENCY => 'USD',
		);

		$order = wc_create_order();
		$order->set_currency( 'GBP' );
		$order->update_meta_data( MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE, '0.81' );
		$order->update_meta_data( MultiCurrencyPriceProjectionService::META_KEY_ORDER_DEFAULT_CURRENCY, 'USD' );
		$order->save();

		$this->sut->record_order_shadow( wc_get_order( $order->get_id() ), 'unit_test' );

		$payload = json_decode( $logger->entries[0]['message'], true );

		$this->assertIsArray( $payload );
		$this->assertSame( '0.81', $payload['actual']['meta'][ MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE ] );
		$this->assertSame( '0.81', $payload['native_computed']['meta'][ MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE ] );
	}
}
