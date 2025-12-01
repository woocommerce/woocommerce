/**
 * External dependencies
 */
import { screen, render } from '@testing-library/react';
import { SlotFillProvider } from '@woocommerce/blocks-checkout';
import * as wpData from '@wordpress/data';
import { CartShippingRate } from '@woocommerce/types';
import { previewCart as mockPreviewCart } from '@woocommerce/resource-previews';
import * as baseContextHooks from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import { TotalsShipping } from '../index';

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

// Mock use select so we can override it when wc/store/checkout is accessed, but return the original select function if any other store is accessed.
(
	wpData.useSelect as jest.MockedFunction< typeof wpData.useSelect >
 ).mockImplementation(
	jest.fn().mockImplementation( ( passedMapSelect ) => {
		const mockedSelect = jest.fn().mockImplementation( ( storeName ) => {
			if ( storeName === 'wc/store/checkout' ) {
				return {
					prefersCollection() {
						return false;
					},
				};
			}
			return jest.requireActual( '@wordpress/data' ).select( storeName );
		} );
		passedMapSelect( mockedSelect, {
			dispatch: jest.requireActual( '@wordpress/data' ).dispatch,
		} );
	} )
);

const shippingAddress = {
	first_name: 'John',
	last_name: 'Doe',
	company: 'Company',
	address_1: '409 Main Street',
	address_2: 'Apt 1',
	city: 'London',
	postcode: 'W1T 4JG',
	country: 'GB',
	state: '',
	email: 'john.doe@company',
	phone: '+1234567890',
};

const shippingRates = [
	{
		package_id: 0,
		name: 'Initial Shipment',
		destination: {
			address_1: '30 Test Street',
			address_2: 'Apt 1 Shipping',
			city: 'Liverpool',
			state: '',
			postcode: 'L1 0BP',
			country: 'GB',
		},
		items: [
			{
				key: 'acf4b89d3d503d8252c9c4ba75ddbf6d',
				name: 'Test product',
				quantity: 1,
			},
		],
		shipping_rates: [
			{
				rate_id: 'flat_rate:1',
				name: 'Shipping',
				description: '',
				delivery_time: '',
				price: '0',
				taxes: '0',
				instance_id: 13,
				method_id: 'flat_rate',
				meta_data: [
					{
						key: 'Items',
						value: 'Test product &times; 1',
					},
				],
				selected: true,
				currency_code: 'USD',
				currency_symbol: '$',
				currency_minor_unit: 2,
				currency_decimal_separator: '.',
				currency_thousand_separator: ',',
				currency_prefix: '$',
				currency_suffix: '',
			},
		],
	},
] as CartShippingRate[];

jest.mock( '@woocommerce/base-context/hooks', () => {
	return {
		__esModule: true,
		...jest.requireActual( '@woocommerce/base-context/hooks' ),
		useShippingData: jest.fn(),
		useStoreCart: jest.fn(),
		useOrderSummaryLoadingState: jest.fn(),
	};
} );

(
	baseContextHooks.useShippingData as jest.MockedFunction<
		typeof baseContextHooks.useShippingData
	>
 ).mockReturnValue( {
	needsShipping: true,
	selectShippingRate: jest.fn(),
	shippingRates,
} );

(
	baseContextHooks.useStoreCart as jest.MockedFunction<
		typeof baseContextHooks.useStoreCart
	>
 ).mockReturnValue( {
	cartItems: mockPreviewCart.items,
	cartTotals: mockPreviewCart.totals,
	cartCoupons: mockPreviewCart.coupons,
	cartFees: mockPreviewCart.fees,
	cartNeedsShipping: mockPreviewCart.needs_shipping,
	shippingRates,
	shippingAddress,
	billingAddress: mockPreviewCart.billing_address,
	cartHasCalculatedShipping: mockPreviewCart.has_calculated_shipping,
	isLoadingRates: false,
} );

(
	baseContextHooks.useOrderSummaryLoadingState as jest.MockedFunction<
		typeof baseContextHooks.useOrderSummaryLoadingState
	>
 ).mockReturnValue( {
	isLoading: false,
} );

describe( 'TotalsShipping', () => {
	it( 'shows skeleton when loading', () => {
		// Set loading state to true
		(
			baseContextHooks.useOrderSummaryLoadingState as jest.MockedFunction<
				typeof baseContextHooks.useOrderSummaryLoadingState
			>
		 ).mockReturnValue( {
			isLoading: true,
		} );

		render(
			<SlotFillProvider>
				<TotalsShipping />
			</SlotFillProvider>
		);
		expect( screen.getByText( 'Shipping' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Loading price…' ) ).toBeInTheDocument();
	} );

	it( 'shows FREE if shipping cost is 0', () => {
		// Set loading state to false
		(
			baseContextHooks.useOrderSummaryLoadingState as jest.MockedFunction<
				typeof baseContextHooks.useOrderSummaryLoadingState
			>
		 ).mockReturnValue( {
			isLoading: false,
		} );
		(
			baseContextHooks.useStoreCart as jest.MockedFunction<
				typeof baseContextHooks.useStoreCart
			>
		 ).mockReturnValue( {
			...baseContextHooks.useStoreCart(),
			shippingRates: [
				...shippingRates,
				{ ...shippingRates[ 0 ], price: '0' },
			],
			cartTotals: {
				...mockPreviewCart.totals,
				total_shipping: '0',
				total_shipping_tax: '0',
			},
		} );

		const { rerender } = render(
			<SlotFillProvider>
				<TotalsShipping />
			</SlotFillProvider>
		);

		expect(
			screen.getByText( 'Free', { exact: true } )
		).toBeInTheDocument();
		expect( screen.queryByText( '0.00' ) ).not.toBeInTheDocument();

		(
			baseContextHooks.useStoreCart as jest.MockedFunction<
				typeof baseContextHooks.useStoreCart
			>
		 ).mockReturnValue( {
			...baseContextHooks.useStoreCart(),
			shippingRates: [
				...shippingRates,
				{ ...shippingRates[ 0 ], price: '5678' },
			],
			cartTotals: {
				...mockPreviewCart.totals,
				total_shipping: '5678',
				total_shipping_tax: '0',
				currency_code: 'USD',
				currency_symbol: '$',
				currency_minor_unit: 2,
				currency_decimal_separator: '.',
				currency_prefix: '',
				currency_suffix: '',
				currency_thousand_separator: ', ',
			},
		} );

		rerender(
			<SlotFillProvider>
				<TotalsShipping />
			</SlotFillProvider>
		);

		expect( screen.queryByText( 'Free' ) ).not.toBeInTheDocument();
		expect( screen.getByText( '56.78' ) ).toBeInTheDocument();
	} );
} );
