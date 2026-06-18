<?php
/**
 * Admin email about a renewal authentication retry.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/failed-renewal-authentication-requested.php.
 *
 * @package WooCommerce\Templates\Emails
 * @version 11.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hook for the woocommerce_email_header.
 *
 * @hooked WC_Emails::email_header() Output the email header
 * @since 3.7.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
	<?php
	printf(
		/* translators: %1$s: order number, %2$s: customer full name, %3$s: retry time. */
		esc_html_x( 'The automatic recurring payment for order %1$s from %2$s has failed. The customer was sent an email requesting authentication of payment. If the customer does not authenticate the payment, they will be requested by email again %3$s.', 'In admin renewal failed email', 'woocommerce' ),
		esc_html( $order->get_order_number() ),
		esc_html( $order->get_formatted_billing_full_name() ),
		esc_html( $retry_time )
	);
	?>
</p>
<p><?php esc_html_e( 'The renewal order is as follows:', 'woocommerce' ); ?></p>

<?php
/**
 * Hook for the woocommerce_email_order_details.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for the woocommerce_email_order_meta.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 * @since 1.0.0
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for woocommerce_email_customer_details.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 * @since 1.0.0
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for the woocommerce_email_footer.
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 * @since 3.7.0
 */
do_action( 'woocommerce_email_footer', $email );
