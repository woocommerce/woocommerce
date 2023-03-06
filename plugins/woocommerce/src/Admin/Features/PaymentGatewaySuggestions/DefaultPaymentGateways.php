<?php
/**
 * Gets a list of fallback methods if remote fetching is disabled.
 */

namespace Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions;

defined( 'ABSPATH' ) || exit;

/**
 * Default Payment Gateways
 */
class DefaultPaymentGateways {
	/**
	 * Priority is used to determine which payment gateway to recommend first.
	 * The lower the number, the higher the priority.
	 *
	 * @var array
	 */
	private static $recommendation_priority = array(
		'woocommerce_payments'                            => 1,
		'woocommerce_payments:with-in-person-payments'    => 1,
		'woocommerce_payments:without-in-person-payments' => 1,
		'stripe'                                          => 2,
		'woo-mercado-pago-custom'                         => 3,
		// PayPal Payments.
		'ppcp-gateway'                                    => 4,
		'mollie_wc_gateway_banktransfer'                  => 5,
		'razorpay'                                        => 5,
		'payfast'                                         => 5,
		'payubiz'                                         => 6,
		'square_credit_card'                              => 6,
		'klarna_payments'                                 => 6,
		// Klarna Checkout.
		'kco'                                             => 6,
		'paystack'                                        => 6,
		'eway'                                            => 7,
		'amazon_payments_advanced'                        => 7,
		'affirm'                                          => 8,
		'afterpay'                                        => 9,
	);

