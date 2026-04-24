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
	onError?: ( errorCode: string ) => void;
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
	const isMountedRef = useRef( true );
	const requestIdRef = useRef( 0 );

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
				if ( ! isMountedRef.current ) {
					return;
				}

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
		const requestId = requestIdRef.current + 1;
		requestIdRef.current = requestId;

		clearTimer();
		expiresAtRef.current = 0;
		setQrUrl( null );
		setSecondsRemaining( 0 );
		setState( QRLoginTokenStates.LOADING );
		setErrorMessage( null );

		try {
			const response = await apiFetch< QRLoginTokenResponse >( {
				path: `${ WC_ADMIN_NAMESPACE }/mobile-app/qr-login-token`,
				method: 'POST',
			} );

			if (
				! isMountedRef.current ||
				requestId !== requestIdRef.current
			) {
				return;
			}

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
			if (
				! isMountedRef.current ||
				requestId !== requestIdRef.current
			) {
				return;
			}

			clearTimer();
			expiresAtRef.current = 0;
			setQrUrl( null );
			setSecondsRemaining( 0 );

			const err = error as { code?: string; message?: string };
			const errorCode = err.code || 'unknown_error';
			let nextErrorMessage: string;

			switch ( errorCode ) {
				case 'woocommerce_rest_cannot_view':
					// The endpoint requires the `manage_woocommerce`
					// capability; surface a clear, actionable message
					// rather than the generic REST wording.
					nextErrorMessage = __(
						'You do not have permission to generate a QR login code. Ask a site administrator for help.',
						'woocommerce'
					);
					break;
				case 'ssl_required':
					nextErrorMessage = __(
						'QR login requires an HTTPS connection.',
						'woocommerce'
					);
					break;
				case 'application_passwords_unavailable':
					nextErrorMessage = __(
						'Application passwords are disabled on this site, so QR login is unavailable. Ask a site administrator to enable them.',
						'woocommerce'
					);
					break;
				case 'rate_limit_exceeded':
					nextErrorMessage = __(
						'Too many QR login requests. Please try again in a few minutes.',
						'woocommerce'
					);
					break;
				default:
					nextErrorMessage =
						err.message ||
						__(
							'Failed to generate QR login code. Please try again.',
							'woocommerce'
						);
			}

			setErrorMessage( nextErrorMessage );
			setState( QRLoginTokenStates.ERROR );
			onErrorRef.current?.( errorCode );
		}
	}, [ clearTimer, startCountdown ] );

	// Cleanup timer on unmount.
	useEffect( () => {
		isMountedRef.current = true;

		return () => {
			isMountedRef.current = false;
			requestIdRef.current += 1;
			clearTimer();
		};
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
