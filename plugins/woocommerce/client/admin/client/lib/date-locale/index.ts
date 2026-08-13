/**
 * External dependencies
 */
import moment from 'moment';
import { loadLocaleData } from '@woocommerce/date';
import { getSetting } from '@woocommerce/settings';

/**
 * WordPress core loads moment with PHP-style long date formats (e.g.
 * `LL: "F j, Y"`) and without localized minimal weekday names, which
 * breaks components that format dates through moment tokens, such as
 * the react-dates calendar. Re-apply moment-style formats and localized
 * weekday names on top of the WordPress-provided locale.
 *
 * The locale setting must name the moment locale WordPress activated:
 * when `wcSettings.locale` is absent the settings package substitutes
 * en_US defaults, and applying those would switch the whole admin to
 * English on a localized site.
 */
export function initDateLocale() {
	const { userLocale, weekdaysShort } = getSetting< {
		userLocale?: string;
		weekdaysShort?: string[];
	} >( 'locale', {} );
	if ( userLocale === moment.locale() ) {
		loadLocaleData( { userLocale, weekdaysShort } );
	}
}
