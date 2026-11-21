/**
 * External dependencies
 */
import React from '@wordpress/element';
import type { ReactNode } from 'react';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import type { QRLoginDeviceInfo } from './useQRLoginToken';
import {
	buildQRLoginDeviceHeadline,
	buildQRLoginDeviceSubline,
} from './qrLoginDeviceCopy';

type QRLoginConsumedPanelProps = {
	deviceInfo: QRLoginDeviceInfo | null;
	onRevoke: () => void;
	onDone?: () => void;
	errorMessage?: ReactNode | null;
};

/**
 * Confirmation panel shown in place of the QR code once the mobile app has
 * exchanged the token for an Application Password. Surfaces what device
 * signed in (so the merchant can spot a wrong-device scan) and offers an
 * "It wasn't you?" path that revokes the AP server-side.
 */
export const QRLoginConsumedPanel = ( {
	deviceInfo,
	onRevoke,
	onDone,
	errorMessage,
}: QRLoginConsumedPanelProps ) => {
	const headline = buildQRLoginDeviceHeadline( deviceInfo );
	const subline = buildQRLoginDeviceSubline( deviceInfo );

	return (
		<div
			className="woocommerce-qr-direct-login woocommerce-qr-direct-login--consumed"
			role="status"
			aria-live="polite"
		>
			<p className="woocommerce-qr-direct-login__consumed-headline">
				{ headline }
			</p>
			{ subline && (
				<p className="woocommerce-qr-direct-login__consumed-subline">
					{ subline }
				</p>
			) }

			{ errorMessage && (
				<p className="woocommerce-qr-direct-login__error" role="alert">
					{ errorMessage }
				</p>
			) }

			{ onDone && (
				<Button variant="primary" onClick={ onDone }>
					{ __( 'Done', 'woocommerce' ) }
				</Button>
			) }

			<Button
				variant="link"
				className="woocommerce-qr-direct-login__revoke"
				onClick={ () => {
					recordEvent( 'mobile_app_qr_direct_login_revoke_attempt' );
					onRevoke();
				} }
			>
				{ __( "It wasn't you? Revoke access", 'woocommerce' ) }
			</Button>
		</div>
	);
};
