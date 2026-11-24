<?php
/**
 * General block content for back-in-stock emails
 *
 * Note: This template is only used in the back-in-stock emails.
 *
 * Used to render information for the email editor WooCommerce content block (BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Block
 * @version 11.5.0
 *
 * @var Notification $notification                      Notification object.
 * @var \WC_Product  $product                           Product object.
 * @var string       $intro_content                     Introduction content.
 * @var bool         $plain_text                        Whether the email is plain text.
 * @var \WC_Email    $email                             Email object.
 * @var bool         $is_guest                          Whether the sign-up belongs to a guest. Not set for the verification email.
 * @var string       $account_link                      Link to manage notifications in the customer's account. Not set for the verification email.
 * @var string       $unsubscribe_link                  Unsubscribe link. Not set for the verification email.
 * @var string       $button_text                       Shop button text. Back-in-stock email only.
 * @var string       $button_link                       Shop button link. Back-in-stock email only.
 * @var string       $verification_button_text          Confirm button text. Verification email only.
 * @var string       $verification_link                 Confirm button link. Verification email only.
 * @var string       $verification_expiration_threshold How long the confirm link stays valid. Verification email only.
 */

use Automattic\WooCommerce\Internal\StockNotifications\Notification;

defined( 'ABSPATH' ) || exit;

// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen -- removed to prevent empty new lines.
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd -- removed to prevent empty new lines.

echo wp_kses_post( wpautop( wptexturize( $intro_content ) ) );
?>

<div style="text-align: center;">
	<?php
	/**
	 * Hook: woocommerce_email_stock_notification_product.
	 *
	 * @since 10.2.0
	 *
	 * @hooked \Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailTemplatesController::email_product_image - 10
	 * @hooked \Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailTemplatesController::email_product_title - 20
	 * @hooked \Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailTemplatesController::email_product_attributes - 30
	 * @hooked \Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailTemplatesController::email_product_price - 40
	 */
	do_action( 'woocommerce_email_stock_notification_product', $product, $notification, $plain_text, $email );

	if ( isset( $verification_link ) ) {
		wc_get_template(
			'emails/email-button.php',
			array(
				'url'   => $verification_link,
				'label' => $verification_button_text,
			)
		);
		?>
		<p><?php
		// translators: %s: how long the verification link stays valid, e.g. "60 minutes".
		echo esc_html( sprintf( __( 'This link will remain active for %s.', 'woocommerce' ), $verification_expiration_threshold ) );
		?></p>
		<?php
	} elseif ( isset( $button_link ) ) {
		wc_get_template(
			'emails/email-button.php',
			array(
				'url'   => $button_link,
				'label' => $button_text,
			)
		);
	}
	?>
</div>

<p><?php
if ( isset( $verification_link ) ) {
	esc_html_e( 'You have received this message because your e-mail address was used to sign up for stock notifications on our store. Wasn’t you? Please get in touch with us if you keep receiving these messages.', 'woocommerce' );
} else {
	esc_html_e( 'You have received this message because your e-mail address was used to sign up for stock notifications on our store.', 'woocommerce' );
	echo ' ';
	if ( ! $is_guest && $account_link ) {
		$account_link_tag = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $account_link ), esc_html_x( 'click here', 'account management cta for stock notifications for existing customers', 'woocommerce' ) );
		// translators: %s: "click here" link to the customer's account.
		echo wp_kses_post( sprintf( __( 'To manage your notifications, %s to log in to your account.', 'woocommerce' ), $account_link_tag ) );
	} else {
		$unsubscribe_link_tag = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $unsubscribe_link ), esc_html_x( 'click here', 'unsubscribe cta for stock notifications for guests', 'woocommerce' ) );
		// translators: %s: "click here" unsubscribe link.
		echo wp_kses_post( sprintf( __( 'To stop receiving these messages, %s to unsubscribe.', 'woocommerce' ), $unsubscribe_link_tag ) );
	}
}
?></p>
