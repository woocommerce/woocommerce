/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { createRegistry, RegistryProvider } from '@wordpress/data';
import type { FormType } from '@woocommerce/settings';
import Ajv from 'ajv';
import { WPDataRegistry } from '@wordpress/data/build-types/registry';

/**
 * Internal dependencies
 */
import { useSchemaParser } from '../use-schema-parser';
import { CheckoutState } from '../../../data/checkout/default-state';
import { PaymentState } from '../../../data/payment/default-state';
import { CartState } from '../../../data/cart/default-state';
import checkoutSchema from './checkout-document-schema.json';

// Mock the stores
jest.mock( '@woocommerce/block-data', () => ( {
	__esModule: true,
	cartStore: 'wc/store/cart',
	checkoutStore: 'wc/store/checkout',
	paymentStore: 'wc/store/payment',
} ) );

// Mock window.schemaParser
const mockSchemaParser = {
	validate: jest.fn(),
	addSchema: jest.fn(),
};

type DeepPartial< T > = T extends object
	? {
			[ P in keyof T ]?: DeepPartial< T[ P ] >;
	  }
	: T;

describe( 'useSchemaParser', () => {
	let registry: WPDataRegistry;
	let mockCartData: DeepPartial< CartState[ 'cartData' ] >;
	let mockCheckoutData: DeepPartial< CheckoutState >;
	let mockPaymentData: DeepPartial< PaymentState >;

	const wrapper = ( { children }: { children: React.ReactNode } ) => (
		<RegistryProvider value={ registry }>{ children }</RegistryProvider>
	);

	const setupMocks = () => {
		// Mock cart data
		mockCartData = {
			coupons: [ { code: 'SAVE10' }, { code: 'FREESHIP' } ],
			shippingRates: [
				{
					shipping_rates: [
						{ rate_id: 'flat_rate:1', selected: true },
						{ rate_id: 'flat_rate:2', selected: false },
					],
				},
				{
					shipping_rates: [
						{ rate_id: 'free_shipping:1', selected: true },
					],
				},
			],
			shippingAddress: {
				first_name: 'John',
				last_name: 'Doe',
				address_1: '123 Main St',
				city: 'New York',
				state: 'NY',
				postcode: '10001',
				country: 'US',
				phone: '123-456-7890',
			},
			billingAddress: {
				first_name: 'Jane',
				last_name: 'Smith',
				address_1: '456 Oak Ave',
				city: 'Los Angeles',
				state: 'CA',
				postcode: '90210',
				country: 'US',
				phone: '123-456-7890',
				email: 'jane@example.com',
			},
			items: [
				{ id: 1, quantity: 2, type: 'simple' },
				{ id: 2, quantity: 1, type: 'variable' },
			],
			itemsCount: 3,
			itemsWeight: 2.5,
			needsShipping: true,
			totals: {
				total_price: '9999',
				total_tax: '899',
			},
			extensions: {
				custom_extension: { data: 'test' },
			},
		};

		// Mock checkout data
		mockCheckoutData = {
			prefersCollection: false,
			shouldCreateAccount: true,
			orderNotes: 'Please deliver after 6 PM',
			additionalFields: {
				'namespace/contact_field': 'value1',
				'namespace/order_field': 'value2',
			},
			customerId: 123,
		};

		// Mock payment data
		mockPaymentData = {
			activePaymentMethod: 'stripe',
		};

		// Register mock stores
		registry.registerStore( 'wc/store/cart', {
			reducer: () => ( {} ),
			selectors: {
				getCartData: jest.fn().mockReturnValue( mockCartData ),
			},
		} );

		registry.registerStore( 'wc/store/checkout', {
			reducer: () => ( {} ),
			selectors: {
				prefersCollection: jest
					.fn()
					.mockReturnValue( mockCheckoutData.prefersCollection ),
				getShouldCreateAccount: jest
					.fn()
					.mockReturnValue( mockCheckoutData.shouldCreateAccount ),
				getOrderNotes: jest
					.fn()
					.mockReturnValue( mockCheckoutData.orderNotes ),
				getAdditionalFields: jest
					.fn()
					.mockReturnValue( mockCheckoutData.additionalFields ),
				getCustomerId: jest
					.fn()
					.mockReturnValue( mockCheckoutData.customerId ),
			},
		} );

		registry.registerStore( 'wc/store/payment', {
			reducer: () => ( {} ),
			selectors: {
				getActivePaymentMethod: jest
					.fn()
					.mockReturnValue( mockPaymentData.activePaymentMethod ),
			},
		} );
	};
	beforeEach( () => {
		registry = createRegistry();
		setupMocks();
		// Reset window.schemaParser
		Object.defineProperty( window, 'schemaParser', {
			value: undefined,
			writable: true,
		} );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'With validating against schema', () => {
		it( 'should validate cart data correctly', async () => {
			const { result } = renderHook(
				( { formType } ) => useSchemaParser( formType ),
				{
					initialProps: { formType: 'billing' as FormType },
					wrapper,
				}
			);

			const ajv = new Ajv( {
				validateSchema: true,
				strictSchema: true,
				strict: true,
			} );
			const validate = ajv.compile( checkoutSchema );
			const valid = validate( result.current.data );

			expect( validate.errors ).toBe( null );
			expect( valid ).toBe( true );
		} );
	} );

	describe( 'when window.schemaParser is not available', () => {
		it( 'should return null parser and document object data', () => {
			const { result } = renderHook(
				( { formType } ) => useSchemaParser( formType ),
				{
					initialProps: { formType: 'billing' as FormType },
					wrapper,
				}
			);

			expect( result.current.parser ).toBeNull();
			expect( result.current.data ).toBeDefined();
			expect( result.current.data ).toHaveProperty( 'cart' );
			expect( result.current.data ).toHaveProperty( 'checkout' );
			expect( result.current.data ).toHaveProperty( 'customer' );
		} );
	} );

	describe( 'when window.schemaParser is available', () => {
		beforeEach( () => {
			Object.defineProperty( window, 'schemaParser', {
				value: mockSchemaParser,
				writable: true,
			} );
		} );

		it( 'should return schema parser and document object data', () => {
			const { result } = renderHook(
				( { formType } ) => useSchemaParser( formType ),
				{
					initialProps: { formType: 'billing' as FormType },
					wrapper,
				}
			);

			expect( result.current.parser ).toBe( mockSchemaParser );
			expect( result.current.data ).toBeDefined();
		} );
	} );

	describe( 'document object data structure', () => {
		it( 'should transform cart data correctly', () => {
			const { result } = renderHook(
				( { formType } ) => useSchemaParser( formType ),
				{
					initialProps: { formType: 'billing' as FormType },
					wrapper,
				}
			);

			const { cart } = result.current.data!;
			expect( cart ).toHaveProperty( 'coupons' );
			expect( cart.coupons ).toEqual( [ 'SAVE10', 'FREESHIP' ] );

			expect( cart ).toHaveProperty( 'shipping_rates' );
			expect( cart.shipping_rates ).toEqual( [
				'flat_rate:1',
				'free_shipping:1',
			] );

			expect( cart ).toHaveProperty( 'items' );
			expect( cart.items ).toEqual( [ 1, 1, 2 ] );

			expect( cart ).toHaveProperty( 'items_type' );
			expect( cart.items_type ).toEqual( [ 'simple', 'variable' ] );

			expect( cart ).toHaveProperty( 'items_count' );
			expect( cart.items_count ).toBe( 3 );

			expect( cart ).toHaveProperty( 'items_weight' );
			expect( cart.items_weight ).toBe( 2.5 );

			expect( cart ).toHaveProperty( 'needs_shipping' );
			expect( cart.needs_shipping ).toBe( true );

			expect( cart ).toHaveProperty( 'prefers_collection' );
			expect( cart.prefers_collection ).toBe( false );

			expect( cart ).toHaveProperty( 'totals' );
			expect( cart.totals ).toEqual( {
				total_price: 9999,
				total_tax: 899,
			} );

			expect( cart ).toHaveProperty( 'extensions' );
			expect( cart.extensions ).toEqual( {
				custom_extension: { data: 'test' },
			} );
		} );

		it( 'should transform checkout data correctly', () => {
			const { result } = renderHook(
				( { formType } ) => useSchemaParser( formType ),
				{
					initialProps: { formType: 'billing' as FormType },
					wrapper,
				}
			);

			const { checkout } = result.current.data!;

			expect( checkout ).toHaveProperty( 'create_account' );
			expect( checkout.create_account ).toBe( true );

			expect( checkout ).toHaveProperty( 'customer_note' );
			expect( checkout.customer_note ).toBe(
				'Please deliver after 6 PM'
			);

			expect( checkout ).toHaveProperty( 'payment_method' );
			expect( checkout.payment_method ).toBe( 'stripe' );

			expect( checkout ).toHaveProperty( 'additional_fields' );
			expect( checkout.additional_fields ).toEqual( {
				'namespace/order_field': 'value2',
			} );
		} );

		it( 'should transform customer data correctly', () => {
			const { result } = renderHook(
				( { formType } ) => useSchemaParser( formType ),
				{
					initialProps: { formType: 'billing' as FormType },
					wrapper,
				}
			);

			const { customer } = result.current.data!;

			expect( customer ).toHaveProperty( 'id' );
			expect( customer.id ).toBe( 123 );

			expect( customer ).toHaveProperty( 'billing_address' );
			expect( customer.billing_address ).toEqual(
				mockCartData.billingAddress
			);

			expect( customer ).toHaveProperty( 'shipping_address' );
			expect( customer.shipping_address ).toEqual(
				mockCartData.shippingAddress
			);

			expect( customer ).toHaveProperty( 'address' );
			expect( customer.address ).toEqual( mockCartData.billingAddress );

			expect( customer ).toHaveProperty( 'additional_fields' );
			// Additional fields should be filtered to only include contact form keys
			expect( customer.additional_fields ).toEqual( {
				'namespace/contact_field': 'value1',
			} );
		} );
	} );

	describe( 'form type specific behavior', () => {
		it( 'should set address to billing address for billing form type', () => {
			const { result } = renderHook(
				( { formType } ) => useSchemaParser( formType ),
				{
					initialProps: { formType: 'billing' as FormType },
					wrapper,
				}
			);

			const { customer } = result.current.data!;
			expect( customer.address ).toEqual( mockCartData.billingAddress );
		} );

		it( 'should set address to shipping address for shipping form type', () => {
			const { result } = renderHook(
				( { formType } ) => useSchemaParser( formType ),
				{
					initialProps: { formType: 'shipping' as FormType },
					wrapper,
				}
			);

			const { customer } = result.current.data!;
			expect( customer.address ).toEqual( mockCartData.shippingAddress );
		} );

		it( 'should not set address for contact form type', () => {
			const { result } = renderHook(
				( { formType } ) => useSchemaParser( formType ),
				{
					initialProps: { formType: 'contact' as FormType },
					wrapper,
				}
			);

			const { customer } = result.current.data!;
			expect( customer.address ).toBeUndefined();
		} );

		it( 'should not set address for order form type', () => {
			const { result } = renderHook(
				( { formType } ) => useSchemaParser( formType ),
				{
					initialProps: { formType: 'order' as FormType },
					wrapper,
				}
			);

			const { customer } = result.current.data!;
			expect( customer.address ).toBeUndefined();
		} );

		it( 'should not set address for global form type', () => {
			const { result } = renderHook(
				( { formType } ) => useSchemaParser( formType ),
				{
					initialProps: { formType: 'global' as FormType },
					wrapper,
				}
			);

			const { customer } = result.current.data!;
			expect( customer.address ).toBeUndefined();
		} );
	} );

	describe( 'additional fields filtering', () => {
		it( 'should filter additional fields for contact form keys', () => {
			const { result } = renderHook(
				( { formType } ) => useSchemaParser( formType ),
				{
					initialProps: { formType: 'contact' as FormType },
					wrapper,
				}
			);

			const { customer } = result.current.data!;
			expect( customer.additional_fields ).toEqual( {
				'namespace/contact_field': 'value1',
			} );
		} );

		it( 'should filter additional fields for order form keys', () => {
			const { result } = renderHook(
				( { formType } ) => useSchemaParser( formType ),
				{
					initialProps: { formType: 'order' as FormType },
					wrapper,
				}
			);

			const { checkout } = result.current.data!;
			expect( checkout.additional_fields ).toEqual( {
				'namespace/order_field': 'value2',
			} );
		} );
	} );
} );
