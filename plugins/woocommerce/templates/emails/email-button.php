<?php
/**
 * Email call-to-action button.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-button.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 11.0.0
 *
 * @var string $url   Button destination URL.
 * @var string $label Button text.
 */

defined( 'ABSPATH' ) || exit;

$wc_button_bg   = get_option( 'woocommerce_email_base_color', '#7f54b3' );
$wc_button_text = wc_hex_is_light( $wc_button_bg ) ? '#000000' : '#ffffff';
?>
<p style="margin: 24px 0;">
	<a href="<?php echo esc_url( $url ); ?>" style="display:inline-block;padding:16px 32px;background-color:<?php echo esc_attr( $wc_button_bg ); ?>;color:<?php echo esc_attr( $wc_button_text ); ?>;border-radius:4px;font-weight:bold;font-size:15px;text-decoration:none;">
		<?php echo esc_html( $label ); ?>
	</a>
</p>
