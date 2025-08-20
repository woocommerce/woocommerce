/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import type { FieldValidationStatus } from '@woocommerce/types';
import { useCheckoutAddress, useCustomerData } from '@woocommerce/base-context';
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
	useCustomerData: jest.fn(),
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

const mockUseCustomerData = useCustomerData as jest.MockedFunction<
	typeof useCustomerData
>;

const baseMockCheckoutAddress = {
	shippingAddress: previewCart.shipping_address as ShippingAddress,
	editingShippingAddress: false,
	setEditingShippingAddress: jest.fn(),
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

		mockUseCustomerData.mockReturnValue( {
			isInitialized: true,
			setBillingAddress: jest.fn(),
			setShippingAddress: jest.fn(),
			billingAddress: previewCart.billing_address as BillingAddress,
			shippingAddress: previewCart.shipping_address as ShippingAddress,
		} );

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
			'false'
		);
	} );

	it( 'should be in editing mode when there are visible validation errors', () => {
		const mockSetEditing = jest.fn();

		// Override only the properties we need for this test
		mockUseCheckoutAddress.mockReturnValue( {
			...baseMockCheckoutAddress,
			editingShippingAddress: false, // Start not editing
			setEditingShippingAddress: mockSetEditing,
			shippingAddress: {
				...previewCart.shipping_address,
				first_name: 'John',
				last_name: 'Doe',
				company: 'Test Company',
				address_1: '123 Test Street',
				address_2: 'Apt 1',
				city: '', // Empty city to trigger validation error
				state: 'CA',
				postcode: '12345',
				country: 'US',
				phone: '555-123-4567',
				email: 'john@example.com',
			} as ShippingAddress,
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
			shippingAddress: {
				...previewCart.shipping_address,
				first_name: 'John',
				last_name: 'Doe',
				company: 'Test Company',
				address_1: '123 Test Street',
				address_2: 'Apt 1',
				city: '', // Empty city to trigger validation error
				state: 'CA',
				postcode: '12345',
				country: 'US',
				phone: '555-123-4567',
				email: 'john@example.com',
			} as ShippingAddress,
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
			shippingAddress: {
				...previewCart.shipping_address,
				first_name: 'John',
				last_name: 'Doe',
				company: 'Test Company',
				address_1: '123 Test Street',
				address_2: 'Apt 1',
				city: '', // Empty city to trigger validation error
				state: 'CA',
				postcode: '', // Empty postcode to trigger validation error
				country: 'US',
				phone: '555-123-4567',
				email: 'john@example.com',
			} as ShippingAddress,
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
			'false'
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

	it( 'should not enter editing mode when there is only an email validation error', () => {
		const mockSetEditing = jest.fn();

		// Override only the properties we need for this test
		mockUseCheckoutAddress.mockReturnValue( {
			...baseMockCheckoutAddress,
			editingShippingAddress: false,
			setEditingShippingAddress: mockSetEditing,
			shippingAddress: {
				...previewCart.shipping_address,
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
				email: 'john@example.com',
			} as ShippingAddress,
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

	it( 'should not enter editing mode when all shipping address fields are empty', () => {
		const mockSetEditing = jest.fn();

		// Override only the properties we need for this test
		mockUseCheckoutAddress.mockReturnValue( {
			...baseMockCheckoutAddress,
			editingShippingAddress: false,
			setEditingShippingAddress: mockSetEditing,
			shippingAddress: {
				...previewCart.shipping_address,
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
				email: '',
			} as ShippingAddress,
		} );

		mockUseCustomerData.mockReturnValue( {
			isInitialized: false,
			setBillingAddress: jest.fn(),
			setShippingAddress: jest.fn(),
			billingAddress: previewCart.billing_address as BillingAddress,
			shippingAddress: previewCart.shipping_address as ShippingAddress,
		} );

		// Mock the validation store to return errors for all empty required fields
		mockGetValidationError.mockImplementation( ( key: string ) => {
			// Check if it's a shipping field that should have validation errors
			if ( key.startsWith( 'shipping_' ) ) {
				const fieldName = key.replace( 'shipping_', '' );
				const errorMessages: Record< string, string > = {
					first_name: 'First name is required',
					last_name: 'Last name is required',
					address_1: 'Address is required',
					city: 'City is required',
					state: 'State is required',
					postcode: 'Postcode is required',
					country: 'Country is required',
					phone: 'Phone is required',
					email: 'Email is required',
				};

				if ( errorMessages[ fieldName ] ) {
					return {
						message: errorMessages[ fieldName ],
						hidden: false,
					} as FieldValidationStatus;
				}
			}
		} );

		render( <CustomerAddress /> );

		// Should not enter editing mode when all fields are empty, even with validation errors
		expect( mockSetEditing ).not.toHaveBeenCalled();
		expect( screen.getByTestId( 'is-editing' ) ).toHaveTextContent(
			'false'
		);
		expect( screen.getByTestId( 'is-expanded' ) ).toHaveTextContent(
			'false'
		);
	} );
} );
