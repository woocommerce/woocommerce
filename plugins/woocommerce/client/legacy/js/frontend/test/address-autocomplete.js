/**
 * @jest-environment jest-fixed-jsdom
 */

describe( 'Address Autocomplete Provider Registration', () => {
	beforeEach( () => {
		delete global.window.wc;
		// Reset the window object and providers before each test
		Object.assign( global.window, {
			wc_address_autocomplete_params: {
				address_providers: JSON.stringify( [
					{ id: 'test-provider', name: 'Test provider' },
					{ id: 'wc-payments', name: 'WooCommerce Payments' },
					{ id: 'provider-1', name: 'Provider 1' },
					{ id: 'provider-2', name: 'Provider 2' },
				] ),
			},
		} );

		// Reset the module before each test
		jest.resetModules();
		require( '../utils/address-autocomplete-common' );
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
		expect( console ).not.toHaveErrored();
	} );

	test( 'should reject invalid provider (null, undefined, non-object)', () => {
		const invalidProviders = [ null, undefined, 'string', 123, true ];

		invalidProviders.forEach( ( provider ) => {
			const result =
				window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
					provider
				);
			expect( result ).toBe( false );
			expect( console ).toHaveErroredWith(
				'Error registering address provider:',
				'Address provider must be a valid object'
			);
			expect( console ).toHaveErrored();
		} );
	} );

	test( 'should handle missing wc_address_autocomplete_params', () => {
		delete global.window.wc; // ensure fresh load
		global.window.wc_address_autocomplete_params = undefined;
		jest.resetModules();
		require( '../utils/address-autocomplete-common' );
		require( '../address-autocomplete' );
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
		expect( console ).toHaveErroredWith(
			'Error registering address provider:',
			'Provider test-provider not registered on server'
		);
	} );

	test( 'should handle invalid address_providers type', () => {
		delete global.window.wc; // ensure fresh load
		global.window.wc_address_autocomplete_params = undefined;
		jest.resetModules();
		require( '../utils/address-autocomplete-common' );
		require( '../address-autocomplete' );
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
		expect( console ).toHaveErroredWith(
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
		expect( console ).toHaveErroredWith(
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
		expect( console ).toHaveErroredWith(
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
		expect( console ).toHaveErroredWith(
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
		expect( console ).toHaveErroredWith(
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
		expect( console ).toHaveErroredWith(
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
		expect( console ).toHaveErroredWith(
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
			window.wc.addressAutocomplete.providers[ 'test-provider' ].newProp =
				'test';
		} ).toThrow( TypeError );

		// Verify the property wasn't added
		expect(
			window.wc.addressAutocomplete.providers[ 'test-provider' ].newProp
		).toBeUndefined();
	} );

	test( 'should not allow duplicate provider registration', () => {
		const provider1 = {
			id: 'test-provider',
			canSearch: () => false,
			search: () => [ 'original' ],
			select: () => {},
		};

		const provider2 = {
			id: 'test-provider',
			canSearch: () => true,
			search: () => [ 'duplicate' ],
			select: () => {},
		};

		// Mock console.warn to capture warning message
		const consoleSpy = jest
			.spyOn( console, 'warn' )
			.mockImplementation( () => {} );

		// Register first provider
		const firstResult =
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
				provider1
			);
		expect( firstResult ).toBe( true );

		// Try to register second provider with same ID
		const duplicateResult =
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
				provider2
			);
		expect( duplicateResult ).toBe( false );

		// Verify warning was logged
		expect( consoleSpy ).toHaveBeenCalledWith(
			'Address provider with ID "test-provider" is already registered.'
		);

		// Verify the original provider is preserved (not overwritten)
		expect(
			window.wc.addressAutocomplete.providers[
				'test-provider'
			].canSearch()
		).toBe( false );
		expect(
			window.wc.addressAutocomplete.providers[ 'test-provider' ].search()
		).toEqual( [ 'original' ] );

		consoleSpy.mockRestore();
	} );

	test( 'should allow multiple providers with different IDs', () => {
		const provider1 = {
			id: 'provider-1',
			canSearch: () => true,
			search: () => [ 'provider1-results' ],
			select: () => {},
		};

		const provider2 = {
			id: 'provider-2',
			canSearch: () => true,
			search: () => [ 'provider2-results' ],
			select: () => {},
		};

		// Register both providers
		const result1 =
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
				provider1
			);
		const result2 =
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
				provider2
			);

		expect( result1 ).toBe( true );
		expect( result2 ).toBe( true );

		// Verify both providers are registered
		expect(
			window.wc.addressAutocomplete.providers[ 'provider-1' ]
		).toBeDefined();
		expect(
			window.wc.addressAutocomplete.providers[ 'provider-2' ]
		).toBeDefined();

		// Verify they maintain their separate functionality
		expect(
			window.wc.addressAutocomplete.providers[ 'provider-1' ].search()
		).toEqual( [ 'provider1-results' ] );
		expect(
			window.wc.addressAutocomplete.providers[ 'provider-2' ].search()
		).toEqual( [ 'provider2-results' ] );
	} );
} );

