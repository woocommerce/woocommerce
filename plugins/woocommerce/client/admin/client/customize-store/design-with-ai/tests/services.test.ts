/**
 * External dependencies
 */
import { __experimentalRequestJetpackToken as requestJetpackToken } from '@woocommerce/ai';
import apiFetch from '@wordpress/api-fetch';
/**
 * Internal dependencies
 */
import { getCompletion } from '../services';
import { trackEvent } from '~/customize-store/tracking';

jest.mock( '~/customize-store/tracking', () => ( {
	trackEvent: jest.fn(),
} ) );

jest.mock( '@woocommerce/ai', () => ( {
	__experimentalRequestJetpackToken: jest.fn(),
} ) );

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

jest.mock(
	'@wordpress/edit-site/build-module/components/global-styles/global-styles-provider',
	() => ( {
		mergeBaseAndUserConfigs: jest.fn(),
	} )
);

// @ts-expect-error Mock AbortSignal.
global.AbortSignal = {
	timeout: jest.fn(),
};

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
			version: '1',
		} );

		expect( result ).toEqual( { key: 'value' } );
		expect( responseValidation ).toBeCalledWith( { key: 'value' } );
		expect( trackEvent ).toBeCalledWith(
			'customize_your_store_ai_completion_success',
			{
				query_id: 'query1',
				retry_count: 0,
				version: '1',
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
				version: '1',
			} )
		).rejects.toThrow( 'API error' );

		expect( trackEvent ).toBeCalledWith(
			'customize_your_store_ai_completion_api_error',
			{
				query_id: 'query1',
				retry_count: 0,
				error_type: 'api_request_error',
				version: '1',
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
				version: '1',
			} )
		).rejects.toThrow(
			`Error validating Jetpack AI text completions response for query1`
		);

		expect( trackEvent ).toBeCalledWith(
			'customize_your_store_ai_completion_response_error',
			{
				query_id: 'query1',
				retry_count: 0,
				error_type: 'json_parse_error',
				response: 'invalid json',
				version: '1',
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
				version: '1',
			} )
		).rejects.toThrow( 'Validation error' );

		expect( trackEvent ).toBeCalledWith(
			'customize_your_store_ai_completion_response_error',
			{
				query_id: 'query1',
				retry_count: 0,
				error_type: 'valid_json_invalid_values',
				response: JSON.stringify( { key: 'invalid value' } ),
				version: '1',
			}
		);
	} );
} );
