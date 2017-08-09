<?php
/**
 * Shows an order item discount.
 *
 * @since 3.2.0
 * @var object $item The item being displayed
 * @var int $item_id The id of the item being displayed
 * @todo This needs to show discount amount (negative) and negative taxes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<tr class="discount <?php echo ( ! empty( $class ) ) ? $class : ''; ?>" data-order_item_id="<?php echo esc_attr( $item_id ); ?>">
	<td class="thumb"><div></div></td>

	<td class="name">
		<div class="view">
			<?php
				/* translators: %1$s: shipping method, %2$s: Discount amount. */
				echo wp_kses_post( sprintf( __( '%1$s &mdash; %2$s', 'woocomerce' ), $item->get_name() ? $item->get_name() : __( 'Discount', 'woocommerce' ), 'fixed' === $item->get_discount_type() ? wc_price( $item->get_amount() ) : $item->get_amount() . '%' ) );
			?>
		</div>
		<div class="edit" style="display: none;">
			<input type="text" placeholder="<?php esc_attr_e( 'Discount name', 'woocommerce' ); ?>" name="order_item_name[<?php echo absint( $item_id ); ?>]" value="<?php echo ( $item->get_name() ) ? esc_attr( $item->get_name() ) : ''; ?>" />
			<input type="hidden" class="order_item_id" name="order_item_id[]" value="<?php echo esc_attr( $item_id ); ?>" />
		</div>
	</td>
	<td class="item_cost" width="1%">&nbsp;</td>
	<td class="quantity" width="1%">&nbsp;</td>
	<td class="line_cost" width="1%">
		<?php echo wc_price( $item->get_total() ); ?>
	</td>
	<?php
		if ( ( $tax_data = $item->get_taxes() ) && wc_tax_enabled() ) {
			foreach ( $order_taxes as $tax_item ) {
				$tax_item_id    = $tax_item->get_rate_id();
				$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
				?>
					<td class="line_tax" width="1%">
						<div class="view">
							<?php
								echo ( '' !== $tax_item_total ) ? wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $order->get_currency() ) ) : '&ndash;';

								if ( $refunded = $order->get_tax_refunded_for_item( $item_id, $tax_item_id, 'fee' ) ) {
									echo '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $order->get_currency() ) ) . '</small>';
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
			}
		}
	?>
	<td class="wc-order-edit-line-item">
		<?php if ( $order->is_editable() ) : ?>
			<div class="wc-order-edit-line-item-actions">
				<a class="edit-order-item" href="#"></a><a class="delete-order-item" href="#"></a>
			</div>
		<?php endif; ?>
	</td>
</tr>
