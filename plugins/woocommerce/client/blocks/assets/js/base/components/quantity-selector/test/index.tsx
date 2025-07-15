/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import QuantitySelector from '../index';
import type { QuantitySelectorProps } from '../types';

const defaults = {
	disabled: false,
	editable: true,
	itemName: 'product',
	maximum: 9999,
	onChange: () => void 0,
} as QuantitySelectorProps;

describe( 'QuantitySelector', () => {
	it( 'The quantity step buttons are rendered when the quantity is editable', () => {
		const { rerender } = render( <QuantitySelector { ...defaults } /> );

		expect(
			screen.getByLabelText(
				`Increase quantity of ${ defaults.itemName }`
			)
		).toBeInTheDocument();
		expect(
			screen.getByLabelText( `Reduce quantity of ${ defaults.itemName }` )
		).toBeInTheDocument();

		rerender( <QuantitySelector { ...defaults } editable={ false } /> );

		expect(
			screen.queryByLabelText(
				`Increase quantity of ${ defaults.itemName }`
			)
		).not.toBeInTheDocument();
		expect(
			screen.queryByLabelText(
				`Reduce quantity of ${ defaults.itemName }`
			)
		).not.toBeInTheDocument();
	} );

	it( 'resets expected quantity type after successful prop update', async () => {
		const user = userEvent.setup();
		const onChange = jest.fn();

		const { rerender } = render(
			<QuantitySelector
				{ ...defaults }
				quantity={ 10 }
				onChange={ onChange }
			/>
		);

		// Click increase button
		const decreaseButton = screen.getByLabelText(
			`Reduce quantity of ${ defaults.itemName }`
		);
		await act( () => user.click( decreaseButton ) );

		// Verify onChange was called with new quantity
		expect( onChange ).toHaveBeenCalledWith( 9 );
		rerender(
			<QuantitySelector
				{ ...defaults }
				quantity={ 9 }
				onChange={ onChange }
			/>
		);

		// The input should reflect the new quantity (3)
		let input = screen.getByLabelText(
			`Quantity of ${ defaults.itemName } in your cart.`
		);
		expect( input ).toHaveValue( 9 );
		// Now test that a subsequent prop change is not blocked
		rerender(
			<QuantitySelector
				{ ...defaults }
				quantity={ 30 }
				onChange={ onChange }
			/>
		);

		// The input should reflect the new quantity (3)
		input = screen.getByLabelText(
			`Quantity of ${ defaults.itemName } in your cart.`
		);
		expect( input ).toHaveValue( 30 );
	} );
} );
