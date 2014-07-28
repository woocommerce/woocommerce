<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<tr class="refund <?php echo ( ! empty( $class ) ) ? $class : ''; ?>" data-order_refund_id="<?php echo $refund->id; ?>">
	<td class="check-column"></td>

	<td class="thumb"><div></div></td>

	<td class="name">
		<?php _e( 'Refund', 'woocommerce' ); ?> - <?php echo date_i18n( get_option( 'date_format' ) . ', ' . get_option( 'time_format' ), strtotime( $refund->post_date ) ); ?>
		<?php if ( $refund->get_refund_reason() ) : ?>
			<p class="description"><?php echo esc_html( $refund->get_refund_reason() ); ?></p>
		<?php endif; ?>
		<input type="hidden" class="order_refund_id" name="order_refund_id[]" value="<?php echo esc_attr( $refund->id ); ?>" />
	</td>

	<td class="quantity" width="1%">&nbsp;</td>

	<td class="line_cost" width="1%">
		<div class="view">
			<?php echo wc_price( '-' . $refund->get_refund_amount() ); ?>
		</div>
	</td>

	<?php if ( ! $legacy_order && 'yes' == get_option( 'woocommerce_calc_taxes' ) ) : for ( $i = 0;  $i < count( $order_taxes ); $i++ ) : ?>

		<td class="line_tax" width="1%"></td>

	<?php endfor; endif; ?>

	<td class="wc-order-edit-line-item">
		<div class="wc-order-edit-line-item-actions">
			<a class="delete_refund" href="#"></a>
		</div>
	</td>
</tr>
