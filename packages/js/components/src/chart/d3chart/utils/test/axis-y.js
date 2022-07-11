/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import { getYGrids } from '../axis-y';

describe( 'getYGrids', () => {
	it( 'returns a single 0 when yMax and yMin are 0', () => {
		expect( getYGrids( 0, 0, 0 ) ).toEqual( [ 0 ] );
	} );

	describe( 'positive charts', () => {
		it( 'returns decimal values when yMax is <= 1 and yMin is 0', () => {
			expect( getYGrids( 0, 1, 0.3333333333333333 ) ).toEqual( [
				0, 0.3333333333333333, 0.6666666666666666, 1,
			] );
		} );

		it( 'returns decimal values when yMax and yMin are <= 1', () => {
			expect( getYGrids( 1, 1, 0.3333333333333333 ) ).toEqual( [
				0, 0.3333333333333333, 0.6666666666666666, 1,
			] );
		} );

		it( "doesn't return decimal values when yMax is > 1", () => {
			expect( getYGrids( 0, 2, 1 ) ).toEqual( [ 0, 1, 2 ] );
		} );

		it( 'returns up to four values when yMax is a big number', () => {
			expect( getYGrids( 0, 12000, 4000 ) ).toEqual( [
				0, 4000, 8000, 12000,
			] );
		} );
	} );

	describe( 'negative charts', () => {
		it( 'returns decimal values when yMin is >= -1 and yMax is 0', () => {
			expect( getYGrids( -1, 0, 0.3333333333333333 ) ).toEqual( [
				0, -0.3333333333333333, -0.6666666666666666, -1,
			] );
		} );

		it( 'returns decimal values when yMax and yMin are >= -1', () => {
			expect( getYGrids( -1, -1, 0.3333333333333333 ) ).toEqual( [
				0, -0.3333333333333333, -0.6666666666666666, -1,
			] );
		} );

		it( "doesn't return decimal values when yMin is < -1", () => {
			expect( getYGrids( -2, 0, 1 ) ).toEqual( [ 0, -1, -2 ] );
		} );

		it( 'returns up to four values when yMin is a big negative number', () => {
			expect( getYGrids( -12000, 0, 4000 ) ).toEqual( [
				0, -4000, -8000, -12000,
			] );
		} );
	} );

	describe( 'positive & negative charts', () => {
		it( 'returns decimal values when yMax is <= 1 and yMin is 0', () => {
			expect( getYGrids( -1, 1, 0.5 ) ).toEqual( [
				0, -0.5, -1, 0.5, 1,
			] );
		} );

		it( "doesn't return decimal values when yMax is > 1", () => {
			expect( getYGrids( -2, 2, 1 ) ).toEqual( [ 0, -1, -2, 1, 2 ] );
		} );

		it( 'returns up to six values when yMax is a big number', () => {
			expect( getYGrids( -12000, 12000, 6000 ) ).toEqual( [
				0, -6000, -12000, 6000, 12000,
			] );
		} );
	} );
} );
