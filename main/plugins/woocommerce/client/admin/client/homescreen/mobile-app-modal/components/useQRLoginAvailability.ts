/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { WC_ADMIN_NAMESPACE } from '@woocommerce/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * REST reason codes returned by `/qr-login-availability`. Mirrors the
 * `MobileAppQRLogin::AVAILABILITY_REASON_*` constants on the server.
 */
export const QRLoginUnavailableReasons = {
	HTTPS_REQUIRED: 'https_required',
	APPLICATION_PASSWORDS_UNSUPPORTED: 'application_passwords_unsupported',
	APPLICATION_PASSWORDS_DISABLED_BY_FILTER:
		'application_passwords_disabled_by_filter',
} as const;

export type QRLoginUnavailableReason =
	( typeof QRLoginUnavailableReasons )[ keyof typeof QRLoginUnavailableReasons ];

type QRLoginAvailabilityResponse = {
	available: boolean;
	reason: QRLoginUnavailableReason | null;
};

/**
 * Cheap up-front capability probe for the QR login feature. Hits
 * `/qr-login-availability` once on mount so the QR card can be rendered
 * permanently disabled (with the right explanation) instead of optimistically
 * mounting and bouncing off `/qr-login-token`.
 *
 * The probe is intentionally idempotent and unrate-limited server-side, so
 * remounting the modal is fine.
 */
export const useQRLoginAvailability = () => {
	// `null` means "still probing"; `true`/`false` are the resolved states.
	// Three states (not just `available: boolean`) so the UI can render an
	// initial spinner instead of flashing a wrong state.
	const [ available, setAvailable ] = useState< boolean | null >( null );
	const [ reason, setReason ] = useState< QRLoginUnavailableReason | null >(
		null
	);

	useEffect( () => {
		let cancelled = false;

		apiFetch< QRLoginAvailabilityResponse >( {
			path: `${ WC_ADMIN_NAMESPACE }/mobile-app/qr-login-availability`,
			method: 'GET',
		} )
			.then( ( response ) => {
				if ( cancelled ) {
					return;
				}
				if ( ! response || typeof response.available !== 'boolean' ) {
					// Defensive: if the response shape is unexpected, treat
					// the feature as available so the existing token-fetch
					// path still runs and surfaces the real error.
					setAvailable( true );
					setReason( null );
					return;
				}
				setAvailable( response.available );
				setReason( response.reason ?? null );
			} )
			.catch( () => {
				if ( cancelled ) {
					return;
				}
				// Network / 5xx — fall through to the optimistic path so the
				// existing error handling in <QRDirectLoginCode /> takes over.
				setAvailable( true );
				setReason( null );
			} );

		return () => {
			cancelled = true;
		};
	}, [] );

	return {
		isLoading: available === null,
		available: available ?? false,
		reason,
	};
};
