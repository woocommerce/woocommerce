/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { Chip, RemovableChip } from '..';

describe( 'Chip', () => {
	test( 'should render text', () => {
		const { container } = render( <Chip text="Test" /> );

		expect( container ).toMatchSnapshot();
	} );

	test( 'should render nodes as the text', () => {
		const { container } = render( <Chip text={ <h1>Test</h1> } /> );

		expect( container ).toMatchSnapshot();
	} );

	test( 'should render defined radius', () => {
		const { container } = render( <Chip text="Test" radius="large" /> );

		expect( container ).toMatchSnapshot();
	} );

	test( 'should render screen reader text', () => {
		const { container } = render(
			<Chip text="Test" screenReaderText="Test 2" />
		);

		expect( container ).toMatchSnapshot();
	} );

	test( 'should render children nodes', () => {
		const { container } = render( <Chip text="Test">Lorem Ipsum</Chip> );

		expect( container ).toMatchSnapshot();
	} );

	describe( 'with custom wrapper', () => {
		test( 'should render a chip made up of a div instead of a li', () => {
			const { container } = render( <Chip text="Test" element="div" /> );

			expect( container ).toMatchSnapshot();
		} );
	} );
} );

describe( 'RemovableChip', () => {
	test( 'should render text and the remove button', () => {
		const { container } = render( <RemovableChip text="Test" /> );

		expect( container ).toMatchSnapshot();
	} );

	test( 'should render with disabled remove button', () => {
		const { container } = render(
			<RemovableChip text="Test" disabled={ true } />
		);

		expect( container ).toMatchSnapshot();
	} );

	test( 'should render custom aria label', () => {
		const { container } = render(
			<RemovableChip text={ <h1>Test</h1> } ariaLabel="Aria test" />
		);

		expect( container ).toMatchSnapshot();
	} );

	test( 'should render default aria label if text is a node', () => {
		const { container } = render(
			<RemovableChip text={ <h1>Test</h1> } screenReaderText="Test 2" />
		);

		expect( container ).toMatchSnapshot();
	} );

	test( 'should render screen reader text aria label', () => {
		const { container } = render(
			<RemovableChip text="Test" screenReaderText="Test 2" />
		);

		expect( container ).toMatchSnapshot();
	} );

	describe( 'with removeOnAnyClick', () => {
		test( 'should be a button when removeOnAnyClick is set to true', () => {
			const { container } = render(
				<RemovableChip text="Test" removeOnAnyClick={ true } />
			);

			expect( container ).toMatchSnapshot();
		} );
	} );
} );
