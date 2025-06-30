/**
 * External dependencies
 */
import { TimezoneConfig, getSettings } from '@wordpress/date';

export function getSiteSettingsTimezoneAbbreviation() {
	const { timezone } = getSettings() as {
		timezone: TimezoneConfig & { offsetFormatted: string };
	};

	if ( timezone.abbr && isNaN( Number( timezone.abbr ) ) ) {
		return timezone.abbr;
	}

	const symbol = Number( timezone.offset ) < 0 ? '' : '+';
	return `UTC${ symbol }${ timezone.offsetFormatted ?? timezone.offset }`;
}
