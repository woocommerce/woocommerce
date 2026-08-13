/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useShallowEqual } from '../use-shallow-equal';

describe( 'useShallowEqual', () => {
	const renderShallowEqual = ( testValue ) =>
		renderHook( ( props ) => useShallowEqual( props.testValue ), {
			initialProps: { testValue },
		} );

	it.each`
		testValueA                  | aType         | testValueB                  | bType
		${ { a: 'b', foo: 'bar' } } | ${ 'object' } | ${ { foo: 'bar', a: 'b' } } | ${ 'object' }
		${ [ 'b', 'bar' ] }         | ${ 'array' }  | ${ [ 'b', 'bar' ] }         | ${ 'array' }
		${ 1 }                      | ${ 'number' } | ${ 1 }                      | ${ 'number' }
		${ '1' }                    | ${ 'string' } | ${ '1' }                    | ${ 'string' }
		${ true }                   | ${ 'bool' }   | ${ true }                   | ${ 'bool' }
	`(
		'$testValueA ($aType) and $testValueB ($bType) are expected to be equal',
		( { testValueA, testValueB } ) => {
			const { result, rerender } = renderShallowEqual( testValueA );
			expect( result.current ).toBe( testValueA );
			// do update
			rerender( { testValue: testValueB } );
			expect( result.current ).toBe( testValueA );
		}
	);

	it.each`
		testValueA                  | aType         | testValueB                  | bType
		${ { a: 'b', foo: 'bar' } } | ${ 'object' } | ${ { foo: 'bar', a: 'c' } } | ${ 'object' }
		${ [ 'b', 'bar' ] }         | ${ 'array' }  | ${ [ 'bar', 'b' ] }         | ${ 'array' }
		${ 1 }                      | ${ 'number' } | ${ '1' }                    | ${ 'string' }
		${ 1 }                      | ${ 'number' } | ${ 2 }                      | ${ 'number' }
		${ 1 }                      | ${ 'number' } | ${ true }                   | ${ 'bool' }
		${ 0 }                      | ${ 'number' } | ${ false }                  | ${ 'bool' }
	`(
		'$testValueA ($aType) and $testValueB ($bType) are expected to not be equal',
		( { testValueA, testValueB } ) => {
			const { result, rerender } = renderShallowEqual( testValueA );
			expect( result.current ).toBe( testValueA );
			// do update
			rerender( { testValue: testValueB } );
			expect( result.current ).toBe( testValueB );
		}
	);
} );
