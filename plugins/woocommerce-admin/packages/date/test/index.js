/**
 * External dependencies
 */
import moment from 'moment';
import { getSetting } from '@woocommerce/wc-admin-settings';

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
	getChartTypeForQuery,
	getAllowedIntervalsForQuery,
	getStoreTimeZoneMoment,
} from '../src';

jest.mock( 'moment', () => {
	const m = jest.requireActual( 'moment' );
	m.prototype.tz = jest.fn().mockImplementation( () => m() );

	return m;
} );

describe( 'appendTimestamp', () => {
	it( 'should append `start` timestamp', () => {
		expect( appendTimestamp( moment( '2018-01-01' ), 'start' ) ).toEqual(
			'2018-01-01T00:00:00'
		);
	} );

	it( 'should append `now` timestamp', () => {
		const nowTimestamp = moment().format( 'HH:mm:00' );
		expect(
			appendTimestamp( moment( '2018-01-01 ' + nowTimestamp ), 'now' )
		).toEqual( '2018-01-01T' + nowTimestamp );
	} );

	it( 'should append `end` timestamp', () => {
		expect( appendTimestamp( moment( '2018-01-01' ), 'end' ) ).toEqual(
			'2018-01-01T23:59:59'
		);
	} );

	it( 'should throw and error if `timeOfDay` is not valid', () => {
		expect( () => appendTimestamp( moment( '2018-01-01' ) ) ).toThrow(
			Error
		);
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

	it( 'should return null on invalid date', () => {
		const invalidDate = toMoment( 'YYYY', '2018-00-00' );
		expect( invalidDate ).toBe( null );
	} );
} );

describe( 'getAllowedIntervalsForQuery', () => {
	it( 'should return days when query period is defined but empty', () => {
		const allowedIntervals = getAllowedIntervalsForQuery( { period: '' } );
		expect( allowedIntervals ).toEqual( [ 'day' ] );
	} );

	it( 'should return days and hours for today and yesterday periods', () => {
		const allowedIntervalsToday = getAllowedIntervalsForQuery( {
			period: 'today',
		} );
		expect( allowedIntervalsToday ).toEqual( [ 'hour', 'day' ] );

		const allowedIntervalsYesterday = getAllowedIntervalsForQuery( {
			period: 'yesterday',
		} );
		expect( allowedIntervalsYesterday ).toEqual( [ 'hour', 'day' ] );
	} );

	it( 'should return day for week and last_week periods', () => {
		const allowedIntervalsWeek = getAllowedIntervalsForQuery( {
			period: 'week',
		} );
		expect( allowedIntervalsWeek ).toEqual( [ 'day' ] );

		const allowedIntervalsLastWeek = getAllowedIntervalsForQuery( {
			period: 'last_week',
		} );
		expect( allowedIntervalsLastWeek ).toEqual( [ 'day' ] );
	} );

	it( 'should return day, week for month and last_month periods', () => {
		const allowedIntervalsMonth = getAllowedIntervalsForQuery( {
			period: 'month',
		} );
		expect( allowedIntervalsMonth ).toEqual( [ 'day', 'week' ] );

		const allowedIntervalsLastMonth = getAllowedIntervalsForQuery( {
			period: 'last_month',
		} );
		expect( allowedIntervalsLastMonth ).toEqual( [ 'day', 'week' ] );
	} );

	it( 'should return day, week, month for quarter and last_quarter periods', () => {
		const allowedIntervalsQuarter = getAllowedIntervalsForQuery( {
			period: 'quarter',
		} );
		expect( allowedIntervalsQuarter ).toEqual( [ 'day', 'week', 'month' ] );

		const allowedIntervalsLastQuarter = getAllowedIntervalsForQuery( {
			period: 'last_quarter',
		} );
		expect( allowedIntervalsLastQuarter ).toEqual( [
			'day',
			'week',
			'month',
		] );
	} );

	it( 'should return day, week, month, quarter for year and last_year periods', () => {
		const allowedIntervalsYear = getAllowedIntervalsForQuery( {
			period: 'year',
		} );
		expect( allowedIntervalsYear ).toEqual( [
			'day',
			'week',
			'month',
			'quarter',
		] );

		const allowedIntervalsLastYear = getAllowedIntervalsForQuery( {
			period: 'last_year',
		} );
		expect( allowedIntervalsLastYear ).toEqual( [
			'day',
			'week',
			'month',
			'quarter',
		] );
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

			expect( today.isSame( dateValue.primaryStart, 'day' ) ).toBe(
				true
			);
			expect( today.isSame( dateValue.primaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getCurrentPeriod( 'day', 'previous_period' );

			expect( yesterday.isSame( dateValue.secondaryStart, 'day' ) ).toBe(
				true
			);
			expect( yesterday.isSame( dateValue.secondaryEnd, 'day' ) ).toBe(
				true
			);
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getCurrentPeriod( 'day', 'previous_year' );

			expect(
				todayLastYear.isSame( dateValue.secondaryStart, 'day' )
			).toBe( true );
			expect(
				todayLastYear.isSame( dateValue.secondaryEnd, 'day' )
			).toBe( true );
		} );
	} );

	describe( 'week', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getCurrentPeriod( 'week', 'previous_period' );

			expect(
				thisWeekStart.isSame( dateValue.primaryStart, 'day' )
			).toBe( true );
			expect( today.isSame( dateValue.primaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getCurrentPeriod( 'week', 'previous_period' );

			expect(
				lastWeekStart.isSame( dateValue.secondaryStart, 'day' )
			).toBe( true );
			expect(
				todayLastWeek.isSame( dateValue.secondaryEnd, 'day' )
			).toBe( true );
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getCurrentPeriod( 'week', 'previous_year' );
			const daysSoFar = today.diff( thisWeekStart, 'days' );
			// Last year weeks are aligned by calendar date not day of week.
			const thisWeekLastYearStart = thisWeekStart
				.clone()
				.subtract( 1, 'years' );
			const todayThisWeekLastYear = thisWeekLastYearStart
				.clone()
				.add( daysSoFar, 'days' );

			expect(
				thisWeekLastYearStart.isSame( dateValue.secondaryStart, 'day' )
			).toBe( true );
			expect(
				todayThisWeekLastYear.isSame( dateValue.secondaryEnd, 'day' )
			).toBe( true );
		} );
	} );

	describe( 'month', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getCurrentPeriod( 'month', 'previous_period' );

			expect(
				thisMonthStart.isSame( dateValue.primaryStart, 'day' )
			).toBe( true );
			expect( today.isSame( dateValue.primaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getCurrentPeriod( 'month', 'previous_period' );

			expect(
				lastMonthStart.isSame( dateValue.secondaryStart, 'day' )
			).toBe( true );
			expect(
				todayLastMonth.isSame( dateValue.secondaryEnd, 'day' )
			).toBe( true );
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getCurrentPeriod( 'month', 'previous_year' );
			const daysSoFar = today.diff( thisMonthStart, 'days' );

			const thisMonthLastYearStart = thisMonthStart
				.clone()
				.subtract( 1, 'years' );
			const thisMonthLastYearEnd = thisMonthLastYearStart
				.clone()
				.add( daysSoFar, 'days' );

			expect(
				thisMonthLastYearStart.isSame( dateValue.secondaryStart, 'day' )
			).toBe( true );
			expect(
				thisMonthLastYearEnd.isSame( dateValue.secondaryEnd, 'day' )
			).toBe( true );
		} );
	} );

	describe( 'quarter', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getCurrentPeriod( 'quarter', 'previous_period' );

			expect(
				thisQuarterStart.isSame( dateValue.primaryStart, 'day' )
			).toBe( true );
			expect( today.isSame( dateValue.primaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getCurrentPeriod( 'quarter', 'previous_period' );

			expect(
				lastQuarterStart.isSame( dateValue.secondaryStart, 'day' )
			).toBe( true );
			expect(
				todayLastQuarter.isSame( dateValue.secondaryEnd, 'day' )
			).toBe( true );
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getCurrentPeriod( 'quarter', 'previous_year' );
			const daysSoFar = today.diff( thisQuarterStart, 'days' );

			const thisQuarterLastYearStart = thisQuarterStart
				.clone()
				.subtract( 1, 'years' );
			const thisQuarterLastYearEnd = thisQuarterLastYearStart
				.clone()
				.add( daysSoFar, 'days' );

			expect(
				thisQuarterLastYearStart.isSame(
					dateValue.secondaryStart,
					'day'
				)
			).toBe( true );
			expect(
				thisQuarterLastYearEnd.isSame( dateValue.secondaryEnd, 'day' )
			).toBe( true );
		} );
	} );

	describe( 'year', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getCurrentPeriod( 'year', 'previous_period' );

			expect(
				thisYearStart.isSame( dateValue.primaryStart, 'day' )
			).toBe( true );
			expect( today.isSame( dateValue.primaryEnd, 'day' ) ).toBe( true );
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getCurrentPeriod( 'year', 'previous_period' );

			expect(
				lastYearStart.isSame( dateValue.secondaryStart, 'day' )
			).toBe( true );
			expect(
				todayLastYear.isSame( dateValue.secondaryEnd, 'day' )
			).toBe( true );
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getCurrentPeriod( 'year', 'previous_year' );
			const daysSoFar = today.diff( thisYearStart, 'days' );

			const lastYearEnd = lastYearStart.clone().add( daysSoFar, 'days' );

			expect(
				lastYearStart.isSame( dateValue.secondaryStart, 'day' )
			).toBe( true );
			expect( lastYearEnd.isSame( dateValue.secondaryEnd, 'day' ) ).toBe(
				true
			);
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
	const lastWeekStart = moment().startOf( 'week' ).subtract( 1, 'week' );
	const lastWeekEnd = lastWeekStart.clone().endOf( 'week' );

	// month
	const lastMonthStart = moment().startOf( 'month' ).subtract( 1, 'month' );
	const lastMonthEnd = lastMonthStart.clone().endOf( 'month' );

	// quarter
	const lastQuarterStart = moment()
		.startOf( 'quarter' )
		.subtract( 1, 'quarter' );
	const lastQuarterEnd = lastQuarterStart.clone().endOf( 'quarter' );

	// year
	const lastYearStart = moment().startOf( 'year' ).subtract( 1, 'year' );
	const lastYearEnd = lastYearStart.clone().endOf( 'year' );
	const twoYearsAgoStart = moment().startOf( 'year' ).subtract( 2, 'year' );

	describe( 'day', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getLastPeriod( 'day', 'previous_period' );

			expect( yesterday.isSame( dateValue.primaryStart, 'day' ) ).toBe(
				true
			);
			expect( yesterday.isSame( dateValue.primaryEnd, 'day' ) ).toBe(
				true
			);
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getLastPeriod( 'day', 'previous_period' );

			expect( twoDaysAgo.isSame( dateValue.secondaryStart, 'day' ) ).toBe(
				true
			);
			expect( twoDaysAgo.isSame( dateValue.secondaryEnd, 'day' ) ).toBe(
				true
			);
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getLastPeriod( 'day', 'previous_year' );

			expect(
				yesterdayLastYear.isSame( dateValue.secondaryStart, 'day' )
			).toBe( true );
			expect(
				yesterdayLastYear.isSame( dateValue.secondaryEnd, 'day' )
			).toBe( true );
		} );
	} );

	describe( 'week', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getLastPeriod( 'week', 'previous_period' );

			expect(
				lastWeekStart.isSame( dateValue.primaryStart, 'day' )
			).toBe( true );
			expect( lastWeekEnd.isSame( dateValue.primaryEnd, 'day' ) ).toBe(
				true
			);
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getLastPeriod( 'week', 'previous_period' );

			const twoWeeksAgoStart = lastWeekStart
				.clone()
				.subtract( 1, 'week' );
			const twoWeeksAgoEnd = twoWeeksAgoStart.clone().endOf( 'week' );

			expect(
				twoWeeksAgoStart.isSame( dateValue.secondaryStart, 'day' )
			).toBe( true );
			expect(
				twoWeeksAgoEnd.isSame( dateValue.secondaryEnd, 'day' )
			).toBe( true );
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getLastPeriod( 'week', 'previous_year' );

			// Last year weeks are aligned by calendar date not day of week.
			const lastWeekLastYearStart = lastWeekStart
				.clone()
				.subtract( 1, 'year' );
			const lastWeekLastYearEnd = lastWeekEnd
				.clone()
				.subtract( 1, 'year' );

			expect(
				lastWeekLastYearStart.isSame( dateValue.secondaryStart, 'day' )
			).toBe( true );
			expect(
				lastWeekLastYearEnd.isSame( dateValue.secondaryEnd, 'day' )
			).toBe( true );
		} );
	} );

	describe( 'month', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getLastPeriod( 'month', 'previous_period' );

			expect(
				lastMonthStart.isSame( dateValue.primaryStart, 'day' )
			).toBe( true );
			expect( lastMonthEnd.isSame( dateValue.primaryEnd, 'day' ) ).toBe(
				true
			);
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getLastPeriod( 'month', 'previous_period' );
			const daysDiff = lastMonthEnd.diff( lastMonthStart, 'days' );

			const twoMonthsAgoEnd = lastMonthStart
				.clone()
				.subtract( 1, 'days' );
			const twoMonthsAgoStart = twoMonthsAgoEnd
				.clone()
				.subtract( daysDiff, 'days' );

			expect(
				twoMonthsAgoStart.isSame( dateValue.secondaryStart, 'day' )
			).toBe( true );
			expect(
				twoMonthsAgoEnd.isSame( dateValue.secondaryEnd, 'day' )
			).toBe( true );
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getLastPeriod( 'month', 'previous_year' );

			const lastMonthkLastYearStart = lastMonthStart
				.clone()
				.subtract( 1, 'year' );
			const lastMonthkLastYearEnd = lastMonthkLastYearStart
				.clone()
				.endOf( 'month' );
			expect(
				lastMonthkLastYearStart.isSame(
					dateValue.secondaryStart,
					'day'
				)
			).toBe( true );
			expect(
				lastMonthkLastYearEnd.isSame( dateValue.secondaryEnd, 'day' )
			).toBe( true );
		} );
	} );

	describe( 'quarter', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getLastPeriod( 'quarter', 'previous_period' );

			expect(
				lastQuarterStart.isSame( dateValue.primaryStart, 'day' )
			).toBe( true );
			expect( lastQuarterEnd.isSame( dateValue.primaryEnd, 'day' ) ).toBe(
				true
			);
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getLastPeriod( 'quarter', 'previous_period' );
			const daysDiff = lastQuarterEnd.diff( lastQuarterStart, 'days' );

			const twoQuartersAgoEnd = lastQuarterStart
				.clone()
				.subtract( 1, 'days' );
			const twoQuartersAgoStart = twoQuartersAgoEnd
				.clone()
				.subtract( daysDiff, 'days' );

			expect(
				twoQuartersAgoStart.isSame( dateValue.secondaryStart, 'day' )
			).toBe( true );
			expect(
				twoQuartersAgoEnd.isSame( dateValue.secondaryEnd, 'day' )
			).toBe( true );
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getLastPeriod( 'quarter', 'previous_year' );
			const lastQuarterLastYearStart = lastQuarterStart
				.clone()
				.subtract( 1, 'year' );
			const lastQuarterLastYearEnd = lastQuarterLastYearStart
				.clone()
				.endOf( 'quarter' );

			expect(
				lastQuarterLastYearStart.isSame(
					dateValue.secondaryStart,
					'day'
				)
			).toBe( true );
			expect(
				lastQuarterLastYearEnd.isSame( dateValue.secondaryEnd, 'day' )
			).toBe( true );
		} );
	} );

	describe( 'year', () => {
		it( 'should return correct values for primary period', () => {
			const dateValue = getLastPeriod( 'year', 'previous_period' );

			expect(
				lastYearStart.isSame( dateValue.primaryStart, 'day' )
			).toBe( true );
			expect( lastYearEnd.isSame( dateValue.primaryEnd, 'day' ) ).toBe(
				true
			);
		} );

		it( 'should return correct values for previous_period', () => {
			const dateValue = getLastPeriod( 'year', 'previous_period' );
			const twoYearsAgoEnd = twoYearsAgoStart.clone().endOf( 'year' );

			expect(
				twoYearsAgoStart.isSame( dateValue.secondaryStart, 'day' )
			).toBe( true );
			expect(
				twoYearsAgoEnd.isSame( dateValue.secondaryEnd, 'day' )
			).toBe( true );
		} );

		it( 'should return correct values for previous_year', () => {
			const dateValue = getLastPeriod( 'year', 'previous_year' );
			const twoYearsAgoEnd = twoYearsAgoStart.clone().endOf( 'year' );

			expect(
				twoYearsAgoStart.isSame( dateValue.secondaryStart, 'day' )
			).toBe( true );
			expect(
				twoYearsAgoEnd.isSame( dateValue.secondaryEnd, 'day' )
			).toBe( true );
		} );
	} );
} );

