/**
 * External dependencies
 */
import DOMPurify from 'dompurify';

/**
 * Internal dependencies
 */
import {
	sanitizeHTML,
	DEFAULT_ALLOWED_TAGS,
	DEFAULT_ALLOWED_ATTR,
} from '../index';

// Mock DOMPurify for testing
jest.mock( 'dompurify' );

describe( 'sanitizeHTML', () => {
	const mockSanitize = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();
		( DOMPurify.sanitize as jest.Mock ) = mockSanitize;
	} );

	describe( 'basic sanitization', () => {
		test( 'should sanitize HTML with default allowed tags and attributes', () => {
			const html =
				'<a href="#" target="_blank" onclick="alert(1)">Link</a><script>alert("xss")</script>';
			const expectedResult = '<a href="#" target="_blank">Link</a>';

			mockSanitize.mockReturnValue( expectedResult );

			const result = sanitizeHTML( html );

			expect( result ).toBe( expectedResult );
			expect( mockSanitize ).toHaveBeenCalledWith( html, {
				ALLOWED_TAGS: [ ...DEFAULT_ALLOWED_TAGS ],
				ALLOWED_ATTR: [ ...DEFAULT_ALLOWED_ATTR ],
			} );
		} );

		test( 'should handle empty string input', () => {
			mockSanitize.mockReturnValue( '' );

			const result = sanitizeHTML( '' );

			expect( result ).toBe( '' );
			expect( mockSanitize ).toHaveBeenCalledWith( '', {
				ALLOWED_TAGS: [ ...DEFAULT_ALLOWED_TAGS ],
				ALLOWED_ATTR: [ ...DEFAULT_ALLOWED_ATTR ],
			} );
		} );

		test( 'should handle null and undefined input', () => {
			mockSanitize.mockReturnValue( '' );

			expect( sanitizeHTML( null as unknown as string ) ).toBe( '' );
			expect( sanitizeHTML( undefined as unknown as string ) ).toBe( '' );
		} );
	} );

	describe( 'custom configuration', () => {
		test( 'should accept custom allowed tags', () => {
			const html =
				'<div class="container"><h1>Title</h1><p>Content</p></div>';
			const customConfig = {
				tags: [ 'div', 'h1', 'p' ],
			};
			const expectedResult =
				'<div class="container"><h1>Title</h1><p>Content</p></div>';

			mockSanitize.mockReturnValue( expectedResult );

			const result = sanitizeHTML( html, customConfig );

			expect( result ).toBe( expectedResult );
			expect( mockSanitize ).toHaveBeenCalledWith( html, {
				ALLOWED_TAGS: [ 'div', 'h1', 'p' ],
				ALLOWED_ATTR: [ ...DEFAULT_ALLOWED_ATTR ],
			} );
		} );

		test( 'should accept custom allowed attributes', () => {
			const html =
				'<div class="container" id="main" style="color: red;">Content</div>';
			const customConfig = {
				attr: [ 'class', 'id' ],
			};
			const expectedResult =
				'<div class="container" id="main">Content</div>';

			mockSanitize.mockReturnValue( expectedResult );

			const result = sanitizeHTML( html, customConfig );

			expect( result ).toBe( expectedResult );
			expect( mockSanitize ).toHaveBeenCalledWith( html, {
				ALLOWED_TAGS: [ ...DEFAULT_ALLOWED_TAGS ],
				ALLOWED_ATTR: [ 'class', 'id' ],
			} );
		} );

		test( 'should accept both custom tags and attributes', () => {
			const html =
				'<div class="container"><h1>Title</h1><img src="test.jpg" alt="Test" /></div>';
			const customConfig = {
				tags: [ 'div', 'h1', 'img' ],
				attr: [ 'class', 'src', 'alt' ],
			};
			const expectedResult =
				'<div class="container"><h1>Title</h1><img src="test.jpg" alt="Test" /></div>';

			mockSanitize.mockReturnValue( expectedResult );

			const result = sanitizeHTML( html, customConfig );

			expect( result ).toBe( expectedResult );
			expect( mockSanitize ).toHaveBeenCalledWith( html, {
				ALLOWED_TAGS: [ 'div', 'h1', 'img' ],
				ALLOWED_ATTR: [ 'class', 'src', 'alt' ],
			} );
		} );
	} );

	describe( 'security tests', () => {
		test( 'should remove script tags', () => {
			const html = '<p>Content</p><script>alert("xss")</script>';
			const expectedResult = '<p>Content</p>';

			mockSanitize.mockReturnValue( expectedResult );

			const result = sanitizeHTML( html );

			expect( result ).toBe( expectedResult );
		} );

		test( 'should remove dangerous attributes', () => {
			const html =
				'<a href="#" onclick="alert(1)" onmouseover="alert(2)">Link</a>';
			const expectedResult = '<a href="#">Link</a>';

			mockSanitize.mockReturnValue( expectedResult );

			const result = sanitizeHTML( html );

			expect( result ).toBe( expectedResult );
		} );

		test( 'should handle malformed HTML', () => {
			const html = '<div><p>Content</div><script>alert("xss")</p>';
			const expectedResult = '<div><p>Content</p></div>';

			mockSanitize.mockReturnValue( expectedResult );

			const result = sanitizeHTML( html );

			expect( result ).toBe( expectedResult );
		} );
	} );
} );

describe( 'trusted types policy', () => {
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
} );
