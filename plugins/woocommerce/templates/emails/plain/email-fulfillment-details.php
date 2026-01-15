<?php
/**
 * Order fulfillment details table shown in emails as plain text.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/email-fulfillment-details.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.1.0
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
		echo esc_html__( 'Tracking Number', 'woocommerce' ) . ': ' . esc_attr( $tracking_number ) . "\n";
		echo esc_html__( 'Shipment Provider', 'woocommerce' ) . ': ' . esc_html( $shipment_provider ) . "\n";
		echo esc_html__( 'Tracking URL', 'woocommerce' ) . ': ' . esc_html( $tracking_url ) . "\n\n";
	}

	echo esc_html__( 'You can access to more details of your order by visiting My Account > Orders and select the order you wish to see the latest status of the delivery.', 'woocommerce' );
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
	$before = '';
	$after  = '(' . esc_url( $order->get_edit_order_url() ) . ')';
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
