/**
 * External dependencies
 */
import renderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import Tag from '../';

const noop = () => {};

describe( 'Tag', () => {
	test( '<Tag label="foo" /> should render a tag with the label foo', () => {
		const tree = renderer.create( <Tag label="foo" /> ).toJSON();
		expect( tree ).toMatchSnapshot();
	} );

	test( '<Tag label="foo" remove={ noop } /> should render a tag with a close button', () => {
		const tree = renderer
			.create( <Tag label="foo" remove={ noop } /> )
			.toJSON();
		expect( tree ).toMatchSnapshot();
	} );

	test( '<Tag label="foo" popoverContents={ <p>This is a popover</p> } /> should render a tag with a popover', () => {
		const tree = renderer
			.create(
				<Tag label="foo" popoverContents={ <p>This is a popover</p> } />
			)
			.toJSON();
		expect( tree ).toMatchSnapshot();
	} );

	test( '<Tag label="foo" screenReaderLabel="FooBar" /> should render a tag with a screen reader label', () => {
		const tree = renderer
			.create( <Tag label="foo" screenReaderLabel="FooBar" /> )
			.toJSON();
		expect( tree ).toMatchSnapshot();
	} );
} );
