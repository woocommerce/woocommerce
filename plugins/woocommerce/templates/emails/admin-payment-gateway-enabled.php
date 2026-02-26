<?php
/**
 * Admin payment gateway enabled email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/admin-payment-gateway-enabled.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\HTML
 * @version 10.7.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Output the email header.
 *
 * @hooked WC_Emails::email_header() Output the email header.
 * @since 10.7.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p>
<?php
	/* translators: %s: Username */
	printf( esc_html__( 'Howdy %s,', 'woocommerce' ), esc_html( $username ) );
?>
</p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<p>
<?php
	printf(
		/* translators: 1: gateway title, 2: site URL */
		esc_html__( 'The payment gateway "%1$s" was just enabled on this site: %2$s', 'woocommerce' ),
		esc_html( $gateway_title ),
		esc_html( home_url() )
	);
	?>
	</p>

<p><?php esc_html_e( 'If you did not enable this payment gateway, please log in to your site and consider disabling it here:', 'woocommerce' ); ?></p>
<p><a href="<?php echo esc_url( $gateway_settings_url ); ?>"><?php echo esc_url( $gateway_settings_url ); ?></a></p>

<p>
<?php
	/* translators: %s: admin email address */
	printf( esc_html__( 'This email has been sent to %s', 'woocommerce' ), esc_html( $admin_email ) );
?>
</p>

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
 * @since 10.6.0
 */
do_action( 'woocommerce_email_footer', $email );
