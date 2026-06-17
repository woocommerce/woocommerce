<?php
/**
 * WooPaymentsOrderTrackingService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Order;

/**
 * Native owner for WooPayments-compatible order tracking queue hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsOrderTrackingService implements RegisterHooksInterface {

	/**
	 * Filters the native WooPayments fraud services config used by order tracking.
	 *
	 * @var string
	 */
	const FILTER_FRAUD_SERVICES_CONFIG = 'woocommerce_woopayments_native_fraud_services_config';

	/**
	 * Preserved WooPayments new-order tracking hook.
	 *
	 * @var string
	 */
	const TRACK_NEW_ORDER_ACTION = 'wcpay_track_new_order';

	/**
	 * Preserved WooPayments update-order tracking hook.
	 *
	 * @var string
	 */
	const TRACK_UPDATE_ORDER_ACTION = 'wcpay_track_update_order';

	/**
	 * Preserved order meta marker for completed creation tracking.
	 *
	 * @var string
	 */
	const NEW_ORDER_TRACKING_COMPLETE_META_KEY = '_new_order_tracking_complete';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $arbiter;

	/**
	 * Action Scheduler service.
	 *
	 * @var WooPaymentsActionSchedulerService
	 */
	private WooPaymentsActionSchedulerService $scheduler;

	/**
	 * WooPayments API client.
	 *
	 * @var WooPaymentsApiClient
	 */
	private WooPaymentsApiClient $api_client;

	/**
	 * WooPayments account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter      $arbiter         Runtime owner arbiter.
	 * @param WooPaymentsActionSchedulerService $scheduler       Action Scheduler service.
	 * @param WooPaymentsApiClient              $api_client      WooPayments API client.
	 * @param WooPaymentsAccountService         $account_service WooPayments account service.
	 */
	final public function init(
		NativePaymentsRuntimeArbiter $arbiter,
		WooPaymentsActionSchedulerService $scheduler,
		WooPaymentsApiClient $api_client,
		WooPaymentsAccountService $account_service
	): void {
		$this->arbiter         = $arbiter;
		$this->scheduler       = $scheduler;
		$this->api_client      = $api_client;
		$this->account_service = $account_service;
	}

	/**
	 * Register preserved order tracking queue producers and consumers.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() ) {
			return;
		}

		add_action( 'woocommerce_update_order', array( $this, 'handle_woocommerce_update_order' ), 10, 2 );
		add_action( self::TRACK_NEW_ORDER_ACTION, array( $this, 'handle_wcpay_track_new_order' ), 10, 1 );
		add_action( self::TRACK_UPDATE_ORDER_ACTION, array( $this, 'handle_wcpay_track_update_order' ), 10, 1 );
	}

	/**
	 * Handle the woocommerce_update_order hook.
	 *
	 * @internal
	 *
	 * @param int           $order_id Order ID.
	 * @param WC_Order|null $order    Order object.
	 */
	public function handle_woocommerce_update_order( $order_id, $order = null ): void {
		if ( doing_action( self::TRACK_NEW_ORDER_ACTION ) || doing_action( self::TRACK_UPDATE_ORDER_ACTION ) ) {
			return;
		}

		if ( ! $this->is_sift_tracking_enabled() ) {
			return;
		}

		$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( ! $this->is_woopayments_payment_method( $order->get_payment_method() ) ) {
			return;
		}

		if ( '' === $this->get_order_meta_string( $order, '_payment_method_id' ) ) {
			return;
		}

		$this->scheduler->schedule_job(
			'yes' === $this->get_order_meta_string( $order, self::NEW_ORDER_TRACKING_COMPLETE_META_KEY )
				? self::TRACK_UPDATE_ORDER_ACTION
				: self::TRACK_NEW_ORDER_ACTION,
			array(
				'order_id' => (int) $order->get_id(),
			)
		);
	}

	/**
	 * Handle the preserved wcpay_track_new_order action.
	 *
	 * @internal
	 *
	 * @param int $order_id Order ID.
	 */
	public function handle_wcpay_track_new_order( $order_id ): void {
		$this->track_new_order_action( (int) $order_id );
	}

	/**
	 * Handle the preserved wcpay_track_update_order action.
	 *
	 * @internal
	 *
	 * @param int $order_id Order ID.
	 */
	public function handle_wcpay_track_update_order( $order_id ): void {
		$this->track_update_order_action( (int) $order_id );
	}

	/**
	 * Track a new order through the preserved action handler.
	 *
	 * @internal
	 *
	 * @param int $order_id Order ID.
	 * @return bool True when tracking succeeded.
	 */
	public function track_new_order_action( $order_id ): bool {
		return $this->track_order( (int) $order_id, false );
	}

	/**
	 * Track an order update through the preserved action handler.
	 *
	 * @internal
	 *
	 * @param int $order_id Order ID.
	 * @return bool True when tracking succeeded.
	 */
	public function track_update_order_action( $order_id ): bool {
		return $this->track_order( (int) $order_id, true );
	}

	/**
	 * Track an order through the WooPayments API.
	 *
	 * @param int  $order_id  Order ID.
	 * @param bool $is_update Whether this is an update event.
	 * @return bool True when tracking succeeded.
	 */
	private function track_order( int $order_id, bool $is_update ): bool {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		$payment_method_id = $this->get_order_meta_string( $order, '_payment_method_id' );
		if ( '' === $payment_method_id ) {
			return false;
		}

		$order_mode = $this->get_order_meta_string( $order, '_wcpay_mode' );
		if ( '' !== $order_mode && ! $this->is_order_mode_compatible( $order_mode ) ) {
			return false;
		}

		$response = $this->api_client->track_order(
			$this->get_order_tracking_data( $order, $payment_method_id, $order_mode ),
			$is_update
		);
		$success  = 'success' === ( $response['result'] ?? null );

		if ( $success && ! $is_update ) {
			$order->update_meta_data( self::NEW_ORDER_TRACKING_COMPLETE_META_KEY, 'yes' );
			$order->save_meta_data();
		}

		return $success;
	}

	/**
	 * Build the WooPayments-compatible order tracking payload.
	 *
	 * @param WC_Order $order             Order object.
	 * @param string   $payment_method_id Provider payment method ID.
	 * @param string   $order_mode        Persisted WooPayments mode.
	 * @return array<string,mixed>
	 */
	private function get_order_tracking_data( WC_Order $order, string $payment_method_id, string $order_mode ): array {
		return array_merge(
			$order->get_data(),
			array(
				'_payment_method_id'  => $payment_method_id,
				'_stripe_customer_id' => $this->get_order_meta_string( $order, '_stripe_customer_id' ),
				'_wcpay_mode'         => $order_mode,
			)
		);
	}

	/**
	 * Tell whether the order mode matches the current WooPayments mode.
	 *
	 * @param string $order_mode Persisted order mode.
	 * @return bool
	 */
	private function is_order_mode_compatible( string $order_mode ): bool {
		if ( $this->account_service->is_test_mode_enabled() ) {
			return 'test' === $order_mode;
		}

		return in_array( $order_mode, array( 'prod', 'live' ), true );
	}

	/**
	 * Tell whether a payment method belongs to WooPayments.
	 *
	 * @param string $payment_method Payment method ID.
	 * @return bool
	 */
	private function is_woopayments_payment_method( string $payment_method ): bool {
		return OrderPaymentStore::GATEWAY_ID === $payment_method || 0 === strpos( $payment_method, OrderPaymentStore::GATEWAY_ID_PREFIX );
	}

	/**
	 * Tell whether Sift order tracking is enabled.
	 *
	 * @return bool
	 */
	private function is_sift_tracking_enabled(): bool {
		/**
		 * Filters native WooPayments fraud services config.
		 *
		 * This mirrors the standalone plugin's Sift gate while native fraud settings are absorbed into Core.
		 *
		 * @since 11.0.0
		 *
		 * @param array<string,mixed> $config Fraud services config.
		 */
		$config = apply_filters( self::FILTER_FRAUD_SERVICES_CONFIG, array( 'sift' => array() ) );

		return is_array( $config ) && array_key_exists( 'sift', $config );
	}

	/**
	 * Read scalar order meta as a string.
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $key   Meta key.
	 * @return string
	 */
	private function get_order_meta_string( WC_Order $order, string $key ): string {
		$value = $order->get_meta( $key, true );

		return is_scalar( $value ) ? (string) $value : '';
	}
}
