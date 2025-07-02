<?php
/**
 * Customer POS completed order email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-pos-completed-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.0.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );
$email                      = $email ?? null;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
} else {
	echo esc_html__( 'Hi there,', 'woocommerce' ) . "\n\n";
}
echo esc_html__( 'Here’s a reminder of what you’ve ordered:', 'woocommerce' ) . "\n\n";

/**
 * Show the order details table, generate and output structured data.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/**
 * Show order meta data.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 * @since 1.0.0
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show customer details and email address.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 * @since 1.0.0
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

/**
 * Show store information - store details are set in the Point of Sale settings.
 */
if ( ! empty( $pos_store_email ) || ! empty( $pos_store_phone_number ) || ! empty( $pos_store_address ) ) {
	if ( ! empty( $pos_store_name ) ) {
		echo "\n" . esc_html( $pos_store_name ) . "\n\n";
	}
	if ( ! empty( $pos_store_email ) ) {
		echo esc_html( $pos_store_email ) . "\n";
	}
	if ( ! empty( $pos_store_phone_number ) ) {
		echo esc_html( $pos_store_phone_number ) . "\n";
	}
	if ( ! empty( $pos_store_address ) ) {
		echo esc_html( wp_strip_all_tags( wptexturize( $pos_store_address ) ) ) . "\n";
	}
	echo "\n----------------------------------------\n\n";
}

/**
 * Show refund & returns policy - this is set in the Point of Sale settings.
 */
if ( ! empty( $pos_refund_returns_policy ) ) {
	echo "\n" . esc_html__( 'Refund & Returns Policy', 'woocommerce' ) . "\n\n";
	echo esc_html( wp_strip_all_tags( wptexturize( $pos_refund_returns_policy ) ) ) . "\n";
	echo "\n----------------------------------------\n\n";
}

/**
 * Filter the email footer text.
 *
 * @since 4.0.0
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ), $email ) );
