/**
 * External dependencies
 */
import { render, renderHook, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { RadioControl, TextControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Form, useFormContext } from '../';

describe( 'Form control props', () => {
	const initialValues = { name: '', size: 'small' };

	it( 'omits props that belong to other controls', () => {
		const { result } = renderHook(
			() => useFormContext< typeof initialValues >(),
			{
				wrapper: ( { children } ) => (
					<Form initialValues={ initialValues }>{ children }</Form>
				),
			}
		);

		const text = result.current.getTextControlProps( 'name' );
		expect( text ).not.toHaveProperty( 'checked' );
		expect( text ).not.toHaveProperty( 'selected' );

		const radio = result.current.getRadioControlProps( 'size' );
		expect( radio ).not.toHaveProperty( 'checked' );
		expect( radio ).not.toHaveProperty( 'value' );
	} );

	it( 'binds text and radio controls to their respective fields', () => {
		const onChange = jest.fn();
		render(
			<Form initialValues={ initialValues } onChange={ onChange }>
				{ ( { getRadioControlProps, getTextControlProps } ) => (
					<>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label="Name"
							{ ...getTextControlProps( 'name' ) }
						/>
						<RadioControl
							label="Size"
							options={ [
								{ label: 'Small', value: 'small' },
								{ label: 'Large', value: 'large' },
							] }
							{ ...getRadioControlProps( 'size' ) }
						/>
					</>
				) }
			</Form>
		);

		expect( screen.getByRole( 'radio', { name: 'Small' } ) ).toBeChecked();
		userEvent.type( screen.getByRole( 'textbox', { name: 'Name' } ), 'A' );
		userEvent.click( screen.getByRole( 'radio', { name: 'Large' } ) );

		expect( onChange ).toHaveBeenLastCalledWith(
			{ name: 'size', value: 'large' },
			{ name: 'A', size: 'large' },
			true
		);
		expect( screen.getByRole( 'radio', { name: 'Large' } ) ).toBeChecked();
	} );
} );
