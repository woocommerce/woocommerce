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
 * @version 9.9.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Hook for the woocommerce_email_header.
 *
 * @hooked WC_Emails::email_header() Output the email header
 * @since 3.7.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) );
} else {
	printf( esc_html__( 'Hi,', 'woocommerce' ) );
}
?>
</p>

<p>
<?php
if ( $email_improvements_enabled ) {
	if ( $partial_refund ) {
		/* translators: %s: Site title */
		echo sprintf( esc_html__( 'Your order from %s has been partially refunded.', 'woocommerce' ), esc_html( $blogname ) ) . "\n\n";
	} else {
		/* translators: %s: Site title */
		echo sprintf( esc_html__( 'Your order from %s has been refunded.', 'woocommerce' ), esc_html( $blogname ) ) . "\n\n";
	}
	echo '</p><p>';
	echo esc_html__( 'Here’s a reminder of what you’ve ordered:', 'woocommerce' ) . "\n\n";

} elseif ( $partial_refund ) {
	/* translators: %s: Site title */
	printf( esc_html__( 'Your order on %s has been partially refunded. There are more details below for your reference:', 'woocommerce' ), esc_html( $blogname ) );
} else {
	/* translators: %s: Site title */
	printf( esc_html__( 'Your order on %s has been refunded. There are more details below for your reference:', 'woocommerce' ), esc_html( $blogname ) );
}
?>
</p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

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
 * Hook for the woocommerce_email_footer.
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 * @since 4.0.0
 */
do_action( 'woocommerce_email_footer', $email );
