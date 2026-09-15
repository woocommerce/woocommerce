/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { Form } from '@woocommerce/base-components/cart-checkout';
import type { FormFields, ShippingAddress } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { Edit } from '../edit';
import type { Attributes } from '../types';

let mockFieldSettings: Record< string, string > = {};
let mockCapturedDefaultFields: FormFields | undefined;
let mockRenderingCheckoutEdit = false;

jest.mock( '@wordpress/data', () => {
	const data = jest.requireActual( '@wordpress/data' );
	return {
		...data,
		useSelect: (
			mapSelect: ( select: ( store: unknown ) => unknown ) => unknown,
			dependencies?: unknown[]
		) => {
			if ( mockRenderingCheckoutEdit ) {
				return mapSelect( () => ( {
					getEditedEntityRecord: () => mockFieldSettings,
				} ) );
			}

			return data.useSelect( mapSelect, dependencies );
		},
	};
} );

jest.mock( '@wordpress/block-editor', () => {
	const InnerBlocks = Object.assign(
		jest.fn( () => null ),
		{
			Content: jest.fn( () => null ),
		}
	);
	const useBlockProps = Object.assign(
		jest.fn( () => ( {} ) ),
		{
			save: jest.fn( () => ( {} ) ),
		}
	);

	return {
		InnerBlocks,
		InspectorControls: jest.fn( ( { children } ) => <>{ children }</> ),
		useBlockProps,
	};
} );

jest.mock( '@woocommerce/base-context', () => ( {
	...jest.requireActual( '@woocommerce/base-context' ),
	CheckoutProvider: jest.fn( ( { children } ) => <>{ children }</> ),
	EditorProvider: jest.fn( ( { children, previewData } ) => {
		mockCapturedDefaultFields = previewData.defaultFields;
		return <>{ children }</>;
	} ),
	useCheckoutAddress: jest.fn( () => ( {
		defaultFields: mockCapturedDefaultFields,
	} ) ),
} ) );

jest.mock( '@woocommerce/base-components/sidebar-layout', () => ( {
	SidebarLayout: jest.fn( ( { children, className } ) => (
		<div className={ className }>{ children }</div>
	) ),
} ) );

jest.mock( '@woocommerce/blocks-checkout', () => ( {
	...jest.requireActual( '@woocommerce/blocks-checkout' ),
	SlotFillProvider: jest.fn( ( { children } ) => <>{ children }</> ),
} ) );

jest.mock( '../../cart-checkout-shared', () => ( {
	addClassToBody: jest.fn(),
	BlockSettings: jest.fn( () => null ),
	useBlockPropsWithLocking: jest.fn( () => ( {} ) ),
} ) );

jest.mock( '../inner-blocks', () => ( {} ) );

const checkoutAttributes: Attributes = {
	hasDarkControls: false,
	showFormStepNumbers: false,
	showOrderNotes: true,
	showPolicyLinks: true,
	showReturnToCart: false,
	showRateAfterTaxName: false,
	cartPageId: 1,
	showCompanyField: false,
	requireCompanyField: false,
	showApartmentField: false,
	requireApartmentField: false,
	showPhoneField: false,
	requirePhoneField: false,
};

const emptyShippingAddress: ShippingAddress = {
	first_name: '',
	last_name: '',
	company: '',
	address_1: '',
	address_2: '',
	city: '',
	state: '',
	postcode: '',
	country: '',
	phone: '',
};

const renderCheckoutEdit = (
	fieldSettings: Record< string, string > = {},
	hasDarkControls = false
) => {
	mockFieldSettings = fieldSettings;
	mockCapturedDefaultFields = undefined;
	mockRenderingCheckoutEdit = true;
	const result = render(
		<Edit
			clientId="checkout-client-id"
			attributes={ { ...checkoutAttributes, hasDarkControls } }
			setAttributes={ jest.fn() }
		/>
	);
	mockRenderingCheckoutEdit = false;

	if ( ! mockCapturedDefaultFields ) {
		throw new Error( 'Checkout Edit did not provide default fields.' );
	}

	return result;
};

