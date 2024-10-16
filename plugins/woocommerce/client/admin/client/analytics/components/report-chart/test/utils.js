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
