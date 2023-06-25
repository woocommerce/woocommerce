/**
 * External dependencies
 */
import { screen, render, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Control from '../control';

describe( 'TreeSelectControl - Control Component', () => {
	const onTagsChange = jest.fn().mockName( 'onTagsChange' );
	const ref = {
		current: {
			focus: jest.fn(),
		},
	};

	it( 'Renders the tags and calls onTagsChange when they change', () => {
		const { queryByText, queryByLabelText, rerender } = render(
			<Control
				ref={ ref }
				tags={ [ { id: 'es', label: 'Spain' } ] }
				onTagsChange={ onTagsChange }
			/>
		);

		expect( queryByText( 'Spain (1 of 1)' ) ).toBeTruthy();
		userEvent.click( queryByLabelText( 'Remove Spain' ) );
		expect( onTagsChange ).toHaveBeenCalledTimes( 1 );
		expect( onTagsChange ).toHaveBeenCalledWith( [] );

		rerender(
			<Control
				ref={ ref }
				tags={ [ { id: 'es', label: 'Spain' } ] }
				disabled={ true }
				onTagsChange={ onTagsChange }
			/>
		);

		expect( screen.queryByText( 'Spain (1 of 1)' ) ).toBeTruthy();
		userEvent.click( screen.queryByLabelText( 'Remove Spain' ) );
		expect( onTagsChange ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'Calls onInputChange when typing', () => {
		const onInputChange = jest
			.fn()
			.mockName( 'onInputChange' )
			.mockImplementation( ( e ) => e.target.value );
		const { queryByRole } = render(
			<Control ref={ ref } onInputChange={ onInputChange } />
		);

		const input = queryByRole( 'combobox' );
		expect( input ).toBeTruthy();
		expect( input.hasAttribute( 'disabled' ) ).toBeFalsy();
		userEvent.type( input, 'a' );
		expect( onInputChange ).toHaveBeenCalledTimes( 1 );
		expect( onInputChange ).toHaveNthReturnedWith( 1, 'a' );
		fireEvent.change( input, { target: { value: 'test' } } );
		expect( onInputChange ).toHaveBeenCalledTimes( 2 );
		expect( onInputChange ).toHaveNthReturnedWith( 2, 'test' );
	} );

	it( 'Allows disabled input', () => {
		const onInputChange = jest.fn().mockName( 'onInputChange' );
		const { queryByRole } = render(
			<Control disabled={ true } onInputChange={ onInputChange } />
		);

		const input = queryByRole( 'combobox' );
		expect( input ).toBeTruthy();
		expect( input.hasAttribute( 'disabled' ) ).toBeTruthy();
		userEvent.type( input, 'a' );
		expect( onInputChange ).not.toHaveBeenCalled();
	} );

	it( 'Calls onFocus callback when it is focused', () => {
		const onFocus = jest.fn().mockName( 'onFocus' );
		const { queryByRole } = render(
			<Control ref={ ref } onFocus={ onFocus } />
		);
		userEvent.click( queryByRole( 'combobox' ) );
		expect( onFocus ).toHaveBeenCalled();
	} );

	it( 'Renders placeholder when there are no tags and is not expanded', () => {
		const { rerender } = render( <Control placeholder="Select" /> );
		let input = screen.queryByRole( 'combobox' );
		let placeholder = input.getAttribute( 'placeholder' );
		expect( placeholder ).toBe( 'Select' );

		rerender(
			<Control
				placeholder="Select"
				tags={ [ { id: 'es', label: 'Spain' } ] }
			/>
		);

		input = screen.queryByRole( 'combobox' );
		placeholder = input.getAttribute( 'placeholder' );
		expect( placeholder ).toBeFalsy();

		rerender( <Control placeholder="Select" isExpanded={ true } /> );
		input = screen.queryByRole( 'combobox' );
		placeholder = input.getAttribute( 'placeholder' );
		expect( placeholder ).toBeFalsy();
	} );

	it( 'Renders placeholder when alwaysShowPlaceholder is true with tags or expanded', () => {
		const { rerender } = render(
			<Control placeholder="Select" alwaysShowPlaceholder={ true } />
		);
		let input = screen.queryByRole( 'combobox' );
		let placeholder = input.getAttribute( 'placeholder' );
		expect( placeholder ).toBe( 'Select' );

		rerender(
			<Control
				placeholder="Select"
				alwaysShowPlaceholder={ true }
				tags={ [ { id: 'es', label: 'Spain' } ] }
			/>
		);

		input = screen.queryByRole( 'combobox' );
		placeholder = input.getAttribute( 'placeholder' );
		expect( placeholder ).toBeTruthy();

		rerender(
			<Control
				placeholder="Select"
				isExpanded={ true }
				alwaysShowPlaceholder={ true }
			/>
		);
		input = screen.queryByRole( 'combobox' );
		placeholder = input.getAttribute( 'placeholder' );
		expect( placeholder ).toBeTruthy();
	} );
} );
