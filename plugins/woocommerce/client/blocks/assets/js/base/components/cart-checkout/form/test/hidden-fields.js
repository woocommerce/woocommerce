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

const FIELD_KEY = 'test/vat-number';
const FIELD_LABEL = 'VAT number (optional)';
const DEFAULT_VALUE = 'GB123456789';

let mockDefaultFields = {};

jest.mock( '@woocommerce/base-context', () => ( {
	useCheckoutAddress: () => ( { defaultFields: mockDefaultFields } ),
} ) );
jest.mock( '../use-form-fields' );
jest.mock( '../use-form-validation', () => ( {
	useFormValidation: () => ( { errors: {}, previousErrors: undefined } ),
} ) );

const conditionalHiddenRule = {
	checkout: {
		properties: {
			additional_fields: {
				properties: {
					'test/customer-type': { not: { const: 'business' } },
				},
			},
		},
	},
};

const setup = ( { hidden, hasHiddenRules = true } ) => {
	let isHidden = hidden;
	let currentValues;

	mockDefaultFields = {
		[ FIELD_KEY ]: hasHiddenRules ? { hidden: conditionalHiddenRule } : {},
	};

	useFormFields.mockImplementation( () => [
		{
			key: FIELD_KEY,
			label: 'VAT number',
			optionalLabel: FIELD_LABEL,
			required: false,
			hidden: isHidden,
			type: 'text',
		},
	] );

	const TestForm = () => {
		const [ values, setValues ] = useState( {
			[ FIELD_KEY ]: DEFAULT_VALUE,
		} );
		currentValues = values;

		return (
			<Form
				id="order"
				addressType="order"
				fields={ [ FIELD_KEY ] }
				values={ values }
				onChange={ setValues }
			/>
		);
	};

	const { rerender } = render( <TestForm /> );

	return {
		setHidden: ( value ) => {
			isHidden = value;
			rerender( <TestForm /> );
		},
		getValue: () => currentValues[ FIELD_KEY ],
	};
};

describe( 'Form hidden fields', () => {
	it( 'clears a field hidden by rules but restores its initial value when first shown', () => {
		const { setHidden, getValue } = setup( { hidden: true } );

		expect( screen.queryByLabelText( FIELD_LABEL ) ).toBeNull();
		expect( getValue() ).toBe( '' );

		setHidden( false );

		expect( screen.getByLabelText( FIELD_LABEL ) ).toHaveValue(
			DEFAULT_VALUE
		);
	} );

	it( 'does not restore the initial value after the field has been shown', () => {
		const { setHidden, getValue } = setup( { hidden: true } );

		setHidden( false );
		setHidden( true );
		expect( getValue() ).toBe( '' );

		setHidden( false );
		expect( screen.getByLabelText( FIELD_LABEL ) ).toHaveValue( '' );
	} );

	it( 'keeps a value the shopper cleared after hiding and showing the field', () => {
		const { setHidden } = setup( { hidden: false } );

		fireEvent.change( screen.getByLabelText( FIELD_LABEL ), {
			target: { value: '' },
		} );
		setHidden( true );
		setHidden( false );

		expect( screen.getByLabelText( FIELD_LABEL ) ).toHaveValue( '' );
	} );

	it( 'does not restore values for fields hidden without conditional rules', () => {
		const { setHidden, getValue } = setup( {
			hidden: true,
			hasHiddenRules: false,
		} );

		expect( getValue() ).toBe( '' );

		setHidden( false );
		expect( screen.getByLabelText( FIELD_LABEL ) ).toHaveValue( '' );
	} );
} );
