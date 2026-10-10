/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import CustomSelectControl from '..';

const options = [
	{ key: 'individual', name: 'Individual' },
	{ key: 'company', name: 'Company' },
];

describe( 'CustomSelectControl', () => {
	it( 'opens the options menu when the toggle button is clicked', () => {
		render(
			<CustomSelectControl
				label="Business type"
				options={ options }
				placeholder="Select an option"
			/>
		);

		fireEvent.click(
			screen.getByRole( 'combobox', { name: 'Business type' } )
		);

		expect( screen.getByText( 'Individual' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Company' ) ).toBeInTheDocument();
	} );

	it( 'selects the highlighted option when navigating with keyboard commands', () => {
		const onChange = jest.fn();

		render(
			<CustomSelectControl
				label="Business type"
				options={ options }
				onChange={ onChange }
				placeholder="Select an option"
				value={ options[ 0 ] }
			/>
		);

		const select = screen.getByRole( 'combobox', {
			name: 'Business type',
		} );

		fireEvent.keyDown( select, { key: 'ArrowDown' } );
		fireEvent.keyDown( select, { key: 'ArrowDown' } );
		fireEvent.keyDown( select, { key: 'Enter' } );

		expect( onChange ).toHaveBeenLastCalledWith(
			expect.objectContaining( {
				selectedItem: options[ 1 ],
			} )
		);
	} );
} );
