/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { AddressFieldControls } from '../address-field-controls';

const mockEditEntityRecord = jest.fn();
const mockDispatch = jest.fn( () => ( {
	editEntityRecord: mockEditEntityRecord,
} ) );
const mockUseCheckoutBlockContext = jest.fn();

jest.mock( '@wordpress/block-editor', () => ( {
	InspectorControls: jest.fn( ( { children } ) => <div>{ children }</div> ),
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	dispatch: ( store: string ) => mockDispatch( store ),
} ) );

jest.mock( '@wordpress/core-data', () => ( {
	store: 'core',
} ) );

jest.mock( '../context', () => ( {
	useCheckoutBlockContext: () => mockUseCheckoutBlockContext(),
} ) );

type FieldName = 'company' | 'address_2' | 'phone';
type FieldState = 'hidden' | 'optional' | 'required';

const fields: Array< {
	label: string;
	name: FieldName;
	option: string;
} > = [
	{
		label: 'Company',
		name: 'company',
		option: 'woocommerce_checkout_company_field',
	},
	{
		label: 'Address line 2',
		name: 'address_2',
		option: 'woocommerce_checkout_address_2_field',
	},
	{
		label: 'Phone',
		name: 'phone',
		option: 'woocommerce_checkout_phone_field',
	},
];

const getField = ( state: FieldState ) => ( {
	hidden: state === 'hidden',
	required: state === 'required',
} );

const renderControls = ( field: FieldName, state: FieldState ) => {
	mockUseCheckoutBlockContext.mockReturnValue( {
		defaultFields: {
			company: getField( field === 'company' ? state : 'hidden' ),
			address_2: getField( field === 'address_2' ? state : 'hidden' ),
			phone: getField( field === 'phone' ? state : 'hidden' ),
		},
	} );

	render( <AddressFieldControls /> );
};

const expectUpdate = ( option: string, value: FieldState ) => {
	expect( mockDispatch ).toHaveBeenCalledWith( 'core' );
	expect( mockEditEntityRecord ).toHaveBeenCalledTimes( 1 );
	expect( mockEditEntityRecord ).toHaveBeenCalledWith(
		'root',
		'site',
		undefined,
		{ [ option ]: value }
	);
};

describe.each( fields )( '$label address field control', ( field ) => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'shows a hidden field as optional', async () => {
		const user = userEvent.setup();
		renderControls( field.name, 'hidden' );

		const visibilityToggle = screen.getByRole( 'checkbox', {
			name: field.label,
		} );
		expect( visibilityToggle ).not.toBeChecked();

		await user.click( visibilityToggle );

		expectUpdate( field.option, 'optional' );
	} );

	it( 'hides an optional field', async () => {
		const user = userEvent.setup();
		renderControls( field.name, 'optional' );

		const visibilityToggle = screen.getByRole( 'checkbox', {
			name: field.label,
		} );
		expect( visibilityToggle ).toBeChecked();
		expect(
			screen.getByRole( 'radio', { name: 'Optional' } )
		).toBeChecked();

		await user.click( visibilityToggle );

		expectUpdate( field.option, 'hidden' );
	} );

	it( 'makes an optional field required', async () => {
		const user = userEvent.setup();
		renderControls( field.name, 'optional' );

		expect(
			screen.getByRole( 'radio', { name: 'Optional' } )
		).toBeChecked();
		await user.click( screen.getByRole( 'radio', { name: 'Required' } ) );

		expectUpdate( field.option, 'required' );
	} );

	it( 'makes a required field optional', async () => {
		const user = userEvent.setup();
		renderControls( field.name, 'required' );

		expect(
			screen.getByRole( 'radio', { name: 'Required' } )
		).toBeChecked();
		await user.click( screen.getByRole( 'radio', { name: 'Optional' } ) );

		expectUpdate( field.option, 'optional' );
	} );
} );
