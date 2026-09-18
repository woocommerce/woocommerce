/**
 * External dependencies
 */
import { act, fireEvent, render, screen } from '@testing-library/react';
import { validationStore } from '@woocommerce/block-data';
import type { Field, OrderFormValues } from '@woocommerce/settings';
import { dispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Form from '../form';

const TERMS_FIELD_KEY = 'test-plugin/terms';
const TERMS_LABEL = 'I agree to the terms';
const REQUIRED_CHECKBOX_ERROR =
	'Please check the box or you will be unable to order';

// `errorMessage` reaches the client on the field config itself, added by
// CheckboxFieldType from the `error_message` the field was registered with. The
// `Field` type does not carry it, so it is spelled out here.
const termsField: Field & { errorMessage: string } = {
	label: TERMS_LABEL,
	optionalLabel: `${ TERMS_LABEL } (optional)`,
	autocomplete: 'off',
	required: true,
	hidden: false,
	validation: [],
	index: 0,
	type: 'checkbox',
	errorMessage: REQUIRED_CHECKBOX_ERROR,
};

const mockDefaultFields = {
	[ TERMS_FIELD_KEY ]: termsField,
};

// The form reads the field config from `useCheckoutAddress`, which otherwise
// serves the store's own fields rather than a registered one.
jest.mock( '@woocommerce/base-context', () => ( {
	...jest.requireActual( '@woocommerce/base-context' ),
	useCheckoutAddress: () => ( { defaultFields: mockDefaultFields } ),
} ) );

describe( 'Form', () => {
	const TermsForm = () => {
		// A checkbox field holds a boolean, which `OrderFormValues` narrows to
		// string.
		const [ values, setValues ] = useState< OrderFormValues >( {
			[ TERMS_FIELD_KEY ]: false,
		} as unknown as OrderFormValues );

		return (
			<Form
				addressType="order"
				fields={ [ TERMS_FIELD_KEY ] }
				values={ values }
				onChange={ setValues }
			/>
		);
	};

	it( "shows a required checkbox field's registered error message and clears it once the box is checked", async () => {
		render( <TermsForm /> );

		// A required field is named by its label. The optional label, which the
		// form picks for a field that is not required, reads differently.
		const checkbox = screen.getByRole( 'checkbox', { name: TERMS_LABEL } );

		// The control renders its error region only for an error that is both
		// present and revealed. Validation runs on mount but starts hidden,
		// which is what a shopper sees before they try to place the order.
		expect( screen.queryByRole( 'alert' ) ).not.toBeInTheDocument();

		await act( async () => {
			dispatch( validationStore ).showAllValidationErrors();
		} );

		// The generated fallback message is a different string, so matching this
		// one inside the checkbox's own error region means the field's message
		// travelled from the config through the form to the control.
		expect( screen.getByRole( 'alert' ) ).toHaveTextContent(
			REQUIRED_CHECKBOX_ERROR
		);

		fireEvent.click( checkbox );

		expect(
			screen.getByRole( 'checkbox', { name: TERMS_LABEL } )
		).toBeChecked();
		expect( screen.queryByRole( 'alert' ) ).not.toBeInTheDocument();
	} );
} );
