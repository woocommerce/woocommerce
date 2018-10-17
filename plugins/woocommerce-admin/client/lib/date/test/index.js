/** @format */
/**
 * External dependencies
 */
import moment from 'moment';
import { setSettings } from '@wordpress/date';
import { setLocaleData } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	appendTimestamp,
	toMoment,
	getLastPeriod,
	getCurrentPeriod,
	getRangeLabel,
	loadLocaleData,
	getCurrentDates,
	validateDateInputForRange,
	dateValidationMessages,
	isoDateFormat,
	getDateDifferenceInDays,
	getPreviousDate,
} from 'lib/date';

describe( 'appendTimestamp', () => {
	it( 'should append `start` timestamp', () => {
		expect( appendTimestamp( '2018-01-01', 'start' ) ).toEqual( '2018-01-01T00:00:00+00:00' );
	} );

	it( 'should append `end` timestamp', () => {
		expect( appendTimestamp( '2018-01-01', 'end' ) ).toEqual( '2018-01-01T23:59:59+00:00' );
	} );

	it( 'should throw and error if `timeOfDay` is not valid', () => {
		expect( () => appendTimestamp( '2018-01-01' ) ).toThrow( Error );
	} );
} );

describe( 'toMoment', () => {
	it( 'should pass through a valid Moment object as an argument', () => {
		const now = moment();
		const myMoment = toMoment( 'YYYY', now );
		expect( myMoment ).toEqual( now );
	} );

	it( 'should handle isoFormat dates', () => {
		const myMoment = toMoment( 'YYYY', '2018-04-15' );
		expect( moment.isMoment( myMoment ) ).toBe( true );
		expect( myMoment.isValid() ).toBe( true );
	} );

	it( 'should handle local formats', () => {
		const longDate = toMoment( 'MMMM D, YYYY', 'April 15, 2018' );
		expect( moment.isMoment( longDate ) ).toBe( true );
		expect( longDate.isValid() ).toBe( true );
		expect( longDate.date() ).toBe( 15 );
		expect( longDate.month() ).toBe( 3 );
		expect( longDate.year() ).toBe( 2018 );

		const shortDate = toMoment( 'DD/MM/YYYY', '15/04/2018' );
		expect( moment.isMoment( shortDate ) ).toBe( true );
		expect( shortDate.isValid() ).toBe( true );
		expect( shortDate.date() ).toBe( 15 );
		expect( shortDate.month() ).toBe( 3 );
		expect( shortDate.year() ).toBe( 2018 );
	} );

	it( 'should throw on an invalid argument', () => {
		const fn = () => toMoment( '', 77 );
		expect( fn ).toThrow();
	} );

	it( 'shoud return null on invalid date', () => {
		const invalidDate = toMoment( 'YYYY', '2018-00-00' );
		expect( invalidDate ).toBe( null );
	} );
} );

