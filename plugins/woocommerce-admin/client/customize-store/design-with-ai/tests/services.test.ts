/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import {
	parseLookAndToneCompletionResponse,
	LookAndToneCompletionResponse,
} from '../services';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

describe( 'parseLookAndToneCompletionResponse', () => {
	beforeEach( () => {
		( recordEvent as jest.Mock ).mockClear();
	} );

	it( 'should return a valid object when given valid JSON', () => {
		const validObj = {
			completion: '{"look": "Contemporary", "tone": "Neutral"}',
		};
		const result = parseLookAndToneCompletionResponse( validObj );
		const expected: LookAndToneCompletionResponse = {
			look: 'Contemporary',
			tone: 'Neutral',
		};
		expect( result ).toEqual( expected );
		expect( recordEvent ).not.toHaveBeenCalled();
	} );

	it( 'should throw an error and record an event for JSON parse error', () => {
		const invalidObj = { completion: 'invalid JSON' };
		expect( () =>
			parseLookAndToneCompletionResponse( invalidObj )
		).toThrow( 'Could not parse Look and Tone completion response.' );
		expect( recordEvent ).toHaveBeenCalledWith(
			'customize_your_store_look_and_tone_ai_completion_response_error',
			{
				error_type: 'json_parse_error',
				response: JSON.stringify( invalidObj ),
			}
		);
	} );

	it( 'should throw an error and record an event for valid JSON but invalid values', () => {
		const invalidValuesObj = {
			completion: '{"look": "Invalid", "tone": "Invalid"}',
		};
		expect( () =>
			parseLookAndToneCompletionResponse( invalidValuesObj )
		).toThrow( 'Could not parse Look and Tone completion response.' );
		expect( recordEvent ).toHaveBeenCalledWith(
			'customize_your_store_look_and_tone_ai_completion_response_error',
			{
				error_type: 'valid_json_invalid_values',
				response: JSON.stringify( invalidValuesObj ),
			}
		);
	} );
} );
