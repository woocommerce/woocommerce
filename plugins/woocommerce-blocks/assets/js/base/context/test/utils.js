/**
 * Internal dependencies
 */
import { assertValidContextValue } from '../utils';

describe( 'assertValidContextValue', () => {
	const contextName = 'testContext';
	const validationMap = {
		cheeseburger: {
			required: false,
			type: 'string',
		},
		amountKetchup: {
			required: true,
			type: 'number',
		},
	};
	it.each`
		testValue                                         | expectedMessage     | expectError
		${ {} }                                           | ${ 'expected' }     | ${ true }
		${ 10 }                                           | ${ 'expected' }     | ${ true }
		${ { amountKetchup: 20 } }                        | ${ 'not expected' } | ${ false }
		${ { amountKetchup: '10' } }                      | ${ 'expected' }     | ${ true }
		${ { cheeseburger: 'fries', amountKetchup: 20 } } | ${ 'not expected' } | ${ false }
	`(
		'The value of $testValue is $expectedMessage to trigger an Error',
		( { testValue, expectError } ) => {
			const invokeTest = () => {
				assertValidContextValue(
					contextName,
					validationMap,
					testValue
				);
			};
			if ( expectError ) {
				expect( invokeTest ).toThrow();
			} else {
				expect( invokeTest ).not.toThrow();
			}
		}
	);
} );
