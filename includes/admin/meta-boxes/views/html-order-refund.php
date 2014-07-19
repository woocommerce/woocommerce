<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<tr class="refund <?php echo ( ! empty( $class ) ) ? $class : ''; ?>" data-order_refund_id="<?php echo $refund->id; ?>">
	<td class="check-column"><input type="checkbox" /></td>

	<td class="thumb"></td>

	<td class="name">
		<?php _e( 'Refund', 'woocommerce' ); ?>
		<table class="display_meta" cellspacing="0">
			<tbody>
				<tr>
					<th><?php _e( 'Date', 'woocommerce' ); ?>:</th>
					<td>
						<p><?php echo date_i18n( get_option( 'date_format' ) . ', ' . get_option( 'time_format' ), strtotime( $refund->post_date ) ); ?></p>
					</td>
				</tr>
				<?php if ( ! empty( $refund->get_refund_reason() ) ) : ?>
					<tr>
						<th><?php _e( 'Reason', 'woocommerce' ); ?>:</th>
						<td>
							<p><?php echo esc_html( $refund->get_refund_reason() ); ?></p>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<input type="hidden" class="order_refund_id" name="order_refund_id[]" value="<?php echo esc_attr( $refund->id ); ?>" />
	</td>

	<td class="quantity" width="1%">1</td>

	<td class="line_cost" width="1%">
		<div class="view">
			<?php echo wc_price( '-' . $refund->get_refund_amount() ); ?>
		</div>
	</td>

	<?php if ( 'yes' == get_option( 'woocommerce_calc_taxes' ) ) : ?>

	<td class="line_tax" width="1%"></td>

	<?php endif; ?>

	<td class="wc-order-item-refund-quantity" width="1%" style="display:none"></td>

	<td class="wc-order-edit-line-item">
		<div class="wc-order-edit-line-item-actions">
			<a class="delete_refund" href="#"></a>
		</div>
	</td>
</tr>
