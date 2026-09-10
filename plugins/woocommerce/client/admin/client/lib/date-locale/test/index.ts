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
	const ENGLISH_WEEKDAYS_SHORT = [
		'Sun',
		'Mon',
		'Tue',
		'Wed',
		'Thu',
		'Fri',
		'Sat',
	];
	const ENGLISH_WEEKDAYS_MIN = [ 'Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa' ];
	const GERMAN_WEEKDAYS_SHORT = [
		'So.',
		'Mo.',
		'Di.',
		'Mi.',
		'Do.',
		'Fr.',
		'Sa.',
	];
	const GERMAN_WEEKDAYS_MIN = [ 'So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa' ];
	const RUSSIAN_WEEKDAYS_SHORT = [ 'вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб' ];
	const definedLocales: string[] = [];

	/**
	 * Defines a locale and remembers it so afterEach can remove it again.
	 * Without a weekdaysMin this is the shape WordPress leaves behind, and
	 * moment falls back to the English names.
	 *
	 * @param name   Locale name, unique per test so moment never overrides one.
	 * @param config Locale configuration.
	 */
	function defineLocale( name: string, config: moment.LocaleSpecification ) {
		definedLocales.push( name );
		moment.defineLocale( name, config );
	}

	afterEach( () => {
		moment.locale( 'en' );
		while ( definedLocales.length ) {
			const name = definedLocales.pop() as string;
			while ( moment.locales().includes( name ) ) {
				moment.updateLocale( name, null );
			}
		}
	} );

	it( 'fills in the missing weekdaysMin from the translated short names', () => {
		defineLocale( 'test_he', { weekdaysShort: HEBREW_WEEKDAYS_SHORT } );
		expect( moment.localeData().weekdaysMin() ).toEqual(
			ENGLISH_WEEKDAYS_MIN
		);

		initDateLocale();

		expect( moment.localeData().weekdaysMin() ).toEqual(
			HEBREW_WEEKDAYS_SHORT
		);
	} );

	it( 'keeps the rest of the locale data untouched', () => {
		defineLocale( 'test_he_formats', {
			weekdaysShort: HEBREW_WEEKDAYS_SHORT,
			longDateFormat: {
				L: 'DD/MM/YYYY',
				LL: 'D [ב]MMMM YYYY',
				LLL: 'D [ב]MMMM YYYY HH:mm',
				LLLL: 'dddd, D [ב]MMMM YYYY HH:mm',
				LT: 'HH:mm',
				LTS: 'HH:mm:ss',
			},
		} );

		initDateLocale();

		expect( moment.localeData().longDateFormat( 'L' ) ).toBe(
			'DD/MM/YYYY'
		);
		expect( moment.localeData().longDateFormat( 'LL' ) ).toBe(
			'D [ב]MMMM YYYY'
		);
		expect( moment.localeData().weekdaysShort() ).toEqual(
			HEBREW_WEEKDAYS_SHORT
		);
	} );

	it( 'leaves an English locale on the moment fallback', () => {
		defineLocale( 'test_en', { weekdaysShort: ENGLISH_WEEKDAYS_SHORT } );

		initDateLocale();

		expect( moment.localeData().weekdaysMin() ).toEqual(
			ENGLISH_WEEKDAYS_MIN
		);
	} );

	it( 'keeps the weekdaysMin something else already supplied', () => {
		defineLocale( 'test_de', {
			weekdaysShort: GERMAN_WEEKDAYS_SHORT,
			weekdaysMin: GERMAN_WEEKDAYS_MIN,
		} );

		initDateLocale();

		expect( moment.localeData().weekdaysMin() ).toEqual(
			GERMAN_WEEKDAYS_MIN
		);
	} );

	it( 'leaves a locale that holds its short names in another shape alone', () => {
		defineLocale( 'test_standalone', {
			weekdaysShort: {
				format: RUSSIAN_WEEKDAYS_SHORT,
				standalone: RUSSIAN_WEEKDAYS_SHORT,
			},
		} );

		initDateLocale();

		expect( moment.localeData().weekdaysMin() ).toEqual(
			ENGLISH_WEEKDAYS_MIN
		);
	} );
} );
