/**
 * External dependencies
 */
import TestRenderer, { act } from 'react-test-renderer';

/**
 * Internal dependencies
 */
import { useShallowEqual } from '../use-shallow-equal';

describe( 'useShallowEqual', () => {
	const TestComponent = ( { testValue } ) => {
		const newValue = useShallowEqual( testValue );
		return <div newValue={ newValue } />;
	};
	let renderer;
	beforeEach( () => ( renderer = null ) );
	it.each`
		testValueA                | aType       | testValueB                | bType       | expectEqual
		${{ a: 'b', foo: 'bar' }} | ${'object'} | ${{ foo: 'bar', a: 'b' }} | ${'object'} | ${true}
		${{ a: 'b', foo: 'bar' }} | ${'object'} | ${{ foo: 'bar', a: 'c' }} | ${'object'} | ${false}
		${[ 'b', 'bar' ]}         | ${'array'}  | ${[ 'b', 'bar' ]}         | ${'array'}  | ${true}
		${[ 'b', 'bar' ]}         | ${'array'}  | ${[ 'bar', 'b' ]}         | ${'array'}  | ${false}
		${1}                      | ${'number'} | ${1}                      | ${'number'} | ${true}
		${1}                      | ${'number'} | ${'1'}                    | ${'string'} | ${false}
		${'1'}                    | ${'string'} | ${'1'}                    | ${'string'} | ${true}
		${1}                      | ${'number'} | ${2}                      | ${'number'} | ${false}
		${1}                      | ${'number'} | ${true}                   | ${'bool'}   | ${false}
		${0}                      | ${'number'} | ${false}                  | ${'bool'}   | ${false}
		${true}                   | ${'bool'}   | ${true}                   | ${'bool'}   | ${true}
	`(
		'$testValueA ($aType) and $testValueB ($bType) are expected to be equal ($expectEqual)',
		( { testValueA, testValueB, expectEqual } ) => {
			let testPropValue;
			act( () => {
				renderer = TestRenderer.create(
					<TestComponent testValue={ testValueA } />
				);
			} );
			testPropValue = renderer.root.findByType( 'div' ).props.newValue;
			expect( testPropValue ).toBe( testValueA );
			// do update
			act( () => {
				renderer.update( <TestComponent testValue={ testValueB } /> );
			} );
			testPropValue = renderer.root.findByType( 'div' ).props.newValue;
			if ( expectEqual ) {
				expect( testPropValue ).toBe( testValueA );
			} else {
				expect( testPropValue ).toBe( testValueB );
			}
		}
	);
} );
