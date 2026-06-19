<?php
/**
 * WooPaymentsPaymentDetailsRestController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Order;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Native WooPayments payment detail REST controller.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsPaymentDetailsRestController implements RegisterHooksInterface {

	private const NAMESPACE                  = 'wc/v3';
	private const EVENT_FRAUD_OUTCOME_REVIEW = 'fraud_outcome_review';
	private const EVENT_FRAUD_OUTCOME_BLOCK  = 'fraud_outcome_block';
	private const EVENTS_ORDER               = array(
		'authorized',
		'authorization_voided',
		'authorization_expired',
		self::EVENT_FRAUD_OUTCOME_REVIEW,
		self::EVENT_FRAUD_OUTCOME_BLOCK,
		'captured',
		'partial_refund',
		'full_refund',
		'refund_failed',
		'failed',
		'dispute_needs_response',
		'dispute_in_review',
		'dispute_won',
		'dispute_lost',
		'financing_paydown',
	);

	/**
	 * Runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $arbiter;

	/**
	 * Native WooPayments API client.
	 *
	 * @var WooPaymentsApiClient
	 */
	private WooPaymentsApiClient $api_client;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter $arbiter    Runtime owner arbiter.
	 * @param WooPaymentsApiClient         $api_client Native WooPayments API client.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter, WooPaymentsApiClient $api_client ): void {
		$this->arbiter    = $arbiter;
		$this->api_client = $api_client;
	}

	/**
	 * Register REST hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() ) {
			return;
		}

		if ( false === has_action( 'rest_api_init', array( $this, 'register_routes' ) ) ) {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}
	}

	/**
	 * Register WooPayments-compatible payment detail routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/payments/charges/(?P<charge_id>\w+)', $this->get_readable_route( 'get_charge' ) );
		register_rest_route( self::NAMESPACE, '/payments/payment_intents/(?P<payment_intent_id>\w+)', $this->get_readable_route( 'get_payment_intent' ) );
		register_rest_route( self::NAMESPACE, '/payments/timeline/(?P<intention_id>\w+)', $this->get_readable_route( 'get_timeline' ) );
	}

	/**
	 * Check route permissions.
	 *
	 * @return bool
	 */
	public function check_permission(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Get a charge.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_charge( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response( $this->api_client->get_charge( (string) $request->get_param( 'charge_id' ) ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get a payment intent.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_payment_intent( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response( $this->api_client->get_payment_intention( (string) $request->get_param( 'payment_intent_id' ) ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get timeline events for a payment intent or order identifier.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_timeline( WP_REST_Request $request ) {
		try {
			$intention_id = (string) $request->get_param( 'intention_id' );
			$timeline     = $this->api_client->get_timeline( $intention_id );

			return new WP_REST_Response( $this->add_manual_fraud_outcome_entry( $timeline, $intention_id ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Add a locally persisted manual fraud outcome event to platform review timelines.
	 *
	 * @param array<string,mixed> $timeline Timeline response.
	 * @param string              $intention_id Payment intent ID or order ID.
	 * @return array<string,mixed>
	 */
	private function add_manual_fraud_outcome_entry( array $timeline, string $intention_id ): array {
		if ( ! $this->timeline_has_fraud_outcome_event( $timeline ) ) {
			return $timeline;
		}

		$order = $this->get_order_for_timeline( $intention_id );
		if ( ! $order instanceof WC_Order ) {
			return $timeline;
		}

		$manual_entry = $order->get_meta( '_wcpay_fraud_outcome_manual_entry', true );
		if ( ! is_array( $manual_entry ) || empty( $manual_entry ) ) {
			return $timeline;
		}

		$events   = isset( $timeline['data'] ) && is_array( $timeline['data'] ) ? $timeline['data'] : array();
		$events[] = $manual_entry;
		usort( $events, array( $this, 'sort_timeline_events' ) );

		$timeline['data'] = $events;

		return $timeline;
	}

	/**
	 * Check whether platform timeline data contains fraud outcome events.
	 *
	 * @param array<string,mixed> $timeline Timeline response.
	 * @return bool
	 */
	private function timeline_has_fraud_outcome_event( array $timeline ): bool {
		if ( empty( $timeline['data'] ) || ! is_array( $timeline['data'] ) ) {
			return false;
		}

		foreach ( $timeline['data'] as $event ) {
			if ( ! is_array( $event ) ) {
				continue;
			}

			$type = $event['type'] ?? '';
			if ( is_string( $type ) && in_array( $type, array( self::EVENT_FRAUD_OUTCOME_REVIEW, self::EVENT_FRAUD_OUTCOME_BLOCK ), true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the WooCommerce order represented by a timeline ID.
	 *
	 * @param string $intention_id Payment intent ID or order ID.
	 * @return WC_Order|null
	 */
	private function get_order_for_timeline( string $intention_id ): ?WC_Order {
		if ( is_numeric( $intention_id ) ) {
			$order = wc_get_order( (int) $intention_id );
			return $order instanceof WC_Order ? $order : null;
		}

		$order = $this->get_order_by_intention_id( $intention_id );
		if ( $order instanceof WC_Order ) {
			return $order;
		}

		$order_id = $this->get_order_id_from_intention( $intention_id );
		if ( $order_id <= 0 ) {
			return null;
		}

		$order = wc_get_order( $order_id );
		return $order instanceof WC_Order ? $order : null;
	}

	/**
	 * Get an order by the persisted native payment intent meta.
	 *
	 * @param string $intention_id Payment intent ID.
	 * @return WC_Order|null
	 */
	private function get_order_by_intention_id( string $intention_id ): ?WC_Order {
		$orders = wc_get_orders(
			array(
				'limit'      => 1,
				'meta_key'   => '_intent_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $intention_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'return'     => 'objects',
			)
		);

		if ( ! is_array( $orders ) ) {
			return null;
		}

		$order = reset( $orders );
		return $order instanceof WC_Order ? $order : null;
	}

	/**
	 * Get an order ID from the platform payment intent metadata.
	 *
	 * @param string $intention_id Payment intent ID.
	 * @return int
	 */
	private function get_order_id_from_intention( string $intention_id ): int {
		try {
			$intention = $this->api_client->get_payment_intention( $intention_id );
		} catch ( WooPaymentsApiException $exception ) {
			return 0;
		}

		$metadata = isset( $intention['metadata'] ) && is_array( $intention['metadata'] ) ? $intention['metadata'] : array();
		$order_id = $metadata['order_id'] ?? 0;

		return is_numeric( $order_id ) ? (int) $order_id : 0;
	}

	/**
	 * Sort timeline events like the reference WooPayments client.
	 *
	 * @param mixed $event_a First event.
	 * @param mixed $event_b Second event.
	 * @return int
	 */
	private function sort_timeline_events( $event_a, $event_b ): int {
		$datetime_result = $this->get_timeline_event_datetime( $event_b ) <=> $this->get_timeline_event_datetime( $event_a );
		if ( 0 !== $datetime_result ) {
			return $datetime_result;
		}

		return $this->get_timeline_event_order( $event_b ) <=> $this->get_timeline_event_order( $event_a );
	}

	/**
	 * Get an event datetime.
	 *
	 * @param mixed $event Timeline event.
	 * @return int
	 */
	private function get_timeline_event_datetime( $event ): int {
		if ( ! is_array( $event ) ) {
			return 0;
		}

		$datetime = $event['datetime'] ?? 0;
		return is_numeric( $datetime ) ? (int) $datetime : 0;
	}

	/**
	 * Get an event type sort order.
	 *
	 * @param mixed $event Timeline event.
	 * @return int
	 */
	private function get_timeline_event_order( $event ): int {
		if ( ! is_array( $event ) ) {
			return -1;
		}

		$type = $event['type'] ?? '';
		if ( ! is_string( $type ) ) {
			return -1;
		}

		$order = array_search( $type, self::EVENTS_ORDER, true );
		return false === $order ? -1 : (int) $order;
	}

	/**
	 * Build a readable REST route definition.
	 *
	 * @param string $callback Callback method.
	 * @return array<string,mixed>
	 */
	private function get_readable_route( string $callback ): array {
		return array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, $callback ),
			'permission_callback' => array( $this, 'check_permission' ),
		);
	}

	/**
	 * Convert a WooPayments API exception to a REST error.
	 *
	 * @param WooPaymentsApiException $exception Exception.
	 * @return WP_Error
	 */
	private function api_exception_to_wp_error( WooPaymentsApiException $exception ): WP_Error {
		$error_code = $exception->get_error_code();
		if ( '' === $error_code ) {
			$error_code = 'wcpay_api_error';
		}

		$http_code = $exception->get_http_code();
		if ( ! $http_code ) {
			$http_code = 400;
		}

		return new WP_Error(
			$error_code,
			$exception->getMessage(),
			array( 'status' => $http_code )
		);
	}
}
