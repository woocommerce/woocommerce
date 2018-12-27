/**
 * External dependencies
 *
 * @format
 */
// import { noop } from 'lodash';
import { utcParse as d3UTCParse } from 'd3-time-format';

/**
 * Internal dependencies
 */
import dummyOrders from './fixtures/dummy';
import {
	compareStrings,
	getDateSpaces,
	getOrderedKeys,
	getLineData,
	getUniqueKeys,
	getUniqueDates,
	getXScale,
	getXGroupScale,
	getXLineScale,
	getXTicks,
	getYMax,
	getYScale,
	getYTickOffset,
} from '../utils';

const orderedKeys = [
	{
		key: 'Cap',
		focus: true,
		visible: true,
		total: 34513697,
	},
	{
		key: 'T-Shirt',
		focus: true,
		visible: true,
		total: 14762281,
	},
	{
		key: 'Sunglasses',
		focus: true,
		visible: true,
		total: 12430349,
	},
	{
		key: 'Polo',
		focus: true,
		visible: true,
		total: 8712807,
	},
	{
		key: 'Hoodie',
		focus: true,
		visible: true,
		total: 6968764,
	},
];
const orderedDates = [
	'2018-05-30T00:00:00',
	'2018-05-31T00:00:00',
	'2018-06-01T00:00:00',
	'2018-06-02T00:00:00',
	'2018-06-03T00:00:00',
	'2018-06-04T00:00:00',
];
const parseDate = d3UTCParse( '%Y-%m-%dT%H:%M:%S' );
const testUniqueKeys = getUniqueKeys( dummyOrders );
const testOrderedKeys = getOrderedKeys( dummyOrders, testUniqueKeys );
const testLineData = getLineData( dummyOrders, testOrderedKeys );
const testUniqueDates = getUniqueDates( testLineData, parseDate );
const testXScale = getXScale( testUniqueDates, 100 );
const testXLineScale = getXLineScale( testUniqueDates, 100 );
const testYMax = getYMax( testLineData );
const testYScale = getYScale( 100, testYMax );

describe( 'parseDate', () => {
	it( 'correctly parse date in the expected format', () => {
		const testDate = parseDate( '2018-06-30T00:00:00' );
		const expectedDate = new Date( Date.UTC( 2018, 5, 30 ) );
		expect( testDate.getTime() ).toEqual( expectedDate.getTime() );
	} );
} );

describe( 'getUniqueKeys', () => {
	it( 'returns an array of keys excluding date', () => {
		// sort is a mutating action so we need a copy
		const testUniqueKeysClone = testUniqueKeys.slice();
		const sortedAZKeys = orderedKeys.map( d => d.key ).slice();
		expect( testUniqueKeysClone.sort() ).toEqual( sortedAZKeys.sort() );
	} );
} );

describe( 'getOrderedKeys', () => {
	it( 'returns an array of keys order by value from largest to smallest', () => {
		expect( testOrderedKeys ).toEqual( orderedKeys );
	} );
} );

describe( 'getLineData', () => {
	it( 'returns a sorted array of objects with category key', () => {
		expect( testLineData ).toBeInstanceOf( Array );
		expect( testLineData ).toHaveLength( 5 );
		expect( testLineData.map( d => d.key ) ).toEqual( orderedKeys.map( d => d.key ) );
	} );

	testLineData.forEach( d => {
		it( 'ensure a key and that the values property is an array', () => {
			expect( d ).toHaveProperty( 'key' );
			expect( d ).toHaveProperty( 'values' );
			expect( d.values ).toBeInstanceOf( Array );
		} );

		it( 'ensure all unique dates exist in values array', () => {
			const rowDates = d.values.map( row => row.date );
			expect( rowDates ).toEqual( orderedDates );
		} );

		d.values.forEach( row => {
			it( 'ensure a date property and that the values property is an array with date (parseable) and value properties', () => {
				expect( row ).toHaveProperty( 'date' );
				expect( row ).toHaveProperty( 'value' );
				expect( parseDate( row.date ) ).not.toBeNull();
				expect( typeof row.date ).toBe( 'string' );
				expect( typeof row.value ).toBe( 'number' );
			} );
		} );
	} );
} );

