/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Combobox from '@woocommerce/base-components/combobox';

describe( 'ComboBox', () => {
	const options = [
		{ value: 'A', label: 'A value A' },
		{ value: 'B', label: 'B value B' },
		{ value: 'C', label: 'C value C' },
		{ value: 'D', label: 'D value D' },
		{ value: 'E', label: 'E value E' },
		{ value: 'F', label: 'F value F' },
	];

	beforeEach( () => {
		jest.resetAllMocks();
	} );

	it( 'calls onChange as soon as a value is changed if requireExactMatch is false or undefined', async () => {
		const onChange = jest.fn();
		const label = 'combo-box';
		render(
			<Combobox
				options={ options }
				value=""
				onChange={ onChange }
				label={ label }
			/>
		);
		const input = await screen.findByLabelText( label );
		await userEvent.type( input, 'A ' );
		expect( onChange ).toHaveBeenCalledWith( 'A' );
	} );

	it( 'calls onChange only when the value is equal to one of the options when requireExactMatch is true', async () => {
		const onChange = jest.fn();
		const label = 'combo-box';
		render(
			<Combobox
				options={ options }
				value=""
				onChange={ onChange }
				label={ label }
				requireExactMatch={ true }
			/>
		);
		const input = await screen.findByLabelText( label );
		await userEvent.type( input, 'A ' );
		expect( onChange ).not.toHaveBeenCalled();
		await userEvent.type( input, 'value A' );
		expect( onChange ).toHaveBeenCalledWith( 'A' );
	} );
} );
