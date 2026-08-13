/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { usePriceConstraint } from '../use-price-constraints';
import { ROUND_UP, ROUND_DOWN } from '../constants';

describe( 'usePriceConstraints', () => {
	const renderPriceConstraints = ( price ) =>
		renderHook(
			( { price: currentPrice } ) => ( {
				max: usePriceConstraint( currentPrice, 2, ROUND_UP ),
				min: usePriceConstraint( currentPrice, 2, ROUND_DOWN ),
			} ),
			{ initialProps: { price } }
		);

	it( 'max price constraint should be updated when new price is set', () => {
		const { result, rerender } = renderPriceConstraints( 1000 );

		expect( result.current.max ).toBe( 1000 );

		rerender( { price: 2000 } );

		expect( result.current.max ).toBe( 2000 );
	} );

	it( 'min price constraint should be updated when new price is set', () => {
		const { result, rerender } = renderPriceConstraints( 1000 );

		expect( result.current.min ).toBe( 1000 );

		rerender( { price: 2000 } );

		expect( result.current.min ).toBe( 2000 );
	} );

	it( 'previous price constraint should be preserved when new price is not an infinite number', () => {
		const { result, rerender } = renderPriceConstraints( 1000 );

		expect( result.current.max ).toBe( 1000 );

		rerender( { price: Infinity } );

		expect( result.current.max ).toBe( 1000 );
	} );

	it( 'max price constraint should be higher if the price is decimal', () => {
		const { result, rerender } = renderPriceConstraints( 1099 );

		expect( result.current.max ).toBe( 1100 );

		rerender( { price: 1999 } );

		expect( result.current.max ).toBe( 2000 );
	} );

	it( 'min price constraint should be lower if the price is decimal', () => {
		const { result, rerender } = renderPriceConstraints( 999 );

		expect( result.current.min ).toBe( 900 );

		rerender( { price: 1999 } );

		expect( result.current.min ).toBe( 1900 );
	} );
} );
