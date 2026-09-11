/**
 * External dependencies
 */
import moment from 'moment';
import {
	getAllowedIntervalsForQuery,
	getCurrentDates,
	getCurrentPeriod,
	getLastPeriod,
	getPreviousDate,
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
	type = 'number'
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
			label: 'Previous year',
			range: '',
			after: secondaryIntervals[ 0 ].date_start,
			before: '',
			shift: 'year',
		},
		'previous_year',
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

	test.each( [
		[ 'avg_items_per_order', 'average' ],
		[ 'avg_order_value', 'currency' ],
		[ 'conversion_rate', 'percent' ],
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
	// Zones on both hemispheres, so a preset crosses a DST change in some
	// clock whichever direction the clocks move. A store without a zone keeps
	// the plain browser time path covered.
	const storeTimeZones = [
		undefined,
		'America/New_York',
		'Australia/Sydney',
	];

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

	const originalSettings = global.window.wcSettings;

	afterEach( () => {
		jest.useRealTimers();
		global.window.wcSettings = originalSettings;
	} );

	test( 'every preset at every clock and store time zone shows each comparison value under its own date', () => {
		const problems = [];

		storeTimeZones.forEach( ( timeZone ) => {
			global.window.wcSettings = { ...originalSettings, timeZone };
			clocks.forEach( ( clock ) => {
				jest.useFakeTimers().setSystemTime( new Date( clock ) );
				Object.entries( builders ).forEach( ( [ preset, build ] ) => {
					compares.forEach( ( compare ) => {
						const range = build( compare );
						problems.push(
							...problemsFor(
								`${ preset }/${ compare } at ${ clock } in ${
									timeZone || 'browser time'
								}`,
								pickerFor(
									range.primaryStart,
									range.primaryEnd
								),
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
		} );

		expect( problems ).toEqual( [] );
	} );

	test( 'pins a comparison value to a hardcoded calendar day', () => {
		jest.useFakeTimers().setSystemTime( new Date( '2026-09-09T12:00:00' ) );
		const range = builders.last_year( 'previous_period' );
		const primary = pickerFor( range.primaryStart, range.primaryEnd );
		const secondary = pickerFor(
			range.secondaryStart,
			range.secondaryEnd,
			range.secondaryShift
		);
		const chartData = buildChartData(
			{ data: { totals: {}, intervals: intervalsFor( primary ) } },
			{ data: { totals: {}, intervals: intervalsFor( secondary ) } },
			primary,
			secondary,
			'previous_period',
			'orders_count',
			'day',
			'number'
		);

		expect( secondaryByDate( chartData, '2025-03-01' ) ).toEqual( {
			labelDate: '2024-03-01 00:00:00',
			value: 20240301,
		} );
		expect( secondaryByDate( chartData, '2025-12-31' ) ).toEqual( {
			labelDate: '2024-12-31 00:00:00',
			value: 20241231,
		} );
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

	// Intervals coarser than a day are matched by position, so every point
	// must be labelled with the interval it was actually plotted from.
	function bucketIntervals( start, end, interval ) {
		const intervals = [];
		let bucketStart = start.clone();
		while ( ! bucketStart.isAfter( end ) ) {
			const nextStart = bucketStart
				.clone()
				.startOf( interval )
				.add( 1, interval );
			intervals.push( {
				date_start: bucketStart.format( 'YYYY-MM-DD HH:mm:ss' ),
				date_end: moment
					.min( nextStart.clone().subtract( 1, 'seconds' ), end )
					.format( 'YYYY-MM-DD HH:mm:ss' ),
				subtotals: {
					orders_count: Number( bucketStart.format( 'YYYYMMDDHH' ) ),
				},
			} );
			bucketStart = nextStart;
		}
		return intervals;
	}

	test( 'every preset at every coarser interval labels the interval it plots', () => {
		const problems = [];

		clocks.forEach( ( clock ) => {
			jest.useFakeTimers().setSystemTime( new Date( clock ) );
			Object.entries( builders ).forEach( ( [ preset, build ] ) => {
				compares.forEach( ( compare ) => {
					const range = build( compare );
					const primary = {
						label: '',
						range: '',
						after: range.primaryStart,
						before: range.primaryEnd,
					};
					const secondary = {
						label: '',
						range: '',
						after: range.secondaryStart,
						before: range.secondaryEnd,
						shift: range.secondaryShift,
					};
					getAllowedIntervalsForQuery( { period: preset, compare } )
						.filter( ( interval ) => interval !== 'day' )
						.forEach( ( interval ) => {
							const secondaryIntervals = bucketIntervals(
								secondary.after,
								secondary.before,
								interval
							);
							const chartData = buildChartData(
								{
									data: {
										totals: {},
										intervals: bucketIntervals(
											primary.after,
											primary.before,
											interval
										),
									},
								},
								{
									data: {
										totals: {},
										intervals: secondaryIntervals,
									},
								},
								primary,
								secondary,
								compare,
								'orders_count',
								interval,
								'number'
							);
							chartData.forEach( ( point, index ) => {
								const plotted = secondaryIntervals[ index ];
								// Positional intervals keep the label they always had:
								// the compare based one, with no shift applied.
								const expectedLabel = getPreviousDate(
									point.primary.labelDate,
									primary.after,
									secondary.after,
									compare,
									interval
								).format( 'YYYY-MM-DD HH:mm:ss' );
								const expectedValue = plotted
									? plotted.subtotals.orders_count
									: 0;
								if (
									point.secondary.labelDate !==
										expectedLabel ||
									point.secondary.value !== expectedValue
								) {
									problems.push(
										`${ preset }/${ compare }/${ interval } at ${ clock } ${ point.date }: label ${ point.secondary.labelDate }, value ${ point.secondary.value }, expected ${ expectedLabel }`
									);
								}
							} );
						} );
				} );
			} );
		} );

		expect( problems ).toEqual( [] );
	} );
} );
