/**
 * Internal dependencies
 */
import { LookAndToneCompletionResponse } from '../../types';
import { lookAndTone } from '..';

describe( 'parseLookAndToneCompletionResponse', () => {
	it( 'should return a valid object when given valid JSON', () => {
		const validObj = JSON.parse(
			'{"look": "Contemporary", "tone": "Neutral"}'
		);
		const result = lookAndTone.responseValidation( validObj );
		const expected: LookAndToneCompletionResponse = {
			look: 'Contemporary',
			tone: 'Neutral',
		};
		expect( result ).toEqual( expected );
	} );

	it( 'should throw an error and record an event for valid JSON but invalid values', () => {
		const invalidValuesObj = {
			completion: '{"look": "Invalid", "tone": "Invalid"}',
		};
		expect( () =>
			lookAndTone.responseValidation( invalidValuesObj )
		).toThrow( 'Invalid values in Look and Tone completion response' );
	} );
} );
