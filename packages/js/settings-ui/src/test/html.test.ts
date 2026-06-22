/**
 * Internal dependencies
 */
import { sanitizeSettingsHtml } from '../html';

describe( 'sanitizeSettingsHtml', () => {
	it( 'neutralizes unsafe markup in settings HTML', () => {
		const sanitized = sanitizeSettingsHtml(
			'<strong>Safe</strong><script>alert("x")</script><img src=x onerror=alert(1)><a href="javascript:alert(1)" onclick="alert(1)">Link</a><iframe src="https://example.com"></iframe>'
		);

		expect( sanitized ).toContain( '<strong>Safe</strong>' );
		expect( sanitized ).toContain( '>Link</a>' );
		expect( sanitized ).not.toContain( '<script' );
		expect( sanitized ).not.toContain( '<img' );
		expect( sanitized ).not.toContain( 'onerror' );
		expect( sanitized ).not.toContain( 'onclick' );
		expect( sanitized ).not.toContain( 'javascript:' );
		expect( sanitized ).not.toContain( '<iframe' );
	} );
} );
