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
	getOrderedKeys,
	getLineData,
	getUniqueKeys,
	getUniqueDates,
} from '../index';
import { getXGroupScale, getXScale, getXLineScale, getYMax, getYScale, getYTickOffset } from '../scales';

const parseDate = d3UTCParse( '%Y-%m-%dT%H:%M:%S' );
const testUniqueKeys = getUniqueKeys( dummyOrders );
const testOrderedKeys = getOrderedKeys( dummyOrders, testUniqueKeys );
const testLineData = getLineData( dummyOrders, testOrderedKeys );
const testUniqueDates = getUniqueDates( testLineData, parseDate );
const testXScale = getXScale( testUniqueDates, 100 );
const testXLineScale = getXLineScale( testUniqueDates, 100 );
const testYMax = getYMax( testLineData );
const testYScale = getYScale( 100, testYMax );

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
