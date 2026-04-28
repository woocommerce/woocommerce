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
import { QRLoginConsumedPanel } from './QRLoginConsumedPanel';
import { QRLoginRevokedPanel } from './QRLoginRevokedPanel';

type QRDirectLoginCodeProps = {
	/**
	 * Optional callback invoked when the merchant clicks "Done" on the
	 * consumed/revoked panels. Surfaces are free to no-op (e.g. the standalone
	 * page) or close themselves (e.g. the homescreen modal).
	 */
	onDone?: () => void;
};

export const QRDirectLoginCode = ( {
	onDone,
}: QRDirectLoginCodeProps = {} ) => {
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
		return (
			<QRLoginConsumedPanel
				deviceInfo={ deviceInfo }
				onRevoke={ revoke }
				onDone={ onDone }
			/>
		);
	}

	if ( state === QRLoginTokenStates.REVOKED ) {
		return <QRLoginRevokedPanel onDone={ onDone } />;
	}

	if ( state === QRLoginTokenStates.READY && qrUrl ) {
		return (
			<div className="woocommerce-qr-direct-login">
				<QRCodeSVG value={ qrUrl } size={ 140 } />
				{ /* Countdown stays outside any live region so screen readers
				     don't re-announce it every second. */ }
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
				   Persistent renew button — always visible while a code is on
				   screen. Lets a merchant who tabbed away mint a fresh code
				   without waiting for the 5-min countdown to finish.
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
		);
	}

	return null;
};
