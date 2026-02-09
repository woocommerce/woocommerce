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
	 * @param array     $list_item The current list item for the saved payment method.
	 * @param \WC_Token $token     The token for the current list item.
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
	 * @return array
	 */
	public static function get_saved_payment_methods() {
		if ( ! is_user_logged_in() ) {
			return;
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

		remove_filter( 'woocommerce_payment_methods_list_item', [ self::class, 'include_token_id_with_payment_methods' ], 10, 2 );

		return $payment_methods;
	}

	/**
	 * Returns the default payment method for a customer.
	 *
	 * @return string
	 */
	public static function get_default_payment_method() {
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

		$first_key                = array_key_first( $enabled_payment_gateways );
		$first_payment_method     = $enabled_payment_gateways[ $first_key ];
		return $first_payment_method->id ?? '';
	}
}
