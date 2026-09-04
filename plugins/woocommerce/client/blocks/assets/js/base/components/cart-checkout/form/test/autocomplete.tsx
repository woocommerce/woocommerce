/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { CheckoutProvider } from '@woocommerce/base-context';
import {
	ADDRESS_FORM_KEYS,
	CONTACT_FORM_KEYS,
} from '@woocommerce/block-settings';
import type {
	AddressFormType,
	AddressFormValues,
	Field,
} from '@woocommerce/settings';
import type { ReactElement } from 'react';

/**
 * Internal dependencies
 */
import AddressLineFields from '../address-line-fields';
import Form from '../form';

const renderInCheckoutProvider = ( ui: ReactElement ) =>
	render( <CheckoutProvider>{ ui }</CheckoutProvider> );

const address1Field = (
	autocomplete: string
): { field: Field & { key: 'address_1' }; value: string } => ( {
	field: {
		index: 0,
		key: 'address_1',
		required: true,
		label: 'Address',
		optionalLabel: 'Address (optional)',
		type: 'text',
		hidden: false,
		validation: [],
		autocomplete,
	},
	value: '',
} );

const address2Field: { field: Field & { key: 'address_2' }; value: string } = {
	field: {
		index: 1,
		key: 'address_2',
		required: false,
		label: 'Apartment, suite, etc.',
		optionalLabel: 'Apartment, suite, etc. (optional)',
		type: 'text',
		hidden: false,
		validation: [],
		autocomplete: 'address-line2',
	},
	value: '',
};

const renderAddressLines = ( {
	addressType,
	autocomplete = 'address-line1',
}: {
	addressType: AddressFormType;
	autocomplete?: string;
} ) =>
	renderInCheckoutProvider(
		<AddressLineFields
			formId="test"
			address1={ address1Field( autocomplete ) }
			address2={ address2Field }
			addressType={ addressType }
			onChange={ jest.fn() }
		/>
	);

describe( 'Checkout field autocomplete attribute', () => {
	it( 'prefixes a billing address field with the billing section', () => {
		renderAddressLines( { addressType: 'billing' } );

		expect( screen.getByLabelText( 'Address' ) ).toHaveAttribute(
			'autocomplete',
			'section-billing billing address-line1'
		);
	} );

	it( 'prefixes a shipping address field with the shipping section', () => {
		renderAddressLines( { addressType: 'shipping' } );

		expect( screen.getByLabelText( 'Address' ) ).toHaveAttribute(
			'autocomplete',
			'section-shipping shipping address-line1'
		);
	} );

	it( 'leaves a field opted out of autofill as "off"', () => {
		renderAddressLines( { addressType: 'billing', autocomplete: 'off' } );

		expect( screen.getByLabelText( 'Address' ) ).toHaveAttribute(
			'autocomplete',
			'off'
		);
	} );

	it( 'gives the hidden address_2 catcher the value it computed for the visible sibling', () => {
		renderAddressLines( { addressType: 'billing' } );

		const hiddenInput = screen.getByLabelText( 'Apartment, suite, etc.' );
		expect( hiddenInput ).toHaveAttribute( 'aria-hidden', 'true' );
		expect( hiddenInput ).toHaveAttribute(
			'autocomplete',
			'section-billing billing address-line2'
		);

		fireEvent.change( hiddenInput, { target: { value: '4B' } } );

		expect(
			screen.getByLabelText( 'Apartment, suite, etc. (optional)' )
		).toHaveAttribute(
			'autocomplete',
			'section-billing billing address-line2'
		);
	} );

	it( 'keeps the prefix on the country select, which renders through Select', () => {
		renderInCheckoutProvider(
			<Form
				addressType="billing"
				fields={ ADDRESS_FORM_KEYS }
				values={ { country: 'GB' } as AddressFormValues }
				onChange={ jest.fn() }
			/>
		);

		expect( screen.getByLabelText( /Country\/Region/ ) ).toHaveAttribute(
			'autocomplete',
			'section-billing billing country'
		);
	} );

	it( 'does not prefix contact fields, whose address type is not valid autofill grammar', () => {
		renderInCheckoutProvider(
			<Form
				addressType="contact"
				fields={ CONTACT_FORM_KEYS }
				values={ { email: '' } }
				onChange={ jest.fn() }
			/>
		);

		expect( screen.getByLabelText( 'Email address' ) ).toHaveAttribute(
			'autocomplete',
			'email'
		);
	} );
} );
