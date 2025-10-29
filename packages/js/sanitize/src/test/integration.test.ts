/**
 * Internal dependencies
 */
import { sanitizeHTML } from '../index';

/**
 * Integration tests using the real DOMPurify implementation.
 * These tests verify actual HTML sanitization behavior.
 */
describe( 'sanitizeHTML integration tests', () => {
	test( 'should sanitize HTML using default config and remove dangerous tags', () => {
		const html =
			'<p>Safe content</p><a href="#test">Link</a><script>alert("xss")</script><img src="x" onerror="alert(1)">';
		const result = sanitizeHTML( html );

		expect( result ).not.toContain( '<script>' );
		expect( result ).not.toContain( 'alert("xss")' );
		expect( result ).not.toContain( 'onerror' );

		expect( result ).toContain( '<p>Safe content</p>' );
		expect( result ).toContain( '<a href="#test">Link</a>' );
	} );

	test( 'should sanitize HTML using custom config and only allow specified tags', () => {
		const html =
			'<div class="container"><h1>Title</h1><p>Content</p><a href="#">Link</a><script>alert("xss")</script>';
		const customConfig = {
			tags: [ 'div', 'h1', 'p' ],
			attr: [ 'class' ],
		};
		const result = sanitizeHTML( html, customConfig );

		expect( result ).not.toContain( '<script>' );
		expect( result ).not.toContain( 'alert("xss")' );

		expect( result ).not.toContain( '<a' );
		expect( result ).not.toContain( 'href' );

		expect( result ).toContain( '<div class="container">' );
		expect( result ).toContain( '<h1>Title</h1>' );
		expect( result ).toContain( '<p>Content</p>' );
		expect( result ).toContain( 'Link' );
	} );
} );
