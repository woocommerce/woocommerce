<?php
/**
 * Order fulfillment details table shown in emails.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-fulfillment-details.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.1.0
 */

defined( 'ABSPATH' ) || exit;

$text_align = is_rtl() ? 'right' : 'left';

$heading_class          = 'email-order-detail-heading';
$order_table_class      = 'email-order-details';
$order_total_text_align = 'right';

if ( null === $fulfillment->get_date_deleted() ) {
	$tracking_number   = $fulfillment->get_meta( '_tracking_number', true );
	$tracking_url      = $fulfillment->get_meta( '_tracking_url' );
	$shipment_provider = $fulfillment->get_meta( '_shipment_provider' );
	if ( ! $tracking_number && ! $tracking_url && ! $shipment_provider ) {
		echo '<p>' . esc_html__( 'No tracking information available for this fulfillment at the moment.', 'woocommerce' ) . '</p>';
	} else {
		echo '<p><strong>' . esc_html__( 'Tracking Number', 'woocommerce' ) . ':</strong> ' . esc_attr( $tracking_number ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Shipment Provider', 'woocommerce' ) . ':</strong> ' . esc_html( $shipment_provider ) . '</p>';
		echo '<p><a href="' . esc_url( $tracking_url ) . '" target="_blank">' . esc_attr__( 'Track your shipment', 'woocommerce' ) . '</a></p>';
	}
	echo '<br />';
	echo '<p>';
	echo wp_kses_post(
		sprintf(
			/* translators: %s: Link to My Account > Orders page. */
			__( 'You can access to more details of your order by visiting <a href="%s" target="_blank">My Account > Orders</a> and select the order you wish to see the latest status of the delivery.', 'woocommerce' ),
			site_url( 'my-account/orders/' )
		)
	);
	echo '</p>';
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
?>

<h2 class="<?php echo esc_attr( $heading_class ); ?>">
	<?php
	echo wp_kses_post( __( 'Fulfillment summary', 'woocommerce' ) );
	if ( $sent_to_admin ) {
		$before = '<a class="link" href="' . esc_url( $order->get_edit_order_url() ) . '">';
		$after  = '</a>';
	} else {
		$before = '';
		$after  = '';
	}
	echo '<br><span>';
	/* translators: %s: Order ID. */
	$order_number_string = __( 'Order #%s', 'woocommerce' );
	echo wp_kses_post( $before . sprintf( $order_number_string . $after . ' (<time datetime="%s">%s</time>)', $order->get_order_number(), $order->get_date_created()->format( 'c' ), wc_format_datetime( $order->get_date_created() ) ) );
	echo '</span>';
	?>
</h2>

<div style="margin-bottom: 24px;">
	<table class="td font-family <?php echo esc_attr( $order_table_class ); ?>" cellspacing="0" cellpadding="6" style="width: 100%;" border="1">
		<tbody>
			<?php
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
			?>
		</tbody>
	</table>
</div>

<?php

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
