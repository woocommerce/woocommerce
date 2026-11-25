<?php
/**
 * Confirm email address form
 *
 * Shown on its own when a customer opens an email confirmation link. They enter their password to confirm the address.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-confirm-email.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.3.0
 *
 * @var string $email             Email address being confirmed. Empty for logged out customers, so the page does not show it.
 * @var string $lost_password_url Lost password URL.
 */

defined( 'ABSPATH' ) || exit;
?>

<form method="post" class="woocommerce-form woocommerce-form-confirm-email confirm-email">

	<h2><?php esc_html_e( 'Confirm your email address', 'woocommerce' ); ?></h2>

	<?php if ( $email ) : ?>

		<p>
			<?php
			printf(
				/* translators: %s: email address */
				esc_html__( 'Enter your password to confirm %s.', 'woocommerce' ),
				'<strong>' . esc_html( $email ) . '</strong>'
			);
			?>
		</p>

		<input type="hidden" name="wc_confirm_username" autocomplete="username" value="<?php echo esc_attr( $email ); ?>" />

	<?php else : ?>

		<p><?php esc_html_e( 'Enter your password to confirm your email address.', 'woocommerce' ); ?></p>

	<?php endif; ?>

	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label for="wc_confirm_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
		<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="wc_confirm_password" id="wc_confirm_password" autocomplete="current-password" required aria-required="true" />
	</p>

	<p class="woocommerce-form-row form-row">
		<?php wp_nonce_field( 'woocommerce-confirm-email', 'woocommerce-confirm-email-nonce' ); ?>
		<button type="submit" class="woocommerce-Button button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="wc_confirm_email" value="1"><?php esc_html_e( 'Confirm', 'woocommerce' ); ?></button>
	</p>

	<p class="woocommerce-LostPassword lost_password">
		<a href="<?php echo esc_url( $lost_password_url ); ?>"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></a>
	</p>

</form>
