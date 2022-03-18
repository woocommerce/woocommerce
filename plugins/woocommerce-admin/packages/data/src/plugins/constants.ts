/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const STORE_NAME = 'wc/admin/plugins';
export const PAYPAL_NAMESPACE = '/wc-paypal/v1';

/**
 * Plugin slugs and names as key/value pairs.
 */
export const pluginNames = {
	'facebook-for-woocommerce': __(
		'Facebook for WooCommerce',
		'woocommerce-admin'
	),
	jetpack: __( 'Jetpack', 'woocommerce-admin' ),
	'klarna-checkout-for-woocommerce': __(
		'Klarna Checkout for WooCommerce',
		'woocommerce-admin'
	),
	'klarna-payments-for-woocommerce': __(
		'Klarna Payments for WooCommerce',
		'woocommerce-admin'
	),
	'mailchimp-for-woocommerce': __(
		'Mailchimp for WooCommerce',
		'woocommerce-admin'
	),
	'creative-mail-by-constant-contact': __(
		'Creative Mail for WooCommerce',
		'woocommerce-admin'
	),
	'woocommerce-gateway-paypal-express-checkout': __(
		'WooCommerce PayPal',
		'woocommerce-admin'
	),
	'woocommerce-gateway-stripe': __(
		'WooCommerce Stripe',
		'woocommerce-admin'
	),
	'woocommerce-payfast-gateway': __(
		'WooCommerce PayFast',
		'woocommerce-admin'
	),
	'woocommerce-payments': __( 'WooCommerce Payments', 'woocommerce-admin' ),
	'woocommerce-services': __(
		'WooCommerce Shipping & Tax',
		'woocommerce-admin'
	),
	'woocommerce-services:shipping': __(
		'WooCommerce Shipping & Tax',
		'woocommerce-admin'
	),
	'woocommerce-services:tax': __(
		'WooCommerce Shipping & Tax',
		'woocommerce-admin'
	),
	'woocommerce-shipstation-integration': __(
		'WooCommerce ShipStation Gateway',
		'woocommerce-admin'
	),
	'woocommerce-mercadopago': __(
		'Mercado Pago payments for WooCommerce',
		'woocommerce-admin'
	),
	'google-listings-and-ads': __(
		'Google Listings and Ads',
		'woocommerce-admin'
	),
	'woo-razorpay': __( 'Razorpay', 'woocommerce-admin' ),
	mailpoet: __( 'MailPoet', 'woocommerce-admin' ),
};
