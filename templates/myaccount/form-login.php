<?php
/**
 * Login Form
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce; ?>

<?php wc_print_messages(); ?>

<?php do_action('woocommerce_before_customer_login_form'); ?>

<?php if (get_option('woocommerce_enable_myaccount_registration')=='yes') : ?>

<div class="col2-set" id="customer_login">

	<div class="col-1">

<?php endif; ?>

		<h2><?php _e( 'Login', 'woocommerce' ); ?></h2>

		<?php do_action( 'woocommerce_after_heading_login_form' ); ?>

		<form method="post" class="login">
			<p class="form-row form-row-wide">
				<label for="username"><?php _e( 'Username or email address', 'woocommerce' ); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="username" id="username" />
			</p>
			<p class="form-row form-row-wide">
				<label for="password"><?php _e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
				<input class="input-text" type="password" name="password" id="password" />
			</p>

			<?php do_action('woocommerce_login_form'); ?>

			<p class="form-row">
				<?php wp_nonce_field( 'woocommerce-login' ) ?>
				<input type="submit" class="button" name="login" value="<?php _e( 'Login', 'woocommerce' ); ?>" />
				<label for="rememberme" class="inline">
					<input name="rememberme" type="checkbox" id="rememberme" value="forever" /> <?php _e( 'Remember me', 'woocommerce' ); ?>
				</label>
			</p>
			<p class="lost_password">
				<a href="<?php echo esc_url( woocommerce_get_endpoint_url( 'lost-password', '', get_permalink( woocommerce_get_page_id( 'myaccount' ) ) ) ); ?>"><?php _e( 'Lost your password?', 'woocommerce' ); ?></a>
			</p>
		</form>

<?php if (get_option('woocommerce_enable_myaccount_registration')=='yes') : ?>

	</div>

	<div class="col-2">

		<h2><?php _e( 'Register', 'woocommerce' ); ?></h2>

		<?php do_action( 'woocommerce_after_heading_register_form' ); ?>

		<form method="post" class="register">

			<?php if ( get_option( 'woocommerce_registration_generate_username' ) == 'no' ) : ?>

				<p class="form-row form-row-wide">
					<label for="reg_username"><?php _e( 'Username', 'woocommerce' ); ?> <span class="required">*</span></label>
					<input type="text" class="input-text" name="username" id="reg_username" value="<?php if ( ! empty( $_POST['username'] ) ) esc_attr_e($_POST['username']); ?>" />
				</p>

			<?php endif; ?>

			<p class="form-row form-row-wide">
				<label for="reg_email"><?php _e( 'Email address', 'woocommerce' ); ?> <span class="required">*</span></label>
				<input type="email" class="input-text" name="email" id="reg_email" value="<?php if ( ! empty( $_POST['email'] ) ) esc_attr_e($_POST['email']); ?>" />
			</p>

			<p class="form-row form-row-wide">
				<label for="reg_password"><?php _e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
				<input type="password" class="input-text" name="password" id="reg_password" value="<?php if ( ! empty( $_POST['password'] ) ) esc_attr_e( $_POST['password'] ); ?>" />
			</p>

			<?php do_action( 'woocommerce_register_form' ); ?>

			<!-- Spam Trap -->
			<div style="left:-999em; position:absolute;"><label for="trap"><?php _e( 'Anti-spam', 'woocommerce' ); ?></label><input type="text" name="email_2" id="trap" tabindex="-1" /></div>

			<p class="form-row">
				<?php wp_nonce_field( 'woocommerce-register', 'register') ?>
				<input type="submit" class="button" name="register" value="<?php _e( 'Register', 'woocommerce' ); ?>" />
			</p>
		</form>

	</div>

</div>
<?php endif; ?>

<?php do_action('woocommerce_after_customer_login_form'); ?>
