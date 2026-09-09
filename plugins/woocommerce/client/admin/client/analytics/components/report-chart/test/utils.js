/**
 * External dependencies
 */
import moment from 'moment';
import {
	getCurrentDates,
	getCurrentPeriod,
	getLastPeriod,
} from '@woocommerce/date';

/**
 * Internal dependencies
 */
import { buildChartData } from '../utils';

function generateDateInterval( interval, startDate, endDate, subtotals ) {
	const subtotalsDefault = {
		orders_count: 0,
		net_revenue: 0,
		avg_order_value: 0,
		avg_items_per_order: 0,
		coupons_count: 0,
		segments: [],
	};
	return {
		interval,
		date_start: `${ startDate } 00:00:00`,
		date_start_gmt: `${ startDate } 00:00:00`,
		date_end: `${ endDate } 23:59:59`,
		date_end_gmt: `${ endDate } 23:59:59`,
		subtotals: {
			...subtotalsDefault,
			...subtotals,
		},
	};
}

function formatDay( date ) {
	const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
	const day = String( date.getDate() ).padStart( 2, '0' );
	return `${ date.getFullYear() }-${ month }-${ day }`;
}

function generateDayIntervals(
	startDate,
	endDate,
	valuesByDate = {},
	key = 'orders_count'
) {
	const intervals = [];
	const day = new Date( `${ startDate }T00:00:00` );
	const end = new Date( `${ endDate }T00:00:00` );
	while ( day <= end ) {
		const date = formatDay( day );
		intervals.push(
			generateDateInterval( date, date, date, {
				[ key ]: valuesByDate[ date ] || 0,
			} )
		);
		day.setDate( day.getDate() + 1 );
	}
	return intervals;
}

function buildDayChartData(
	primaryIntervals,
	secondaryIntervals,
	key = 'orders_count',
	type = 'number',
	compare = 'previous_year',
	shift
) {
	return buildChartData(
		{ data: { totals: {}, intervals: primaryIntervals } },
		{ data: { totals: {}, intervals: secondaryIntervals } },
		{
			label: 'Custom',
			range: '',
			after: primaryIntervals[ 0 ].date_start,
			before: '',
		},
		{
			label: 'Previous period',
			range: '',
			after: secondaryIntervals[ 0 ].date_start,
			before: '',
			shift,
		},
		compare,
		key,
		'day',
		type
	);
}

function secondaryByDate( chartData, date ) {
	const entry = chartData.find( ( d ) => d.date === `${ date }T00:00:00` );
	const { labelDate, labelDateEnd, value } = entry.secondary;
	return { labelDate, labelDateEnd, value };
}

