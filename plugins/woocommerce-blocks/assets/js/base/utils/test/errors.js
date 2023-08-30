/**
 * Internal dependencies
 */
import { formatError } from '../errors';

describe( 'formatError', () => {
	const mockResponseBody = JSON.stringify( { message: 'Lorem Ipsum' } );
	const mockMalformedJson = '{ "message": "Lorem Ipsum"';

	test( 'should format general errors', async () => {
		const error = await formatError( {
			message: 'Lorem Ipsum',
		} );
		const expectedError = {
			message: 'Lorem Ipsum',
			type: 'general',
		};

		expect( error ).toEqual( expectedError );
	} );

	test( 'should format API errors', async () => {
		const mockResponse = new Response( mockResponseBody, { status: 400 } );

		const error = await formatError( mockResponse );
		const expectedError = {
			message: 'Lorem Ipsum',
			type: 'api',
		};

		expect( error ).toEqual( expectedError );
	} );

	test( 'should format JSON parse errors', async () => {
		const mockResponse = new Response( mockMalformedJson, { status: 400 } );

		const error = await formatError( mockResponse );
		const expectedError = {
			message:
				'invalid json response body at  reason: Unexpected end of JSON input',
			type: 'general',
		};

		expect( error ).toEqual( expectedError );
	} );
} );
