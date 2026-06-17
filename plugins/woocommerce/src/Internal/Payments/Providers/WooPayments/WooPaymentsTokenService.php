<?php
/**
 * WooPaymentsTokenService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use WC_Order;
use WC_Payment_Token;
use WC_Payment_Token_CC;
use WC_Payment_Tokens;

/**
 * Persists WooPayments card payment methods as WooCommerce payment tokens.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsTokenService {

	/**
	 * Payment method details service.
	 *
	 * @var WooPaymentsPaymentMethodDetailsService
	 */
	private WooPaymentsPaymentMethodDetailsService $payment_method_details_service;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsPaymentMethodDetailsService $payment_method_details_service Payment method details service.
	 */
	final public function init( WooPaymentsPaymentMethodDetailsService $payment_method_details_service ): void {
		$this->payment_method_details_service = $payment_method_details_service;
	}

	/**
	 * Resolve a WooCommerce payment token ID to the provider payment method ID.
	 *
	 * @since 11.0.0
	 *
	 * @param string $token_id WooCommerce payment token ID.
	 * @param int    $user_id  Expected token owner user ID.
	 * @return string Provider payment method ID, or empty string when the token is invalid for WooPayments.
	 */
	public function resolve_payment_method_id_from_token_id( string $token_id, int $user_id ): string {
		$token = $this->get_valid_token_from_token_id( $token_id, $user_id );

		return $token instanceof WC_Payment_Token ? (string) $token->get_token() : '';
	}

	/**
	 * Resolve an order-attached WooCommerce payment token ID to the provider payment method ID.
	 *
	 * This is used for WC Subscriptions renewals, where Subscriptions has already attached the token
	 * to the renewal order and the renewal may not have the same runtime user context as checkout.
	 *
	 * @since 11.0.0
	 *
	 * @param string   $token_id WooCommerce payment token ID.
	 * @param WC_Order $order    Order that must contain the token.
	 * @return string Provider payment method ID, or empty string when the token is not attached to the order or is invalid for WooPayments.
	 */
	public function resolve_payment_method_id_from_order_token_id( string $token_id, WC_Order $order ): string {
		$token_id_int = absint( $token_id );
		if ( 0 >= $token_id_int || ! in_array( $token_id_int, array_map( 'absint', $order->get_payment_tokens() ), true ) ) {
			return '';
		}

		$token = WC_Payment_Tokens::get( $token_id_int );
		if ( ! $token instanceof WC_Payment_Token || OrderPaymentStore::GATEWAY_ID !== $token->get_gateway_id() ) {
			return '';
		}

		return (string) $token->get_token();
	}

	/**
	 * Get a WooPayments token object by WooCommerce payment token ID.
	 *
	 * @since 11.0.0
	 *
	 * @param string $token_id WooCommerce payment token ID.
	 * @param int    $user_id  Expected token owner user ID.
	 * @return WC_Payment_Token|null Token object, or null when invalid.
	 */
	public function get_valid_token_from_token_id( string $token_id, int $user_id ): ?WC_Payment_Token {
		if ( 0 >= $user_id || '' === trim( $token_id ) ) {
			return null;
		}

		$token = WC_Payment_Tokens::get( absint( $token_id ) );
		if ( ! $token instanceof WC_Payment_Token ) {
			return null;
		}

		if ( OrderPaymentStore::GATEWAY_ID !== $token->get_gateway_id() || $user_id !== $token->get_user_id() ) {
			return null;
		}

		return $token;
	}

	/**
	 * Get or create a saved card token for a user.
	 *
	 * @since 11.0.0
	 *
	 * @param string $payment_method_id Provider payment method ID.
	 * @param int    $user_id           User ID.
	 * @return WC_Payment_Token_CC|null Saved card token, or null when details are unavailable.
	 */
	public function get_or_create_card_token_for_user( string $payment_method_id, int $user_id ): ?WC_Payment_Token_CC {
		if ( 0 >= $user_id || '' === trim( $payment_method_id ) ) {
			return null;
		}

		$existing_token = $this->get_existing_card_token_for_user( $payment_method_id, $user_id );
		if ( $existing_token instanceof WC_Payment_Token_CC ) {
			return $existing_token;
		}

		$payment_method = $this->payment_method_details_service->get_payment_method_details( $payment_method_id );
		$card_details   = $this->get_card_details( $payment_method );
		if ( empty( $card_details ) ) {
			return null;
		}

		$provider_token = isset( $payment_method['id'] ) && '' !== (string) $payment_method['id']
			? (string) $payment_method['id']
			: $payment_method_id;
		$card_type      = $this->get_card_type( $card_details );
		$last4          = isset( $card_details['last4'] ) ? (string) $card_details['last4'] : '';
		$expiry_month   = isset( $card_details['exp_month'] ) ? (string) $card_details['exp_month'] : '';
		$expiry_year    = isset( $card_details['exp_year'] ) ? (string) $card_details['exp_year'] : '';

		if ( '' === $card_type || '' === $last4 || '' === $expiry_month || '' === $expiry_year ) {
			return null;
		}

		$token = new WC_Payment_Token_CC();
		$token->set_gateway_id( OrderPaymentStore::GATEWAY_ID );
		$token->set_user_id( $user_id );
		$token->set_token( $provider_token );
		$token->set_card_type( $card_type );
		$token->set_last4( $last4 );
		$token->set_expiry_month( $expiry_month );
		$token->set_expiry_year( $expiry_year );

		$wallet_type = $card_details['wallet']['type'] ?? '';
		if ( is_string( $wallet_type ) && '' !== $wallet_type ) {
			$token->add_meta_data( '_wcpay_wallet_type', $wallet_type, true );
		}

		$token->save();

		return $token;
	}

	/**
	 * Attach a payment token to an order.
	 *
	 * @since 11.0.0
	 *
	 * @param WC_Order         $order Order object.
	 * @param WC_Payment_Token $token Payment token.
	 * @return bool True when the token was attached.
	 */
	public function attach_token_to_order( WC_Order $order, WC_Payment_Token $token ): bool {
		if ( 0 >= $token->get_id() ) {
			return false;
		}

		$result = $order->add_payment_token( $token );
		if ( false === $result ) {
			return false;
		}

		$order->save();

		return true;
	}

	/**
	 * Copy a saved payment token and provider metadata to subscriptions related to an initial order.
	 *
	 * @since 11.0.0
	 *
	 * @param WC_Order         $order             Parent order.
	 * @param WC_Payment_Token $token             Saved payment token.
	 * @param string           $payment_method_id Provider payment method ID.
	 * @param string           $customer_id       WooPayments customer ID.
	 */
	public function sync_related_subscriptions_payment_token( WC_Order $order, WC_Payment_Token $token, string $payment_method_id, string $customer_id ): void {
		if ( 0 >= $token->get_id() ) {
			return;
		}

		$provider_payment_method_id = '' !== $payment_method_id ? $payment_method_id : (string) $token->get_token();
		$provider_customer_id       = '' !== $customer_id ? $customer_id : (string) $order->get_meta( '_stripe_customer_id', true );

		foreach ( $this->get_related_subscriptions_for_order( $order ) as $subscription ) {
			if ( ! $subscription instanceof WC_Order || $order->get_payment_method() !== $subscription->get_payment_method() ) {
				continue;
			}

			$subscription_token_ids = array_map( 'absint', $subscription->get_payment_tokens() );
			if ( ! in_array( $token->get_id(), $subscription_token_ids, true ) ) {
				$subscription->add_payment_token( $token );
			}

			if ( '' !== $provider_payment_method_id ) {
				$subscription->update_meta_data( '_payment_method_id', $provider_payment_method_id );
			}

			if ( '' !== $provider_customer_id ) {
				$subscription->update_meta_data( '_stripe_customer_id', $provider_customer_id );
			}

			$subscription->save();
		}
	}

	/**
	 * Get an existing card token for a provider payment method ID.
	 *
	 * @param string $payment_method_id Provider payment method ID.
	 * @param int    $user_id           User ID.
	 * @return WC_Payment_Token_CC|null
	 */
	private function get_existing_card_token_for_user( string $payment_method_id, int $user_id ): ?WC_Payment_Token_CC {
		$tokens = WC_Payment_Tokens::get_customer_tokens( $user_id, OrderPaymentStore::GATEWAY_ID );
		foreach ( $tokens as $token ) {
			if ( $token instanceof WC_Payment_Token_CC && $payment_method_id === (string) $token->get_token() ) {
				return $token;
			}
		}

		return null;
	}

	/**
	 * Get subscriptions related to an initial order.
	 *
	 * @param WC_Order $order Parent order.
	 * @return array<int,mixed>
	 */
	private function get_related_subscriptions_for_order( WC_Order $order ): array {
		$subscriptions = array();
		if ( function_exists( 'wcs_get_subscriptions_for_order' ) ) {
			$subscriptions = wcs_get_subscriptions_for_order( $order->get_id() );
		}

		$subscriptions = is_array( $subscriptions ) ? $subscriptions : array();

		/**
		 * Filters native WooPayments subscriptions related to an initial order.
		 *
		 * @since 11.0.0
		 *
		 * @param array<int,mixed> $subscriptions Related subscriptions.
		 * @param WC_Order         $order         Parent order.
		 */
		$subscriptions = apply_filters( 'woocommerce_native_woopayments_related_subscriptions_for_order', $subscriptions, $order );

		return is_array( $subscriptions ) ? $subscriptions : array();
	}

	/**
	 * Get card details from a payment method payload.
	 *
	 * @param array<string,mixed> $payment_method Payment method details.
	 * @return array<string,mixed>
	 */
	private function get_card_details( array $payment_method ): array {
		$type = isset( $payment_method['type'] ) ? (string) $payment_method['type'] : '';

		if ( 'card' === $type && isset( $payment_method['card'] ) && is_array( $payment_method['card'] ) ) {
			return $payment_method['card'];
		}

		if ( 'card_present' === $type && isset( $payment_method['card_present'] ) && is_array( $payment_method['card_present'] ) ) {
			return $payment_method['card_present'];
		}

		return array();
	}

	/**
	 * Get the WooCommerce card type from Stripe card details.
	 *
	 * @param array<string,mixed> $card_details Card details.
	 * @return string
	 */
	private function get_card_type( array $card_details ): string {
		$preferred_network = '';
		if ( isset( $card_details['networks'] ) && is_array( $card_details['networks'] ) && isset( $card_details['networks']['preferred'] ) ) {
			$preferred_network = (string) $card_details['networks']['preferred'];
		}

		$card_type = isset( $card_details['display_brand'] ) ? (string) $card_details['display_brand'] : '';
		if ( '' === $card_type ) {
			$card_type = $preferred_network;
		}

		if ( '' === $card_type && isset( $card_details['brand'] ) ) {
			$card_type = (string) $card_details['brand'];
		}

		return strtolower( $card_type );
	}
}
