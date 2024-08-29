/**
 * External dependencies
 */
import {
	DateSettings,
	__experimentalGetSettings as getSettings,
} from '@wordpress/date';

export function isSiteSettingsTimezoneSameAsDateTimezone( date: Date ) {
	const { timezone } = getSettings() as DateSettings;

	const siteOffset = Number( timezone.offset );
	const dateOffset = -1 * ( date.getTimezoneOffset() / 60 );
	return siteOffset === dateOffset;
}
