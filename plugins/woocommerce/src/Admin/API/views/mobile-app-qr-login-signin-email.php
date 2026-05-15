<?php
/**
 * Email body for the QR mobile-app login sign-in notification.
 *
 * Renders a full HTML document so we can own the entire shell — the WC
 * mailer's wrap_message() auto-prepends a small site-name header that
 * duplicates the subject line and squeezes the body into a narrow column.
 *
 * Receives the following locals (validated upstream by the controller):
 *
 * @var \WP_User             $user             Recipient.
 * @var array<string,string> $device           Sanitized device payload (model, brand, os, os_version, app_version).
 * @var int                  $consumed_at      Unix timestamp of the exchange.
 * @var string               $ap_name          Descriptive Application Password name.
 * @var string               $site_name        Decoded site name.
 * @var string               $subject          Email subject; rendered as the in-body heading.
 * @var string               $applications_url Admin URL to the user's Application Passwords list.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$device_model      = isset( $device['model'] ) ? trim( (string) $device['model'] ) : '';
$device_brand      = isset( $device['brand'] ) ? trim( (string) $device['brand'] ) : '';
$device_os         = isset( $device['os'] ) ? trim( (string) $device['os'] ) : '';
$device_os_version = isset( $device['os_version'] ) ? trim( (string) $device['os_version'] ) : '';
$app_version       = isset( $device['app_version'] ) ? trim( (string) $device['app_version'] ) : '';

// /qr-login-scan requires a device payload, so by the time this email
// renders we have at least an OS label. Prefer "{Brand} {Model}" when both
// are present; fall back to model alone, then to OS.
if ( '' !== $device_brand && '' !== $device_model ) {
	$display_name = ucfirst( $device_brand ) . ' ' . $device_model;
} elseif ( '' !== $device_model ) {
	$display_name = $device_model;
} elseif ( '' !== $device_os ) {
	$display_name = $device_os;
} else {
	$display_name = __( 'WooCommerce mobile app', 'woocommerce' );
}

$os_line   = trim( $device_os . ( '' !== $device_os_version ? ' ' . $device_os_version : '' ) );
$timestamp = (string) wp_date(
	get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
	$consumed_at
);

$preheader = sprintf(
	/* translators: 1: device name. 2: site name. */
	__( '%1$s just signed in to the WooCommerce mobile app for %2$s.', 'woocommerce' ),
	$display_name,
	$site_name
);

$brand_purple_50    = '#873eff';
$brand_purple_70    = '#5007aa';
$text_primary       = '#1d2327';
$text_secondary     = '#50575e';
$text_muted         = '#757575';
$divider            = '#dcdcde';
$card_background    = '#f6f7f7';
$body_background    = '#f6f7f7';
$content_background = '#ffffff';
$font_stack         = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif";
$language           = esc_attr( get_bloginfo( 'language' ) );
?>
<!DOCTYPE html>
<html lang="<?php echo $language; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped above. ?>">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="x-apple-disable-message-reformatting" />
<title><?php echo esc_html( $subject ); ?></title>
</head>
<body style="margin:0; padding:0; background:<?php echo esc_attr( $body_background ); ?>; font-family:<?php echo esc_attr( $font_stack ); ?>; -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;">
<div style="display:none !important; visibility:hidden; opacity:0; height:0; width:0; max-height:0; max-width:0; overflow:hidden; mso-hide:all; font-size:1px; color:<?php echo esc_attr( $body_background ); ?>; line-height:1px;"><?php echo esc_html( $preheader ); ?></div>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:<?php echo esc_attr( $body_background ); ?>; border-collapse:collapse;">
<tr>
<td align="center" style="padding:24px 16px;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:640px; margin:0 auto; background:<?php echo esc_attr( $content_background ); ?>; border-radius:8px; border-collapse:separate; box-shadow:0 1px 2px rgba(0,0,0,0.04);">
<tr>
<td style="height:6px; line-height:6px; font-size:0; background:<?php echo esc_attr( $brand_purple_50 ); ?>; border-top-left-radius:8px; border-top-right-radius:8px;">&nbsp;</td>
</tr>
<tr>
<td style="padding:32px 40px 8px 40px;">
<h1 style="margin:0; font-family:<?php echo esc_attr( $font_stack ); ?>; font-size:24px; line-height:1.3; font-weight:600; letter-spacing:-0.01em; color:<?php echo esc_attr( $text_primary ); ?>;"><?php echo esc_html( $subject ); ?></h1>
</td>
</tr>
<tr>
<td style="padding:20px 40px 0 40px;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:<?php echo esc_attr( $card_background ); ?>; border-left:4px solid <?php echo esc_attr( $brand_purple_50 ); ?>; border-radius:6px; border-collapse:separate;">
<tr>
<td style="padding:20px 24px;">
<p style="margin:0 0 6px 0; font-family:<?php echo esc_attr( $font_stack ); ?>; font-size:18px; font-weight:600; line-height:1.3; color:<?php echo esc_attr( $text_primary ); ?>;"><?php echo esc_html( $display_name ); ?></p>
<?php if ( '' !== $os_line ) : ?>
<p style="margin:0 0 4px 0; font-family:<?php echo esc_attr( $font_stack ); ?>; font-size:14px; line-height:1.5; color:<?php echo esc_attr( $text_secondary ); ?>;"><?php echo esc_html( $os_line ); ?></p>
<?php endif; ?>
<?php if ( '' !== $app_version ) : ?>
	<?php
	/* translators: %s: mobile app version, e.g. "24.7.0". */
	$app_version_line = sprintf( esc_html__( 'App version %s', 'woocommerce' ), esc_html( $app_version ) );
	?>
