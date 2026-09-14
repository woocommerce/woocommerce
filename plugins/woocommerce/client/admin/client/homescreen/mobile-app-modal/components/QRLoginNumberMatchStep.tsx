/**
 * External dependencies
 */
import React, { useEffect, useMemo, useState } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import type { QRLoginDeviceInfo } from './useQRLoginToken';
import { buildQRLoginDeviceLine } from './qrLoginDeviceCopy';

type QRLoginNumberMatchStepProps = {
	/**
	 * Shuffled candidate triple as returned by /qr-login-status while the
	 * underlying token is in the SCANNED state. The wc-admin client never
	 * knows which one is the real one — server compares constant-time.
	 */
	numbers: [ string, string, string ];
	/**
	 * Device info reported by the mobile app on /qr-login-scan. Surfaced so
	 * the merchant can spot a wrong-device scan before approving.
	 */
	deviceInfo: QRLoginDeviceInfo | null;
	/**
	 * Unix-seconds timestamp at which the challenge expires (90s after scan).
	 * Drives the in-step countdown and disables the tiles when it hits zero.
	 */
	challengeExpiresAt: number;
	/**
	 * Submit a number choice — server will return approved or rejected. Empty
	 * string is the explicit cancel sentinel from the "It wasn't me" link.
	 */
	onChooseNumber: ( choice: string ) => Promise< void > | void;
	/**
	 * Optional error surfaced after an approval request fails without a state
	 * transition.
	 */
	errorMessage?: ReactNode | null;
};

/**
 * Number-match approval step — Microsoft Authenticator-style coincidence
 * verification. The mobile app is showing the merchant a 3-digit number; we
 * render that same number plus two distractors (server-shuffled). The
 * merchant has to tap the matching one.
 *
 * One strike: a wrong tap (or the "It wasn't me" cancel link) terminates the
 * session permanently. There is no retry — that's the entire point of the
 * security guarantee. A 1-in-3 brute-force is not viable when paired with the
 * single-attempt rule and the 90-second window.
 *
 * All three tiles are disabled while a click is in flight. We don't want a
 * fast double-click registering as two attempts — that would race the state
 * transition on the server side and pessimize the UX.
 */
export const QRLoginNumberMatchStep = ( {
	numbers,
	deviceInfo,
	challengeExpiresAt,
	onChooseNumber,
	errorMessage = null,
}: QRLoginNumberMatchStepProps ) => {
	const [ inFlight, setInFlight ] = useState( false );
	// Tracks which choice is currently being submitted so we can render the
	// busy state only on the tapped tile (or the cancel link). Empty-string
	// sentinel matches the cancel path; null means nothing is in flight.
	const [ pendingChoice, setPendingChoice ] = useState< string | null >(
		null
	);
	const [ secondsRemaining, setSecondsRemaining ] = useState< number >( () =>
		Math.max( 0, Math.floor( challengeExpiresAt - Date.now() / 1000 ) )
	);

	// Local countdown that mirrors the server's challenge expiry. Driven off
	// challengeExpiresAt rather than a duration prop so it stays in sync if
	// the parent re-mounts mid-window.
	useEffect( () => {
		const tick = () => {
			setSecondsRemaining(
				Math.max(
					0,
					Math.floor( challengeExpiresAt - Date.now() / 1000 )
				)
			);
		};
		tick();
		const id = setInterval( tick, 1000 );
		return () => clearInterval( id );
	}, [ challengeExpiresAt ] );

	useEffect( () => {
		recordEvent( 'mobile_app_qr_login_number_match_displayed' );
	}, [] );

	const deviceLine = useMemo(
		() => buildQRLoginDeviceLine( deviceInfo ),
		[ deviceInfo ]
	);

	const expired = secondsRemaining <= 0;
	const tilesDisabled = inFlight || expired;

	const handleChoose = async ( choice: string ) => {
		if ( tilesDisabled ) {
			return;
		}

		setInFlight( true );
		setPendingChoice( choice );
		recordEvent( 'mobile_app_qr_login_number_match_chosen' );

		try {
			await onChooseNumber( choice );
		} finally {
			// Note: even on a wrong pick the parent flips state to REJECTED
			// and unmounts this component, so resetting `inFlight` is mostly
			// defensive — it matters only if the request errors out without
			// a state transition.
			setInFlight( false );
			setPendingChoice( null );
		}
	};

	const headline = sprintf(
		/* translators: %s: device summary, e.g. "Pixel 10 · Android 16 · App version 24.6". */
		__( 'Match this number on %s', 'woocommerce' ),
		deviceLine
	);

	return (
		<div
			className="woocommerce-qr-direct-login woocommerce-qr-direct-login--number-match"
			role="group"
			aria-label={ __( 'Confirm sign-in', 'woocommerce' ) }
		>
			<p className="woocommerce-qr-direct-login__match-headline">
				{ headline }
			</p>
			<p className="woocommerce-qr-direct-login__match-description">
				{ __(
					'Tap the number that matches what you see on your phone.',
					'woocommerce'
				) }
			</p>

			<div
				className="woocommerce-qr-direct-login__number-tiles"
				role="group"
				aria-label={ __( 'Number-match candidates', 'woocommerce' ) }
			>
				{ numbers.map( ( candidate, index ) => {
					// Only the tile that was tapped shows the busy state.
					// the other two stay disabled but un-spinnered so the
					// merchant can see which one is being submitted.
					const isThisTilePending =
						inFlight && pendingChoice === candidate;
					return (
						<Button
							key={ `${ candidate }-${ index }` }
							variant="secondary"
							className="woocommerce-qr-direct-login__number-tile"
							disabled={ tilesDisabled }
							aria-disabled={ tilesDisabled }
							isBusy={ isThisTilePending }
							aria-label={ sprintf(
								/* translators: %s: 3-digit candidate number. */
								__(
									'Confirm with the number %s',
									'woocommerce'
								),
								candidate
							) }
							onClick={ () => handleChoose( candidate ) }
						>
							{ candidate }
						</Button>
					);
				} ) }
			</div>

			<p
				className="woocommerce-qr-direct-login__match-countdown"
				aria-live={ expired ? 'polite' : 'off' }
			>
				{ expired
					? __( 'This sign-in attempt has expired.', 'woocommerce' )
					: sprintf(
							/* translators: %d: seconds remaining before the challenge expires. */
							__( 'Expires in %ds', 'woocommerce' ),
							secondsRemaining
					  ) }
			</p>

			{ errorMessage && (
				<p className="woocommerce-qr-direct-login__error" role="alert">
					{ errorMessage }
				</p>
			) }

			<div className="woocommerce-qr-direct-login__match-cancel-row">
				<p className="woocommerce-qr-direct-login__match-cancel-text">
					{ __( "I don't recognise this device", 'woocommerce' ) }
				</p>
				<Button
					variant="secondary"
					className="woocommerce-qr-direct-login__match-cancel-button"
					disabled={ tilesDisabled }
					onClick={ () => {
						recordEvent(
							'mobile_app_qr_login_number_match_cancelled'
						);
						// Empty string is treated by the server as a non-matching
						// pick — same one-strike rejection path as a wrong tap.
						void handleChoose( '' );
					} }
				>
					{ inFlight && pendingChoice === '' ? (
						<>
							<Spinner />
							<span>{ __( 'Cancelling…', 'woocommerce' ) }</span>
						</>
					) : (
						__( 'Cancel login', 'woocommerce' )
					) }
				</Button>
			</div>
		</div>
	);
};
