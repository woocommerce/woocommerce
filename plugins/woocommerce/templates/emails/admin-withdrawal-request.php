<?php
/**
 * Admin new withdrawal request email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/admin-withdrawal-request.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.9.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p><?php esc_html_e( 'A customer has submitted a new withdrawal request for the following order:', 'woocommerce' ); ?></p>

<table class="td" cellspacing="0" cellpadding="6" style="width:100%;margin-bottom:30px;" border="1">
	<tr>
		<th class="td" scope="row" style="text-align:left;"><?php esc_html_e( 'Order number', 'woocommerce' ); ?></th>
		<td class="td" style="text-align:left;"><?php echo esc_html( $order->get_order_number() ); ?></td>
	</tr>
	<tr>
		<th class="td" scope="row" style="text-align:left;"><?php esc_html_e( 'Withdrawal reference', 'woocommerce' ); ?></th>
		<td class="td" style="text-align:left;"><code><?php echo esc_html( $request_id ); ?></code></td>
	</tr>
	<tr>
		<th class="td" scope="row" style="text-align:left;"><?php esc_html_e( 'Date of request', 'woocommerce' ); ?></th>
		<td class="td" style="text-align:left;"><?php echo '' !== $request_date_created ? esc_html( $request_date_created ) : esc_html( wc_format_datetime( new WC_DateTime() ) ); ?></td>
	</tr>
	<tr>
		<th class="td" scope="row" style="text-align:left;"><?php esc_html_e( 'Customer', 'woocommerce' ); ?></th>
		<td class="td" style="text-align:left;">
			<?php echo esc_html( $order->get_formatted_billing_full_name() ); ?>
			(<a href="mailto:<?php echo esc_attr( $order->get_billing_email() ); ?>"><?php echo esc_html( $order->get_billing_email() ); ?></a>)
		</td>
	</tr>
</table>

<p>
	<a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $order->get_id() ) . '&action=edit' ) ); ?>" class="button">
		<?php esc_html_e( 'Review and process', 'woocommerce' ); ?>
	</a>
</p>

<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php
/**
 * @hooked WC_Emails::order_details() Shows the order details table.
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
