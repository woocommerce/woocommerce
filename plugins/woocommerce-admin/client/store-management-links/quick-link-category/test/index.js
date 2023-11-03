/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { QuickLinkCategory } from '..';

describe( 'QuickLinkCategory', () => {
	it( 'displays the passed title and children', () => {
		const { queryByText } = render(
			<QuickLinkCategory title="hello world">
				<div>Test</div>
			</QuickLinkCategory>
		);

		expect( queryByText( 'hello world' ) ).not.toBeEmptyDOMElement();
		expect( queryByText( 'Test' ) ).not.toBeEmptyDOMElement();
	} );
} );