describe( 'buildChartData', () => {
	test( 'should bump up data since 29th Feb for previous year and compare by day', () => {
		const primaryData = {
			data: {
				totals: {
					orders_count: 1,
				},
				intervals: [
					generateDateInterval(
						'2020-02-28',
						'2020-02-28',
						'2020-02-28',
						{}
					),
					generateDateInterval(
						'2020-02-29',
						'2020-02-29',
						'2020-02-29',
						{ orders_count: 1 }
					),
					generateDateInterval(
						'2020-03-01',
						'2020-03-01',
						'2020-03-01',
						{}
					),
					generateDateInterval(
						'2020-03-02',
						'2020-03-02',
						'2020-03-02',
						{}
					),
				],
			},
		};

		const secondaryData = {
			data: {
				totals: {
					orders_count: 2,
				},
				intervals: [
					generateDateInterval(
						'2019-02-28',
						'2019-02-28',
						'2019-02-28',
						{ orders_count: 1 }
					),
					generateDateInterval(
						'2019-03-01',
						'2019-03-01',
						'2019-03-01',
						{ orders_count: 1 }
					),
					generateDateInterval(
						'2019-03-02',
						'2019-03-02',
						'2019-03-02',
						{}
					),
				],
			},
		};

		const primaryDatePicker = {
			label: 'Custom',
			range: 'Feb 28 - Mar 2, 2020',
			after: '2020-02-27T16:00:00.000Z',
			before: '2020-03-02T15:59:59.999Z',
		};

		const secondaryDatePicker = {
			label: 'Previous year',
			range: 'Feb 28 - Mar 2, 2019',
			after: '2019-02-27T16:00:00.000Z',
			before: '2019-03-02T15:59:59.999Z',
		};

		const chartData = buildChartData(
			primaryData,
			secondaryData,
			primaryDatePicker,
			secondaryDatePicker,
			'previous_year',
			'orders_count',
			'day'
		);

		expect( chartData ).toEqual( [
			{
				date: '2020-02-28T00:00:00',
				primary: {
					label: 'Custom (Feb 28 - Mar 2, 2020)',
					labelDate: '2020-02-28 00:00:00',
					value: 0,
				},
				secondary: {
					label: 'Previous year (Feb 28 - Mar 2, 2019)',
					labelDate: '2019-02-28 00:00:00',
					value: 1,
				},
			},
			{
				date: '2020-02-29T00:00:00',
				primary: {
					label: 'Custom (Feb 28 - Mar 2, 2020)',
					labelDate: '2020-02-29 00:00:00',
					value: 1,
				},
				secondary: {
					label: 'Previous year (Feb 28 - Mar 2, 2019)',
					labelDate: '-',
					value: 0,
				},
			},
			{
				date: '2020-03-01T00:00:00',
				primary: {
					label: 'Custom (Feb 28 - Mar 2, 2020)',
					labelDate: '2020-03-01 00:00:00',
					value: 0,
				},
				secondary: {
					label: 'Previous year (Feb 28 - Mar 2, 2019)',
					labelDate: '2019-03-01 00:00:00',
					value: 1,
				},
			},
			{
				date: '2020-03-02T00:00:00',
				primary: {
					label: 'Custom (Feb 28 - Mar 2, 2020)',
					labelDate: '2020-03-02 00:00:00',
					value: 0,
				},
				secondary: {
					label: 'Previous year (Feb 28 - Mar 2, 2019)',
					labelDate: '2019-03-02 00:00:00',
					value: 0,
				},
			},
		] );
	} );

	test( 'should not bump up data in leap year when compare by month', () => {
		const primaryData = {
			data: {
				totals: {
					orders_count: 2,
				},
				intervals: [
					generateDateInterval(
						'2020-02',
						'2020-02-01',
						'2020-02-29',
						{ orders_count: 1 }
					),
					generateDateInterval(
						'2020-03',
						'2020-03-01',
						'2020-03-31',
						{ orders_count: 1 }
					),
				],
			},
		};

		const secondaryData = {
			data: {
				totals: {
					orders_count: 2,
				},
				intervals: [
					generateDateInterval(
						'2019-02',
						'2019-02-01',
						'2019-02-28',
						{ orders_count: 1 }
					),
					generateDateInterval(
						'2019-03',
						'2019-03-01',
						'2019-03-31',
						{ orders_count: 1 }
					),
				],
			},
		};

		const primaryDatePicker = {
			label: 'Custom',
			range: 'Feb 1 - Mar 31, 2020',
			after: '2020-01-31T16:00:00.000Z',
			before: '2020-03-31T15:59:59.999Z',
		};

		const secondaryDatePicker = {
			label: 'Previous year',
			range: 'Feb 1 - Mar 31, 2019',
			after: '2019-01-31T16:00:00.000Z',
			before: '2019-03-31T15:59:59.999Z',
		};

		const chartData = buildChartData(
			primaryData,
			secondaryData,
			primaryDatePicker,
			secondaryDatePicker,
			'previous_year',
			'orders_count',
			'month'
		);

		expect( chartData ).toEqual( [
			{
				date: '2020-02-01T00:00:00',
				primary: {
					label: 'Custom (Feb 1 - Mar 31, 2020)',
					labelDate: '2020-02-01 00:00:00',
					value: 1,
				},
				secondary: {
					label: 'Previous year (Feb 1 - Mar 31, 2019)',
					labelDate: '2019-02-01 00:00:00',
					value: 1,
				},
			},
			{
				date: '2020-03-01T00:00:00',
				primary: {
					label: 'Custom (Feb 1 - Mar 31, 2020)',
					labelDate: '2020-03-01 00:00:00',
					value: 1,
				},
				secondary: {
					label: 'Previous year (Feb 1 - Mar 31, 2019)',
					labelDate: '2019-03-01 00:00:00',
					value: 1,
				},
			},
		] );
	} );

	test( 'should fold the 29th Feb of the previous year into the 28th when the primary range has no leap day', () => {
		const primary = generateDayIntervals( '2021-02-27', '2021-03-02' );
		const secondary = generateDayIntervals( '2020-02-27', '2020-03-02', {
			'2020-02-28': 5,
			'2020-02-29': 1,
			'2020-03-01': 2,
			'2020-03-02': 3,
		} );

		const chartData = buildDayChartData( primary, secondary );

		expect( chartData ).toHaveLength( 4 );
		expect( secondaryByDate( chartData, '2021-02-27' ) ).toEqual( {
			labelDate: '2020-02-27 00:00:00',
			value: 0,
		} );
		expect( secondaryByDate( chartData, '2021-02-28' ) ).toEqual( {
			labelDate: '2020-02-28 00:00:00',
			labelDateEnd: '2020-02-29 00:00:00',
			value: 6,
		} );
		expect( secondaryByDate( chartData, '2021-03-01' ) ).toEqual( {
			labelDate: '2020-03-01 00:00:00',
			value: 2,
		} );
		expect( secondaryByDate( chartData, '2021-03-02' ) ).toEqual( {
			labelDate: '2020-03-02 00:00:00',
			value: 3,
		} );
	} );

	test( 'should fold the 29th Feb of the previous year when the primary range ends in February', () => {
		const primary = generateDayIntervals( '2021-02-01', '2021-02-28' );
		const secondary = generateDayIntervals( '2020-02-01', '2020-02-29', {
			'2020-02-28': 5,
			'2020-02-29': 1,
		} );

		const chartData = buildDayChartData( primary, secondary );

		expect( chartData ).toHaveLength( 28 );
		expect( secondaryByDate( chartData, '2021-02-28' ) ).toEqual( {
			labelDate: '2020-02-28 00:00:00',
			labelDateEnd: '2020-02-29 00:00:00',
			value: 6,
		} );
	} );

	test( 'should fold the 29th Feb of the previous year when both ranges span a leap year', () => {
		// Both ranges touch 2024, but only the previous year range contains the leap day.
		const primary = generateDayIntervals( '2024-12-31', '2025-03-02' );
		const secondary = generateDayIntervals( '2023-12-31', '2024-03-02', {
			'2024-02-29': 1,
			'2024-03-01': 2,
			'2024-03-02': 3,
		} );

		const chartData = buildDayChartData( primary, secondary );

		expect( secondaryByDate( chartData, '2025-02-28' ) ).toEqual( {
			labelDate: '2024-02-28 00:00:00',
			labelDateEnd: '2024-02-29 00:00:00',
			value: 1,
		} );
		expect( secondaryByDate( chartData, '2025-03-01' ) ).toEqual( {
			labelDate: '2024-03-01 00:00:00',
			value: 2,
		} );
		expect( secondaryByDate( chartData, '2025-03-02' ) ).toEqual( {
			labelDate: '2024-03-02 00:00:00',
			value: 3,
		} );
	} );

	test.each( [
		[ 'avg_items_per_order', 'average' ],
		[ 'avg_order_value', 'currency' ],
	] )(
		'should not fold the 29th Feb of the previous year for the %s average',
		( key, type ) => {
			const primary = generateDayIntervals( '2021-02-27', '2021-03-02' );
			const secondary = generateDayIntervals(
				'2020-02-27',
				'2020-03-02',
				{
					'2020-02-28': 5,
					'2020-02-29': 1,
					'2020-03-01': 2,
					'2020-03-02': 3,
				},
				key
			);

			const chartData = buildDayChartData(
				primary,
				secondary,
				key,
				type
			);

			expect( secondaryByDate( chartData, '2021-02-28' ) ).toEqual( {
				labelDate: '2020-02-28 00:00:00',
				value: 5,
			} );
			expect( secondaryByDate( chartData, '2021-03-01' ) ).toEqual( {
				labelDate: '2020-03-01 00:00:00',
				value: 2,
			} );
			expect( secondaryByDate( chartData, '2021-03-02' ) ).toEqual( {
				labelDate: '2020-03-02 00:00:00',
				value: 3,
			} );
		}
	);

	test( 'should not fold under previous period when the comparison range has the 29th Feb', () => {
		const primary = generateDayIntervals( '2024-12-01', '2025-12-01' );
		const secondary = generateDayIntervals( '2023-12-01', '2024-11-30', {
			'2024-02-28': 5,
			'2024-02-29': 1,
			'2024-03-01': 2,
			'2024-11-30': 9,
		} );

		const chartData = buildDayChartData(
			primary,
			secondary,
			'orders_count',
			'number',
			'previous_period'
		);

		expect( secondaryByDate( chartData, '2025-02-28' ) ).toEqual( {
			labelDate: '2024-02-28 00:00:00',
			value: 5,
		} );
		expect( secondaryByDate( chartData, '2025-03-01' ) ).toEqual( {
			labelDate: '2024-02-29 00:00:00',
			value: 1,
		} );
		expect( secondaryByDate( chartData, '2025-03-02' ) ).toEqual( {
			labelDate: '2024-03-01 00:00:00',
			value: 2,
		} );
		expect( secondaryByDate( chartData, '2025-12-01' ) ).toEqual( {
			labelDate: '2024-11-30 00:00:00',
			value: 9,
		} );
	} );

	test( 'should show zero for a day missing from the comparison data without shifting the rest', () => {
		const primary = generateDayIntervals( '2021-02-27', '2021-03-02' );
		const secondary = generateDayIntervals( '2020-02-27', '2020-03-02', {
			'2020-02-27': 1,
			'2020-02-28': 2,
			'2020-02-29': 3,
			'2020-03-01': 4,
			'2020-03-02': 5,
		} ).filter(
			( interval ) => ! interval.date_start.startsWith( '2020-02-28' )
		);

		const chartData = buildDayChartData( primary, secondary );

		expect( secondaryByDate( chartData, '2021-02-27' ) ).toEqual( {
			labelDate: '2020-02-27 00:00:00',
			value: 1,
		} );
		expect( secondaryByDate( chartData, '2021-02-28' ) ).toEqual( {
			labelDate: '2020-02-28 00:00:00',
			value: 0,
		} );
		expect( secondaryByDate( chartData, '2021-03-01' ) ).toEqual( {
			labelDate: '2020-03-01 00:00:00',
			value: 4,
		} );
		expect( secondaryByDate( chartData, '2021-03-02' ) ).toEqual( {
			labelDate: '2020-03-02 00:00:00',
			value: 5,
		} );
	} );

	test( 'should pad the 29th Feb under a year shifted previous period', () => {
		// Last year 2024 compared to the previous period is the whole of 2023.
		const primary = generateDayIntervals( '2024-01-01', '2024-12-31' );
		const secondary = generateDayIntervals( '2023-01-01', '2023-12-31', {
			'2023-02-28': 5,
			'2023-03-01': 1,
			'2023-03-02': 2,
			'2023-12-31': 9,
		} );

		const chartData = buildDayChartData(
			primary,
			secondary,
			'orders_count',
			'number',
			'previous_period',
			'year'
		);

		expect( secondaryByDate( chartData, '2024-02-28' ) ).toEqual( {
			labelDate: '2023-02-28 00:00:00',
			value: 5,
		} );
		expect( secondaryByDate( chartData, '2024-02-29' ) ).toEqual( {
			labelDate: '-',
			value: 0,
		} );
		expect( secondaryByDate( chartData, '2024-03-01' ) ).toEqual( {
			labelDate: '2023-03-01 00:00:00',
			value: 1,
		} );
		expect( secondaryByDate( chartData, '2024-12-31' ) ).toEqual( {
			labelDate: '2023-12-31 00:00:00',
			value: 9,
		} );
	} );

	test( 'should leave data alone under previous period when both ranges have the 29th Feb', () => {
		const primary = generateDayIntervals( '2016-01-01', '2019-12-31', {
			'2016-02-29': 7,
		} );
		const secondary = generateDayIntervals( '2012-01-01', '2015-12-31', {
			'2012-02-28': 28,
			'2012-02-29': 29,
			'2012-03-01': 301,
		} );

		const chartData = buildDayChartData(
			primary,
			secondary,
			'orders_count',
			'number',
			'previous_period'
		);

		expect( chartData ).toHaveLength( 1461 );
		expect( secondaryByDate( chartData, '2016-02-28' ) ).toEqual( {
			labelDate: '2012-02-28 00:00:00',
			value: 28,
		} );
		expect( secondaryByDate( chartData, '2016-02-29' ) ).toEqual( {
			labelDate: '2012-02-29 00:00:00',
			value: 29,
		} );
		expect( secondaryByDate( chartData, '2016-03-01' ) ).toEqual( {
			labelDate: '2012-03-01 00:00:00',
			value: 301,
		} );
	} );

	test( 'should bump up data since 29th Feb when both ranges span a leap year', () => {
		// Both ranges touch 2024, but only the primary range contains the leap day.
		const primary = generateDayIntervals( '2024-02-27', '2025-01-02' );
		const secondary = generateDayIntervals( '2023-02-27', '2024-01-02', {
			'2023-02-28': 1,
			'2023-03-01': 2,
			'2023-03-02': 3,
		} );

		const chartData = buildDayChartData( primary, secondary );

		expect( secondaryByDate( chartData, '2024-02-28' ) ).toEqual( {
			labelDate: '2023-02-28 00:00:00',
			value: 1,
		} );
		expect( secondaryByDate( chartData, '2024-02-29' ) ).toEqual( {
			labelDate: '-',
			value: 0,
		} );
		expect( secondaryByDate( chartData, '2024-03-01' ) ).toEqual( {
			labelDate: '2023-03-01 00:00:00',
			value: 2,
		} );
		expect( secondaryByDate( chartData, '2024-03-02' ) ).toEqual( {
			labelDate: '2023-03-02 00:00:00',
			value: 3,
		} );
	} );
} );

