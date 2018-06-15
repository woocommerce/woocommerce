/**
 * External dependencies
 *
 * @format
 */
// import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import { dummyOrders } from '../dummy';
import {
	parseDate,
	getUniqueKeys,
	getOrderedKeys,
	getLineData,
	getXScale,
	getXGroupScale,
	getXLineScale,
	getColorScale,
	getYMax,
} from '../utils';

const orderedKeys = [ 'Cap', 'T-Shirt', 'Sunglasses', 'Polo', 'Hoodie' ];
const orderedDates = [
	'2018-05-30',
	'2018-05-31',
	'2018-06-01',
	'2018-06-02',
	'2018-06-03',
	'2018-06-04',
];
const testLineData = getLineData( dummyOrders );

describe( 'parseDate', () => {
	it( 'correctly parse date in the expected format', () => {
		const testDate = parseDate( '2018-06-30' );
		const expectedDate = new Date( Date.UTC( 2018, 5, 30 ) );
		expect( testDate.getTime() ).toEqual( expectedDate.getTime() );
	} );
} );

describe( 'getUniqueKeys', () => {
	it( 'returns an array of keys excluding date', () => {
		const testUniqueKeys = getUniqueKeys( dummyOrders );
		const sortedAZKeys = orderedKeys.slice();
		expect( testUniqueKeys.sort() ).toEqual( sortedAZKeys.sort() );
	} );
} );

describe( 'getOrderedKeys', () => {
	it( 'returns an array of keys order by value from largest to smallest', () => {
		const testKeys = getOrderedKeys( dummyOrders );
		expect( testKeys ).toEqual( orderedKeys );
	} );
} );

describe( 'getLineData', () => {
	it( 'returns a sorted array of objects with category key', () => {
		expect( testLineData ).toBeInstanceOf( Array );
		expect( testLineData ).toHaveLength( 5 );
		expect( testLineData.map( d => d.key ) ).toEqual( orderedKeys );
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
		const testXScale = getXScale( dummyOrders, { width: 100 } );
		expect( testXScale( orderedDates[ 0 ] ) ).toEqual( 3 );
		expect( testXScale( orderedDates[ 2 ] ) ).toEqual( 35 );
		expect( testXScale( orderedDates[ orderedDates.length - 1 ] ) ).toEqual( 83 );
	} );
	it( 'properly scale inputs and test the bandwidth', () => {
		const testXScale = getXScale( dummyOrders, { width: 100 } );
		expect( testXScale.bandwidth() ).toEqual( 14 );
	} );
} );

describe( 'getXGroupScale', () => {
	it( 'properly scale inputs based on the getXScale', () => {
		const testXGroupScale = getXGroupScale( dummyOrders, { width: 100 } );
		expect( testXGroupScale( orderedKeys[ 0 ] ) ).toEqual( 2 );
		expect( testXGroupScale( orderedKeys[ 2 ] ) ).toEqual( 6 );
		expect( testXGroupScale( orderedKeys[ orderedKeys.length - 1 ] ) ).toEqual( 10 );
	} );
} );

describe( 'getXLineScale', () => {
	it( 'properly scale inputs for the line', () => {
		const testXLineScale = getXLineScale( dummyOrders, { width: 100 } );
		expect( testXLineScale( new Date( orderedDates[ 0 ] ) ) ).toEqual( 0 );
		expect( testXLineScale( new Date( orderedDates[ 2 ] ) ) ).toEqual( 40 );
		expect( testXLineScale( new Date( orderedDates[ orderedDates.length - 1 ] ) ) ).toEqual( 100 );
	} );
} );

describe( 'getColorScale', () => {
	it( 'properly scale product keys into a range of colors', () => {
		const testColorScale = getColorScale( dummyOrders );
		orderedKeys.map( d => testColorScale( d ) ); // without this it fails! why? how?
		expect( testColorScale( orderedKeys[ 0 ] ) ).toEqual( 0 );
		expect( testColorScale( orderedKeys[ 2 ] ) ).toEqual( 0.5 );
		expect( testColorScale( orderedKeys[ orderedKeys.length - 1 ] ) ).toEqual( 1 );
	} );
} );

describe( 'getYMax', () => {
	it( 'calculate the correct maximum y value', () => {
		const testYMax = getYMax( dummyOrders );
		expect( testYMax ).toEqual( 14139347 );
	} );
} );
