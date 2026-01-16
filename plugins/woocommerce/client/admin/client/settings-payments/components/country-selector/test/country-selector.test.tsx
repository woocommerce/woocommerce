/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { CountrySelector } from '../country-selector';

describe( 'CountrySelector', () => {
	// Sample items for testing
	const mockItems = [
		{ key: 'US', name: 'United States' },
		{ key: 'CA', name: 'Canada' },
		{ key: 'FR', name: 'France' },
	];

	const scrollIntoViewMock = jest.fn();
	Object.defineProperty( HTMLElement.prototype, 'scrollIntoView', {
		value: scrollIntoViewMock,
		writable: true,
	} );
	const mockOnChange = jest.fn();

	const defaultProps = {
		name: 'country-selector',
		label: __( 'Select a country', 'woocommerce' ),
		options: mockItems,
		onChange: mockOnChange,
		value: mockItems[ 0 ], // Initially selected item
		placeholder: __( 'Choose a country', 'woocommerce' ),
	};

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders correctly with initial props', () => {
		render( <CountrySelector { ...defaultProps } /> );
		expect( screen.getByText( 'Select a country' ) ).toBeInTheDocument();
		expect( screen.getByText( 'United States' ) ).toBeInTheDocument();
		expect( screen.getByRole( 'combobox' ) ).toBeInTheDocument();
	} );

	it( 'opens the dropdown menu when the button is clicked', () => {
		render( <CountrySelector { ...defaultProps } /> );
		const toggleButton = screen.getByRole( 'combobox' );

		fireEvent.click( toggleButton );
		expect( screen.getByRole( 'listbox' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Canada' ) ).toBeInTheDocument();
		expect( screen.getByText( 'France' ) ).toBeInTheDocument();
	} );

	it( 'filters options based on search input', () => {
		render( <CountrySelector { ...defaultProps } /> );
		const toggleButton = screen.getByRole( 'combobox' );

		fireEvent.click( toggleButton );

		const searchInput = screen.getByPlaceholderText( 'Search' );
		fireEvent.change( searchInput, { target: { value: 'Ca' } } );

		expect( screen.getByText( 'Canada' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'France' ) ).not.toBeInTheDocument();
	} );

	it( 'calls onChange with the selected item when Apply button is clicked', () => {
		render( <CountrySelector { ...defaultProps } /> );
		const toggleButton = screen.getByRole( 'combobox' );

		fireEvent.click( toggleButton );

		const item = screen.getByText( 'Canada' );
		fireEvent.click( item );

		const applyButton = screen.getByText( 'Apply' );
		fireEvent.click( applyButton );

		expect( mockOnChange ).toHaveBeenCalledWith( 'CA' );
	} );

	it( 'closes the dropdown menu when Apply button is clicked', () => {
		render( <CountrySelector { ...defaultProps } /> );
		const toggleButton = screen.getByRole( 'combobox' );

		fireEvent.click( toggleButton );

		const applyButton = screen.getByText( 'Apply' );
		fireEvent.click( applyButton );

		expect( screen.queryByRole( 'listbox' ) ).not.toBeInTheDocument();
	} );

	describe( 'Keyboard Navigation', () => {
		it( 'focuses search input when dropdown opens', async () => {
			render( <CountrySelector { ...defaultProps } /> );
			const toggleButton = screen.getByRole( 'combobox' );

			fireEvent.click( toggleButton );

			// Wait for focus to be set via setTimeout.
			await new Promise( ( resolve ) => setTimeout( resolve, 10 ) );

			const searchInput = screen.getByPlaceholderText( 'Search' );
			expect( document.activeElement ).toBe( searchInput );
		} );

		it( 'navigates items with Arrow Down key', () => {
			render( <CountrySelector { ...defaultProps } /> );
			const toggleButton = screen.getByRole( 'combobox' );

			fireEvent.click( toggleButton );

			const searchInput = screen.getByPlaceholderText( 'Search' );

			// Initial highlight is on selected item (US, index 0).
			// Press Arrow Down to move to Canada (index 1).
			fireEvent.keyDown( searchInput, { key: 'ArrowDown' } );

			// Canada should now be highlighted.
			const canadaItem = screen
				.getByText( 'Canada' )
				.closest( '.components-country-select-control__item' );
			expect( canadaItem ).toHaveClass( 'is-highlighted' );
		} );

		it( 'navigates items with Arrow Up key', () => {
			render( <CountrySelector { ...defaultProps } /> );
			const toggleButton = screen.getByRole( 'combobox' );

			fireEvent.click( toggleButton );

			const searchInput = screen.getByPlaceholderText( 'Search' );

			// Initial highlight is on selected item (US, index 0).
			// Arrow Up from index 0 should stay at 0 (no circular navigation).
			// So let's first go down, then up.
			fireEvent.keyDown( searchInput, { key: 'ArrowDown' } ); // Now at Canada (1).
			fireEvent.keyDown( searchInput, { key: 'ArrowUp' } ); // Back to US (0).

			// US should be highlighted.
			const usItem = screen
				.getByRole( 'option', { name: 'United States' } );
			expect( usItem ).toHaveClass( 'is-highlighted' );
		} );

		it( 'selects highlighted item and closes dropdown on Enter', () => {
			render( <CountrySelector { ...defaultProps } /> );
			const toggleButton = screen.getByRole( 'combobox' );

			fireEvent.click( toggleButton );

			const searchInput = screen.getByPlaceholderText( 'Search' );

			// Initial highlight is on US (index 0).
			// Navigate to Canada (index 1).
			fireEvent.keyDown( searchInput, { key: 'ArrowDown' } );

			// Press Enter to select Canada.
			fireEvent.keyDown( searchInput, { key: 'Enter' } );

			expect( mockOnChange ).toHaveBeenCalledWith( 'CA' );
			expect( screen.queryByRole( 'listbox' ) ).not.toBeInTheDocument();
		} );

		it( 'closes dropdown on Escape key without selecting', () => {
			render( <CountrySelector { ...defaultProps } /> );
			const toggleButton = screen.getByRole( 'combobox' );

			fireEvent.click( toggleButton );

			const searchInput = screen.getByPlaceholderText( 'Search' );

			// Navigate to an item.
			fireEvent.keyDown( searchInput, { key: 'ArrowDown' } );

			// Press Escape to close without selecting.
			fireEvent.keyDown( searchInput, { key: 'Escape' } );

			expect( mockOnChange ).not.toHaveBeenCalled();
			expect( screen.queryByRole( 'listbox' ) ).not.toBeInTheDocument();
		} );

		it( 'allows Tab to move focus to Apply button', () => {
			render( <CountrySelector { ...defaultProps } /> );
			const toggleButton = screen.getByRole( 'combobox' );

			fireEvent.click( toggleButton );

			const searchInput = screen.getByPlaceholderText( 'Search' );

			// Tab should not be prevented, allowing focus to move.
			fireEvent.keyDown( searchInput, { key: 'Tab' } );

			// Dropdown should still be open.
			expect( screen.getByRole( 'listbox' ) ).toBeInTheDocument();
		} );
	} );
} );
