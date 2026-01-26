<?php
/**
 * Customer back-in-stock notification confirmation email.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-stock-notification-verify.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hook: woocommerce_email_header.
 *
 * @since 10.2.0
 *
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email );

?>

<table border="0" cellpadding="0" cellspacing="0" id="notification__container"><tr><td>

	<div id="notification__into_content">
		<?php echo wp_kses_post( wpautop( wptexturize( $intro_content ) ) ); ?>
	</div>

	<div id="notification__product">
		<?php
		/**
		 * Hook: woocommerce_email_stock_notification_product.
		 *
		 * @since 10.2.0
		 *
		 * @hooked \Automattic\WooCommerce\Internal\StockNotifications\Templates::email_product_image - 10
		 * @hooked \Automattic\WooCommerce\Internal\StockNotifications\Templates::email_product_title - 20
		 * @hooked \Automattic\WooCommerce\Internal\StockNotifications\Templates::email_product_attributes - 30
		 * @hooked \Automattic\WooCommerce\Internal\StockNotifications\Templates::email_product_price - 40
		 */
		do_action( 'woocommerce_email_stock_notification_product', $product, $notification, $plain_text, $email );
		?>

		<a href="<?php echo esc_url( $verification_link ); ?>" id="notification__action_button"><?php echo esc_html( $verification_button_text ); ?></a>

		<div id="notification__verification_expiration">
			<?php
			// translators: %$s placeholder is the verification expiration datetime string.
			echo wp_kses_post( sprintf( esc_html__( 'This link will remain active for %s.', 'woocommerce' ), $verification_expiration_threshold ) );
			?>
		</div>

	</div>

	<table id="notification__footer"><tr><td>
		<?php
		echo esc_html( __( 'You have received this message because your e-mail address was used to sign up for stock notifications on our store. Wasn\'t you? Please get in touch with us if you keep receiving these messages.', 'woocommerce' ) );
		?>
		<br><br>
		<?php

		/**
		 * Show user-defined additional content - this is set in each email's settings.
		 */
		if ( $additional_content ) {
			echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
		}
		?>
	</td></tr></table>

</td></tr></table>

<?php

/**
 * Hook: woocommerce_email_footer.
 *
 * @since 10.2.0
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
