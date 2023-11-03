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
	test( '<Tag label="foo" /> should render a tag with the label foo', () => {
		const component = render( <Tag label="foo" /> );
		expect( component ).toMatchSnapshot();
	} );

	test( '<Tag label="foo" remove={ noop } /> should render a tag with a close button', () => {
		const component = render( <Tag label="foo" remove={ noop } /> );
		expect( component ).toMatchSnapshot();
	} );

	test( '<Tag label="foo" popoverContents={ <p>This is a popover</p> } /> should render a tag with a popover', () => {
		const component = render(
			<Tag label="foo" popoverContents={ <p>This is a popover</p> } />
		);
		expect( component ).toMatchSnapshot();
	} );

	test( '<Tag label="foo" screenReaderLabel="FooBar" /> should render a tag with a screen reader label', () => {
		const component = render(
			<Tag label="foo" screenReaderLabel="FooBar" />
		);
		expect( component ).toMatchSnapshot();
	} );

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