describe( 'getRangeLabel', () => {
	it( 'should return correct string for dates on the same day', () => {
		const label = getRangeLabel(
			moment( '2018-04-15' ),
			moment( '2018-04-15' )
		);
		expect( label ).toBe( 'Apr 15, 2018' );
	} );

	it( 'should return correct string for dates in the same month', () => {
		const label = getRangeLabel(
			moment( '2018-04-01' ),
			moment( '2018-04-15' )
		);
		expect( label ).toBe( 'Apr 1 - 15, 2018' );
	} );

	it( 'should return correct string for dates in the same year, but different months', () => {
		const label = getRangeLabel(
			moment( '2018-04-01' ),
			moment( '2018-05-15' )
		);
		expect( label ).toBe( 'Apr 1 - May 15, 2018' );
	} );

	it( 'should return correct string for dates in different years', () => {
		const label = getRangeLabel(
			moment( '2017-04-01' ),
			moment( '2018-05-15' )
		);
		expect( label ).toBe( 'Apr 1, 2017 - May 15, 2018' );
	} );
} );

describe( 'loadLocaleData', () => {
	const originalLocale = getSetting( 'locale' );
	beforeEach( () => {
		// Reset to default settings
		loadLocaleData( originalLocale );
	} );

	it( 'should load locale data on user locale', () => {
		// initialize locale. Gutenberg normaly does this, but not in test environment.
		moment.locale( 'fr_FR', {} );

		const weekdaysShort = [
			'dim',
			'lun',
			'mar',
			'mer',
			'jeu',
			'ven',
			'sam',
		];

		loadLocaleData( {
			userLocale: 'fr_FR',
			weekdaysShort,
		} );
		expect( moment.localeData().weekdaysMin() ).toEqual( weekdaysShort );
	} );
} );

