import {
	afterEach,
	beforeEach,
	describe,
	expect,
	test,
	vi,
	type Mock,
} from 'vitest';
describe( 'getTrustedTypesPolicy', () => {
	let mockCreatePolicy: Mock;
	beforeEach( () => {
		mockCreatePolicy = vi.fn();
		Object.defineProperty( window, 'trustedTypes', {
			value: {
				createPolicy: mockCreatePolicy,
			},
			writable: true,
			configurable: true,
		} );
	} );
	afterEach( () => {
		vi.resetModules();
		delete (
			window as unknown as {
				trustedTypes?: unknown;
			}
		 ).trustedTypes;
	} );
	test( 'should create trusted types policy when window.trustedTypes is available', async () => {
		const mockPolicy = {
			name: 'woocommerce-sanitize',
			createHTML: vi.fn( ( str: string ) => str ),
		};
		mockCreatePolicy.mockReturnValue( mockPolicy );
		const { getTrustedTypesPolicy } = await import(
			'../trusted-types-policy'
		);
		const policy = getTrustedTypesPolicy();
		expect( policy ).toBe( mockPolicy );
		expect( mockCreatePolicy ).toHaveBeenCalledWith(
			'woocommerce-sanitize',
			{
				createHTML: expect.any( Function ),
			}
		);
	} );
	test( 'should cache the policy instance and not create it multiple times', async () => {
		const mockPolicy = {
			name: 'woocommerce-sanitize',
			createHTML: vi.fn( ( str: string ) => str ),
		};
		mockCreatePolicy.mockReturnValue( mockPolicy );
		const { getTrustedTypesPolicy } = await import(
			'../trusted-types-policy'
		);
		const policy1 = getTrustedTypesPolicy();
		const policy2 = getTrustedTypesPolicy();
		expect( policy1 ).toBe( policy2 );
		expect( mockCreatePolicy ).toHaveBeenCalledTimes( 1 );
	} );
	test( 'should handle case when window.trustedTypes is not available', async () => {
		delete (
			window as unknown as {
				trustedTypes?: unknown;
			}
		 ).trustedTypes;
		const { getTrustedTypesPolicy } = await import(
			'../trusted-types-policy'
		);
		const policy = getTrustedTypesPolicy();
		expect( policy ).toBeNull();
	} );
	test( 'should handle policy creation errors', async () => {
		mockCreatePolicy.mockImplementation( () => {
			throw new Error( 'Creation failed' );
		} );
		const { getTrustedTypesPolicy } = await import(
			'../trusted-types-policy'
		);
		const policy = getTrustedTypesPolicy();
		expect( policy ).toBeNull();
		expect( console.warn ).toHaveBeenCalledWith(
			expect.stringContaining( 'trusted type policy:' ),
			expect.objectContaining( { message: 'Creation failed' } )
		);
	} );
	test( 'should call sanitizeHTML when createHTML is invoked', async () => {
		// Mock sanitizeHTML
		const mockSanitizeHTML = vi.fn(
			( input: string ) => `sanitized: ${ input }`
		);

		// Setup trusted types mock
		const mockPolicy = {
			name: 'woocommerce-sanitize',
			createHTML: vi.fn(),
		};
		mockCreatePolicy.mockImplementation( ( name, config ) => {
			// Capture the createHTML function that was passed
			mockPolicy.createHTML = config.createHTML;
			return mockPolicy;
		} );

		// Mock the sanitize module
		vi.doMock( '../sanitize', () => {
			const mock = {
				sanitizeHTML: mockSanitizeHTML,
			};
			return Object.defineProperties(
				{
					default: mock,
				},
				Object.getOwnPropertyDescriptors( mock )
			);
		} );
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
		vi.doUnmock( '../sanitize' );
	} );
} );
