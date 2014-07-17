<?php
/**
 * Order Data
 *
 * Functions for displaying the order items meta box.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Meta Boxes
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Meta_Box_Order_Items {

	/**
	 * Output the metabox
	 */
	public static function output( $post ) {
		global $thepostid, $theorder;

		if ( ! is_object( $theorder ) ) {
			$theorder = get_order( $thepostid );
		}

		$order = $theorder;
		$data  = get_post_meta( $post->ID );
		?>
		<div class="woocommerce_order_items_wrapper">
			<table cellpadding="0" cellspacing="0" class="woocommerce_order_items">
				<thead>
					<tr>
						<th><input type="checkbox" class="check-column" /></th>
						<th class="item" colspan="2"><?php _e( 'Item', 'woocommerce' ); ?></th>

						<?php do_action( 'woocommerce_admin_order_item_headers' ); ?>

						<?php if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) : ?>
							<th class="tax_class"><?php _e( 'Tax&nbsp;Class', 'woocommerce' ); ?></th>
						<?php endif; ?>

						<th class="quantity"><?php _e( 'Qty', 'woocommerce' ); ?></th>

						<th class="line_cost"><?php _e( 'Total', 'woocommerce' ); ?></th>

						<?php if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) : ?>
							<th class="line_tax"><?php _e( 'Tax', 'woocommerce' ); ?></th>
						<?php endif; ?>

						<th class="wc-order-item-refund-quantity" style="display:none"><?php _e( 'Refund', 'woocommerce' ); ?></th>

						<th class="wc-order-edit-line-item" width="1%">&nbsp;</th>
					</tr>
				</thead>
				<tbody id="order_items_list">

					<?php
						// List order items
						$order_items = $order->get_items( apply_filters( 'woocommerce_admin_order_item_types', array( 'line_item', 'fee', 'shipping', 'coupon' ) ) );
						$shipping_methods = WC()->shipping() ? WC()->shipping->load_shipping_methods() : array();

						foreach ( $order_items as $item_id => $item ) {

							switch ( $item['type'] ) {
								case 'line_item' :
									$_product 	= $order->get_product_from_item( $item );
									$item_meta 	= $order->get_item_meta( $item_id );

									include( 'views/html-order-item.php' );
									break;
								case 'fee' :
									include( 'views/html-order-fee.php' );
									break;
								case 'shipping' :
									include( 'views/html-order-shipping.php' );
									break;
								case 'coupon' :
									include( 'views/html-order-coupon.php' );
									break;
							}

							do_action( 'woocommerce_order_item_' . $item['type'] . '_html', $item_id, $item );
						}

						if ( $refunds = $order->get_refunds() ) {
							foreach ( $refunds as $refund ) {
								include( 'views/html-order-refund.php' );
							}
						}
					?>
				</tbody>
			</table>
		</div>
		<div class="wc-order-data-row wc-order-totals wc-order-totals-items">
			<ul>
				<li>
					<span class="label"><?php _e( 'Shipping', 'woocommerce' ); ?>:</span>
					<span class="total"><?php echo wc_price( $order->get_total_shipping() ); ?></span>
				</li>
				<?php if ( 'yes' == get_option( 'woocommerce_calc_taxes' ) ) : ?>
					<li>
						<span class="label"><?php _e( 'Taxes', 'woocommerce' ); ?>:</span>
						<span class="total"><?php echo wc_price( $order->get_total_tax() ); ?></span>
					</li>
				<?php endif; ?>
				<li>
					<span class="label"><?php _e( 'Order Discount', 'woocommerce' ); ?>:</span>
					<span class="total"><?php echo wc_price( $order->get_total_discount() ); ?></span>
				</li>
				<li>
					<span class="label refunded-total"><?php _e( 'Refunded', 'woocommerce' ); ?>:</span>
					<span class="total refunded-total">-<?php echo wc_price( $order->get_total_refunded() ); ?></span>
				</li>
				<li>
					<span class="label"><?php _e( 'Order Total', 'woocommerce' ); ?>:</span>
					<span class="total"><?php echo wc_price( $order->get_total() ); ?></span>
				</li>
			</ul>
		</div>
		<div class="wc-order-data-row wc-order-bulk-actions">
			<p class="bulk_actions">
				<select>
					<option value=""><?php _e( 'Actions', 'woocommerce' ); ?></option>
					<optgroup label="<?php _e( 'Edit', 'woocommerce' ); ?>">
						<option value="delete"><?php _e( 'Delete line item(s)', 'woocommerce' ); ?></option>
					</optgroup>
					<optgroup label="<?php _e( 'Stock Actions', 'woocommerce' ); ?>">
						<option value="reduce_stock"><?php _e( 'Reduce line item stock', 'woocommerce' ); ?></option>
						<option value="increase_stock"><?php _e( 'Increase line item stock', 'woocommerce' ); ?></option>
					</optgroup>
				</select>

				<button type="button" class="button do_bulk_action wc-reload" title="<?php _e( 'Apply', 'woocommerce' ); ?>"><span><?php _e( 'Apply', 'woocommerce' ); ?></span></button>
			</p>
			<p class="add_items">
				<button type="button" class="button add_line_item"><?php _e( 'Add line item(s)', 'woocommerce' ); ?></button>
				<button type="button" class="button refund_items"><?php _e( 'Refund', 'woocommerce' ); ?></button>
			</p>
		</div>
		<div class="wc-order-data-row wc-order-add-item" style="display:none;">
			<button type="button" class="button add_order_item"><?php _e( 'Add product(s)', 'woocommerce' ); ?></button>
			<button type="button" class="button add_order_fee"><?php _e( 'Add fee', 'woocommerce' ); ?></button>
			<button type="button" class="button add_order_shipping"><?php _e( 'Add shipping cost', 'woocommerce' ); ?></button>
			<button type="button" class="button cancel-action"><?php _e( 'Done', 'woocommerce' ); ?></button>
		</div>
		<div class="wc-order-data-row wc-order-totals wc-order-refund-items" style="display:none;">
			<ul>
				<li>
					<label for="restock_refunded_items"><?php _e( 'Restock refunded items', 'woocommerce' ); ?>:</label>
					<span class="checkbox"><input type="checkbox" id="restock_refunded_items" name="restock_refunded_items" checked="checked" /></span>
				</li>
				<li>
					<label><?php _e( 'Amount already refunded', 'woocommerce' ); ?>:</label>
					<span class="total">-<?php echo wc_price( $order->get_total_refunded() ); ?></span>
				</li>
				<li>
					<label><?php _e( 'Total available to refund', 'woocommerce' ); ?>:</label>
					<span class="total"><?php echo wc_price( $order->get_total() - $order->get_total_refunded() ); ?></span>
				</li>
				<li>
					<label for="refund_amount"><?php _e( 'Refund amount', 'woocommerce' ); ?>:</label>
					<input type="text" class="text" id="refund_amount" name="refund_amount" class="wc_input_price" />
				</li>
				<li>
					<label for="refund_reason"><?php _e( 'Reason for refund (optional)', 'woocommerce' ); ?>:</label>
					<input type="text" class="text" id="refund_reason" name="refund_reason" />
				</li>
			</ul>
			<button type="button" class="button button-primary do-api-refund"><?php printf( _x( 'Refund %s via %s', 'Refund $amount', 'woocommerce' ), '<span class="wc-order-refund-amount">' . wc_price( 0 ) . '</span>', $order->payment_method_title ); ?></button>
			<button type="button" class="button button-primary do-manual-refund"><?php _e( 'Refund manually', 'woocommerce' ); ?></button>
			<button type="button" class="button cancel-action"><?php _e( 'Cancel', 'woocommerce' ); ?></button>
		</div>

		<script type="text/template" id="wc-modal-add-products">
			<div class="wc-backbone-modal">
				<div class="wc-backbone-modal-content">
					<section class="wc-backbone-modal-main" role="main">
						<header>
							<h1><?php echo __( 'Add products', 'woocommerce' ); ?></h1>
						</header>
						<article>
							<form>
								<select id="add_item_id" class="ajax_chosen_select_products_and_variations" multiple="multiple" data-placeholder="<?php _e( 'Search for a product&hellip;', 'woocommerce' ); ?>" style="width: 96%"></select>
							</form>
						</article>
						<footer>
							<div class="inner">
								<button id="btn-cancel" class="button button-large"><?php echo __( 'Cancel' , 'woocommerce' ); ?></button>
								<button id="btn-ok" class="button button-primary button-large"><?php echo __( 'Add' , 'woocommerce' ); ?></button>
							</div>
						</footer>
					</section>
				</div>
			</div>
			<div class="wc-backbone-modal-backdrop">&nbsp;</div>
		</script>
		<?php
	}

	/**
	 * Save meta box data
	 */
	public static function save( $post_id, $post ) {
		global $wpdb;

		// Order items + fees
		$subtotal = 0;
		$total    = 0;

		if ( isset( $_POST['order_item_id'] ) ) {

			$get_values = array( 'order_item_id', 'order_item_name', 'order_item_qty', 'line_subtotal', 'line_subtotal_tax', 'line_total', 'line_tax', 'order_item_tax_class' );

			foreach ( $get_values as $value ) {
				$$value = isset( $_POST[ $value ] ) ? $_POST[ $value ] : array();
			}

			foreach ( $order_item_id as $item_id ) {

				$item_id = absint( $item_id );

				if ( isset( $order_item_name[ $item_id ] ) ) {
					$wpdb->update(
						$wpdb->prefix . 'woocommerce_order_items',
						array( 'order_item_name' => wc_clean( $order_item_name[ $item_id ] ) ),
						array( 'order_item_id' => $item_id ),
						array( '%s' ),
						array( '%d' )
					);
				}

				if ( isset( $order_item_qty[ $item_id ] ) ) {
					wc_update_order_item_meta( $item_id, '_qty', wc_stock_amount( $order_item_qty[ $item_id ] ) );
				}

				if ( isset( $order_item_tax_class[ $item_id ] ) ) {
					wc_update_order_item_meta( $item_id, '_tax_class', wc_clean( $order_item_tax_class[ $item_id ] ) );
				}

				// Get values. Subtotals might not exist, in which case copy value from total field
				$line_total[ $item_id ]        = isset( $line_total[ $item_id ] ) ? $line_total[ $item_id ] : 0;
				$line_tax[ $item_id ]          = isset( $line_tax[ $item_id ] ) ? $line_tax[ $item_id ] : 0;
				$line_subtotal[ $item_id ]     = isset( $line_subtotal[ $item_id ] ) ? $line_subtotal[ $item_id ] : $line_total[ $item_id ];
				$line_subtotal_tax[ $item_id ] = isset( $line_subtotal_tax[ $item_id ] ) ? $line_subtotal_tax[ $item_id ] : $line_tax[ $item_id ];

				// Update values
				wc_update_order_item_meta( $item_id, '_line_subtotal', wc_format_decimal( $line_subtotal[ $item_id ] ) );
				wc_update_order_item_meta( $item_id, '_line_subtotal_tax', wc_format_decimal( $line_subtotal_tax[ $item_id ] ) );
				wc_update_order_item_meta( $item_id, '_line_total', wc_format_decimal( $line_total[ $item_id ] ) );
				wc_update_order_item_meta( $item_id, '_line_tax', wc_format_decimal( $line_tax[ $item_id ] ) );

				// Total up
				$subtotal += wc_format_decimal( $line_subtotal[ $item_id ] );
				$total    += wc_format_decimal( $line_total[ $item_id ] );

				// Clear meta cache
				wp_cache_delete( $item_id, 'order_item_meta' );
			}
		}

		// Save meta
		$meta_keys   = isset( $_POST['meta_key'] ) ? $_POST['meta_key'] : array();
		$meta_values = isset( $_POST['meta_value'] ) ? $_POST['meta_value'] : array();

		foreach ( $meta_keys as $id => $meta_key ) {
			$meta_value = ( empty( $meta_values[ $id ] ) && ! is_numeric( $meta_values[ $id ] ) ) ? '' : $meta_values[ $id ];
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_order_itemmeta',
				array(
					'meta_key'   => wp_unslash( $meta_key ),
					'meta_value' => wp_unslash( $meta_value )
				),
				array( 'meta_id' => $id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}

		// Shipping Rows
		$order_shipping = 0;

		if ( isset( $_POST['shipping_method_id'] ) ) {

			$get_values = array( 'shipping_method_id', 'shipping_method_title', 'shipping_method', 'shipping_cost' );

			foreach ( $get_values as $value ) {
				$$value = isset( $_POST[ $value ] ) ? $_POST[ $value ] : array();
			}

			foreach ( $shipping_method_id as $item_id => $value ) {

				if ( 'new' == $item_id ) {

					foreach ( $value as $new_key => $new_value ) {
						$method_id    = wc_clean( $shipping_method[ $item_id ][ $new_key ] );
						$method_title = wc_clean( $shipping_method_title[ $item_id ][ $new_key ] );
						$cost         = wc_format_decimal( $shipping_cost[ $item_id ][ $new_key ] );

						$new_id = wc_add_order_item( $post_id, array(
							'order_item_name' => $method_title,
							'order_item_type' => 'shipping'
						) );

						if ( $new_id ) {
							wc_add_order_item_meta( $new_id, 'method_id', $method_id );
							wc_add_order_item_meta( $new_id, 'cost', $cost );
						}

						$order_shipping += $cost;
					}

				} else {

					$item_id      = absint( $item_id );
					$method_id    = wc_clean( $shipping_method[ $item_id ] );
					$method_title = wc_clean( $shipping_method_title[ $item_id ] );
					$cost         = wc_format_decimal( $shipping_cost[ $item_id ] );

					$wpdb->update(
						$wpdb->prefix . 'woocommerce_order_items',
						array( 'order_item_name' => $method_title ),
						array( 'order_item_id' => $item_id ),
						array( '%s' ),
						array( '%d' )
					);

					wc_update_order_item_meta( $item_id, 'method_id', $method_id );
					wc_update_order_item_meta( $item_id, 'cost', $cost );

					$order_shipping += $cost;
				}
			}
		}

		update_post_meta( $post_id, '_order_shipping', $order_shipping );

		// Update cart discount from item totals
		update_post_meta( $post_id, '_cart_discount', $subtotal - $total );
	}
}
