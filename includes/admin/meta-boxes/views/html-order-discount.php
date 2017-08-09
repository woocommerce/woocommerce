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
<tr class="fee <?php echo ( ! empty( $class ) ) ? $class : ''; ?>" data-order_item_id="<?php echo esc_attr( $item_id ); ?>">
	<td class="thumb"><div></div></td>

	<td class="name">
		<div class="view">
			<?php
				/* translators: %1$s: shipping method, %2$s: Discount amount. */
				echo wp_kses_post( sprintf( __( '%1$s &mdash; %2$s off', 'woocomerce' ), $item->get_name() ? $item->get_name() : __( 'Discount', 'woocommerce' ), 'fixed' === $item->get_discount_type() ? wc_price( $item->get_amount() ) : $item->get_amount() . '% off' ) );
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
		<?php echo wc_price( $item->get_discount_total() ); ?>
	</td>
	<?php
		// taxes?
	?>
	<td class="wc-order-edit-line-item">
		<?php if ( $order->is_editable() ) : ?>
			<div class="wc-order-edit-line-item-actions">
				<a class="edit-order-item" href="#"></a><a class="delete-order-item" href="#"></a>
			</div>
		<?php endif; ?>
	</td>
</tr>
