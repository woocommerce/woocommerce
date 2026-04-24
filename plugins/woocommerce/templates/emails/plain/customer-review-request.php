<?php
/**
 * Customer review request email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-review-request.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.8.0
 */

// phpcs:disable Universal.WhiteSpace.PrecisionAlignment.Found, Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed -- Plain text output needs specific spacing without tabs

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";

if ( ! empty( $review_order_url ) ) {
	echo wp_kses_post(
		sprintf(
			/* translators: %s: Review order link */
			__( 'We\'d love to know what you thought of the products you ordered. Your review helps other shoppers make better decisions and helps us improve. %s', 'woocommerce' ),
			esc_url( $review_order_url )
		)
	) . "\n\n";
} else {
	echo esc_html__( 'We\'d love to know what you thought of the products you ordered. Your review helps other shoppers make better decisions and helps us improve.', 'woocommerce' ) . "\n\n";
}

/**
 * Hook for the woocommerce_email_order_details.
 *
 * @param WC_Order $order         The order object.
 * @param bool     $sent_to_admin Whether the email is sent to admin.
 * @param bool     $plain_text    Whether the email is plain text.
 * @param WC_Email $email         The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/**
 * Hook for the woocommerce_email_order_meta.
 *
 * @param WC_Order $order         The order object.
 * @param bool     $sent_to_admin Whether the email is sent to admin.
 * @param bool     $plain_text    Whether the email is plain text.
 * @param WC_Email $email         The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for the woocommerce_email_customer_details.
 *
 * @param WC_Order $order         The order object.
 * @param bool     $sent_to_admin Whether the email is sent to admin.
 * @param bool     $plain_text    Whether the email is plain text.
 * @param WC_Email $email         The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
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
 * Filter the email footer text.
 *
 * @param string $footer_text The footer text.
 * @since 2.3.0
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

// phpcs:enable Universal.WhiteSpace.PrecisionAlignment.Found, Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed
