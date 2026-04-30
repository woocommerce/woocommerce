/**
 * External dependencies
 */
import { QRCodeSVG } from 'qrcode.react';
import React, { useEffect, useRef } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';
import interpolateComponents from '@automattic/interpolate-components';
import { recordEvent } from '@woocommerce/tracks';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { useQRLoginToken, QRLoginTokenStates } from './useQRLoginToken';

export const QRDirectLoginCode = () => {
	const {
		state,
		qrUrl,
		secondsRemaining,
		errorMessage,
		fetchToken,
		refreshToken,
	} = useQRLoginToken();

	useEffect( () => {
		fetchToken();
	}, [ fetchToken ] );

	// Fire the displayed event only once a QR code is actually shown, so
	// funnel analysis (display → scan → login) doesn't conflate users who
	// only ever saw LOADING/ERROR with users who saw a real code.
	const displayedTrackedRef = useRef( false );
	useEffect( () => {
		if (
			state === QRLoginTokenStates.READY &&
			! displayedTrackedRef.current
		) {
			displayedTrackedRef.current = true;
			recordEvent( 'mobile_app_qr_direct_login_displayed' );
		}
	}, [ state ] );

	const formatTime = ( seconds: number ) => {
		const mins = Math.floor( seconds / 60 );
		const secs = seconds % 60;
		return `${ mins }:${ secs.toString().padStart( 2, '0' ) }`;
	};

	if ( state === QRLoginTokenStates.LOADING ) {
		return (
			<div className="qr-direct-login">
				<Spinner />
				<p role="status" aria-live="polite">
					{ __( 'Generating secure login code…', 'woocommerce' ) }
				</p>
			</div>
		);
	}

	if ( state === QRLoginTokenStates.ERROR ) {
		return (
			<div className="qr-direct-login">
				<p
					className="qr-direct-login__error"
					role="status"
					aria-live="polite"
				>
					{ errorMessage }
				</p>
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
			</div>
		);
	}

	if ( state === QRLoginTokenStates.EXPIRED ) {
		return (
			<div className="qr-direct-login">
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

	if ( state === QRLoginTokenStates.READY && qrUrl ) {
		return (
			<div className="qr-direct-login">
				<QRCodeSVG value={ qrUrl } size={ 140 } />
				{ /* Countdown stays outside any live region so screen readers
				     don't re-announce it every second. */ }
				<p className="qr-direct-login__timer" aria-live="off">
					{ sprintf(
						/* translators: %s: time remaining in M:SS format */
						__( 'Code expires in %s', 'woocommerce' ),
						formatTime( secondsRemaining )
					) }
				</p>
				<p className="qr-direct-login__version-note">
					{ __(
						'The app version needs to be 15.7 or above to sign in with this link.',
						'woocommerce'
					) }
				</p>
				<div>
					{ interpolateComponents( {
						mixedString: __(
							'Any troubles signing in? Check out the {{link}}FAQ{{/link}}.',
							'woocommerce'
						),
						components: {
							link: (
								<Link
									href="https://woocommerce.com/document/android-ios-apps-login-help-faq/"
									target="_blank"
									type="external"
									onClick={ () => {
										recordEvent(
											'onboarding_app_login_faq_click'
										);
									} }
								/>
							),
							strong: <strong />,
						},
					} ) }
				</div>
			</div>
		);
	}

	return null;
};
