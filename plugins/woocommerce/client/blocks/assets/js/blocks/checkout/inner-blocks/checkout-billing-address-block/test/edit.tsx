/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useCheckoutAddress } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import { Edit } from '../edit';

// Stands in for the editor canvas: the inspector renders inline so the panel is
// in the same tree as the step.
jest.mock( '@wordpress/block-editor', () => ( {
	InspectorControls: jest.fn( ( { children } ) => <div>{ children }</div> ),
	PlainText: jest.fn( ( { value }: { value: string } ) => (
		<span>{ value }</span>
	) ),
	useBlockProps: Object.assign(
		jest.fn( () => ( {} ) ),
		{
			save: jest.fn( () => ( {} ) ),
		}
	),
} ) );

jest.mock( '@woocommerce/base-context/hooks', () => ( {
	useCheckoutAddress: jest.fn(),
} ) );

// The inner blocks area needs a block editor store to resolve its layout.
jest.mock( '../../../form-step/additional-fields', () => ( {
	AdditionalFields: jest.fn( () => null ),
	AdditionalFieldsContent: jest.fn( () => null ),
} ) );

// The billing form itself reads the cart store; the address field controls are
// what this suite renders.
jest.mock( '../block', () => jest.fn( () => <div>Billing form</div> ) );

const mockUseCheckoutAddress = useCheckoutAddress as jest.MockedFunction<
	typeof useCheckoutAddress
>;

const billingAttributes = {
	title: 'Billing address',
	description: 'Enter the billing address',
	showStepNumber: true,
	className: '',
};

const renderBillingAddressBlock = ( showBillingFields: boolean ) => {
	mockUseCheckoutAddress.mockReturnValue( {
		showBillingFields,
		forcedBillingAddress: false,
		useBillingAsShipping: false,
	} as ReturnType< typeof useCheckoutAddress > );

	return render(
		<Edit
			attributes={ { ...billingAttributes } }
			setAttributes={ jest.fn() }
		/>
	);
};

describe( 'Billing address block editor', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it.each( [ 'Company', 'Address line 2', 'Phone' ] )(
		'renders the %s address field control in the inspector',
		( label ) => {
			renderBillingAddressBlock( true );

			expect(
				screen.getByRole( 'checkbox', { name: label } )
			).toBeInTheDocument();
			expect( screen.getByText( 'Billing form' ) ).toBeInTheDocument();
		}
	);

	it( 'renders nothing when the billing fields are not shown', () => {
		const { container } = renderBillingAddressBlock( false );

		expect( container ).toBeEmptyDOMElement();
	} );
} );
