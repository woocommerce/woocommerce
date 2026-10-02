<?php
/**
 * Admin Low stock email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/low-stock.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\HTML
 * @version 11.2.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Output the email header.
 *
 * @hooked WC_Emails::email_header() Output the email header.
 * @since 11.2.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p><?php echo esc_html( $message ); ?></p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php if ( $product instanceof WC_Product && $product->get_id() ) : ?>
	<p><a href="<?php echo esc_url( get_edit_post_link( $product->get_id(), '' ) ); ?>"><?php esc_html_e( 'Manage this product', 'woocommerce' ); ?></a></p>
<?php endif; ?>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/**
 * Output the email footer.
 *
 * @hooked WC_Emails::email_footer() Output the email footer.
 * @since 11.2.0
 */
do_action( 'woocommerce_email_footer', $email );
