/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import '../../../test-helper/global-mock';
import FulfillmentLineItem from '../fulfillment-line-item';

jest.mock( '@wordpress/components', () => ( {
	CheckboxControl: ( { value, checked, onChange } ) => (
		<input
			type="checkbox"
			data-testid={ `checkbox-${ value }` }
			checked={ checked }
			onChange={ ( e ) => onChange( e.target.checked ) }
		/>
	),
	Icon: ( { icon, onClick } ) => (
		<div
			role="button"
			tabIndex={ 0 }
			data-testid={ `icon-${ icon }` }
			onClick={ onClick }
			onKeyUp={ () => {} }
		></div>
	),
} ) );

describe( 'FulfillmentLineItem', () => {
	const mockToggleItem = jest.fn();
	const mockIsChecked = jest.fn();
	const mockIsIndeterminate = jest.fn();

	const item = {
		id: '1',
		name: 'Test Item',
		sku: 'SKU123',
		total: '100',
		quantity: 1,
		image: { src: 'image-src' },
	};

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders item details', () => {
		render(
			<FulfillmentLineItem
				item={ item }
				quantity={ 1 }
				currency="USD"
				editMode={ false }
				toggleItem={ mockToggleItem }
				isChecked={ mockIsChecked }
				isIndeterminate={ mockIsIndeterminate }
			/>
		);

		expect( screen.getByText( 'Test Item' ) ).toBeInTheDocument();
		expect( screen.getByText( 'SKU123' ) ).toBeInTheDocument();
		expect( screen.getByAltText( 'Test Item' ) ).toBeInTheDocument();
		expect( screen.getByText( '$100.00' ) ).toBeInTheDocument();
	} );

	it( 'renders checkbox in edit mode', () => {
		mockIsChecked.mockReturnValue( true );
		render(
			<FulfillmentLineItem
				item={ item }
				quantity={ 1 }
				currency="USD"
				editMode={ true }
				toggleItem={ mockToggleItem }
				isChecked={ mockIsChecked }
				isIndeterminate={ mockIsIndeterminate }
			/>
		);

		const checkbox = screen.getByTestId( 'checkbox-1' );
		expect( checkbox ).toBeInTheDocument();
		expect( checkbox ).toBeChecked();

		fireEvent.click( checkbox );
		expect( mockToggleItem ).toHaveBeenCalledWith( '1', -1, false );
	} );

	it( 'toggles item expansion when quantity > 1 in edit mode', () => {
		render(
			<FulfillmentLineItem
				item={ item }
				quantity={ 2 }
				currency="USD"
				editMode={ true }
				toggleItem={ mockToggleItem }
				isChecked={ mockIsChecked }
				isIndeterminate={ mockIsIndeterminate }
			/>
		);

		const icon = screen.getByTestId( 'icon-arrow-down-alt2' );
		expect( icon ).toBeInTheDocument();

		fireEvent.click( icon );
		expect( screen.getByText( 'x2' ) ).toBeInTheDocument();
	} );
} );
