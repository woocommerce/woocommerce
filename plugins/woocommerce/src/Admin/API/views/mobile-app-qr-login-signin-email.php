<?php
/**
 * Email body for the QR mobile-app login sign-in notification.
 *
 * Rendered by `MobileAppQRLogin::render_sign_in_notification_email_body()`.
 * Receives the following locals (all required, all already escaped at the
 * controller boundary except where noted):
 *
 * @var \WP_User                $user            Recipient.
 * @var array<string, string>   $device          Sanitized device payload (model, os, os_version, app_version).
 * @var int                     $consumed_at     Unix timestamp of the exchange.
 * @var string                  $ap_name         Descriptive Application Password name (e.g. "Woo Mobile · iPhone 15 · 2026-04-28").
 * @var string                  $site_name       Decoded site name.
 * @var string                  $applications_url Admin URL to the user's Application Passwords list.
 *
 * Markup is intentionally minimal — no inline images, no external assets,
 * no JS — so it survives clipping and dark-mode rewrites in mainstream
 * email clients.
 */

defined( 'ABSPATH' ) || exit;

$device_model      = isset( $device['model'] ) ? (string) $device['model'] : '';
$device_os         = isset( $device['os'] ) ? (string) $device['os'] : '';
$device_os_version = isset( $device['os_version'] ) ? (string) $device['os_version'] : '';
$app_version       = isset( $device['app_version'] ) ? (string) $device['app_version'] : '';

$display_name = $device_model;
if ( '' === $display_name && '' !== $device_os ) {
	$display_name = $device_os;
}
if ( '' === $display_name ) {
	$display_name = __( 'a new device', 'woocommerce' );
}

$os_line = trim( $device_os . ( '' !== $device_os_version ? ' ' . $device_os_version : '' ) );
// `wp_date()` can technically return `false` if the requested format is
// unparseable; coerce to a string so the partial's escaping helpers always
// see a defined value.
$timestamp = (string) wp_date(
	get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
	$consumed_at
);
?>
<p>
	<?php
	printf(
		/* translators: 1: device name (model, OS, or "a new device"). 2: site name. */
		esc_html__( '%1$s just signed in to the WooCommerce mobile app for %2$s.', 'woocommerce' ),
		'<strong>' . esc_html( $display_name ) . '</strong>',
		'<strong>' . esc_html( $site_name ) . '</strong>'
	);
	?>
</p>

<ul>
	<?php if ( '' !== $os_line ) : ?>
		<li>
			<?php
			printf(
				/* translators: %s: OS and version, e.g. "iOS 17.5". */
				esc_html__( 'Operating system: %s', 'woocommerce' ),
				esc_html( $os_line )
			);
			?>
		</li>
	<?php endif; ?>
	<?php if ( '' !== $app_version ) : ?>
		<li>
			<?php
			printf(
				/* translators: %s: mobile app version, e.g. "24.7.0". */
				esc_html__( 'App version: %s', 'woocommerce' ),
				esc_html( $app_version )
			);
			?>
		</li>
	<?php endif; ?>
	<li>
		<?php
		printf(
			/* translators: %s: localized date and time. */
			esc_html__( 'When: %s', 'woocommerce' ),
			esc_html( $timestamp )
		);
		?>
	</li>
	<?php if ( '' !== $ap_name ) : ?>
		<li>
			<?php
			printf(
				/* translators: %s: Application Password name (e.g. "Woo Mobile · iPhone 15 · 2026-04-28"). */
				esc_html__( 'Application Password: %s', 'woocommerce' ),
				esc_html( $ap_name )
			);
			?>
		</li>
	<?php endif; ?>
</ul>

<p>
	<?php esc_html_e( "If this was you, no action is needed.", 'woocommerce' ); ?>
</p>

<p>
	<?php
	printf(
		/* translators: %s: HTML link to the Application Passwords list in the user's profile. */
		esc_html__( "If this wasn't you, %s right away.", 'woocommerce' ),
		'<a href="' . esc_url( $applications_url ) . '">' . esc_html__( 'revoke access', 'woocommerce' ) . '</a>'
	);
	?>
</p>