describe( 'Address Suggestions Component', () => {
	let mockProvider;
	let billingAddressInput;
	let shippingAddressInput;

	beforeEach( async () => {
		// Reset DOM
		document.body.innerHTML = '';
		delete global.window.wc;

		// Mock jQuery
		global.window.jQuery = jest.fn( ( selector ) => ( {
			hasClass: jest.fn( () => false ),
			trigger: jest.fn(),
			select2: jest.fn(),
			on: jest.fn(),
		} ) );

		// Setup window object
		Object.assign( global.window, {
			DOMPurify: {
				sanitize: ( input ) => input, // No-op for testing
			},
			wc_address_autocomplete_common_params: {
				address_providers: JSON.stringify( [
					{
						id: 'test-provider',
						name: 'Test provider',
						branding_html:
							'<div class="provider-branding">Powered by Test Provider</div>',
					},
					{
						id: 'test-provider-unbranded',
						name: 'Test provider unbranded',
					},
				] ),
			},
		} );

		// Create DOM structure
		const form = document.createElement( 'form' );

		// Billing fields
		const billingCountry = document.createElement( 'select' );
		billingCountry.id = 'billing_country';
		const billingOption = document.createElement( 'option' );
		billingOption.value = 'US';
		billingOption.selected = true;
		billingCountry.appendChild( billingOption );
		billingCountry.value = 'US';

		const billingAddress1 = document.createElement( 'input' );
		billingAddress1.id = 'billing_address_1';
		billingAddress1.type = 'text';

		const billingCity = document.createElement( 'input' );
		billingCity.id = 'billing_city';
		billingCity.type = 'text';

		const billingPostcode = document.createElement( 'input' );
		billingPostcode.id = 'billing_postcode';
		billingPostcode.type = 'text';

		const billingState = document.createElement( 'input' );
		billingState.id = 'billing_state';
		billingState.type = 'text';

		// Create wrapper for billing address
		const billingWrapper = document.createElement( 'div' );
		billingWrapper.className = 'woocommerce-input-wrapper';
		billingWrapper.appendChild( billingAddress1 );

		// Shipping fields
		const shippingCountry = document.createElement( 'select' );
		shippingCountry.id = 'shipping_country';
		const shippingOption = document.createElement( 'option' );
		shippingOption.value = 'US';
		shippingOption.selected = true;
		shippingCountry.appendChild( shippingOption );
		shippingCountry.value = 'US';

		const shippingAddress1 = document.createElement( 'input' );
		shippingAddress1.id = 'shipping_address_1';
		shippingAddress1.type = 'text';

		const shippingCity = document.createElement( 'input' );
		shippingCity.id = 'shipping_city';
		shippingCity.type = 'text';

		const shippingPostcode = document.createElement( 'input' );
		shippingPostcode.id = 'shipping_postcode';
		shippingPostcode.type = 'text';

		const shippingState = document.createElement( 'input' );
		shippingState.id = 'shipping_state';
		shippingState.type = 'text';

		// Create wrapper for shipping address
		const shippingWrapper = document.createElement( 'div' );
		shippingWrapper.className = 'woocommerce-input-wrapper';
		shippingWrapper.appendChild( shippingAddress1 );

		form.appendChild( billingCountry );
		form.appendChild( billingWrapper );
		form.appendChild( billingCity );
		form.appendChild( billingPostcode );
		form.appendChild( billingState );
		form.appendChild( shippingCountry );
		form.appendChild( shippingWrapper );
		form.appendChild( shippingCity );
		form.appendChild( shippingPostcode );
		form.appendChild( shippingState );

		document.body.appendChild( form );

		billingAddressInput = billingAddress1;
		shippingAddressInput = shippingAddress1;

		// Create mock provider
		mockProvider = {
			id: 'test-provider',
			canSearch: jest.fn( ( country ) => country === 'US' ),
			search: jest.fn( async ( query, country, type ) => [
				{
					id: 'addr1',
					label: '123 Main Street, City, US',
					matchedSubstrings: [ { offset: 0, length: 3 } ],
				},
				{
					id: 'addr2',
					label: '456 Oak Avenue, Town, US',
					matchedSubstrings: [ { offset: 0, length: 3 } ],
				},
			] ),
			select: jest.fn( async ( addressId ) => ( {
				address_1: '123 Main Street',
				city: 'City',
				postcode: '12345',
				country: 'US',
				state: 'CA',
			} ) ),
		};

		// Reset modules and require fresh instance
		jest.resetModules();
		require( '../utils/address-autocomplete-common' );
		require( '../address-autocomplete' );

		// Register the mock provider
		window.wc.addressAutocomplete.registerAddressAutocompleteProvider(
			mockProvider
		);

		// Trigger DOMContentLoaded event and wait for initialization
		const event = new Event( 'DOMContentLoaded' );
		document.dispatchEvent( event );

		// Wait a bit for DOM initialization to complete
		await new Promise( ( resolve ) => setTimeout( resolve, 10 ) );
	} );

	afterEach( () => {
		jest.clearAllMocks();
		// Reset providers properly
		if ( window.wc && window.wc.addressAutocomplete ) {
			window.wc.addressAutocomplete.providers = {};
			window.wc.addressAutocomplete.activeProvider = {
				billing: null,
				shipping: null,
			};
		}
	} );

	describe( 'DOM Initialization', () => {
		test( 'should create suggestions container for address inputs', () => {
			const billingSuggestions = document.getElementById(
				'address_suggestions_billing'
			);
			const shippingSuggestions = document.getElementById(
				'address_suggestions_shipping'
			);

			expect( billingSuggestions ).toBeTruthy();
			expect( shippingSuggestions ).toBeTruthy();

			expect( billingSuggestions.className ).toBe(
				'woocommerce-address-suggestions'
			);
			expect( billingSuggestions.style.display ).toBe( 'none' );
			expect( billingSuggestions.getAttribute( 'role' ) ).toBe(
				'region'
			);
			expect( billingSuggestions.getAttribute( 'aria-live' ) ).toBe(
				'polite'
			);

			// Check suggestions list
			const billingList =
				billingSuggestions.querySelector( '.suggestions-list' );
			expect( billingList ).toBeTruthy();
			expect( billingList.getAttribute( 'role' ) ).toBe( 'listbox' );
			expect( billingList.getAttribute( 'aria-label' ) ).toBe(
				'Address suggestions'
			);

			// Check search icon container exists
			const billingIconContainer = document.querySelector(
				'.address-search-icon'
			);
			expect( billingIconContainer ).toBeTruthy();
		} );

		test( 'should set active provider based on country value', () => {
			expect( window.wc.addressAutocomplete.activeProvider.billing ).toBe(
				mockProvider
			);
			expect(
				window.wc.addressAutocomplete.activeProvider.shipping
			).toBe( mockProvider );
		} );

		test( 'should add autocomplete-available class when provider is active', () => {
			const billingWrapper = billingAddressInput.closest(
				'.woocommerce-input-wrapper'
			);
			const shippingWrapper = shippingAddressInput.closest(
				'.woocommerce-input-wrapper'
			);

			expect(
				billingWrapper.classList.contains( 'autocomplete-available' )
			).toBe( true );
			expect(
				shippingWrapper.classList.contains( 'autocomplete-available' )
			).toBe( true );
		} );
	} );

	describe( 'Active Provider Management', () => {
		test( 'should set active provider when country matches canSearch criteria', () => {
			const billingCountry = document.getElementById( 'billing_country' );
			billingCountry.value = 'US';
			billingCountry.dispatchEvent( new Event( 'change' ) );

			expect( mockProvider.canSearch ).toHaveBeenCalledWith( 'US' );
			expect( window.wc.addressAutocomplete.activeProvider.billing ).toBe(
				mockProvider
			);
		} );

		test( 'should clear active provider when country does not match canSearch criteria', () => {
			const billingCountry = document.getElementById( 'billing_country' );
			// Create new option and select it
			const frOption = document.createElement( 'option' );
			frOption.value = 'FR';
			billingCountry.appendChild( frOption );
			billingCountry.value = 'FR';
			billingCountry.dispatchEvent( new Event( 'change' ) );

			expect( mockProvider.canSearch ).toHaveBeenCalledWith( 'FR' );
			expect( window.wc.addressAutocomplete.activeProvider.billing ).toBe(
				null
			);
		} );

		test( 'should remove autocomplete-available class when no provider is active', () => {
			const billingCountry = document.getElementById( 'billing_country' );
			const billingWrapper = billingAddressInput.closest(
				'.woocommerce-input-wrapper'
			);

			billingCountry.value = 'FR';
			billingCountry.dispatchEvent( new Event( 'change' ) );

			expect(
				billingWrapper.classList.contains( 'autocomplete-available' )
			).toBe( false );
		} );

		test( 'should handle country change for both billing and shipping', () => {
			const billingCountry = document.getElementById( 'billing_country' );
			const shippingCountry =
				document.getElementById( 'shipping_country' );

			// Add FR option to billing
			const frOption = document.createElement( 'option' );
			frOption.value = 'FR';
			billingCountry.appendChild( frOption );
			billingCountry.value = 'FR';

			billingCountry.dispatchEvent( new Event( 'change' ) );
			shippingCountry.dispatchEvent( new Event( 'change' ) );

			expect( window.wc.addressAutocomplete.activeProvider.billing ).toBe(
				null
			);
			expect(
				window.wc.addressAutocomplete.activeProvider.shipping
			).toBe( mockProvider );
		} );
	} );

	describe( 'Address Suggestions Display', () => {
		test( 'should not display suggestions for input less than 3 characters', async () => {
			billingAddressInput.value = 'ab';
			billingAddressInput.dispatchEvent( new Event( 'input' ) );

			// Wait for timeout
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const suggestionsList = document.querySelector(
				'#address_suggestions_billing .suggestions-list'
			);
			expect( suggestionsList.innerHTML ).toBe( '' );
			expect( mockProvider.search ).not.toHaveBeenCalled();
		} );

		test( 'should hide suggestions when input goes from 3+ characters to less than 3', async () => {
			// First show suggestions with 3+ characters
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const suggestionsContainer = document.getElementById(
				'address_suggestions_billing'
			);
			expect( suggestionsContainer.style.display ).toBe( 'block' );

			// Now reduce to less than 3 characters
			billingAddressInput.value = '12';
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			expect( suggestionsContainer.style.display ).toBe( 'none' );
		} );

		test( 'should display suggestions for input with 3 or more characters', async () => {
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );

			// Wait for timeout and async operations
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			expect( mockProvider.search ).toHaveBeenCalledWith(
				'123',
				'US',
				'billing'
			);

			const suggestionsList = document.querySelector(
				'#address_suggestions_billing .suggestions-list'
			);
			const suggestions = suggestionsList.querySelectorAll( 'li' );

			expect( suggestions ).toHaveLength( 2 );
			expect( suggestions[ 0 ].textContent ).toContain(
				'123 Main Street'
			);
			expect( suggestions[ 1 ].textContent ).toContain(
				'456 Oak Avenue'
			);
		} );

		test( 'should highlight matched text in suggestions', async () => {
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );

			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const suggestionsList = document.querySelector(
				'#address_suggestions_billing .suggestions-list'
			);
			const firstSuggestion = suggestionsList.querySelector( 'li' );
			const strongElement = firstSuggestion.querySelector( 'strong' );

			expect( strongElement ).toBeTruthy();
			expect( strongElement.textContent ).toBe( '123' );
		} );

		test( 'should limit suggestions to maximum of 5', async () => {
			// Mock provider to return more than 5 suggestions
			mockProvider.search.mockResolvedValue(
				Array.from( { length: 10 }, ( _, i ) => ( {
					id: `addr${ i }`,
					label: `${ i } Test Street`,
					matchedSubstrings: [],
				} ) )
			);

			billingAddressInput.value = 'test';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );

			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const suggestionsList = document.querySelector(
				'#address_suggestions_billing .suggestions-list'
			);
			const suggestions = suggestionsList.querySelectorAll( 'li' );

			expect( suggestions ).toHaveLength( 5 );
		} );

		test( 'should hide suggestions when no results returned', async () => {
			mockProvider.search.mockResolvedValue( [] );

			billingAddressInput.value = 'xyz';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );

			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const suggestionsContainer = document.getElementById(
				'address_suggestions_billing'
			);
			expect( suggestionsContainer.style.display ).toBe( 'none' );
		} );

		test( 'should hide suggestions and log error when search throws exception', async () => {
			mockProvider.search.mockRejectedValue(
				new Error( 'Search failed' )
			);

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			billingAddressInput.value = 'test';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );

			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const suggestionsContainer = document.getElementById(
				'address_suggestions_billing'
			);
			expect( suggestionsContainer.style.display ).toBe( 'none' );
			expect( consoleSpy ).toHaveBeenCalledWith(
				'Address search error:',
				expect.any( Error )
			);

			consoleSpy.mockRestore();
		} );
	} );

	describe( 'Keyboard Navigation', () => {
		beforeEach( async () => {
			// Setup suggestions
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );
		} );

		test( 'should navigate down with ArrowDown key', () => {
			const suggestions = document.querySelectorAll(
				'#address_suggestions_billing .suggestions-list li'
			);

			// No suggestion should be active initially
			expect( suggestions[ 0 ].classList.contains( 'active' ) ).toBe(
				false
			);
			expect( suggestions[ 0 ].getAttribute( 'aria-selected' ) ).toBe(
				null
			);

			// Press ArrowDown
			const keydownEvent = new KeyboardEvent( 'keydown', {
				key: 'ArrowDown',
				bubbles: true,
			} );
			billingAddressInput.dispatchEvent( keydownEvent );

			// First suggestion should now be active
			expect( suggestions[ 0 ].classList.contains( 'active' ) ).toBe(
				true
			);
			expect( suggestions[ 0 ].getAttribute( 'aria-selected' ) ).toBe(
				'true'
			);
			expect( suggestions[ 1 ].classList.contains( 'active' ) ).toBe(
				false
			);
		} );

		test( 'should navigate up with ArrowUp key', () => {
			const suggestions = document.querySelectorAll(
				'#address_suggestions_billing .suggestions-list li'
			);

			// Navigate to first item first
			let keydownEvent = new KeyboardEvent( 'keydown', {
				key: 'ArrowDown',
				bubbles: true,
			} );
			billingAddressInput.dispatchEvent( keydownEvent );

			// Navigate to second item
			keydownEvent = new KeyboardEvent( 'keydown', {
				key: 'ArrowDown',
				bubbles: true,
			} );
			billingAddressInput.dispatchEvent( keydownEvent );

			// Press ArrowUp
			keydownEvent = new KeyboardEvent( 'keydown', {
				key: 'ArrowUp',
				bubbles: true,
			} );
			billingAddressInput.dispatchEvent( keydownEvent );

			// First suggestion should be active again
			expect( suggestions[ 0 ].classList.contains( 'active' ) ).toBe(
				true
			);
			expect( suggestions[ 1 ].classList.contains( 'active' ) ).toBe(
				false
			);
		} );

		test( 'should wrap around when navigating beyond bounds', () => {
			const suggestions = document.querySelectorAll(
				'#address_suggestions_billing .suggestions-list li'
			);

			// Navigate to first item
			let keydownEvent = new KeyboardEvent( 'keydown', {
				key: 'ArrowDown',
				bubbles: true,
			} );
			billingAddressInput.dispatchEvent( keydownEvent );

			// Navigate to second (last) item
			keydownEvent = new KeyboardEvent( 'keydown', {
				key: 'ArrowDown',
				bubbles: true,
			} );
			billingAddressInput.dispatchEvent( keydownEvent );

			// Navigate beyond last item - should wrap to first
			keydownEvent = new KeyboardEvent( 'keydown', {
				key: 'ArrowDown',
				bubbles: true,
			} );
			billingAddressInput.dispatchEvent( keydownEvent );

			expect( suggestions[ 0 ].classList.contains( 'active' ) ).toBe(
				true
			);
			expect( suggestions[ 1 ].classList.contains( 'active' ) ).toBe(
				false
			);
		} );

		test( 'should select address with Enter key', async () => {
			// Navigate to first suggestion first
			let keydownEvent = new KeyboardEvent( 'keydown', {
				key: 'ArrowDown',
				bubbles: true,
			} );
			billingAddressInput.dispatchEvent( keydownEvent );

			// Press Enter to select first suggestion
			keydownEvent = new KeyboardEvent( 'keydown', {
				key: 'Enter',
				bubbles: true,
			} );
			billingAddressInput.dispatchEvent( keydownEvent );

			// Wait for async operations
			await new Promise( ( resolve ) => setTimeout( resolve, 250 ) );

			expect( mockProvider.select ).toHaveBeenCalledWith( 'addr1' );

			// Suggestions should be hidden
			const suggestionsContainer = document.getElementById(
				'address_suggestions_billing'
			);
			expect( suggestionsContainer.style.display ).toBe( 'none' );
		} );

		test( 'should hide suggestions with Escape key', () => {
			const suggestionsContainer = document.getElementById(
				'address_suggestions_billing'
			);
			expect( suggestionsContainer.style.display ).toBe( 'block' );

			// Press Escape
			const keydownEvent = new KeyboardEvent( 'keydown', {
				key: 'Escape',
				bubbles: true,
			} );
			billingAddressInput.dispatchEvent( keydownEvent );

			expect( suggestionsContainer.style.display ).toBe( 'none' );
		} );

		test( 'should not handle keyboard events when suggestions are hidden', () => {
			// Hide suggestions first
			const escapeEvent = new KeyboardEvent( 'keydown', {
				key: 'Escape',
				bubbles: true,
			} );
			billingAddressInput.dispatchEvent( escapeEvent );

			// Try to navigate with ArrowDown - should not throw error
			const arrowEvent = new KeyboardEvent( 'keydown', {
				key: 'ArrowDown',
				bubbles: true,
			} );
			expect( () => {
				billingAddressInput.dispatchEvent( arrowEvent );
			} ).not.toThrow();
		} );
	} );

	describe( 'Address Selection', () => {
		test( 'should populate address fields when address is selected', async () => {
			// Setup suggestions
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			// Click on first suggestion
			const firstSuggestion = document.querySelector(
				'#address_suggestions_billing .suggestions-list li'
			);
			firstSuggestion.click();

			// Wait for async operations and timeout
			await new Promise( ( resolve ) => setTimeout( resolve, 250 ) );

			expect( mockProvider.select ).toHaveBeenCalledWith( 'addr1' );

			// Check that fields are populated
			expect( document.getElementById( 'billing_address_1' ).value ).toBe(
				'123 Main Street'
			);
			expect( document.getElementById( 'billing_city' ).value ).toBe(
				'City'
			);
			expect( document.getElementById( 'billing_postcode' ).value ).toBe(
				'12345'
			);
			expect( document.getElementById( 'billing_country' ).value ).toBe(
				'US'
			);
			expect( document.getElementById( 'billing_state' ).value ).toBe(
				'CA'
			);
		} );

		test( 'should handle partial address data from provider', async () => {
			// Mock provider to return partial data
			mockProvider.select.mockResolvedValue( {
				address_1: '123 Main Street',
				city: 'City',
				// Missing postcode, country, state
			} );

			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const firstSuggestion = document.querySelector(
				'#address_suggestions_billing .suggestions-list li'
			);
			firstSuggestion.click();

			await new Promise( ( resolve ) => setTimeout( resolve, 250 ) );

			// Only provided fields should be populated
			expect( document.getElementById( 'billing_address_1' ).value ).toBe(
				'123 Main Street'
			);
			expect( document.getElementById( 'billing_city' ).value ).toBe(
				'City'
			);
			expect( document.getElementById( 'billing_postcode' ).value ).toBe(
				''
			);
		} );

		test( 'should clear existing field values when not present in selected address data', async () => {
			// Create address_2 field since it's not in the initial setup
			const billingAddress2 = document.createElement( 'input' );
			billingAddress2.id = 'billing_address_2';
			billingAddress2.type = 'text';
			billingAddress2.value = 'Apt 101';
			document.querySelector( 'form' ).appendChild( billingAddress2 );

			// Pre-populate some fields
			document.getElementById( 'billing_city' ).value = 'Old City';
			document.getElementById( 'billing_postcode' ).value = '99999';
			document.getElementById( 'billing_state' ).value = 'TX';

			// Mock provider to return data with some fields missing
			mockProvider.select.mockResolvedValue( {
				address_1: '456 Oak Avenue',
				city: 'New City',
				country: 'US',
				// Missing address_2, postcode, and state
			} );

			billingAddressInput.value = '456';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const firstSuggestion = document.querySelector(
				'#address_suggestions_billing .suggestions-list li'
			);
			firstSuggestion.click();

			await new Promise( ( resolve ) => setTimeout( resolve, 250 ) );

			// Check that provided fields are populated
			expect( document.getElementById( 'billing_address_1' ).value ).toBe(
				'456 Oak Avenue'
			);
			expect( document.getElementById( 'billing_city' ).value ).toBe(
				'New City'
			);
			expect( document.getElementById( 'billing_country' ).value ).toBe(
				'US'
			);

			// Check that missing fields are cleared
			expect( document.getElementById( 'billing_address_2' ).value ).toBe(
				''
			);
			expect( document.getElementById( 'billing_postcode' ).value ).toBe(
				''
			);
			expect( document.getElementById( 'billing_state' ).value ).toBe(
				''
			);
		} );

		test( 'should only clear fields that exist and have values', async () => {
			// Pre-populate only some fields
			document.getElementById( 'billing_city' ).value = 'Existing City';
			document.getElementById( 'billing_postcode' ).value = '12345';

			// Mock provider to return partial data
			mockProvider.select.mockResolvedValue( {
				address_1: '789 Pine Street',
				state: 'CA',
				country: 'US',
				// Missing city and postcode
			} );

			billingAddressInput.value = '789';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const firstSuggestion = document.querySelector(
				'#address_suggestions_billing .suggestions-list li'
			);
			firstSuggestion.click();

			await new Promise( ( resolve ) => setTimeout( resolve, 250 ) );

			// Check that provided fields are populated
			expect( document.getElementById( 'billing_address_1' ).value ).toBe(
				'789 Pine Street'
			);
			expect( document.getElementById( 'billing_state' ).value ).toBe(
				'CA'
			);
			expect( document.getElementById( 'billing_country' ).value ).toBe(
				'US'
			);

			// Check that city and postcode are cleared since they had values but weren't in the response
			expect( document.getElementById( 'billing_city' ).value ).toBe(
				''
			);
			expect( document.getElementById( 'billing_postcode' ).value ).toBe(
				''
			);
		} );

		test( 'should handle provider selection errors gracefully', async () => {
			mockProvider.select.mockRejectedValue(
				new Error( 'Selection failed' )
			);

			const consoleSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );

			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const firstSuggestion = document.querySelector(
				'#address_suggestions_billing .suggestions-list li'
			);
			firstSuggestion.click();

			await new Promise( ( resolve ) => setTimeout( resolve, 250 ) );

			expect( consoleSpy ).toHaveBeenCalledWith(
				'Error selecting address from provider',
				'test-provider',
				expect.any( Error )
			);

			// Fields should remain unchanged
			expect( document.getElementById( 'billing_address_1' ).value ).toBe(
				'123'
			);

			consoleSpy.mockRestore();
		} );

		test( 'should handle invalid address data from provider', async () => {
			mockProvider.select.mockResolvedValue( null );

			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const firstSuggestion = document.querySelector(
				'#address_suggestions_billing .suggestions-list li'
			);
			firstSuggestion.click();

			await new Promise( ( resolve ) => setTimeout( resolve, 250 ) );

			// Fields should remain unchanged
			expect( document.getElementById( 'billing_address_1' ).value ).toBe(
				'123'
			);
		} );
	} );

	describe( 'Browser Autofill Management', () => {
		test( 'should disable browser autofill when suggestions are shown', async () => {
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );

			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			expect( billingAddressInput.getAttribute( 'autocomplete' ) ).toBe(
				'none'
			);
			expect( billingAddressInput.getAttribute( 'data-lpignore' ) ).toBe(
				'true'
			);
			expect( billingAddressInput.getAttribute( 'data-op-ignore' ) ).toBe(
				'true'
			);
			expect( billingAddressInput.getAttribute( 'data-1p-ignore' ) ).toBe(
				'true'
			);
		} );

		test( 'should enable browser autofill when suggestions are hidden', async () => {
			// First show suggestions
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			// Then hide them
			billingAddressInput.value = 'xy';
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			expect( billingAddressInput.getAttribute( 'autocomplete' ) ).toBe(
				'address-line1'
			);
			expect( billingAddressInput.getAttribute( 'data-lpignore' ) ).toBe(
				'false'
			);
		} );
	} );

	describe( 'Security and Sanitization', () => {
		test( 'should sanitize input values for XSS protection', async () => {
			const maliciousInput = '<script>alert("xss")</script>';
			const consoleSpy = jest
				.spyOn( console, 'warn' )
				.mockImplementation( () => {} );

			billingAddressInput.value = maliciousInput;
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );

			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			expect( consoleSpy ).toHaveBeenCalledWith(
				'Input was sanitized for security'
			);
			expect( mockProvider.search ).toHaveBeenCalledWith(
				'alert("xss")',
				'US',
				'billing'
			);

			consoleSpy.mockRestore();
		} );

		test( 'should handle invalid match data safely', async () => {
			// Mock provider to return invalid match data
			mockProvider.search.mockResolvedValue( [
				{
					id: 'addr1',
					label: '123 Main Street',
					matchedSubstrings: [
						{ offset: -1, length: 5 }, // Invalid offset
						{ offset: 50, length: 10 }, // Offset beyond string length
						{ offset: 0, length: -1 }, // Invalid length
						null, // Null match
					],
				},
			] );

			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );

			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const suggestionsList = document.querySelector(
				'#address_suggestions_billing .suggestions-list'
			);
			const firstSuggestion = suggestionsList.querySelector( 'li' );

			// Should still render the suggestion without highlighting
			expect( firstSuggestion.textContent ).toBe( '123 Main Street' );
			expect( firstSuggestion.querySelector( 'strong' ) ).toBe( null );
		} );
	} );

	describe( 'Click Outside Behavior', () => {
		test( 'should hide suggestions when clicking outside', async () => {
			// Show suggestions first
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const suggestionsContainer = document.getElementById(
				'address_suggestions_billing'
			);
			expect( suggestionsContainer.style.display ).toBe( 'block' );

			// Click outside
			const outsideElement = document.createElement( 'div' );
			document.body.appendChild( outsideElement );
			outsideElement.click();

			expect( suggestionsContainer.style.display ).toBe( 'none' );
		} );

		test( 'should not hide suggestions when clicking inside suggestions container', async () => {
			// Show suggestions first
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const suggestionsContainer = document.getElementById(
				'address_suggestions_billing'
			);
			expect( suggestionsContainer.style.display ).toBe( 'block' );

			// Click inside suggestions container
			suggestionsContainer.click();

			expect( suggestionsContainer.style.display ).toBe( 'block' );
		} );

		test( 'should not hide suggestions when clicking address input', async () => {
			// Show suggestions first
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const suggestionsContainer = document.getElementById(
				'address_suggestions_billing'
			);
			expect( suggestionsContainer.style.display ).toBe( 'block' );

			// Click on address input
			billingAddressInput.click();

			expect( suggestionsContainer.style.display ).toBe( 'block' );
		} );
	} );

	describe( 'Branding HTML', () => {
		test( 'should display branding HTML when suggestions are shown', async () => {
			// Show suggestions
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const suggestionsContainer = document.getElementById(
				'address_suggestions_billing'
			);
			const brandingElement = suggestionsContainer.querySelector(
				'.woocommerce-address-autocomplete-branding'
			);

			expect( brandingElement ).toBeTruthy();
			expect( brandingElement.innerHTML ).toBe(
				'<div class="provider-branding">Powered by Test Provider</div>'
			);
		} );

		test.skip( 'should hide branding HTML when suggestions are hidden', async () => {
			// Show suggestions first
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const suggestionsContainer = document.getElementById(
				'address_suggestions_billing'
			);
			let brandingElement = suggestionsContainer.querySelector(
				'.woocommerce-address-autocomplete-branding'
			);
			expect( brandingElement.innerHTML ).toBe(
				'<div class="provider-branding">Powered by Test Provider</div>'
			);
			expect( brandingElement.style.display ).toBe( 'flex' );

			// Hide suggestions
			billingAddressInput.value = 'xy';
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			brandingElement = suggestionsContainer.querySelector(
				'.woocommerce-address-autocomplete-branding'
			);
			// Element should still exist but be hidden
			expect( brandingElement ).toBeTruthy();
			expect( brandingElement.style.display ).toBe( 'none' );
		} );

		test( 'should not create branding element when provider has no branding_html', async () => {
			// Re-initialize the module
			jest.resetModules();
			window.wc.addressAutocomplete.providers = [];
			require( '../address-autocomplete' );

			// Re-register provider
			window.wc.addressAutocomplete.registerAddressAutocompleteProvider( {
				search: mockProvider,
				select: mockProvider,
				canSearch: mockProvider,
				id: 'mock-provider-unbranded',
			} );

			// Trigger DOMContentLoaded again
			const event = new Event( 'DOMContentLoaded' );
			document.dispatchEvent( event );
			await new Promise( ( resolve ) => setTimeout( resolve, 10 ) );

			// Show suggestions
			billingAddressInput.value = '456';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const suggestionsContainer = document.getElementById(
				'address_suggestions_billing'
			);
			const brandingElement = suggestionsContainer.querySelector(
				'.woocommerce-address-autocomplete-branding'
			);

			// Branding element should not be created when there's no branding_html
			expect( brandingElement ).toBeFalsy();
		} );

		test( 'should reuse existing branding element on subsequent searches', async () => {
			// First search
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const suggestionsContainer = document.getElementById(
				'address_suggestions_billing'
			);
			const firstBrandingElement = suggestionsContainer.querySelector(
				'.woocommerce-address-autocomplete-branding'
			);

			// Clear and search again
			billingAddressInput.value = '12';
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			billingAddressInput.value = '456';
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const secondBrandingElement = suggestionsContainer.querySelector(
				'.woocommerce-address-autocomplete-branding'
			);

			// Should be the same element
			expect( secondBrandingElement ).toBe( firstBrandingElement );
			expect( secondBrandingElement.innerHTML ).toBe(
				'<div class="provider-branding">Powered by Test Provider</div>'
			);
		} );

		test( 'should remove branding element when country changes', async () => {
			// Show suggestions first
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const suggestionsContainer = document.getElementById(
				'address_suggestions_billing'
			);
			let brandingElement = suggestionsContainer.querySelector(
				'.woocommerce-address-autocomplete-branding'
			);
			expect( brandingElement ).toBeTruthy();

			// Change country
			const billingCountry = document.getElementById( 'billing_country' );
			const frOption = document.createElement( 'option' );
			frOption.value = 'FR';
			billingCountry.appendChild( frOption );
			billingCountry.value = 'FR';
			billingCountry.dispatchEvent( new Event( 'change' ) );

			// Branding element should be removed completely
			brandingElement = suggestionsContainer.querySelector(
				'.woocommerce-address-autocomplete-branding'
			);
			expect( brandingElement ).toBeFalsy();
		} );

		test( 'should display branding HTML for both billing and shipping if DOMPurify is present', async () => {
			// Show suggestions for billing
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );
			window.DOMPurify = { sanitize: ( html ) => html }; // Mock DOMPurify

			const billingSuggestionsContainer = document.getElementById(
				'address_suggestions_billing'
			);
			const billingBrandingElement =
				billingSuggestionsContainer.querySelector(
					'.woocommerce-address-autocomplete-branding'
				);

			expect( billingBrandingElement ).toBeTruthy();
			expect( billingBrandingElement.innerHTML ).toBe(
				'<div class="provider-branding">Powered by Test Provider</div>'
			);

			// Show suggestions for shipping
			shippingAddressInput.value = '456';
			shippingAddressInput.focus();
			shippingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const shippingSuggestionsContainer = document.getElementById(
				'address_suggestions_shipping'
			);
			const shippingBrandingElement =
				shippingSuggestionsContainer.querySelector(
					'.woocommerce-address-autocomplete-branding'
				);

			expect( shippingBrandingElement ).toBeTruthy();
			expect( shippingBrandingElement.innerHTML ).toBe(
				'<div class="provider-branding">Powered by Test Provider</div>'
			);
		} );

		test( 'should not display branding HTML for both billing and shipping if DOMPurify is not present', async () => {
			delete window.DOMPurify;
			// Show suggestions for billing
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );
			const billingSuggestionsContainer = document.getElementById(
				'address_suggestions_billing'
			);
			const billingBrandingElement =
				billingSuggestionsContainer.querySelector(
					'.woocommerce-address-autocomplete-branding'
				);

			expect( billingBrandingElement ).toBeNull();

			// Show suggestions for shipping
			shippingAddressInput.value = '456';
			shippingAddressInput.focus();
			shippingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const shippingSuggestionsContainer = document.getElementById(
				'address_suggestions_shipping'
			);
			const shippingBrandingElement =
				shippingSuggestionsContainer.querySelector(
					'.woocommerce-address-autocomplete-branding'
				);

			expect( shippingBrandingElement ).toBeNull();
		} );
	} );

	describe( 'Blur Event Behavior', () => {
		test( 'should hide suggestions when input loses focus', async () => {
			// Show suggestions first
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const suggestionsContainer = document.getElementById(
				'address_suggestions_billing'
			);
			expect( suggestionsContainer.style.display ).toBe( 'block' );

			// Blur the input
			billingAddressInput.dispatchEvent( new Event( 'blur' ) );

			// Wait for blur timeout
			await new Promise( ( resolve ) => setTimeout( resolve, 250 ) );

			expect( suggestionsContainer.style.display ).toBe( 'none' );
		} );

		test( 'should not refocus input when blurred with suggestions active', async () => {
			// Show suggestions first
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			const suggestionsContainer = document.getElementById(
				'address_suggestions_billing'
			);
			expect( suggestionsContainer.style.display ).toBe( 'block' );

			// Create another element to focus
			const otherElement = document.createElement( 'input' );
			document.body.appendChild( otherElement );

			// Blur the address input and focus the other element
			billingAddressInput.blur();
			otherElement.focus();

			// Wait for blur timeout
			await new Promise( ( resolve ) => setTimeout( resolve, 250 ) );

			// The other element should still be focused (address input shouldn't refocus)
			expect( document.activeElement ).toBe( otherElement );
			expect( suggestionsContainer.style.display ).toBe( 'none' );

			document.body.removeChild( otherElement );
		} );

		test( 'should not have blur event listener when suggestions are not shown', () => {
			// No suggestions should be shown initially
			const suggestionsContainer = document.getElementById(
				'address_suggestions_billing'
			);
			expect( suggestionsContainer.style.display ).toBe( 'none' );

			// Blur the input - should not cause any issues
			expect( () => {
				billingAddressInput.dispatchEvent( new Event( 'blur' ) );
			} ).not.toThrow();
		} );

		test( 'should enable browser autofill without refocusing when suggestions are hidden via blur', async () => {
			// Show suggestions first
			billingAddressInput.value = '123';
			billingAddressInput.focus();
			billingAddressInput.dispatchEvent( new Event( 'input' ) );
			await new Promise( ( resolve ) => setTimeout( resolve, 150 ) );

			// Verify autofill is disabled
			expect( billingAddressInput.getAttribute( 'autocomplete' ) ).toBe(
				'none'
			);

			// Blur the input
			billingAddressInput.dispatchEvent( new Event( 'blur' ) );

			// Wait for blur timeout
			await new Promise( ( resolve ) => setTimeout( resolve, 250 ) );

			// Autofill should be re-enabled
			expect( billingAddressInput.getAttribute( 'autocomplete' ) ).toBe(
				'address-line1'
			);
			expect( billingAddressInput.getAttribute( 'data-lpignore' ) ).toBe(
				'false'
			);
		} );
	} );
} );
