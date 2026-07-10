/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react-hooks/dom';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useEndpointDismiss } from '../use-endpoint-dismiss';
import { createNoticesFromResponse } from '../../lib/notices';

jest.mock( '@wordpress/api-fetch' );
jest.mock( '../../lib/notices', () => ( {
	createNoticesFromResponse: jest.fn(),
} ) );

const PATH = '/wc-admin/tax/recommendations/dismiss';

const mockApiFetch = apiFetch as unknown as jest.Mock;

describe( 'useEndpointDismiss', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'honours the initial dismissal state', () => {
		const { result } = renderHook( () => useEndpointDismiss( PATH, true ) );

		expect( result.current.isDismissed ).toBe( true );
	} );

	it( 'optimistically dismisses and POSTs to the endpoint', async () => {
		mockApiFetch.mockResolvedValueOnce( { dismissed: true } );

		const { result } = renderHook( () =>
			useEndpointDismiss( PATH, false )
		);

		await act( async () => {
			result.current.onDismiss();
		} );

		expect( result.current.isDismissed ).toBe( true );
		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: PATH,
			method: 'POST',
		} );
		expect( createNoticesFromResponse ).not.toHaveBeenCalled();
	} );

	it( 'rolls back and surfaces a notice when the request fails', async () => {
		const error = { code: 'fail', message: 'Nope' };
		mockApiFetch.mockRejectedValueOnce( error );

		const { result } = renderHook( () =>
			useEndpointDismiss( PATH, false )
		);

		await act( async () => {
			result.current.onDismiss();
		} );

		expect( result.current.isDismissed ).toBe( false );
		expect( createNoticesFromResponse ).toHaveBeenCalledWith( error );
	} );
} );
