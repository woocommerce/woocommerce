<?php
/**
 * NativePaymentsShadowMode class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Shadow;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WC_Order;

/**
 * Records same-store native shadow output while the WooPayments plugin owns processing.
 *
 * A1 shadow mode is intentionally read-only: it observes after plugin-owned hooks have run, reads
 * the persisted payment surface, computes the native A1 projection, and logs a machine-readable
 * comparison outside the order. It must not save orders, create refunds, add notes, or call provider
 * mutation APIs.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class NativePaymentsShadowMode implements RegisterHooksInterface {

	/**
	 * Filter that enables read-only native shadow mode while the plugin owns processing.
	 *
	 * @var string
	 */
	const FILTER_SHADOW_ENABLED = 'woocommerce_native_payments_shadow_mode_enabled';

	/**
	 * Filter that enables full actual/native-computed surfaces in shadow logs.
	 *
	 * @var string
	 */
	const FILTER_LOG_FULL_SURFACES = 'woocommerce_native_payments_shadow_mode_log_full_surfaces';

	/**
	 * WC logger source for machine-readable shadow comparison records.
	 *
	 * @var string
	 */
	const LOG_SOURCE = 'native-payments-shadow';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $arbiter;

	/**
	 * Order payment projection store.
	 *
	 * @var OrderPaymentStore
	 */
	private OrderPaymentStore $order_payment_store;

	/**
	 * Payment-surface differ.
	 *
	 * @var PaymentSurfaceDiffer
	 */
	private PaymentSurfaceDiffer $differ;

	/**
	 * Legacy proxy for mockable global calls.
	 *
	 * @var LegacyProxy
	 */
	private LegacyProxy $legacy_proxy;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter $arbiter             Runtime owner arbiter.
	 * @param OrderPaymentStore            $order_payment_store Order payment projection store.
	 * @param PaymentSurfaceDiffer         $differ              Payment-surface differ.
	 * @param LegacyProxy                  $legacy_proxy        Legacy proxy.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter, OrderPaymentStore $order_payment_store, PaymentSurfaceDiffer $differ, LegacyProxy $legacy_proxy ): void {
		$this->arbiter             = $arbiter;
		$this->order_payment_store = $order_payment_store;
		$this->differ              = $differ;
		$this->legacy_proxy        = $legacy_proxy;
	}

	/**
	 * Register read-only shadow hooks.
	 *
	 * Shadow hooks are allowed only while the WooPayments plugin owns processing and the explicit
	 * shadow flag is enabled. This deliberately does not use should_native_register(), which remains
	 * false in the plugin-owned state.
	 */
	public function register() {
		if ( ! $this->should_register_shadow_hooks() ) {
			return;
		}

		$this->add_shadow_action_once( 'woocommerce_payment_complete', array( $this, 'handle_woocommerce_payment_complete' ), 100, 1 );
		$this->add_shadow_action_once( 'woocommerce_order_refunded', array( $this, 'handle_woocommerce_order_refunded' ), 100, 2 );
	}

	/**
	 * Tell whether shadow mode should register hooks.
	 *
	 * @return bool
	 */
	public function should_register_shadow_hooks(): bool {
		return $this->is_shadow_mode_enabled() && $this->arbiter->is_plugin_runtime_active();
	}

	/**
	 * Tell whether shadow mode is enabled.
	 *
	 * @return bool
	 */
	public function is_shadow_mode_enabled(): bool {
		/**
		 * Filters whether read-only native shadow mode is enabled.
		 *
		 * @since 11.0.0
		 *
		 * @param bool $enabled Whether shadow mode is enabled. Default false.
		 */
		return (bool) apply_filters( self::FILTER_SHADOW_ENABLED, false );
	}

	/**
	 * Observe the WooCommerce payment-complete hook after plugin-owned effects.
	 *
	 * @param int $order_id Order ID.
	 */
	public function handle_woocommerce_payment_complete( int $order_id ): void {
		$order = $this->legacy_proxy->call_function( 'wc_get_order', $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( ! $this->is_woopayments_order( $order ) ) {
			return;
		}

		$this->record_shadow_for_order( $order, 'woocommerce_payment_complete' );
	}

	/**
	 * Observe the WooCommerce order-refunded hook after plugin-owned effects.
	 *
	 * @param int $order_id  Order ID.
	 * @param int $refund_id Refund ID.
	 */
	public function handle_woocommerce_order_refunded( int $order_id, int $refund_id ): void {
		$order = $this->legacy_proxy->call_function( 'wc_get_order', $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( ! $this->is_woopayments_order( $order ) ) {
			return;
		}

		$this->record_shadow_for_order( $order, 'woocommerce_order_refunded' );
	}

	/**
	 * Record a same-store shadow comparison for an order.
	 *
	 * @param WC_Order $order   Order object.
	 * @param string   $trigger Trigger name.
	 * @return ShadowComparison|null Shadow comparison, or null for non-WooPayments orders.
	 */
	public function record_shadow_for_order( WC_Order $order, string $trigger ): ?ShadowComparison {
		if ( ! $this->is_woopayments_order( $order ) ) {
			return null;
		}

		$start           = microtime( true );
		$actual          = $this->order_payment_store->read_payment_surface( $order );
		$native_computed = $this->compute_a1_projection_baseline( $order, $actual );
		$diff            = $this->differ->diff( $native_computed, $actual );
		$elapsed_ms      = ( microtime( true ) - $start ) * 1000;

		$comparison = new ShadowComparison( $trigger, (int) $order->get_id(), $actual, $native_computed, $diff, $elapsed_ms );
		$this->log_comparison( $comparison );

		return $comparison;
	}

	/**
	 * Compute the A1 projection baseline.
	 *
	 * In A1 this deliberately mirrors the read projection and is labeled as a baseline snapshot in
	 * the log payload. Later stages replace this with native lifecycle/event/processing effect
	 * computation and keep this shadow output shape.
	 *
	 * @param WC_Order            $order          Order object.
	 * @param array<string,mixed> $actual_surface Already-read persisted payment surface.
	 * @return array<string,mixed>
	 */
	private function compute_a1_projection_baseline( WC_Order $order, array $actual_surface ): array {
		return $actual_surface;
	}

	/**
	 * Tell whether an order belongs to WooPayments.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool True for the main WooPayments gateway and split-UPE WooPayments gateway IDs.
	 */
	private function is_woopayments_order( WC_Order $order ): bool {
		$payment_method = (string) $order->get_payment_method();

		return OrderPaymentStore::GATEWAY_ID === $payment_method || 0 === strpos( $payment_method, OrderPaymentStore::GATEWAY_ID_PREFIX );
	}

	/**
	 * Log a machine-readable shadow comparison out of band.
	 *
	 * @param ShadowComparison $comparison Shadow comparison.
	 */
	private function log_comparison( ShadowComparison $comparison ): void {
		$logger = $this->legacy_proxy->call_function( 'wc_get_logger' );
		if ( ! is_object( $logger ) || ! is_callable( array( $logger, 'debug' ) ) ) {
			return;
		}

		/**
		 * Filters whether shadow logs include full actual/native-computed surfaces.
		 *
		 * Defaults to false so production canaries record compact diffs and surface hashes rather
		 * than duplicating full order/refund payment surfaces on every observed event.
		 *
		 * @since 11.0.0
		 *
		 * @param bool             $include_surfaces Whether to include full surfaces. Default false.
		 * @param ShadowComparison $comparison       Shadow comparison.
		 */
		$include_surfaces = (bool) apply_filters( self::FILTER_LOG_FULL_SURFACES, false, $comparison );

		$message = wp_json_encode( $comparison->to_log_array( $include_surfaces ) );
		if ( false === $message ) {
			return;
		}

		$logger->debug(
			$message,
			array(
				'source' => self::LOG_SOURCE,
			)
		);
	}

	/**
	 * Register an action only once for this controller instance.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Hook callback.
	 * @param int      $priority      Hook priority.
	 * @param int      $accepted_args Accepted argument count.
	 */
	private function add_shadow_action_once( string $hook, callable $callback, int $priority, int $accepted_args ): void {
		if ( false === has_action( $hook, $callback ) ) {
			add_action( $hook, $callback, $priority, $accepted_args );
		}
	}
}
