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

$user = wp_get_current_user();

?>

<?php do_action( 'woocommerce_auth_page_header' ); ?>

	<h1><?php printf( __( 'Grant "%s" permissions to "%s".', 'woocommerce' ), esc_html( $scope ), wc_clean( $app_name ) ); ?></h1>

	<?php wc_print_notices(); ?>

	<?php echo get_avatar( $user->ID, 70 ); ?>

	<p><?php printf( __( 'Logged in as %s.', 'woocommerce' ), wc_clean( $user->display_name ) ); ?></p>

	<a href="<?php echo esc_url( $granted_url ); ?>" class="button button-primary"><?php _e( 'Authorize', 'woocommerce' ); ?></a>

	<p><?php printf( __( 'Not %s? %s', 'woocommerce' ), wc_clean( $user->display_name ), '<a href="' . esc_url( $logout_url ) . '">' . __( 'Logout', 'woocommerce' ) . '</a>' ); ?></p>

	<a href="<?php echo esc_url( $return_url ); ?>"><?php printf( __( 'Return To %s', 'woocommerce' ), wc_clean( $app_name ) ); ?></a>

<?php do_action( 'woocommerce_auth_page_footer' ); ?>