describe( 'getXScale', () => {
	it( 'properly scale inputs to the provided domain and range', () => {
		expect( testXScale( orderedDates[ 0 ] ) ).toEqual( 3 );
		expect( testXScale( orderedDates[ 2 ] ) ).toEqual( 35 );
		expect( testXScale( orderedDates[ orderedDates.length - 1 ] ) ).toEqual( 83 );
	} );
	it( 'properly scale inputs and test the bandwidth', () => {
		expect( testXScale.bandwidth() ).toEqual( 14 );
	} );
} );

describe( 'getXGroupScale', () => {
	it( 'properly scale inputs based on the getXScale', () => {
		const testXGroupScale = getXGroupScale( testOrderedKeys, testXScale );
		expect( testXGroupScale( orderedKeys[ 0 ].key ) ).toEqual( 2 );
		expect( testXGroupScale( orderedKeys[ 2 ].key ) ).toEqual( 6 );
		expect( testXGroupScale( orderedKeys[ orderedKeys.length - 1 ].key ) ).toEqual( 10 );
	} );
} );

describe( 'getXLineScale', () => {
	it( 'properly scale inputs for the line', () => {
		expect( testXLineScale( new Date( orderedDates[ 0 ] ) ) ).toEqual( 0 );
		expect( testXLineScale( new Date( orderedDates[ 2 ] ) ) ).toEqual( 40 );
		expect( testXLineScale( new Date( orderedDates[ orderedDates.length - 1 ] ) ) ).toEqual( 100 );
	} );
} );

