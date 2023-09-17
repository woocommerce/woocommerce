/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';
import { __experimentalRequestJetpackToken as requestJetpackToken } from '@woocommerce/ai';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { LookAndToneCompletionResponse } from '../types';
import { lookAndTone, colorPairing } from '../prompts';
import { getCompletion } from '../services';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '@woocommerce/ai', () => ( {
	__experimentalRequestJetpackToken: jest.fn(),
} ) );

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( 'getCompletion', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should successfully get completion', async () => {
		( requestJetpackToken as jest.Mock ).mockResolvedValue( {
			token: 'fake_token',
		} );
		( apiFetch as unknown as jest.Mock ).mockResolvedValue( {
			completion: JSON.stringify( { key: 'value' } ),
		} );
		const responseValidation = jest.fn( ( json ) => json );

		const result = await getCompletion( {
			queryId: 'query1',
			prompt: 'test prompt',
			responseValidation,
			retryCount: 0,
		} );

		expect( result ).toEqual( { key: 'value' } );
		expect( responseValidation ).toBeCalledWith( { key: 'value' } );
		expect( recordEvent ).toBeCalledWith(
			'customize_your_store_ai_completion_success',
			{
				query_id: 'query1',
				retry_count: 0,
			}
		);
	} );

	it( 'should handle API fetch error', async () => {
		( requestJetpackToken as jest.Mock ).mockResolvedValue( {
			token: 'fake_token',
		} );
		( apiFetch as unknown as jest.Mock ).mockRejectedValue(
			new Error( 'API error' )
		);

		await expect(
			getCompletion( {
				queryId: 'query1',
				prompt: 'test prompt',
				responseValidation: () => {},
				retryCount: 0,
			} )
		).rejects.toThrow( 'API error' );

		expect( recordEvent ).toBeCalledWith(
			'customize_your_store_ai_completion_api_error',
			{
				query_id: 'query1',
				retry_count: 0,
				error_type: 'api_request_error',
			}
		);
	} );

	it( 'should handle JSON parse error', async () => {
		( requestJetpackToken as jest.Mock ).mockResolvedValue( {
			token: 'fake_token',
		} );
		( apiFetch as unknown as jest.Mock ).mockResolvedValue( {
			completion: 'invalid json',
		} );

		await expect(
			getCompletion( {
				queryId: 'query1',
				prompt: 'test prompt',
				responseValidation: () => {},
				retryCount: 0,
			} )
		).rejects.toThrow(
			`Error validating Jetpack AI text completions response for query1`
		);

		expect( recordEvent ).toBeCalledWith(
			'customize_your_store_ai_completion_response_error',
			{
				query_id: 'query1',
				retry_count: 0,
				error_type: 'json_parse_error',
				response: 'invalid json',
			}
		);
	} );

	it( 'should handle validation error', async () => {
		( requestJetpackToken as jest.Mock ).mockResolvedValue( {
			token: 'fake_token',
		} );
		( apiFetch as unknown as jest.Mock ).mockResolvedValue( {
			completion: JSON.stringify( { key: 'invalid value' } ),
		} );
		const responseValidation = jest.fn( () => {
			throw new Error( 'Validation error' );
		} );

		await expect(
			getCompletion( {
				queryId: 'query1',
				prompt: 'test prompt',
				responseValidation,
				retryCount: 0,
			} )
		).rejects.toThrow( 'Validation error' );

		expect( recordEvent ).toBeCalledWith(
			'customize_your_store_ai_completion_response_error',
			{
				query_id: 'query1',
				retry_count: 0,
				error_type: 'valid_json_invalid_values',
				response: JSON.stringify( { key: 'invalid value' } ),
			}
		);
	} );
} );

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

describe( 'colorPairing.responseValidation', () => {
	it( 'should validate a correct color palette', () => {
		const validPalette = {
			name: 'Ancient Bronze',
			primary: '#11163d',
			secondary: '#8C8369',
			foreground: '#11163d',
			background: '#ffffff',
		};

		const parsedResult = colorPairing.responseValidation( validPalette );
		expect( parsedResult ).toEqual( validPalette );
	} );

	it( 'should fail for an incorrect name', () => {
		const invalidPalette = {
			name: 'Invalid Name',
			primary: '#11163d',
			secondary: '#8C8369',
			foreground: '#11163d',
			background: '#ffffff',
		};
		expect( () => colorPairing.responseValidation( invalidPalette ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"code\\": \\"custom\\",
		    \\"message\\": \\"Color palette not part of allowed list\\",
		    \\"path\\": [
		      \\"name\\"
		    ]
		  }
		]"
	` );
	} );

	it( 'should fail for an invalid primary color', () => {
		const invalidPalette = {
			name: 'Ancient Bronze',
			primary: 'invalidColor',
			secondary: '#8C8369',
			foreground: '#11163d',
			background: '#ffffff',
		};
		expect( () => colorPairing.responseValidation( invalidPalette ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"validation\\": \\"regex\\",
		    \\"code\\": \\"invalid_string\\",
		    \\"message\\": \\"Invalid primary color\\",
		    \\"path\\": [
		      \\"primary\\"
		    ]
		  }
		]"
	` );
	} );

	it( 'should fail for an invalid secondary color', () => {
		const invalidPalette = {
			name: 'Ancient Bronze',
			primary: '#11163d',
			secondary: 'invalidColor',
			foreground: '#11163d',
			background: '#ffffff',
		};
		expect( () => colorPairing.responseValidation( invalidPalette ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"validation\\": \\"regex\\",
		    \\"code\\": \\"invalid_string\\",
		    \\"message\\": \\"Invalid secondary color\\",
		    \\"path\\": [
		      \\"secondary\\"
		    ]
		  }
		]"
	` );
	} );

	it( 'should fail for an invalid foreground color', () => {
		const invalidPalette = {
			name: 'Ancient Bronze',
			primary: '#11163d',
			secondary: '11163d',
			foreground: '#invalid_color',
			background: '#ffffff',
		};
		expect( () => colorPairing.responseValidation( invalidPalette ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"validation\\": \\"regex\\",
		    \\"code\\": \\"invalid_string\\",
		    \\"message\\": \\"Invalid secondary color\\",
		    \\"path\\": [
		      \\"secondary\\"
		    ]
		  },
		  {
		    \\"validation\\": \\"regex\\",
		    \\"code\\": \\"invalid_string\\",
		    \\"message\\": \\"Invalid foreground color\\",
		    \\"path\\": [
		      \\"foreground\\"
		    ]
		  }
		]"
	` );
	} );

	it( 'should fail for an invalid background color', () => {
		const invalidPalette = {
			name: 'Ancient Bronze',
			primary: '#11163d',
			secondary: '#11163d',
			foreground: '#11163d',
			background: '#fffff',
		};
		expect( () => colorPairing.responseValidation( invalidPalette ) )
			.toThrowErrorMatchingInlineSnapshot( `
		"[
		  {
		    \\"validation\\": \\"regex\\",
		    \\"code\\": \\"invalid_string\\",
		    \\"message\\": \\"Invalid background color\\",
		    \\"path\\": [
		      \\"background\\"
		    ]
		  }
		]"
	` );
	} );
} );
