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
	TRUSTED_POLICY_NAME,
} from '../index';
import { initializeTrustedTypesPolicy } from '../trusted-types-policy';

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
	const mockCreatePolicy = jest.fn();
	const mockSetConfig = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();

		// Mock window.trustedTypes
		Object.defineProperty( window, 'trustedTypes', {
			value: {
				createPolicy: mockCreatePolicy,
			},
			writable: true,
		} );

		// Mock DOMPurify.setConfig
		( DOMPurify.setConfig as jest.Mock ) = mockSetConfig;
	} );

	afterEach( () => {
		delete ( window as unknown as { trustedTypes?: unknown } ).trustedTypes;
	} );

	test( 'should export TRUSTED_POLICY_NAME constant', () => {
		expect( TRUSTED_POLICY_NAME ).toBe( 'woocommerce-sanitize' );
	} );

	test( 'should create trusted types policy when window.trustedTypes is available', () => {
		const mockPolicy = {
			createHTML: jest.fn( ( str: string ) => str ),
			createScript: jest.fn( ( str: string ) => str ),
			createScriptURL: jest.fn( ( str: string ) => str ),
		};

		mockCreatePolicy.mockReturnValue( mockPolicy );

		initializeTrustedTypesPolicy();

		expect( mockCreatePolicy ).toHaveBeenCalledWith( TRUSTED_POLICY_NAME, {
			createHTML: expect.any( Function ),
			createScriptURL: expect.any( Function ),
		} );

		expect( mockSetConfig ).toHaveBeenCalledWith( {
			TRUSTED_TYPES_POLICY: mockPolicy,
		} );
	} );

	test( 'should handle case when window.trustedTypes is not available', () => {
		delete ( window as unknown as { trustedTypes?: unknown } ).trustedTypes;

		// Should not throw an error
		expect( () => initializeTrustedTypesPolicy() ).not.toThrow();
	} );
} );
