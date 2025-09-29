<?php
/**
 * Customer POS refunded order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-pos-refunded-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.0.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Hook for the woocommerce_email_header.
 *
 * @hooked WC_Email_Customer_POS_*::email_header() Output the email header
 * @since 10.0.0
 */
do_action( 'woocommerce_pos_email_header', $email_heading, $email ); ?>

<div class="email-introduction">
<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) );
} else {
	printf( esc_html__( 'Hi there,', 'woocommerce' ) );
}
?>
</p>

<p>
<?php
if ( $partial_refund ) {
	/* translators: %s: Site title */
	echo sprintf( esc_html__( 'Your order from %s has been partially refunded.', 'woocommerce' ), esc_html( $pos_store_name ) ) . "\n\n";
} else {
	/* translators: %s: Site title */
	echo sprintf( esc_html__( 'Your order from %s has been refunded.', 'woocommerce' ), esc_html( $pos_store_name ) ) . "\n\n";
}
echo '</p><p>';
echo esc_html__( 'Here’s a reminder of what you’ve bought:', 'woocommerce' ) . "\n\n";
?>
</p>
</div>

<?php

/**
 * Show order details.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

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

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/**
 * Show store information - store details are set in the Point of Sale settings.
 */
if ( ! empty( $pos_store_email ) || ! empty( $pos_store_phone_number ) || ! empty( $pos_store_address ) ) {
	echo '<div class="pos-store-information">';
	if ( ! empty( $pos_store_name ) ) {
		echo '<h2>' . esc_html( $pos_store_name ) . '</h2>';
	}
	if ( ! empty( $pos_store_email ) ) {
		echo '<p>' . esc_html( $pos_store_email ) . '</p>';
	}
	if ( ! empty( $pos_store_phone_number ) ) {
		echo '<p>' . esc_html( $pos_store_phone_number ) . '</p>';
	}
	if ( ! empty( $pos_store_address ) ) {
		echo wp_kses_post( wpautop( wptexturize( $pos_store_address ) ) );
	}
	echo '</div>';
}

/**
 * Show refund & returns policy - this is set in the Point of Sale settings.
 */
if ( ! empty( $pos_refund_returns_policy ) ) {
	echo '<div class="refund-returns-policy">';
	echo '<h2>' . esc_html__( 'Refund & Returns Policy', 'woocommerce' ) . '</h2>';
	echo wp_kses_post( wpautop( wptexturize( $pos_refund_returns_policy ) ) );
	echo '</div>';
}

/**
 * Hook for the woocommerce_email_footer.
 *
 * @hooked WC_Email_Customer_POS_*::email_footer() Output the email footer
 * @since 10.0.0
*/
do_action( 'woocommerce_pos_email_footer', $email );
