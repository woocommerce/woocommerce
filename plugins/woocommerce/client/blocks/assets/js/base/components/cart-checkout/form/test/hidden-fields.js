/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Form from '../form';
import { useFormFields } from '../use-form-fields';

jest.mock( '@woocommerce/base-context', () => ( {
	useCheckoutAddress: () => ( { defaultFields: {} } ),
} ) );

jest.mock( '../use-form-fields' );
jest.mock( '../use-form-validation', () => ( {
	useFormValidation: () => ( { errors: {}, previousErrors: undefined } ),
} ) );

describe( 'Form hidden fields', () => {
	it( 'preserves an untouched default until first display without restoring a cleared value', () => {
		let hidden = true;
		useFormFields.mockImplementation( () => [
			{
				key: 'test/vat-number',
				label: 'VAT number',
				optionalLabel: 'VAT number (optional)',
				required: false,
				hidden,
				type: 'text',
			},
		] );

		const TestForm = () => {
			const [ values, setValues ] = useState( {
				'test/vat-number': 'GB123456789',
			} );

			return (
				<Form
					id="order"
					addressType="order"
					fields={ [ 'test/vat-number' ] }
					values={ values }
					onChange={ setValues }
				/>
			);
		};

		const { rerender } = render( <TestForm /> );

		expect( screen.queryByLabelText( 'VAT number (optional)' ) ).toBeNull();

		hidden = false;
		rerender( <TestForm /> );
		expect( screen.getByLabelText( 'VAT number (optional)' ) ).toHaveValue(
			'GB123456789'
		);

		fireEvent.change( screen.getByLabelText( 'VAT number (optional)' ), {
			target: { value: '' },
		} );
		hidden = true;
		rerender( <TestForm /> );
		hidden = false;
		rerender( <TestForm /> );

		expect( screen.getByLabelText( 'VAT number (optional)' ) ).toHaveValue(
			''
		);
	} );
} );
