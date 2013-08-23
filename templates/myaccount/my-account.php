<?php
/**
 * My Account page
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;

wc_print_messages(); ?>

<p class="myaccount_user">
	<?php
	printf(
		__( 'Hello <strong>%s</strong> (not %s? <a href="%s">Sign out</a>).', 'woocommerce' ) . ' ',
		$current_user->display_name,
		$current_user->display_name,
		wp_logout_url()
	);

	printf( __( 'From your account dashboard you can view your recent orders, manage your shipping and billing addresses and <a href="%s">edit your password and account details</a>.', 'woocommerce' ),
		woocommerce_customer_edit_account_url()
	);
	?>
</p>

<?php do_action( 'woocommerce_before_my_account' ); ?>

<?php woocommerce_get_template( 'myaccount/my-downloads.php' ); ?>

<?php woocommerce_get_template( 'myaccount/my-orders.php', array( 'order_count' => $order_count ) ); ?>

<?php woocommerce_get_template( 'myaccount/my-address.php' ); ?>

<?php do_action( 'woocommerce_after_my_account' ); ?>