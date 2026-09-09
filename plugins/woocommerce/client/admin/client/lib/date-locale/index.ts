/**
 * External dependencies
 */
import moment from 'moment';

/**
 * WordPress sets moment's locale up with translated `weekdays` and
 * `weekdaysShort` but never `weekdaysMin`, so moment keeps its English
 * fallback for that one. react-dates builds the calendar week header from
 * `weekdaysMin`, which is why the Analytics date picker headers stay English.
 *
 * The names are read back off moment instead of `wcSettings.locale` so they
 * always belong to the locale that is actually active.
 */
export function initDateLocale() {
	const weekdaysShort = moment.localeData().weekdaysShort();
	const englishWeekdaysShort = moment.localeData( 'en' ).weekdaysShort();

	// The English fallback is already right for English locales.
	const isUntranslated =
		! Array.isArray( weekdaysShort ) ||
		englishWeekdaysShort.every(
			( name, index ) => name === weekdaysShort[ index ]
		);

	if ( isUntranslated ) {
		return;
	}

	moment.updateLocale( moment.locale(), {
		weekdaysMin: [ ...weekdaysShort ],
	} );
}
