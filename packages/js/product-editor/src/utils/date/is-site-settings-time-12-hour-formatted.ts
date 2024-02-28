/**
 * External dependencies
 */
import {
	DateSettings,
	__experimentalGetSettings as getSettings,
} from '@wordpress/date';

export function isSiteSettingsTime12HourFormatted() {
	const settings = getSettings() as DateSettings;

	return /a(?!\\)/i.test(
		settings.formats.time
			.toLowerCase()
			.replace( /\\\\/g, '' )
			.split( '' )
			.reverse()
			.join( '' )
	);
}