type FieldName = 'company' | 'address_2' | 'phone';
type FieldState = 'hidden' | 'optional' | 'required';

const fieldCases: Array< {
	field: FieldName;
	label: string;
	addLabel?: string;
} > = [
	{ field: 'company', label: 'Company' },
	{
		field: 'address_2',
		label: 'Apartment, suite, etc.',
		addLabel: '+ Add apartment, suite, etc.',
	},
	{ field: 'phone', label: 'Phone' },
];

const stateCases: Array< {
	state: FieldState;
	hidden: boolean;
	required: boolean;
} > = [
	{ state: 'hidden', hidden: true, required: false },
	{ state: 'optional', hidden: false, required: false },
	{ state: 'required', hidden: false, required: true },
];

const expectHiddenField = ( label: string ) => {
	expect( screen.queryByLabelText( label ) ).not.toBeInTheDocument();
	expect(
		screen.queryByLabelText( `${ label } (optional)` )
	).not.toBeInTheDocument();
};

const expectHiddenAddControl = ( addLabel: string ) => {
	expect(
		screen.queryByRole( 'button', { name: addLabel } )
	).not.toBeInTheDocument();
};

const expectCollapsedOptionalAddressLine = (
	label: string,
	addLabel: string
) => {
	expect( screen.getByRole( 'button', { name: addLabel } ) ).toBeVisible();
	expect( screen.getByLabelText( label ) ).toHaveAttribute(
		'aria-hidden',
		'true'
	);
};

const expectVisibleField = ( label: string, required: boolean ) => {
	const input = screen.getByLabelText(
		required ? label : `${ label } (optional)`
	);
	expect( input ).toBeVisible();
	expect( ( input as HTMLInputElement ).required ).toBe( required );
};

describe( 'Checkout editor consumers', () => {
	beforeEach( () => {
		mockFieldSettings = {};
		mockCapturedDefaultFields = undefined;
		mockRenderingCheckoutEdit = false;
	} );

	it( 'omits the dark controls class when hasDarkControls is false', () => {
		const { container } = renderCheckoutEdit();
		const checkoutLayout = container.querySelector( '.wc-block-checkout' );

		expect( checkoutLayout ).toBeInTheDocument();
		expect( checkoutLayout ).not.toHaveClass( 'has-dark-controls' );
	} );

	it( 'adds the dark controls class when hasDarkControls is true', () => {
		const { container } = renderCheckoutEdit( {}, true );
		const checkoutLayout = container.querySelector( '.wc-block-checkout' );

		expect( checkoutLayout ).toBeInTheDocument();
		expect( checkoutLayout ).toHaveClass( 'has-dark-controls' );
	} );

	it.each(
		fieldCases.flatMap( ( fieldCase ) =>
			stateCases.map( ( stateCase ) => ( {
				...fieldCase,
				...stateCase,
			} ) )
		)
	)(
		'maps $field=$state from the root-site record into the real form',
		( { field, label, addLabel, state, hidden, required } ) => {
			const optionName = `woocommerce_checkout_${ field }_field`;
			const { unmount } = renderCheckoutEdit( {
				[ optionName ]: state,
			} );
			const defaultFields = mockCapturedDefaultFields as FormFields;

			expect( defaultFields[ field ] ).toMatchObject( {
				hidden,
				required,
			} );
			unmount();

			const fields: ( keyof FormFields )[] =
				field === 'address_2'
					? [ 'address_1', 'address_2' ]
					: [ field ];
			render(
				<Form< ShippingAddress >
					id="shipping"
					addressType="shipping"
					fields={ fields }
					isEditing
					onChange={ jest.fn() }
					values={ emptyShippingAddress }
				/>
			);

			if ( hidden ) {
				expectHiddenField( label );
				if ( addLabel ) {
					expectHiddenAddControl( addLabel );
				}
				return;
			}

			if ( field === 'address_2' && ! required && addLabel ) {
				expectCollapsedOptionalAddressLine( label, addLabel );
				return;
			}

			expectVisibleField( label, required );
		}
	);
} );
