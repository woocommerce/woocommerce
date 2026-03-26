/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { speak } from '@wordpress/a11y';

/**
 * Internal dependencies
 */
import '../../../test-helper/global-mock';
import ItemSelector from '../item-selector';
import { useFulfillmentContext } from '../../../context/fulfillment-context';

jest.mock( '@wordpress/a11y', () => ( {
	speak: jest.fn(),
} ) );

jest.mock( '../../../context/fulfillment-context', () => ( {
	useFulfillmentContext: jest.fn(),
} ) );

const createMockItems = ( items ) =>
	items.map( ( item ) => ( {
		item_id: item.id,
		item: { id: item.id, name: item.name, quantity: item.qty },
		selection: Array.from( { length: item.qty }, ( _, i ) => ( {
			index: i,
			checked: item.checked ?? false,
		} ) ),
	} ) );

describe( 'ItemSelector speak() announcements', () => {
	let mockSetSelectedItems;

	beforeEach( () => {
		jest.clearAllMocks();
		mockSetSelectedItems = jest.fn();
	} );

	it( 'should announce when all items are selected', () => {
		const items = createMockItems( [
			{ id: 1, name: 'Widget', qty: 2, checked: false },
			{ id: 2, name: 'Gadget', qty: 1, checked: false },
		] );

		useFulfillmentContext.mockReturnValue( {
			order: { id: 1, currency: 'USD' },
			selectedItems: items,
			setSelectedItems: mockSetSelectedItems,
		} );

		render( <ItemSelector editMode={ true } /> );

		// Click the select-all checkbox
		const selectAllCheckbox = screen.getByRole( 'checkbox', {
			name: 'Select all items',
		} );
		fireEvent.click( selectAllCheckbox );

		expect( speak ).toHaveBeenCalledWith( '3 items selected.', 'polite' );
	} );

	it( 'should announce when all items are deselected', () => {
		const items = createMockItems( [
			{ id: 1, name: 'Widget', qty: 2, checked: true },
			{ id: 2, name: 'Gadget', qty: 1, checked: true },
		] );

		useFulfillmentContext.mockReturnValue( {
			order: { id: 1, currency: 'USD' },
			selectedItems: items,
			setSelectedItems: mockSetSelectedItems,
		} );

		render( <ItemSelector editMode={ true } /> );

		// Click the deselect-all checkbox (all items are already selected)
		const deselectAllCheckbox = screen.getByRole( 'checkbox', {
			name: 'Deselect all items',
		} );
		fireEvent.click( deselectAllCheckbox );

		expect( speak ).toHaveBeenCalledWith(
			'All items deselected.',
			'polite'
		);
	} );
} );
