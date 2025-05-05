/**
 * External dependencies
 */
import { render } from '@testing-library/react';

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
	'<img src="https://example.com/image.jpg" alt="Image" height="100" width="100" />' +
	'<script>alert("Hello");</script>' +
	'</p>';

const getProps = ( source: string ) =>
	( {
		source,
		maxLength: 1000,
		countType: 'words',
		className: 'test-class',
	} as SummaryProps );

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
	} );
} );
