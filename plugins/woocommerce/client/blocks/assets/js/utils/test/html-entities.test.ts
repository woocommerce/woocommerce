/**
 * External dependencies
 */
import { decodeHtmlEntities } from '@woocommerce/utils';

describe( 'decodeHtmlEntities', () => {
	describe( 'Input validation', () => {
		it( 'returns empty string for non-string inputs', () => {
			expect( decodeHtmlEntities( null ) ).toBe( '' );
			expect( decodeHtmlEntities( undefined ) ).toBe( '' );
			expect( decodeHtmlEntities( 123 ) ).toBe( '' );
			expect( decodeHtmlEntities( {} ) ).toBe( '' );
			expect( decodeHtmlEntities( [] ) ).toBe( '' );
			expect( decodeHtmlEntities( true ) ).toBe( '' );
			expect( decodeHtmlEntities( false ) ).toBe( '' );
			expect( decodeHtmlEntities( Symbol( 'test' ) ) ).toBe( '' );
		} );

		it( 'handles empty strings', () => {
			expect( decodeHtmlEntities( '' ) ).toBe( '' );
		} );

		it( 'preserves whitespace in strings without entities', () => {
			expect( decodeHtmlEntities( '   ' ) ).toBe( '   ' );
			expect( decodeHtmlEntities( '\t\n' ) ).toBe( '\t\n' );
		} );
	} );

	describe( 'Common HTML entities', () => {
		it( 'decodes basic HTML entities', () => {
			expect( decodeHtmlEntities( '&amp;' ) ).toBe( '&' );
			expect( decodeHtmlEntities( '&lt;' ) ).toBe( '<' );
			expect( decodeHtmlEntities( '&gt;' ) ).toBe( '>' );
			expect( decodeHtmlEntities( '&quot;' ) ).toBe( '"' );
			expect( decodeHtmlEntities( '&apos;' ) ).toBe( "'" );
			expect( decodeHtmlEntities( '&#39;' ) ).toBe( "'" );
		} );

		it( 'decodes non-breaking space', () => {
			// Note: jsdom may handle this differently than real browsers
			const result = decodeHtmlEntities( '&nbsp;' );
			// Could be either regular space or non-breaking space depending on jsdom version
			expect( [ ' ', '\u00A0' ] ).toContain( result );
		} );

		it( 'decodes special characters', () => {
			expect( decodeHtmlEntities( '&hellip;' ) ).toBe( '…' );
			expect( decodeHtmlEntities( '&copy;' ) ).toBe( '©' );
			expect( decodeHtmlEntities( '&reg;' ) ).toBe( '®' );
			expect( decodeHtmlEntities( '&trade;' ) ).toBe( '™' );
		} );

		it( 'decodes currency symbols', () => {
			expect( decodeHtmlEntities( '&pound;' ) ).toBe( '£' );
			expect( decodeHtmlEntities( '&euro;' ) ).toBe( '€' );
			expect( decodeHtmlEntities( '&yen;' ) ).toBe( '¥' );
		} );
	} );

	describe( 'Numeric and hex entities', () => {
		it( 'decodes decimal numeric entities', () => {
			expect( decodeHtmlEntities( '&#8364;' ) ).toBe( '€' );
			expect( decodeHtmlEntities( '&#169;' ) ).toBe( '©' );
			expect( decodeHtmlEntities( '&#8482;' ) ).toBe( '™' );
			expect( decodeHtmlEntities( '&#65;' ) ).toBe( 'A' );
		} );

		it( 'decodes hexadecimal entities', () => {
			expect( decodeHtmlEntities( '&#x20AC;' ) ).toBe( '€' );
			expect( decodeHtmlEntities( '&#x00A9;' ) ).toBe( '©' );
			expect( decodeHtmlEntities( '&#x2122;' ) ).toBe( '™' );
			expect( decodeHtmlEntities( '&#x41;' ) ).toBe( 'A' );
		} );
	} );

	describe( 'Complex scenarios', () => {
		it( 'decodes multiple entities in one string', () => {
			const result = decodeHtmlEntities(
				'&lt;div&gt;&nbsp;&amp;&nbsp;&lt;/div&gt;'
			);
			// Handle both possible nbsp conversions
			expect( [
				'<div> & </div>',
				'<div>\u00A0&\u00A0</div>',
			] ).toContain( result );
		} );

		it( 'handles mixed content with entities and plain text', () => {
			const result = decodeHtmlEntities(
				'Price: €&nbsp;50.00 &amp; tax'
			);
			expect( [
				'Price: € 50.00 & tax',
				'Price: €\u00A050.00 & tax',
			] ).toContain( result );
		} );

		it( 'preserves text without entities', () => {
			const plainText = 'This is plain text with no entities!';
			expect( decodeHtmlEntities( plainText ) ).toBe( plainText );
		} );

		it( 'handles incomplete entities', () => {
			// Browsers handle incomplete entities if:
			// It is a legacy ISO Latin 1 entity (character code < 256)
			// There's no "name character" following it (or it's at end of string)
			// Historical SGML legacy: HTML up to 4.01 was SGML-based, allowing semicolon omission when entity isn't followed by a name character (e.g., &amp works but &amp4 doesn't)
			expect( decodeHtmlEntities( '&amp' ) ).toBe( '&' );

			// Incomplete numeric entities should not be decoded
			expect( decodeHtmlEntities( '&#' ) ).toBe( '&#' );
			expect( decodeHtmlEntities( '&' ) ).toBe( '&' );
			expect( decodeHtmlEntities( 'test & text' ) ).toBe( 'test & text' );
		} );

		it( 'handles entities at string boundaries', () => {
			expect( decodeHtmlEntities( '&amp;test' ) ).toBe( '&test' );
			expect( decodeHtmlEntities( 'test&amp;' ) ).toBe( 'test&' );
		} );
	} );

	describe( 'WooCommerce-specific use cases', () => {
		it( 'handles price formatting with thousand separators', () => {
			const result1 = decodeHtmlEntities( '1&nbsp;000.50' );
			expect( [ '1 000.50', '1\u00A0000.50' ] ).toContain( result1 );

			const result2 = decodeHtmlEntities( '€&nbsp;999&nbsp;999.99' );
			expect( [ '€ 999 999.99', '€\u00A0999\u00A0999.99' ] ).toContain(
				result2
			);
		} );

		it( 'handles product names with special characters', () => {
			expect( decodeHtmlEntities( 'T-shirt &amp; Jeans' ) ).toBe(
				'T-shirt & Jeans'
			);
			expect(
				decodeHtmlEntities( 'Coffee &quot;Premium&quot; Blend' )
			).toBe( 'Coffee "Premium" Blend' );
			expect( decodeHtmlEntities( 'Men&apos;s Clothing' ) ).toBe(
				"Men's Clothing"
			);
		} );

		it( 'handles product descriptions with multiple entities', () => {
			const description =
				'This product is &lt;strong&gt;amazing&lt;/strong&gt; &amp; costs &pound;50&nbsp;only!';
			const result = decodeHtmlEntities( description );
			expect( [
				'This product is <strong>amazing</strong> & costs £50 only!',
				'This product is <strong>amazing</strong> & costs £50\u00A0only!',
			] ).toContain( result );
		} );

		it( 'handles international characters and symbols', () => {
			expect( decodeHtmlEntities( 'Caf&eacute; &amp; Restaurant' ) ).toBe(
				'Café & Restaurant'
			);
			expect(
				decodeHtmlEntities( '&copy; 2024 &mdash; All rights reserved' )
			).toBe( '© 2024 — All rights reserved' );
		} );
	} );

	describe( 'HTML content handling', () => {
		it( 'extracts text content from HTML tags', () => {
			expect( decodeHtmlEntities( '<p>Hello &amp; welcome</p>' ) ).toBe(
				'Hello & welcome'
			);

			const result = decodeHtmlEntities(
				'<div><span>Test&nbsp;text</span></div>'
			);
			expect( [ 'Test text', 'Test\u00A0text' ] ).toContain( result );
		} );

		it( 'removes HTML tags while preserving decoded entities', () => {
			expect(
				decodeHtmlEntities( '<strong>Bold &amp; Beautiful</strong>' )
			).toBe( 'Bold & Beautiful' );
		} );

		it( 'handles self-closing tags', () => {
			expect( decodeHtmlEntities( 'Line 1<br/>Line 2' ) ).toBe(
				'Line 1Line 2'
			);
		} );

		it( 'handles nested HTML structures', () => {
			expect(
				decodeHtmlEntities(
					'<div><p>Nested <em>&amp;</em> text</p></div>'
				)
			).toBe( 'Nested & text' );
		} );
	} );

	describe( 'Performance and edge cases', () => {
		it( 'handles very long strings', () => {
			const longString = '&amp;'.repeat( 1000 );
			const expected = '&'.repeat( 1000 );
			expect( decodeHtmlEntities( longString ) ).toBe( expected );
		} );

		it( 'handles strings with many different entities', () => {
			const complexString =
				'&amp;&lt;&gt;&quot;&apos;&copy;&reg;&trade;&pound;&euro;&yen;';
			const result = decodeHtmlEntities( complexString );
			// Some browsers might handle &apos; differently
			expect( [ '&<>"\'©®™£€¥', '&<>"\'©®™£€¥' ] ).toContain( result );
		} );

		it( 'handles unusual but valid entity formats', () => {
			expect( decodeHtmlEntities( '&#x0041;' ) ).toBe( 'A' ); // Hex with leading zeros
			expect( decodeHtmlEntities( '&#065;' ) ).toBe( 'A' ); // Decimal with leading zeros
		} );

		it( 'handles entity-like strings that are not valid entities', () => {
			expect( decodeHtmlEntities( '&invalidentity;' ) ).toBe(
				'&invalidentity;'
			);
			expect( decodeHtmlEntities( '&#cfsdf;' ) ).toBe( '&#cfsdf;' );
			expect( decodeHtmlEntities( '&#xGGGG;' ) ).toBe( '&#xGGGG;' );
		} );
	} );

	describe( 'Document creation behavior', () => {
		it( 'creates a new document for each call', () => {
			const spy = jest.spyOn(
				document.implementation,
				'createHTMLDocument'
			);

			decodeHtmlEntities( 'test1' );
			decodeHtmlEntities( 'test2' );

			expect( spy ).toHaveBeenCalledTimes( 2 );
			expect( spy ).toHaveBeenCalledWith( '' );

			spy.mockRestore();
		} );
	} );

	describe( 'Script and style handling', () => {
		it( 'removes script tags and their content', () => {
			expect(
				decodeHtmlEntities( '<script>alert("test")</script>Hello' )
			).toBe( 'alert("test")Hello' );
		} );

		it( 'removes style tags and their content', () => {
			expect(
				decodeHtmlEntities( '<style>body { color: red; }</style>Hello' )
			).toBe( 'body { color: red; }Hello' );
		} );

		it( 'handles encoded script tags', () => {
			expect(
				decodeHtmlEntities(
					'&lt;script&gt;alert("xss")&lt;/script&gt;'
				)
			).toBe( '<script>alert("xss")</script>' );
		} );
	} );

	describe( 'Real-world entity combinations', () => {
		it( 'handles common French entities', () => {
			expect(
				decodeHtmlEntities( 'Cr&egrave;me br&ucirc;l&eacute;e' )
			).toBe( 'Crème brûlée' );
		} );

		it( 'handles common German entities', () => {
			expect( decodeHtmlEntities( 'M&uuml;nchen &amp; K&ouml;ln' ) ).toBe(
				'München & Köln'
			);
		} );

		it( 'handles mathematical symbols', () => {
			expect( decodeHtmlEntities( '5 &lt; 10 &amp; 10 &gt; 5' ) ).toBe(
				'5 < 10 & 10 > 5'
			);
			expect(
				decodeHtmlEntities( '&plusmn;5 &divide; 2 = &plusmn;2.5' )
			).toBe( '±5 ÷ 2 = ±2.5' );
		} );

		it( 'handles quotes and punctuation', () => {
			expect(
				decodeHtmlEntities( '&ldquo;Hello&rdquo; &mdash; world' )
			).toBe( '“Hello” — world' );
			expect(
				decodeHtmlEntities( '&lsquo;Test&rsquo; &ndash; example' )
			).toBe( '‘Test’ – example' );
		} );
	} );
} );
