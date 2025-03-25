/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import QuantitySelector, { QuantitySelectorProps } from '../index';

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
} );
