/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import type { FieldValidationStatus } from '@woocommerce/types';
import { useCheckoutAddress } from '@woocommerce/base-context';
import { previewCart } from '@woocommerce/resource-previews';
import type {
	FormFields,
	ShippingAddress,
	BillingAddress,
} from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import CustomerAddress from '../customer-address';

// Mock all the data dependencies
jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );

jest.mock( '@woocommerce/block-data', () => ( {
	validationStore: 'wc/store/validation',
} ) );

jest.mock( '@woocommerce/base-context', () => ( {
	useCheckoutAddress: jest.fn(),
	useStoreEvents: jest.fn( () => ( {
		dispatchCheckoutEvent: jest.fn(),
	} ) ),
} ) );

jest.mock( '@woocommerce/base-components/cart-checkout', () => ( {
	Form: jest.fn( () => <div data-testid="shipping-form" /> ),
} ) );

jest.mock( '../../../address-wrapper', () =>
	jest.fn( ( { isEditing, addressCard, addressForm } ) => (
		<div data-testid="address-wrapper">
			<div data-testid="is-editing">{ isEditing.toString() }</div>
			{ addressCard }
			{ addressForm }
		</div>
	) )
);

jest.mock( '../../../address-card', () =>
	jest.fn( ( { isExpanded } ) => (
		<div data-testid="address-card">
			<div data-testid="is-expanded">{ isExpanded.toString() }</div>
		</div>
	) )
);

const mockUseCheckoutAddress = useCheckoutAddress as jest.MockedFunction<
	typeof useCheckoutAddress
>;

// Minimal mock - only the properties our test actually uses
const baseMockCheckoutAddress = {
	shippingAddress: previewCart.shipping_address as ShippingAddress,
	editingShippingAddress: false,
	setEditingShippingAddress: jest.fn(),
	// Required TypeScript properties (minimal implementation)
	billingAddress: previewCart.billing_address as BillingAddress,
	setBillingAddress: jest.fn(),
	setShippingAddress: jest.fn(),
	setEmail: jest.fn(),
	useBillingAsShipping: false,
	useShippingAsBilling: false,
	editingBillingAddress: false,
	setEditingBillingAddress: jest.fn(),
	customerData: {
		billingAddress: previewCart.billing_address as BillingAddress,
		shippingAddress: previewCart.shipping_address as ShippingAddress,
	},
	setCustomerData: jest.fn(),
	// Additional required properties from CheckoutAddress interface
	setUseShippingAsBilling: jest.fn(),
	defaultFields: {} as FormFields,
	showShippingFields: true,
	showBillingFields: false,
	forcedBillingAddress: false,
	needsShipping: true,
	showShippingMethods: true,
};

