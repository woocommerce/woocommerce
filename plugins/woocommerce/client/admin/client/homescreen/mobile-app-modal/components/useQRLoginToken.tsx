/**
 * External dependencies
 */
import {
	createInterpolateElement,
	useState,
	useCallback,
	useEffect,
	useRef,
} from '@wordpress/element';
import type { ReactNode } from 'react';
import { __ } from '@wordpress/i18n';
import { WC_ADMIN_NAMESPACE } from '@woocommerce/data';
import apiFetch from '@wordpress/api-fetch';
import { Link } from '@woocommerce/components';

/**
 * Documentation URL we link to when application passwords are unavailable.
 * Centralized so the constant can be reused (e.g. in tests or future
 * surfaces) and so the link is easy to update when the WP docs URL moves.
 */
const APPLICATION_PASSWORDS_DOCS_URL =
	'https://developer.wordpress.org/advanced-administration/security/application-passwords/';

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
	// `errorMessage` is rendered directly by `<QRDirectLoginCode />`. It is a
	// `ReactNode` (not just `string`) so individual cases can inject inline
	// links, for example the `application_passwords_unavailable` branch wraps a
	// "Learn more" link in the message itself.
	const [ errorMessage, setErrorMessage ] = useState< ReactNode | null >(
		null
	);
	// `errorCode` mirrors the REST error code that triggered the message,
	// exposed alongside `errorMessage` so callers (e.g. analytics) can
	// reliably reference the failure mode regardless of how the message was
	// rendered.
	const [ errorCode, setErrorCode ] = useState< string | null >( null );
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
		setErrorCode( null );

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
			const nextErrorCode = err.code ?? null;
			let nextErrorMessage: ReactNode;

			switch ( nextErrorCode ) {
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
					nextErrorMessage = createInterpolateElement(
						__(
							'Application passwords are disabled on this site, so QR login is unavailable. Find more about application passwords <link>here</link>.',
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

			setErrorCode( nextErrorCode );
			setErrorMessage( nextErrorMessage );
			setState( QRLoginTokenStates.ERROR );
			onErrorRef.current?.( nextErrorCode ?? 'unknown_error' );
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
		errorCode,
		fetchToken,
		refreshToken: fetchToken,
	};
};
