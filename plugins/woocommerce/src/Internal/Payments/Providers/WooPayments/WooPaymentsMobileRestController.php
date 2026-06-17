<?php
/**
 * WooPaymentsMobileRestController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Throwable;
use WC_Order;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Native WooPayments mobile and In-Person Payments REST controller.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsMobileRestController implements RegisterHooksInterface {

	private const NAMESPACE = 'wc/v3';

	private const STORE_READERS_TRANSIENT_KEY = 'wcpay_store_terminal_readers';

	private const STORE_LOCATIONS_TRANSIENT_KEY = 'wcpay_store_terminal_locations';

	private const READER_CACHE_TTL = 2 * HOUR_IN_SECONDS;

	private const LOCATION_CACHE_TTL = DAY_IN_SECONDS;

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
	 * WooPayments account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

	/**
	 * WooPayments customer service.
	 *
	 * @var WooPaymentsCustomerService
	 */
	private WooPaymentsCustomerService $customer_service;

	/**
	 * WooPayments order data service.
	 *
	 * @var WooPaymentsOrderDataService
	 */
	private WooPaymentsOrderDataService $order_data_service;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter $arbiter            Runtime owner arbiter.
	 * @param WooPaymentsApiClient         $api_client         Native WooPayments API client.
	 * @param WooPaymentsAccountService    $account_service    WooPayments account service.
	 * @param WooPaymentsCustomerService   $customer_service   WooPayments customer service.
	 * @param WooPaymentsOrderDataService  $order_data_service WooPayments order data service.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter, WooPaymentsApiClient $api_client, WooPaymentsAccountService $account_service, WooPaymentsCustomerService $customer_service, WooPaymentsOrderDataService $order_data_service ): void {
		$this->arbiter            = $arbiter;
		$this->api_client         = $api_client;
		$this->account_service    = $account_service;
		$this->customer_service   = $customer_service;
		$this->order_data_service = $order_data_service;
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
	 * Register WooPayments-compatible mobile and IPP routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/payments/connection_tokens',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_connection_token' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments/orders/(?P<order_id>\w+)/capture_terminal_payment',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'capture_terminal_payment' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments/orders/(?P<order_id>\w+)/prepare_terminal_payment',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'prepare_terminal_payment' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments/orders/(?P<order_id>\w+)/create_terminal_intent',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_terminal_intent' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments/orders/(?P<order_id>\d+)/create_customer',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_customer' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments/readers',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_readers' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'register_reader' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments/readers/charges/(?P<transaction_id>\w+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_reader_charge_summary' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments/readers/receipts/preview',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'preview_print_receipt' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments/readers/receipts/(?P<payment_intent_id>\w+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'generate_print_receipt' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments/terminal/locations/store',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_store_location' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments/terminal/locations',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_terminal_locations' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_terminal_location' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments/terminal/locations/(?P<location_id>\w+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_terminal_location' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_terminal_location' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_terminal_location' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);
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
	 * Create a terminal connection token.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_connection_token( WP_REST_Request $request ) {
		unset( $request );

		try {
			$data              = $this->api_client->create_terminal_connection_token();
			$data['test_mode'] = $this->account_service->is_test_mode_enabled();

			return new WP_REST_Response( $data );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Create or update the WooPayments customer for an order.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_customer( WP_REST_Request $request ) {
		$order = $this->get_order_from_request( $request );
		if ( $order instanceof WP_Error ) {
			return $order;
		}

		/**
		 * Filters order statuses that cannot create or update WooPayments customers through the mobile REST API.
		 *
		 * @since 11.0.0
		 *
		 * @param string[] $statuses Disallowed order statuses.
		 */
		$disallowed_statuses = apply_filters(
			'wcpay_create_customer_disallowed_order_statuses',
			array(
				'completed',
				'cancelled',
				'refunded',
				'failed',
			)
		);

		if ( $order->has_status( is_array( $disallowed_statuses ) ? $disallowed_statuses : array() ) ) {
			return new WP_Error( 'wcpay_invalid_order_status', __( 'Invalid order status', 'woocommerce' ), array( 'status' => 400 ) );
		}

		try {
			$customer_id = (string) $order->get_meta( '_stripe_customer_id', true );
			if ( '' !== $customer_id ) {
				$customer_id = $this->customer_service->update_customer_for_order( $customer_id, $order );
			} else {
				$customer_id = $this->customer_service->get_or_create_customer_id_for_order( $order );
			}

			$order->update_meta_data( '_stripe_customer_id', $customer_id );
			$order->save();

			return new WP_REST_Response( array( 'id' => $customer_id ) );
		} catch ( Throwable $exception ) {
			return $this->server_error();
		}
	}

	/**
	 * Create a terminal payment intent for an order.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_terminal_intent( WP_REST_Request $request ) {
		$order = $this->get_order_from_request( $request );
		if ( $order instanceof WP_Error ) {
			return $order;
		}

		try {
			$currency        = strtolower( $order->get_currency() );
			$metadata        = $request->get_param( 'metadata' );
			$metadata        = is_array( $metadata ) ? $metadata : array();
			$payment_methods = $this->get_terminal_intent_payment_methods( $request );
			$capture_method  = $this->get_terminal_intent_capture_method( $request );
			$request_data    = array(
				'amount'               => $this->order_data_service->prepare_amount( (float) $order->get_total(), $currency ),
				'currency'             => $currency,
				'metadata'             => array_merge(
					$metadata,
					array(
						'order_id'     => (string) $order->get_id(),
						'order_number' => $order->get_order_number(),
					)
				),
				'payment_method_types' => $payment_methods,
				'capture_method'       => $capture_method,
			);
			$customer_id     = $request->get_param( 'customer_id' );

			if ( is_string( $customer_id ) && '' !== $customer_id ) {
				$request_data['customer'] = $customer_id;
			}

			$intent = $this->api_client->create_terminal_payment_intention( $request_data );

			return new WP_REST_Response( array( 'id' => $intent['id'] ?? null ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		} catch ( Throwable $exception ) {
			return $this->server_error();
		}
	}

	/**
	 * Prepare a terminal payment.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function prepare_terminal_payment( WP_REST_Request $request ) {
		$order = $this->get_order_from_request( $request );
		if ( $order instanceof WP_Error ) {
			return $order;
		}

		$intent_id = $this->get_request_string( $request, 'payment_intent_id' );
		if ( ! $this->is_valid_provider_id( $intent_id ) ) {
			return new WP_Error( 'wcpay_invalid_payment_intent_id', __( 'Invalid payment intent ID.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		if ( 0 < count( $order->get_refunds() ) ) {
			return new WP_Error( 'wcpay_refunded_order_unpreparable', __( 'Refunded orders cannot be prepared for terminal payment.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		try {
			return new WP_REST_Response( $this->api_client->prepare_terminal_payment( $intent_id, $order->get_id() ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Capture a terminal payment.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function capture_terminal_payment( WP_REST_Request $request ) {
		$order = $this->get_order_from_request( $request );
		if ( $order instanceof WP_Error ) {
			return $order;
		}

		$intent_id = $this->get_request_string( $request, 'payment_intent_id' );
		if ( '' === $intent_id ) {
			return new WP_Error( 'wcpay_missing_payment_intent_id', __( 'Payment intent ID is required.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		if ( 0 < count( $order->get_refunds() ) ) {
			return new WP_Error( 'wcpay_refunded_order_uncapturable', __( 'Refunded orders cannot be captured.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$uncapturable_error = $this->get_uncapturable_order_error( $order, $intent_id );
		if ( $uncapturable_error instanceof WP_Error ) {
			return $uncapturable_error;
		}

		try {
			$intent = $this->api_client->get_payment_intention( $intent_id );
			if ( ! $this->intent_matches_order( $intent, $order ) ) {
				return new WP_Error( 'wcpay_intent_order_mismatch', __( 'Payment intent does not belong to this order.', 'woocommerce' ), array( 'status' => 409 ) );
			}

			$status = isset( $intent['status'] ) ? (string) $intent['status'] : '';
			if ( ! in_array( $status, array( 'requires_capture', 'succeeded' ), true ) ) {
				return new WP_Error( 'wcpay_payment_uncapturable', __( 'Payment cannot be captured for this order.', 'woocommerce' ), array( 'status' => 409 ) );
			}

			$result = 'succeeded' === $status
				? $intent
				: $this->api_client->capture_intention(
					$intent_id,
					$this->order_data_service->prepare_amount( (float) $order->get_total(), (string) $order->get_currency() ),
					$this->get_intent_metadata( $intent )
				);

			if ( 'succeeded' !== (string) ( $result['status'] ?? '' ) ) {
				return $this->get_terminal_capture_error( $result );
			}

			$this->mark_terminal_payment_completed( $order, $result, $intent_id );

			return new WP_REST_Response(
				array(
					'status' => isset( $result['status'] ) ? (string) $result['status'] : '',
					'id'     => isset( $result['id'] ) ? (string) $result['id'] : $intent_id,
				)
			);
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		} catch ( Throwable $exception ) {
			return $this->server_error();
		}
	}

	/**
	 * Get terminal readers.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_readers( WP_REST_Request $request ) {
		unset( $request );

		$cached = get_transient( self::STORE_READERS_TRANSIENT_KEY );
		if ( is_array( $cached ) ) {
			return new WP_REST_Response( $cached );
		}

		try {
			$readers = array_map(
				array( $this, 'extract_reader' ),
				$this->extract_list( $this->api_client->get_terminal_readers() )
			);
			$summary = $this->extract_list( $this->api_client->get_readers_charge_summary( gmdate( 'Y-m-d' ) ) );
			$readers = $this->annotate_active_readers( $readers, $summary );

			set_transient( self::STORE_READERS_TRANSIENT_KEY, $readers, self::READER_CACHE_TTL );

			return new WP_REST_Response( $readers );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Register a terminal reader.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function register_reader( WP_REST_Request $request ) {
		$location          = $this->get_request_string( $request, 'location' );
		$registration_code = $this->get_request_string( $request, 'registration_code' );

		if ( '' === $location || '' === $registration_code ) {
			return new WP_Error( 'wcpay_missing_reader_registration_data', __( 'Reader location and registration code are required.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$label    = $this->get_request_string( $request, 'label' );
		$metadata = $request->get_param( 'metadata' );

		try {
			$reader = $this->api_client->register_terminal_reader(
				$location,
				$registration_code,
				'' === $label ? null : $label,
				is_array( $metadata ) ? $metadata : null
			);
			delete_transient( self::STORE_READERS_TRANSIENT_KEY );

			return new WP_REST_Response( $this->extract_reader( $reader ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get reader charge summary.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_reader_charge_summary( WP_REST_Request $request ) {
		try {
			$transaction_id = $this->get_request_string( $request, 'transaction_id' );
			$transaction    = $this->api_client->get_transaction( $transaction_id );
			if ( empty( $transaction ) ) {
				return new WP_REST_Response( array() );
			}

			$created     = isset( $transaction['created'] ) ? (int) $transaction['created'] : 0;
			$charge_date = 0 < $created ? gmdate( 'Y-m-d', $created ) : gmdate( 'Y-m-d' );

			return new WP_REST_Response(
				$this->api_client->get_readers_charge_summary(
					$charge_date,
					$transaction_id
				)
			);
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Generate a preview receipt.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response
	 */
	public function preview_print_receipt( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : $request->get_params();

		return new WP_REST_Response(
			array(
				'html_content' => $this->render_receipt_html( $this->get_preview_receipt_data( $params ) ),
			)
		);
	}

	/**
	 * Generate a receipt for a payment intent.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 * @throws \RuntimeException When receipt data cannot be generated before the error response is built.
	 */
	public function generate_print_receipt( WP_REST_Request $request ) {
		$intent_id = $this->get_request_string( $request, 'payment_intent_id' );

		try {
			$intent = $this->api_client->get_payment_intention( $intent_id );
			if ( 'succeeded' !== (string) ( $intent['status'] ?? '' ) ) {
				throw new \RuntimeException( __( 'Invalid payment intent', 'woocommerce' ) );
			}

			$charge = $this->get_latest_charge( $intent );
			if ( isset( $charge['id'] ) ) {
				$charge = array_merge( $charge, $this->api_client->get_charge( (string) $charge['id'] ) );
			}

			$order = $this->get_receipt_order( $intent, $charge );
			if ( ! $order instanceof WC_Order ) {
				throw new \RuntimeException( __( 'Order not found', 'woocommerce' ) );
			}

			return new WP_REST_Response(
				array(
					'html_content' => $this->render_receipt_html( $this->get_receipt_data( $intent, $charge, $order ) ),
				)
			);
		} catch ( Throwable $exception ) {
			$status = 500;
			if ( $exception instanceof WooPaymentsApiException && 0 < $exception->get_http_code() ) {
				$status = $exception->get_http_code();
			}

			return new WP_Error( 'generate_print_receipt_error', $exception->getMessage(), array( 'status' => $status ) );
		}
	}

	/**
	 * Get the store terminal location.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_store_location( WP_REST_Request $request ) {
		unset( $request );

		$address = $this->get_store_terminal_address();
		if ( empty( $address['country'] ) || empty( $address['city'] ) || empty( $address['postal_code'] ) || empty( $address['line1'] ) ) {
			return new WP_Error( 'store_address_is_incomplete', admin_url( 'admin.php?page=wc-settings&tab=general' ), array( 'status' => 400 ) );
		}

		try {
			$locations     = $this->get_cached_terminal_locations();
			$display_names = $this->get_store_location_display_names();
			foreach ( $locations as $location ) {
				if ( $this->location_matches_store( $location, $display_names, $address ) ) {
					return new WP_REST_Response( $this->extract_location( $location ) );
				}
			}

			$location = $this->api_client->create_terminal_location(
				$display_names[0],
				$address,
				array(
					'source' => 'woocommerce_native',
				)
			);
			delete_transient( self::STORE_LOCATIONS_TRANSIENT_KEY );

			return new WP_REST_Response( $this->extract_location( $location ) );
		} catch ( WooPaymentsApiException $exception ) {
			if ( 'invalid_request_error' === $exception->get_error_code() ) {
				return new WP_Error( 'store_address_is_incomplete', admin_url( 'admin.php?page=wc-settings&tab=general' ), array( 'status' => 400 ) );
			}

			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get terminal locations.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_terminal_locations( WP_REST_Request $request ) {
		unset( $request );

		try {
			return new WP_REST_Response( array_map( array( $this, 'extract_location' ), $this->get_cached_terminal_locations() ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get one terminal location by ID.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_terminal_location( WP_REST_Request $request ) {
		$location_id = $this->get_request_string( $request, 'location_id' );

		try {
			foreach ( $this->get_cached_terminal_locations() as $location ) {
				if ( (string) ( $location['id'] ?? '' ) === $location_id ) {
					return new WP_REST_Response( $this->extract_location( $location ) );
				}
			}

			$location = $this->api_client->get_terminal_location( $location_id );
			delete_transient( self::STORE_LOCATIONS_TRANSIENT_KEY );

			return new WP_REST_Response( $this->extract_location( $location ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Create a terminal location.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_terminal_location( WP_REST_Request $request ) {
		$address      = $request->get_param( 'address' );
		$display_name = $this->get_request_string( $request, 'display_name' );
		$metadata     = $request->get_param( 'metadata' );

		try {
			$location = $this->api_client->create_terminal_location(
				$display_name,
				is_array( $address ) ? $address : array(),
				is_array( $metadata ) ? $metadata : array()
			);
			delete_transient( self::STORE_LOCATIONS_TRANSIENT_KEY );

			return new WP_REST_Response( $this->extract_location( $location ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Update a terminal location.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_terminal_location( WP_REST_Request $request ) {
		$address      = $request->get_param( 'address' );
		$display_name = $request->get_param( 'display_name' );

		try {
			$location = $this->api_client->update_terminal_location(
				$this->get_request_string( $request, 'location_id' ),
				is_string( $display_name ) ? $display_name : null,
				is_array( $address ) ? $address : null
			);
			delete_transient( self::STORE_LOCATIONS_TRANSIENT_KEY );

			return new WP_REST_Response( $this->extract_location( $location ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Delete a terminal location.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_terminal_location( WP_REST_Request $request ) {
		try {
			$result = $this->api_client->delete_terminal_location( $this->get_request_string( $request, 'location_id' ) );
			delete_transient( self::STORE_LOCATIONS_TRANSIENT_KEY );

			return new WP_REST_Response( $result );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get an order from a REST request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WC_Order|WP_Error
	 */
	private function get_order_from_request( WP_REST_Request $request ) {
		$order_id = absint( $request->get_param( 'order_id' ) );
		$order    = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			return new WP_Error( 'wcpay_missing_order', __( 'Order not found', 'woocommerce' ), array( 'status' => 404 ) );
		}

		return $order;
	}

	/**
	 * Get terminal intent payment methods from the request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return string[]
	 * @throws \UnexpectedValueException When the request contains unsupported payment methods.
	 */
	private function get_terminal_intent_payment_methods( WP_REST_Request $request ): array {
		$payment_methods = $request->get_param( 'payment_methods' );
		if ( null === $payment_methods ) {
			return array( 'card_present' );
		}

		if ( ! is_array( $payment_methods ) ) {
			throw new \UnexpectedValueException( 'Invalid terminal payment methods.' );
		}

		$allowed = array( 'card_present', 'interac_present' );
		foreach ( $payment_methods as $payment_method ) {
			if ( ! in_array( $payment_method, $allowed, true ) ) {
				throw new \UnexpectedValueException( 'Unsupported terminal payment method.' );
			}
		}

		return array_values( array_map( 'strval', $payment_methods ) );
	}

	/**
	 * Get terminal intent capture method from the request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return string
	 * @throws \UnexpectedValueException When the request contains an unsupported capture method.
	 */
	private function get_terminal_intent_capture_method( WP_REST_Request $request ): string {
		$capture_method = $request->get_param( 'capture_method' );
		if ( null === $capture_method ) {
			return 'manual';
		}

		if ( ! is_string( $capture_method ) || ! in_array( $capture_method, array( 'manual', 'automatic' ), true ) ) {
			throw new \UnexpectedValueException( 'Unsupported terminal capture method.' );
		}

		return $capture_method;
	}

	/**
	 * Get an uncapturable order error when terminal capture must not proceed.
	 *
	 * @param WC_Order $order     Order.
	 * @param string   $intent_id Intent ID.
	 * @return WP_Error|null
	 */
	private function get_uncapturable_order_error( WC_Order $order, string $intent_id ): ?WP_Error {
		$stored_status = (string) $order->get_meta( '_intention_status', true );
		$stored_intent = (string) $order->get_meta( '_intent_id', true );

		if ( in_array( $stored_status, array( 'succeeded', 'canceled', 'processing' ), true ) ) {
			return new WP_Error( 'wcpay_payment_uncapturable', __( 'Payment cannot be captured for this order.', 'woocommerce' ), array( 'status' => 409 ) );
		}

		if ( '' !== $stored_intent && $stored_intent !== $intent_id ) {
			return new WP_Error( 'wcpay_payment_uncapturable', __( 'Payment cannot be captured for this order.', 'woocommerce' ), array( 'status' => 409 ) );
		}

		return null;
	}

	/**
	 * Tell whether an intent belongs to an order.
	 *
	 * @param array<string,mixed> $intent Intent.
	 * @param WC_Order            $order  Order.
	 * @return bool
	 */
	private function intent_matches_order( array $intent, WC_Order $order ): bool {
		$metadata = $this->get_intent_metadata( $intent );
		$order_id = $metadata['order_id'] ?? '';

		return is_numeric( $order_id ) && $order->get_id() === (int) $order_id;
	}

	/**
	 * Build a terminal capture error response from a failed capture result.
	 *
	 * @param array<string,mixed> $result Capture result.
	 * @return WP_Error
	 */
	private function get_terminal_capture_error( array $result ): WP_Error {
		$error_type    = isset( $result['error_code'] ) ? (string) $result['error_code'] : null;
		$extra_details = isset( $result['extra_details'] ) && is_array( $result['extra_details'] ) ? $result['extra_details'] : array();
		$error_code    = 'wcpay_capture_error';
		$http_code     = isset( $result['http_code'] ) ? (int) $result['http_code'] : 502;
		$message       = sprintf(
			/* translators: %s: the error message. */
			__( 'Payment capture failed to complete with the following message: %s', 'woocommerce' ),
			isset( $result['message'] ) ? (string) $result['message'] : __( 'Unknown error', 'woocommerce' )
		);

		if ( 'amount_too_small' === $error_type && ! empty( $extra_details ) ) {
			$error_code      = 'wcpay_capture_error_amount_too_small';
			$encoded_details = wp_json_encode( $extra_details );
			$message         = false === $encoded_details ? '' : esc_html( $encoded_details );
		}

		return new WP_Error( $error_code, $message, array( 'status' => $http_code ) );
	}

	/**
	 * Get intent metadata.
	 *
	 * @param array<string,mixed> $intent Intent.
	 * @return array<string,mixed>
	 */
	private function get_intent_metadata( array $intent ): array {
		return isset( $intent['metadata'] ) && is_array( $intent['metadata'] ) ? $intent['metadata'] : array();
	}

	/**
	 * Persist terminal payment state and complete the order.
	 *
	 * @param WC_Order            $order     Order.
	 * @param array<string,mixed> $intent    Captured or already-succeeded intent.
	 * @param string              $intent_id Intent ID.
	 */
	private function mark_terminal_payment_completed( WC_Order $order, array $intent, string $intent_id ): void {
		$status = isset( $intent['status'] ) ? (string) $intent['status'] : '';
		$charge = $this->get_latest_charge( $intent );

		$order->set_payment_method( OrderPaymentStore::GATEWAY_ID );
		$order->set_payment_method_title( __( 'WooCommerce In-Person Payments', 'woocommerce' ) );
		$order->set_transaction_id( $intent_id );
		$order->update_meta_data( '_intent_id', $intent_id );
		$order->update_meta_data( '_intention_status', $status );
		$order->update_meta_data( '_wcpay_mode', $this->account_service->get_mode() );
		$order->update_meta_data( 'receipt_url', get_rest_url( null, self::NAMESPACE . '/payments/readers/receipts/' . $intent_id ) );
		$order->update_meta_data( '_wcpay_fraud_meta_box_type', 'terminal_payment' );

		if ( isset( $intent['currency'] ) ) {
			$order->update_meta_data( '_wcpay_intent_currency', (string) $intent['currency'] );
		}

		$payment_method_id = $this->get_result_payment_method_id( $intent, $charge );
		if ( '' !== $payment_method_id ) {
			$order->update_meta_data( '_payment_method_id', $payment_method_id );
		}

		if ( isset( $charge['id'] ) ) {
			$order->update_meta_data( '_charge_id', (string) $charge['id'] );
		}

		if ( isset( $charge['balance_transaction']['id'] ) ) {
			$order->update_meta_data( '_wcpay_payment_transaction_id', (string) $charge['balance_transaction']['id'] );
		}

		$this->apply_payment_method_details( $order, $charge );

		/**
		 * Filters the order status applied after a successful terminal payment.
		 *
		 * @since 11.0.0
		 *
		 * @param string $order_status Order status.
		 */
		$order_status = apply_filters( 'wcpay_terminal_payment_completed_order_status', 'completed' );
		if ( 'payment_complete' === $order_status ) {
			$order->payment_complete( $intent_id );
		} elseif ( is_string( $order_status ) && '' !== $order_status ) {
			$order->update_status( $order_status );
		}

		$order->save();
	}

	/**
	 * Apply payment method details from a charge to order meta.
	 *
	 * @param WC_Order            $order  Order.
	 * @param array<string,mixed> $charge Charge.
	 */
	private function apply_payment_method_details( WC_Order $order, array $charge ): void {
		$payment_method_details = isset( $charge['payment_method_details'] ) && is_array( $charge['payment_method_details'] ) ? $charge['payment_method_details'] : array();
		if ( empty( $payment_method_details ) ) {
			return;
		}

		$encoded_payment_method_details = wp_json_encode( $payment_method_details );
		if ( false !== $encoded_payment_method_details ) {
			$order->update_meta_data( '_wcpay_payment_method_details', $encoded_payment_method_details );
			$order->update_meta_data( '_wcpay_raw_payment_method_details', $encoded_payment_method_details );
		}

		$card_details = array();
		if ( isset( $payment_method_details['card'] ) && is_array( $payment_method_details['card'] ) ) {
			$card_details = $payment_method_details['card'];
		} elseif ( isset( $payment_method_details['card_present'] ) && is_array( $payment_method_details['card_present'] ) ) {
			$card_details = $payment_method_details['card_present'];
		}

		if ( isset( $card_details['last4'] ) ) {
			$order->update_meta_data( 'last4', (string) $card_details['last4'] );
		}

		if ( isset( $card_details['brand'] ) ) {
			$order->update_meta_data( '_card_brand', (string) $card_details['brand'] );
		}
	}

	/**
	 * Get the payment method ID from an intent or charge.
	 *
	 * @param array<string,mixed> $intent Intent.
	 * @param array<string,mixed> $charge Charge.
	 * @return string
	 */
	private function get_result_payment_method_id( array $intent, array $charge ): string {
		if ( isset( $intent['payment_method'] ) && is_string( $intent['payment_method'] ) ) {
			return $intent['payment_method'];
		}

		if ( isset( $intent['payment_method'] ) && is_array( $intent['payment_method'] ) && isset( $intent['payment_method']['id'] ) ) {
			return (string) $intent['payment_method']['id'];
		}

		if ( isset( $charge['payment_method'] ) ) {
			return (string) $charge['payment_method'];
		}

		return '';
	}

	/**
	 * Get the latest charge from an intent.
	 *
	 * @param array<string,mixed> $intent Intent.
	 * @return array<string,mixed>
	 */
	private function get_latest_charge( array $intent ): array {
		$charges = isset( $intent['charges']['data'] ) && is_array( $intent['charges']['data'] ) ? $intent['charges']['data'] : array();
		$charge  = empty( $charges ) ? array() : end( $charges );

		return is_array( $charge ) ? $charge : array();
	}

	/**
	 * Get cached terminal locations.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_cached_terminal_locations(): array {
		$cached = get_transient( self::STORE_LOCATIONS_TRANSIENT_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$locations = array_map( array( $this, 'extract_location' ), $this->extract_list( $this->api_client->get_terminal_locations() ) );
		set_transient( self::STORE_LOCATIONS_TRANSIENT_KEY, $locations, self::LOCATION_CACHE_TTL );

		return $locations;
	}

	/**
	 * Extract a reader response.
	 *
	 * @param array<string,mixed> $reader Reader.
	 * @return array<string,mixed>
	 */
	private function extract_reader( array $reader ): array {
		return array_intersect_key(
			$reader,
			array_flip(
				array(
					'id',
					'livemode',
					'device_type',
					'label',
					'location',
					'metadata',
					'status',
					'is_active',
				)
			)
		);
	}

	/**
	 * Extract a location response.
	 *
	 * @param array<string,mixed> $location Location.
	 * @return array<string,mixed>
	 */
	private function extract_location( array $location ): array {
		return array_intersect_key(
			$location,
			array_flip(
				array(
					'id',
					'address',
					'display_name',
					'livemode',
				)
			)
		);
	}

	/**
	 * Extract a list from common WPCOM collection envelopes.
	 *
	 * @param array<string,mixed> $response Response.
	 * @return array<int,array<string,mixed>>
	 */
	private function extract_list( array $response ): array {
		foreach ( array( 'data', 'readers', 'locations', 'terminal_locations' ) as $key ) {
			if ( isset( $response[ $key ] ) && is_array( $response[ $key ] ) ) {
				return $this->normalize_list( $response[ $key ] );
			}
		}

		return $this->is_list_array( $response ) ? $this->normalize_list( $response ) : array();
	}

	/**
	 * Normalize list items to arrays.
	 *
	 * @param array<mixed> $items Items.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_list( array $items ): array {
		return array_values(
			array_filter(
				$items,
				static function ( $item ): bool {
					return is_array( $item );
				}
			)
		);
	}

	/**
	 * Tell whether an array is a list.
	 *
	 * @param array<mixed> $items Items.
	 * @return bool
	 */
	private function is_list_array( array $items ): bool {
		return array_keys( $items ) === range( 0, count( $items ) - 1 );
	}

	/**
	 * Add active-reader flags from charge summary data.
	 *
	 * @param array<int,array<string,mixed>> $readers Reader list.
	 * @param array<int,array<string,mixed>> $summary Charge summary.
	 * @return array<int,array<string,mixed>>
	 */
	private function annotate_active_readers( array $readers, array $summary ): array {
		$active_reader_ids = array();
		foreach ( $summary as $item ) {
			if ( isset( $item['reader_id'] ) && 'active' === (string) ( $item['status'] ?? '' ) ) {
				$active_reader_ids[] = (string) $item['reader_id'];
			}
		}

		foreach ( $readers as &$reader ) {
			$reader['is_active'] = isset( $reader['id'] ) && in_array( (string) $reader['id'], $active_reader_ids, true );
		}

		return $readers;
	}

	/**
	 * Build a terminal address from WooCommerce store settings.
	 *
	 * @return array<string,string>
	 */
	private function get_store_terminal_address(): array {
		$country = WC()->countries->get_base_country();
		$state   = WC()->countries->get_base_state();

		if ( 'PR' === $country ) {
			$country = 'US';
			$state   = 'PR';
		}

		return array_filter(
			array(
				'country'     => $country,
				'state'       => $state,
				'city'        => get_option( 'woocommerce_store_city', '' ),
				'postal_code' => get_option( 'woocommerce_store_postcode', '' ),
				'line1'       => get_option( 'woocommerce_store_address', '' ),
				'line2'       => get_option( 'woocommerce_store_address_2', '' ),
			),
			static function ( string $value ): bool {
				return '' !== $value;
			}
		);
	}

	/**
	 * Get store location display names that may have been used by prior clients.
	 *
	 * @return string[]
	 */
	private function get_store_location_display_names(): array {
		$site_name = str_replace( array( 'https://', 'http://' ), '', get_site_url() );
		$host      = wp_parse_url( get_site_url(), PHP_URL_HOST );
		$host      = is_string( $host ) ? trim( $host ) : '';
		$blog_name = trim( get_bloginfo( 'name' ) );
		$names     = array_values( array_unique( array_filter( array( $site_name, $blog_name, $host ) ) ) );

		return empty( $names ) ? array( 'Store' ) : $names;
	}

	/**
	 * Tell whether a location matches store data.
	 *
	 * @param array<string,mixed>  $location     Location.
	 * @param string[]             $display_names Store display names.
	 * @param array<string,string> $address      Store address.
	 * @return bool
	 */
	private function location_matches_store( array $location, array $display_names, array $address ): bool {
		if ( ! in_array( (string) ( $location['display_name'] ?? '' ), $display_names, true ) ) {
			return false;
		}

		$location_address = isset( $location['address'] ) && is_array( $location['address'] ) ? $location['address'] : array();
		foreach ( $address as $key => $value ) {
			if ( (string) ( $location_address[ $key ] ?? '' ) !== $value ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get receipt data from request settings for preview rendering.
	 *
	 * @param array<string,mixed> $params Request params.
	 * @return array<string,mixed>
	 */
	private function get_preview_receipt_data( array $params ): array {
		$settings = $this->get_receipt_settings_from_params( $params );

		return array_merge(
			$settings,
			array(
				'order_id'            => '42',
				'currency'            => 'USD',
				'subtotal'            => 0,
				'total'               => 0,
				'total_fees'          => 0,
				'shipping_tax'        => 0,
				'line_items'          => array(
					array(
						'name'          => 'Sample',
						'quantity'      => 1,
						'subtotal'      => 0,
						'product_id'    => 'sample',
						'price'         => 0,
						'regular_price' => 1,
					),
					array(
						'name'          => 'Sample',
						'quantity'      => 1,
						'subtotal'      => 0,
						'product_id'    => 'sample',
						'price'         => 0,
						'regular_price' => 1,
					),
				),
				'coupon_lines'        => array(
					array(
						'code'        => 'DISCOUNT',
						'description' => 'sample',
						'discount'    => 0,
					),
				),
				'tax_lines'           => array(
					array(
						'rate_percent'       => 0,
						'tax_total'          => 0,
						'shipping_tax_total' => 0,
					),
				),
				'amount_captured'     => 0,
				'brand'               => 'Sample',
				'last4'               => '0000',
				'payment_method_name' => 'Sample',
				'receipt'             => array(
					'application_preferred_name' => 'Sample, Receipts preview',
					'dedicated_file_name'        => '0000',
					'account_type'               => 'Sample',
				),
			)
		);
	}

	/**
	 * Get receipt settings from preview request params.
	 *
	 * @param array<string,mixed> $params Request params.
	 * @return array<string,mixed>
	 */
	private function get_receipt_settings_from_params( array $params ): array {
		$business_name           = (string) $this->account_service->get_gateway_setting( 'account_business_name', get_bloginfo( 'name' ) );
		$gateway_support_address = $this->account_service->get_gateway_setting( 'account_business_support_address', array() );
		$support_address         = $params['accountBusinessSupportAddress'] ?? $params['support_address'] ?? $gateway_support_address;
		$support_phone           = (string) $this->account_service->get_gateway_setting( 'account_business_support_phone', '' );
		$support_email           = (string) $this->account_service->get_gateway_setting( 'account_business_support_email', get_option( 'admin_email', '' ) );

		return array(
			'business_name'   => $this->get_receipt_string( $params, array( 'accountBusinessName', 'business_name' ), $business_name ),
			'support_address' => $this->normalize_receipt_support_address( is_array( $support_address ) ? $support_address : array() ),
			'support_phone'   => $this->get_receipt_string( $params, array( 'accountBusinessSupportPhone', 'support_phone' ), $support_phone ),
			'support_email'   => $this->get_receipt_string( $params, array( 'accountBusinessSupportEmail', 'support_email' ), $support_email ),
		);
	}

	/**
	 * Get the first non-empty receipt string from request params.
	 *
	 * @param array<string,mixed> $params  Request params.
	 * @param string[]            $keys    Param keys.
	 * @param string              $fallback Fallback value.
	 * @return string
	 */
	private function get_receipt_string( array $params, array $keys, string $fallback ): string {
		foreach ( $keys as $key ) {
			if ( isset( $params[ $key ] ) && is_scalar( $params[ $key ] ) && '' !== (string) $params[ $key ] ) {
				return (string) $params[ $key ];
			}
		}

		return $fallback;
	}

	/**
	 * Normalize receipt support address shape.
	 *
	 * @param array<string,mixed> $address Address.
	 * @return array<string,string>
	 */
	private function normalize_receipt_support_address( array $address ): array {
		$default = $this->get_store_terminal_address();

		return array(
			'line1'       => (string) ( $address['line1'] ?? $default['line1'] ?? '' ),
			'line2'       => (string) ( $address['line2'] ?? $default['line2'] ?? '' ),
			'city'        => (string) ( $address['city'] ?? $default['city'] ?? '' ),
			'state'       => (string) ( $address['state'] ?? $default['state'] ?? '' ),
			'postal_code' => (string) ( $address['postal_code'] ?? $default['postal_code'] ?? '' ),
			'country'     => (string) ( $address['country'] ?? $default['country'] ?? '' ),
		);
	}

	/**
	 * Get the receipt's WooCommerce order from intent or charge metadata.
	 *
	 * @param array<string,mixed> $intent Intent.
	 * @param array<string,mixed> $charge Charge.
	 * @return WC_Order|null
	 */
	private function get_receipt_order( array $intent, array $charge ): ?WC_Order {
		$order_id = isset( $charge['order']['number'] ) ? (int) $charge['order']['number'] : 0;
		if ( 0 >= $order_id ) {
			$charge_metadata = isset( $charge['metadata'] ) && is_array( $charge['metadata'] ) ? $charge['metadata'] : array();
			$intent_metadata = $this->get_intent_metadata( $intent );
			$order_id        = isset( $charge_metadata['order_id'] ) ? (int) $charge_metadata['order_id'] : (int) ( $intent_metadata['order_id'] ?? 0 );
		}

		$order = 0 < $order_id ? wc_get_order( $order_id ) : false;

		return $order instanceof WC_Order ? $order : null;
	}

	/**
	 * Get receipt data from intent, charge, and order.
	 *
	 * @param array<string,mixed> $intent Intent.
	 * @param array<string,mixed> $charge Charge.
	 * @param WC_Order            $order  Order.
	 * @return array<string,mixed>
	 */
	private function get_receipt_data( array $intent, array $charge, WC_Order $order ): array {
		$payment_method_details = isset( $charge['payment_method_details'] ) && is_array( $charge['payment_method_details'] ) ? $charge['payment_method_details'] : array();
		$card_present           = isset( $payment_method_details['card_present'] ) && is_array( $payment_method_details['card_present'] ) ? $payment_method_details['card_present'] : array();
		$receipt                = isset( $card_present['receipt'] ) && is_array( $card_present['receipt'] ) ? $card_present['receipt'] : array();

		return array_merge(
			$this->get_receipt_settings_from_params( array() ),
			array(
				'order_id'            => $order->get_id(),
				'currency'            => $order->get_currency(),
				'subtotal'            => (float) $order->get_subtotal(),
				'total'               => (float) $order->get_total(),
				'total_fees'          => (float) $order->get_total_fees(),
				'shipping_tax'        => 0 < count( $order->get_shipping_methods() ) ? (float) $order->get_shipping_total() : 0,
				'line_items'          => $this->get_receipt_line_items( $order ),
				'coupon_lines'        => $this->get_receipt_coupon_lines( $order ),
				'tax_lines'           => $this->get_receipt_tax_lines( $order ),
				'amount_captured'     => isset( $charge['amount_captured'] ) ? $this->interpret_minor_amount( (int) $charge['amount_captured'], (string) ( $charge['currency'] ?? $intent['currency'] ?? $order->get_currency() ) ) : $this->interpret_minor_amount( (int) ( $intent['amount'] ?? 0 ), (string) ( $intent['currency'] ?? $order->get_currency() ) ),
				'brand'               => isset( $card_present['brand'] ) ? (string) $card_present['brand'] : '',
				'last4'               => isset( $card_present['last4'] ) ? (string) $card_present['last4'] : '',
				'payment_method_name' => $this->get_terminal_card_display_name( $card_present ),
				'receipt'             => array(
					'application_preferred_name' => (string) ( $receipt['application_preferred_name'] ?? '' ),
					'dedicated_file_name'        => (string) ( $receipt['dedicated_file_name'] ?? '' ),
					'account_type'               => (string) ( $receipt['account_type'] ?? '' ),
				),
			)
		);
	}

	/**
	 * Get receipt line items.
	 *
	 * @param WC_Order $order Order.
	 * @return array<int,array<string,mixed>>
	 */
	private function get_receipt_line_items( WC_Order $order ): array {
		$items = array();
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$product       = $item->get_product();
			$quantity      = max( 1, (int) $item->get_quantity() );
			$line_subtotal = (float) $item->get_subtotal();
			$price         = $quantity > 0 ? $line_subtotal / $quantity : $line_subtotal;
			$regular_price = $product instanceof \WC_Product && '' !== $product->get_regular_price() ? (float) $product->get_regular_price() : $price;

			$items[] = array(
				'name'          => $item->get_name(),
				'quantity'      => $quantity,
				'subtotal'      => $line_subtotal,
				'product_id'    => $product instanceof \WC_Product ? $product->get_id() : $item->get_product_id(),
				'price'         => $price,
				'regular_price' => $regular_price,
			);
		}

		return $items;
	}

	/**
	 * Get receipt coupon lines.
	 *
	 * @param WC_Order $order Order.
	 * @return array<int,array<string,mixed>>
	 */
	private function get_receipt_coupon_lines( WC_Order $order ): array {
		$items = array();
		foreach ( $order->get_items( 'coupon' ) as $coupon ) {
			if ( ! $coupon instanceof \WC_Order_Item_Coupon ) {
				continue;
			}

			$items[] = array(
				'code'        => $coupon->get_code(),
				'description' => '',
				'discount'    => (float) $coupon->get_discount(),
			);
		}

		return $items;
	}

	/**
	 * Get receipt tax lines.
	 *
	 * @param WC_Order $order Order.
	 * @return array<int,array<string,mixed>>
	 */
	private function get_receipt_tax_lines( WC_Order $order ): array {
		$items = array();
		foreach ( $order->get_items( 'tax' ) as $tax ) {
			if ( ! $tax instanceof \WC_Order_Item_Tax ) {
				continue;
			}

			$items[] = array(
				'rate_percent'       => (float) $tax->get_rate_percent(),
				'tax_total'          => (float) $tax->get_tax_total(),
				'shipping_tax_total' => (float) $tax->get_shipping_tax_total(),
			);
		}

		return $items;
	}

	/**
	 * Render receipt HTML matching the preserved mobile printer contract.
	 *
	 * @param array<string,mixed> $data Receipt data.
	 * @return string
	 */
	private function render_receipt_html( array $data ): string {
		$currency = isset( $data['currency'] ) ? strtoupper( (string) $data['currency'] ) : get_woocommerce_currency();
		$receipt  = isset( $data['receipt'] ) && is_array( $data['receipt'] ) ? $data['receipt'] : array();
		$address  = isset( $data['support_address'] ) && is_array( $data['support_address'] ) ? $data['support_address'] : array();

		ob_start();
		?>
		<!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Print Receipt</title>
			<style>.receipt{min-width:130px;max-width:300px;margin:0 auto;text-align:center;font-family:SF Pro Text,sans-serif;font-size:10px}.receipt-table{width:100%;border-collapse:separate;border-spacing:0 2px;font-size:10px}.align-left{text-align:left}.align-right{text-align:right}.align-top{vertical-align:top}.receipt__header .title{font-size:14px;line-height:17px;margin:12px 0;font-weight:700}.receipt__transaction{line-height:2px}#powered_by{font-size:7px;padding-top:5px}</style>
		</head>
		<body>
			<div class="receipt">
				<div class="receipt__header">
					<h1 class="title"><?php echo esc_html( (string) ( $data['business_name'] ?? get_bloginfo( 'name' ) ) ); ?></h1>
					<hr />
					<div class="store">
						<div class="store__address">
							<p><?php echo esc_html( (string) ( $address['line1'] ?? '' ) ); ?></p>
							<p><?php echo esc_html( (string) ( $address['line2'] ?? '' ) ); ?></p>
							<p><?php echo esc_html( implode( ' ', array_filter( array( (string) ( $address['city'] ?? '' ), (string) ( $address['state'] ?? '' ), (string) ( $address['postal_code'] ?? '' ), (string) ( $address['country'] ?? '' ) ) ) ) ); ?></p>
							<?php echo esc_html( gmdate( 'Y/m/d - H:iA' ) ); ?>
						</div>
						<p class="store__contact"><?php echo esc_html( trim( (string) ( $data['support_phone'] ?? '' ) . ' ' . (string) ( $data['support_email'] ?? '' ) ) ); ?></p>
					</div>
					<div class="order">
						<p class="order__title"><?php printf( '%s %s', esc_html__( 'Order', 'woocommerce' ), esc_html( (string) ( $data['order_id'] ?? '' ) ) ); ?></p>
					</div>
				</div>
				<hr />
				<div class="receipt__products">
					<table class="receipt-table">
						<?php foreach ( $this->get_receipt_list( $data, 'line_items' ) as $item ) : ?>
							<tr>
								<td class="align-left">
									<div><?php echo esc_html( (string) ( $item['name'] ?? '' ) ); ?></div>
									<div><?php echo esc_html( (string) ( $item['quantity'] ?? '' ) ); ?> @ <?php echo wp_kses_post( $this->format_receipt_price( (float) ( $item['price'] ?? 0 ), (float) ( $item['regular_price'] ?? $item['price'] ?? 0 ), $currency ) ); ?></div>
									<div><?php printf( '%s: %s', esc_html__( 'SKU', 'woocommerce' ), esc_html( (string) ( $item['product_id'] ?? '' ) ) ); ?></div>
								</td>
								<td class="align-right align-top"><?php echo wp_kses_post( wc_price( (float) ( $item['subtotal'] ?? 0 ), array( 'currency' => $currency ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>
				<hr />
				<div class="receipt__subtotal">
					<table class="receipt-table">
						<tr><td class="align-left"><b><?php esc_html_e( 'SUBTOTAL', 'woocommerce' ); ?></b></td><td class="align-right"><b><?php echo wp_kses_post( wc_price( (float) ( $data['subtotal'] ?? 0 ), array( 'currency' => $currency ) ) ); ?></b></td></tr>
						<?php foreach ( $this->get_receipt_list( $data, 'coupon_lines' ) as $coupon ) : ?>
							<tr><td class="align-left"><div><?php printf( '%s: %s', esc_html__( 'Discount', 'woocommerce' ), esc_html( (string) ( $coupon['code'] ?? '' ) ) ); ?></div><div><?php echo esc_html( (string) ( $coupon['description'] ?? '' ) ); ?></div></td><td class="align-right align-top"><?php echo wp_kses_post( wc_price( abs( (float) ( $coupon['discount'] ?? 0 ) ) * -1, array( 'currency' => $currency ) ) ); ?></td></tr>
						<?php endforeach; ?>
						<?php if ( 0 < (float) ( $data['total_fees'] ?? 0 ) ) : ?>
							<tr><td class="align-left"><?php esc_html_e( 'Fees:', 'woocommerce' ); ?></td><td class="align-right align-top"><?php echo wp_kses_post( wc_price( (float) $data['total_fees'], array( 'currency' => $currency ) ) ); ?></td></tr>
						<?php endif; ?>
						<?php if ( 0 < (float) ( $data['shipping_tax'] ?? 0 ) ) : ?>
							<tr><td class="align-left"><?php esc_html_e( 'Shipping:', 'woocommerce' ); ?></td><td class="align-right align-top"><?php echo wp_kses_post( wc_price( (float) $data['shipping_tax'], array( 'currency' => $currency ) ) ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $this->get_receipt_list( $data, 'tax_lines' ) as $tax_line ) : ?>
							<tr><td class="align-left"><div><?php esc_html_e( 'Tax', 'woocommerce' ); ?></div><div><?php echo esc_html( (string) wc_round_tax_total( (float) ( $tax_line['rate_percent'] ?? 0 ) ) ); ?>%</div></td><td class="align-right align-top"><?php echo wp_kses_post( wc_price( (float) ( $tax_line['tax_total'] ?? 0 ) + (float) ( $tax_line['shipping_tax_total'] ?? 0 ), array( 'currency' => $currency ) ) ); ?></td></tr>
						<?php endforeach; ?>
						<tr><td class="align-left"><b><?php esc_html_e( 'TOTAL', 'woocommerce' ); ?></b></td><td class="align-right"><b><?php echo wp_kses_post( wc_price( (float) ( $data['total'] ?? 0 ), array( 'currency' => $currency ) ) ); ?></b></td></tr>
					</table>
				</div>
				<hr />
				<div class="receipt__amount-paid">
					<table class="receipt-table">
						<tr><td class="align-left"><b><?php esc_html_e( 'AMOUNT PAID', 'woocommerce' ); ?></b>:</td><td class="align-right"><b><?php echo wp_kses_post( wc_price( (float) ( $data['amount_captured'] ?? 0 ), array( 'currency' => $currency ) ) ); ?></b></td></tr>
						<tr><td colspan="2" class="align-left"><?php echo esc_html( sprintf( '%s - %s', (string) ( $data['payment_method_name'] ?? strtoupper( (string) ( $data['brand'] ?? '' ) ) ), (string) ( $data['last4'] ?? '' ) ) ); ?></td></tr>
					</table>
				</div>
				<hr />
				<div class="receipt__transaction">
					<p id="application-preferred-name"><?php printf( '%s: %s', esc_html__( 'Application name', 'woocommerce' ), esc_html( ucfirst( (string) ( $receipt['application_preferred_name'] ?? '' ) ) ) ); ?></p>
					<p id="dedicated-file-name"><?php printf( '%s: %s', esc_html__( 'AID', 'woocommerce' ), esc_html( ucfirst( (string) ( $receipt['dedicated_file_name'] ?? '' ) ) ) ); ?></p>
					<p id="account_type"><?php printf( '%s: %s', esc_html__( 'Account Type', 'woocommerce' ), esc_html( ucfirst( (string) ( $receipt['account_type'] ?? '' ) ) ) ); ?></p>
					<p id="powered_by"><?php esc_html_e( 'Powered by WooCommerce', 'woocommerce' ); ?></p>
				</div>
			</div>
		</body>
		</html>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Get a normalized receipt list from data.
	 *
	 * @param array<string,mixed> $data Receipt data.
	 * @param string              $key  List key.
	 * @return array<int,array<string,mixed>>
	 */
	private function get_receipt_list( array $data, string $key ): array {
		if ( ! isset( $data[ $key ] ) || ! is_array( $data[ $key ] ) ) {
			return array();
		}

		return $this->normalize_list( $data[ $key ] );
	}

	/**
	 * Format a receipt item price.
	 *
	 * @param float  $price         Active price.
	 * @param float  $regular_price Regular price.
	 * @param string $currency      Currency.
	 * @return string
	 */
	private function format_receipt_price( float $price, float $regular_price, string $currency ): string {
		if ( $price !== $regular_price ) {
			return '<s>' . wc_price( $regular_price, array( 'currency' => $currency ) ) . '</s> ' . wc_price( $price, array( 'currency' => $currency ) );
		}

		return wc_price( $price, array( 'currency' => $currency ) );
	}

	/**
	 * Get the terminal card display name.
	 *
	 * @param array<string,mixed> $card_present Card-present details.
	 * @return string
	 */
	private function get_terminal_card_display_name( array $card_present ): string {
		$brand = isset( $card_present['brand'] ) ? (string) $card_present['brand'] : '';

		return '' === $brand ? __( 'Card', 'woocommerce' ) : ucfirst( $brand );
	}

	/**
	 * Interpret a provider minor-unit amount.
	 *
	 * @param int    $amount   Minor-unit amount.
	 * @param string $currency Currency code.
	 * @return float
	 */
	private function interpret_minor_amount( int $amount, string $currency ): float {
		$zero_decimal = array( 'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'vnd', 'vuv', 'xaf', 'xof', 'xpf' );

		return in_array( strtolower( $currency ), $zero_decimal, true ) ? (float) $amount : (float) $amount / 100;
	}

	/**
	 * Get a string parameter from a request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @param string          $key     Parameter key.
	 * @return string
	 */
	private function get_request_string( WP_REST_Request $request, string $key ): string {
		$value = $request->get_param( $key );

		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * Tell whether a provider ID is safe for route interpolation.
	 *
	 * @param string $id Provider ID.
	 * @return bool
	 */
	private function is_valid_provider_id( string $id ): bool {
		return '' !== $id && (bool) preg_match( '/^[^\/\\\\%]+$/', $id );
	}

	/**
	 * Convert an API exception to a REST error.
	 *
	 * @param WooPaymentsApiException $exception API exception.
	 * @return WP_Error
	 */
	private function api_exception_to_wp_error( WooPaymentsApiException $exception ): WP_Error {
		return new WP_Error(
			'' !== $exception->get_error_code() ? $exception->get_error_code() : 'wcpay_api_error',
			$exception->getMessage(),
			array( 'status' => 0 < $exception->get_http_code() ? $exception->get_http_code() : 500 )
		);
	}

	/**
	 * Build a generic server error.
	 *
	 * @return WP_Error
	 */
	private function server_error(): WP_Error {
		return new WP_Error( 'wcpay_server_error', __( 'Unexpected server error', 'woocommerce' ), array( 'status' => 500 ) );
	}
}
