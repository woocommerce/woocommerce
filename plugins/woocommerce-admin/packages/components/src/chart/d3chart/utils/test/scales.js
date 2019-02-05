/** @format */
/**
 * External dependencies
 */
import { utcParse as d3UTCParse } from 'd3-time-format';
import { scaleBand, scaleLinear, scaleTime } from 'd3-scale';

/**
 * Internal dependencies
 */
import dummyOrders from './fixtures/dummy-orders';
import {
	getOrderedKeys,
	getLineData,
	getUniqueKeys,
	getUniqueDates,
} from '../index';
import { getXGroupScale, getXScale, getXLineScale, getYMax, getYScale, getYTickOffset } from '../scales';

jest.mock( 'd3-scale', () => ( {
	...require.requireActual( 'd3-scale' ),
	scaleBand: jest.fn().mockReturnValue( {
		bandwidth: jest.fn().mockReturnThis(),
		domain: jest.fn().mockReturnThis(),
		padding: jest.fn().mockReturnThis(),
		paddingInner: jest.fn().mockReturnThis(),
		range: jest.fn().mockReturnThis(),
		rangeRound: jest.fn().mockReturnThis(),
	} ),
	scaleLinear: jest.fn().mockReturnValue( {
		domain: jest.fn().mockReturnThis(),
		rangeRound: jest.fn().mockReturnThis(),
	} ),
	scaleTime: jest.fn().mockReturnValue( {
		domain: jest.fn().mockReturnThis(),
		rangeRound: jest.fn().mockReturnThis(),
	} ),
} ) );

const testUniqueKeys = getUniqueKeys( dummyOrders );
const testOrderedKeys = getOrderedKeys( dummyOrders, testUniqueKeys );
const testLineData = getLineData( dummyOrders, testOrderedKeys );

describe( 'X scales', () => {
	const parseDate = d3UTCParse( '%Y-%m-%dT%H:%M:%S' );
	const testUniqueDates = getUniqueDates( testLineData, parseDate );

	describe( 'getXScale', () => {
		it( 'creates band scale with correct parameters', () => {
			getXScale( testUniqueDates, 100 );

			expect( scaleBand().domain ).toHaveBeenLastCalledWith( testUniqueDates );
			expect( scaleBand().range ).toHaveBeenLastCalledWith( [ 0, 100 ] );
			expect( scaleBand().paddingInner ).toHaveBeenLastCalledWith( 0.1 );
		} );

		it( 'creates band scale with correct paddingInner parameter when it\'s in compact mode', () => {
			getXScale( testUniqueDates, 100, true );

			expect( scaleBand().paddingInner ).toHaveBeenLastCalledWith( 0 );
		} );
	} );

	describe( 'getXGroupScale', () => {
		const testXScale = getXScale( testUniqueDates, 100 );

		it( 'creates band scale with correct parameters', () => {
			getXGroupScale( testOrderedKeys, testXScale );
			const filteredOrderedKeys = [ 'Cap', 'T-Shirt', 'Sunglasses', 'Polo', 'Hoodie' ];

			expect( scaleBand().domain ).toHaveBeenLastCalledWith( filteredOrderedKeys );
			expect( scaleBand().range ).toHaveBeenLastCalledWith( [ 0, 100 ] );
			expect( scaleBand().padding ).toHaveBeenLastCalledWith( 0.07 );
		} );

		it( 'creates band scale with correct padding parameter when it\'s in compact mode', () => {
			getXGroupScale( testOrderedKeys, testXScale, true );

			expect( scaleBand().padding ).toHaveBeenLastCalledWith( 0 );
		} );
	} );

	describe( 'getXLineScale', () => {
		it( 'creates time scale with correct parameters', () => {
			getXLineScale( testUniqueDates, 100 );

			expect( scaleTime().domain ).toHaveBeenLastCalledWith( [
				new Date( '2018-05-30T00:00:00' ),
				new Date( '2018-06-04T00:00:00' ),
			] );
			expect( scaleTime().rangeRound ).toHaveBeenLastCalledWith( [ 0, 100 ] );
		} );
	} );
} );

describe( 'Y scales', () => {
	describe( 'getYMax', () => {
		it( 'calculate the correct maximum y value', () => {
			expect( getYMax( testLineData ) ).toEqual( 15000000 );
		} );
	} );

	describe( 'getYScale', () => {
		it( 'creates linear scale with correct parameters', () => {
			getYScale( 100, 15000000 );

			expect( scaleLinear().domain ).toHaveBeenLastCalledWith( [ 0, 15000000 ] );
			expect( scaleLinear().rangeRound ).toHaveBeenLastCalledWith( [ 100, 0 ] );
		} );

		it( 'avoids the domain starting and ending at the same point when yMax is 0', () => {
			getYScale( 100, 0 );

			const args = scaleLinear().domain.mock.calls;
			const lastArgs = args[ args.length - 1 ][ 0 ];
			expect( lastArgs[ 0 ] ).toBeLessThan( lastArgs[ 1 ] );
		} );
	} );

	describe( 'getYTickOffset', () => {
		it( 'creates linear scale with correct parameters', () => {
			getYTickOffset( 100, 15000000 );

			expect( scaleLinear().domain ).toHaveBeenLastCalledWith( [ 0, 15000000 ] );
			expect( scaleLinear().rangeRound ).toHaveBeenLastCalledWith( [ 112, 12 ] );
		} );

		it( 'avoids the domain starting and ending at the same point when yMax is 0', () => {
			getYTickOffset( 100, 0 );

			const args = scaleLinear().domain.mock.calls;
			const lastArgs = args[ args.length - 1 ][ 0 ];
			expect( lastArgs[ 0 ] ).toBeLessThan( lastArgs[ 1 ] );
		} );
	} );
} );
