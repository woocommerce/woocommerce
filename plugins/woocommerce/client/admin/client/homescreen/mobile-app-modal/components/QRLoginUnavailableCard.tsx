/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';
import type { ReactNode } from 'react';

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
	// setup issues. All branches share the docs link because the diagnostic
	// flow is the same regardless.
	let headline: ReactNode;
	if ( reason === QRLoginUnavailableReasons.HTTPS_REQUIRED ) {
		headline = __(
			'QR sign-in is unavailable because this site is not served over HTTPS. Application passwords require an HTTPS connection.',
			'woocommerce'
		);
	} else {
		// AP unsupported or filtered off — the merchant-facing distinction is
		// blurry, so we share one message and let the docs link carry the
		// explanation.
		headline = createInterpolateElement(
			__(
				'Mobile login is unavailable if application passwords are disabled on your site. Find more about application passwords <link>here</link>.',
				'woocommerce'
			),
			{
				link: (
					<Link
						href={ APPLICATION_PASSWORDS_DOCS_URL }
						target="_blank"
						type="external"
					/>
				),
			}
		);
	}

	return (
		<div className="woocommerce-qr-direct-login woocommerce-qr-direct-login--unavailable">
			<Notice
				className="woocommerce-qr-direct-login__unavailable-notice"
				status="warning"
				isDismissible={ false }
			>
				{ headline }
			</Notice>

			{ /*
			   Native <details> — full keyboard + screen-reader support out
			   of the box, and the collapsed state keeps the headline
			   scannable.
			*/ }
			<details className="woocommerce-qr-direct-login__why">
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
		</div>
	);
};
