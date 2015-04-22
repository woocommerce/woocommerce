<?php
/**
 * Auth form grant access
 *
 * @author  WooThemes
 * @package WooCommerce/Templates/Auth
 * @version 2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php do_action( 'woocommerce_auth_page_header' ); ?>

	<h1><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>

	<form method="post" class="grant-access">

	</form>

<?php do_action( 'woocommerce_auth_page_footer' ); ?>