describe( 'buildChartData across every date range shape', () => {
	// Every day gets a value only it can produce, so a chart value can be
	// traced back to the day it came from.
	const valueOf = ( day ) => Number( day.replace( /-/g, '' ) );

	const builders = {
		today: ( compare ) => getCurrentPeriod( 'day', compare ),
		yesterday: ( compare ) => getLastPeriod( 'day', compare ),
		week: ( compare ) => getCurrentPeriod( 'week', compare ),
		last_week: ( compare ) => getLastPeriod( 'week', compare ),
		month: ( compare ) => getCurrentPeriod( 'month', compare ),
		last_month: ( compare ) => getLastPeriod( 'month', compare ),
		quarter: ( compare ) => getCurrentPeriod( 'quarter', compare ),
		last_quarter: ( compare ) => getLastPeriod( 'quarter', compare ),
		year: ( compare ) => getCurrentPeriod( 'year', compare ),
		last_year: ( compare ) => getLastPeriod( 'year', compare ),
	};
	const clocks = [
		'2021-02-28T12:00:00',
		'2024-02-29T12:00:00',
		'2024-03-15T12:00:00',
		'2025-03-15T12:00:00',
		'2025-12-31T12:00:00',
		'2026-09-09T12:00:00',
	];
	const customRanges = [
		[ '2021-01-01', '2021-03-31' ],
		[ '2021-02-01', '2021-02-28' ],
		[ '2024-02-01', '2024-03-31' ],
		[ '2024-01-01', '2025-01-31' ],
		[ '2024-12-01', '2025-12-01' ],
		[ '2016-01-01', '2019-12-31' ],
	];
	const compares = [ 'previous_period', 'previous_year' ];

	function pickerFor( start, end, shift ) {
		return {
			label: '',
			range: '',
			after: start.clone().startOf( 'day' ),
			before: end.clone().startOf( 'day' ),
			shift,
		};
	}

	function daysBetween( picker ) {
		const days = [];
		const day = picker.after.clone();
		while ( ! day.isAfter( picker.before, 'day' ) ) {
			days.push( day.format( 'YYYY-MM-DD' ) );
			day.add( 1, 'days' );
		}
		return days;
	}

	function intervalsFor( picker ) {
		return daysBetween( picker ).map( ( day ) =>
			generateDateInterval( day, day, day, {
				orders_count: valueOf( day ),
			} )
		);
	}

	function problemsFor( name, primary, secondary, compare ) {
		const chartData = buildChartData(
			{ data: { totals: {}, intervals: intervalsFor( primary ) } },
			{ data: { totals: {}, intervals: intervalsFor( secondary ) } },
			primary,
			secondary,
			compare,
			'orders_count',
			'day',
			'number'
		);
		const secondaryDays = daysBetween( secondary );
		const problems = [];

		chartData.forEach( ( point ) => {
			const { labelDate, labelDateEnd, value } = point.secondary;
			let expected = 0;
			[ labelDate, labelDateEnd ]
				.filter( Boolean )
				.map( ( date ) => date.slice( 0, 10 ) )
				.forEach( ( day ) => {
					if ( secondaryDays.includes( day ) ) {
						expected += valueOf( day );
					}
				} );
			if ( value !== expected ) {
				problems.push(
					`${ name } ${
						point.date
					}: shows ${ value } under "${ labelDate }${
						labelDateEnd ? ` - ${ labelDateEnd }` : ''
					}", expected ${ expected }`
				);
			}
		} );

		// No comparison day up to the last labelled one may go missing. Under
		// a year shift the 29th Feb right after the last label is folded into
		// it, so it counts too.
		const lastLabel = (
			chartData
				.map( ( point ) => point.secondary.labelDate )
				.filter( ( labelDate ) => labelDate !== '-' )
				.sort()
				.pop() || ''
		).slice( 0, 10 );
		const yearShifted = secondary.shift === 'year';
		const expectedTotal = secondaryDays
			.filter(
				( day ) =>
					day <= lastLabel ||
					( yearShifted &&
						day.slice( 5 ) === '02-29' &&
						moment( day )
							.subtract( 1, 'days' )
							.format( 'YYYY-MM-DD' ) <= lastLabel )
			)
			.reduce( ( total, day ) => total + valueOf( day ), 0 );
		const chartTotal = chartData.reduce(
			( total, point ) => total + point.secondary.value,
			0
		);
		if ( chartTotal !== expectedTotal ) {
			problems.push(
				`${ name }: chart total ${ chartTotal }, comparison days total ${ expectedTotal }`
			);
		}

		return problems;
	}

	afterEach( () => {
		jest.useRealTimers();
	} );

	test( 'every preset at every clock shows each comparison value under its own date', () => {
		const problems = [];

		clocks.forEach( ( clock ) => {
			jest.useFakeTimers().setSystemTime( new Date( clock ) );
			Object.entries( builders ).forEach( ( [ preset, build ] ) => {
				compares.forEach( ( compare ) => {
					const range = build( compare );
					problems.push(
						...problemsFor(
							`${ preset }/${ compare } at ${ clock }`,
							pickerFor( range.primaryStart, range.primaryEnd ),
							pickerFor(
								range.secondaryStart,
								range.secondaryEnd,
								range.secondaryShift
							),
							compare
						)
					);
				} );
			} );
		} );

		expect( problems ).toEqual( [] );
	} );

	test( 'every custom range shows each comparison value under its own date', () => {
		const problems = [];

		customRanges.forEach( ( [ after, before ] ) => {
			compares.forEach( ( compare ) => {
				const { primary, secondary } = getCurrentDates( {
					period: 'custom',
					compare,
					after,
					before,
				} );
				problems.push(
					...problemsFor(
						`custom ${ after }..${ before }/${ compare }`,
						primary,
						secondary,
						compare
					)
				);
			} );
		} );

		expect( problems ).toEqual( [] );
	} );
} );
