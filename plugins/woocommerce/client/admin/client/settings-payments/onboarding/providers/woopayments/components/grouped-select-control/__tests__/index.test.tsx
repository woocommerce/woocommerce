/**
 * External dependencies
 */
import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import GroupedSelectControl, { GroupedSelectControlProps } from '..';

describe( 'GroupedSelectControl', () => {
	const onChange = jest.fn();

	const options = [
		{ key: 'g1', name: 'Group 1', items: [ 'o1', 'o2' ] },
		{ key: 'o1', name: 'Option 1', group: 'g1' },
		{ key: 'o2', name: 'Option 2', group: 'g1' },
		{ key: 'g2', name: 'Group 2', items: [ 'o3', 'o4' ] },
		{ key: 'o3', name: 'Option 3', group: 'g2' },
		{ key: 'o4', name: 'Option 4', group: 'g2' },
		{ key: 'g3', name: 'Group 3', items: [ 'o5' ] },
		{ key: 'o5', name: 'Option 5', group: 'g3', context: 'z' },
	];

	const renderControl = (
		props?: Partial< GroupedSelectControlProps< ( typeof options )[ 1 ] > >
	) =>
		render(
			<GroupedSelectControl
				{ ...props }
				label="Group select"
				options={ options }
				onChange={ onChange }
			/>
		);

	const openMenu = () =>
		userEvent.click(
			screen.getByRole( 'combobox', { name: /Group select/ } )
		);

	const getSearch = () => screen.getByRole( 'combobox', { name: 'Search' } );

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'toggles a group when it is clicked while browsing', async () => {
		renderControl();
		await openMenu();

		expect( screen.getByText( 'Option 1' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Option 3' ) ).not.toBeInTheDocument();

		await userEvent.click(
			screen.getByRole( 'option', { name: 'Group 2' } )
		);

		expect( screen.getByText( 'Option 3' ) ).toBeInTheDocument();
	} );

	it( 'moves focus to the search field when a searchable menu opens', async () => {
		renderControl( { searchable: true } );
		await openMenu();

		expect( getSearch() ).toHaveFocus();
	} );

	it( 'shows matching groups as headings instead of options while searching', async () => {
		renderControl( { searchable: true } );
		await openMenu();
		await userEvent.type( getSearch(), 'Option 1' );

		expect( screen.getByText( 'Group 1' ) ).toBeInTheDocument();
		expect(
			screen.queryByRole( 'option', { name: 'Group 1' } )
		).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'option', { name: 'Option 1' } )
		).toHaveAccessibleDescription( 'Group 1' );
		// Options must belong to the listbox, not to a nested list.
		expect( screen.queryByRole( 'list' ) ).not.toBeInTheDocument();
	} );

	it( 'skips group headings when navigating search results with the keyboard', async () => {
		renderControl( { searchable: true } );
		await openMenu();
		await userEvent.type( getSearch(), 'Option' );
		await userEvent.keyboard( '{ArrowDown}{Enter}' );

		expect( onChange ).toHaveBeenCalledWith(
			expect.objectContaining( { selectedItem: options[ 1 ] } )
		);
	} );

	it( 'keeps the highlight on the right option after crossing several group headings', async () => {
		renderControl( { searchable: true } );
		await openMenu();

		const search = getSearch();
		await userEvent.type( search, 'Option' );
		await userEvent.keyboard( '{ArrowDown}{ArrowDown}{ArrowDown}' );

		const highlighted = screen.getByRole( 'option', { name: 'Option 3' } );
		expect( highlighted ).toHaveClass( 'is-highlighted' );
		expect( search ).toHaveAttribute(
			'aria-activedescendant',
			highlighted.id
		);

		await userEvent.keyboard( '{Enter}' );

		expect( onChange ).toHaveBeenCalledWith(
			expect.objectContaining( { selectedItem: options[ 4 ] } )
		);
	} );

	it( 'returns focus to the field after picking an option', async () => {
		renderControl( { searchable: true } );
		await openMenu();
		await userEvent.type( getSearch(), 'Option' );
		await userEvent.click(
			screen.getByRole( 'option', { name: 'Option 3' } )
		);

		expect( onChange ).toHaveBeenCalledWith(
			expect.objectContaining( { selectedItem: options[ 4 ] } )
		);
		expect(
			screen.getByRole( 'combobox', { name: /Group select/ } )
		).toHaveFocus();
	} );

	it( 'returns focus to the field when the menu is closed with Escape', async () => {
		renderControl( { searchable: true } );
		await openMenu();
		await userEvent.keyboard( '{Escape}' );

		expect( screen.queryByRole( 'option' ) ).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'combobox', { name: /Group select/ } )
		).toHaveFocus();
		expect( onChange ).not.toHaveBeenCalled();
	} );

	it( 'toggles a group from the keyboard while browsing and keeps the menu open', async () => {
		renderControl( { searchable: true } );
		await openMenu();
		await userEvent.keyboard(
			'{ArrowDown}{ArrowDown}{ArrowDown}{ArrowDown}{Enter}'
		);

		expect( screen.getByText( 'Option 3' ) ).toBeInTheDocument();
		expect( getSearch() ).toHaveFocus();
	} );

	it( 'ignores clicks on group headings while searching', async () => {
		renderControl( { searchable: true } );
		await openMenu();
		await userEvent.type( getSearch(), 'Option' );
		await userEvent.click( screen.getByText( 'Group 1' ) );

		expect( onChange ).not.toHaveBeenCalled();
		expect(
			screen.getByRole( 'option', { name: 'Option 1' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'option', { name: 'Option 2' } )
		).toBeInTheDocument();
	} );

	it( 'exposes the search field as a combobox that controls the list', async () => {
		renderControl( { searchable: true } );
		await openMenu();

		const search = getSearch();
		expect( search ).toHaveAttribute(
			'aria-controls',
			screen.getByRole( 'listbox' ).id
		);
		expect( search ).toHaveAttribute( 'aria-expanded', 'true' );
	} );

	it( 'restores the browsing groups after clearing the search', async () => {
		renderControl( { searchable: true } );
		await openMenu();

		const search = getSearch();
		await userEvent.type( search, 'Option 5' );
		expect( screen.queryByText( 'Option 1' ) ).not.toBeInTheDocument();

		await userEvent.clear( search );

		expect( screen.getByText( 'Option 1' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Option 5' ) ).not.toBeInTheDocument();
	} );
} );
