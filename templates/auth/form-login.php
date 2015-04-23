<?php
/**
 * Auth form login
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

	<h1><?php printf( __( 'Login To %s', 'woocommerce' ), esc_html( get_bloginfo( 'name' ) ) ); ?></h1>

	<?php wc_print_notices(); ?>

	<form method="post" class="login">

		<p class="form-row form-row-wide">
			<label for="username"><?php _e( 'Username or email address', 'woocommerce' ); ?> <span class="required">*</span></label>
			<input type="text" class="input-text" name="username" id="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( $_POST['username'] ) : ''; ?>" />
		</p>
		<p class="form-row form-row-wide">
			<label for="password"><?php _e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
			<input class="input-text" type="password" name="password" id="password" />
		</p>
		<p class="form-row">
			<?php wp_nonce_field( 'woocommerce-login' ); ?>
			<input type="submit" class="button" name="login" value="<?php _e( 'Login', 'woocommerce' ); ?>" />
		</p>

		<input type="hidden" name="redirect" value="<?php echo esc_url( $redirect_url ); ?>" />
	</form>

	<a href="<?php echo esc_url( $return_url ); ?>"><?php printf( __( 'Return To %s', 'woocommerce' ), wc_clean( $app_name ) ); ?></a>

<?php do_action( 'woocommerce_auth_page_footer' ); ?>
