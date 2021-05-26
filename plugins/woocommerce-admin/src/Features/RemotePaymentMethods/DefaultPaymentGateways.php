<?php
/**
 * Gets a list of fallback methods if remote fetching is disabled.
 */

namespace Automattic\WooCommerce\Admin\Features\RemotePaymentMethods;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks;

/**
 * Default Payment Gateways
 */
class DefaultPaymentGateways {

	/**
	 * Get default specs.
	 *
	 * @return array Default specs.
	 */
	public static function get_all() {
		return array(
			array(
				'key'        => 'payfast',
				'title'      => __( 'PayFast', 'woocommerce-admin' ),
				'content'    => __( 'The PayFast extension for WooCommerce enables you to accept payments by Credit Card and EFT via one of South Africa’s most popular payment gateways. No setup fees or monthly subscription costs.  Selecting this extension will configure your store to use South African rands as the selected currency.', 'woocommerce-admin' ),
				'image'      => WC()->plugin_url() . '/assets/images/payfast.png',
				'plugins'    => array( 'woocommerce-payfast-gateway' ),
				'is_visible' => array(
					(object) array(
						'type'      => 'base_location_country',
						'value'     => 'ZA',
						'operation' => '=',
					),
					self::get_rules_for_cbd( false ),
				),
			),
			array(
				'key'        => 'stripe',
				'title'      => __( 'Credit cards - powered by Stripe', 'woocommerce-admin' ),
				'content'    => __( 'Accept debit and credit cards in 135+ currencies, methods such as Alipay, and one-touch checkout with Apple Pay.', 'woocommerce-admin' ),
				'image'      => WC()->plugin_url() . '/assets/images/stripe.png',
				'plugins'    => array( 'woocommerce-gateway-stripe' ),
				'is_visible' => array(
					self::get_rules_for_countries( OnboardingTasks::get_stripe_supported_countries() ),
					self::get_rules_for_cbd( false ),
				),
			),
			array(
				'key'        => 'paystack',
				'title'      => __( 'Paystack', 'woocommerce-admin' ),
				'content'    => __( 'Paystack helps African merchants accept one-time and recurring payments online with a modern, safe, and secure payment gateway.', 'woocommerce-admin' ),
				'image'      => plugins_url( 'images/onboarding/paystack.png', WC_ADMIN_PLUGIN_FILE ),
				'plugins'    => array( 'woo-paystack' ),
				'is_visible' => array(
					self::get_rules_for_countries( array( 'ZA', 'GH', 'NG' ) ),
					self::get_rules_for_cbd( false ),
				),
			),
			array(
				'key'        => 'woo-mercado-pago-custom',
				'title'      => __( 'Mercado Pago Checkout Pro & Custom', 'woocommerce-admin' ),
				'content'    => __( 'Accept credit and debit cards, offline (cash or bank transfer) and logged-in payments with money in Mercado Pago. Safe and secure payments with the leading payment processor in LATAM.', 'woocommerce-admin' ),
				'image'      => plugins_url( 'images/onboarding/mercadopago.png', WC_ADMIN_PLUGIN_FILE ),
				'plugins'    => array( 'woocommerce-mercadopago' ),
				'is_visible' => array(
					self::get_rules_for_countries( array( 'AR', 'BR', 'CL', 'CO', 'MX', 'PE', 'UY' ) ),
				),
			),
			array(
				'key'        => 'ppcp-gateway',
				'title'      => __( 'PayPal Payments', 'woocommerce-admin' ),
				'content'    => __( "Safe and secure payments using credit cards or your customer's PayPal account.", 'woocommerce-admin' ),
				'image'      => WC()->plugin_url() . '/assets/images/paypal.png',
				'plugins'    => array( 'woocommerce-paypal-payments' ),
				'is_visible' => array(
					(object) array(
						'type'      => 'base_location_country',
						'value'     => 'IN',
						'operation' => '!=',
					),
					self::get_rules_for_cbd( false ),
				),
			),
			array(
				'key'        => 'cod',
				'title'      => __( 'Cash on delivery', 'woocommerce-admin' ),
				'content'    => __( 'Take payments in cash upon delivery.', 'woocommerce-admin' ),
				'image'      => plugins_url( 'images/onboarding/cod.svg', WC_ADMIN_PLUGIN_FILE ),
				'is_visible' => array(
					self::get_rules_for_cbd( false ),
				),
			),
		);
	}

	/**
	 * Get rules that match the store base location to one of the provided countries.
	 *
	 * @param array $countries Array of countries to match.
	 * @return object Rules to match.
	 */
	public static function get_rules_for_countries( $countries ) {
		$rules = array();

		foreach ( $countries as $country ) {
			$rules[] = (object) array(
				'type'      => 'base_location_country',
				'value'     => $country,
				'operation' => '=',
			);
		}

		return (object) array(
			'type'     => 'or',
			'operands' => $rules,
		);
	}

	/**
	 * Get default rules for CBD based on given argument.
	 *
	 * @param bool $should_have Whether or not the store should have CBD as an industry (true) or not (false).
	 * @return array Rules to match.
	 */
	public static function get_rules_for_cbd( $should_have ) {
		return (object) array(
			'type'         => 'option',
			'transformers' => array(
				(object) array(
					'use'       => 'dot_notation',
					'arguments' => (object) array(
						'path' => 'industry',
					),
				),
				(object) array(
					'use'       => 'array_column',
					'arguments' => (object) array(
						'key' => 'slug',
					),
				),
			),
			'option_name'  => 'woocommerce_onboarding_profile',
			'operation'    => $should_have ? 'contains' : '!contains',
			'value'        => 'cbd-other-hemp-derived-products',
			'default'      => array(),
		);
	}

}
