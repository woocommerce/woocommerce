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
import {
	useQRLoginToken,
	QRLoginTokenStates,
	type QRLoginDeviceInfo,
} from './useQRLoginToken';
import { QRLoginConsumedPanel } from './QRLoginConsumedPanel';
import { QRLoginRevokedPanel } from './QRLoginRevokedPanel';

/**
 * Snapshot the parent receives via `onConsumed`. Just the fields the parent
 * needs to render the third stepper step — `revoke` is not exposed because
 * the stepper uses its own `useRevokeQRLoginAccess` hook to keep the success
 * step self-contained after the QR component is unmounted.
 */
export type QRLoginConsumedSnapshot = {
	deviceInfo: QRLoginDeviceInfo | null;
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

	const {
		state,
		qrUrl,
		secondsRemaining,
		errorMessage,
		deviceInfo,
		apUuid,
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
		onError: ( errorCode ) => {
			recordEvent( 'mobile_app_qr_direct_login_failed', {
				error_code: errorCode,
			} );
		},
	} );

	useEffect( () => {
		fetchToken();
	}, [ fetchToken ] );

	// Bubble the consumed snapshot up to the parent so it can advance its
	// own stepper to the third step. Standalone surfaces don't pass
	// `onConsumed` and keep using the inline `QRLoginConsumedPanel`.
	useEffect( () => {
		if ( state === QRLoginTokenStates.CONSUMED && onConsumed ) {
			onConsumed( { deviceInfo, apUuid } );
		}
	}, [ state, deviceInfo, apUuid, onConsumed ] );

	const formatTime = ( seconds: number ) => {
		const mins = Math.floor( seconds / 60 );
		const secs = seconds % 60;
		return `${ mins }:${ secs.toString().padStart( 2, '0' ) }`;
	};

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
		return (
			<div className="woocommerce-qr-direct-login">
				<p
					className="woocommerce-qr-direct-login__error"
					role="status"
					aria-live="polite"
				>
					{ errorMessage }
				</p>
				<Button
					variant="secondary"
					onClick={ () => {
						recordEvent( 'mobile_app_qr_direct_login_refreshed' );
						refreshToken();
					} }
				>
					{ __( 'Try again', 'woocommerce' ) }
				</Button>
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
