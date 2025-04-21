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

	test( 'should reject undefined provider', () => {
		const result =
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider();
		expect( result ).toBe( false );
		expect( console.error ).toHaveBeenCalledWith(
			'Address provider must be a valid object'
		);
	} );

	test( 'should reject null provider', () => {
		const result =
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider( null );
		expect( result ).toBe( false );
		expect( console.error ).toHaveBeenCalledWith(
			'Address provider must be a valid object'
		);
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
			'Server providers configuration is invalid'
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
			'Provider unregistered-provider not registered on server'
		);
	} );
} );
