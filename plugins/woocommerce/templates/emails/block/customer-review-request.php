<?php
/**
 * Customer review request email (initial block content)
 *
 * Rendered inside the block-editor email shell via the
 * `woocommerce_email_general_block_content` action. The block editor supplies
 * the outer email chrome (header, footer, styling); this template outputs only
 * the body: greeting, body copy, CTA button and order meta line.
 *
 * This template can be overridden by editing it in the WooCommerce email editor.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Block
 * @version 10.8.0
 */

defined( 'ABSPATH' ) || exit;

$first_name = $order instanceof WC_Order ? $order->get_billing_first_name() : '';
?>

<p>
<?php
if ( ! empty( $first_name ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $first_name ) );
} else {
	esc_html_e( 'Hi,', 'woocommerce' );
}
?>
</p>

<p><?php esc_html_e( 'We\'d love to know what you thought of the products you ordered. Your review helps other shoppers make better decisions and helps us improve.', 'woocommerce' ); ?></p>

<?php if ( ! empty( $review_order_url ) ) : ?>
<p style="margin: 16px 0;">
	<a href="<?php echo esc_url( $review_order_url ); ?>" style="display: inline-block; padding: 6px 12px; background-color: #3858e9; color: #ffffff; text-decoration: none; border-radius: 2px; font-size: 13px; line-height: 20px; font-weight: 600;">
		<?php esc_html_e( 'Leave a review', 'woocommerce' ); ?>
	</a>
</p>
<?php endif; ?>

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
