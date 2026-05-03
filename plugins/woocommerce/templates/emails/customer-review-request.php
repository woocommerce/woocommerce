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

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

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

<p><?php esc_html_e( 'We’d love to know what you thought of the products you ordered. Your review helps other shoppers make better decisions and helps us improve.', 'woocommerce' ); ?></p>

<?php if ( ! empty( $review_order_url ) ) : ?>
<p>
	<a href="<?php echo esc_url( $review_order_url ); ?>"><?php esc_html_e( 'Leave a review', 'woocommerce' ); ?></a>
</p>
<?php endif; ?>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php if ( $order instanceof WC_Order ) : ?>
<p style="font-size: 12px; line-height: 16px; color: #4d4d4d; margin-top: 16px;">
	<?php
	$date_created = $order->get_date_created();
	printf(
	/* translators: 1: order number, 2: order date */
		esc_html__( 'Order #%1$s (%2$s)', 'woocommerce' ),
		esc_html( $order->get_order_number() ),
		esc_html( $date_created ? wc_format_datetime( $date_created ) : '' )
	);
	?>
</p>
<?php endif; ?>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
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
