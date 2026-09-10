/**
 * External dependencies
 */
import moment from 'moment';

/**
 * Compares two lists of weekday names.
 *
 * @param names      Names to compare.
 * @param otherNames Names to compare against.
 */
function isSameNames( names: string[], otherNames: string[] ) {
	return (
		names.length === otherNames.length &&
		names.every( ( name, index ) => name === otherNames[ index ] )
	);
}

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
	const localeData = moment.localeData();
	const weekdaysMin = localeData.weekdaysMin();
	const weekdaysShort = localeData.weekdaysShort();

	// A locale can hold its weekday names in shapes other than a plain list.
	if ( ! Array.isArray( weekdaysMin ) || ! Array.isArray( weekdaysShort ) ) {
		return;
	}

	const englishLocaleData = moment.localeData( 'en' );

	// Anything that already replaced the English fallback is a better source
	// than the short names, so leave it alone.
	if ( ! isSameNames( weekdaysMin, englishLocaleData.weekdaysMin() ) ) {
		return;
	}

	// The English fallback is already right for English locales.
	if ( isSameNames( weekdaysShort, englishLocaleData.weekdaysShort() ) ) {
		return;
	}

	moment.updateLocale( moment.locale(), {
		weekdaysMin: [ ...weekdaysShort ],
	} );
}
