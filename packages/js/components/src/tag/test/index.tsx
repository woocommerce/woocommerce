/**
 * External dependencies
 */
import { render, fireEvent, screen } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Tag from '..';

const noop = () => () => {};

describe( 'Tag', () => {
	test( 'Do not show popoverContents by default', () => {
		const { queryByText } = render(
			<Tag label="foo" popoverContents={ <p>This is a popover</p> } />
		);
		expect( queryByText( 'This is a popover' ) ).toBeNull();
	} );

	test( 'Show popoverContents after clicking the button', () => {
		const { queryByText } = render(
			<Tag label="foo" popoverContents={ <p>This is a popover</p> } />
		);

		const button = screen.getByRole( 'button', {
			name: 'foo',
		} );
		fireEvent.click( button );
		expect( queryByText( 'This is a popover' ) ).toBeDefined();
	} );
} );
