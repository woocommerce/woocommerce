<?php
/**
 * Customer cart recovery email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-cart-recovery.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 11.3.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Hook for the woocommerce_email_header.
 *
 * @param string   $email_heading The email heading.
 * @param WC_Email $email         The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p><?php esc_html_e( 'Hi,', 'woocommerce' ); ?></p>
<p><?php esc_html_e( 'You left these items in your cart. They\'re ready when you are.', 'woocommerce' ); ?></p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<ul>
	<?php foreach ( $items as $item ) : ?>
		<li>
			<?php
			/* translators: 1: product name, 2: quantity */
			echo esc_html( sprintf( __( '%1$s &times; %2$d', 'woocommerce' ), $item['product']->get_name(), $item['quantity'] ) );
			?>
		</li>
	<?php endforeach; ?>
</ul>

<?php if ( ! empty( $recovery_url ) ) : ?>
<p>
	<a href="<?php echo esc_url( $recovery_url ); ?>"><?php esc_html_e( 'Return to your cart', 'woocommerce' ); ?></a>
</p>
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
 * Hook for the woocommerce_email_footer.
 *
 * @param WC_Email $email The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
