/**
 * External dependencies
 */
import {
	DateSettings,
	__experimentalGetSettings as getSettings,
	dateI18n,
} from '@wordpress/date';

export function formatDatetime( date: string ) {
	const {
		formats: { datetime },
		timezone,
	} = getSettings() as DateSettings;

	return dateI18n( datetime, date, timezone.offset );
}
