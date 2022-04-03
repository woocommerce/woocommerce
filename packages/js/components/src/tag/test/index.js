/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Tag from '../';

const noop = () => {};

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
		const { queryByText, queryByRole } = render(
			<Tag
				label="foo"
				instanceId="1"
				popoverContents={ <p>This is a popover</p> }
			/>
		);

		fireEvent.click(
			queryByRole( 'button', { id: 'woocommerce-tag__label-1' } )
		);
		expect( queryByText( 'This is a popover' ) ).toBeDefined();
	} );
} );
