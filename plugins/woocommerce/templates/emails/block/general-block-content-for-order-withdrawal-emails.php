<?php
/**
 * General block content for order withdrawal emails
 *
 * Note: This template is only used in the order withdrawal emails.
 *
 * Used to render information for the email editor WooCommerce content block (BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Block
 * @version 11.5.0
 *
 * @var array<string,string> $detail_rows               Withdrawal request detail rows.
 * @var \WC_Order|null       $matched_order             Matched order, if found. Admin email only.
 * @var bool                 $outside_withdrawal_window Whether the matched order is outside the withdrawal window.
 * @var string               $withdrawal_window_warning Withdrawal window warning message.
 * @var bool                 $sent_to_admin             Whether sent to admin.
 */

defined( 'ABSPATH' ) || exit;

if ( $sent_to_admin ) :
	if ( $matched_order instanceof WC_Order ) : ?>
		<p><?php esc_html_e( 'WooCommerce matched this request to an order and added an order note.', 'woocommerce' ); ?></p>
	<?php else : ?>
		<p><?php esc_html_e( 'WooCommerce could not match this request to an order automatically, so no order note was added.', 'woocommerce' ); ?></p>
		<?php
	endif;
endif;
?>

<ul>
	<?php foreach ( $detail_rows as $label => $value ) : ?>
		<li><strong><?php echo esc_html( $label ); ?>:</strong> <?php echo nl2br( esc_html( $value ) ); ?></li>
	<?php endforeach; ?>
</ul>

<?php if ( $sent_to_admin && $matched_order instanceof WC_Order ) : ?>
	<?php if ( $outside_withdrawal_window ) : ?>
		<p><?php echo esc_html( $withdrawal_window_warning ); ?></p>
	<?php endif; ?>
	<p>
		<?php
		printf(
			/* translators: %d: order ID. */
			esc_html__( 'Matched order ID: %d', 'woocommerce' ),
			absint( $matched_order->get_id() )
		);
		?>
	</p>
	<?php if ( '' !== $matched_order->get_edit_order_url() ) : ?>
		<p><a href="<?php echo esc_url( $matched_order->get_edit_order_url() ); ?>"><?php esc_html_e( 'View matched order', 'woocommerce' ); ?></a></p>
	<?php endif; ?>
<?php endif; ?>