describe( 'getCurrentDates', () => {
	it( 'should return a correctly shaped object', () => {
		const query = {};
		const currentDates = getCurrentDates( query );

		expect( currentDates.primary ).toBeDefined();
		expect( typeof currentDates.primary.label ).toBe( 'string' );
		expect( typeof currentDates.primary.range ).toBe( 'string' );
		expect( moment.isMoment( currentDates.primary.after ) ).toBe( true );
		expect( moment.isMoment( currentDates.primary.before ) ).toBe( true );

		expect( currentDates.secondary ).toBeDefined();
		expect( typeof currentDates.secondary.label ).toBe( 'string' );
		expect( typeof currentDates.secondary.range ).toBe( 'string' );
		expect( moment.isMoment( currentDates.secondary.after ) ).toBe( true );
		expect( moment.isMoment( currentDates.secondary.before ) ).toBe( true );
	} );

	it( 'should correctly apply default values', () => {
		const query = {};
		const today = moment().format( isoDateFormat );
		const startOfMonth = moment()
			.startOf( 'month' )
			.format( isoDateFormat );
		const startOfMonthYearAgo = moment()
			.startOf( 'month' )
			.subtract( 1, 'year' )
			.format( isoDateFormat );
		const todayLastYear = moment()
			.subtract( 1, 'year' )
			.format( isoDateFormat );
		const currentDates = getCurrentDates( query );

		// Ensure default period is 'month'
		expect( currentDates.primary.after.format( isoDateFormat ) ).toBe(
			startOfMonth
		);
		expect( currentDates.primary.before.format( isoDateFormat ) ).toBe(
			today
		);

		// Ensure default compare is `previous_period`
		expect( currentDates.secondary.after.format( isoDateFormat ) ).toBe(
			startOfMonthYearAgo
		);
		expect( currentDates.secondary.before.format( isoDateFormat ) ).toBe(
			todayLastYear
		);
	} );
} );