describe( 'getCurrentPeriod', () => {
	it( 'should return a DateValue object with correct properties', () => {
		const dateValue = getCurrentPeriod( 'day', 'previous_period' );

		expect( dateValue.primaryStart ).toBeDefined();
		expect( dateValue.primaryEnd ).toBeDefined();
		expect( dateValue.secondaryStart ).toBeDefined();
		expect( dateValue.secondaryEnd ).toBeDefined();
	} );

	// day
	const today = moment();
	const yesterday = moment().subtract( 1, 'days' );
	const todayLastYear = moment().subtract( 1, 'years' );

	// week
	const thisWeekStart = moment().startOf( 'week' );
	const lastWeekStart = thisWeekStart.clone().subtract( 1, 'week' );
	const todayLastWeek = today.clone().subtract( 1, 'week' );

	// month
	const thisMonthStart = moment().startOf( 'month' );
	const lastMonthStart = thisMonthStart.clone().subtract( 1, 'month' );
	const todayLastMonth = today.clone().subtract( 1, 'month' );

	// quarter
	const thisQuarterStart = moment().startOf( 'quarter' );
	const lastQuarterStart = thisQuarterStart.clone().subtract( 1, 'quarter' );
	const todayLastQuarter = today.clone().subtract( 1, 'quarter' );

	// year
	const thisYearStart = moment().startOf( 'year' );
	const lastYearStart = thisYearStart.clone().subtract( 1, 'year' );

	describe( 'day', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getCurrentPeriod( 'day', 'previous_period' );

			expect( today.isSame( dateValue.primaryStart, 'day' ) ).toBe( true );
			expect( today.isSame( dateValue.primaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getCurrentPeriod( 'day', 'previous_period' );

			expect( yesterday.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( yesterday.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getCurrentPeriod( 'day', 'previous_year' );

			expect( todayLastYear.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( todayLastYear.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );
	} );

	describe( 'week', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getCurrentPeriod( 'week', 'previous_period' );

			expect( thisWeekStart.isSame( dateValue.primaryStart, 'day' ) ).toBe( true );
			expect( today.isSame( dateValue.primaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getCurrentPeriod( 'week', 'previous_period' );

			expect( lastWeekStart.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( todayLastWeek.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getCurrentPeriod( 'week', 'previous_year' );
			const daysSoFar = today.diff( thisWeekStart, 'days' );
			const thisWeekLastYearStart = thisWeekStart
				.clone()
				.subtract( 1, 'years' )
				.week( thisWeekStart.week() )
				.startOf( 'week' );
			const todayThisWeekLastYear = thisWeekLastYearStart.clone().add( daysSoFar, 'days' );

			expect( thisWeekLastYearStart.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( todayThisWeekLastYear.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );
	} );

	describe( 'month', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getCurrentPeriod( 'month', 'previous_period' );

			expect( thisMonthStart.isSame( dateValue.primaryStart, 'day' ) ).toBe( true );
			expect( today.isSame( dateValue.primaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getCurrentPeriod( 'month', 'previous_period' );

			expect( lastMonthStart.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( todayLastMonth.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getCurrentPeriod( 'month', 'previous_year' );
			const daysSoFar = today.diff( thisMonthStart, 'days' );

			const thisMonthLastYearStart = thisMonthStart.clone().subtract( 1, 'years' );
			const thisMonthLastYearEnd = thisMonthLastYearStart.clone().add( daysSoFar, 'days' );

			expect( thisMonthLastYearStart.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( thisMonthLastYearEnd.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );
	} );

	describe( 'quarter', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getCurrentPeriod( 'quarter', 'previous_period' );

			expect( thisQuarterStart.isSame( dateValue.primaryStart, 'day' ) ).toBe( true );
			expect( today.isSame( dateValue.primaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getCurrentPeriod( 'quarter', 'previous_period' );

			expect( lastQuarterStart.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( todayLastQuarter.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getCurrentPeriod( 'quarter', 'previous_year' );
			const daysSoFar = today.diff( thisQuarterStart, 'days' );

			const thisQuarterLastYearStart = thisQuarterStart.clone().subtract( 1, 'years' );
			const thisQuarterLastYearEnd = thisQuarterLastYearStart.clone().add( daysSoFar, 'days' );

			expect( thisQuarterLastYearStart.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( thisQuarterLastYearEnd.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );
	} );

	describe( 'year', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getCurrentPeriod( 'year', 'previous_period' );

			expect( thisYearStart.isSame( dateValue.primaryStart, 'day' ) ).toBe( true );
			expect( today.isSame( dateValue.primaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getCurrentPeriod( 'year', 'previous_period' );

			expect( lastYearStart.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( todayLastYear.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getCurrentPeriod( 'year', 'previous_year' );
			const daysSoFar = today.diff( thisYearStart, 'days' );

			const lastYearEnd = lastYearStart.clone().add( daysSoFar, 'days' );

			expect( lastYearStart.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( lastYearEnd.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );
	} );
} );

describe( 'getLastPeriod', () => {
	it( 'should return a DateValue object with correct properties', () => {
		const dateValue = getLastPeriod( 'day', 'previous_period' );

		expect( dateValue.primaryStart ).toBeDefined();
		expect( dateValue.primaryEnd ).toBeDefined();
		expect( dateValue.secondaryStart ).toBeDefined();
		expect( dateValue.secondaryEnd ).toBeDefined();
	} );

	// day
	const yesterday = moment().subtract( 1, 'days' );
	const twoDaysAgo = moment().subtract( 2, 'days' );
	const yesterdayLastYear = moment()
		.subtract( 1, 'days' )
		.subtract( 1, 'years' );

	// week
	const lastWeekStart = moment()
		.startOf( 'week' )
		.subtract( 1, 'week' );
	const lastWeekEnd = lastWeekStart.clone().endOf( 'week' );

	// month
	const lastMonthStart = moment()
		.startOf( 'month' )
		.subtract( 1, 'month' );
	const lastMonthEnd = lastMonthStart.clone().endOf( 'month' );

	// quarter
	const lastQuarterStart = moment()
		.startOf( 'quarter' )
		.subtract( 1, 'quarter' );
	const lastQuarterEnd = lastQuarterStart.clone().endOf( 'quarter' );

	// year
	const lastYearStart = moment()
		.startOf( 'year' )
		.subtract( 1, 'year' );
	const lastYearEnd = lastYearStart.clone().endOf( 'year' );
	const twoYearsAgoStart = moment()
		.startOf( 'year' )
		.subtract( 2, 'year' );

	describe( 'day', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getLastPeriod( 'day', 'previous_period' );

			expect( yesterday.isSame( dateValue.primaryStart, 'day' ) ).toBe( true );
			expect( yesterday.isSame( dateValue.primaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getLastPeriod( 'day', 'previous_period' );

			expect( twoDaysAgo.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( twoDaysAgo.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getLastPeriod( 'day', 'previous_year' );

			expect( yesterdayLastYear.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( yesterdayLastYear.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );
	} );

	describe( 'week', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getLastPeriod( 'week', 'previous_period' );

			expect( lastWeekStart.isSame( dateValue.primaryStart, 'day' ) ).toBe( true );
			expect( lastWeekEnd.isSame( dateValue.primaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getLastPeriod( 'week', 'previous_period' );

			const twoWeeksAgoStart = lastWeekStart.clone().subtract( 1, 'week' );
			const twoWeeksAgoEnd = twoWeeksAgoStart.clone().endOf( 'week' );

			expect( twoWeeksAgoStart.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( twoWeeksAgoEnd.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getLastPeriod( 'week', 'previous_year' );

			const lastWeekLastYearStart = lastWeekStart
				.clone()
				.subtract( 1, 'year' )
				.week( lastWeekStart.week() )
				.startOf( 'week' );
			const lastWeekLastYearEnd = lastWeekLastYearStart.clone().endOf( 'week' );

			expect( lastWeekLastYearStart.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( lastWeekLastYearEnd.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );
	} );

	describe( 'month', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getLastPeriod( 'month', 'previous_period' );

			expect( lastMonthStart.isSame( dateValue.primaryStart, 'day' ) ).toBe( true );
			expect( lastMonthEnd.isSame( dateValue.primaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getLastPeriod( 'month', 'previous_period' );
			const daysDiff = lastMonthEnd.diff( lastMonthStart, 'days' );

			const twoMonthsAgoEnd = lastMonthStart.clone().subtract( 1, 'days' );
			const twoMonthsAgoStart = twoMonthsAgoEnd.clone().subtract( daysDiff, 'days' );

			expect( twoMonthsAgoStart.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( twoMonthsAgoEnd.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getLastPeriod( 'month', 'previous_year' );

			const lastMonthkLastYearStart = lastMonthStart.clone().subtract( 1, 'year' );
			const lastMonthkLastYearEnd = lastMonthkLastYearStart.clone().endOf( 'month' );

			expect( lastMonthkLastYearStart.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( lastMonthkLastYearEnd.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );
	} );

	describe( 'quarter', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getLastPeriod( 'quarter', 'previous_period' );

			expect( lastQuarterStart.isSame( dateValue.primaryStart, 'day' ) ).toBe( true );
			expect( lastQuarterEnd.isSame( dateValue.primaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getLastPeriod( 'quarter', 'previous_period' );
			const daysDiff = lastQuarterEnd.diff( lastQuarterStart, 'days' );

			const twoQuartersAgoEnd = lastQuarterStart.clone().subtract( 1, 'days' );
			const twoQuartersAgoStart = twoQuartersAgoEnd.clone().subtract( daysDiff, 'days' );

			expect( twoQuartersAgoStart.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( twoQuartersAgoEnd.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getLastPeriod( 'quarter', 'previous_year' );
			const lastQuarterLastYearStart = lastQuarterStart.clone().subtract( 1, 'year' );
			const lastQuarterLastYearEnd = lastQuarterLastYearStart.clone().endOf( 'quarter' );

			expect( lastQuarterLastYearStart.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( lastQuarterLastYearEnd.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );
	} );

	describe( 'year', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getLastPeriod( 'year', 'previous_period' );

			expect( lastYearStart.isSame( dateValue.primaryStart, 'day' ) ).toBe( true );
			expect( lastYearEnd.isSame( dateValue.primaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getLastPeriod( 'year', 'previous_period' );
			const twoYearsAgoEnd = twoYearsAgoStart.clone().endOf( 'year' );

			expect( twoYearsAgoStart.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( twoYearsAgoEnd.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getLastPeriod( 'year', 'previous_year' );
			const twoYearsAgoEnd = twoYearsAgoStart.clone().endOf( 'year' );

			expect( twoYearsAgoStart.isSame( dateValue.secondaryStart, 'day' ) ).toBe( true );
			expect( twoYearsAgoEnd.isSame( dateValue.secondaryEnd, 'day' ) ).toBe( true );
		} );
	} );
} );

describe( 'getRangeLabel', () => {
	it( 'should return correct string for dates on the same day', () => {
		const label = getRangeLabel( moment( '2018-04-15' ), moment( '2018-04-15' ) );
		expect( label ).toBe( 'Apr 15, 2018' );
	} );

	it( 'should return correct string for dates in the same month', () => {
		const label = getRangeLabel( moment( '2018-04-01' ), moment( '2018-04-15' ) );
		expect( label ).toBe( 'Apr 1 - 15, 2018' );
	} );

	it( 'should return correct string for dates in the same year, but different months', () => {
		const label = getRangeLabel( moment( '2018-04-01' ), moment( '2018-05-15' ) );
		expect( label ).toBe( 'Apr 1 - May 15, 2018' );
	} );

	it( 'should return correct string for dates in different years', () => {
		const label = getRangeLabel( moment( '2017-04-01' ), moment( '2018-05-15' ) );
		expect( label ).toBe( 'Apr 1, 2017 - May 15, 2018' );
	} );
} );

describe( 'loadLocaleData', () => {
	beforeEach( () => {
		// Reset to default settings
		setSettings( {
			l10n: {
				locale: 'en_US',
				months: [
					'January',
					'February',
					'March',
					'April',
					'May',
					'June',
					'July',
					'August',
					'September',
					'October',
					'November',
					'December',
				],
				monthsShort: [
					'Jan',
					'Feb',
					'Mar',
					'Apr',
					'May',
					'Jun',
					'Jul',
					'Aug',
					'Sep',
					'Oct',
					'Nov',
					'Dec',
				],
				weekdays: [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ],
				weekdaysShort: [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ],
				meridiem: { am: 'am', pm: 'pm', AM: 'AM', PM: 'PM' },
				relative: { future: ' % s from now', past: '% s ago' },
			},
			formats: {
				time: 'g: i a',
				date: 'F j, Y',
				datetime: 'F j, Y g: i a',
			},
			timezone: { offset: '0', string: '' },
		} );

		wcSettings = {
			adminUrl: 'https://vagrant.local/wp/wp-admin/',
			siteLocale: 'en-US',
			currency: { code: 'USD', precision: 2, symbol: '&#36;' },
			date: {
				dow: 0,
			},
		};
	} );

	function setToFrancais() {
		setSettings( {
			l10n: {
				locale: 'fr_FR',
				months: [
					'janvier',
					'f\u00e9vrier',
					'mars',
					'avril',
					'mai',
					'juin',
					'juillet',
					'ao\u00fbt',
					'septembre',
					'octobre',
					'novembre',
					'd\u00e9cembre',
				],
				monthsShort: [
					'Jan',
					'F\u00e9v',
					'Mar',
					'Avr',
					'Mai',
					'Juin',
					'Juil',
					'Ao\u00fbt',
					'Sep',
					'Oct',
					'Nov',
					'D\u00e9c',
				],
				weekdays: [ 'dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi' ],
				weekdaysShort: [ 'dim', 'lun', 'mar', 'mer', 'jeu', 'ven', 'sam' ],
				meridiem: { am: ' ', pm: ' ', AM: ' ', PM: ' ' },
				relative: { future: '%s \u00e0 partir de maintenant', past: 'Il y a %s' },
			},
			formats: { time: 'g:i a', date: 'F j, Y', datetime: 'j F Y G \\h i \\m\\i\\n' },
			timezone: { offset: '0', string: '' },
		} );
		setLocaleData( { '': { domain: 'wc-admin', lang: 'fr_FR' } }, 'wc-admin' );
	}

	it( 'should leave default momentjs data unchanged for english languages', () => {
		loadLocaleData();
		expect( moment.locale() ).toBe( 'en' );
	} );

	it( "should load french data on user locale 'fr-FR'", () => {
		setToFrancais();
		loadLocaleData();
		expect( moment.localeData()._months[ 0 ] ).toBe( 'janvier' );
	} );

	it( "should load translated longDateFormats on user locale 'fr-FR'", () => {
		setToFrancais();
		loadLocaleData();
		expect( moment.localeData()._longDateFormat.LL ).not.toBe( 'F j, Y' );
	} );

	it( "should load start of week on user locale 'fr-FR'", () => {
		setToFrancais();
		wcSettings.date.dow = 5;
		loadLocaleData();
		expect( moment.localeData()._week.dow ).toBe( 5 );
	} );
} );

describe( 'getCurrentDates', () => {
	it( 'should return a correctly shaped object', () => {
		const query = {};
		const currentDates = getCurrentDates( query );

		expect( currentDates.primary ).toBeDefined();
		expect( 'string' === typeof currentDates.primary.label ).toBe( true );
		expect( 'string' === typeof currentDates.primary.range ).toBe( true );
		expect( 'string' === typeof currentDates.primary.after ).toBe( true );
		expect( 'string' === typeof currentDates.primary.before ).toBe( true );

		expect( currentDates.secondary ).toBeDefined();
		expect( 'string' === typeof currentDates.secondary.label ).toBe( true );
		expect( 'string' === typeof currentDates.secondary.range ).toBe( true );
		expect( 'string' === typeof currentDates.secondary.after ).toBe( true );
		expect( 'string' === typeof currentDates.secondary.before ).toBe( true );
	} );

	it( 'should correctly apply default values', () => {
		const query = {};
		const today = moment().format( isoDateFormat );
		const yesterday = moment()
			.subtract( 1, 'day' )
			.format( isoDateFormat );
		const currentDates = getCurrentDates( query );

		// Ensure default period is 'today'
		expect( currentDates.primary.after ).toBe( today );
		expect( currentDates.primary.before ).toBe( today );

		// Ensure default compare is `previous_period`
		expect( currentDates.secondary.after ).toBe( yesterday );
		expect( currentDates.secondary.before ).toBe( yesterday );
	} );
} );

describe( 'validateDateInputForRange', () => {
	const dateFormat = 'YYYY-MM-DD';

	it( 'should return a valid date in Moment object', () => {
		const validated = validateDateInputForRange( 'after', '2018-04-15', null, null, dateFormat );
		expect( moment.isMoment( validated.date ) ).toBe( true );
		expect( validated.error ).toBe( undefined );
	} );

	it( 'should return a null date on invalid date string', () => {
		const validated = validateDateInputForRange(
			'after',
			'BAd-2018-Date-Format/15/4',
			null,
			null,
			dateFormat
		);
		expect( validated.date ).toBe( null );
		expect( validated.error ).toBe( dateValidationMessages.invalid );
	} );

	it( 'should return a correct error for a date in the future', () => {
		const futureDateString = moment()
			.add( 1, 'months' )
			.format( dateFormat );
		const validated = validateDateInputForRange(
			'after',
			futureDateString,
			null,
			null,
			dateFormat
		);
		expect( validated.date ).toBe( null );
		expect( validated.error ).toBe( dateValidationMessages.future );
	} );

	it( 'should return a correct error for start', () => {
		const futureDateString = moment()
			.add( 1, 'months' )
			.format( dateFormat );
		const validated = validateDateInputForRange(
			'after',
			futureDateString,
			null,
			null,
			dateFormat
		);
		expect( validated.date ).toBe( null );
		expect( validated.error ).toBe( dateValidationMessages.future );
	} );

	it( 'should return a correct error for start after end', () => {
		const end = moment().subtract( 5, 'months' );
		const value = end
			.clone()
			.add( 1, 'months' )
			.format( dateFormat );
		const validated = validateDateInputForRange( 'after', value, end, null, dateFormat );
		expect( validated.date ).toBe( null );
		expect( validated.error ).toBe( dateValidationMessages.startAfterEnd );
	} );

	it( 'should return a correct error for end after start', () => {
		const start = moment().subtract( 5, 'months' );
		const value = start
			.clone()
			.subtract( 1, 'months' )
			.format( dateFormat );
		const validated = validateDateInputForRange( 'before', value, null, start, dateFormat );
		expect( validated.date ).toBe( null );
		expect( validated.error ).toBe( dateValidationMessages.endBeforeStart );
	} );
} );

describe( 'getDateDifferenceInDays', () => {
	it( 'should calculate the day difference between two dates', () => {
		const difference = getDateDifferenceInDays( '2018-08-22', '2018-05-22' );
		expect( difference ).toBe( 92 );
	} );
} );

describe( 'getPreviousDate', () => {
	it( 'should return valid date for previous period by days', () => {
		const date = '2018-08-21';
		const primaryStart = '2018-08-25';
		const secondaryStart = '2018-08-15';
		const previousDate = getPreviousDate(
			date,
			primaryStart,
			secondaryStart,
			'previous_period',
			'day'
		);
		expect( previousDate.format( isoDateFormat ) ).toBe( '2018-08-11' );
	} );
	it( 'should return valid date for previous period by months', () => {
		const date = '2018-08-21';
		const primaryStart = '2018-08-01';
		const secondaryStart = '2018-07-01';
		const previousDate = getPreviousDate(
			date,
			primaryStart,
			secondaryStart,
			'previous_period',
			'month'
		);
		expect( previousDate.format( isoDateFormat ) ).toBe( '2018-07-21' );
	} );
	it( 'should return valid date for previous year', () => {
		const date = '2018-08-21';
		const primaryStart = '2018-08-01';
		const secondaryStart = '2018-07-01';
		const previousDate = getPreviousDate(
			date,
			primaryStart,
			secondaryStart,
			'previous_year',
			'day'
		);
		expect( previousDate.format( isoDateFormat ) ).toBe( '2017-08-21' );
	} );
} );
