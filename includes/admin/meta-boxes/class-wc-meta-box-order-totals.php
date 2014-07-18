<?php
/**
 * Order Totals
 *
 * Functions for displaying the order totals meta box.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Meta Boxes
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Meta_Box_Order_Totals {

	/**
	 * Output the metabox
	 */
	public static function output() {
		global $theorder, $wpdb, $post;

		if ( ! is_object( $theorder ) )
			$theorder = get_order( $post->ID );

		$order = $theorder;

		$data = get_post_meta( $post->ID );
		?>

		<?php if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) : ?>

		<div class="totals_group tax_rows_group">
			<h4><span class="tax_total_display inline_total"></span><?php _e( 'Taxes', 'woocommerce' ); ?></h4>
			<div id="tax_rows" class="total_rows">
				<?php
					global $wpdb;

					$rates = $wpdb->get_results( "SELECT tax_rate_id, tax_rate_country, tax_rate_state, tax_rate_name, tax_rate_priority FROM {$wpdb->prefix}woocommerce_tax_rates ORDER BY tax_rate_name" );

					$tax_codes = array();

					foreach( $rates as $rate ) {
						$code = array();

						$code[] = $rate->tax_rate_country;
						$code[] = $rate->tax_rate_state;
						$code[] = $rate->tax_rate_name ? sanitize_title( $rate->tax_rate_name ) : 'TAX';
						$code[] = absint( $rate->tax_rate_priority );

						$tax_codes[ $rate->tax_rate_id ] = strtoupper( implode( '-', array_filter( $code ) ) );
					}

					foreach ( $order->get_taxes() as $item_id => $item ) {
						include( 'views/html-order-tax.php' );
					}
				?>
			</div>
			<h4><a href="#" class="add_total_row" data-row="<?php
				$item_id = '';
				$item    = '';
				ob_start();
				include( 'views/html-order-tax.php' );
				echo esc_attr( ob_get_clean() );
				?>"><?php _e( '+ Add tax row', 'woocommerce' ); ?> <span class="tips" data-tip="<?php _e( 'These rows contain taxes for this order. This allows you to display multiple or compound taxes rather than a single total.', 'woocommerce' ); ?>">[?]</span></a></a></h4>
			<div class="clear"></div>
		</div>

		<?php endif; ?>

		<p class="buttons">
			<?php if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) : ?>
				<button type="button" class="button calc_line_taxes"><?php _e( 'Calculate Tax', 'woocommerce' ); ?></button>
			<?php endif; ?>
			<button type="button" class="button calc_totals"><?php _e( 'Calculate Total', 'woocommerce' ); ?></button>
		</p>
		<?php
	}

	/**
	 * Save meta box data
	 */
	public static function save( $post_id, $post ) {
		global $wpdb;

		// Save tax rows
		$total_tax          = 0;
		$total_shipping_tax = 0;

		if ( isset( $_POST['order_taxes_id'] ) ) {

			$get_values = array( 'order_taxes_id', 'order_taxes_rate_id', 'order_taxes_amount', 'order_taxes_shipping_amount' );

			foreach( $get_values as $value )
				$$value = isset( $_POST[ $value ] ) ? $_POST[ $value ] : array();

			foreach( $order_taxes_id as $item_id => $value ) {

				if ( $item_id == 'new' ) {

					foreach ( $value as $new_key => $new_value ) {
						$rate_id  = absint( $order_taxes_rate_id[ $item_id ][ $new_key ] );

						if ( $rate_id ) {
							$rate     = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %s", $rate_id ) );
							$label    = $rate->tax_rate_name ? $rate->tax_rate_name : WC()->countries->tax_or_vat();
							$compound = $rate->tax_rate_compound ? 1 : 0;

							$code = array();

							$code[] = $rate->tax_rate_country;
							$code[] = $rate->tax_rate_state;
							$code[] = $rate->tax_rate_name ? $rate->tax_rate_name : 'TAX';
							$code[] = absint( $rate->tax_rate_priority );
							$code   = strtoupper( implode( '-', array_filter( $code ) ) );
						} else {
							$code  = '';
							$label = WC()->countries->tax_or_vat();
						}

						// Add line item
					   	$new_id = wc_add_order_item( $post_id, array(
								'order_item_name' => wc_clean( $code ),
								'order_item_type' => 'tax'
					 	) );

					 	// Add line item meta
					 	if ( $new_id ) {
							wc_update_order_item_meta( $new_id, 'rate_id', $rate_id );
							wc_update_order_item_meta( $new_id, 'label', $label );
							wc_update_order_item_meta( $new_id, 'compound', $compound );

							if ( isset( $order_taxes_amount[ $item_id ][ $new_key ] ) ) {
						 		wc_update_order_item_meta( $new_id, 'tax_amount', wc_format_decimal( $order_taxes_amount[ $item_id ][ $new_key ] ) );

						 		$total_tax          += wc_format_decimal( $order_taxes_amount[ $item_id ][ $new_key ] );
						 	}

						 	if ( isset( $order_taxes_shipping_amount[ $item_id ][ $new_key ] ) ) {
						 		wc_update_order_item_meta( $new_id, 'shipping_tax_amount', wc_format_decimal( $order_taxes_shipping_amount[ $item_id ][ $new_key ] ) );

						 		$total_shipping_tax += wc_format_decimal( $order_taxes_shipping_amount[ $item_id ][ $new_key ] );
						 	}
					 	}
					}

				} else {

					$item_id  = absint( $item_id );
					$rate_id  = absint( $order_taxes_rate_id[ $item_id ] );

					if ( $rate_id ) {
						$rate     = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %s", $rate_id ) );
						$label    = $rate->tax_rate_name ? $rate->tax_rate_name : WC()->countries->tax_or_vat();
						$compound = $rate->tax_rate_compound ? 1 : 0;

						$code = array();

						$code[] = $rate->tax_rate_country;
						$code[] = $rate->tax_rate_state;
						$code[] = $rate->tax_rate_name ? $rate->tax_rate_name : 'TAX';
						$code[] = absint( $rate->tax_rate_priority );
						$code   = strtoupper( implode( '-', array_filter( $code ) ) );
					} else {
						$code  = '';
						$label = WC()->countries->tax_or_vat();
					}

					$wpdb->update(
						$wpdb->prefix . "woocommerce_order_items",
						array( 'order_item_name' => wc_clean( $code ) ),
						array( 'order_item_id' => $item_id ),
						array( '%s' ),
						array( '%d' )
					);

					wc_update_order_item_meta( $item_id, 'rate_id', $rate_id );
					wc_update_order_item_meta( $item_id, 'label', $label );
					wc_update_order_item_meta( $item_id, 'compound', $compound );

					if ( isset( $order_taxes_amount[ $item_id ] ) ) {
				 		wc_update_order_item_meta( $item_id, 'tax_amount', wc_format_decimal( $order_taxes_amount[ $item_id ] ) );

				 		$total_tax += wc_format_decimal( $order_taxes_amount[ $item_id ] );
				 	}

				 	if ( isset( $order_taxes_shipping_amount[ $item_id ] ) ) {
				 		wc_update_order_item_meta( $item_id, 'shipping_tax_amount', wc_format_decimal( $order_taxes_shipping_amount[ $item_id ] ) );

				 		$total_shipping_tax += wc_format_decimal( $order_taxes_shipping_amount[ $item_id ] );
				 	}
				}
			}
		}

		// Update totals
		update_post_meta( $post_id, '_order_tax', wc_format_decimal( $total_tax ) );
		update_post_meta( $post_id, '_order_shipping_tax', wc_format_decimal( $total_shipping_tax ) );

		// Delete rows
		if ( isset( $_POST['delete_order_item_id'] ) ) {
			$delete_ids = $_POST['delete_order_item_id'];

			foreach ( $delete_ids as $id )
				wc_delete_order_item( absint( $id ) );
		}
	}
}
