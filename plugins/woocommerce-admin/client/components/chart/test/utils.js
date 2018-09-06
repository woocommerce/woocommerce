/**
 * External dependencies
 *
 * @format
 */
// import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import dummyOrders from './fixtures/dummy';
import {
	getDateSpaces,
	getOrderedKeys,
	getLineData,
	getUniqueKeys,
	getUniqueDates,
	getXScale,
	getXGroupScale,
	getXLineScale,
	getYMax,
	getYScale,
	getYTickOffset,
	parseDate,
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
const testUniqueKeys = getUniqueKeys( dummyOrders );
const testOrderedKeys = getOrderedKeys( dummyOrders, testUniqueKeys );
const testLineData = getLineData( dummyOrders, testOrderedKeys );
const testUniqueDates = getUniqueDates( testLineData );
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

describe( 'getYMax', () => {
	it( 'calculate the correct maximum y value', () => {
		expect( testYMax ).toEqual( 14139347 );
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
		const testYTickOffset1 = getYTickOffset( 100, 1, testYMax );
		expect( testYTickOffset1( 0 ) ).toEqual( 112 );
		expect( testYTickOffset1( testYMax ) ).toEqual( 12 );
		const testYTickOffset2 = getYTickOffset( 100, 2, testYMax );
		expect( testYTickOffset2( 0 ) ).toEqual( 124 );
		expect( testYTickOffset2( testYMax ) ).toEqual( 24 );
	} );
} );

describe( 'getdateSpaces', () => {
	it( 'return an array used to space out the mouseover rectangles, used for tooltips', () => {
		const testDateSpaces = getDateSpaces( testUniqueDates, 100, testXLineScale );
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