	/**
	 * Get default specs.
	 *
	 * @return array Default specs.
	 */
	public static function get_all() {
		$payment_gateways = array(
			array(
				'id'                  => 'payfast',
				'title'               => __( 'PayFast', 'woocommerce' ),
				'content'             => __( 'The PayFast extension for WooCommerce enables you to accept payments by Credit Card and EFT via one of South Africa’s most popular payment gateways. No setup fees or monthly subscription costs. Selecting this extension will configure your store to use South African rands as the selected currency.', 'woocommerce' ),
				'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/payfast.png',
				'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/payfast.png',
				'plugins'             => array( 'woocommerce-payfast-gateway' ),
				'is_visible'          => array(
					self::get_rules_for_countries( array( 'ZA', 'GH', 'NG' ) ),
					self::get_rules_for_cbd( false ),
				),
				'category_other'      => array( 'ZA', 'GH', 'NG' ),
				'category_additional' => array(),
			),
			array(
				'id'                  => 'stripe',
				'title'               => __( ' Stripe', 'woocommerce' ),
				'content'             => __( 'Accept debit and credit cards in 135+ currencies, methods such as Alipay, and one-touch checkout with Apple Pay.', 'woocommerce' ),
				'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/stripe.png',
				'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/stripe.png',
				'plugins'             => array( 'woocommerce-gateway-stripe' ),
				'is_visible'          => array(
					// https://stripe.com/global.
					self::get_rules_for_countries(
						array( 'AU', 'AT', 'BE', 'BG', 'BR', 'CA', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HK', 'IN', 'IE', 'IT', 'JP', 'LV', 'LT', 'LU', 'MY', 'MT', 'MX', 'NL', 'NZ', 'NO', 'PL', 'PT', 'RO', 'SG', 'SK', 'SI', 'ES', 'SE', 'CH', 'GB', 'US', 'PR', 'HU', 'SL', 'ID', 'MY', 'SI', 'PR' )
					),
					self::get_rules_for_cbd( false ),
				),
				'category_other'      => array( 'AU', 'AT', 'BE', 'BG', 'BR', 'CA', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HK', 'IN', 'IE', 'IT', 'JP', 'LV', 'LT', 'LU', 'MY', 'MT', 'MX', 'NL', 'NZ', 'NO', 'PL', 'PT', 'RO', 'SG', 'SK', 'SI', 'ES', 'SE', 'CH', 'GB', 'US', 'PR', 'HU', 'SL', 'ID', 'MY', 'SI', 'PR' ),
				'category_additional' => array(),
			),
			array(
				'id'                  => 'paystack',
				'title'               => __( 'Paystack', 'woocommerce' ),
				'content'             => __( 'Paystack helps African merchants accept one-time and recurring payments online with a modern, safe, and secure payment gateway.', 'woocommerce' ),
				'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/paystack.png',
				'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/paystack.png',
				'plugins'             => array( 'woo-paystack' ),
				'is_visible'          => array(
					self::get_rules_for_countries( array( 'ZA', 'GH', 'NG' ) ),
					self::get_rules_for_cbd( false ),
				),
				'category_other'      => array( 'ZA', 'GH', 'NG' ),
				'category_additional' => array(),
			),
			array(
				'id'                  => 'kco',
				'title'               => __( 'Klarna Checkout', 'woocommerce' ),
				'content'             => __( 'Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries.', 'woocommerce' ),
				'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/klarna-black.png',
				'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/klarna.png',
				'plugins'             => array( 'klarna-checkout-for-woocommerce' ),
				'is_visible'          => array(
					self::get_rules_for_countries( array( 'SE', 'FI', 'NO' ) ),
					self::get_rules_for_cbd( false ),
				),
				'category_other'      => array( 'SE', 'FI', 'NO' ),
				'category_additional' => array(),
			),
			array(
				'id'                  => 'klarna_payments',
				'title'               => __( 'Klarna Payments', 'woocommerce' ),
				'content'             => __( 'Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries.', 'woocommerce' ),
				'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/klarna-black.png',
				'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/klarna.png',
				'plugins'             => array( 'klarna-payments-for-woocommerce' ),
				'is_visible'          => array(
					self::get_rules_for_countries(
						array( 'DK', 'DE', 'AT', 'NL', 'CH', 'BE', 'SP', 'PL', 'FR', 'IT', 'GB', 'ES', 'FI', 'NO', 'SE', 'ES', 'FI', 'NO', 'SE' )
					),
					self::get_rules_for_cbd( false ),
				),
				'category_other'      => array(),
				'category_additional' => array( 'DK', 'DE', 'AT', 'NL', 'CH', 'BE', 'SP', 'PL', 'FR', 'IT', 'GB', 'ES', 'FI', 'NO', 'SE', 'ES', 'FI', 'NO', 'SE' ),
			),
			array(
				'id'                  => 'mollie_wc_gateway_banktransfer',
				'title'               => __( 'Mollie', 'woocommerce' ),
				'content'             => __( 'Effortless payments by Mollie: Offer global and local payment methods, get onboarded in minutes, and supported in your language.', 'woocommerce' ),
				'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/mollie.svg',
				'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/mollie.png',
				'plugins'             => array( 'mollie-payments-for-woocommerce' ),
				'is_visible'          => array(
					self::get_rules_for_countries(
						array( 'FR', 'DE', 'GB', 'AT', 'CH', 'ES', 'IT', 'PL', 'FI', 'NL', 'BE' )
					),
				),
				'category_other'      => array( 'FR', 'DE', 'GB', 'AT', 'CH', 'ES', 'IT', 'PL', 'FI', 'NL', 'BE' ),
				'category_additional' => array(),
			),
			array(
				'id'                  => 'woo-mercado-pago-custom',
				'title'               => __( 'Mercado Pago Checkout Pro & Custom', 'woocommerce' ),
				'content'             => __( 'Accept credit and debit cards, offline (cash or bank transfer) and logged-in payments with money in Mercado Pago. Safe and secure payments with the leading payment processor in LATAM.', 'woocommerce' ),
				'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/mercadopago.png',
				'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/mercadopago.png',
				'plugins'             => array( 'woocommerce-mercadopago' ),
				'is_visible'          => array(
					self::get_rules_for_countries( array( 'AR', 'BR', 'CL', 'CO', 'MX', 'PE', 'UY' ) ),
				),
				'is_local_partner'    => true,
				'category_other'      => array( 'AR', 'BR', 'CL', 'CO', 'MX', 'PE', 'UY' ),
				'category_additional' => array(),
			),
			array(
				'id'                  => 'ppcp-gateway',
				'title'               => __( 'PayPal Payments', 'woocommerce' ),
				'content'             => __( "Safe and secure payments using credit cards or your customer's PayPal account.", 'woocommerce' ),
				'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/paypal.png',
				'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/paypal.png',
				'plugins'             => array( 'woocommerce-paypal-payments' ),
				'is_visible'          => array(
					(object) array(
						'type'      => 'base_location_country',
						'value'     => 'IN',
						'operation' => '!=',
					),
					self::get_rules_for_cbd( false ),
				),
				'category_other'      => array( 'US', 'CA', 'AT', 'BE', 'BG', 'HR', 'CH', 'CY', 'CZ', 'DK', 'EE', 'ES', 'FI', 'FR', 'DE', 'GB', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'NO', 'PL', 'PT', 'RO', 'SK', 'SL', 'SE', 'MX', 'BR', 'AR', 'CL', 'CO', 'EC', 'PE', 'UY', 'VE', 'AU', 'NZ', 'HK', 'JP', 'SG', 'CN', 'ID', 'ZA', 'NG', 'GH' ),
				'category_additional' => array( 'US', 'CA', 'AT', 'BE', 'BG', 'HR', 'CH', 'CY', 'CZ', 'DK', 'EE', 'ES', 'FI', 'FR', 'DE', 'GB', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'NO', 'PL', 'PT', 'RO', 'SK', 'SL', 'SE', 'MX', 'BR', 'AR', 'CL', 'CO', 'EC', 'PE', 'UY', 'VE', 'AU', 'NZ', 'HK', 'JP', 'SG', 'CN', 'ID', 'IN', 'ZA', 'NG', 'GH' ),
			),
			array(
				'id'          => 'cod',
				'title'       => __( 'Cash on delivery', 'woocommerce' ),
				'content'     => __( 'Take payments in cash upon delivery.', 'woocommerce' ),
				'image'       => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/cod.svg',
				'image_72x72' => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/cod.png',
				'is_visible'  => array(
					self::get_rules_for_cbd( false ),
				),
				'is_offline'  => true,
			),
			array(
				'id'          => 'bacs',
				'title'       => __( 'Direct bank transfer', 'woocommerce' ),
				'content'     => __( 'Take payments via bank transfer.', 'woocommerce' ),
				'image'       => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/bacs.svg',
				'image_72x72' => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/bacs.png',
				'is_visible'  => array(
					self::get_rules_for_cbd( false ),
				),
				'is_offline'  => true,
			),
			// This is for backwards compatibility only (WC < 5.10.0-dev or WCA < 2.9.0-dev).
			array(
				'id'          => 'woocommerce_payments',
				'title'       => __( 'WooCommerce Payments', 'woocommerce' ),
				'content'     => __(
					'Manage transactions without leaving your WordPress Dashboard. Only with WooCommerce Payments.',
					'woocommerce'
				),
				'image'       => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/wcpay.svg',
				'image_72x72' => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/wcpay.svg',
				'plugins'     => array( 'woocommerce-payments' ),
				'description' => __( 'With WooCommerce Payments, you can securely accept major cards, Apple Pay, and payments in over 100 currencies. Track cash flow and manage recurring revenue directly from your store’s dashboard - with no setup costs or monthly fees.', 'woocommerce' ),
				'is_visible'  => array(
					self::get_rules_for_cbd( false ),
					self::get_rules_for_countries( self::get_wcpay_countries() ),
					(object) array(
						'type'     => 'plugin_version',
						'plugin'   => 'woocommerce',
						'version'  => '5.10.0-dev',
						'operator' => '<',
					),
					(object) array(
						'type'     => 'or',
						'operands' => (object) array(
							(object) array(
								'type'    => 'not',
								'operand' => [
									(object) array(
										'type'    => 'plugins_activated',
										'plugins' => [ 'woocommerce-admin' ],
									),
								],
							),
							(object) array(
								'type'     => 'plugin_version',
								'plugin'   => 'woocommerce-admin',
								'version'  => '2.9.0-dev',
								'operator' => '<',
							),
						),
					),
				),
			),
			array(
				'id'          => 'woocommerce_payments:without-in-person-payments',
				'title'       => __( 'WooCommerce Payments', 'woocommerce' ),
				'content'     => __(
					'Manage transactions without leaving your WordPress Dashboard. Only with WooCommerce Payments.',
					'woocommerce'
				),
				'image'       => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/wcpay.svg',
				'image_72x72' => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/wcpay.svg',
				'plugins'     => array( 'woocommerce-payments' ),
				'description' => __( 'With WooCommerce Payments, you can securely accept major cards, Apple Pay, and payments in over 100 currencies. Track cash flow and manage recurring revenue directly from your store’s dashboard - with no setup costs or monthly fees.', 'woocommerce' ),
				'is_visible'  => array(
					self::get_rules_for_cbd( false ),
					self::get_rules_for_countries( array_diff( self::get_wcpay_countries(), array( 'US', 'CA' ) ) ),
					(object) array(
						'type'     => 'or',
						// Older versions of WooCommerce Admin require the ID to be `woocommerce-payments` to show the suggestion card.
						'operands' => (object) array(
							(object) array(
								'type'     => 'plugin_version',
								'plugin'   => 'woocommerce-admin',
								'version'  => '2.9.0-dev',
								'operator' => '>=',
							),
							(object) array(
								'type'     => 'plugin_version',
								'plugin'   => 'woocommerce',
								'version'  => '5.10.0-dev',
								'operator' => '>=',
							),
						),
					),
				),
			),
			// This is the same as the above, but with a different description for countries that support in-person payments such as US and CA.
			array(
				'id'          => 'woocommerce_payments:with-in-person-payments',
				'title'       => __( 'WooCommerce Payments', 'woocommerce' ),
				'content'     => __(
					'Manage transactions without leaving your WordPress Dashboard. Only with WooCommerce Payments.',
					'woocommerce'
				),
				'image'       => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/wcpay.svg',
				'image_72x72' => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/wcpay.svg',
				'plugins'     => array( 'woocommerce-payments' ),
				'description' => __( 'With WooCommerce Payments, you can securely accept major cards, Apple Pay, and payments in over 100 currencies – with no setup costs or monthly fees – and you can now accept in-person payments with the Woo mobile app.', 'woocommerce' ),
				'is_visible'  => array(
					self::get_rules_for_cbd( false ),
					self::get_rules_for_countries( array( 'US', 'CA' ) ),
					(object) array(
						'type'     => 'or',
						// Older versions of WooCommerce Admin require the ID to be `woocommerce-payments` to show the suggestion card.
						'operands' => (object) array(
							(object) array(
								'type'     => 'plugin_version',
								'plugin'   => 'woocommerce-admin',
								'version'  => '2.9.0-dev',
								'operator' => '>=',
							),
							(object) array(
								'type'     => 'plugin_version',
								'plugin'   => 'woocommerce',
								'version'  => '5.10.0-dev',
								'operator' => '>=',
							),
						),
					),
				),
			),
			array(
				'id'                  => 'razorpay',
				'title'               => __( 'Razorpay', 'woocommerce' ),
				'content'             => __( 'The official Razorpay extension for WooCommerce allows you to accept credit cards, debit cards, netbanking, wallet, and UPI payments.', 'woocommerce' ),
				'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/razorpay.svg',
				'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/razorpay.png',
				'plugins'             => array( 'woo-razorpay' ),
				'is_visible'          => array(
					(object) array(
						'type'      => 'base_location_country',
						'value'     => 'IN',
						'operation' => '=',
					),
					self::get_rules_for_cbd( false ),
				),
				'category_other'      => array( 'IN' ),
				'category_additional' => array(),
			),
			array(
				'id'                  => 'payubiz',
				'title'               => __( 'PayU for WooCommerce', 'woocommerce' ),
				'content'             => __( 'Enable PayU’s exclusive plugin for WooCommerce to start accepting payments in 100+ payment methods available in India including credit cards, debit cards, UPI, & more!', 'woocommerce' ),
				'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/payu.svg',
				'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/payu.png',
				'plugins'             => array( 'payu-india' ),
				'is_visible'          => array(
					(object) array(
						'type'      => 'base_location_country',
						'value'     => 'IN',
						'operation' => '=',
					),
					self::get_rules_for_cbd( false ),
				),
				'category_other'      => array( 'IN' ),
				'category_additional' => array(),
			),
			array(
				'id'                  => 'eway',
				'title'               => __( 'Eway', 'woocommerce' ),
				'content'             => __( 'The Eway extension for WooCommerce allows you to take credit card payments directly on your store without redirecting your customers to a third party site to make payment.', 'woocommerce' ),
				'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/eway.png',
				'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/eway.png',
				'plugins'             => array( 'woocommerce-gateway-eway' ),
				'is_visible'          => array(
					self::get_rules_for_countries( array( 'AU', 'NZ' ) ),
					self::get_rules_for_cbd( false ),
				),
				'category_other'      => array( 'AU', 'NZ' ),
				'category_additional' => array(),
			),
			array(
				'id'                  => 'square_credit_card',
				'title'               => __( 'Square', 'woocommerce' ),
				'content'             => __( 'Securely accept credit and debit cards with one low rate, no surprise fees (custom rates available). Sell online and in store and track sales and inventory in one place.', 'woocommerce' ),
				'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/square-black.png',
				'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/square.png',
				'plugins'             => array( 'woocommerce-square' ),
				'is_visible'          => array(
					(object) array(
						'type'     => 'or',
						'operands' => (object) array(
							array(
								self::get_rules_for_countries( array( 'US' ) ),
								self::get_rules_for_cbd( true ),
							),
							array(
								self::get_rules_for_countries( array( 'US', 'CA', 'JP', 'GB', 'AU', 'IE', 'FR', 'ES', 'FI' ) ),
								self::get_rules_for_selling_venues( array( 'brick-mortar', 'brick-mortar-other' ) ),
							),
						),
					),
				),
				'category_other'      => array( 'US', 'CA', 'JP', 'GB', 'AU', 'IE', 'FR', 'ES', 'FI' ),
				'category_additional' => array(),
			),
			array(
				'id'                  => 'afterpay',
				'title'               => __( 'Afterpay', 'woocommerce' ),
				'content'             => __( 'Afterpay allows customers to receive products immediately and pay for purchases over four installments, always interest-free.', 'woocommerce' ),
				'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/afterpay.png',
				'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/afterpay.png',
				'plugins'             => array( 'afterpay-gateway-for-woocommerce' ),
				'is_visible'          => array(
					self::get_rules_for_countries( array( 'US', 'CA' ) ),
				),
				'category_other'      => array(),
				'category_additional' => array( 'US', 'CA' ),
			),
			array(
				'id'                  => 'amazon_payments_advanced',
				'title'               => __( 'Amazon Pay', 'woocommerce' ),
				'content'             => __( 'Enable a familiar, fast checkout for hundreds of millions of active Amazon customers globally.', 'woocommerce' ),
				'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/amazonpay.png',
				'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/amazonpay.png',
				'plugins'             => array( 'woocommerce-gateway-amazon-payments-advanced' ),
				'is_visible'          => array(
					self::get_rules_for_countries( array( 'US', 'GB', 'JP', 'AT', 'BE', 'CY', 'DK', 'ES', 'FR', 'DE', 'HU', 'IE', 'IT', 'LU', 'NL', 'PT', 'SL', 'SE' ) ),
				),
				'category_other'      => array(),
				'category_additional' => array( 'US', 'GB', 'JP', 'AT', 'BE', 'CY', 'DK', 'ES', 'FR', 'DE', 'HU', 'IE', 'IT', 'LU', 'NL', 'PT', 'SL', 'SE' ),
			),
			array(
				'id'                  => 'affirm',
				'title'               => __( 'Affirm', 'woocommerce' ),
				'content'             => __( 'Affirm’s tailored Buy Now Pay Later programs remove price as a barrier, turning browsers into buyers, increasing average order value, and expanding your customer base.', 'woocommerce' ),
				'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/affirm.png',
				'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/affirm.png',
				'plugins'             => array(),
				'external_link'       => 'https://woocommerce.com/products/woocommerce-gateway-affirm',
				'is_visible'          => array(
					self::get_rules_for_countries( array( 'US', 'CA' ) ),
				),
				'category_other'      => array(),
				'category_additional' => array( 'US', 'CA' ),
			),
		);

		foreach ( $payment_gateways as $index => $payment_gateway ) {
			$payment_gateways[ $index ]['recommendation_priority'] = self::get_recommendation_priority( $payment_gateway['id'] );
		}

		return $payment_gateways;
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

	/**
	 * Get recommendation priority for a given payment gateway by id.
	 * If no priority is set or the id is not found, return null.
	 *
	 * @param string $id Payment gateway id.
	 * @return int Priority.
	 */
	private static function get_recommendation_priority( $id ) {
		if ( ! $id || ! array_key_exists( $id, self::$recommendation_priority ) ) {
			return null;
		}
		return self::$recommendation_priority[ $id ];
	}
}