<p style="margin:0 0 4px 0; font-family:<?php echo esc_attr( $font_stack ); ?>; font-size:14px; line-height:1.5; color:<?php echo esc_attr( $text_secondary ); ?>;"><?php echo $app_version_line; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped above. ?></p>
<?php endif; ?>
<p style="margin:8px 0 0 0; font-family:<?php echo esc_attr( $font_stack ); ?>; font-size:13px; line-height:1.5; color:<?php echo esc_attr( $text_muted ); ?>;"><?php echo esc_html( $timestamp ); ?></p>
</td>
</tr>
</table>
</td>
</tr>
<tr>
<td style="padding:28px 40px 0 40px;">
<h2 style="margin:0 0 8px 0; font-family:<?php echo esc_attr( $font_stack ); ?>; font-size:18px; line-height:1.4; font-weight:600; color:<?php echo esc_attr( $text_primary ); ?>;"><?php esc_html_e( 'Was this you?', 'woocommerce' ); ?></h2>
<p style="margin:0 0 24px 0; font-family:<?php echo esc_attr( $font_stack ); ?>; font-size:14px; line-height:1.6; color:<?php echo esc_attr( $text_secondary ); ?>;"><?php esc_html_e( "If you recognise this sign-in, you don't need to do anything. If it wasn't you, revoke access immediately to remove this device.", 'woocommerce' ); ?></p>
</td>
</tr>
<tr>
<td style="padding:0 40px 32px 40px;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;">
<tr>
<td align="center" bgcolor="<?php echo esc_attr( $brand_purple_50 ); ?>" style="border-radius:6px; padding:14px 32px; mso-padding-alt:14px 32px;"><a href="<?php echo esc_url( $applications_url ); ?>" style="font-family:<?php echo esc_attr( $font_stack ); ?>; font-size:15px; font-weight:600; line-height:1; color:#ffffff; text-decoration:none;"><?php esc_html_e( 'Revoke access', 'woocommerce' ); ?></a></td>
</tr>
</table>
</td>
</tr>
<?php
$manage_link  = '<a href="' . esc_url( $applications_url ) . '" style="color:' . esc_attr( $brand_purple_70 ) . '; text-decoration:underline;">';
$manage_link .= esc_html__( 'Users → Profile → Application Passwords', 'woocommerce' ) . '</a>';
/* translators: %s: HTML link to the Application Passwords screen. */
$manage_line = sprintf( esc_html__( 'You can manage all connected devices anytime under %s.', 'woocommerce' ), $manage_link );
?>
<tr>
<td style="padding:0 40px 32px 40px; border-top:1px solid <?php echo esc_attr( $divider ); ?>;">
<p style="margin:24px 0 0 0; font-family:<?php echo esc_attr( $font_stack ); ?>; font-size:13px; line-height:1.6; color:<?php echo esc_attr( $text_muted ); ?>;"><?php echo $manage_line; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped above. ?></p>
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
