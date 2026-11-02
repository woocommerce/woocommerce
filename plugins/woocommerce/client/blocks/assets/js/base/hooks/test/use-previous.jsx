/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { usePrevious } from '../use-previous';

describe( 'usePrevious', () => {
	const renderUsePrevious = ( testValue, validation ) =>
		renderHook(
			( props ) => usePrevious( props.testValue, props.validation ),
			{ initialProps: { testValue, validation } }
		);

	it( 'should be undefined at first pass', () => {
		const { result } = renderUsePrevious( 1 );

		expect( result.current ).toBe( undefined );
	} );

	it( 'test new and previous value', () => {
		const { result, rerender } = renderUsePrevious( 1 );

		rerender( { testValue: 2 } );
		expect( result.current ).toBe( 1 );

		rerender( { testValue: 3 } );
		expect( result.current ).toBe( 2 );
	} );

	it( 'should not update value if validation fails', () => {
		const { result, rerender } = renderUsePrevious( 1, Number.isFinite );

		rerender( { testValue: 'abc', validation: Number.isFinite } );
		expect( result.current ).toBe( 1 );

		rerender( { testValue: 3, validation: Number.isFinite } );
		expect( result.current ).toBe( 1 );
	} );
} );
