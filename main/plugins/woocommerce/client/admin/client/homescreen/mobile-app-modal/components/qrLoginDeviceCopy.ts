/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { QRLoginDeviceInfo } from './useQRLoginToken';

/**
 * Build the headline shown after a successful sign-in. The Task 7
 * `/qr-login-scan` endpoint requires a device payload, so by the time we
 * render we should have at least an OS label. The null guard covers brief
 * React state handoff renders, not protocol compatibility.
 */
export const buildQRLoginDeviceHeadline = (
	device: QRLoginDeviceInfo | null
): string => {
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
 * Build a one-line device summary (model · OS version · app version). Skips
 * any individual field the mobile app didn't send so we never render empty
 * separators or `undefined` artifacts, and falls back to a generic "Mobile
 * app" label only when nothing at all is available (a server contract bug or
 * the brief pre-poll render window).
 *
 * Shared by the number-match step (step 2) and the success step (step 3) so
 * both surface the same device line — including the model, which is the most
 * recognizable field for spotting a wrong-device scan.
 */
export const buildQRLoginDeviceLine = (
	device: QRLoginDeviceInfo | null
): string => {
	const parts: string[] = [];

	const model = device?.model?.trim();
	if ( model ) {
		parts.push( model );
	}

	if ( device?.os ) {
		parts.push(
			device.os_version
				? `${ device.os } ${ device.os_version }`
				: device.os
		);
	}

	if ( device?.app_version ) {
		parts.push(
			sprintf(
				/* translators: %s: mobile app version, e.g. "24.7.0". */
				__( 'App version %s', 'woocommerce' ),
				device.app_version
			)
		);
	}

	return parts.length > 0
		? parts.join( ' · ' )
		: __( 'Mobile app', 'woocommerce' );
};

/**
 * Build a one-line subline summarizing the OS/app the merchant signed in with,
 * deliberately omitting the model because the standalone `QRLoginConsumedPanel`
 * already names the model in its headline ("Signed in successfully on …").
 * Skips any field the mobile app didn't send so we never render empty
 * separators or `undefined` artifacts.
 */
export const buildQRLoginDeviceSubline = (
	device: QRLoginDeviceInfo | null
): string => {
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
