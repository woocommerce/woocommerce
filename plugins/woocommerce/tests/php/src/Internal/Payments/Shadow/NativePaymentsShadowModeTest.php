<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Shadow;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\Shadow\NativePaymentsShadowMode;
use Automattic\WooCommerce\Internal\Payments\Shadow\PaymentSurfaceDiffer;
use Automattic\WooCommerce\Internal\Payments\Shadow\ShadowComparison;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WC_Unit_Test_Case;

/**
 * Tests for the NativePaymentsShadowMode class.
 */
class NativePaymentsShadowModeTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var NativePaymentsShadowMode
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( NativePaymentsShadowMode::class );
		$this->remove_shadow_hooks();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->remove_shadow_hooks();
		remove_all_filters( NativePaymentsShadowMode::FILTER_SHADOW_ENABLED );
		remove_all_filters( NativePaymentsShadowMode::FILTER_LOG_FULL_SURFACES );
		remove_all_filters( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED );
		$this->reset_legacy_proxy_mocks();
		parent::tearDown();
	}

	/**
	 * Remove shadow hooks for this SUT.
	 */
	private function remove_shadow_hooks(): void {
		remove_action( 'woocommerce_payment_complete', array( $this->sut, 'handle_woocommerce_payment_complete' ), 100 );
		remove_action( 'woocommerce_order_refunded', array( $this->sut, 'handle_woocommerce_order_refunded' ), 100 );
	}

	/**
	 * Control every WooPayments-plugin detection signal in a single mock registration.
	 *
	 * @param bool $active Whether the WooPayments plugin should appear active.
	 */
	private function fake_plugin( bool $active ): void {
		$entry = NativePaymentsRuntimeArbiter::PLUGIN_FILE;
		$this->register_legacy_proxy_function_mocks(
			array(
				'get_option'      => function ( $name, $default_value = false ) use ( $active, $entry ) {
					if ( 'active_plugins' === $name ) {
						return $active ? array( $entry ) : array();
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
	 * @testdox Shadow mode registers no hooks by default.
	 */
	public function test_registers_no_hooks_by_default(): void {
		$this->fake_plugin( true );

		$this->sut->register();

		$this->assertFalse( has_action( 'woocommerce_payment_complete', array( $this->sut, 'handle_woocommerce_payment_complete' ) ) );
		$this->assertFalse( has_action( 'woocommerce_order_refunded', array( $this->sut, 'handle_woocommerce_order_refunded' ) ) );
	}

	/**
	 * @testdox Shadow mode hooks only when explicitly enabled while the plugin owns the runtime.
	 */
	public function test_registers_hooks_only_when_enabled_and_plugin_owns_runtime(): void {
		$this->fake_plugin( true );
		add_filter( NativePaymentsShadowMode::FILTER_SHADOW_ENABLED, '__return_true' );

		$this->sut->register();

		$this->assertSame( 100, has_action( 'woocommerce_payment_complete', array( $this->sut, 'handle_woocommerce_payment_complete' ) ) );
		$this->assertSame( 100, has_action( 'woocommerce_order_refunded', array( $this->sut, 'handle_woocommerce_order_refunded' ) ) );
	}

	/**
	 * @testdox Shadow mode does not hook after the native runtime takes ownership.
	 */
	public function test_does_not_register_hooks_when_native_owns_runtime(): void {
		$this->fake_plugin( false );
		add_filter( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED, '__return_true' );
		add_filter( NativePaymentsShadowMode::FILTER_SHADOW_ENABLED, '__return_true' );

		$this->sut->register();

		$this->assertFalse( has_action( 'woocommerce_payment_complete', array( $this->sut, 'handle_woocommerce_payment_complete' ) ) );
		$this->assertFalse( has_action( 'woocommerce_order_refunded', array( $this->sut, 'handle_woocommerce_order_refunded' ) ) );
	}

	/**
	 * @testdox Shadow comparisons are logged out-of-band without mutating order payment state.
	 */
	public function test_records_same_store_shadow_comparison_without_mutating_order_payment_state(): void {
		$logger = new class() {
			/**
			 * Logged debug entries.
			 *
			 * @var array
			 */
			public $entries = array();

			/**
			 * Record a debug log entry.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
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

		$order = wc_create_order();
		$order->set_payment_method( OrderPaymentStore::GATEWAY_ID );
		$order->update_meta_data( '_intent_id', 'pi_123' );
		$order->save();

		$before = wc_get_order( $order->get_id() )->get_meta( '_intent_id', true );

		$comparison = $this->sut->record_shadow_for_order( wc_get_order( $order->get_id() ), 'unit_test' );

		$after = wc_get_order( $order->get_id() )->get_meta( '_intent_id', true );

		$this->assertInstanceOf( ShadowComparison::class, $comparison );
		$this->assertSame( $order->get_id(), $comparison->get_order_id() );
		$this->assertSame( 'unit_test', $comparison->get_trigger() );
		$this->assertSame( array(), $comparison->get_diff() );
		$this->assertSame( $comparison->get_actual(), $comparison->get_native_computed() );
		$this->assertSame( $before, $after );
		$this->assertCount( 1, $logger->entries );
		$this->assertSame( NativePaymentsShadowMode::LOG_SOURCE, $logger->entries[0]['context']['source'] );
		$this->assertStringContainsString( '"trigger":"unit_test"', $logger->entries[0]['message'] );

		$payload = json_decode( $logger->entries[0]['message'], true );

		$this->assertIsArray( $payload );
		$this->assertSame( 'unit_test', $payload['trigger'] );
		$this->assertSame( ShadowComparison::COMPARISON_TYPE_A1_PROJECTION_BASELINE, $payload['comparison_type'] );
		$this->assertFalse( $payload['independent_native_computation'] );
		$this->assertFalse( $payload['has_diff'] );
		$this->assertArrayHasKey( 'actual_hash', $payload );
		$this->assertArrayHasKey( 'native_computed_hash', $payload );
		$this->assertArrayNotHasKey( 'actual', $payload );
		$this->assertArrayNotHasKey( 'native_computed', $payload );
	}

	/**
	 * @testdox Full shadow surfaces are logged only when the diagnostic filter is enabled.
	 */
	public function test_full_shadow_surfaces_are_logged_only_when_diagnostic_filter_is_enabled(): void {
		add_filter( NativePaymentsShadowMode::FILTER_LOG_FULL_SURFACES, '__return_true' );

		$logger = new class() {
			/**
			 * Logged debug entries.
			 *
			 * @var array
			 */
			public $entries = array();

			/**
			 * Record a debug log entry.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
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

		$order = wc_create_order();
		$order->set_payment_method( OrderPaymentStore::GATEWAY_ID );
		$order->update_meta_data( '_intent_id', 'pi_123' );
		$order->save();

		$this->sut->record_shadow_for_order( wc_get_order( $order->get_id() ), 'unit_test' );

		$payload = json_decode( $logger->entries[0]['message'], true );

		$this->assertIsArray( $payload );
		$this->assertSame( ShadowComparison::COMPARISON_TYPE_A1_PROJECTION_BASELINE, $payload['comparison_type'] );
		$this->assertFalse( $payload['independent_native_computation'] );
		$this->assertSame( 'pi_123', $payload['actual']['meta']['_intent_id'] );
		$this->assertSame( 'pi_123', $payload['native_computed']['meta']['_intent_id'] );
	}

	/**
	 * @testdox Shadow mode ignores non-WooPayments orders.
	 */
	public function test_shadow_mode_ignores_non_woopayments_orders(): void {
		$logger = new class() {
			/**
			 * Logged debug entries.
			 *
			 * @var array
			 */
			public $entries = array();

			/**
			 * Record a debug log entry.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
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

		$order = wc_create_order();
		$order->set_payment_method( 'cheque' );
		$order->save();

		$this->assertNull( $this->sut->record_shadow_for_order( wc_get_order( $order->get_id() ), 'unit_test' ) );

		$this->sut->handle_woocommerce_payment_complete( $order->get_id() );

		$this->assertSame( array(), $logger->entries );
	}

	/**
	 * @testdox A1 native computation reuses the first payment surface projection.
	 */
	public function test_a1_native_computation_reuses_first_payment_surface_projection(): void {
		$logger = new class() {
			/**
			 * Record a debug log entry.
			 *
			 * @param string $message Log message.
			 * @param array  $context Log context.
			 */
			public function debug( string $message, array $context = array() ): void {}
		};

		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_get_logger' => function () use ( $logger ) {
					return $logger;
				},
			)
		);

		$store = new class() extends OrderPaymentStore {
			/**
			 * Number of read_payment_surface calls.
			 *
			 * @var int
			 */
			public $reads = 0;

			/**
			 * Read a stable, HPOS-safe projection of an order's payment surface.
			 *
			 * @param \WC_Order $order Order to project.
			 * @return array<string,mixed>
			 */
			public function read_payment_surface( \WC_Order $order ): array {
				++$this->reads;

				return parent::read_payment_surface( $order );
			}
		};

		$sut = new NativePaymentsShadowMode();
		$sut->init(
			wc_get_container()->get( NativePaymentsRuntimeArbiter::class ),
			$store,
			new PaymentSurfaceDiffer(),
			wc_get_container()->get( LegacyProxy::class )
		);

		$order = wc_create_order();
		$order->set_payment_method( OrderPaymentStore::GATEWAY_ID );
		$order->update_meta_data( '_intent_id', 'pi_123' );
		$order->save();

		$sut->record_shadow_for_order( wc_get_order( $order->get_id() ), 'unit_test' );

		$this->assertSame( 1, $store->reads );
	}
}