describe( 'getXTicks', () => {
	describe( 'interval=day', () => {
		const uniqueDates = [
			'2018-01-01T00:00:00',
			'2018-01-02T00:00:00',
			'2018-01-03T00:00:00',
			'2018-01-04T00:00:00',
			'2018-01-05T00:00:00',
			'2018-01-06T00:00:00',
			'2018-01-07T00:00:00',
			'2018-01-08T00:00:00',
			'2018-01-09T00:00:00',
			'2018-01-10T00:00:00',
			'2018-01-11T00:00:00',
			'2018-01-12T00:00:00',
			'2018-01-13T00:00:00',
			'2018-01-14T00:00:00',
			'2018-01-15T00:00:00',
			'2018-01-16T00:00:00',
			'2018-01-17T00:00:00',
			'2018-01-18T00:00:00',
			'2018-01-19T00:00:00',
			'2018-01-20T00:00:00',
			'2018-01-21T00:00:00',
			'2018-01-22T00:00:00',
			'2018-01-23T00:00:00',
			'2018-01-24T00:00:00',
			'2018-01-25T00:00:00',
			'2018-01-26T00:00:00',
			'2018-01-27T00:00:00',
			'2018-01-28T00:00:00',
			'2018-01-29T00:00:00',
			'2018-01-30T00:00:00',
			'2018-01-31T00:00:00',
		];

		it( 'returns a subset of the uniqueDates as ticks depending on the width', () => {
			const width = 0;
			const mode = 'item-comparison';
			const interval = 'day';
			const expectedXTicks = [
				'2018-01-01T00:00:00',
				'2018-01-06T00:00:00',
				'2018-01-11T00:00:00',
				'2018-01-16T00:00:00',
				'2018-01-21T00:00:00',
				'2018-01-26T00:00:00',
				'2018-01-31T00:00:00',
			];

			const xTicks = getXTicks( uniqueDates, width, mode, interval );

			expect( xTicks ).toEqual( expectedXTicks );
		} );

		it( 'returns a tick for the first date of each month when the list of uniqueDates exceeds the threshold', () => {
			const width = 0;
			const mode = 'item-comparison';
			const interval = 'day';
			const extendedUniqueDates = [
				'2018-02-01T00:00:00',
				'2018-02-02T00:00:00',
				'2018-02-03T00:00:00',
				'2018-02-04T00:00:00',
				'2018-02-05T00:00:00',
				'2018-02-06T00:00:00',
				'2018-02-07T00:00:00',
				'2018-02-08T00:00:00',
				'2018-02-09T00:00:00',
				'2018-02-10T00:00:00',
				'2018-02-11T00:00:00',
				'2018-02-12T00:00:00',
				'2018-02-13T00:00:00',
				'2018-02-14T00:00:00',
				'2018-02-15T00:00:00',
				'2018-02-16T00:00:00',
				'2018-02-17T00:00:00',
				'2018-02-18T00:00:00',
				'2018-02-19T00:00:00',
				'2018-02-20T00:00:00',
				'2018-02-21T00:00:00',
				'2018-02-22T00:00:00',
				'2018-02-23T00:00:00',
				'2018-02-24T00:00:00',
				'2018-02-25T00:00:00',
				'2018-02-26T00:00:00',
				'2018-02-27T00:00:00',
				'2018-02-28T00:00:00',
				'2018-03-01T00:00:00',
				'2018-03-02T00:00:00',
				'2018-03-03T00:00:00',
				'2018-03-04T00:00:00',
				'2018-03-05T00:00:00',
				'2018-03-06T00:00:00',
				'2018-03-07T00:00:00',
				'2018-03-08T00:00:00',
				'2018-03-09T00:00:00',
				'2018-03-10T00:00:00',
				'2018-03-11T00:00:00',
				'2018-03-12T00:00:00',
				'2018-03-13T00:00:00',
				'2018-03-14T00:00:00',
				'2018-03-15T00:00:00',
				'2018-03-16T00:00:00',
				'2018-03-17T00:00:00',
				'2018-03-18T00:00:00',
				'2018-03-19T00:00:00',
				'2018-03-20T00:00:00',
				'2018-03-21T00:00:00',
				'2018-03-22T00:00:00',
				'2018-03-23T00:00:00',
				'2018-03-24T00:00:00',
				'2018-03-25T00:00:00',
				'2018-03-26T00:00:00',
				'2018-03-27T00:00:00',
				'2018-03-28T00:00:00',
				'2018-03-29T00:00:00',
				'2018-03-30T00:00:00',
				'2018-03-31T00:00:00',
				'2018-04-01T00:00:00',
				'2018-04-02T00:00:00',
				'2018-04-03T00:00:00',
				'2018-04-04T00:00:00',
				'2018-04-05T00:00:00',
				'2018-04-06T00:00:00',
				'2018-04-07T00:00:00',
				'2018-04-08T00:00:00',
			];
			const expectedXTicks = [
				'2018-01-01T00:00:00',
				'2018-02-01T00:00:00',
				'2018-03-01T00:00:00',
				'2018-04-01T00:00:00',
			];

			const xTicks = getXTicks( uniqueDates.concat( extendedUniqueDates ), width, mode, interval );

			expect( xTicks ).toEqual( expectedXTicks );
		} );
	} );

	describe( 'interval=hour', () => {
		const uniqueDates = [
			'2018-01-02T00:00:00',
			'2018-01-02T01:00:00',
			'2018-01-02T02:00:00',
			'2018-01-02T03:00:00',
			'2018-01-02T04:00:00',
			'2018-01-02T05:00:00',
			'2018-01-02T06:00:00',
			'2018-01-02T07:00:00',
			'2018-01-02T08:00:00',
			'2018-01-02T09:00:00',
			'2018-01-02T10:00:00',
			'2018-01-02T11:00:00',
			'2018-01-02T12:00:00',
			'2018-01-02T13:00:00',
			'2018-01-02T14:00:00',
			'2018-01-02T15:00:00',
			'2018-01-02T16:00:00',
			'2018-01-02T17:00:00',
			'2018-01-02T18:00:00',
			'2018-01-02T19:00:00',
			'2018-01-02T20:00:00',
			'2018-01-02T21:00:00',
			'2018-01-02T22:00:00',
			'2018-01-02T23:00:00',
		];

		it( 'doesn\'t return a tick for each unique date when width is not big enough', () => {
			const width = 0;
			const mode = 'item-comparison';
			const interval = 'hour';
			const expectedXTicks = [
				'2018-01-02T00:00:00',
				'2018-01-02T11:00:00',
				'2018-01-02T22:00:00',
			];

			const xTicks = getXTicks( uniqueDates, width, mode, interval );

			expect( xTicks ).toEqual( expectedXTicks );
		} );

		it( 'doesn\'t return a tick for each unique date when all dates don\'t belong to the same day', () => {
			const width = 9999;
			const mode = 'item-comparison';
			const interval = 'hour';
			const expectedXTicks = [
				'2018-01-01T23:00:00',
				'2018-01-02T01:00:00',
				'2018-01-02T03:00:00',
				'2018-01-02T05:00:00',
				'2018-01-02T07:00:00',
				'2018-01-02T09:00:00',
				'2018-01-02T11:00:00',
				'2018-01-02T13:00:00',
				'2018-01-02T15:00:00',
				'2018-01-02T17:00:00',
				'2018-01-02T19:00:00',
				'2018-01-02T21:00:00',
				'2018-01-02T23:00:00',
			];

			const xTicks = getXTicks( [ '2018-01-01T23:00:00' ].concat( uniqueDates ), width, mode, interval );

			expect( xTicks ).toEqual( expectedXTicks );
		} );

		it( 'returns a tick for each unique date when all dates are from the same day and width is big enough', () => {
			const width = 9999;
			const mode = 'item-comparison';
			const interval = 'hour';
			const expectedXTicks = uniqueDates;

			const xTicks = getXTicks( uniqueDates, width, mode, interval );

			expect( xTicks ).toEqual( expectedXTicks );
		} );
	} );
} );

