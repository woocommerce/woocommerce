<?php
/**
 * Shipping Methods Display
 *
 * In 2.1 we show methods per package. This allows for multiple methods per order if so desired.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-shipping.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you (the theme developer).
 * will need to copy the new files to your theme to maintain compatibility. We try to do this.
 * as little as possible, but it does happen. When this occurs the version of the template file will.
 * be bumped and the readme will list any important changes.
 *
 * @see 	    http://docs.woothemes.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.5.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<tr class="shipping">
	<th><?php
		if ( $show_package_details && $index ) {
			$shipping_name = sprintf( __( 'Shipping #%d', 'woocommerce' ), $index + 1 );
		} else {
			$shipping_name = __( 'Shipping', 'woocommerce' );
		}

		echo wp_kses_post( apply_filters( 'woocommerce_shipping_package_name', $shipping_name, $index, $package ) );
	?></th>
	<td data-title="<?php echo wp_kses_post( apply_filters( 'woocommerce_shipping_package_name', $shipping_name, $index, $package ) ); ?>">
		<?php if ( empty( $available_methods ) ) : ?>

			<?php if ( ( WC()->countries->get_states( WC()->customer->get_shipping_country() ) && ! WC()->customer->get_shipping_state() ) || ! WC()->customer->get_shipping_postcode() ) : ?>

				<?php echo wpautop( __( 'Shipping costs will be calculated once you have provided your address.', 'woocommerce' ) ); ?>

			<?php else : ?>

				<?php echo apply_filters( is_cart() ? 'woocommerce_cart_no_shipping_available_html' : 'woocommerce_no_shipping_available_html', wpautop( __( 'There are no shipping methods available. Please double check your address, or contact us if you need any help.', 'woocommerce' ) ) ); ?>

			<?php endif; ?>

		<?php elseif ( 1 === count( $available_methods ) ) : ?>

			<?php $method = current( $available_methods ); ?>
			<?php echo wc_cart_totals_shipping_method_label( $method ); ?>
			<input type="hidden" name="shipping_method[<?php echo $index; ?>]" data-index="<?php echo $index; ?>" id="shipping_method_<?php echo $index; ?>" value="<?php echo esc_attr( $method->id ); ?>" class="shipping_method" />

		<?php elseif ( 'select' === get_option( 'woocommerce_shipping_method_format' ) ) : ?>

			<select name="shipping_method[<?php echo $index; ?>]" data-index="<?php echo $index; ?>" id="shipping_method_<?php echo $index; ?>" class="shipping_method">
				<?php foreach ( $available_methods as $method ) : ?>
					<option value="<?php echo esc_attr( $method->id ); ?>" <?php selected( $method->id, $chosen_method ); ?>><?php echo wc_cart_totals_shipping_method_label( $method ); ?></option>
				<?php endforeach; ?>
			</select>

		<?php else : ?>

			<ul id="shipping_method">
				<?php foreach ( $available_methods as $method ) : ?>
					<li>
						<input type="radio" name="shipping_method[<?php echo $index; ?>]" data-index="<?php echo $index; ?>" id="shipping_method_<?php echo $index; ?>_<?php echo sanitize_title( $method->id ); ?>" value="<?php echo esc_attr( $method->id ); ?>" <?php checked( $method->id, $chosen_method ); ?> class="shipping_method" />
						<label for="shipping_method_<?php echo $index; ?>_<?php echo sanitize_title( $method->id ); ?>"><?php echo  wc_cart_totals_shipping_method_label( $method ); ?></label>
					</li>
				<?php endforeach; ?>
			</ul>

		<?php endif; ?>

		<?php if ( $show_package_details ) : ?>
			<?php
				foreach ( $package['contents'] as $item_id => $values ) {
					$product_names[] = $values['data']->get_title() . ' &times;' . $values['quantity'];
				}
				echo '<p class="woocommerce-shipping-contents"><small>' . __( 'Shipping', 'woocommerce' ) . ': ' . implode( ', ', $product_names ) . '</small></p>';
			?>
		<?php endif; ?>

		<?php if ( is_cart() && ! $index ) : ?>
			<?php woocommerce_shipping_calculator(); ?>
		<?php endif; ?>
	</td>
</tr>
