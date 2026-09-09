/**
 * Internal dependencies
 */
import { buildChartData, dataContainsLeapYear } from '../utils';

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
	compare = 'previous_year'
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

	test( 'should leave data alone when both ranges have the 29th Feb at the same position', () => {
		// A four year custom range compared to the previous period: both sides are
		// 1461 days long and both contain a leap day at the same index.
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

describe( 'dataContainsLeapYear', () => {
	it( 'should return false when intervals are empty', () => {
		const data = {
			data: {
				intervals: [],
			},
		};
		expect( dataContainsLeapYear( data ) ).toBe( false );
	} );

	it( 'should return false when intervals are undefined', () => {
		const data = {
			data: {},
		};
		expect( dataContainsLeapYear( data ) ).toBe( false );
	} );

	it( 'should return false when interval does not include a leap year', () => {
		const data = {
			data: {
				intervals: [
					{ date_start: '2019-01-01', date_end: '2019-01-01' },
					{ date_start: '2019-12-31', date_end: '2019-12-31' },
				],
			},
		};
		expect( dataContainsLeapYear( data ) ).toBe( false );
	} );

	// Test with multiple intervals where none include a leap year
	it( 'should return false when no intervals include a leap year', () => {
		const data = {
			data: {
				intervals: [
					{ date_start: '2019-01-01', date_end: '2019-06-30' },
					{ date_start: '2019-07-01', date_end: '2019-12-31' },
				],
			},
		};
		expect( dataContainsLeapYear( data ) ).toBe( false );
	} );

	// Test with multiple intervals where one includes a leap year
	it( 'should return true when any interval includes a leap year', () => {
		const data = {
			data: {
				intervals: [
					{ date_start: '2020-01-01', date_end: '2020-01-01' },
					{ date_start: '2020-01-02', date_end: '2020-01-02' },
				],
			},
		};
		expect( dataContainsLeapYear( data ) ).toBe( true );
	} );

	// Test with malformed date formats
	it( 'should handle invalid date formats gracefully', () => {
		const data = {
			data: {
				intervals: [ { date_start: null, date_end: '2020-99-99' } ],
			},
		};
		expect( dataContainsLeapYear( data ) ).toBe( false );
	} );
} );
