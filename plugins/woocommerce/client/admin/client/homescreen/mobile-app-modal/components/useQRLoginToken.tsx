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
	CONSUMED: 'consumed',
	REVOKED: 'revoked',
	ERROR: 'error',
} as const;

export type QRLoginTokenState =
	( typeof QRLoginTokenStates )[ keyof typeof QRLoginTokenStates ];

/**
 * Whitelisted device-info shape returned by the status endpoint after a
 * successful exchange. The mobile app fills this in on the exchange request;
 * older clients omit it, in which case all fields are absent and the UI falls
 * back to generic copy. Mirrors `MobileAppQRLogin::DEVICE_PAYLOAD_KEYS`.
 *
 * `brand` is Android-only (`Build.BRAND`); iOS clients leave it absent.
 */
export type QRLoginDeviceInfo = {
	os?: string;
	os_version?: string;
	model?: string;
	brand?: string;
	app_version?: string;
};

type QRLoginTokenResponse = {
	qr_url: string;
	expires_at: number;
	ttl: number;
};

type QRLoginStatusResponse =
	| { status: 'pending'; expires_at: number }
	| {
			status: 'consumed';
			consumed_at: number;
			ap_uuid: string;
			ap_name: string | null;
			device: QRLoginDeviceInfo;
	  }
	| { status: 'expired' };

/**
 * Polling cadence for the status endpoint while the QR is on screen. ~2.5s
 * gives the merchant near-instant feedback after scanning without hammering
 * the backend; the per-user rate limit on the status endpoint allows ~24/min.
 */
