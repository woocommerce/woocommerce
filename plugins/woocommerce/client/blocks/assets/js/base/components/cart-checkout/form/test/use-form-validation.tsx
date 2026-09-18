/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { createRegistry, RegistryProvider } from '@wordpress/data';
import type { WPDataRegistry } from '@wordpress/data/build-types/registry';
import type {
	AddressFormType,
	BillingAddress,
	KeyedFormFields,
	ShippingAddress,
} from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { useFormValidation } from '../use-form-validation';

jest.mock( '@woocommerce/block-data', () => ( {
	__esModule: true,
	cartStore: 'wc/store/cart',
	checkoutStore: 'wc/store/checkout',
	paymentStore: 'wc/store/payment',
} ) );

const postcodeField = {
	key: 'postcode',
	label: 'Postal code',
	optionalLabel: 'Postal code (optional)',
	autocomplete: 'postal-code',
	required: true,
	hidden: false,
	validation: [],
	index: 90,
} as KeyedFormFields[ number ];

const shippingAddress: ShippingAddress = {
	first_name: 'Jane',
	last_name: 'Doe',
	company: '',
	address_1: '123 Main Street',
	address_2: '',
	city: 'Tirana',
	state: '',
	postcode: '1001',
	country: 'AL',
	phone: '',
};

// The same postcode as shipping, which the UK rejects and Albania accepts.
const billingAddress: BillingAddress = {
	...shippingAddress,
	city: 'London',
	country: 'GB',
	email: 'jane@example.com',
};

describe( 'useFormValidation postcode check', () => {
	let registry: WPDataRegistry;

	beforeEach( () => {
		// Selectors return the same objects on every call, as real stores do for unchanged state.
		const cartData = {
			coupons: [],
			shippingRates: [],
			shippingAddress,
			billingAddress,
			items: [],
			itemsCount: 0,
			itemsWeight: 0,
			needsShipping: true,
			totals: { total_price: '0', total_tax: '0' },
			extensions: {},
		};
		const additionalFields = {};

		registry = createRegistry();
		registry.registerStore( 'wc/store/cart', {
			reducer: () => ( {} ),
			selectors: {
				getCartData: () => cartData,
			},
		} );
		registry.registerStore( 'wc/store/checkout', {
			reducer: () => ( {} ),
			selectors: {
				prefersCollection: () => false,
				getShouldCreateAccount: () => false,
				getOrderNotes: () => '',
				getAdditionalFields: () => additionalFields,
				getCustomerId: () => 0,
			},
		} );
		registry.registerStore( 'wc/store/payment', {
			reducer: () => ( {} ),
			selectors: {
				getActivePaymentMethod: () => '',
			},
		} );
	} );

	const getErrors = ( formType: AddressFormType ) => {
		const { result } = renderHook(
			() => useFormValidation( [ postcodeField ], formType ),
			{
				wrapper: ( { children } ) => (
					<RegistryProvider value={ registry }>
						{ children }
					</RegistryProvider>
				),
			}
		);
		return result.current.errors;
	};

	it( "checks each form's postcode against that form's own country", () => {
		expect( getErrors( 'billing' ) ).toHaveProperty(
			'postcode',
			'Please enter a valid postcode'
		);
		expect( getErrors( 'shipping' ) ).not.toHaveProperty( 'postcode' );
	} );
} );
