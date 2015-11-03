<?php
/**
 * Shows an order item fee
 *
 * @var object $item The item being displayed
 * @var int $item_id The id of the item being displayed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<tr class="fee <?php echo ( ! empty( $class ) ) ? $class : ''; ?>" data-order_item_id="<?php echo $item_id; ?>">
	<td class="check-column"><input type="checkbox" /></td>

	<td class="thumb"><div></div></td>

	<td class="name">
		<div class="view">
			<?php echo ! empty( $item['name'] ) ? esc_html( $item['name'] ) : __( 'Fee', 'woocommerce' ); ?>
		</div>
		<div class="edit" style="display: none;">
			<input type="text" placeholder="<?php esc_attr_e( 'Fee Name', 'woocommerce' ); ?>" name="order_item_name[<?php echo absint( $item_id ); ?>]" value="<?php echo ( isset( $item['name'] ) ) ? esc_attr( $item['name'] ) : ''; ?>" />
			<input type="hidden" class="order_item_id" name="order_item_id[]" value="<?php echo esc_attr( $item_id ); ?>" />
			<input type="hidden" name="order_item_tax_class[<?php echo absint( $item_id ); ?>]" value="<?php echo isset( $item['tax_class'] ) ? esc_attr( $item['tax_class'] ) : ''; ?>" />
		</div>
	</td>

	<?php do_action( 'woocommerce_admin_order_item_values', null, $item, absint( $item_id ) ); ?>

	<td class="item_cost" width="1%">&nbsp;</td>
	<td class="quantity" width="1%">&nbsp;</td>

	<td class="line_cost" width="1%">
		<div class="view">
			<?php
				echo ( isset( $item['line_total'] ) ) ? wc_price( wc_round_tax_total( $item['line_total'] ) ) : '';

				if ( $refunded = $order->get_total_refunded_for_item( $item_id, 'fee' ) ) {
					echo '<small class="refunded">-' . wc_price( $refunded ) . '</small>';
				}
			?>
		</div>
		<div class="edit" style="display: none;">
			<input type="text" name="line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo ( isset( $item['line_total'] ) ) ? esc_attr( wc_format_localized_price( $item['line_total'] ) ) : ''; ?>" class="line_total wc_input_price" />
		</div>
		<div class="refund" style="display: none;">
			<input type="text" name="refund_line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" class="refund_line_total wc_input_price" />
		</div>
	</td>

	<?php
		if ( empty( $legacy_order ) && wc_tax_enabled() ) :
			$line_tax_data = isset( $item['line_tax_data'] ) ? $item['line_tax_data'] : '';
			$tax_data      = maybe_unserialize( $line_tax_data );

			foreach ( $order_taxes as $tax_item ) :
				$tax_item_id       = $tax_item['rate_id'];
				$tax_item_total    = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
				?>
					<td class="line_tax" width="1%">
						<div class="view">
							<?php
								echo ( '' != $tax_item_total ) ? wc_price( wc_round_tax_total( $tax_item_total ) ) : '&ndash;';

								if ( $refunded = $order->get_tax_refunded_for_item( $item_id, $tax_item_id, 'fee' ) ) {
									echo '<small class="refunded">-' . wc_price( $refunded ) . '</small>';
								}
							?>
						</div>
						<div class="edit" style="display: none;">
							<input type="text" name="line_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo ( isset( $tax_item_total ) ) ? esc_attr( wc_format_localized_price( $tax_item_total ) ) : ''; ?>" class="line_tax wc_input_price" />
						</div>
						<div class="refund" style="display: none;">
							<input type="text" name="refund_line_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" class="refund_line_tax wc_input_price" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>" />
						</div>
					</td>

				<?php
			endforeach;
		endif;
	?>

	<td class="wc-order-edit-line-item">
		<?php if ( $order->is_editable() ) : ?>
			<div class="wc-order-edit-line-item-actions">
				<a class="edit-order-item" href="#"></a><a class="delete-order-item" href="#"></a>
			</div>
		<?php endif; ?>
	</td>
</tr>
