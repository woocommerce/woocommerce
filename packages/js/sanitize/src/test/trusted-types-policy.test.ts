describe( 'getTrustedTypesPolicy', () => {
	test( 'should create trusted types policy when window.trustedTypes is available', async () => {
		const mockCreatePolicy = jest.fn();
		const mockPolicy = {
			name: 'woocommerce-sanitize',
			createHTML: jest.fn( ( str: string ) => str ),
			createScript: jest.fn( ( str: string ) => str ),
			createScriptURL: jest.fn( ( str: string ) => str ),
		};

		mockCreatePolicy.mockReturnValue( mockPolicy );

		Object.defineProperty( window, 'trustedTypes', {
			value: {
				createPolicy: mockCreatePolicy,
			},
			writable: true,
			configurable: true,
		} );

		jest.resetModules();

		const { getTrustedTypesPolicy } = await import(
			'../trusted-types-policy'
		);
		const policy = getTrustedTypesPolicy();

		expect( policy ).toBe( mockPolicy );
		expect( mockCreatePolicy ).toHaveBeenCalledWith(
			'woocommerce-sanitize',
			{
				createHTML: expect.any( Function ),
				createScriptURL: expect.any( Function ),
			}
		);

		delete ( window as unknown as { trustedTypes?: unknown } ).trustedTypes;
	} );

	test( 'should cache the policy instance and not create it multiple times', async () => {
		const mockCreatePolicy = jest.fn();
		const mockPolicy = {
			name: 'woocommerce-sanitize',
			createHTML: jest.fn( ( str: string ) => str ),
			createScript: jest.fn( ( str: string ) => str ),
			createScriptURL: jest.fn( ( str: string ) => str ),
		};

		mockCreatePolicy.mockReturnValue( mockPolicy );

		Object.defineProperty( window, 'trustedTypes', {
			value: {
				createPolicy: mockCreatePolicy,
			},
			writable: true,
			configurable: true,
		} );

		jest.resetModules();

		const { getTrustedTypesPolicy } = await import(
			'../trusted-types-policy'
		);
		const policy1 = getTrustedTypesPolicy();
		const policy2 = getTrustedTypesPolicy();

		expect( policy1 ).toBe( policy2 );
		expect( mockCreatePolicy ).toHaveBeenCalledTimes( 1 );

		delete ( window as unknown as { trustedTypes?: unknown } ).trustedTypes;
	} );

	test( 'should handle case when window.trustedTypes is not available', async () => {
		delete ( window as unknown as { trustedTypes?: unknown } ).trustedTypes;

		jest.resetModules();

		const { getTrustedTypesPolicy } = await import(
			'../trusted-types-policy'
		);
		const policy = getTrustedTypesPolicy();

		expect( policy ).toBeNull();
	} );

	test( 'should call sanitizeHTML when createHTML is invoked', async () => {
		// Mock sanitizeHTML
		const mockSanitizeHTML = jest.fn(
			( input: string ) => `sanitized: ${ input }`
		);

		// Setup trusted types mock
		const mockPolicy = {
			name: 'woocommerce-sanitize',
			createHTML: jest.fn(),
			createScript: jest.fn(),
			createScriptURL: jest.fn(),
		};

		const mockCreatePolicy = jest.fn( ( name, config ) => {
			// Capture the createHTML function that was passed
			mockPolicy.createHTML = config.createHTML;
			return mockPolicy;
		} );

		Object.defineProperty( window, 'trustedTypes', {
			value: {
				createPolicy: mockCreatePolicy,
			},
			writable: true,
			configurable: true,
		} );

		// Mock the sanitize module
		jest.doMock( '../sanitize', () => ( {
			sanitizeHTML: mockSanitizeHTML,
		} ) );

		jest.resetModules();

		const { getTrustedTypesPolicy } = await import(
			'../trusted-types-policy'
		);
		const policy = getTrustedTypesPolicy();

		// Now call createHTML on the policy
		const testInput = '<script>alert("xss")</script><p>Hello</p>';
		const result = policy?.createHTML( testInput );

		// Verify sanitizeHTML was called with the input
		expect( mockSanitizeHTML ).toHaveBeenCalledWith( testInput );
		expect( result ).toBe( 'sanitized: ' + testInput );

		// Cleanup
		jest.dontMock( '../sanitize' );
		delete ( window as unknown as { trustedTypes?: unknown } ).trustedTypes;
	} );
} );
