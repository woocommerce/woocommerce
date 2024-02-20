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
	} = getSettings() as DateSettings;

	return dateI18n( datetime, date, undefined );
}
