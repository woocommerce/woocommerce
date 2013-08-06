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
		global $woocommerce, $theorder, $wpdb, $post;

		if ( ! is_object( $theorder ) )
			$theorder = new WC_Order( $post->ID );

		$order = $theorder;

		$data = get_post_meta( $post->ID );
		?>
		<div class="totals_group">
			<h4><span class="discount_total_display inline_total"></span><?php _e( 'Discounts', 'woocommerce' ); ?></h4>
			<ul class="totals">

				<li class="left">
					<label><?php _e( 'Cart Discount:', 'woocommerce' ); ?>&nbsp;<a class="tips" data-tip="<?php _e( 'Discounts before tax - calculated by comparing subtotals to totals.', 'woocommerce' ); ?>" href="#">[?]</a></label>
					<input type="number" step="any" min="0" id="_cart_discount" name="_cart_discount" placeholder="0.00" value="<?php
						if ( isset( $data['_cart_discount'][0] ) )
							echo esc_attr( $data['_cart_discount'][0] );
					?>" class="calculated" />
				</li>

				<li class="right">
					<label><?php _e( 'Order Discount:', 'woocommerce' ); ?>&nbsp;<a class="tips" data-tip="<?php _e( 'Discounts after tax - user defined.', 'woocommerce' ); ?>" href="#">[?]</a></label>
					<input type="number" step="any" min="0" id="_order_discount" name="_order_discount" placeholder="0.00" value="<?php
						if ( isset( $data['_order_discount'][0] ) )
							echo esc_attr( $data['_order_discount'][0] );
					?>" />
				</li>

			</ul>

			<ul class="wc_coupon_list">

			<?php
				$coupons = $order->get_items( array( 'coupon' ) );

				foreach ( $coupons as $item_id => $item ) {

					$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' LIMIT 1;", $item['name'] ) );

					$link = $post_id ? admin_url( 'post.php?post=' . $post_id . '&action=edit' ) : admin_url( 'edit.php?s=' . esc_url( $item['name'] ) . '&post_status=all&post_type=shop_coupon' );

					echo '<li class="tips code" data-tip="' . esc_attr( woocommerce_price( $item['discount_amount'] ) ) . '"><a href="' . $link . '"><span>' . esc_html( $item['name'] ). '</span></a></li>';

				}
			?>

			</ul>

		</div>
		<div class="totals_group">
			<h4><?php _e( 'Shipping', 'woocommerce' ); ?></h4>
			<ul class="totals">

				<li class="wide">
					<label><?php _e( 'Label:', 'woocommerce' ); ?></label>
					<input type="text" id="_shipping_method_title" name="_shipping_method_title" placeholder="<?php _e( 'The shipping title the customer sees', 'woocommerce' ); ?>" value="<?php
						if ( isset( $data['_shipping_method_title'][0] ) )
							echo esc_attr( $data['_shipping_method_title'][0] );
					?>" class="first" />
				</li>

				<li class="left">
					<label><?php _e( 'Cost:', 'woocommerce' ); ?></label>
					<input type="number" step="any" min="0" id="_order_shipping" name="_order_shipping" placeholder="0.00 <?php _e( '(ex. tax)', 'woocommerce' ); ?>" value="<?php
						if ( isset( $data['_order_shipping'][0] ) )
							echo esc_attr( $data['_order_shipping'][0] );
					?>" class="first" />
				</li>

				<li class="right">
					<label><?php _e( 'Method:', 'woocommerce' ); ?></label>
					<select name="_shipping_method" id="_shipping_method" class="first">
						<option value=""><?php _e( 'N/A', 'woocommerce' ); ?></option>
						<?php
							$chosen_method 	= ! empty( $data['_shipping_method'][0] ) ? $data['_shipping_method'][0] : '';
							$found_method 	= false;

							if ( $woocommerce->shipping() ) {
								foreach ( $woocommerce->shipping->load_shipping_methods() as $method ) {

									if ( strpos( $chosen_method, $method->id ) === 0 )
										$value = $chosen_method;
									else
										$value = $method->id;

									echo '<option value="' . esc_attr( $value ) . '" ' . selected( $chosen_method == $value, true, false ) . '>' . esc_html( $method->get_title() ) . '</option>';
									if ( $chosen_method == $value )
										$found_method = true;
								}
							}

							if ( ! $found_method && ! empty( $chosen_method ) ) {
								echo '<option value="' . esc_attr( $chosen_method ) . '" selected="selected">' . __( 'Other', 'woocommerce' ) . '</option>';
							} else {
								echo '<option value="other">' . __( 'Other', 'woocommerce' ) . '</option>';
							}
						?>
					</select>
				</li>

			</ul>
			<?php do_action( 'woocommerce_admin_order_totals_after_shipping', $post->ID ) ?>
			<div class="clear"></div>
		</div>

		<?php if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) : ?>

		<div class="totals_group tax_rows_group">
			<h4><?php _e( 'Tax Rows', 'woocommerce' ); ?></h4>
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
			<h4><a href="#" class="add_tax_row"><?php _e( '+ Add tax row', 'woocommerce' ); ?> <span class="tips" data-tip="<?php _e( 'These rows contain taxes for this order. This allows you to display multiple or compound taxes rather than a single total.', 'woocommerce' ); ?>">[?]</span></a></a></h4>
			<div class="clear"></div>
		</div>
		<div class="totals_group">
			<h4><span class="tax_total_display inline_total"></span><?php _e( 'Tax Totals', 'woocommerce' ); ?></h4>
			<ul class="totals">

				<li class="left">
					<label><?php _e( 'Sales Tax:', 'woocommerce' ); ?>&nbsp;<a class="tips" data-tip="<?php _e( 'Total tax for line items + fees.', 'woocommerce' ); ?>" href="#">[?]</a></label>
					<input type="number" step="any" min="0" id="_order_tax" name="_order_tax" placeholder="0.00" value="<?php
						if ( isset( $data['_order_tax'][0] ) )
							echo esc_attr( $data['_order_tax'][0] );
					?>" class="calculated" />
				</li>

				<li class="right">
					<label><?php _e( 'Shipping Tax:', 'woocommerce' ); ?></label>
					<input type="number" step="any" min="0" id="_order_shipping_tax" name="_order_shipping_tax" placeholder="0.00" value="<?php
						if ( isset( $data['_order_shipping_tax'][0] ) )
							echo esc_attr( $data['_order_shipping_tax'][0] );
					?>" />
				</li>

			</ul>
			<div class="clear"></div>
		</div>

		<?php endif; ?>

		<div class="totals_group">
			<h4><?php _e( 'Order Totals', 'woocommerce' ); ?></h4>
			<ul class="totals">

				<li class="left">
					<label><?php _e( 'Order Total:', 'woocommerce' ); ?></label>
					<input type="number" step="any" min="0" id="_order_total" name="_order_total" placeholder="0.00" value="<?php
						if ( isset( $data['_order_total'][0] ) )
							echo esc_attr( $data['_order_total'][0] );
					?>" class="calculated" />
				</li>

				<li class="right">
					<label><?php _e( 'Payment Method:', 'woocommerce' ); ?></label>
					<select name="_payment_method" id="_payment_method" class="first">
						<option value=""><?php _e( 'N/A', 'woocommerce' ); ?></option>
						<?php
							$chosen_method 	= ! empty( $data['_payment_method'][0] ) ? $data['_payment_method'][0] : '';
							$found_method 	= false;

							if ( $woocommerce->payment_gateways() ) {
								foreach ( $woocommerce->payment_gateways->payment_gateways() as $gateway ) {
									if ( $gateway->enabled == "yes" ) {
										echo '<option value="' . esc_attr( $gateway->id ) . '" ' . selected( $chosen_method, $gateway->id, false ) . '>' . esc_html( $gateway->get_title() ) . '</option>';
										if ( $chosen_method == $gateway->id )
											$found_method = true;
									}
								}
							}

							if ( ! $found_method && ! empty( $chosen_method ) ) {
								echo '<option value="' . esc_attr( $chosen_method ) . '" selected="selected">' . __( 'Other', 'woocommerce' ) . '</option>';
							} else {
								echo '<option value="other">' . __( 'Other', 'woocommerce' ) . '</option>';
							}
						?>
					</select>
				</li>

			</ul>
			<div class="clear"></div>
		</div>
		<p class="buttons">
			<?php if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) : ?>
				<button type="button" class="button calc_line_taxes"><?php _e( 'Calc taxes', 'woocommerce' ); ?></button>
			<?php endif; ?>
			<button type="button" class="button calc_totals button-primary"><?php _e( 'Calc totals', 'woocommerce' ); ?></button>
		</p>
		<?php
	}

	/**
	 * Save meta box data
	 */
	public static function save( $post_id, $post ) {
		global $wpdb, $woocommerce;

		// Update post data
		update_post_meta( $post_id, '_order_shipping', woocommerce_clean( $_POST['_order_shipping'] ) );
		update_post_meta( $post_id, '_cart_discount', woocommerce_clean( $_POST['_cart_discount'] ) );
		update_post_meta( $post_id, '_order_discount', woocommerce_clean( $_POST['_order_discount'] ) );
		update_post_meta( $post_id, '_order_total', woocommerce_clean( $_POST['_order_total'] ) );

		if ( isset( $_POST['_order_tax'] ) )
			update_post_meta( $post_id, '_order_tax', woocommerce_clean( $_POST['_order_tax'] ) );

		if ( isset( $_POST['_order_shipping_tax'] ) )
			update_post_meta( $post_id, '_order_shipping_tax', woocommerce_clean( $_POST['_order_shipping_tax'] ) );

		// Shipping method handling
		if ( get_post_meta( $post_id, '_shipping_method', true ) !== stripslashes( $_POST['_shipping_method'] ) ) {

			$shipping_method = woocommerce_clean( $_POST['_shipping_method'] );

			update_post_meta( $post_id, '_shipping_method', $shipping_method );
		}

		if ( get_post_meta( $post_id, '_shipping_method_title', true ) !== stripslashes( $_POST['_shipping_method_title'] ) ) {

			$shipping_method_title = woocommerce_clean( $_POST['_shipping_method_title'] );

			if ( ! $shipping_method_title ) {

				$shipping_method = esc_attr( $_POST['_shipping_method'] );
				$methods = $woocommerce->shipping->load_shipping_methods();

				if ( isset( $methods ) && isset( $methods[ $shipping_method ] ) )
					$shipping_method_title = $methods[ $shipping_method ]->get_title();
			}

			update_post_meta( $post_id, '_shipping_method_title', $shipping_method_title );
		}

		// Payment method handling
		if ( get_post_meta( $post_id, '_payment_method', true ) !== stripslashes( $_POST['_payment_method'] ) ) {

			$methods 				= $woocommerce->payment_gateways->payment_gateways();
			$payment_method 		= woocommerce_clean( $_POST['_payment_method'] );
			$payment_method_title 	= $payment_method;

			if ( isset( $methods) && isset( $methods[ $payment_method ] ) )
				$payment_method_title = $methods[ $payment_method ]->get_title();

			update_post_meta( $post_id, '_payment_method', $payment_method );
			update_post_meta( $post_id, '_payment_method_title', $payment_method_title );
		}

		// Tax rows
		if ( isset( $_POST['order_taxes_id'] ) ) {

			$get_values = array( 'order_taxes_id', 'order_taxes_rate_id', 'order_taxes_amount', 'order_taxes_shipping_amount' );

			foreach( $get_values as $value )
				$$value = isset( $_POST[ $value ] ) ? $_POST[ $value ] : array();

			foreach( $order_taxes_id as $item_id ) {

				$item_id  = absint( $item_id );
				$rate_id  = absint( $order_taxes_rate_id[ $item_id ] );

				if ( $rate_id ) {
					$rate     = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %s", $rate_id ) );
					$label    = $rate->tax_rate_name ? $rate->tax_rate_name : $woocommerce->countries->tax_or_vat();
					$compound = $rate->tax_rate_compound ? 1 : 0;

					$code = array();

					$code[] = $rate->tax_rate_country;
					$code[] = $rate->tax_rate_state;
					$code[] = $rate->tax_rate_name ? $rate->tax_rate_name : 'TAX';
					$code[] = absint( $rate->tax_rate_priority );
					$code   = strtoupper( implode( '-', array_filter( $code ) ) );
				} else {
					$code  = '';
					$label = $woocommerce->countries->tax_or_vat();
				}

				$wpdb->update(
					$wpdb->prefix . "woocommerce_order_items",
					array( 'order_item_name' => woocommerce_clean( $code ) ),
					array( 'order_item_id' => $item_id ),
					array( '%s' ),
					array( '%d' )
				);

				woocommerce_update_order_item_meta( $item_id, 'rate_id', $rate_id );
				woocommerce_update_order_item_meta( $item_id, 'label', $label );
				woocommerce_update_order_item_meta( $item_id, 'compound', $compound );

				if ( isset( $order_taxes_amount[ $item_id ] ) )
			 		woocommerce_update_order_item_meta( $item_id, 'tax_amount', woocommerce_clean( $order_taxes_amount[ $item_id ] ) );

			 	if ( isset( $order_taxes_shipping_amount[ $item_id ] ) )
			 		woocommerce_update_order_item_meta( $item_id, 'shipping_tax_amount', woocommerce_clean( $order_taxes_shipping_amount[ $item_id ] ) );
			}
		}
	}
}