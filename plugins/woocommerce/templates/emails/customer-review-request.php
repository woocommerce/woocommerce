<?php
/**
 * Customer review request email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-review-request.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.8.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hook for the woocommerce_email_header.
 *
 * @param string   $email_heading The email heading.
 * @param WC_Email $email         The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) );
} else {
	esc_html_e( 'Hi,', 'woocommerce' );
}
?>
</p>
<p>
<?php
printf(
	/* translators: %s: order date */
	esc_html__( 'Thanks again for your order on %s. We\'d love to hear what you think of the products you received.', 'woocommerce' ),
	esc_html( wc_format_datetime( $order->get_date_created() ) )
);
?>
</p>
<p><?php esc_html_e( 'Leaving a review takes about a minute, and it helps other shoppers a lot.', 'woocommerce' ); ?></p>

<?php if ( ! empty( $review_order_url ) ) : ?>
<p style="margin: 24px 0;">
	<a href="<?php echo esc_url( $review_order_url ); ?>" style="display: inline-block; padding: 12px 24px; background-color: #3858e9; color: #ffffff; text-decoration: none; border-radius: 2px; font-weight: 600;">
		<?php esc_html_e( 'Review your products', 'woocommerce' ); ?>
	</a>
</p>
<?php endif; ?>

<?php
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
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/**
 * Hook for the woocommerce_email_footer.
 *
 * @param WC_Email $email The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
