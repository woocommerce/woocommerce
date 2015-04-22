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

	<?php wc_print_notices(); ?>

	<form method="post" class="grant-access">

		<input type="hidden" name="permission_type" value="<?php echo absint( $permission_type ); ?>">
	</form>

	<a href="<?php echo esc_url( urldecode( $return_url ) ); ?>"><?php printf( __( 'Return To %s', 'woocommerce' ), wc_clean( $app_name ) ); ?></a>

<?php do_action( 'woocommerce_auth_page_footer' ); ?>
