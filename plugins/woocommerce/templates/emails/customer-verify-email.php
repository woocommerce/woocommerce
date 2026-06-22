<?php
/**
 * Customer verify email address email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-verify-email.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 11.0.0
 *
 * @var string    $email_heading      Email heading.
 * @var string    $additional_content Additional content below the body.
 * @var string    $user_display_name  Customer's display name.
 * @var string    $user_email         Email address being confirmed.
 * @var string    $verify_code        One-time verification code.
 * @var string    $blogname           Site name.
 * @var bool      $sent_to_admin      Whether sent to admin.
 * @var bool      $plain_text         Whether plain-text variant.
 * @var \WC_Email $email              Email object.
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Fires to output the email header.
 *
 * @hooked WC_Emails::email_header()
 *
 * @since 3.7.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>

<?php /* translators: %s: Customer first name, or username if name is not available. */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $user_display_name ) ); ?></p>
<?php /* translators: %s: the customer's email address. */ ?>
<p><?php printf( esc_html__( "Once you've confirmed that %s is your email address, we'll link any past orders to your account.", 'woocommerce' ), '<b>' . esc_html( $user_email ) . '</b>' ); ?></p>
<p><?php esc_html_e( 'Use this code to confirm your email address:', 'woocommerce' ); ?></p>
<p style="font-size: 32px; font-weight: 700; letter-spacing: 0.4em; text-align: center; margin: 24px 0;"><?php echo esc_html( $verify_code ); ?></p>
<p><?php esc_html_e( "If you didn't request this email, there's nothing to worry about, and you can safely ignore it.", 'woocommerce' ); ?></p>

<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content email-additional-content-aligned">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/**
 * Fires to output the email footer.
 *
 * @hooked WC_Emails::email_footer()
 *
 * @since 3.7.0
 */
do_action( 'woocommerce_email_footer', $email );
