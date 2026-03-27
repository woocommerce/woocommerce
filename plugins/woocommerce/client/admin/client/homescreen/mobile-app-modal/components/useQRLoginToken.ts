/**
 * External dependencies
 */
import { useState, useCallback, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { WC_ADMIN_NAMESPACE } from '@woocommerce/data';
import apiFetch from '@wordpress/api-fetch';

export const QRLoginTokenStates = {
	IDLE: 'idle',
	LOADING: 'loading',
	READY: 'ready',
	EXPIRED: 'expired',
	ERROR: 'error',
} as const;

export type QRLoginTokenState =
	( typeof QRLoginTokenStates )[ keyof typeof QRLoginTokenStates ];

type QRLoginTokenResponse = {
	qr_url: string;
	expires_at: number;
	ttl: number;
};

export const useQRLoginToken = () => {
	const [ state, setState ] = useState< QRLoginTokenState >(
		QRLoginTokenStates.IDLE
	);
	const [ qrUrl, setQrUrl ] = useState< string | null >( null );
	const [ secondsRemaining, setSecondsRemaining ] = useState< number >( 0 );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const timerRef = useRef< ReturnType< typeof setInterval > | null >( null );
	const expiresAtRef = useRef< number >( 0 );

	const clearTimer = useCallback( () => {
		if ( timerRef.current ) {
			clearInterval( timerRef.current );
			timerRef.current = null;
		}
	}, [] );

	const startCountdown = useCallback(
		( expiresAt: number ) => {
			clearTimer();
			expiresAtRef.current = expiresAt;

			const updateRemaining = () => {
				const remaining = Math.max(
					0,
					Math.floor( expiresAtRef.current - Date.now() / 1000 )
				);
				setSecondsRemaining( remaining );

				if ( remaining <= 0 ) {
					clearTimer();
					setState( QRLoginTokenStates.EXPIRED );
					setQrUrl( null );
				}
			};

			updateRemaining();
			timerRef.current = setInterval( updateRemaining, 1000 );
		},
		[ clearTimer ]
	);

	const fetchToken = useCallback( async () => {
		setState( QRLoginTokenStates.LOADING );
		setErrorMessage( null );

		try {
			const response = await apiFetch< QRLoginTokenResponse >( {
				path: `${ WC_ADMIN_NAMESPACE }/mobile-app/qr-login-token`,
				method: 'POST',
			} );

			setQrUrl( response.qr_url );
			setState( QRLoginTokenStates.READY );
			startCountdown( response.expires_at );
		} catch ( error: unknown ) {
			setState( QRLoginTokenStates.ERROR );

			const err = error as { code?: string; message?: string };
			if ( err.code === 'wpcom_account_required' ) {
				setErrorMessage(
					__(
						'QR login is only available for WordPress.com connected accounts.',
						'woocommerce'
					)
				);
			} else if ( err.code === 'rate_limit_exceeded' ) {
				setErrorMessage(
					__(
						'Too many requests. Please try again in a few minutes.',
						'woocommerce'
					)
				);
			} else if ( err.code === 'ssl_required' ) {
				setErrorMessage(
					__(
						'QR login requires an HTTPS connection.',
						'woocommerce'
					)
				);
			} else {
				setErrorMessage(
					err.message ||
						__(
							'Failed to generate QR login code. Please try again.',
							'woocommerce'
						)
				);
			}
		}
	}, [ startCountdown ] );

	// Cleanup timer on unmount.
	useEffect( () => {
		return () => clearTimer();
	}, [ clearTimer ] );

	return {
		state,
		qrUrl,
		secondsRemaining,
		errorMessage,
		fetchToken,
		refreshToken: fetchToken,
	};
};
