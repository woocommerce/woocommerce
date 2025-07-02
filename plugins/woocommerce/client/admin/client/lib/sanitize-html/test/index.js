/**
 * External dependencies
 */
import dompurify from 'dompurify';

/**
 * Internal dependencies
 */
import sanitizeHtml, { ALLOWED_TAGS, ALLOWED_ATTR } from '../../sanitize-html';

import sanitizeHtmlExtended, {
	EXTENDED_ALLOWED_TAGS,
	EXTENDED_ALLOWED_ATTR,
} from '../../sanitize-html/sanitize-html-extended.js';

describe( 'sanitizeHtml', () => {
	/* eslint-disable quotes */
	const html = `<html><body><a href="#" malformed="attribute">Link</a></body></html>`;
	/* eslint-enable quotes */

	test( 'should remove any html tags and attributes which are not allowed', () => {
		expect( sanitizeHtml( html ) ).toEqual( {
			__html: '<a href="#">Link</a>',
		} );
	} );

	test( 'should call dompurify.sanitize with list of allowed tags and attributes', () => {
		const sanitizeMock = jest.spyOn( dompurify, 'sanitize' );

		sanitizeHtml( html );
		expect( sanitizeMock ).toHaveBeenCalledWith( html, {
			ALLOWED_ATTR,
			ALLOWED_TAGS,
		} );
	} );
} );

describe( 'sanitizeHtmlExtended', () => {
	/* eslint-disable quotes */
	const html = `<html><body><div class="container"><h1>Title</h1><img src="test.jpg" alt="Test" /><table><tr><td>Cell</td></tr></table></div></body></html>`;
	/* eslint-enable quotes */

	test( 'should allow extended set of HTML tags and attributes', () => {
		// domPurify adds tbody and changes order of attributes
		expect( sanitizeHtmlExtended( html ) ).toEqual( {
			__html:
				'<div class="container"><h1>Title</h1><img alt="Test"' +
				' src="test.jpg"><table><tbody><tr><td>Cell</td></tr></tbody></table></div>',
		} );
	} );

	test( 'should accept custom allowed tags and attributes', () => {
		/* eslint-disable quotes */
		const customHtml = `<div class="container"><h1>Title</h1><svg></svg></div>`;
		/* eslint-enable quotes */

		const customConfig = {
			allowedTags: [ 'div', 'h1', 'svg' ],
			allowedAttributes: [ 'class' ],
		};

		expect( sanitizeHtmlExtended( customHtml, customConfig ) ).toEqual( {
			__html: '<div class="container"><h1>Title</h1><svg></svg></div>',
		} );
	} );

	test( 'should return empty string for falsy input', () => {
		expect( sanitizeHtmlExtended( '' ) ).toBe( '' );
		expect( sanitizeHtmlExtended( null ) ).toBe( '' );
		expect( sanitizeHtmlExtended( undefined ) ).toBe( '' );
	} );

	test( 'should call dompurify.sanitize with extended allowed tags and attributes', () => {
		const sanitizeMock = jest.spyOn( dompurify, 'sanitize' );

		sanitizeHtmlExtended( html );
		expect( sanitizeMock ).toHaveBeenCalledWith( html, {
			ALLOWED_TAGS: EXTENDED_ALLOWED_TAGS,
			ALLOWED_ATTR: EXTENDED_ALLOWED_ATTR,
		} );
	} );
} );
