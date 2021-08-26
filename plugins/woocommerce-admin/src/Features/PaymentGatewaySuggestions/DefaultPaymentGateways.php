<?php
/**
 * Gets a list of fallback methods if remote fetching is disabled.
 */

namespace Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions;

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
				'id'         => 'payfast',
				'title'      => __( 'PayFast', 'woocommerce-admin' ),
				'content'    => __( 'The PayFast extension for WooCommerce enables you to accept payments by Credit Card and EFT via one of South Africa’s most popular payment gateways. No setup fees or monthly subscription costs. Selecting this extension will configure your store to use South African rands as the selected currency.', 'woocommerce-admin' ),
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
				'id'                      => 'stripe',
				'title'                   => __( ' Stripe', 'woocommerce-admin' ),
				'content'                 => __( 'Accept debit and credit cards in 135+ currencies, methods such as Alipay, and one-touch checkout with Apple Pay.', 'woocommerce-admin' ),
				'image'                   => WC()->plugin_url() . '/assets/images/stripe.png',
				'plugins'                 => array( 'woocommerce-gateway-stripe' ),
				'is_visible'              => array(
					self::get_rules_for_countries( OnboardingTasks::get_stripe_supported_countries() ),
					self::get_rules_for_cbd( false ),
				),
				'recommendation_priority' => 3,
			),
			array(
				'id'         => 'paystack',
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
				'id'         => 'kco',
				'title'      => __( 'Klarna Checkout', 'woocommerce-admin' ),
				'content'    => __( 'Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries.', 'woocommerce-admin' ),
				'image'      => WC()->plugin_url() . '/assets/images/klarna-black.png',
				'plugins'    => array( 'klarna-checkout-for-woocommerce' ),
				'is_visible' => array(
					self::get_rules_for_countries( array( 'SE', 'FI', 'NO' ) ),
					self::get_rules_for_cbd( false ),
				),
			),
			array(
				'id'         => 'klarna_payments',
				'title'      => __( 'Klarna Payments', 'woocommerce-admin' ),
				'content'    => __( 'Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries.', 'woocommerce-admin' ),
				'image'      => WC()->plugin_url() . '/assets/images/klarna-black.png',
				'plugins'    => array( 'klarna-payments-for-woocommerce' ),
				'is_visible' => array(
					self::get_rules_for_countries(
						array(
							'DK',
							'DE',
							'AT',
							'NL',
							'CH',
							'BE',
							'SP',
							'PL',
							'FR',
							'IT',
							'GB',
						)
					),
					self::get_rules_for_cbd( false ),
				),
			),
			array(
				'id'         => 'mollie_wc_gateway_banktransfer',
				'title'      => __( 'Mollie', 'woocommerce-admin' ),
				'content'    => __( 'Effortless payments by Mollie: Offer global and local payment methods, get onboarded in minutes, and supported in your language.', 'woocommerce-admin' ),
				'image'      => plugins_url( 'images/onboarding/mollie.svg', WC_ADMIN_PLUGIN_FILE ),
				'plugins'    => array( 'mollie-payments-for-woocommerce' ),
				'is_visible' => array(
					self::get_rules_for_countries(
						array(
							'FR',
							'DE',
							'GB',
							'AT',
							'CH',
							'ES',
							'IT',
							'PL',
							'FI',
							'NL',
							'BE',
						)
					),
				),
			),
			array(
				'id'                      => 'woo-mercado-pago-custom',
				'title'                   => __( 'Mercado Pago Checkout Pro & Custom', 'woocommerce-admin' ),
				'content'                 => __( 'Accept credit and debit cards, offline (cash or bank transfer) and logged-in payments with money in Mercado Pago. Safe and secure payments with the leading payment processor in LATAM.', 'woocommerce-admin' ),
				'image'                   => plugins_url( 'images/onboarding/mercadopago.png', WC_ADMIN_PLUGIN_FILE ),
				'plugins'                 => array( 'woocommerce-mercadopago' ),
				'is_visible'              => array(
					self::get_rules_for_countries( array( 'AR', 'BR', 'CL', 'CO', 'MX', 'PE', 'UY' ) ),
				),
				'recommendation_priority' => 2,
				'is_local_partner'        => true,
			),
			array(
				'id'         => 'ppcp-gateway',
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
				'id'         => 'cod',
				'title'      => __( 'Cash on delivery', 'woocommerce-admin' ),
				'content'    => __( 'Take payments in cash upon delivery.', 'woocommerce-admin' ),
				'image'      => plugins_url( 'images/onboarding/cod.svg', WC_ADMIN_PLUGIN_FILE ),
				'is_visible' => array(
					self::get_rules_for_cbd( false ),
				),
			),
			array(
				'id'         => 'bacs',
				'title'      => __( 'Direct bank transfer', 'woocommerce-admin' ),
				'content'    => __( 'Take payments via bank transfer.', 'woocommerce-admin' ),
				'image'      => plugins_url( 'images/onboarding/bacs.svg', WC_ADMIN_PLUGIN_FILE ),
				'is_visible' => array(
					self::get_rules_for_cbd( false ),
				),
			),
			array(
				'id'                      => 'woocommerce_payments',
				'title'                   => __( 'WooCommerce Payments', 'woocommerce-admin' ),
				'content'                 => __(
					'Manage transactions without leaving your WordPress Dashboard. Only with WooCommerce Payments.',
					'woocommerce-admin'
				),
				'image'                   => plugins_url( 'images/onboarding/wcpay.svg', WC_ADMIN_PLUGIN_FILE ),
				'plugins'                 => array( 'woocommerce-payments' ),
				'description'             => 'Try the new way to get paid. Securely accept credit and debit cards on your site. Manage transactions without leaving your WordPress dashboard. Only with WooCommerce Payments.',
				'is_visible'              => array(
					self::get_rules_for_cbd( false ),
					self::get_rules_for_countries( self::get_wcpay_countries() ),
				),
				'recommendation_priority' => 1,
			),
			array(
				'id'         => 'razorpay',
				'title'      => __( 'Razorpay', 'woocommerce-admin' ),
				'content'    => __( 'The official Razorpay extension for WooCommerce allows you to accept credit cards, debit cards, netbanking, wallet, and UPI payments.', 'woocommerce-admin' ),
				'image'      => plugins_url( 'images/onboarding/razorpay.svg', WC_ADMIN_PLUGIN_FILE ),
				'plugins'    => array( 'woo-razorpay' ),
				'is_visible' => array(
					(object) array(
						'type'      => 'base_location_country',
						'value'     => 'IN',
						'operation' => '=',
					),
					self::get_rules_for_cbd( false ),
				),
			),
			array(
				'id'         => 'payubiz',
				'title'      => __( 'PayU for WooCommerce', 'woocommerce-admin' ),
				'content'    => __( 'Enable PayU’s exclusive plugin for WooCommerce to start accepting payments in 100+ payment methods available in India including credit cards, debit cards, UPI, & more!', 'woocommerce-admin' ),
				'image'      => plugins_url( 'images/onboarding/payu.svg', WC_ADMIN_PLUGIN_FILE ),
				'plugins'    => array( 'payu-india' ),
				'is_visible' => array(
					(object) array(
						'type'      => 'base_location_country',
						'value'     => 'IN',
						'operation' => '=',
					),
					self::get_rules_for_cbd( false ),
				),
			),
			array(
				'id'         => 'eway',
				'title'      => __( 'eWAY', 'woocommerce-admin' ),
				'content'    => __( 'The eWAY extension for WooCommerce allows you to take credit card payments directly on your store without redirecting your customers to a third party site to make payment.', 'woocommerce-admin' ),
				'image'      => plugins_url( 'images/onboarding/eway.png', WC_ADMIN_PLUGIN_FILE ),
				'plugins'    => array( 'woocommerce-gateway-eway' ),
				'is_visible' => array(
					self::get_rules_for_countries( array( 'AU', 'NZ' ) ),
					self::get_rules_for_cbd( false ),
				),
			),
			array(
				'id'         => 'square_credit_card',
				'title'      => __( 'Square', 'woocommerce-admin' ),
				'content'    => __( 'Securely accept credit and debit cards with one low rate, no surprise fees (custom rates available). Sell online and in store and track sales and inventory in one place.', 'woocommerce-admin' ),
				'image'      => WC()->plugin_url() . '/assets/images/square-black.png',
				'plugins'    => array( 'woocommerce-square' ),
				'is_visible' => array(
					(object) array(
						'type'     => 'or',
						'operands' => (object) array(
							array(
								self::get_rules_for_countries( array( 'US' ) ),
								self::get_rules_for_cbd( true ),
							),
							array(
								self::get_rules_for_countries( array( 'US', 'CA', 'JP', 'GB', 'AU', 'IE' ) ),
								self::get_rules_for_selling_venues( array( 'brick-mortar', 'brick-mortar-other' ) ),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Get array of countries supported by WCPay depending on feature flag.
	 *
	 * @return array Array of countries.
	 */
	public static function get_wcpay_countries() {
		return array( 'US', 'PR', 'AU', 'CA', 'DE', 'ES', 'FR', 'GB', 'IE', 'IT', 'NZ', 'AT', 'BE', 'NL', 'PL', 'PT', 'CH', 'HK', 'SG' );
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
	 * Get rules that match the store's selling venues.
	 *
	 * @param array $selling_venues Array of venues to match.
	 * @return object Rules to match.
	 */
	public static function get_rules_for_selling_venues( $selling_venues ) {
		$rules = array();

		foreach ( $selling_venues as $venue ) {
			$rules[] = (object) array(
				'type'         => 'option',
				'transformers' => array(
					(object) array(
						'use'       => 'dot_notation',
						'arguments' => (object) array(
							'path' => 'selling_venues',
						),
					),
				),
				'option_name'  => 'woocommerce_onboarding_profile',
				'operation'    => '=',
				'value'        => $venue,
				'default'      => array(),
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