describe( 'getYMax', () => {
	it( 'calculate the correct maximum y value', () => {
		expect( testYMax ).toEqual( 15000000 );
	} );
} );

describe( 'getYScale', () => {
	it( 'properly scale the y values given the height and maximum y value', () => {
		expect( testYScale( 0 ) ).toEqual( 100 );
		expect( testYScale( testYMax ) ).toEqual( 0 );
	} );
} );

describe( 'getYTickOffset', () => {
	it( 'properly scale the y values for the y-axis ticks given the height and maximum y value', () => {
		const testYTickOffset1 = getYTickOffset( 100, testYMax );
		expect( testYTickOffset1( 0 ) ).toEqual( 112 );
		expect( testYTickOffset1( testYMax ) ).toEqual( 12 );
	} );
} );

describe( 'getDateSpaces', () => {
	it( 'return an array used to space out the mouseover rectangles, used for tooltips', () => {
		const testDateSpaces = getDateSpaces( dummyOrders, testUniqueDates, 100, testXLineScale );
		expect( testDateSpaces[ 0 ].date ).toEqual( '2018-05-30T00:00:00' );
		expect( testDateSpaces[ 0 ].start ).toEqual( 0 );
		expect( testDateSpaces[ 0 ].width ).toEqual( 10 );
		expect( testDateSpaces[ 3 ].date ).toEqual( '2018-06-02T00:00:00' );
		expect( testDateSpaces[ 3 ].start ).toEqual( 50 );
		expect( testDateSpaces[ 3 ].width ).toEqual( 20 );
		expect( testDateSpaces[ testDateSpaces.length - 1 ].date ).toEqual( '2018-06-04T00:00:00' );
		expect( testDateSpaces[ testDateSpaces.length - 1 ].start ).toEqual( 90 );
		expect( testDateSpaces[ testDateSpaces.length - 1 ].width ).toEqual( 10 );
	} );
} );

describe( 'compareStrings', () => {
	it( 'return an array of unique words from s2 that dont appear in base string', () => {
		expect( compareStrings( 'Jul 2018', 'Aug 2018' ).join( ' ' ) ).toEqual( 'Aug' );
		expect( compareStrings( 'Jul 2017', 'Aug 2018' ).join( ' ' ) ).toEqual( 'Aug 2018' );
		expect( compareStrings( 'Jul 2017', 'Jul 2018' ).join( ' ' ) ).toEqual( '2018' );
		expect( compareStrings( 'Jul, 2018', 'Aug, 2018' ).join( ' ' ) ).toEqual( 'Aug' );
	} );
} );
