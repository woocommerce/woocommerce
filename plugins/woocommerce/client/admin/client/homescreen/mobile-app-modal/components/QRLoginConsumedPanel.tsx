/**
 * External dependencies
 */
import React from '@wordpress/element';
import { Button } from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import type { QRLoginDeviceInfo } from './useQRLoginToken';

type QRLoginConsumedPanelProps = {
	deviceInfo: QRLoginDeviceInfo | null;
	onRevoke: () => void;
	onDone?: () => void;
};

/**
 * Build the headline shown after a successful sign-in. Prefers the device
 * model when the mobile app sent one; falls back to the OS, then to a
 * device-agnostic line for older mobile clients that don't yet send a
 * `device` payload.
 */
const buildHeadline = ( device: QRLoginDeviceInfo | null ): string => {
	const model = device?.model?.trim();
	if ( model ) {
		return sprintf(
			/* translators: %s: device model, e.g. "iPhone 15". */
			__( 'Signed in successfully on %s', 'woocommerce' ),
			model
		);
	}

	const os = device?.os?.trim();
	if ( os ) {
		return sprintf(
			/* translators: %s: OS name, e.g. "iOS" or "Android". */
			__( 'Signed in successfully on %s', 'woocommerce' ),
			os
		);
	}

	return __( 'Signed in successfully', 'woocommerce' );
};

/**
 * Build a one-line subline summarizing the device/app the merchant signed in
 * with. Skips any field the mobile app didn't send so we never render
  " · undefined" garbage.
 */
const buildSubline = ( device: QRLoginDeviceInfo | null ): string => {
	if ( ! device ) {
		return '';
	}

	const parts: string[] = [];

	if ( device.os ) {
		parts.push(
			device.os_version
				? `${ device.os } ${ device.os_version }`
				: device.os
		);
	}

	if ( device.app_version ) {
		parts.push(
			sprintf(
				/* translators: %s: mobile app version, e.g. "24.7.0". */
				__( 'App version %s', 'woocommerce' ),
				device.app_version
			)
		);
	}

	return parts.join( ' · ' );
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
}: QRLoginConsumedPanelProps ) => {
	const headline = buildHeadline( deviceInfo );
	const subline = buildSubline( deviceInfo );

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

			{ onDone && (
				<Button variant="primary" onClick={ onDone }>
					{ __( 'Done', 'woocommerce' ) }
				</Button>
			) }

			<Button
				variant="link"
				className="woocommerce-qr-direct-login__revoke"
				onClick={ () => {
					recordEvent( 'mobile_app_qr_direct_login_revoked' );
					onRevoke();
				} }
			>
				{ __( "It wasn't you? Revoke access", 'woocommerce' ) }
			</Button>
		</div>
	);
};
