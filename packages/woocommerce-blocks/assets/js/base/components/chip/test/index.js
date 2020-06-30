/**
 * External dependencies
 */
import TestRenderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import Chip from '../';

describe( 'Chip', () => {
	describe( 'without custom wrapper', () => {
		test( 'should render text and the remove button', () => {
			const component = TestRenderer.create( <Chip text="Test" /> );

			expect( component.toJSON() ).toMatchSnapshot();
		} );

		test( 'should render defined radius', () => {
			const component = TestRenderer.create(
				<Chip text="Test" radius="large" />
			);

			expect( component.toJSON() ).toMatchSnapshot();
		} );

		test( 'should render with disabled remove button', () => {
			const component = TestRenderer.create(
				<Chip text="Test" disabled={ true } />
			);

			expect( component.toJSON() ).toMatchSnapshot();
		} );
	} );

	describe( 'with custom wrapper', () => {
		test( 'should render a chip made up of a div instead of a li', () => {
			const component = TestRenderer.create(
				<Chip text="Test" element="div" />
			);

			expect( component.toJSON() ).toMatchSnapshot();
		} );
	} );
} );
