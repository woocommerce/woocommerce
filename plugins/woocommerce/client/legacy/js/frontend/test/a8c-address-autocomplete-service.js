/**
 * @jest-environment jest-fixed-jsdom
 */

describe( 'Automattic address autocomplete service', () => {
	let provider;

	beforeEach( () => {
		jest.useFakeTimers();
		jest.resetModules();
		global.a8cAddressAutocompleteServiceKeys = {
			'test-provider': { key: 'test-key', canTelemetry: false },
		};
		window.wc = {
			addressAutocomplete: {
				registerAddressAutocompleteProvider: ( registered ) => {
					provider = registered;
				},
			},
		};
		require( '../a8c-address-autocomplete-service' );
	} );

	afterEach( () => {
		jest.useRealTimers();
		delete global.a8cAddressAutocompleteServiceKeys;
		delete global.fetch;
		delete window.wc;
	} );

	test.each( [ false, true ] )(
		'returns no suggestions without caching an invalid response (HTTP ok: %s)',
		async ( ok ) => {
			const suggestion = { id: 'address-1', label: '123 Main Street' };
			global.fetch = jest
				.fn()
				.mockResolvedValueOnce( {
					ok,
					json: async () => ( { code: 'query_too_short' } ),
				} )
				.mockResolvedValueOnce( {
					ok: true,
					json: async () => [ suggestion ],
				} );

			const failedSearch = provider.search( '123', 'US' );
			await jest.advanceTimersByTimeAsync( 300 );
			expect( await failedSearch ).toEqual( [] );

			const retriedSearch = provider.search( '123', 'US' );
			await jest.advanceTimersByTimeAsync( 300 );
			expect( await retriedSearch ).toEqual( [ suggestion ] );
			expect( global.fetch ).toHaveBeenCalledTimes( 2 );
		}
	);
} );
