<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<tr class="coupon <?php echo ( ! empty( $class ) ) ? $class : ''; ?>" data-order_item_id="<?php echo $item_id; ?>">
	<td class="check-column"></td>

	<td class="thumb"></td>

	<td class="name">
		<div class="view">
			<?php
				$coupon_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' LIMIT 1;", $item['name'] ) );

				$coupon_url = $coupon_id ? add_query_arg( array( 'post' => $coupon_id, 'action' => 'edit' ), admin_url( 'post.php' ) ) : add_query_arg( array( 's' => $item['name'], 'post_status' => 'all', 'post_type' => 'shop_coupon' ), admin_url( 'edit.php' ) );

				echo '<a href="' . esc_url( $coupon_url ) . '"><span>' . esc_html( $item['name'] ). '</span></a>';
			?>
		</div>
	</td>

	<td class="quantity" width="1%">1</td>

	<td class="line_cost" width="1%">
		<div class="view">
			<?php echo wc_price( $item['discount_amount'] ); ?>
		</div>
	</td>

	<?php if ( 'yes' == get_option( 'woocommerce_calc_taxes' ) ) : for ( $i = 0;  $i < count( $order_taxes ); $i++ ) : ?>

		<td class="line_tax" width="1%"></td>

	<?php endfor; endif; ?>

	<td class="wc-order-item-refund-quantity" width="1%" style="display: none;"></td>

	<td class="wc-order-edit-line-item"></td>
</tr>
