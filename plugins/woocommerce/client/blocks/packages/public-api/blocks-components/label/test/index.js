/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Label from '../';

describe( 'Label', () => {
	describe( 'without wrapperElement', () => {
		test( 'should render both label and screen reader label', () => {
			const { container } = render(
				<Label label="Lorem" screenReaderLabel="Ipsum" />
			);

			expect( container ).toMatchSnapshot();
		} );

		test( 'should render only the label', () => {
			const { container } = render( <Label label="Lorem" /> );

			expect( container ).toMatchSnapshot();
		} );

		test( 'should render only the screen reader label', () => {
			const { container } = render( <Label screenReaderLabel="Ipsum" /> );

			expect( container ).toMatchSnapshot();
		} );
	} );

	describe( 'with wrapperElement', () => {
		test( 'should render both label and screen reader label', () => {
			const { container } = render(
				<Label
					label="Lorem"
					screenReaderLabel="Ipsum"
					wrapperElement="label"
					wrapperProps={ {
						className: 'foo-bar',
						'data-foo': 'bar',
					} }
				/>
			);

			expect( container ).toMatchSnapshot();
		} );

		test( 'should render only the label', () => {
			const { container } = render(
				<Label
					label="Lorem"
					wrapperElement="label"
					wrapperProps={ {
						className: 'foo-bar',
						'data-foo': 'bar',
					} }
				/>
			);

			expect( container ).toMatchSnapshot();
		} );

		test( 'should render only the screen reader label', () => {
			const { container } = render(
				<Label
					screenReaderLabel="Ipsum"
					wrapperElement="label"
					wrapperProps={ {
						className: 'foo-bar',
						'data-foo': 'bar',
					} }
				/>
			);

			expect( container ).toMatchSnapshot();
		} );
	} );
} );
