/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { getQueryArg } from '@wordpress/url';

/**
 * Internal dependencies
 */
import taxes from '../taxes';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const mockedApiFetch = apiFetch as unknown as jest.Mock;

describe( 'taxes autocompleter', () => {
	beforeEach( () => {
		mockedApiFetch.mockReset();
		mockedApiFetch.mockResolvedValue( [] );
	} );

	const getRequestedPath = () => mockedApiFetch.mock.calls[ 0 ][ 0 ].path;

	test( 'requests the full page so every applicable tax rate can be filtered (empty search)', () => {
		taxes.options( '' );

		const path = getRequestedPath();
		expect( getQueryArg( path, 'per_page' ) ).toBe( '100' );
	} );

	test( 'requests the full page and forwards the search term', () => {
		taxes.options( 'US' );

		const path = getRequestedPath();
		expect( getQueryArg( path, 'per_page' ) ).toBe( '100' );
		expect( getQueryArg( path, 'search' ) ).toBe( 'US' );
	} );

	test( 'omits the search param when no term is provided', () => {
		taxes.options( '' );

		const path = getRequestedPath();
		expect( getQueryArg( path, 'search' ) ).toBeUndefined();
	} );

	test( 'queries the analytics taxes endpoint', () => {
		taxes.options( '' );

		expect( getRequestedPath() ).toContain( '/wc-analytics/taxes' );
	} );
} );
