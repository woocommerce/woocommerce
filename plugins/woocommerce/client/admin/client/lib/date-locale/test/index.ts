/**
 * External dependencies
 */
import moment from 'moment';

/**
 * Internal dependencies
 */
import { initDateLocale } from '..';

describe( 'initDateLocale', () => {
	const HEBREW_WEEKDAYS_SHORT = [ 'א', 'ב', 'ג', 'ד', 'ה', 'ו', 'ש' ];

	beforeEach( () => {
		// Simulate WordPress core's inline moment setup for a non-English
		// locale: PHP-style long date formats and no weekdaysMin.
		moment.updateLocale( 'he_IL', {
			longDateFormat: {
				LL: 'F j, Y',
			},
			weekdaysShort: HEBREW_WEEKDAYS_SHORT,
		} );
		global.wcSettings = {
			...global.wcSettings,
			locale: {
				siteLocale: 'he_IL',
				userLocale: 'he_IL',
				weekdaysShort: HEBREW_WEEKDAYS_SHORT,
			},
		};
	} );

	afterEach( () => {
		moment.locale( 'en' );
		moment.updateLocale( 'he_IL', null );
		delete global.wcSettings.locale;
	} );

	it( 'overrides the PHP-style long date format WordPress injects into moment', () => {
		expect( moment( '2026-08-02' ).format( 'LL' ) ).toBe( 'F j, 2026' );

		initDateLocale();

		expect( moment( '2026-08-02' ).format( 'LL' ) ).toBe(
			'August 2, 2026'
		);
	} );

	it( 'does nothing when the locale setting does not match the active moment locale', () => {
		// A missing wcSettings.locale entry resolves to en_US defaults in the
		// settings package; applying those on a non-English site would switch
		// the whole admin to English.
		global.wcSettings = {
			...global.wcSettings,
			locale: {
				siteLocale: 'en_US',
				userLocale: 'en_US',
				weekdaysShort: [
					'Sun',
					'Mon',
					'Tue',
					'Wed',
					'Thu',
					'Fri',
					'Sat',
				],
			},
		};

		initDateLocale();

		expect( moment.locale() ).toBe( 'he_IL' );
		expect( moment( '2026-08-02' ).format( 'LL' ) ).toBe( 'F j, 2026' );
	} );

	it( 'loads localized minimal weekday names used by calendar week headers', () => {
		expect( moment.localeData().weekdaysMin() ).toEqual( [
			'Su',
			'Mo',
			'Tu',
			'We',
			'Th',
			'Fr',
			'Sa',
		] );

		initDateLocale();

		expect( moment.localeData().weekdaysMin() ).toEqual(
			HEBREW_WEEKDAYS_SHORT
		);
	} );
} );
