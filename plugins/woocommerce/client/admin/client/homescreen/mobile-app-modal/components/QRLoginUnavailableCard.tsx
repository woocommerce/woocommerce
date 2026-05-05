/**
 * External dependencies
 */
import { Button, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import interpolateComponents from '@automattic/interpolate-components';
import { Link } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import {
	QRLoginUnavailableReasons,
	type QRLoginUnavailableReason,
} from './useQRLoginAvailability';

/**
 * WordPress documentation on application passwords. Centralized here (and in
 * useQRLoginToken.tsx) so the URL is easy to refresh when the docs move.
 */
const APPLICATION_PASSWORDS_DOCS_URL =
	'https://developer.wordpress.org/advanced-administration/security/application-passwords/';

/**
 * Deep-link to the wp-admin Application Passwords section. Opens in the same
 * tab — the merchant is already in wp-admin and won't lose context.
 */
const APPLICATION_PASSWORDS_SETTINGS_PATH =
	'/wp-admin/profile.php#application-passwords-section';

/**
 * Permanently-disabled QR card. Rendered when `/qr-login-availability` reports
 * `available: false` so the merchant gets a clear up-front explanation instead
 * of mounting the QR component, spinning, hitting `/qr-login-token`, and only
 * then seeing a generic error.
 */
export const QRLoginUnavailableCard = ( {
	reason,
}: {
	reason: QRLoginUnavailableReason | null;
} ) => {
	// Each reason gets its own headline so the merchant can act on it. The
	// AP-disabled-by-filter case is the most common third-party-plugin
	// scenario; the AP-unsupported and HTTPS branches are typically infra
	// setup issues. All branches expose the same docs link + settings shortcut
	// because the diagnostic flow is the same regardless.
	let headline: React.ReactNode;
	if ( reason === QRLoginUnavailableReasons.HTTPS_REQUIRED ) {
		headline = __(
			'QR sign-in is unavailable because this site is not served over HTTPS. Application passwords require an HTTPS connection.',
			'woocommerce'
		);
	} else {
		// AP unsupported or filtered off — the merchant-facing distinction is
		// blurry, so we share one message and let the docs link carry the
		// explanation.
		headline = interpolateComponents( {
			mixedString: __(
				'QR sign-in is unavailable because application passwords are disabled on this site. Find more about application passwords {{link}}here{{/link}}.',
				'woocommerce'
			),
			components: {
				link: (
					<Link
						href={ APPLICATION_PASSWORDS_DOCS_URL }
						target="_blank"
						type="external"
					/>
				),
			},
		} );
	}

	return (
		<div className="qr-direct-login qr-direct-login--unavailable">
			<Notice
				className="qr-direct-login__unavailable-notice"
				status="warning"
				isDismissible={ false }
			>
				{ headline }
			</Notice>

			{ /*
			   Native <details> intentionally — no extra library, full
			   keyboard + screen-reader support out of the box, and the
			   collapsed state keeps the headline scannable.
			*/ }
			<details className="qr-direct-login__why">
				<summary>
					{ __( 'Why am I seeing this?', 'woocommerce' ) }
				</summary>
				<ul>
					<li>
						{ __(
							'A security plugin (e.g. Wordfence, Solid Security, iThemes Security) may have disabled application passwords.',
							'woocommerce'
						) }
					</li>
					<li>
						{ __(
							'A custom code snippet using the wp_is_application_passwords_available filter may have disabled them.',
							'woocommerce'
						) }
					</li>
					<li>
						{ __(
							'On most hosts, application passwords also require an HTTPS connection.',
							'woocommerce'
						) }
					</li>
				</ul>
			</details>

			<Button
				variant="secondary"
				className="qr-direct-login__open-ap-settings"
				href={ APPLICATION_PASSWORDS_SETTINGS_PATH }
				onClick={ () => {
					recordEvent(
						'mobile_app_qr_direct_login_open_ap_settings',
						{ reason: reason ?? 'unknown' }
					);
				} }
			>
				{ __( 'Open Application Passwords settings', 'woocommerce' ) }
			</Button>
		</div>
	);
};
