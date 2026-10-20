<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Utilities;

/**
 * PaymentUtils
 *
 * Utility class for payment methods.
 */
class PaymentUtils {

	/**
	 * Callback for woocommerce_payment_methods_list_item filter to add token id
	 * to the generated list.
	 *
	 * @param array             $list_item The current list item for the saved payment method.
	 * @param \WC_Payment_Token $token     The token for the current list item.
	 *
	 * @return array The list item with the token id added.
	 */
	public static function include_token_id_with_payment_methods( $list_item, $token ) {
		$list_item['tokenId'] = $token->get_id();
		$brand                = ! empty( $list_item['method']['brand'] ) ?
			strtolower( $list_item['method']['brand'] ) :
			'';
		if ( ! empty( $brand ) && esc_html__( 'Credit card', 'woocommerce' ) !== $brand ) {
			$list_item['method']['brand'] = wc_get_credit_card_type_label( $brand );
		}
		return $list_item;
	}

	/**
	 * Get enabled payment gateways.
	 *
	 * @return array
	 */
	public static function get_enabled_payment_gateways() {
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		return array_filter(
			$payment_gateways,
			function ( $payment_gateway ) {
				return 'yes' === $payment_gateway->enabled;
			}
		);
	}

	/**
	 * Returns enabled saved payment methods for a customer and the default method if there are multiple.
	 *
	 * @return array|null Null when no customer is logged in.
	 */
	public static function get_saved_payment_methods() {
		if ( ! is_user_logged_in() ) {
			return null;
		}

		add_filter( 'woocommerce_payment_methods_list_item', [ self::class, 'include_token_id_with_payment_methods' ], 10, 2 );

		$enabled_payment_gateways = self::get_enabled_payment_gateways();
		$saved_payment_methods    = wc_get_customer_saved_methods_list( get_current_user_id() );
		$payment_methods          = [
			'enabled' => [],
			'default' => null,
		];

		// Filter out payment methods that are not enabled.
		foreach ( $saved_payment_methods as $payment_method_group => $saved_payment_methods ) {
			$payment_methods['enabled'][ $payment_method_group ] = array_values(
				array_filter(
					$saved_payment_methods,
					function ( $saved_payment_method ) use ( $enabled_payment_gateways, &$payment_methods ) {
						if ( true === $saved_payment_method['is_default'] && null === $payment_methods['default'] ) {
							$payment_methods['default'] = $saved_payment_method;
						}
						return in_array( $saved_payment_method['method']['gateway'], array_keys( $enabled_payment_gateways ), true );
					}
				)
			);
		}

		remove_filter( 'woocommerce_payment_methods_list_item', [ self::class, 'include_token_id_with_payment_methods' ], 10 );

		return $payment_methods;
	}

	/**
	 * Returns the payment method the checkout carries: the shopper's session choice while that gateway
	 * is available for the cart, otherwise the default from get_default_payment_method(). The draft
	 * response and the draft order sync both use this, so they agree.
	 *
	 * @return string
	 */
	public static function get_checkout_payment_method(): string {
		$chosen_payment_method = (string) WC()->session->get( 'chosen_payment_method' );

		if ( '' !== $chosen_payment_method && isset( WC()->payment_gateways()->get_available_payment_gateways()[ $chosen_payment_method ] ) ) {
			return $chosen_payment_method;
		}

		return (string) self::get_default_payment_method();
	}

	/**
	 * Returns the default payment method for a customer: the default saved card, then the method
	 * remembered in the session, then the first enabled gateway. The `woocommerce_default_payment_method`
	 * filter runs last and can replace that answer.
	 *
	 * @return string
	 */
	public static function get_default_payment_method() {
		$payment_method     = self::get_unfiltered_default_payment_method();
		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

		/**
		 * Filters the payment method the Checkout block starts with.
		 *
		 * Return a gateway id from `$available_gateways` to preselect it, or an empty string to select nothing;
		 * any other value selects nothing. Attach the callback by `init` at the latest.
		 *
		 * @since 11.4.0
		 *
		 * @param string $payment_method     Gateway id WooCommerce would preselect: the customer's default saved
		 *                                   card's gateway, the method remembered in the session, or the first
		 *                                   enabled gateway; '' when there is none.
		 * @param array  $available_gateways Gateways available for this cart, keyed by id.
		 */
		$filtered = apply_filters( 'woocommerce_default_payment_method', $payment_method, $available_gateways );

		// An unchanged answer passes through as before, even when that gateway isn't available for the cart;
		// the block then falls back to the first method it can use. A callback's answer must be available.
		if ( $filtered === $payment_method ) {
			return $payment_method;
		}

		return is_string( $filtered ) && isset( $available_gateways[ $filtered ] ) ? $filtered : '';
	}

	/**
	 * The default payment method before the filter: saved card, session, then the first enabled gateway.
	 *
	 * @return string
	 */
	private static function get_unfiltered_default_payment_method() {
		$saved_payment_methods = self::get_saved_payment_methods();
		// A saved payment method exists, set as default.
		if ( $saved_payment_methods && ! empty( $saved_payment_methods['default'] ) ) {
			return $saved_payment_methods['default']['method']['gateway'] ?? '';
		}

		$chosen_payment_method = WC()->session->get( 'chosen_payment_method' );

		// If payment method is already stored in session, use it.
		if ( $chosen_payment_method ) {
			return $chosen_payment_method;
		}

		// If no saved payment method exists, use the first enabled payment method.
		$enabled_payment_gateways = self::get_enabled_payment_gateways();

		if ( empty( $enabled_payment_gateways ) ) {
			return '';
		}

		$first_key            = array_key_first( $enabled_payment_gateways );
		$first_payment_method = $enabled_payment_gateways[ $first_key ];
		return $first_payment_method->id ?? '';
	}
}
