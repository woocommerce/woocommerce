<?php
/**
 * Fraud Protection OTP email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/fraud-protection-otp.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Fires to output the email header.
 *
 * @hooked WC_Emails::email_header()
 *
 * @since 10.4.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p><?php esc_html_e( 'We received a request to verify your email address for fraud protection purposes.', 'woocommerce' ); ?></p>
<p><?php esc_html_e( 'Use the following verification code to complete your purchase:', 'woocommerce' ); ?></p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php if ( $email_improvements_enabled ) : ?>
	<div class="otp-code-section" style="text-align: center; margin: 30px 0;">
		<div class="otp-code" style="display: inline-block; background-color: #f5f5f5; border: 2px solid #333; border-radius: 8px; padding: 20px 40px; font-size: 32px; font-weight: bold; letter-spacing: 8px; font-family: monospace;">
			<?php echo esc_html( $otp_code ); ?>
		</div>
	</div>
	<div class="otp-details" style="text-align: center; margin: 20px 0;">
		<p style="color: #666; font-size: 14px;">
			<?php
			/* translators: %d: expiration time in minutes */
			printf( esc_html__( 'This code will expire in %d minutes.', 'woocommerce' ), (int) $expiration_minutes );
			?>
		</p>
	</div>
<?php else : ?>
	<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 20px 0;">
		<tr>
			<td align="center">
				<div style="display: inline-block; background-color: #f5f5f5; border: 2px solid #333; border-radius: 8px; padding: 20px 40px; font-size: 32px; font-weight: bold; letter-spacing: 8px; font-family: monospace;">
					<?php echo esc_html( $otp_code ); ?>
				</div>
			</td>
		</tr>
		<tr>
			<td align="center" style="padding-top: 20px;">
				<p style="color: #666; font-size: 14px;">
					<?php
					/* translators: %d: expiration time in minutes */
					printf( esc_html__( 'This code will expire in %d minutes.', 'woocommerce' ), (int) $expiration_minutes );
					?>
				</p>
			</td>
		</tr>
	</table>
<?php endif; ?>

<?php echo $email_improvements_enabled ? '<div class="email-security-notice">' : ''; ?>
<p style="font-size: 14px; color: #666;">
	<?php esc_html_e( 'For security reasons, do not share this code with anyone.', 'woocommerce' ); ?>
</p>
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
 * @since 10.4.0
 */
do_action( 'woocommerce_email_footer', $email );
