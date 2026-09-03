/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { bind } from '@woocommerce/input-mask';

/**
 * Internal dependencies
 */
import TextInput from '../text-input';

const setInputMask = ( inputMask?: { bind: typeof bind } ) => {
	( window as unknown as { wc: unknown } ).wc = { ...window.wc, inputMask };
};

describe( 'TextInput with a mask', () => {
	afterEach( () => {
		setInputMask( undefined );
	} );

	it( 'shows the formatted text and reports the raw value', async () => {
		setInputMask( { bind } );
		const user = userEvent.setup();
		const onChange = jest.fn();

		render(
			<TextInput
				id="phone"
				label="Phone"
				value=""
				onChange={ onChange }
				mask="000-000"
			/>
		);

		const input = screen.getByLabelText( 'Phone' );
		await act( () => user.type( input, '1234' ) );

		expect( input ).toHaveValue( '123-4' );
		expect( onChange ).toHaveBeenLastCalledWith( '1234' );
		expect( input ).toHaveAccessibleDescription(
			'Expected format: 000-000'
		);
	} );

	it( 'shows the formatted initial value', () => {
		setInputMask( { bind } );

		render(
			<TextInput
				id="cpf"
				label="CPF"
				value="12345678901"
				onChange={ () => void 0 }
				mask="000.000.000-00"
			/>
		);

		expect( screen.getByLabelText( 'CPF' ) ).toHaveValue(
			'123.456.789-01'
		);
	} );

	it( 'falls back to a plain input when the mask script is not loaded', async () => {
		const user = userEvent.setup();
		const onChange = jest.fn();

		render(
			<TextInput
				id="phone"
				label="Phone"
				value=""
				onChange={ onChange }
				mask="000-000"
			/>
		);

		const input = screen.getByLabelText( 'Phone' );
		await act( () => user.type( input, '1234' ) );

		expect( input ).toHaveValue( '1234' );
		expect( onChange ).toHaveBeenLastCalledWith( '1234' );
	} );
} );
