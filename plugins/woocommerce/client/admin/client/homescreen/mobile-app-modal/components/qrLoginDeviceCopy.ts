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
 * Build a one-line subline summarizing the device/app the merchant signed in
 * with. Skips any field the mobile app didn't send so we never render empty
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
