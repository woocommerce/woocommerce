<?php
/**
 * Settings for PayPal Standard Gateway.
 *
 * @package WooCommerce\Classes\Payment
 */

declare(strict_types=1);

use Automattic\WooCommerce\Utilities\LoggingUtil;

defined( 'ABSPATH' ) || exit;

$settings = array(
	'enabled'          => array(
		'title'   => __( 'Enable/Disable', 'woocommerce' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable PayPal Standard', 'woocommerce' ),
		'default' => 'no',
	),
	'title'            => array(
		'title'       => __( 'Title', 'woocommerce' ),
		'type'        => 'safe_text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
		'default'     => __( 'PayPal', 'woocommerce' ),
		'desc_tip'    => true,
	),
	'description'      => array(
		'title'       => __( 'Description', 'woocommerce' ),
		'type'        => 'text',
		'desc_tip'    => true,
		'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
		'default'     => __( "Pay via PayPal; you can pay with your credit card if you don't have a PayPal account.", 'woocommerce' ),
	),
	'email'            => array(
		'title'       => __( 'PayPal email', 'woocommerce' ),
		'type'        => 'email',
		'description' => __( 'Please enter your PayPal email address; this is needed in order to take payment.', 'woocommerce' ),
		'default'     => get_option( 'admin_email' ),
		'desc_tip'    => true,
		'placeholder' => 'you@youremail.com',
	),
	'advanced'         => array(
		'title'       => __( 'Advanced options', 'woocommerce' ),
		'type'        => 'title',
		'description' => '',
	),
	'testmode'         => array(
		'title'       => __( 'PayPal sandbox', 'woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable PayPal sandbox', 'woocommerce' ),
		'default'     => 'no',
		/* translators: %s: URL */
		'description' => sprintf( __( 'PayPal sandbox can be used to test payments. Sign up for a <a href="%s">developer account</a>.', 'woocommerce' ), 'https://developer.paypal.com/' ),
	),
	'paymentaction'    => array(
		'title'       => __( 'Payment action', 'woocommerce' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'description' => __( 'Choose whether you wish to capture funds immediately or authorize payment only.', 'woocommerce' ),
		'default'     => 'sale',
		'desc_tip'    => true,
		'options'     => array(
			'sale'          => __( 'Capture', 'woocommerce' ),
			'authorization' => __( 'Authorize', 'woocommerce' ),
		),
	),
	'paypal_buttons'   => array(
		'title'       => __( 'PayPal Buttons', 'woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable PayPal Buttons', 'woocommerce' ),
		'default'     => 'yes',
		'description' => __( 'Enable PayPal buttons to offer PayPal, Venmo and Pay Later as express checkout options on product, cart, and checkout pages.', 'woocommerce' ),
	),
	'invoice_prefix'   => array(
		'title'       => __( 'Invoice prefix', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'woocommerce' ),
		'default'     => 'WC-',
		'desc_tip'    => true,
	),
	'send_shipping'    => array(
		'title'       => __( 'Shipping details', 'woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Send shipping details to PayPal instead of billing.', 'woocommerce' ),
		'description' => __( 'PayPal allows us to send one address. If you are using PayPal for shipping labels you may prefer to send the shipping address rather than billing. Turning this option off may prevent PayPal Seller protection from applying.', 'woocommerce' ),
		'default'     => 'yes',
	),
	'address_override' => array(
		'title'       => __( 'Address override', 'woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Prevent buyers from changing the shipping address.', 'woocommerce' ),
		'description' => __( 'When enabled, PayPal will use the address provided by the checkout form, and prevent the buyer from changing it inside the PayPal payment page. Disable this to let buyers choose a shipping address from their PayPal account. PayPal verifies addresses therefore this setting can cause errors (we recommend keeping it disabled).', 'woocommerce' ),
		'default'     => 'no',
	),
	'debug'            => array(
		'title'       => __( 'Debug log', 'woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable logging', 'woocommerce' ),
		'default'     => 'no',
		/* translators: %s: URL */
		'description' => sprintf(
			// translators: %s is a placeholder for a URL.
			__( 'Log PayPal events such as IPN requests and review them on the <a href="%s">Logs screen</a>. Note: this may log personal information. We recommend using this for debugging purposes only and deleting the logs when finished.', 'woocommerce' ),
			esc_url( LoggingUtil::get_logs_tab_url() )
		),
	),
);


$legacy_settings = array(
	'image_url'             => array(
		'title'       => __( 'Image url', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'Optionally enter the URL to a 150x50px image displayed as your logo in the upper left corner of the PayPal checkout pages.', 'woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => __( 'Optional', 'woocommerce' ),
		'is_legacy'   => true,
	),
	'ipn_notification'      => array(
		'title'       => __( 'IPN email notifications', 'woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable IPN email notifications', 'woocommerce' ),
		'default'     => 'yes',
		'description' => __( 'Send notifications when an IPN is received from PayPal indicating refunds, chargebacks and cancellations.', 'woocommerce' ),
		'is_legacy'   => true,
	),
	'receiver_email'        => array(
		'title'       => __( 'Receiver email', 'woocommerce' ),
		'type'        => 'email',
		'description' => __( 'If your main PayPal email differs from the PayPal email entered above, input your main receiver email for your PayPal account here. This is used to validate IPN requests.', 'woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => 'you@youremail.com',
		'is_legacy'   => true,
	),
	'identity_token'        => array(
		'title'       => __( 'PayPal identity token', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'Optionally enable "Payment Data Transfer" (Profile > Profile and Settings > My Selling Tools > Website Preferences) and then copy your identity token here. This will allow payments to be verified without the need for PayPal IPN.', 'woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => '',
		'is_legacy'   => true,
	),
	'api_details'           => array(
		'title'       => __( 'API credentials', 'woocommerce' ),
		'type'        => 'title',
		/* translators: %s: URL */
		'description' => sprintf( __( 'Enter your PayPal API credentials to process refunds via PayPal. Learn how to access your <a href="%s">PayPal API Credentials</a>.', 'woocommerce' ), 'https://developer.paypal.com/webapps/developer/docs/classic/api/apiCredentials/#create-an-api-signature' ),
		'is_legacy'   => true,
	),
	'api_username'          => array(
		'title'       => __( 'Live API username', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'Get your API credentials from PayPal.', 'woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => __( 'Optional', 'woocommerce' ),
		'is_legacy'   => true,
	),
	'api_password'          => array(
		'title'       => __( 'Live API password', 'woocommerce' ),
		'type'        => 'password',
		'description' => __( 'Get your API credentials from PayPal.', 'woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => __( 'Optional', 'woocommerce' ),
		'is_legacy'   => true,
	),
	'api_signature'         => array(
		'title'       => __( 'Live API signature', 'woocommerce' ),
		'type'        => 'password',
		'description' => __( 'Get your API credentials from PayPal.', 'woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => __( 'Optional', 'woocommerce' ),
		'is_legacy'   => true,
	),
	'sandbox_api_username'  => array(
		'title'       => __( 'Sandbox API username', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'Get your API credentials from PayPal.', 'woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => __( 'Optional', 'woocommerce' ),
		'is_legacy'   => true,
	),
	'sandbox_api_password'  => array(
		'title'       => __( 'Sandbox API password', 'woocommerce' ),
		'type'        => 'password',
		'description' => __( 'Get your API credentials from PayPal.', 'woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => __( 'Optional', 'woocommerce' ),
		'is_legacy'   => true,
	),
	'sandbox_api_signature' => array(
		'title'       => __( 'Sandbox API signature', 'woocommerce' ),
		'type'        => 'password',
		'description' => __( 'Get your API credentials from PayPal.', 'woocommerce' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => __( 'Optional', 'woocommerce' ),
		'is_legacy'   => true,
	),
);

return array_merge( $settings, $legacy_settings );
