/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { noop } from 'lodash';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import PhoneNumberInput from '..';

describe( 'PhoneNumberInput', () => {
	it( 'should match snapshot', () => {
		const { container } = render(
			<PhoneNumberInput value="" onChange={ noop } />
		);
		expect( container ).toMatchSnapshot();
	} );

	it( 'should match snapshot with custom renders', () => {
		const { container } = render(
			<PhoneNumberInput
				value=""
				onChange={ noop }
				selectedRender={ ( { name } ) => name }
				itemRender={ ( { code } ) => code }
				arrowRender={ () => '⬇️' }
			/>
		);
		expect( container ).toMatchSnapshot();
	} );

	it( 'should render with provided `id`', () => {
		render( <PhoneNumberInput id="test-id" value="" onChange={ noop } /> );
		expect( screen.getByRole( 'textbox' ) ).toHaveAttribute(
			'id',
			'test-id'
		);
	} );

	it( 'calls onChange callback on number input', () => {
		const onChange = jest.fn();
		render( <PhoneNumberInput value="" onChange={ onChange } /> );

		const input = screen.getByRole( 'textbox' );
		userEvent.type( input, '1' );

		expect( onChange ).toHaveBeenCalledWith( '+1 1', '+11', 'US' );
	} );

	it( 'calls onChange callback when a country is selected', () => {
		const onChange = jest.fn();
		render( <PhoneNumberInput value="0 0" onChange={ onChange } /> );

		const select = screen.getByRole( 'button' );
		userEvent.click( select );

		const option = screen.getByRole( 'option', { name: /es/i } );
		userEvent.click( option );

		expect( onChange ).toHaveBeenCalledWith( '+34 0 0', '+3400', 'ES' );
	} );

	it( 'prevents consecutive spaces and hyphens', () => {
		const onChange = jest.fn();
		render( <PhoneNumberInput value="0-" onChange={ onChange } /> );

		const input = screen.getByRole( 'textbox' );
		userEvent.type( input, '-' );

		expect( onChange ).not.toHaveBeenCalled();
	} );
} );
