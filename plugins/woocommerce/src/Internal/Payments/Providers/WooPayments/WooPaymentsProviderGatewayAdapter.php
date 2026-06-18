<?php
/**
 * WooPaymentsProviderGatewayAdapter class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Internal\Payments\PaymentContext;
use Automattic\WooCommerce\Internal\Payments\PaymentOutcome;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Throwable;
use WC_Order;
use WP_Error;

/**
 * Normalizes WooPayments gateway operations to native payment outcomes.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsProviderGatewayAdapter {

	/**
	 * WooPayments legacy runtime.
	 *
	 * @var WooPaymentsLegacyRuntime
	 */
	private WooPaymentsLegacyRuntime $legacy_runtime;

	/**
	 * Native WooPayments API client.
	 *
	 * @var WooPaymentsApiClient
	 */
	private WooPaymentsApiClient $api_client;

	/**
	 * WooPayments customer service.
	 *
	 * @var WooPaymentsCustomerService
	 */
	private WooPaymentsCustomerService $customer_service;

	/**
	 * WooPayments token service.
	 *
	 * @var WooPaymentsTokenService
	 */
	private WooPaymentsTokenService $token_service;

	/**
	 * WooPayments account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

	/**
	 * WooPayments order data service.
	 *
	 * @var WooPaymentsOrderDataService|null
	 */
	private ?WooPaymentsOrderDataService $order_data_service = null;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsLegacyRuntime         $legacy_runtime    WooPayments legacy runtime.
	 * @param WooPaymentsApiClient             $api_client        Native WooPayments API client.
	 * @param WooPaymentsCustomerService       $customer_service  WooPayments customer service.
	 * @param WooPaymentsTokenService          $token_service     WooPayments token service.
	 * @param WooPaymentsAccountService        $account_service   WooPayments account service.
	 * @param WooPaymentsOrderDataService|null $order_data_service WooPayments order data service.
	 */
	final public function init( WooPaymentsLegacyRuntime $legacy_runtime, WooPaymentsApiClient $api_client, WooPaymentsCustomerService $customer_service, WooPaymentsTokenService $token_service, WooPaymentsAccountService $account_service, ?WooPaymentsOrderDataService $order_data_service = null ): void {
		$this->legacy_runtime     = $legacy_runtime;
		$this->api_client         = $api_client;
		$this->customer_service   = $customer_service;
		$this->token_service      = $token_service;
		$this->account_service    = $account_service;
		$this->order_data_service = $order_data_service;
	}

	/**
	 * Tell whether the legacy bridge can currently process operations.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		$gateway = $this->get_legacy_gateway();
		if ( ! is_object( $gateway ) ) {
			return false;
		}

		if ( is_callable( array( $gateway, 'is_available' ) ) ) {
			return (bool) $gateway->is_available();
		}

		return true;
	}

	/**
	 * Charge an order through the active WooPayments gateway.
	 *
	 * @since 11.0.0
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function charge( PaymentContext $context, string $idempotency_key ): PaymentOutcome {
		$order = $context->get_order();
		if ( $this->get_api_client()->is_available() ) {
			try {
				return 0.0 < (float) $order->get_total()
					? $this->charge_via_native_transport( $context, $idempotency_key )
					: $this->setup_intent_via_native_transport( $context, $idempotency_key );
			} catch ( WooPaymentsApiException $exception ) {
				return $this->failed_transport_outcome( 'charge', $exception );
			}
		}

		$gateway = $this->get_legacy_gateway();
		if ( ! is_object( $gateway ) || ! is_callable( array( $gateway, 'process_payment' ) ) ) {
			return $this->unavailable_outcome( 'charge' );
		}

		$result = $this->with_idempotency_key(
			$idempotency_key,
			static function () use ( $gateway, $context ) {
				return $gateway->process_payment( $context->get_order_id() );
			}
		);

		return $this->normalize_charge_result( is_array( $result ) ? $result : null, $context );
	}

	/**
	 * Refund an order through the active WooPayments gateway.
	 *
	 * @since 11.0.0
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function refund( PaymentContext $context, string $idempotency_key ): PaymentOutcome {
		$order = $context->get_order();
		if ( $this->get_api_client()->is_available() ) {
			$charge_id = (string) $order->get_meta( '_charge_id', true );
			if ( '' !== $charge_id ) {
				$payment_data = $context->get_payment_data();
				$amount       = isset( $payment_data['amount'] ) ? (float) $payment_data['amount'] : 0.0;
				$reason       = isset( $payment_data['reason'] ) ? (string) $payment_data['reason'] : '';

				try {
					$result = $this->get_api_client()->refund_charge(
						$charge_id,
						$this->prepare_amount( $amount, (string) $order->get_currency() ),
						$reason,
						'woocommerce_native',
						$idempotency_key
					);

					return $this->normalize_refund_result( $result, $context );
				} catch ( WooPaymentsApiException $exception ) {
					return $this->failed_transport_outcome( 'refund', $exception );
				}
			}
		}

		$gateway = $this->get_legacy_gateway();
		if ( ! is_object( $gateway ) || ! is_callable( array( $gateway, 'process_refund' ) ) ) {
			return $this->unavailable_outcome( 'refund' );
		}

		$payment_data = $context->get_payment_data();
		$amount       = isset( $payment_data['amount'] ) ? (float) $payment_data['amount'] : 0.0;
		$reason       = isset( $payment_data['reason'] ) ? (string) $payment_data['reason'] : '';
		$result       = $this->with_idempotency_key(
			$idempotency_key,
			static function () use ( $gateway, $context, $amount, $reason ) {
				return $gateway->process_refund( $context->get_order_id(), $amount, $reason );
			}
		);

		if ( true === $result ) {
			return new PaymentOutcome( PaymentOutcome::STATUS_COMPLETED );
		}

		if ( $result instanceof WP_Error ) {
			return new PaymentOutcome(
				PaymentOutcome::STATUS_FAILED,
				'',
				'',
				'',
				'',
				array(
					'error_code'    => $result->get_error_code(),
					'error_message' => $result->get_error_message(),
				)
			);
		}

		return new PaymentOutcome(
			PaymentOutcome::STATUS_FAILED,
			'',
			'',
			'',
			'',
			array( 'error_code' => 'legacy_refund_failed' )
		);
	}

	/**
	 * Capture an authorized payment through the active WooPayments gateway.
	 *
	 * @since 11.0.0
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function capture( PaymentContext $context, string $idempotency_key ): PaymentOutcome {
		$order = $context->get_order();
		if ( $this->get_api_client()->is_available() ) {
			$intent_id = $this->get_order_intent_id( $order );
			if ( '' !== $intent_id ) {
				try {
					$result = $this->get_api_client()->capture_intention(
						$intent_id,
						$this->prepare_amount( (float) $order->get_total(), (string) $order->get_currency() ),
						array()
					);

					return $this->normalize_capture_result( $result, $context );
				} catch ( WooPaymentsApiException $exception ) {
					return $this->failed_transport_outcome( 'capture', $exception, $context );
				}
			}
		}

		$gateway = $this->get_legacy_gateway();
		if ( ! is_object( $gateway ) || ! is_callable( array( $gateway, 'capture_charge' ) ) ) {
			return $this->unavailable_outcome( 'capture' );
		}

		$result = $this->with_idempotency_key(
			$idempotency_key,
			static function () use ( $gateway, $context ) {
				return $gateway->capture_charge( $context->get_order() );
			}
		);

		return $this->normalize_capture_result( is_array( $result ) ? $result : array(), $context );
	}

	/**
	 * Cancel an authorized payment through the active WooPayments gateway.
	 *
	 * @since 11.0.0
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function cancel( PaymentContext $context, string $idempotency_key ): PaymentOutcome {
		$order = $context->get_order();
		if ( $this->get_api_client()->is_available() ) {
			$intent_id = $this->get_order_intent_id( $order );
			if ( '' !== $intent_id ) {
				try {
					$result = $this->get_api_client()->cancel_intention( $intent_id );

					return $this->normalize_cancel_result( $result );
				} catch ( WooPaymentsApiException $exception ) {
					return $this->failed_transport_outcome( 'cancel', $exception );
				}
			}
		}

		$gateway = $this->get_legacy_gateway();
		if ( ! is_object( $gateway ) || ! is_callable( array( $gateway, 'cancel_authorization' ) ) ) {
			return $this->unavailable_outcome( 'cancel' );
		}

		$result = $this->with_idempotency_key(
			$idempotency_key,
			static function () use ( $gateway, $context ) {
				return $gateway->cancel_authorization( $context->get_order() );
			}
		);

		return $this->normalize_cancel_result( is_array( $result ) ? $result : array() );
	}

	/**
	 * Get the PaymentIntent id stored on an order.
	 *
	 * @param WC_Order $order Order.
	 * @return string
	 */
	private function get_order_intent_id( WC_Order $order ): string {
		$intent_id = (string) $order->get_transaction_id();
		if ( '' === $intent_id ) {
			$intent_id = (string) $order->get_meta( '_intent_id', true );
		}

		return $intent_id;
	}

	/**
	 * Run a legacy gateway operation with a scoped WooPayments API idempotency key.
	 *
	 * @param string   $idempotency_key Deterministic idempotency key.
	 * @param callable $operation       Operation callback.
	 * @return mixed
	 */
	private function with_idempotency_key( string $idempotency_key, callable $operation ) {
		$idempotency_filter = static function ( $params, $api = '', $method = '' ) use ( $idempotency_key ) {
			unset( $api );

			if ( '' === $idempotency_key || ! is_array( $params ) || in_array( strtoupper( (string) $method ), array( 'GET', 'DELETE' ), true ) ) {
				return $params;
			}

			$params['idempotency_key'] = $idempotency_key;

			return $params;
		};

		add_filter( 'wcpay_api_request_params', $idempotency_filter, 10, 3 );

		try {
			return $operation();
		} finally {
			remove_filter( 'wcpay_api_request_params', $idempotency_filter, 10 );
		}
	}

	/**
	 * Get the active legacy WooPayments gateway.
	 *
	 * @return object|null
	 */
	private function get_legacy_gateway(): ?object {
		return $this->legacy_runtime->get_gateway();
	}

	/**
	 * Get the native API client.
	 *
	 * @return WooPaymentsApiClient
	 */
	private function get_api_client(): WooPaymentsApiClient {
		return $this->api_client;
	}

	/**
	 * Get the WooPayments customer service.
	 *
	 * @return WooPaymentsCustomerService
	 */
	private function get_customer_service(): WooPaymentsCustomerService {
		return $this->customer_service;
	}

	/**
	 * Get the WooPayments token service.
	 *
	 * @return WooPaymentsTokenService
	 */
	private function get_token_service(): WooPaymentsTokenService {
		return $this->token_service;
	}

	/**
	 * Get the WooPayments account service.
	 *
	 * @return WooPaymentsAccountService
	 */
	private function get_account_service(): WooPaymentsAccountService {
		return $this->account_service;
	}

	/**
	 * Get the WooPayments order data service.
	 *
	 * @return WooPaymentsOrderDataService
	 */
	private function get_order_data_service(): WooPaymentsOrderDataService {
		if ( null === $this->order_data_service ) {
			$this->order_data_service = wc_get_container()->get( WooPaymentsOrderDataService::class );
		}

		return $this->order_data_service;
	}

	/**
	 * Charge an order through the native WooPayments transport.
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 * @throws WooPaymentsApiException When the provider request fails.
	 */
	private function charge_via_native_transport( PaymentContext $context, string $idempotency_key ): PaymentOutcome {
		$order              = $context->get_order();
		$payment_credential = $this->get_payment_credential( $context );

		if ( '' === $payment_credential ) {
			return new PaymentOutcome(
				PaymentOutcome::STATUS_FAILED,
				'',
				'',
				'',
				'',
				array( 'error_code' => 'wcpay_missing_payment_credential' )
			);
		}

		$customer_id  = $this->get_customer_service()->get_or_create_customer_id_for_order( $order );
		$request_data = $this->build_native_charge_request_data( $context, $payment_credential, $customer_id );

		try {
			$result = $this->get_api_client()->create_and_confirm_payment_intention( $request_data, $idempotency_key );
		} catch ( WooPaymentsApiException $exception ) {
			if ( ! $this->is_missing_customer_exception( $exception ) ) {
				throw $exception;
			}

			$customer_id              = $this->get_customer_service()->recreate_customer_for_order( $order );
			$request_data['customer'] = $customer_id;
			$result                   = $this->get_api_client()->create_and_confirm_payment_intention( $request_data, $idempotency_key );
		}

		$this->apply_payment_method_display_details( $order, $result );

		return $this->normalize_native_charge_result( $result, $context, $customer_id );
	}

	/**
	 * Create or confirm a zero-amount setup intent through the native WooPayments transport.
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 * @throws WooPaymentsApiException When the provider request fails.
	 */
	private function setup_intent_via_native_transport( PaymentContext $context, string $idempotency_key ): PaymentOutcome {
		$order              = $context->get_order();
		$payment_credential = $this->get_payment_credential( $context );

		if ( '' === $payment_credential ) {
			return new PaymentOutcome(
				PaymentOutcome::STATUS_FAILED,
				'',
				'',
				'',
				'',
				array( 'error_code' => 'wcpay_missing_payment_credential' )
			);
		}

		$customer_id  = $this->get_customer_service()->get_or_create_customer_id_for_order( $order );
		$request_data = $this->build_native_setup_intent_request_data( $context, $payment_credential, $customer_id );

		try {
			$result = $this->is_confirmation_token( $payment_credential )
				? $this->get_api_client()->create_setup_intention( $request_data, $idempotency_key )
				: $this->get_api_client()->create_and_confirm_setup_intention( $request_data, $idempotency_key );
		} catch ( WooPaymentsApiException $exception ) {
			if ( ! $this->is_missing_customer_exception( $exception ) ) {
				throw $exception;
			}

			$customer_id              = $this->get_customer_service()->recreate_customer_for_order( $order );
			$request_data['customer'] = $customer_id;
			$result                   = $this->is_confirmation_token( $payment_credential )
				? $this->get_api_client()->create_setup_intention( $request_data, $idempotency_key )
				: $this->get_api_client()->create_and_confirm_setup_intention( $request_data, $idempotency_key );
		}

		return $this->normalize_native_setup_intent_result( $result, $context, $customer_id );
	}

	/**
	 * Build the native WooPayments charge request payload.
	 *
	 * @param PaymentContext $context            Payment context.
	 * @param string         $payment_credential Payment method or confirmation token.
	 * @param string         $customer_id        Customer ID.
	 * @return array<string,mixed>
	 */
	private function build_native_charge_request_data( PaymentContext $context, string $payment_credential, string $customer_id ): array {
		$order                = $context->get_order();
		$payment_data         = $context->get_payment_data();
		$provider_data        = $context->get_provider_data();
		$is_renewal           = ! empty( $provider_data['scheduled_subscription_payment'] );
		$is_recurring         = $is_renewal || $this->is_recurring_payment( $order );
		$payment_type         = $is_recurring ? 'recurring' : 'single';
		$subscription_payment = $is_renewal ? 'renewal' : ( $is_recurring ? 'initial' : 'no' );
		$request_data         = array(
			'amount'               => $this->prepare_amount( (float) $order->get_total(), (string) $order->get_currency() ),
			'capture_method'       => ! $is_renewal && 'yes' === $this->get_account_service()->get_gateway_setting( 'manual_capture', 'no' ) ? 'manual' : 'automatic',
			'currency'             => strtolower( (string) $order->get_currency() ),
			'customer'             => $customer_id,
			'metadata'             => $this->build_order_metadata( $order, $payment_type, $subscription_payment ),
			'payment_method_types' => $this->get_payment_method_types_for_request( $provider_data, (string) $order->get_currency() ),
		);

		if ( $this->is_confirmation_token( $payment_credential ) ) {
			$request_data['confirmation_token'] = $payment_credential;
		} else {
			$request_data['payment_method'] = $payment_credential;
		}

		if ( ! empty( $provider_data['cvc_confirmation'] ) ) {
			$request_data['cvc_confirmation'] = (string) $provider_data['cvc_confirmation'];
		}

		if ( $is_renewal ) {
			$request_data['off_session'] = true;
			$renewal_mandate             = isset( $provider_data['renewal_mandate'] ) ? (string) $provider_data['renewal_mandate'] : '';
			if ( '' !== $renewal_mandate ) {
				$request_data['mandate'] = $renewal_mandate;
			}
		}

		if ( ! $is_renewal && ( ! empty( $payment_data['save_payment_method'] ) || $is_recurring ) ) {
			$request_data['setup_future_usage'] = 'off_session';
		}

		if ( $this->is_using_saved_payment_token( $payment_data ) && ! preg_match( '/^(card_|src_)/', $payment_credential ) ) {
			$billing_details = $this->get_order_data_service()->get_billing_data_from_order( $order );
			if ( ! empty( $billing_details ) ) {
				$request_data['payment_method_update_data'] = array(
					'billing_details' => $billing_details,
				);
			}
		}

		return WooPaymentsPlatformPaymentMethodContext::from_provider_data( $provider_data )->apply_to_request_data( $request_data );
	}

	/**
	 * Build the native WooPayments setup-intent request payload.
	 *
	 * @param PaymentContext $context            Payment context.
	 * @param string         $payment_credential Payment method or confirmation token.
	 * @param string         $customer_id        Customer ID.
	 * @return array<string,mixed>
	 */
	private function build_native_setup_intent_request_data( PaymentContext $context, string $payment_credential, string $customer_id ): array {
		$payment_type         = $this->is_recurring_payment( $context->get_order() ) ? 'recurring' : 'single';
		$subscription_payment = 'recurring' === $payment_type ? 'initial' : 'no';
		$request_data         = array(
			'customer'             => $customer_id,
			'metadata'             => $this->build_order_metadata( $context->get_order(), $payment_type, $subscription_payment ),
			'payment_method_types' => $this->get_payment_method_types_for_request( $context->get_provider_data(), (string) $context->get_order()->get_currency() ),
		);

		if ( ! $this->is_confirmation_token( $payment_credential ) ) {
			$request_data['payment_method'] = $payment_credential;
		}

		return WooPaymentsPlatformPaymentMethodContext::from_provider_data( $context->get_provider_data() )->apply_to_request_data( $request_data );
	}

	/**
	 * Get Stripe payment method types for a native WCPay request.
	 *
	 * @param array<string,mixed> $provider_data WooPayments provider data.
	 * @param string              $currency      Order currency.
	 * @return array<int,string>
	 */
	private function get_payment_method_types_for_request( array $provider_data, string $currency ): array {
		$submitted_types = $provider_data[ WooPaymentsExpressPaymentMethodTypes::PROVIDER_DATA_KEY ] ?? array();
		$context         = isset( $provider_data[ WooPaymentsExpressPaymentMethodTypes::PROVIDER_CONTEXT_KEY ] ) && is_scalar( $provider_data[ WooPaymentsExpressPaymentMethodTypes::PROVIDER_CONTEXT_KEY ] )
			? (string) $provider_data[ WooPaymentsExpressPaymentMethodTypes::PROVIDER_CONTEXT_KEY ]
			: 'checkout';
		$allowed_types   = WooPaymentsExpressPaymentMethodTypes::get_allowed_payment_method_types_for_account( $this->get_account_service(), $context, $currency );
		$validated_types = WooPaymentsExpressPaymentMethodTypes::validate_submitted_payment_method_types( $submitted_types, $allowed_types );

		return empty( $validated_types ) ? array( WooPaymentsExpressPaymentMethodTypes::STRIPE_TYPE_CARD ) : $validated_types;
	}

	/**
	 * Build the WooPayments metadata payload for an order.
	 *
	 * @param WC_Order $order                Order being charged.
	 * @param string   $payment_type         Payment type slug.
	 * @param string   $subscription_payment Subscription payment type.
	 * @return array<string,mixed>
	 */
	private function build_order_metadata( WC_Order $order, string $payment_type = 'single', string $subscription_payment = 'no' ): array {
		$payment_type         = 'recurring' === $payment_type ? 'recurring' : 'single';
		$subscription_payment = in_array( $subscription_payment, array( 'initial', 'renewal' ), true ) ? $subscription_payment : 'no';
		$metadata             = array(
			'customer_name'        => trim( sanitize_text_field( $order->get_billing_first_name() ) . ' ' . sanitize_text_field( $order->get_billing_last_name() ) ),
			'customer_email'       => sanitize_email( $order->get_billing_email() ),
			'site_url'             => esc_url( get_site_url() ),
			'order_id'             => $order->get_id(),
			'order_number'         => $order->get_order_number(),
			'order_key'            => $order->get_order_key(),
			'payment_type'         => $payment_type,
			'checkout_type'        => $order->get_created_via(),
			'client_version'       => defined( 'WC_VERSION' ) ? WC_VERSION : '',
			'subscription_payment' => $subscription_payment,
		);

		if ( 'no' !== $subscription_payment ) {
			$metadata['payment_context'] = 'regular_subscription';
		}

		/**
		 * Filters the WooPayments metadata created from an order.
		 *
		 * @since 11.0.0
		 *
		 * @param array<string,mixed> $metadata Metadata being sent to WooPayments.
		 * @param WC_Order            $order    Order object.
		 * @param string              $payment_type Payment type slug.
		 */
		$metadata = apply_filters( 'wcpay_metadata_from_order', $metadata, $order, $payment_type );

		return is_array( $metadata ) ? $metadata : array();
	}

	/**
	 * Apply payment-method details to the order before completion or customer action.
	 *
	 * @param WC_Order            $order  Order being charged.
	 * @param array<string,mixed> $result Native PaymentIntent response.
	 */
	private function apply_payment_method_display_details( WC_Order $order, array $result ): void {
		$charge                 = $this->get_latest_charge( $result );
		$payment_method_details = is_array( $charge['payment_method_details'] ?? null ) ? $charge['payment_method_details'] : array();
		$wallet_type            = $payment_method_details['card']['wallet']['type'] ?? null;

		if ( ! empty( $payment_method_details ) ) {
			$encoded_payment_method_details = wp_json_encode( $payment_method_details );
			if ( false !== $encoded_payment_method_details ) {
				$order->update_meta_data( '_wcpay_payment_method_details', $encoded_payment_method_details );
			}
		}

		if ( 'link' !== $wallet_type && isset( $payment_method_details['card']['last4'] ) ) {
			$order->update_meta_data( 'last4', (string) $payment_method_details['card']['last4'] );
			if ( isset( $payment_method_details['card']['brand'] ) ) {
				$order->update_meta_data( '_card_brand', (string) $payment_method_details['card']['brand'] );
			}
		}

		if ( is_string( $wallet_type ) && '' !== $wallet_type ) {
			$order->update_meta_data( '_wcpay_express_checkout_payment_method', $wallet_type );
		}

		$order->set_payment_method_title( $this->get_payment_method_title( $payment_method_details ) );
		$order->save();
	}

	/**
	 * Normalize a native PaymentIntent response to a neutral payment outcome.
	 *
	 * @param array<string,mixed> $result              Native PaymentIntent response.
	 * @param PaymentContext      $context             Payment context.
	 * @param string              $fallback_customer_id Customer ID used in the request.
	 * @return PaymentOutcome
	 */
	private function normalize_native_charge_result( array $result, PaymentContext $context, string $fallback_customer_id ): PaymentOutcome {
		$status            = isset( $result['status'] ) ? (string) $result['status'] : '';
		$intent_id         = isset( $result['id'] ) ? (string) $result['id'] : '';
		$client_secret     = isset( $result['client_secret'] ) ? (string) $result['client_secret'] : '';
		$charge            = $this->get_latest_charge( $result );
		$charge_id         = isset( $charge['id'] ) ? (string) $charge['id'] : '';
		$payment_method_id = isset( $result['payment_method'] ) ? (string) $result['payment_method'] : ( isset( $charge['payment_method'] ) ? (string) $charge['payment_method'] : $this->get_payment_credential( $context ) );
		$customer_id       = isset( $result['customer'] ) ? (string) $result['customer'] : $fallback_customer_id;
		$meta              = array(
			'_wcpay_intent_currency' => isset( $result['currency'] ) ? (string) $result['currency'] : (string) $context->get_order()->get_currency(),
			'_wcpay_mode'            => $this->get_account_service()->get_mode(),
		);

		if ( '' !== $charge_id ) {
			$meta['_charge_id'] = $charge_id;
		}

		$balance_transaction_id = $this->get_balance_transaction_id( $charge['balance_transaction'] ?? null );
		if ( '' !== $balance_transaction_id ) {
			$meta['_wcpay_payment_transaction_id'] = $balance_transaction_id;
		}

		if ( isset( $charge['outcome']['risk_level'] ) ) {
			$meta['_charge_risk_level'] = (string) $charge['outcome']['risk_level'];
		}

		if ( 'succeeded' === $status ) {
			$meta = array_merge( $meta, $this->get_completed_charge_order_meta( $result, $charge, $context->get_order() ) );
		}

		if ( $this->is_authorized_native_intent_status( $status ) ) {
			$this->maybe_attach_saved_payment_token_to_order( $context, $payment_method_id, $customer_id );
			$token_save_failure = $this->maybe_save_new_card_token_to_order( $context, $payment_method_id, $customer_id );
			if ( null !== $token_save_failure ) {
				return $token_save_failure;
			}
		}

		$outcome_data = array( 'meta' => $meta );
		if ( '' !== $charge_id ) {
			$outcome_data['charge_id'] = $charge_id;
		}

		if ( 'succeeded' === $status ) {
			$outcome_data['note'] = $this->get_payment_success_note( $context->get_order(), $intent_id, $charge_id, $balance_transaction_id );
		}

		switch ( $status ) {
			case 'succeeded':
				return new PaymentOutcome( PaymentOutcome::STATUS_COMPLETED, $intent_id, '', $payment_method_id, $customer_id, $outcome_data );

			case 'requires_capture':
				return new PaymentOutcome( PaymentOutcome::STATUS_AUTHORIZED, $intent_id, '', $payment_method_id, $customer_id, $outcome_data );

			case 'processing':
				return new PaymentOutcome( PaymentOutcome::STATUS_PENDING_ASYNC, $intent_id, '', $payment_method_id, $customer_id, $outcome_data );

			case 'requires_action':
			case 'requires_confirmation':
				return new PaymentOutcome(
					PaymentOutcome::STATUS_REQUIRES_CUSTOMER_ACTION,
					$intent_id,
					$this->build_confirmation_redirect( $context->get_order(), $client_secret ),
					$payment_method_id,
					$customer_id,
					$outcome_data
				);

			case 'canceled':
				return new PaymentOutcome( PaymentOutcome::STATUS_CANCELED, $intent_id, '', $payment_method_id, $customer_id, $outcome_data );
		}

		$error                         = is_array( $result['last_payment_error'] ?? null ) ? $result['last_payment_error'] : array();
		$outcome_data['error_code']    = isset( $error['code'] ) ? (string) $error['code'] : 'wcpay_native_charge_failed';
		$outcome_data['error_message'] = isset( $error['message'] ) ? (string) $error['message'] : '';

		return new PaymentOutcome(
			PaymentOutcome::STATUS_FAILED,
			$intent_id,
			'',
			$payment_method_id,
			$customer_id,
			$outcome_data
		);
	}

	/**
	 * Normalize a native SetupIntent response to a neutral payment outcome.
	 *
	 * @param array<string,mixed> $result              Native SetupIntent response.
	 * @param PaymentContext      $context             Payment context.
	 * @param string              $fallback_customer_id Customer ID used in the request.
	 * @return PaymentOutcome
	 */
	private function normalize_native_setup_intent_result( array $result, PaymentContext $context, string $fallback_customer_id ): PaymentOutcome {
		$status             = isset( $result['status'] ) ? (string) $result['status'] : '';
		$setup_intent_id    = isset( $result['id'] ) ? (string) $result['id'] : '';
		$client_secret      = isset( $result['client_secret'] ) ? (string) $result['client_secret'] : '';
		$payment_method_id  = $this->get_result_payment_method_id( $result );
		$customer_id        = isset( $result['customer'] ) ? (string) $result['customer'] : $fallback_customer_id;
		$payment_credential = $this->get_payment_credential( $context );
		$confirmation_token = $this->is_confirmation_token( $payment_credential ) ? $payment_credential : '';
		$meta               = array(
			'_wcpay_intent_currency' => (string) $context->get_order()->get_currency(),
			'_wcpay_mode'            => $this->get_account_service()->get_mode(),
		);

		if ( '' === $payment_method_id && ! $this->is_confirmation_token( $payment_credential ) ) {
			$payment_method_id = $payment_credential;
		}

		if ( 'succeeded' === $status ) {
			$this->maybe_attach_saved_payment_token_to_order( $context, $payment_method_id, $customer_id );
			$token_save_failure = $this->maybe_save_new_card_token_to_order( $context, $payment_method_id, $customer_id );
			if ( null !== $token_save_failure ) {
				return $token_save_failure;
			}
		}

		$this->persist_setup_intent_details( $context->get_order(), $setup_intent_id, $payment_method_id, $customer_id, $meta );

		switch ( $status ) {
			case 'succeeded':
				return new PaymentOutcome( PaymentOutcome::STATUS_COMPLETED, $setup_intent_id, '', $payment_method_id, $customer_id, array( 'meta' => $meta ) );

			case 'requires_action':
			case 'requires_confirmation':
				return new PaymentOutcome(
					PaymentOutcome::STATUS_REQUIRES_CUSTOMER_ACTION,
					$setup_intent_id,
					$this->build_confirmation_redirect( $context->get_order(), $client_secret, 'si', $confirmation_token ),
					$payment_method_id,
					$customer_id,
					array( 'meta' => $meta )
				);

			case 'processing':
				return new PaymentOutcome( PaymentOutcome::STATUS_PENDING_ASYNC, $setup_intent_id, '', $payment_method_id, $customer_id, array( 'meta' => $meta ) );

			case 'canceled':
				return new PaymentOutcome( PaymentOutcome::STATUS_CANCELED, $setup_intent_id, '', $payment_method_id, $customer_id, array( 'meta' => $meta ) );
		}

		$error = is_array( $result['last_setup_error'] ?? null ) ? $result['last_setup_error'] : array();

		return new PaymentOutcome(
			PaymentOutcome::STATUS_FAILED,
			$setup_intent_id,
			'',
			$payment_method_id,
			$customer_id,
			array(
				'meta'          => $meta,
				'error_code'    => isset( $error['code'] ) ? (string) $error['code'] : 'wcpay_native_setup_intent_failed',
				'error_message' => isset( $error['message'] ) ? (string) $error['message'] : '',
			)
		);
	}

	/**
	 * Get a payment method ID from a provider intent response.
	 *
	 * @param array<string,mixed> $result Intent response.
	 * @return string
	 */
	private function get_result_payment_method_id( array $result ): string {
		if ( isset( $result['payment_method'] ) && is_string( $result['payment_method'] ) ) {
			return $result['payment_method'];
		}

		if ( isset( $result['payment_method'] ) && is_array( $result['payment_method'] ) && isset( $result['payment_method']['id'] ) ) {
			return (string) $result['payment_method']['id'];
		}

		return '';
	}

	/**
	 * Persist setup-intent details needed by the post-authentication AJAX callback.
	 *
	 * @param WC_Order             $order             Order being charged.
	 * @param string               $setup_intent_id   SetupIntent ID.
	 * @param string               $payment_method_id Payment method ID.
	 * @param string               $customer_id       Customer ID.
	 * @param array<string,string> $meta              SetupIntent meta.
	 */
	private function persist_setup_intent_details( WC_Order $order, string $setup_intent_id, string $payment_method_id, string $customer_id, array $meta ): void {
		if ( '' !== $setup_intent_id ) {
			$order->set_transaction_id( $setup_intent_id );
			$order->update_meta_data( '_intent_id', $setup_intent_id );
		}

		if ( '' !== $payment_method_id ) {
			$order->update_meta_data( '_payment_method_id', $payment_method_id );
		}

		if ( '' !== $customer_id ) {
			$order->update_meta_data( '_stripe_customer_id', $customer_id );
		}

		foreach ( $meta as $key => $value ) {
			$order->update_meta_data( $key, $value );
		}

		$order->save();
	}

	/**
	 * Get the submitted payment method or saved payment token.
	 *
	 * @param PaymentContext $context Payment context.
	 * @return string
	 */
	private function get_payment_credential( PaymentContext $context ): string {
		$payment_data  = $context->get_payment_data();
		$payment_token = isset( $payment_data['payment_token'] ) ? (string) $payment_data['payment_token'] : '';

		if ( '' !== $payment_token && 'new' !== $payment_token ) {
			if ( ! empty( $context->get_provider_data()['scheduled_subscription_payment'] ) ) {
				return $this->get_token_service()->resolve_payment_method_id_from_order_token_id( $payment_token, $context->get_order() );
			}

			return $this->get_token_service()->resolve_payment_method_id_from_token_id( $payment_token, $context->get_order()->get_user_id() );
		}

		return $context->get_payment_method_id();
	}

	/**
	 * Tell whether payment data represents an existing saved WooCommerce token.
	 *
	 * @param array<string,mixed> $payment_data Payment data.
	 * @return bool
	 */
	private function is_using_saved_payment_token( array $payment_data ): bool {
		$payment_token = isset( $payment_data['payment_token'] ) ? (string) $payment_data['payment_token'] : '';

		return '' !== $payment_token && 'new' !== $payment_token;
	}

	/**
	 * Attach an existing saved token to the order.
	 *
	 * @param PaymentContext $context           Payment context.
	 * @param string         $payment_method_id Provider payment method ID.
	 * @param string         $customer_id       WooPayments customer ID.
	 */
	private function maybe_attach_saved_payment_token_to_order( PaymentContext $context, string $payment_method_id, string $customer_id ): void {
		$payment_data = $context->get_payment_data();
		if ( ! $this->is_using_saved_payment_token( $payment_data ) ) {
			return;
		}

		$payment_token_id = isset( $payment_data['payment_token'] ) ? (string) $payment_data['payment_token'] : '';
		$token            = $this->get_token_service()->get_valid_token_from_token_id( $payment_token_id, $context->get_order()->get_user_id() );
		if ( null === $token ) {
			return;
		}

		$order = $context->get_order();
		$this->get_token_service()->attach_token_to_order( $order, $token );
		$this->get_token_service()->sync_related_subscriptions_payment_token( $order, $token, $payment_method_id, $customer_id );
	}

	/**
	 * Save a newly used card to the customer and attach it to the order.
	 *
	 * @param PaymentContext $context           Payment context.
	 * @param string         $payment_method_id Provider payment method ID.
	 * @param string         $customer_id       WooPayments customer ID.
	 */
	private function maybe_save_new_card_token_to_order( PaymentContext $context, string $payment_method_id, string $customer_id ): ?PaymentOutcome {
		$payment_data = $context->get_payment_data();
		$order        = $context->get_order();
		$is_recurring = $this->is_recurring_payment( $order );
		if ( $this->is_using_saved_payment_token( $payment_data ) || ( empty( $payment_data['save_payment_method'] ) && ! $is_recurring ) ) {
			return null;
		}

		if ( '' === $payment_method_id || 0 >= $order->get_user_id() ) {
			return $is_recurring ? $this->recurring_token_save_failed_outcome() : null;
		}

		try {
			$token = $this->get_token_service()->get_or_create_card_token_for_user( $payment_method_id, $order->get_user_id() );
			if ( null !== $token ) {
				$this->get_token_service()->attach_token_to_order( $order, $token );
				$this->get_token_service()->sync_related_subscriptions_payment_token( $order, $token, $payment_method_id, $customer_id );

				return null;
			}
		} catch ( Throwable $exception ) {
			$this->log_token_save_error( $payment_method_id, $exception );

			return $is_recurring ? $this->recurring_token_save_failed_outcome() : null;
		}

		return $is_recurring ? $this->recurring_token_save_failed_outcome() : null;
	}

	/**
	 * Tell whether this payment must persist a token for a recurring order.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool
	 */
	private function is_recurring_payment( WC_Order $order ): bool {
		$is_recurring = false;
		if ( function_exists( 'wcs_order_contains_subscription' ) ) {
			$is_recurring = (bool) wcs_order_contains_subscription( $order->get_id() );
		}

		if ( ! $is_recurring && function_exists( 'wcs_order_contains_renewal' ) ) {
			$is_recurring = (bool) wcs_order_contains_renewal( $order->get_id() );
		}

		/**
		 * Filters whether a native WooPayments payment requires saved-token persistence.
		 *
		 * @since 11.0.0
		 *
		 * @param bool     $is_recurring Whether the order requires saved-token persistence.
		 * @param WC_Order $order        Order object.
		 */
		return (bool) apply_filters( 'woocommerce_native_woopayments_is_recurring_payment', $is_recurring, $order );
	}

	/**
	 * Build the recurring token-save failure outcome.
	 *
	 * @return PaymentOutcome
	 */
	private function recurring_token_save_failed_outcome(): PaymentOutcome {
		return new PaymentOutcome(
			PaymentOutcome::STATUS_FAILED,
			'',
			'',
			'',
			'',
			array(
				'error_code'    => 'wcpay_recurring_token_save_failed',
				'error_message' => __( 'Unable to save payment method for subscription. Please try again or use a different payment method.', 'woocommerce' ),
			)
		);
	}

	/**
	 * Tell whether a native intent status represents an authorized payment.
	 *
	 * @param string $status Intent status.
	 * @return bool
	 */
	private function is_authorized_native_intent_status( string $status ): bool {
		return in_array( $status, array( 'succeeded', 'requires_capture', 'processing' ), true );
	}

	/**
	 * Log a non-fatal token save error.
	 *
	 * @param string    $payment_method_id Provider payment method ID.
	 * @param Throwable $exception         Token save exception.
	 */
	private function log_token_save_error( string $payment_method_id, Throwable $exception ): void {
		$logger = $this->legacy_runtime->get_logger();
		if ( ! is_object( $logger ) || ! is_callable( array( $logger, 'error' ) ) ) {
			return;
		}

		$logger->error(
			sprintf(
				'Error saving WooPayments payment method %s: %s',
				$payment_method_id,
				$exception->getMessage()
			),
			array(
				'source' => 'payment-info',
			)
		);
	}

	/**
	 * Tell whether a credential is a Stripe confirmation token.
	 *
	 * @param string $payment_credential Credential value.
	 * @return bool
	 */
	private function is_confirmation_token( string $payment_credential ): bool {
		return 0 === strpos( $payment_credential, 'ctoken_' );
	}

	/**
	 * Tell whether a transport exception represents a missing customer.
	 *
	 * @param WooPaymentsApiException $exception Native transport exception.
	 * @return bool
	 */
	private function is_missing_customer_exception( WooPaymentsApiException $exception ): bool {
		return 'resource_missing' === $exception->get_error_code()
			&& false !== strpos( strtolower( $exception->getMessage() ), 'customer' );
	}

	/**
	 * Get the latest charge array from a PaymentIntent response.
	 *
	 * @param array<string,mixed> $result Native PaymentIntent response.
	 * @return array<string,mixed>
	 */
	private function get_latest_charge( array $result ): array {
		$charges = isset( $result['charges']['data'] ) && is_array( $result['charges']['data'] ) ? $result['charges']['data'] : array();
		$charge  = empty( $charges ) ? array() : end( $charges );

		return is_array( $charge ) ? $charge : array();
	}

	/**
	 * Get legacy-compatible order meta for a completed native charge.
	 *
	 * @param array<string,mixed> $intent Native PaymentIntent response.
	 * @param array<string,mixed> $charge Native Charge response.
	 * @param WC_Order            $order  Order being charged.
	 * @return array<string,string>
	 */
	private function get_completed_charge_order_meta( array $intent, array $charge, WC_Order $order ): array {
		$meta = array();

		$transaction_fee = $this->get_transaction_fee_from_charge( $intent, $charge );
		if ( '' !== $transaction_fee ) {
			$meta['_wcpay_transaction_fee'] = $transaction_fee;
		}

		$net = $this->get_net_from_charge( $intent, $charge, $transaction_fee );
		if ( '' !== $net ) {
			$meta['_wcpay_net'] = $net;
		}

		$meta = array_merge(
			$meta,
			$this->get_order_data_service()->get_settlement_exchange_rate_order_meta(
				$order,
				$charge,
				$this->get_account_service()->get_account_default_currency()
			)
		);

		$meta = array_merge( $meta, $this->get_fraud_outcome_order_meta( $intent, $charge ) );

		return $meta;
	}

	/**
	 * Get lifecycle meta from a completed capture response.
	 *
	 * @param array<string,mixed> $intent Native PaymentIntent response.
	 * @param WC_Order            $order  Order being captured.
	 * @return array<string,string>
	 */
	private function get_completed_capture_order_meta( array $intent, WC_Order $order ): array {
		$charge = $this->get_latest_charge( $intent );
		if ( empty( $charge ) ) {
			return array();
		}

		$meta = array(
			'_wcpay_intent_currency' => strtolower( isset( $intent['currency'] ) ? (string) $intent['currency'] : (string) $order->get_currency() ),
			'_wcpay_mode'            => $this->get_account_service()->get_mode(),
		);

		$charge_id = isset( $charge['id'] ) ? (string) $charge['id'] : '';
		if ( '' !== $charge_id ) {
			$meta['_charge_id'] = $charge_id;
		}

		$balance_transaction_id = $this->get_balance_transaction_id( $charge['balance_transaction'] ?? null );
		if ( '' !== $balance_transaction_id ) {
			$meta['_wcpay_payment_transaction_id'] = $balance_transaction_id;
		}

		return array_merge( $meta, $this->get_completed_charge_order_meta( $intent, $charge, $order ) );
	}

	/**
	 * Get legacy-compatible order meta for a failed capture response.
	 *
	 * @return array<string,string>
	 */
	private function get_failed_capture_order_meta(): array {
		return array(
			'_intention_status' => 'requires_capture',
		);
	}

	/**
	 * Get WooPayments fraud-outcome order meta from provider metadata.
	 *
	 * @param array<string,mixed> $intent Native PaymentIntent response.
	 * @param array<string,mixed> $charge Native Charge response.
	 * @return array<string,string>
	 */
	private function get_fraud_outcome_order_meta( array $intent, array $charge ): array {
		$metadata      = isset( $intent['metadata'] ) && is_array( $intent['metadata'] ) ? $intent['metadata'] : array();
		$fraud_outcome = isset( $metadata['fraud_outcome'] ) ? (string) $metadata['fraud_outcome'] : '';

		if ( '' === $fraud_outcome && $this->is_card_charge( $charge ) ) {
			$fraud_outcome = 'allow';
		}

		if ( ! in_array( $fraud_outcome, array( 'allow', 'block', 'review' ), true ) ) {
			return array();
		}

		return array(
			'_wcpay_fraud_outcome_status' => $fraud_outcome,
			'_wcpay_fraud_meta_box_type'  => 'allow',
		);
	}

	/**
	 * Tell whether a native charge was made with a card payment method.
	 *
	 * @param array<string,mixed> $charge Native Charge response.
	 * @return bool
	 */
	private function is_card_charge( array $charge ): bool {
		$payment_method_details = isset( $charge['payment_method_details'] ) && is_array( $charge['payment_method_details'] )
			? $charge['payment_method_details']
			: array();

		return 'card' === (string) ( $payment_method_details['type'] ?? '' );
	}

	/**
	 * Get the merchant transaction fee from a native charge.
	 *
	 * @param array<string,mixed> $intent Native PaymentIntent response.
	 * @param array<string,mixed> $charge Native Charge response.
	 * @return string
	 */
	private function get_transaction_fee_from_charge( array $intent, array $charge ): string {
		$fee_breakdown_v1 = $charge['fee_breakdown_v1'] ?? null;
		if ( is_array( $fee_breakdown_v1 ) && isset( $fee_breakdown_v1['totals']['fee']['amount'], $fee_breakdown_v1['totals']['fee']['currency'] ) ) {
			return (string) $this->interpret_stripe_amount( (int) $fee_breakdown_v1['totals']['fee']['amount'], (string) $fee_breakdown_v1['totals']['fee']['currency'] );
		}

		$application_fee_amount = $charge['application_fee_amount'] ?? null;
		$currency               = isset( $charge['currency'] ) ? (string) $charge['currency'] : (string) ( $intent['currency'] ?? '' );
		if ( null !== $application_fee_amount && '' !== $currency ) {
			return (string) $this->interpret_stripe_amount( (int) $application_fee_amount, $currency );
		}

		return '';
	}

	/**
	 * Get the merchant net amount from a native charge.
	 *
	 * @param array<string,mixed> $intent          Native PaymentIntent response.
	 * @param array<string,mixed> $charge          Native Charge response.
	 * @param string              $transaction_fee Transaction fee.
	 * @return string
	 */
	private function get_net_from_charge( array $intent, array $charge, string $transaction_fee ): string {
		$fee_breakdown_v1 = $charge['fee_breakdown_v1'] ?? null;
		if ( is_array( $fee_breakdown_v1 ) && isset( $fee_breakdown_v1['totals']['net']['amount'], $fee_breakdown_v1['totals']['net']['currency'] ) ) {
			return (string) $this->interpret_stripe_amount( (int) $fee_breakdown_v1['totals']['net']['amount'], (string) $fee_breakdown_v1['totals']['net']['currency'] );
		}

		$application_fee_amount = $charge['application_fee_amount'] ?? null;
		$charge_amount          = $charge['amount'] ?? $intent['amount'] ?? null;
		$currency               = isset( $charge['currency'] ) ? (string) $charge['currency'] : (string) ( $intent['currency'] ?? '' );
		if ( null !== $application_fee_amount && '' !== $transaction_fee && null !== $charge_amount && '' !== $currency ) {
			return (string) ( $this->interpret_stripe_amount( (int) $charge_amount, $currency ) - (float) $transaction_fee );
		}

		return '';
	}

	/**
	 * Interpret a Stripe integer amount for a currency.
	 *
	 * @param int    $amount   Stripe integer amount.
	 * @param string $currency Currency code.
	 * @return float
	 */
	private function interpret_stripe_amount( int $amount, string $currency ): float {
		return $this->is_zero_decimal_currency( $currency ) ? (float) $amount : (float) $amount / 100;
	}

	/**
	 * Build the legacy-compatible frontend confirmation hash.
	 *
	 * @param WC_Order $order              Order being charged.
	 * @param string   $client_secret      Intent client secret.
	 * @param string   $intent_type        Intent type.
	 * @param string   $confirmation_token Confirmation token.
	 * @return string
	 */
	private function build_confirmation_redirect( WC_Order $order, string $client_secret, string $intent_type = 'pi', string $confirmation_token = '' ): string {
		$redirect = '#wcpay-confirm-' . $intent_type . ':' . $order->get_id() . ':' . $client_secret . ':' . wp_create_nonce( 'wcpay_update_order_status_nonce' );

		if ( '' !== $confirmation_token ) {
			$redirect .= ':' . $confirmation_token;
		}

		return $redirect;
	}

	/**
	 * Get the human-readable payment method title.
	 *
	 * @param array<string,mixed> $payment_method_details Payment method details from the charge.
	 * @return string
	 */
	private function get_payment_method_title( array $payment_method_details ): string {
		$wallet_type = $payment_method_details['card']['wallet']['type'] ?? null;

		switch ( $wallet_type ) {
			case 'link':
				return __( 'Link', 'woocommerce' );

			case 'apple_pay':
				return __( 'Apple Pay', 'woocommerce' );

			case 'google_pay':
				return __( 'Google Pay', 'woocommerce' );
		}

		if ( 'card' === ( $payment_method_details['type'] ?? '' ) && isset( $payment_method_details['card'] ) && is_array( $payment_method_details['card'] ) ) {
			return $this->get_card_payment_method_title( $payment_method_details['card'] );
		}

		return __( 'Credit / Debit Cards', 'woocommerce' );
	}

	/**
	 * Get the human-readable card payment method title from charge details.
	 *
	 * @param array<string,mixed> $card_details Card details from the charge.
	 * @return string
	 */
	private function get_card_payment_method_title( array $card_details ): string {
		$funding_types = array(
			'credit'  => __( 'credit', 'woocommerce' ),
			'debit'   => __( 'debit', 'woocommerce' ),
			'prepaid' => __( 'prepaid', 'woocommerce' ),
			'unknown' => __( 'unknown', 'woocommerce' ),
		);

		$networks     = isset( $card_details['networks'] ) && is_array( $card_details['networks'] ) ? $card_details['networks'] : array();
		$available    = isset( $networks['available'] ) && is_array( $networks['available'] ) ? $networks['available'] : array();
		$card_network = $card_details['display_brand'] ?? $card_details['network'] ?? $networks['preferred'] ?? $available[0] ?? 'card';
		$card_network = str_replace( '_', ' ', (string) $card_network );
		$funding      = isset( $card_details['funding'] ) && isset( $funding_types[ (string) $card_details['funding'] ] )
			? $funding_types[ (string) $card_details['funding'] ]
			: $funding_types['unknown'];

		return sprintf(
			/* translators: %1$s: card brand, %2$s: card funding type. */
			__( '%1$s %2$s card', 'woocommerce' ),
			ucwords( $card_network ),
			$funding
		);
	}

	/**
	 * Get a WooPayments-compatible payment success order note.
	 *
	 * @param WC_Order $order     Order object.
	 * @param string   $intent_id Payment intent ID.
	 * @param string   $charge_id              Charge ID.
	 * @param string   $balance_transaction_id Balance transaction ID.
	 * @return string
	 */
	private function get_payment_success_note( WC_Order $order, string $intent_id, string $charge_id, string $balance_transaction_id = '' ): string {
		$formatted_amount = wc_price( (float) $order->get_total(), array( 'currency' => $order->get_currency() ) ) . ' ' . $order->get_currency();
		$transaction_id   = '' !== $intent_id ? $intent_id : $charge_id;
		$transaction_url  = $this->get_transaction_url( $intent_id, $charge_id, $balance_transaction_id );

		if ( 'test' === $this->get_account_service()->get_mode() ) {
			return sprintf(
				$this->get_interpolated_note_text(
					/* translators: %1$s: charged amount, %2$s: WooPayments, %3$s: transaction ID. */
					__( 'A test payment of %1$s was processed using %2$s in <strong>test mode</strong> (<a>%3$s</a>). No real funds were collected.', 'woocommerce' ),
					array(
						'strong' => '<strong>',
						'a'      => '' !== $transaction_url ? '<a href="' . $transaction_url . '" target="_blank" rel="noopener noreferrer">' : '<code>',
					)
				),
				$formatted_amount,
				'WooPayments',
				$transaction_id
			);
		}

		return sprintf(
			$this->get_interpolated_note_text(
				/* translators: %1$s: charged amount, %2$s: WooPayments, %3$s: transaction ID, %4$s: transaction URL. */
				__( 'A payment of %1$s was <strong>successfully charged</strong> using %2$s (<a>%3$s</a>).', 'woocommerce' ),
				array(
					'strong' => '<strong>',
					'a'      => '' !== $transaction_url ? '<a href="%4$s" target="_blank" rel="noopener noreferrer">' : '<code>',
				)
			),
			$formatted_amount,
			'WooPayments',
			$transaction_id,
			$transaction_url
		);
	}

	/**
	 * Get a WooPayments-compatible capture failure order note.
	 *
	 * @param WC_Order $order     Order object.
	 * @param string   $intent_id Payment intent ID.
	 * @param string   $charge_id Charge ID.
	 * @param string   $message   Failure message.
	 * @return string
	 */
	private function get_capture_failed_note( WC_Order $order, string $intent_id, string $charge_id, string $message ): string {
		$formatted_amount = wc_price( (float) $order->get_total(), array( 'currency' => $order->get_currency() ) ) . ' ' . $order->get_currency();
		$transaction_id   = '' !== $intent_id ? $intent_id : $charge_id;
		$transaction_url  = $this->get_transaction_url( $intent_id, $charge_id, (string) $order->get_meta( '_wcpay_payment_transaction_id', true ) );
		$note             = sprintf(
			$this->get_interpolated_note_text(
				/* translators: %1$s: authorized amount, %2$s: WooPayments, %3$s: transaction ID, %4$s: transaction URL. */
				__( 'A capture of %1$s <strong>failed</strong> to complete using %2$s (<a>%3$s</a>).', 'woocommerce' ),
				array(
					'strong' => '<strong>',
					'a'      => '' !== $transaction_url ? '<a href="%4$s" target="_blank" rel="noopener noreferrer">' : '<code>',
				)
			),
			$formatted_amount,
			'WooPayments',
			$transaction_id,
			$transaction_url
		);

		if ( '' !== $message ) {
			$note .= ' ' . $message;
		}

		return $note;
	}

	/**
	 * Replace simple interpolation tags with stored note HTML.
	 *
	 * @param string               $text        Note text.
	 * @param array<string,string> $element_map Element replacements.
	 * @return string
	 */
	private function get_interpolated_note_text( string $text, array $element_map ): string {
		foreach ( $element_map as $tag => $opening_tag ) {
			$closing_tag = '</' . $tag . '>';
			if ( preg_match( '/^<(\w+)/', $opening_tag, $matches ) ) {
				$closing_tag = '</' . $matches[1] . '>';
			}

			$text = str_replace( '<' . $tag . '>', $opening_tag, $text );
			$text = str_replace( '</' . $tag . '>', $closing_tag, $text );
		}

		return $text;
	}

	/**
	 * Get the WooPayments transaction details URL.
	 *
	 * @param string $intent_id              Payment intent ID.
	 * @param string $charge_id              Charge ID.
	 * @param string $balance_transaction_id Balance transaction ID.
	 * @return string
	 */
	private function get_transaction_url( string $intent_id, string $charge_id, string $balance_transaction_id = '' ): string {
		if ( '' === $intent_id && '' === $charge_id && '' === $balance_transaction_id ) {
			return '';
		}

		if ( false !== strpos( $intent_id, 'seti_' ) ) {
			return '';
		}

		$params = array(
			'id' => '' !== $intent_id ? $intent_id : $charge_id,
		);
		if ( '' !== $balance_transaction_id ) {
			$params['transaction_id'] = $balance_transaction_id;
		}

		return Utils::wc_payments_settings_url(
			'/woopayments/transactions/details',
			$params
		);
	}

	/**
	 * Get a balance transaction ID from a provider response field.
	 *
	 * @param mixed $balance_transaction Balance transaction response field.
	 * @return string
	 */
	private function get_balance_transaction_id( $balance_transaction ): string {
		if ( is_string( $balance_transaction ) ) {
			return $balance_transaction;
		}

		if ( is_array( $balance_transaction ) && isset( $balance_transaction['id'] ) ) {
			return (string) $balance_transaction['id'];
		}

		return '';
	}

	/**
	 * Normalize a process_payment result.
	 *
	 * @param array<string,mixed>|null $result  Legacy result.
	 * @param PaymentContext           $context Payment context.
	 * @return PaymentOutcome
	 */
	private function normalize_charge_result( ?array $result, PaymentContext $context ): PaymentOutcome {
		if ( null === $result ) {
			return new PaymentOutcome(
				PaymentOutcome::STATUS_FAILED,
				'',
				'',
				'',
				'',
				array( 'error_code' => 'legacy_process_payment_empty_response' )
			);
		}

		if ( 'success' !== ( $result['result'] ?? '' ) ) {
			return new PaymentOutcome(
				PaymentOutcome::STATUS_FAILED,
				'',
				'',
				'',
				'',
				array( 'error_code' => 'legacy_process_payment_failed' )
			);
		}

		$redirect              = isset( $result['redirect'] ) ? (string) $result['redirect'] : '';
		$payment_method_id     = isset( $result['payment_method'] ) ? (string) $result['payment_method'] : $context->get_payment_method_id();
		$fresh_order           = wc_get_order( $context->get_order_id() );
		$order                 = $fresh_order instanceof WC_Order ? $fresh_order : $context->get_order();
		$provider_payment_id   = (string) $order->get_meta( '_intent_id', true );
		$stored_payment_method = (string) $order->get_meta( '_payment_method_id', true );
		$intention_status      = (string) $order->get_meta( '_intention_status', true );
		$data                  = array();

		if ( '' === $payment_method_id ) {
			$payment_method_id = $stored_payment_method;
		}

		if ( array_key_exists( 'redirect', $result ) ) {
			$data['checkout_redirect'] = $redirect;
		}

		if ( str_starts_with( $redirect, '#wcpay-confirm-' ) ) {
			return new PaymentOutcome( PaymentOutcome::STATUS_REQUIRES_CUSTOMER_ACTION, $provider_payment_id, $redirect, $payment_method_id, '', $data );
		}

		if ( '' !== $redirect && ! $this->is_order_received_redirect( $order, $redirect ) ) {
			return new PaymentOutcome( PaymentOutcome::STATUS_REQUIRES_REDIRECT, $provider_payment_id, $redirect, $payment_method_id, '', $data );
		}

		$status = $this->map_intention_status_to_outcome_status( $intention_status );
		if ( '' === $status && '' === $provider_payment_id && 0.0 < (float) $order->get_total() && '' === $redirect ) {
			$status = PaymentOutcome::STATUS_PENDING_ASYNC;
		}

		return new PaymentOutcome(
			'' === $status ? PaymentOutcome::STATUS_COMPLETED : $status,
			$provider_payment_id,
			$redirect,
			$payment_method_id,
			'',
			$data
		);
	}

	/**
	 * Map a persisted WooPayments intention status to a neutral payment outcome.
	 *
	 * @param string $intention_status WooPayments intention status.
	 * @return string Empty string when the status does not imply a different outcome.
	 */
	private function map_intention_status_to_outcome_status( string $intention_status ): string {
		switch ( $intention_status ) {
			case 'succeeded':
				return PaymentOutcome::STATUS_COMPLETED;

			case 'requires_capture':
			case 'processing':
				return PaymentOutcome::STATUS_AUTHORIZED;

			case 'requires_action':
			case 'requires_confirmation':
				return PaymentOutcome::STATUS_REQUIRES_CUSTOMER_ACTION;

			case 'requires_payment_method':
				return PaymentOutcome::STATUS_FAILED;

			case 'canceled':
				return PaymentOutcome::STATUS_CANCELED;
		}

		return '';
	}

	/**
	 * Normalize a capture result.
	 *
	 * @param array<string,mixed> $result  Legacy or native capture result.
	 * @param PaymentContext      $context Payment context.
	 * @return PaymentOutcome
	 */
	private function normalize_capture_result( array $result, PaymentContext $context ): PaymentOutcome {
		$status     = isset( $result['status'] ) ? (string) $result['status'] : 'failed';
		$intent_id  = isset( $result['id'] ) ? (string) $result['id'] : '';
		$error_code = isset( $result['error_code'] ) ? (string) $result['error_code'] : '';
		$message    = isset( $result['message'] ) ? (string) $result['message'] : '';

		if ( 'succeeded' === $status ) {
			$meta = $this->get_completed_capture_order_meta( $result, $context->get_order() );
			$data = empty( $meta ) ? array() : array( 'meta' => $meta );

			return new PaymentOutcome( PaymentOutcome::STATUS_COMPLETED, $intent_id, '', '', '', $data );
		}

		if ( 'requires_capture' === $status && '' === $message ) {
			return new PaymentOutcome( PaymentOutcome::STATUS_AUTHORIZED, $intent_id );
		}

		return new PaymentOutcome(
			PaymentOutcome::STATUS_FAILED,
			$intent_id,
			'',
			'',
			'',
			array(
				'error_code'    => $error_code,
				'error_message' => $message,
				'meta'          => $this->get_failed_capture_order_meta(),
				'note'          => $this->get_capture_failed_note(
					$context->get_order(),
					$intent_id,
					$this->get_failed_capture_charge_id( $result, $context->get_order() ),
					$message
				),
			)
		);
	}

	/**
	 * Get the charge ID for a failed capture response.
	 *
	 * @param array<string,mixed> $result Native or legacy capture result.
	 * @param WC_Order            $order  Order being captured.
	 * @return string
	 */
	private function get_failed_capture_charge_id( array $result, WC_Order $order ): string {
		$charge = $this->get_latest_charge( $result );
		if ( isset( $charge['id'] ) && '' !== (string) $charge['id'] ) {
			return (string) $charge['id'];
		}

		return (string) $order->get_meta( '_charge_id', true );
	}

	/**
	 * Normalize a native refund response.
	 *
	 * @param array<string,mixed> $result  Native refund result.
	 * @param PaymentContext      $context Payment context.
	 * @return PaymentOutcome
	 */
	private function normalize_refund_result( array $result, PaymentContext $context ): PaymentOutcome {
		$refund_id              = isset( $result['id'] ) ? (string) $result['id'] : '';
		$provider_status        = isset( $result['status'] ) ? (string) $result['status'] : '';
		$refund_status          = 'pending' === $provider_status ? 'pending' : 'successful';
		$balance_transaction_id = $this->get_refund_balance_transaction_id( $result['balance_transaction'] ?? null );

		if ( ! in_array( $provider_status, array( '', 'pending', 'succeeded' ), true ) ) {
			$failure_reason = isset( $result['failure_reason'] ) ? (string) $result['failure_reason'] : '';
			$error_message  = sprintf(
				/* translators: %1$s: refund status, %2$s: failure reason. */
				__( 'The refund returned status "%1$s". Reason: %2$s', 'woocommerce' ),
				$provider_status,
				'' !== $failure_reason ? $failure_reason : __( 'No reason provided.', 'woocommerce' )
			);

			return new PaymentOutcome(
				PaymentOutcome::STATUS_FAILED,
				$refund_id,
				'',
				'',
				'',
				array(
					'error_code'    => '' !== $failure_reason ? $failure_reason : $provider_status,
					'error_message' => $error_message,
					'refund_status' => $provider_status,
				)
			);
		}

		$data = array(
			'order_meta'    => array( '_wcpay_refund_status' => $refund_status ),
			'refund_meta'   => array( '_wcpay_refund_id' => $refund_id ),
			'refund_note'   => $this->get_refund_note( $context, $refund_id, 'pending' === $refund_status ),
			'refund_status' => $refund_status,
		);

		if ( '' !== $balance_transaction_id ) {
			$data['refund_meta']['_wcpay_refund_transaction_id'] = $balance_transaction_id;
			$data['refund_balance_transaction_id']               = $balance_transaction_id;
		}

		return new PaymentOutcome(
			PaymentOutcome::STATUS_COMPLETED,
			$refund_id,
			'',
			'',
			'',
			$data
		);
	}

	/**
	 * Get the refund balance transaction ID from a provider response.
	 *
	 * @param mixed $balance_transaction Balance transaction response field.
	 * @return string
	 */
	private function get_refund_balance_transaction_id( $balance_transaction ): string {
		if ( is_string( $balance_transaction ) ) {
			return $balance_transaction;
		}

		if ( is_array( $balance_transaction ) && isset( $balance_transaction['id'] ) ) {
			return (string) $balance_transaction['id'];
		}

		return '';
	}

	/**
	 * Build a WooPayments-compatible provider refund note.
	 *
	 * @param PaymentContext $context    Payment context.
	 * @param string         $refund_id  Provider refund ID.
	 * @param bool           $is_pending Whether the provider refund is pending.
	 * @return string
	 */
	private function get_refund_note( PaymentContext $context, string $refund_id, bool $is_pending ): string {
		$order            = $context->get_order();
		$payment_data     = $context->get_payment_data();
		$refund_amount    = isset( $payment_data['amount'] ) ? (float) $payment_data['amount'] : 0.0;
		$refund_reason    = isset( $payment_data['reason'] ) ? (string) $payment_data['reason'] : '';
		$formatted_amount = wc_price( $refund_amount, array( 'currency' => $order->get_currency() ) );
		$status_text      = $is_pending
			? sprintf(
				'<a href="https://woocommerce.com/document/woopayments/managing-money/#pending-refunds" target="_blank" rel="noopener noreferrer">%s</a>',
				esc_html__( 'is pending', 'woocommerce' )
			)
			: esc_html__( 'was successfully processed', 'woocommerce' );
		$refund_id_markup = '<code>' . esc_html( $refund_id ) . '</code>';

		if ( '' === $refund_reason ) {
			$note = sprintf(
				/* translators: %1$s: refund amount, %2$s: WooPayments, %3$s: provider refund ID, %4$s: refund status. */
				__( 'A refund of %1$s %4$s using %2$s (%3$s).', 'woocommerce' ),
				$formatted_amount,
				'WooPayments',
				$refund_id_markup,
				$status_text
			);
		} else {
			$note = sprintf(
				/* translators: %1$s: refund amount, %2$s: WooPayments, %3$s: refund reason, %4$s: provider refund ID, %5$s: refund status. */
				__( 'A refund of %1$s %5$s using %2$s. Reason: %3$s. (%4$s)', 'woocommerce' ),
				$formatted_amount,
				'WooPayments',
				esc_html( $refund_reason ),
				$refund_id_markup,
				$status_text
			);
		}

		return wp_kses_post( $note );
	}

	/**
	 * Normalize a cancel authorization result.
	 *
	 * @param array<string,mixed> $result Legacy cancel result.
	 * @return PaymentOutcome
	 */
	private function normalize_cancel_result( array $result ): PaymentOutcome {
		$status    = isset( $result['status'] ) ? (string) $result['status'] : 'failed';
		$intent_id = isset( $result['id'] ) ? (string) $result['id'] : '';
		$message   = isset( $result['message'] ) ? (string) $result['message'] : '';

		if ( 'canceled' === $status ) {
			return new PaymentOutcome( PaymentOutcome::STATUS_CANCELED, $intent_id );
		}

		return new PaymentOutcome(
			PaymentOutcome::STATUS_FAILED,
			$intent_id,
			'',
			'',
			'',
			array(
				'error_code'    => 'legacy_cancel_authorization_failed',
				'error_message' => $message,
			)
		);
	}

	/**
	 * Tell whether a redirect URL is the order received URL.
	 *
	 * @param WC_Order $order    Order object.
	 * @param string   $redirect Redirect URL.
	 * @return bool
	 */
	private function is_order_received_redirect( WC_Order $order, string $redirect ): bool {
		return '' !== $redirect && $redirect === $order->get_checkout_order_received_url();
	}

	/**
	 * Build a failed outcome for unavailable legacy gateway calls.
	 *
	 * @param string $operation Operation name.
	 * @return PaymentOutcome
	 */
	private function unavailable_outcome( string $operation ): PaymentOutcome {
		return new PaymentOutcome(
			PaymentOutcome::STATUS_FAILED,
			'',
			'',
			'',
			'',
			array(
				'error_code' => 'wcpay_gateway_unavailable',
				'operation'  => $operation,
			)
		);
	}

	/**
	 * Build a failed outcome from a transport exception.
	 *
	 * @param string                  $operation Operation name.
	 * @param WooPaymentsApiException $exception Native transport exception.
	 * @param PaymentContext|null     $context   Payment context.
	 * @return PaymentOutcome
	 */
	private function failed_transport_outcome( string $operation, WooPaymentsApiException $exception, ?PaymentContext $context = null ): PaymentOutcome {
		$provider_payment_id = '';
		$data                = array(
			'error_code'    => '' !== $exception->get_error_code() ? $exception->get_error_code() : 'wcpay_native_transport_failed',
			'error_message' => $exception->getMessage(),
			'operation'     => $operation,
		);

		if ( 'capture' === $operation && null !== $context ) {
			$order               = $context->get_order();
			$provider_payment_id = $this->get_order_intent_id( $order );
			$data['meta']        = $this->get_failed_capture_order_meta();
			$data['note']        = $this->get_capture_failed_note(
				$order,
				$provider_payment_id,
				(string) $order->get_meta( '_charge_id', true ),
				$exception->getMessage()
			);
		}

		return new PaymentOutcome(
			PaymentOutcome::STATUS_FAILED,
			$provider_payment_id,
			'',
			'',
			'',
			$data
		);
	}

	/**
	 * Convert a decimal amount into provider minor units.
	 *
	 * @param float  $amount   Decimal amount.
	 * @param string $currency Order currency.
	 * @return int
	 */
	private function prepare_amount( float $amount, string $currency ): int {
		$conversion_rate = $this->is_zero_decimal_currency( $currency ) ? 1 : 100;

		return (int) round( $amount * $conversion_rate );
	}

	/**
	 * Tell whether the currency uses zero decimal places at the provider boundary.
	 *
	 * @param string $currency Currency code.
	 * @return bool
	 */
	private function is_zero_decimal_currency( string $currency ): bool {
		return in_array(
			strtolower( $currency ),
			array(
				'bif',
				'clp',
				'djf',
				'gnf',
				'jpy',
				'kmf',
				'krw',
				'mga',
				'pyg',
				'rwf',
				'vnd',
				'vuv',
				'xaf',
				'xof',
				'xpf',
			),
			true
		);
	}
}