const STATUS_POLL_INTERVAL_MS = 2500;

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
	const [ deviceInfo, setDeviceInfo ] = useState< QRLoginDeviceInfo | null >(
		null
	);
	const [ apUuid, setApUuid ] = useState< string | null >( null );
	const timerRef = useRef< ReturnType< typeof setInterval > | null >( null );
	// Plaintext token kept in a ref (not state) so it never causes re-renders
	// and never leaves the hook closure. The status-polling and revoke calls
	// need it server-side; the consumer only ever sees state transitions.
	const tokenRef = useRef< string | null >( null );
	const pollTimerRef = useRef< ReturnType< typeof setInterval > | null >(
		null
	);
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

	const clearPollTimer = useCallback( () => {
		if ( pollTimerRef.current ) {
			clearInterval( pollTimerRef.current );
			pollTimerRef.current = null;
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
					clearPollTimer();
					setState( QRLoginTokenStates.EXPIRED );
					setQrUrl( null );
					tokenRef.current = null;
				}
			};

			updateRemaining();
			timerRef.current = setInterval( updateRemaining, 1000 );
		},
		[ clearTimer, clearPollTimer ]
	);

	/**
	 * Extract the plaintext token from the deep link returned by
	 * `qr-login-token`. Robust against future query-string ordering changes.
	 * Returns `null` if the URL doesn't carry a token (defensive).
	 */
	const extractTokenFromQrUrl = ( deepLink: string ): string | null => {
		const queryStart = deepLink.indexOf( '?' );
		if ( queryStart === -1 ) {
			return null;
		}
		const params = new URLSearchParams( deepLink.slice( queryStart + 1 ) );
		const token = params.get( 'token' );
		return token ? token : null;
	};

	/**
	 * Poll the status endpoint while the QR is on screen, stop as soon as the
	 * server reports the token has been consumed (mobile app exchanged it for
	 * an Application Password) so the UI can transition to the confirmation
	 * panel within a couple of seconds of the user scanning. Called from the
	 * effect below when state flips to READY and a plaintext token is in scope.
	 */
	const pollStatus = useCallback( async () => {
		const token = tokenRef.current;
		if ( ! token || ! isMountedRef.current ) {
			return;
		}

		try {
			const response = await apiFetch< QRLoginStatusResponse >( {
				path: `${ WC_ADMIN_NAMESPACE }/mobile-app/qr-login-status`,
				method: 'POST',
				data: { token },
			} );

			if ( ! isMountedRef.current ) {
				return;
			}

			if ( response.status === 'consumed' ) {
				clearTimer();
				clearPollTimer();
				setQrUrl( null );
				setApUuid( response.ap_uuid );
				setDeviceInfo( response.device || null );
				setState( QRLoginTokenStates.CONSUMED );
				tokenRef.current = null;
			}
			// `pending` and `expired` are no-ops here — the countdown timer
			// drives the EXPIRED transition; we just keep polling on `pending`.
		} catch {
			// Swallow polling errors. A transient 500/429 should not break the
			// QR flow — the next tick will retry, and the countdown will
			// eventually push us to EXPIRED.
		}
	}, [ clearTimer, clearPollTimer ] );

	const startStatusPolling = useCallback( () => {
		clearPollTimer();
		// First tick fires immediately so a fast-scanning merchant gets the
		// confirmation panel without waiting a full interval.
		pollStatus();
		pollTimerRef.current = setInterval(
			pollStatus,
			STATUS_POLL_INTERVAL_MS
		);
	}, [ clearPollTimer, pollStatus ] );

	const fetchToken = useCallback( async () => {
		const requestId = requestIdRef.current + 1;
		requestIdRef.current = requestId;

		clearTimer();
		clearPollTimer();
		tokenRef.current = null;
		setApUuid( null );
		setDeviceInfo( null );
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

			tokenRef.current = extractTokenFromQrUrl( response.qr_url );
			setQrUrl( response.qr_url );
			setState( QRLoginTokenStates.READY );
			startCountdown( response.expires_at );
			onReadyRef.current?.();
			// Kick off the poll loop only once we actually have a token to
			// poll for. If the URL was malformed and we couldn't extract one,
			// the QR still renders, we just won't transition to CONSUMED
			// until the next refresh.
			if ( tokenRef.current ) {
				startStatusPolling();
			}
		} catch ( error: unknown ) {
			if (
				! isMountedRef.current ||
				requestId !== requestIdRef.current
			) {
				return;
			}

			clearTimer();
			clearPollTimer();
			tokenRef.current = null;
			expiresAtRef.current = 0;
			setQrUrl( null );
			setSecondsRemaining( 0 );

			const err = error as {
				code?: string;
				message?: string;
				data?: { status?: number };
			};
			const nextErrorCode = err.code ?? null;
			let nextErrorMessage: ReactNode;

			// Edge rate-limiters (Cloudflare, VIP, etc.) return an HTML 429
			// page, and apiFetch surfaces that as `invalid_json`. Treat our
			// own code, an explicit HTTP 429, and that parse failure as the
			// same merchant-facing "wait a moment" state.
			const httpStatus = err.data?.status;
			const isRateLimited =
				nextErrorCode === 'rate_limit_exceeded' ||
				nextErrorCode === 'invalid_json' ||
				httpStatus === 429;

			if ( isRateLimited ) {
				nextErrorMessage = __(
					"You've requested QR login codes too quickly. Please wait a moment and try again.",
					'woocommerce'
				);
			} else {
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
					default:
						nextErrorMessage =
							err.message ||
							__(
								'Failed to generate QR login code. Please try again.',
								'woocommerce'
							);
				}
			}

			setErrorCode( nextErrorCode );
			setErrorMessage( nextErrorMessage );
			setState( QRLoginTokenStates.ERROR );
			onErrorRef.current?.( nextErrorCode ?? 'unknown_error' );
		}
	}, [ clearTimer, clearPollTimer, startCountdown, startStatusPolling ] );

	/**
	 * Revoke the Application Password issued by the most recent successful
	 * exchange. Only meaningful when state === CONSUMED — earlier states
	 * have no AP yet, REVOKED is a no-op, and EXPIRED means the consumed
	 * record may already be gone from the server.
	 */
	const revoke = useCallback( async () => {
		if ( ! apUuid ) {
			return;
		}

		try {
			await apiFetch( {
				path: `${ WC_ADMIN_NAMESPACE }/mobile-app/qr-login-revoke`,
				method: 'DELETE',
				data: { uuid: apUuid },
			} );

			if ( ! isMountedRef.current ) {
				return;
			}

			setState( QRLoginTokenStates.REVOKED );
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
		}
	}, [ apUuid ] );

	// Cleanup timers + polling on unmount.
	useEffect( () => {
		isMountedRef.current = true;

		return () => {
			isMountedRef.current = false;
			requestIdRef.current += 1;
			tokenRef.current = null;
			clearTimer();
			clearPollTimer();
		};
	}, [ clearTimer, clearPollTimer ] );

	return {
		state,
		qrUrl,
		secondsRemaining,
		errorMessage,
		errorCode,
		deviceInfo,
		apUuid,
		fetchToken,
		refreshToken: fetchToken,
		revoke,
	};
};
