<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * @var object $refund The refund object.
 */
$who_refunded = new WP_User( $refund->get_refunded_by() );
?>
<tr class="refund <?php echo ( ! empty( $class ) ) ? $class : ''; ?>" data-order_refund_id="<?php echo $refund->get_id(); ?>">
	<td class="thumb"><div></div></td>

	<td class="name">
		<?php
			/* translators: 1: refund id 2: date */
			printf( __( 'Refund #%1$s - %2$s', 'woocommerce' ), $refund->get_id(), wc_format_datetime( $refund->get_date_created(), get_option( 'date_format' ) . ', ' . get_option( 'time_format' ) ) );

			if ( $who_refunded->exists() ) {
				echo ' ' . esc_attr_x( 'by', 'Ex: Refund - $date >by< $username', 'woocommerce' ) . ' ' . '<abbr class="refund_by" title="' . sprintf( esc_attr__( 'ID: %d', 'woocommerce' ), absint( $who_refunded->ID ) ) . '">' . esc_attr( $who_refunded->display_name ) . '</abbr>' ;
			}
		?>
		<?php if ( $refund->get_reason() ) : ?>
			<p class="description"><?php echo esc_html( $refund->get_reason() ); ?></p>
		<?php endif; ?>
		<input type="hidden" class="order_refund_id" name="order_refund_id[]" value="<?php echo esc_attr( $refund->get_id() ); ?>" />
	</td>

	<?php do_action( 'woocommerce_admin_order_item_values', null, $refund, $refund->get_id() ); ?>

	<td class="item_cost" width="1%">&nbsp;</td>
	<td class="quantity" width="1%">&nbsp;</td>

	<td class="line_cost" width="1%">
		<div class="view">
			<?php echo wc_price( '-' . $refund->get_amount() ); ?>
		</div>
	</td>

	<?php if ( wc_tax_enabled() ) : $total_taxes = count( $order_taxes ); ?>
		<?php for ( $i = 0;  $i < $total_taxes; $i++ ) : ?>
			<td class="line_tax" width="1%"></td>
		<?php endfor; ?>
	<?php endif; ?>

	<td class="wc-order-edit-line-item">
		<div class="wc-order-edit-line-item-actions">
			<a class="delete_refund" href="#"></a>
		</div>
	</td>
</tr>
