/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks/dom';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { usePayoutsPage } from '../use-payouts-page';
import { getBalancesPath, getPayoutsPath } from '../api';

jest.mock( '@wordpress/api-fetch' );

const mockApiFetch = apiFetch as unknown as jest.Mock;

const emptyPage = {
	schema_version: 1,
	items: [],
	has_more: false,
	next_cursor: null,
	prev_cursor: null,
};

describe( 'finance API paths', () => {
	it( 'builds the payouts path with the page size and an optional cursor', () => {
		expect( getPayoutsPath( 'bacs', { cursor: null, perPage: 10 } ) ).toBe(
			'/wc-admin/payments/finance/providers/bacs/payouts?per_page=10'
		);
		expect(
			getPayoutsPath( 'bacs', { cursor: 'abc/def', perPage: 25 } )
		).toBe(
			'/wc-admin/payments/finance/providers/bacs/payouts?per_page=25&next_cursor=abc%2Fdef'
		);
	} );

	it( 'encodes the provider id', () => {
		expect( getBalancesPath( 'my gateway' ) ).toBe(
			'/wc-admin/payments/finance/providers/my%20gateway/balance'
		);
	} );
} );

describe( 'usePayoutsPage', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'does not fetch without a provider', () => {
		const { result } = renderHook( () =>
			usePayoutsPage( null, { cursor: null, perPage: 10 } )
		);

		expect( mockApiFetch ).not.toHaveBeenCalled();
		expect( result.current.isLoading ).toBe( false );
		expect( result.current.page ).toBeNull();
	} );

	it( 'fetches the page for the provider and cursor', async () => {
		mockApiFetch.mockResolvedValueOnce( emptyPage );

		const { result, waitForNextUpdate } = renderHook( () =>
			usePayoutsPage( 'bacs', { cursor: 'c2', perPage: 25 } )
		);

		expect( result.current.isLoading ).toBe( true );
		await waitForNextUpdate();

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc-admin/payments/finance/providers/bacs/payouts?per_page=25&next_cursor=c2',
		} );
		expect( result.current.isLoading ).toBe( false );
		expect( result.current.page ).toEqual( emptyPage );
		expect( result.current.error ).toBeNull();
	} );

	it( 'ignores the response of a superseded request', async () => {
		let resolveFirst: ( value: unknown ) => void = () => {};
		mockApiFetch
			.mockImplementationOnce(
				() =>
					new Promise( ( resolve ) => {
						resolveFirst = resolve;
					} )
			)
			.mockResolvedValueOnce( { ...emptyPage, next_cursor: 'second' } );

		const { result, rerender, waitForNextUpdate } = renderHook(
			( { providerId }: { providerId: string } ) =>
				usePayoutsPage( providerId, { cursor: null, perPage: 10 } ),
			{ initialProps: { providerId: 'bacs' } }
		);

		rerender( { providerId: 'cheque' } );
		await waitForNextUpdate();

		expect( result.current.page?.next_cursor ).toBe( 'second' );

		resolveFirst( { ...emptyPage, next_cursor: 'first' } );
		await Promise.resolve();

		expect( result.current.page?.next_cursor ).toBe( 'second' );
	} );

	it( 'exposes a normalised error', async () => {
		mockApiFetch.mockRejectedValueOnce( {
			code: 'woocommerce_rest_payments_finance_provider_error',
			message: 'The provider is unavailable.',
		} );

		const { result, waitForNextUpdate } = renderHook( () =>
			usePayoutsPage( 'bacs', { cursor: null, perPage: 10 } )
		);
		await waitForNextUpdate();

		expect( result.current.error ).toEqual( {
			code: 'woocommerce_rest_payments_finance_provider_error',
			message: 'The provider is unavailable.',
		} );
		expect( result.current.page ).toBeNull();
	} );

	it( 'falls back to a generic message for unexpected errors', async () => {
		mockApiFetch.mockRejectedValueOnce( new TypeError( '' ) );

		const { result, waitForNextUpdate } = renderHook( () =>
			usePayoutsPage( 'bacs', { cursor: null, perPage: 10 } )
		);
		await waitForNextUpdate();

		expect( result.current.error?.code ).toBe( 'unknown_error' );
		expect( result.current.error?.message ).toBe(
			'Something went wrong. Please try again.'
		);
	} );
} );
