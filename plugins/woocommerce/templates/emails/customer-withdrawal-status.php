<?php
/**
 * Customer withdrawal status email
 *
 * Sent to the customer when their withdrawal request has been approved or rejected.
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

$status_label = 'approved' === $new_status
	? __( 'approved', 'woocommerce' )
	: __( 'rejected', 'woocommerce' );

/**
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
<p>
<?php
/* translators: 1: order number, 2: status (approved/rejected) */
printf( esc_html__( 'Your withdrawal request for order %1$s has been %2$s by the store.', 'woocommerce' ), esc_html( $order->get_order_number() ), esc_html( $status_label ) );
?>
</p>

<?php if ( ! empty( $admin_notes ) ) : ?>
	<p><strong><?php esc_html_e( 'Notes from the store', 'woocommerce' ); ?>:</strong></p>
	<blockquote style="margin:0 0 20px;padding:10px 15px;border-left:3px solid #ddd;color:#555;">
		<?php echo esc_html( $admin_notes ); ?>
	</blockquote>
<?php endif; ?>

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
		<th class="td" scope="row" style="text-align:left;"><?php esc_html_e( 'Status', 'woocommerce' ); ?></th>
		<td class="td" style="text-align:left;">
			<strong><?php echo esc_html( $status_label ); ?></strong>
		</td>
	</tr>
</table>

<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php
/**
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
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

/**
 * Show user-defined additional content.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
