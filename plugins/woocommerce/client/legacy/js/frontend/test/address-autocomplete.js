/**
 * @jest-environment jsdom
 */

describe( 'Address Autocomplete Provider Registration', () => {
	beforeEach( () => {
		// Reset the window object and providers before each test
		global.window = {};
		global.window.wc_checkout_params = {
			address_providers: [
				{ id: 'test-provider', name: 'Test provider' },
				{ id: 'wc-payments', name: 'WooCommerce Payments' },
			],
		};
		global.console = {
			error: jest.fn(),
		};

		// Reset the module before each test
		jest.resetModules();
		require( '../address-autocomplete' );
	} );

	test( 'should successfully register a valid provider', () => {
		const validProvider = {
			id: 'test-provider',
			canSearch: () => {},
			search: () => {},
			select: () => {},
		};

		const result =
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
				validProvider
			);
		expect( result ).toBe( true );
		expect( console.error ).not.toHaveBeenCalled();
	} );

	test( 'should reject invalid provider (null, undefined, non-object)', () => {
		const invalidProviders = [ null, undefined, 'string', 123, true ];

		invalidProviders.forEach( ( provider ) => {
			const result =
				window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
					provider
				);
			expect( result ).toBe( false );
			expect( console.error ).toHaveBeenCalledWith(
				'Error registering address provider:',
				'Address provider must be a valid object'
			);
			console.error.mockClear();
		} );
	} );

	test( 'should handle missing wc_checkout_params', () => {
		delete window.wc_checkout_params;
		const validProvider = {
			id: 'test-provider',
			canSearch: () => {},
			search: () => {},
			select: () => {},
		};

		const result =
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
				validProvider
			);
		expect( result ).toBe( false );
		expect( console.error ).toHaveBeenCalledWith(
			'Error registering address provider:',
			'Provider test-provider not registered on server'
		);
	} );

	test( 'should handle invalid address_providers type', () => {
		window.wc_checkout_params.address_providers = 'not an array';
		const validProvider = {
			id: 'test-provider',
			canSearch: () => {},
			search: () => {},
			select: () => {},
		};

		const result =
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
				validProvider
			);
		expect( result ).toBe( false );
		expect( console.error ).toHaveBeenCalledWith(
			'Error registering address provider:',
			'Provider test-provider not registered on server'
		);
	} );

	test( 'should reject provider without ID', () => {
		const invalidProvider = {
			canSearch: () => {},
			search: () => {},
			select: () => {},
		};

		const result =
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
				invalidProvider
			);
		expect( result ).toBe( false );
		expect( console.error ).toHaveBeenCalledWith(
			'Error registering address provider:',
			'Address provider must have a valid ID'
		);
	} );

	test( 'should reject provider with non-string ID', () => {
		const invalidProvider = {
			id: 123,
			canSearch: () => {},
			search: () => {},
			select: () => {},
		};

		const result =
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
				invalidProvider
			);
		expect( result ).toBe( false );
		expect( console.error ).toHaveBeenCalledWith(
			'Error registering address provider:',
			'Address provider must have a valid ID'
		);
	} );

	test( 'should reject provider without canSearch function', () => {
		const invalidProvider = {
			id: 'test-provider',
			search: () => {},
			select: () => {},
		};

		const result =
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
				invalidProvider
			);
		expect( result ).toBe( false );
		expect( console.error ).toHaveBeenCalledWith(
			'Error registering address provider:',
			'Address provider must have a canSearch function'
		);
	} );

	test( 'should reject provider without search function', () => {
		const invalidProvider = {
			id: 'test-provider',
			canSearch: () => {},
			select: () => {},
		};

		const result =
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
				invalidProvider
			);
		expect( result ).toBe( false );
		expect( console.error ).toHaveBeenCalledWith(
			'Error registering address provider:',
			'Address provider must have a search function'
		);
	} );

	test( 'should reject provider without select function', () => {
		const invalidProvider = {
			id: 'test-provider',
			canSearch: () => {},
			search: () => {},
		};

		const result =
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
				invalidProvider
			);
		expect( result ).toBe( false );
		expect( console.error ).toHaveBeenCalledWith(
			'Error registering address provider:',
			'Address provider must have a select function'
		);
	} );

	test( 'should reject provider not registered on server', () => {
		const unregisteredProvider = {
			id: 'unregistered-provider',
			canSearch: () => {},
			search: () => {},
			select: () => {},
		};

		const result =
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
				unregisteredProvider
			);
		expect( result ).toBe( false );
		expect( console.error ).toHaveBeenCalledWith(
			'Error registering address provider:',
			'Provider unregistered-provider not registered on server'
		);
	} );

	test( 'should freeze provider after successful registration', () => {
		const validProvider = {
			id: 'test-provider',
			canSearch: () => {},
			search: () => {},
			select: () => {},
		};

		const result =
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
				validProvider
			);
		expect( result ).toBe( true );

		// Verify provider is frozen
		expect(
			Object.isFrozen(
				window.wc.addressAutocomplete.providers[ 'test-provider' ]
			)
		).toBe( true );

		// Attempt to modify should throw in strict mode
		expect( () => {
			'use strict';
			window.wc.addressAutocomplete.providers[ 'test-provider' ].newProp = 'test';
		} ).toThrow( TypeError );
		
		// Verify the property wasn't added
		expect( window.wc.addressAutocomplete.providers[ 'test-provider' ].newProp ).toBeUndefined();
	} );

	test( 'should not allow duplicate provider registration', () => {
		const provider1 = {
			id: 'test-provider',
			canSearch: () => {},
			search: () => {},
			select: () => {},
		};

		const provider2 = {
			id: 'test-provider',
			canSearch: () => {
				return true;
			},
			search: () => {
				return [];
			},
			select: () => {
				return {};
			},
		};

		// Register first provider
		window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
			provider1
		);

		// Try to register second provider with same ID
		const result =
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
				provider2
			);
		expect( result ).toBe( true );

		// Verify the second provider overwrote the first
		expect(
			window.wc.addressAutocomplete.providers[
				'test-provider'
			].canSearch()
		).toBe( true );
	} );
} );
