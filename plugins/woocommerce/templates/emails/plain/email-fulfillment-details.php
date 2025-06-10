<?php
/**
 * Email template for fulfillment details.
 *
 * @package WooCommerce\Templates\Emails
 */

defined( 'ABSPATH' ) || exit;

if ( null === $fulfillment->get_date_deleted() ) {
	$tracking_number   = $fulfillment->get_meta( '_tracking_number', true );
	$tracking_url      = $fulfillment->get_meta( '_tracking_url' );
	$shipment_provider = $fulfillment->get_meta( '_shipment_provider' );
	if ( ! $tracking_number && ! $tracking_url && ! $shipment_provider ) {
		echo esc_html__( 'No tracking information available for this fulfillment at the moment.', 'woocommerce' );
		return;
	} else {
		?>
<strong>Tracking Number:</strong> <?php echo esc_attr( $tracking_number ); ?><br />
<strong>Shipment Provider:</strong> <?php echo esc_html( $shipment_provider ); ?><br />
<a href="<?php echo esc_html( $tracking_url ); ?>" target="_blank">Track your shipment</a><br /><br /><?php
	}
	echo wp_kses(
		sprintf(
			/* translators: %s: Link to My Account > Orders page. */
			__( 'You can access to more details of your order by visiting <a href="%s" target="_blank">My Account > Orders</a> and select the order you wish to see the latest status of the delivery.', 'woocommerce' ),
			site_url( 'my-account/orders/' )
		),
		'strong, a'
	);
	echo "\n\n\n";
}

/**
 * Action hook to add custom content before fulfillment details in email.
 *
 * @param WC_Order $order Order object.
 * @param Fulfillment $fulfillment Fulfillment object.
 * @param bool     $sent_to_admin Whether it's sent to admin or customer.
 * @param bool     $plain_text Whether it's a plain text email.
 * @param WC_Email $email Email object.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_before_fulfillment_table', $order, $fulfillment, $sent_to_admin, $plain_text, $email );

echo wp_kses_post( __( 'Fulfillment summary', 'woocommerce' ) );
echo "\n\n==========\n\n";

if ( $sent_to_admin ) {
	$before = '<a class="link" href="' . esc_url( $order->get_edit_order_url() ) . '">';
	$after  = '</a>';
} else {
	$before = '';
	$after  = '';
}

/* translators: %s: Order ID. */
$order_number_string = __( 'Order #%s', 'woocommerce' );
echo wp_kses_post(
	$before . sprintf(
		$order_number_string . $after . ' (%s)',
		$order->get_order_number(),
		wc_format_datetime( $order->get_date_created() )
	)
);
echo "\n\n\n";
echo wc_get_email_fulfillment_items( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$order,
	$fulfillment,
	array(
		'show_sku'      => $sent_to_admin,
		'show_image'    => true,
		'image_size'    => array( 48, 48 ),
		'plain_text'    => $plain_text,
		'sent_to_admin' => $sent_to_admin,
	)
);

/**
 * Action hook to add custom content after fulfillment details in email.
 *
 * @param WC_Order $order Order object.
 * @param bool     $sent_to_admin Whether it's sent to admin or customer.
 * @param bool     $plain_text Whether it's a plain text email.
 * @param WC_Email $email Email object.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_after_fulfillment_table', $order, $fulfillment, $sent_to_admin, $plain_text, $email );
?>
