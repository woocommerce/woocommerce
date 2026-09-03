/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import * as sanitize from '@woocommerce/sanitize';

jest.mock( '@woocommerce/sanitize', () => {
	const actual = jest.requireActual( '@woocommerce/sanitize' );

	return {
		...actual,
		sanitizeHTML: jest.fn( actual.sanitizeHTML ),
	};
} );

/**
 * Internal dependencies
 */
import Summary, { SummaryProps } from '../index';

const allowedSource =
	'<p class="some-class">' +
	'Lorem ipsum dolor sit amet ' +
	'<h1 style="color: red;">Heading</h1>' +
	'<ul><li>List item</li></ul>' +
	'<img src="https://example.com/image.jpg" alt="Image" />' +
	'</p>';

const disallowedSource =
	'<p class="some-class">' +
	'Lorem ipsum dolor sit amet ' +
	'<script src="http://evil.com" />' +
	'<h1 style="color: red;">Heading</h1>' +
	'<ul><li>List item</li></ul>' +
	'<img src="https://example.com/image.jpg" alt="Image" onerror="alert(1)" onload="alert(2)" height="100" width="100" />' +
	'<script>alert("Hello");</script>' +
	'</p>';

const getProps = ( source: string ) =>
	( {
		source,
		maxLength: 1000,
		countType: 'words',
		className: 'test-class',
	} ) as SummaryProps;

describe( 'Summary component', () => {
	it( 'renders rich HTML with the allowed tags and attributes', () => {
		const props = getProps( allowedSource );
		const { container } = render( <Summary { ...props } /> );

		expect( container ).toMatchSnapshot( allowedSource );
	} );

	it( 'omits disallowed tags and attributes', () => {
		const props = getProps( disallowedSource );
		const { container } = render( <Summary { ...props } /> );

		expect( container ).toMatchSnapshot( allowedSource );
		expect( container.querySelector( 'script' ) ).toBeNull();
		expect( container.querySelector( '[onerror]' ) ).toBeNull();
		expect( container.querySelector( '[onload]' ) ).toBeNull();
		expect( container.querySelector( '[height]' ) ).toBeNull();
		expect( container.querySelector( '[width]' ) ).toBeNull();
	} );

	it( 'omits disallowed attributes that survive sanitization', () => {
		// Simulate the reported sanitizer bypass so this test covers Summary's final attribute check, not DOMPurify's current behavior.
		( sanitize.sanitizeHTML as jest.Mock ).mockReturnValueOnce(
			'Best jet<img src="x" onerror="alert(document.cookie)" onload="alert(document.cookie)" height="100">'
		);
		const props = getProps(
			'Best jet<img/src/onerror=alert(document.cookie)><img src="x" onload="alert(document.cookie)">'
		);
		const { container } = render( <Summary { ...props } /> );

		expect( container.querySelector( '[onerror]' ) ).toBeNull();
		expect( container.querySelector( '[onload]' ) ).toBeNull();
		expect( container.innerHTML ).not.toContain( 'alert(document.cookie)' );
	} );
} );
