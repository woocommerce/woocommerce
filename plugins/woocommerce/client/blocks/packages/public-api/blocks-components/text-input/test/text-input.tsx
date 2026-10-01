/**
 * External dependencies
 */
// screen is re-exported from @testing-library/dom, which import/named cannot resolve here.
// eslint-disable-next-line import/named
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { bind } from '@woocommerce/input-mask';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import TextInput from '../text-input';
import ValidatedTextInput from '../validated-text-input';

const setInputMask = ( inputMask?: { bind: typeof bind } ) => {
	( window as unknown as { wc: unknown } ).wc = { ...window.wc, inputMask };
};

describe( 'TextInput with a mask', () => {
	afterEach( () => {
		setInputMask( undefined );
	} );

	it.each( [ true, false ] )(
		'syncs parent changes with the mask script loaded: %p',
		async ( loaded ) => {
			if ( loaded ) {
				setInputMask( { bind } );
			}
			const user = userEvent.setup();
			const props = {
				id: 'phone',
				label: 'Phone',
				mask: '000-000',
				onChange: jest.fn(),
			};
			const { rerender } = render(
				<TextInput { ...props } value="123456" />
			);
			const input = screen.getByLabelText( 'Phone' );
			await act( () => user.type( input, '7' ) );
			rerender( <TextInput { ...props } value="654321" /> );
			expect( input ).toHaveValue( loaded ? '654-321' : '654321' );
			await act( () => user.tab() );
			rerender( <TextInput { ...props } value="" /> );
			expect( input ).toHaveValue( '' );
			expect( input.parentElement ).not.toHaveClass( 'is-active' );
		}
	);

	it( 'keeps typed literals and the caret when the parent echoes the raw value', async () => {
		setInputMask( { bind } );
		const user = userEvent.setup();
		const ControlledInput = () => {
			const [ value, setValue ] = useState( '' );
			return (
				<TextInput
					id="phone"
					label="Phone"
					mask="+00-00"
					value={ value }
					onChange={ setValue }
				/>
			);
		};
		render( <ControlledInput /> );
		const input = screen.getByLabelText( 'Phone' ) as HTMLInputElement;
		await act( () => user.type( input, '+12345' ) );
		expect( input ).toHaveValue( '+12345' );
		expect( input.selectionStart ).toBe( 6 );
	} );

	it( 'formats raw props without consuming literal digits', () => {
		setInputMask( { bind } );
		render(
			<TextInput
				id="code"
				label="Code"
				mask="10000"
				value="1234"
				onChange={ () => void 0 }
			/>
		);
		expect( screen.getByLabelText( 'Code' ) ).toHaveValue( '11234' );
	} );

	it( 'keeps a literal-only field label active after blur and clears it on reset', async () => {
		setInputMask( { bind } );
		const user = userEvent.setup();
		const props = {
			id: 'phone',
			label: 'Phone',
			mask: '+00-00',
			onChange: jest.fn(),
		};
		const { rerender } = render( <TextInput { ...props } value="" /> );
		const input = screen.getByLabelText( 'Phone' );
		await act( () => user.type( input, '+' ) );
		await act( () => user.tab() );
		expect( input ).toHaveValue( '+' );
		expect( input.parentElement ).toHaveClass( 'is-active' );
		rerender( <TextInput { ...props } value="1234" /> );
		rerender( <TextInput { ...props } value="" /> );
		expect( input ).toHaveValue( '' );
		expect( input.parentElement ).not.toHaveClass( 'is-active' );
	} );

	it( 'applies a changed mask to the raw value', () => {
		setInputMask( { bind } );
		const props = {
			id: 'code',
			label: 'Code',
			value: '123456',
			onChange: jest.fn(),
		};
		const { rerender } = render(
			<TextInput { ...props } mask="000-000" />
		);
		rerender( <TextInput { ...props } mask="00.0000" /> );
		expect( screen.getByLabelText( 'Code' ) ).toHaveValue( '12.3456' );
	} );

	it.each( [
		[ undefined, 'A&B' ],
		[ '*-*-*', 'A-&-B' ],
	] )( 'keeps entity decoding for mask %p', ( mask, display ) => {
		setInputMask( { bind } );
		render(
			<TextInput
				id="code"
				label="Code"
				value="A&amp;B"
				mask={ mask }
				onChange={ jest.fn() }
			/>
		);
		expect( screen.getByLabelText( 'Code' ) ).toHaveValue( display );
	} );

	it( 'keeps the mask hint and other descriptions on checkout fields', () => {
		setInputMask( { bind } );
		render(
			<>
				<p id="extra-help">Enter your code.</p>
				<ValidatedTextInput
					id="code"
					label="Code"
					mask="000-000"
					ariaDescribedBy="extra-help"
					value=""
					onChange={ () => void 0 }
				/>
			</>
		);
		expect( screen.getByLabelText( 'Code' ) ).toHaveAccessibleDescription(
			'Enter your code. Expected format: 000-000'
		);
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
