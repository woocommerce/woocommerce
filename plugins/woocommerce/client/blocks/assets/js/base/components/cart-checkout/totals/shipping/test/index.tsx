/**
 * External dependencies
 */
import { screen, render, within } from '@testing-library/react';
import { SlotFillProvider } from '@woocommerce/blocks-checkout';
import { ShippingCalculatorContext } from '@woocommerce/base-components/cart-checkout/shipping-calculator/context';
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
wpData.useSelect.mockImplementation(
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
				selected: false,
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
	};
} );

baseContextHooks.useShippingData.mockReturnValue( {
	needsShipping: true,
	selectShippingRate: jest.fn(),
	shippingRates,
} );

baseContextHooks.useStoreCart.mockReturnValue( {
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

describe( 'TotalsShipping', () => {
	it( 'shows FREE if shipping cost is 0', () => {
		baseContextHooks.useStoreCart.mockReturnValue( {
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

		baseContextHooks.useStoreCart.mockReturnValue( {
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

	it( 'should show correct calculator panel label if address is complete', () => {
		render(
			<SlotFillProvider>
				<ShippingCalculatorContext.Provider
					value={ {
						showCalculator: true,
						isShippingCalculatorOpen: false,
						setIsShippingCalculatorOpen: jest.fn(),
						shippingCalculatorID:
							'shipping-calculator-form-wrapper',
					} }
				>
					<TotalsShipping />
				</ShippingCalculatorContext.Provider>
			</SlotFillProvider>
		);

		const panel = screen.getByRole( 'button' );
		const paragraph = within( panel ).getByRole( 'paragraph' );

		expect(
			within( paragraph ).getByText( ( _, element ) => {
				const text = element?.textContent || '';
				return /Delivers to W1T 4JG, London, United Kingdom \(UK\)/.test(
					text
				);
			} )
		).toBeInTheDocument();
	} );

	it( 'should show correct calculator button label if address is incomplete', () => {
		baseContextHooks.useStoreCart.mockReturnValue( {
			...baseContextHooks.useStoreCart(),
			shippingAddress: {
				...shippingAddress,
				city: '',
				country: '',
				postcode: '',
			},
		} );

		render(
			<SlotFillProvider>
				<ShippingCalculatorContext.Provider
					value={ {
						showCalculator: true,
						isShippingCalculatorOpen: false,
						setIsShippingCalculatorOpen: jest.fn(),
						shippingCalculatorID:
							'shipping-calculator-form-wrapper',
					} }
				>
					<TotalsShipping />
				</ShippingCalculatorContext.Provider>
			</SlotFillProvider>
		);
		expect(
			screen.getByText( 'Enter address to check delivery options' )
		).toBeInTheDocument();
	} );

	it( 'does show the calculator panel when default rates are available and has formatted address', () => {
		baseContextHooks.useStoreCart.mockReturnValue( {
			...baseContextHooks.useStoreCart(),
			shippingAddress: {
				...shippingAddress,
				city: '',
				state: 'California',
				country: 'US',
				postcode: '',
			},
		} );

		render(
			<SlotFillProvider>
				<ShippingCalculatorContext.Provider
					value={ {
						showCalculator: true,
						isShippingCalculatorOpen: false,
						setIsShippingCalculatorOpen: jest.fn(),
						shippingCalculatorID:
							'shipping-calculator-form-wrapper',
					} }
				>
					<TotalsShipping />
				</ShippingCalculatorContext.Provider>
			</SlotFillProvider>
		);
		expect(
			screen.queryByText( 'Enter address to check delivery options' )
		).not.toBeInTheDocument();
	} );

	it( 'should not show a calculator button label if no shipping methods exist', () => {
		baseContextHooks.useStoreCart.mockReturnValue( {
			...baseContextHooks.useStoreCart(),
			shippingAddress: {
				...shippingAddress,
				city: '',
				country: '',
				postcode: '',
			},
		} );

		render(
			<SlotFillProvider>
				<ShippingCalculatorContext.Provider
					value={ {
						showCalculator: false,
						isShippingCalculatorOpen: false,
						setIsShippingCalculatorOpen: jest.fn(),
						shippingCalculatorID:
							'shipping-calculator-form-wrapper',
					} }
				>
					<TotalsShipping />
				</ShippingCalculatorContext.Provider>
			</SlotFillProvider>
		);
		expect(
			screen.queryByText( 'Enter address to check delivery options' )
		).not.toBeInTheDocument();
	} );
} );