describe( 'CustomerAddress (Shipping)', () => {
	let mockGetValidationError: jest.Mock;

	beforeEach( () => {
		jest.clearAllMocks();
		// Set default mock with base implementation
		mockUseCheckoutAddress.mockReturnValue( baseMockCheckoutAddress );

		// Create fresh mock for each test
		mockGetValidationError = jest.fn();

		// Set up useSelect mock with the validation store pattern
		( useSelect as jest.Mock ).mockImplementation( ( callback ) => {
			return callback( () => ( {
				getValidationError: mockGetValidationError,
			} ) );
		} );
	} );

	it( 'should not be in editing mode when there are no validation errors', () => {
		// Mock the validation store to return no errors for any field
		mockGetValidationError.mockImplementation( () => undefined );

		render( <CustomerAddress /> );

		expect( screen.getByTestId( 'is-editing' ) ).toHaveTextContent(
			'false'
		);
		expect( screen.getByTestId( 'is-expanded' ) ).toHaveTextContent(
			'true'
		);
	} );

	it( 'should be in editing mode when there are visible validation errors', () => {
		const mockSetEditing = jest.fn();

		// Override only the properties we need for this test
		mockUseCheckoutAddress.mockReturnValue( {
			...baseMockCheckoutAddress,
			editingShippingAddress: false, // Start not editing
			setEditingShippingAddress: mockSetEditing,
		} );

		// Mock the validation store to return error for shipping_city
		mockGetValidationError.mockImplementation( ( key: string ) => {
			if ( key === 'shipping_city' ) {
				return {
					message: 'Please enter a valid city',
					hidden: false,
				} as FieldValidationStatus;
			}
			return undefined;
		} );

		render( <CustomerAddress /> );

		// The useEffect should trigger setEditing(true) due to hasValidationErrors being true
		expect( mockSetEditing ).toHaveBeenCalledWith( true );
	} );

	it( 'should be in editing mode when there are hidden validation errors', () => {
		const mockSetEditing = jest.fn();

		// Override only the properties we need for this test
		mockUseCheckoutAddress.mockReturnValue( {
			...baseMockCheckoutAddress,
			editingShippingAddress: false,
			setEditingShippingAddress: mockSetEditing,
		} );

		// Mock the validation store to return hidden error for shipping_city
		mockGetValidationError.mockImplementation( ( key: string ) => {
			if ( key === 'shipping_city' ) {
				return {
					message: 'Please enter a valid city',
					hidden: true,
				} as FieldValidationStatus;
			}
			return undefined;
		} );

		render( <CustomerAddress /> );

		expect( mockSetEditing ).toHaveBeenCalledWith( true );
	} );

	it( 'should handle mixed hidden and visible validation errors', () => {
		const mockSetEditing = jest.fn();

		// Override only the properties we need for this test
		mockUseCheckoutAddress.mockReturnValue( {
			...baseMockCheckoutAddress,
			editingShippingAddress: false,
			setEditingShippingAddress: mockSetEditing,
		} );

		// Mock the validation store to return mixed errors for shipping fields
		mockGetValidationError.mockImplementation( ( key: string ) => {
			if ( key === 'shipping_city' ) {
				return {
					message: 'Please enter a valid city',
					hidden: true,
				} as FieldValidationStatus;
			}
			if ( key === 'shipping_postcode' ) {
				return {
					message: 'Please enter a valid postcode',
					hidden: false,
				} as FieldValidationStatus;
			}
			return undefined;
		} );

		render( <CustomerAddress /> );

		expect( mockSetEditing ).toHaveBeenCalledWith( true );
	} );

	it( 'should handle empty validation errors object', () => {
		// Mock the validation store to return no errors for any field
		mockGetValidationError.mockImplementation( () => undefined );

		render( <CustomerAddress /> );

		expect( screen.getByTestId( 'is-editing' ) ).toHaveTextContent(
			'false'
		);
		expect( screen.getByTestId( 'is-expanded' ) ).toHaveTextContent(
			'true'
		);
	} );

	it( 'should not change editing state when already in editing mode', () => {
		const mockSetEditing = jest.fn();

		// Override only the properties we need for this test
		mockUseCheckoutAddress.mockReturnValue( {
			...baseMockCheckoutAddress,
			editingShippingAddress: true, // Already editing
			setEditingShippingAddress: mockSetEditing,
		} );

		// Mock the validation store to return error for shipping_city
		mockGetValidationError.mockImplementation( ( key: string ) => {
			if ( key === 'shipping_city' ) {
				return {
					message: 'Please enter a valid city',
					hidden: false,
				} as FieldValidationStatus;
			}
			return undefined;
		} );

		render( <CustomerAddress /> );

		// Should not call setEditing since already in editing mode
		expect( mockSetEditing ).not.toHaveBeenCalled();
		expect( screen.getByTestId( 'is-editing' ) ).toHaveTextContent(
			'true'
		);
	} );

	it( 'should always show expanded address card regardless of validation errors', () => {
		// Mock the validation store to return no errors for any field initially
		mockGetValidationError.mockImplementation( () => undefined );

		const { rerender } = render( <CustomerAddress /> );

		// Should be expanded even with no errors (unlike billing address)
		expect( screen.getByTestId( 'is-expanded' ) ).toHaveTextContent(
			'true'
		);

		// Mock validation errors for rerender
		mockGetValidationError.mockImplementation( ( key: string ) => {
			if ( key === 'shipping_city' ) {
				return {
					message: 'Please enter a valid city',
					hidden: false,
				} as FieldValidationStatus;
			}
			return undefined;
		} );

		rerender( <CustomerAddress /> );

		// Should still be expanded with errors
		expect( screen.getByTestId( 'is-expanded' ) ).toHaveTextContent(
			'true'
		);
	} );

	it( 'should not enter editing mode when there is only an email validation error', () => {
		const mockSetEditing = jest.fn();

		// Override only the properties we need for this test
		mockUseCheckoutAddress.mockReturnValue( {
			...baseMockCheckoutAddress,
			editingShippingAddress: false,
			setEditingShippingAddress: mockSetEditing,
		} );

		// Mock the validation store to return email error for contact_email
		// Shipping address doesn't contain email field, so this shouldn't trigger editing
		mockGetValidationError.mockImplementation( ( key: string ) => {
			if ( key === 'contact_email' ) {
				return {
					message: 'Please enter a valid email address',
					hidden: false,
				} as FieldValidationStatus;
			}
			// No errors for shipping fields (shipping_first_name, shipping_last_name, etc.)
			return undefined;
		} );

		render( <CustomerAddress /> );

		// Should not enter editing mode since email is not part of shipping address fields
		expect( mockSetEditing ).not.toHaveBeenCalled();
		expect( screen.getByTestId( 'is-editing' ) ).toHaveTextContent(
			'false'
		);
	} );
} );
