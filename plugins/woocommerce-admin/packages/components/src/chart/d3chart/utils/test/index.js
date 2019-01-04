/** @format */
/**
 * External dependencies
 */
import { utcParse as d3UTCParse } from 'd3-time-format';

/**
 * Internal dependencies
 */
import dummyOrders from './fixtures/dummy-orders';
import orderedDates from './fixtures/dummy-ordered-dates';
import orderedKeys from './fixtures/dummy-ordered-keys';
import {
	getDateSpaces,
	getOrderedKeys,
	getLineData,
	getUniqueKeys,
	getUniqueDates,
} from '../index';
import { getXLineScale } from '../scales';

const parseDate = d3UTCParse( '%Y-%m-%dT%H:%M:%S' );
const testUniqueKeys = getUniqueKeys( dummyOrders );
const testOrderedKeys = getOrderedKeys( dummyOrders, testUniqueKeys );
const testLineData = getLineData( dummyOrders, testOrderedKeys );
const testUniqueDates = getUniqueDates( testLineData, parseDate );
const testXLineScale = getXLineScale( testUniqueDates, 100 );

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
