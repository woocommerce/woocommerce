/**
 * External dependencies
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { WC_ADMIN_NAMESPACE } from '@woocommerce/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Standalone hook that wraps the `DELETE /wc-admin/mobile-app/qr-login-revoke`
 * call.
 *
 * Owned separately from `useQRLoginToken` because the third stepper step
 * outlives the QR component — once we advance to "Signed in successfully" the
 * QR is unmounted, but we still need to revoke the Application Password
 * issued by the most recent exchange. Keeping the revoke logic in its own
 * hook lets the success step manage its own request lifecycle without
 * threading state up from the unmounted QR.
 */
export const useRevokeQRLoginAccess = () => {
	const [ isRevoking, setIsRevoking ] = useState< boolean >( false );
	const [ isRevoked, setIsRevoked ] = useState< boolean >( false );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const isMountedRef = useRef( true );

	useEffect( () => {
		isMountedRef.current = true;
		return () => {
			isMountedRef.current = false;
		};
	}, [] );

	const revoke = useCallback( async ( apUuid: string ) => {
		if ( ! apUuid ) {
			return;
		}

		setIsRevoking( true );
		setErrorMessage( null );

		try {
			await apiFetch( {
				path: `${ WC_ADMIN_NAMESPACE }/mobile-app/qr-login-revoke`,
				method: 'DELETE',
				data: { uuid: apUuid },
			} );

			if ( ! isMountedRef.current ) {
				return;
			}

			setIsRevoked( true );
		} catch ( error: unknown ) {
			if ( ! isMountedRef.current ) {
				return;
			}

			const err = error as {
				code?: string;
				message?: string;
				data?: { status?: number };
			};

			// Same rate-limit detection as useQRLoginToken: our own
			// `rate_limit_exceeded`, the upstream edge limiter's HTML 429
			// (apiFetch reports as `invalid_json`), or any explicit 429
			// status. All three resolve to the same merchant-facing message.
			const httpStatus = err.data?.status;
			const isRateLimited =
				err.code === 'rate_limit_exceeded' ||
				err.code === 'invalid_json' ||
				httpStatus === 429;

			if ( isRateLimited ) {
				setErrorMessage(
					__(
						"You're sending requests too quickly. Please wait a moment and try again.",
						'woocommerce'
					)
				);
				return;
			}

			setErrorMessage(
				err.message ||
					__(
						'Failed to revoke access. Please try again or remove the application password manually under Users → Profile.',
						'woocommerce'
					)
			);
		} finally {
			if ( isMountedRef.current ) {
				setIsRevoking( false );
			}
		}
	}, [] );

	return {
		revoke,
		isRevoking,
		isRevoked,
		errorMessage,
	};
};
