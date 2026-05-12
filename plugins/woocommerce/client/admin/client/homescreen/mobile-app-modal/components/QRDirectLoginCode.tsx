/**
 * External dependencies
 */
import { QRCodeSVG } from 'qrcode.react';
import React, { useEffect, useRef } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { useQRLoginToken, QRLoginTokenStates } from './useQRLoginToken';
import { useQRLoginAvailability } from './useQRLoginAvailability';
import { QRLoginConsumedPanel } from './QRLoginConsumedPanel';
import { QRLoginRevokedPanel } from './QRLoginRevokedPanel';
import { QRLoginNumberMatchStep } from './QRLoginNumberMatchStep';
import { QRLoginUnavailableCard } from './QRLoginUnavailableCard';

/**
 * Deep-link into wp-admin's Application Passwords section. Reused by the
 * ERROR-state action when the failure is `application_passwords_unavailable`.
 */
const APPLICATION_PASSWORDS_SETTINGS_PATH =
	'/wp-admin/profile.php#application-passwords-section';

/**
 * Snapshot the parent receives via `onConsumed`. The success step uses its
 * own `useRevokeQRLoginAccess` hook (so it stays self-contained after the QR
 * component is unmounted), so the only field the parent actually needs is the
 * AP UUID to drive the revoke CTA.
 */
export type QRLoginConsumedSnapshot = {
	apUuid: string | null;
};

type QRDirectLoginCodeProps = {
	/**
	 * Optional callback invoked when the merchant clicks "Done" on the
	 * consumed/revoked panels. Surfaces are free to no-op (e.g. the standalone
	 * page) or close themselves (e.g. the homescreen modal).
	 */
	onDone?: () => void;
	/**
	 * Fires once the internal token state transitions to CONSUMED. Used by
	 * the homescreen modal stepper to advance to its third step. Standalone
	 * surfaces can leave this prop unset and the inline `QRLoginConsumedPanel`
	 * keeps its existing behavior.
	 */
	onConsumed?: ( snapshot: QRLoginConsumedSnapshot ) => void;
	/**
	 * When `true`, the component returns `null` for the CONSUMED and REVOKED
	 * states so the parent surface can render its own confirmation UI. Used
	 * by the stepper, which renders the third-step success panel itself.
	 * Default `false` preserves the existing inline-panel rendering for the
	 * standalone `/mobile-app-login` page.
	 */
	suppressInlinePanels?: boolean;
};

