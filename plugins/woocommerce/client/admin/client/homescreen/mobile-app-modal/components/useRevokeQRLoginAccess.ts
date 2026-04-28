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

			const err = error as { message?: string };
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
