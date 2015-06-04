<?php
/**
 * Proceed to checkout button
 *
 * Contains the markup for the proceed to checkout button on the cart
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php
	$checkout_url = WC()->cart->get_checkout_url();

	echo '<a href="' . $checkout_url . '" class="checkout-button button alt wc-forward">' . __( 'Proceed to Checkout', 'woocommerce' ) . '</a>';
?>