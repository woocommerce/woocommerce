<?php
/**
 * Login form
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;

if (is_user_logged_in()) return;
?>
<form method="post" class="login" <?php if ( $hidden ) echo 'style="display:none;"'; ?>>
	<?php if ( $message ) echo wpautop( wptexturize( $message ) ); ?>

	<?php do_action( 'woocommerce_after_message_shop_login_form' ); ?>

	<p class="form-row form-row-first">
		<label for="username"><?php _e( 'Username or email', 'woocommerce' ); ?> <span class="required">*</span></label>
		<input type="text" class="input-text" name="username" id="username" />
	</p>
	<p class="form-row form-row-last">
		<label for="password"><?php _e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
		<input class="input-text" type="password" name="password" id="password" />
	</p>

	<?php do_action('woocommerce_shop_login_form'); ?>

	<div class="clear"></div>

	<p class="form-row">
		<?php wp_nonce_field( 'woocommerce-login' ) ?>
		<input type="submit" class="button" name="login" value="<?php _e( 'Login', 'woocommerce' ); ?>" />
		<input type="hidden" name="redirect" value="<?php echo esc_url( $redirect ) ?>" />
		<label for="rememberme" class="inline">
			<input name="rememberme" type="checkbox" id="rememberme" value="forever" /> <?php _e( 'Remember me', 'woocommerce' ); ?>
		</label>
	</p>
	<p class="lost_password">
		<a href="<?php echo esc_url( woocommerce_get_endpoint_url( 'lost-password', '', get_permalink( woocommerce_get_page_id( 'myaccount' ) ) ) ); ?>"><?php _e( 'Lost your password?', 'woocommerce' ); ?></a>
	</p>

	<div class="clear"></div>
</form>
