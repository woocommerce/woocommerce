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

type UseQRLoginTokenOptions = {
	onReady?: () => void;
	onError?: ( errorMessage: string ) => void;
};

export const useQRLoginToken = ( {
	onReady,
	onError,
}: UseQRLoginTokenOptions = {} ) => {
	const [ state, setState ] = useState< QRLoginTokenState >(
		QRLoginTokenStates.IDLE
	);
	const [ qrUrl, setQrUrl ] = useState< string | null >( null );
	const [ secondsRemaining, setSecondsRemaining ] = useState< number >( 0 );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const timerRef = useRef< ReturnType< typeof setInterval > | null >( null );
	const expiresAtRef = useRef< number >( 0 );
	const onReadyRef = useRef( onReady );
	const onErrorRef = useRef( onError );

	onReadyRef.current = onReady;
	onErrorRef.current = onError;

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

			if (
				! response ||
				typeof response.qr_url !== 'string' ||
				response.qr_url.length === 0 ||
				! Number.isFinite( response.expires_at ) ||
				response.expires_at <= Date.now() / 1000
			) {
				throw new Error(
					__(
						'Failed to generate QR login code. Please try again.',
						'woocommerce'
					)
				);
			}

			setQrUrl( response.qr_url );
			setState( QRLoginTokenStates.READY );
			startCountdown( response.expires_at );
			onReadyRef.current?.();
		} catch ( error: unknown ) {
			const err = error as { code?: string; message?: string };
			let nextErrorMessage: string;

			if ( err.code === 'rate_limit_exceeded' ) {
				nextErrorMessage = __(
					'Too many requests. Please try again in a few minutes.',
					'woocommerce'
				);
			} else if ( err.code === 'ssl_required' ) {
				nextErrorMessage = __(
					'QR login requires an HTTPS connection.',
					'woocommerce'
				);
			} else {
				nextErrorMessage =
					err.message ||
					__(
						'Failed to generate QR login code. Please try again.',
						'woocommerce'
					);
			}

			setErrorMessage( nextErrorMessage );
			setState( QRLoginTokenStates.ERROR );
			onErrorRef.current?.( nextErrorMessage );
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
