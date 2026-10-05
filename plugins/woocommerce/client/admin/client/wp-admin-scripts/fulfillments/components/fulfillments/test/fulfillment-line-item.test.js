import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import '../../../test-helper/global-mock';
import FulfillmentLineItem from '../fulfillment-line-item';
vi.mock( '@wordpress/components', () => {
	const mock = {
		CheckboxControl: ( { value, checked, onChange } ) => (
			<input
				type="checkbox"
				data-testid={ `checkbox-${ value }` }
				checked={ checked }
				onChange={ ( e ) => onChange( e.target.checked ) }
			/>
		),
		Button: ( { onClick, children, ...props } ) => (
			<button onClick={ onClick } { ...props }>
				{ children }
			</button>
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
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'FulfillmentLineItem', () => {
	const mockToggleItem = vi.fn();
	const mockIsChecked = vi.fn();
	const mockIsIndeterminate = vi.fn();
	const item = {
		id: '1',
		name: 'Test Item',
		sku: 'SKU123',
		total: '100',
		quantity: 1,
		image: {
			src: 'image-src',
		},
	};
	beforeEach( () => {
		vi.clearAllMocks();
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