describe( 'validateDateInputForRange', () => {
	const dateFormat = 'YYYY-MM-DD';

	it( 'should return a valid date in Moment object', () => {
		const validated = validateDateInputForRange(
			'after',
			'2018-04-15',
			null,
			null,
			dateFormat
		);
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
		const value = end.clone().add( 1, 'months' ).format( dateFormat );
		const validated = validateDateInputForRange(
			'after',
			value,
			end,
			null,
			dateFormat
		);
		expect( validated.date ).toBe( null );
		expect( validated.error ).toBe( dateValidationMessages.startAfterEnd );
	} );

	it( 'should return a correct error for end after start', () => {
		const start = moment().subtract( 5, 'months' );
		const value = start
			.clone()
			.subtract( 1, 'months' )
			.format( dateFormat );
		const validated = validateDateInputForRange(
			'before',
			value,
			null,
			start,
			dateFormat
		);
		expect( validated.date ).toBe( null );
		expect( validated.error ).toBe( dateValidationMessages.endBeforeStart );
	} );
} );

describe( 'getDateDifferenceInDays', () => {
	it( 'should calculate the day difference between two dates', () => {
		const difference = getDateDifferenceInDays(
			'2018-08-22',
			'2018-05-22'
		);
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

describe( 'getChartTypeForQuery', () => {
	it( 'should return allowed type', () => {
		const query = {
			chartType: 'bar',
		};
		expect( getChartTypeForQuery( query ) ).toBe( 'bar' );
	} );

	it( 'should default to line', () => {
		expect( getChartTypeForQuery( {} ) ).toBe( 'line' );
	} );

	it( 'should return line for not allowed type', () => {
		const query = {
			chartType: 'burrito',
		};
		expect( getChartTypeForQuery( query ) ).toBe( 'line' );
	} );
} );

describe( 'getStoreTimeZoneMoment', () => {
	it( 'should return the default moment when no timezone exists', () => {
		const mockTz = ( moment.prototype.tz = jest.fn() );
		const utcOffset = ( moment.prototype.utcOffset = jest.fn() );

		expect( getStoreTimeZoneMoment() ).toHaveProperty( '_isAMomentObject' );

		expect( mockTz ).not.toHaveBeenCalled();
		expect( utcOffset ).not.toHaveBeenCalled();
	} );

	it( 'should use the timezone string when one is set', () => {
		global.window.wcSettings = {
			timeZone: 'Asia/Taipei',
		};

		const mockTz = ( moment.prototype.tz = jest.fn() );
		const utcOffset = ( moment.prototype.utcOffset = jest.fn() );

		getStoreTimeZoneMoment();

		expect( mockTz ).toHaveBeenCalledWith( 'Asia/Taipei' );
		expect( utcOffset ).not.toHaveBeenCalled();
	} );

	it( 'should use the utc offest when it is set', () => {
		global.window.wcSettings = {
			timeZone: '+06:00',
		};

		const mockTz = ( moment.prototype.tz = jest.fn() );
		const utcOffset = ( moment.prototype.utcOffset = jest.fn() );

		getStoreTimeZoneMoment();

		expect( mockTz ).not.toHaveBeenCalled();
		expect( utcOffset ).toHaveBeenCalledWith( '+06:00' );

		global.window.wcSettings = {
			timeZone: '-04:00',
		};

		getStoreTimeZoneMoment();

		expect( mockTz ).not.toHaveBeenCalled();
		expect( utcOffset ).toHaveBeenCalledWith( '-04:00' );
	} );
} );