export const QRDirectLoginCode = ( {
	onDone,
	onConsumed,
	suppressInlinePanels = false,
}: QRDirectLoginCodeProps ) => {
	// Tracks whether _displayed has already fired for this mount so that
	// subsequent successful refreshes (which re-enter the READY state) only
	// emit _refreshed and don't over-count first-displays in the funnel.
	const displayedTrackedRef = useRef( false );
	const availability = useQRLoginAvailability();
	const {
		state,
		qrUrl,
		secondsRemaining,
		errorMessage,
		errorCode,
		deviceInfo,
		apUuid,
		candidateNumbers,
		challengeExpiresAt,
		chooseNumber,
		fetchToken,
		refreshToken,
		revoke,
	} = useQRLoginToken( {
		onReady: () => {
			if ( displayedTrackedRef.current ) {
				return;
			}
			displayedTrackedRef.current = true;
			recordEvent( 'mobile_app_qr_direct_login_displayed' );
		},
		onError: ( nextErrorCode ) => {
			recordEvent( 'mobile_app_qr_direct_login_failed', {
				error_code: nextErrorCode,
			} );
		},
	} );

	useEffect( () => {
		// Don't even attempt to mint a token until we've heard back from
		// `/qr-login-availability`. If the feature is unavailable, never
		// fetch — `<QRLoginUnavailableCard />` owns the rendered state.
		if ( availability.isLoading || ! availability.available ) {
			return;
		}
		fetchToken();
	}, [ availability.isLoading, availability.available, fetchToken ] );

	// Bubble the consumed snapshot up to the parent so it can advance its
	// own stepper to the third step. Standalone surfaces don't pass
	// `onConsumed` and keep using the inline `QRLoginConsumedPanel`.
	useEffect( () => {
		if ( state === QRLoginTokenStates.CONSUMED && onConsumed ) {
			onConsumed( { apUuid } );
		}
	}, [ state, apUuid, onConsumed ] );

	const formatTime = ( seconds: number ) => {
		const mins = Math.floor( seconds / 60 );
		const secs = seconds % 60;
		return `${ mins }:${ secs.toString().padStart( 2, '0' ) }`;
	};

	// Up-front availability gate — render a brief loading state while the
	// /qr-login-availability probe resolves, then either the disabled card
	// (terminal) or fall through to the normal state machine below.
	if ( availability.isLoading ) {
		return (
			<div className="woocommerce-qr-direct-login">
				<Spinner />
				<p role="status" aria-live="polite">
					{ __( 'Checking sign-in availability…', 'woocommerce' ) }
				</p>
			</div>
		);
	}

	if ( ! availability.available ) {
		return <QRLoginUnavailableCard reason={ availability.reason } />;
	}

	if ( state === QRLoginTokenStates.LOADING ) {
		return (
			<div className="woocommerce-qr-direct-login">
				<Spinner />
				<p role="status" aria-live="polite">
					{ __( 'Generating secure login code…', 'woocommerce' ) }
				</p>
			</div>
		);
	}

	if ( state === QRLoginTokenStates.ERROR ) {
		// "Try again" only makes sense when retrying could succeed. For
		// `application_passwords_unavailable` the failure is structural —
		// nothing about retrying changes the site's AP configuration — so
		// swap in a one-click shortcut to wp-admin's Application Passwords
		// settings instead.
		const isStructuralAPFailure =
			errorCode === 'application_passwords_unavailable';

		return (
			<div className="woocommerce-qr-direct-login">
				<p
					className="woocommerce-qr-direct-login__error"
					role="status"
					aria-live="polite"
				>
					{ errorMessage }
				</p>
				{ isStructuralAPFailure ? (
					<Button
						variant="secondary"
						className="woocommerce-qr-direct-login__open-ap-settings"
						href={ APPLICATION_PASSWORDS_SETTINGS_PATH }
						onClick={ () => {
							recordEvent(
								'mobile_app_qr_direct_login_open_ap_settings',
								{ reason: 'error_state' }
							);
						} }
					>
						{ __(
							'Open Application Passwords settings',
							'woocommerce'
						) }
					</Button>
				) : (
					<Button
						variant="secondary"
						onClick={ () => {
							recordEvent(
								'mobile_app_qr_direct_login_refreshed'
							);
							refreshToken();
						} }
					>
						{ __( 'Try again', 'woocommerce' ) }
					</Button>
				) }
			</div>
		);
	}

	if ( state === QRLoginTokenStates.EXPIRED ) {
		return (
			<div className="woocommerce-qr-direct-login">
				<p role="status" aria-live="polite">
					{ __( 'The login code has expired.', 'woocommerce' ) }
				</p>
				<Button
					variant="secondary"
					onClick={ () => {
						recordEvent( 'mobile_app_qr_direct_login_refreshed' );
						refreshToken();
					} }
				>
					{ __( 'Generate new code', 'woocommerce' ) }
				</Button>
			</div>
		);
	}

	// Task 7 — number-matching states.
	if ( state === QRLoginTokenStates.SCANNED && candidateNumbers ) {
		return (
			<QRLoginNumberMatchStep
				numbers={ candidateNumbers }
				deviceInfo={ deviceInfo }
				challengeExpiresAt={ challengeExpiresAt }
				onChooseNumber={ chooseNumber }
				errorMessage={ errorMessage }
			/>
		);
	}

	if ( state === QRLoginTokenStates.APPROVED ) {
		return (
			<div
				className="woocommerce-qr-direct-login woocommerce-qr-direct-login--approved"
				role="status"
				aria-live="polite"
			>
				<Spinner />
				<p>
					{ __(
						'Confirmed. Finishing sign-in on your phone…',
						'woocommerce'
					) }
				</p>
			</div>
		);
	}

	if ( state === QRLoginTokenStates.REJECTED ) {
		return (
			<div
				className="woocommerce-qr-direct-login woocommerce-qr-direct-login--rejected"
				role="alert"
			>
				<p>
					{ __(
						'Sign-in denied. For your security, this attempt has been cancelled.',
						'woocommerce'
					) }
				</p>
				<Button
					variant="secondary"
					onClick={ () => {
						recordEvent( 'mobile_app_qr_direct_login_refreshed' );
						refreshToken();
					} }
				>
					{ __( 'Start over', 'woocommerce' ) }
				</Button>
			</div>
		);
	}

	if ( state === QRLoginTokenStates.CONSUMED ) {
		if ( suppressInlinePanels ) {
			return null;
		}
		return (
			<QRLoginConsumedPanel
				deviceInfo={ deviceInfo }
				onRevoke={ revoke }
				onDone={ onDone }
				errorMessage={ errorMessage }
			/>
		);
	}

	if ( state === QRLoginTokenStates.REVOKED ) {
		if ( suppressInlinePanels ) {
			return null;
		}
		return <QRLoginRevokedPanel onDone={ onDone } />;
	}

	if ( state === QRLoginTokenStates.READY && qrUrl ) {
		return (
			<div className="woocommerce-qr-direct-login woocommerce-qr-direct-login--ready">
				<div className="woocommerce-qr-direct-login__qr">
					<QRCodeSVG value={ qrUrl } size={ 140 } />
				</div>
				<div className="woocommerce-qr-direct-login__meta">
					{ /* Countdown stays outside any live region so screen
					     readers don't re-announce it every second. */ }
					<p
						className="woocommerce-qr-direct-login__timer"
						aria-live="off"
					>
						{ sprintf(
							/* translators: %s: time remaining in M:SS format */
							__( 'Code expires in %s', 'woocommerce' ),
							formatTime( secondsRemaining )
						) }
					</p>
					{ /*
					   Persistent renew button — always visible while a code is
					   on screen. Lets a merchant who tabbed away mint a fresh
					   code without waiting for the 5-min countdown to finish.
					*/ }
					<Button
						variant="link"
						className="woocommerce-qr-direct-login__renew"
						onClick={ () => {
							recordEvent( 'mobile_app_qr_direct_login_renewed' );
							refreshToken();
						} }
					>
						{ __( 'Renew code', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		);
	}

	return null;
};
