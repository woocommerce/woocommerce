/**
 * External dependencies
 */
import { screen, render } from '@testing-library/react';
import { SlotFillProvider } from '@woocommerce/blocks-checkout';
import { previewCart as mockPreviewCart } from '@woocommerce/resource-previews';
import * as wpData from '@wordpress/data';
import * as baseContextHooks from '@woocommerce/base-context/hooks';
const woocommerceSettings = jest.requireMock( '@woocommerce/settings' );

/**
 * Internal dependencies
 */
import { TotalsShipping } from '../index';

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

// Mock the settings module to return the active shipping zones.
jest.mock( '@woocommerce/settings', () => {
	const originalModule = jest.requireActual( '@woocommerce/settings' );

	return {
		...originalModule,
		getSetting: jest.fn().mockImplementation( ( setting, ...rest ) => {
			if ( setting === 'activeShippingZones' ) {
				return [
					{
						id: 7,
						title: 'India',
						description: 'India',
					},
					{
						id: 0,
						title: 'International',
						description: 'Locations outside all other zones',
					},
				];
			}
			return originalModule.getSetting( setting, ...rest );
		} ),
	};
} );

const setGetSettingImplementation = ( implementation ) => {
	woocommerceSettings.getSetting.mockImplementation( implementation );
};

// Mock use select so we can override it when wc/store/checkout is accessed, but return the original select function if any other store is accessed.
wpData.useSelect.mockImplementation( ( selector ) => {
	const mockSelect = ( storeName: string ) => {
		if ( storeName === 'wc/store/checkout' ) {
			return {
				getCustomerData: () => ( {
					firstName: 'John',
					lastName: 'Doe',
					email: 'john.doe@example.com',
				} ),
				isInitialized: true,
				prefersCollection: () => false,
			};
		}
		return jest.requireActual( '@wordpress/data' ).select( storeName );
	};
	return selector( mockSelect );
} );

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
	shippingRates: [
		{
			package_id: 0,
			name: 'Shipping method',
			destination: {
				address_1: '',
				address_2: '',
				city: '',
				state: '',
				postcode: '',
				country: '',
			},
			items: [
				{
					key: 'fb0c0a746719a7596f296344b80cb2b6',
					name: 'Hoodie - Blue, Yes',
					quantity: 1,
				},
				{
					key: '1f0e3dad99908345f7439f8ffabdffc4',
					name: 'Beanie',
					quantity: 1,
				},
			],
			shipping_rates: [
				{
					rate_id: 'flat_rate:1',
					name: 'Flat rate',
					description: '',
					delivery_time: '',
					price: '500',
					taxes: '0',
					instance_id: 1,
					method_id: 'flat_rate',
					meta_data: [
						{
							key: 'Items',
							value: 'Hoodie - Blue, Yes &times; 1, Beanie &times; 1',
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
				{
					rate_id: 'local_pickup:2',
					name: 'Local pickup',
					description: '',
					delivery_time: '',
					price: '0',
					taxes: '0',
					instance_id: 2,
					method_id: 'local_pickup',
					meta_data: [
						{
							key: 'Items',
							value: 'Hoodie - Blue, Yes &times; 1, Beanie &times; 1',
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
				{
					rate_id: 'free_shipping:5',
					name: 'Free shipping',
					description: '',
					delivery_time: '',
					price: '0',
					taxes: '0',
					instance_id: 5,
					method_id: 'free_shipping',
					meta_data: [
						{
							key: 'Items',
							value: 'Hoodie - Blue, Yes &times; 1, Beanie &times; 1',
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
	],
} );
baseContextHooks.useStoreCart.mockReturnValue( {
	cartItems: mockPreviewCart.items,
	cartTotals: [ mockPreviewCart.totals ],
	cartCoupons: mockPreviewCart.coupons,
	cartFees: mockPreviewCart.fees,
	cartNeedsShipping: mockPreviewCart.needs_shipping,
	shippingRates: [],
	shippingAddress,
	billingAddress: mockPreviewCart.billing_address,
	cartHasCalculatedShipping: mockPreviewCart.has_calculated_shipping,
	isLoadingRates: false,
} );

describe( 'TotalsShipping', () => {
	it( 'shows FREE if shipping cost is 0', () => {
		baseContextHooks.useStoreCart.mockReturnValue( {
			cartItems: mockPreviewCart.items,
			cartTotals: [ mockPreviewCart.totals ],
			cartCoupons: mockPreviewCart.coupons,
			cartFees: mockPreviewCart.fees,
			cartNeedsShipping: mockPreviewCart.needs_shipping,
			shippingRates: [
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
							rate_id: 'free_shipping:1',
							name: 'Free shipping',
							description: '',
							delivery_time: '',
							price: '0',
							taxes: '0',
							instance_id: 13,
							method_id: 'free_shipping',
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
			],
			shippingAddress,
			billingAddress: mockPreviewCart.billing_address,
			cartHasCalculatedShipping: mockPreviewCart.has_calculated_shipping,
			isLoadingRates: false,
		} );

		const { rerender } = render(
			<SlotFillProvider>
				<TotalsShipping
					currency={ {
						code: 'USD',
						symbol: '$',
						minorUnit: 2,
						decimalSeparator: '.',
						prefix: '',
						suffix: '',
						thousandSeparator: ', ',
					} }
					values={ {
						total_shipping: '0',
						total_shipping_tax: '0',
					} }
					showCalculator={ true }
					showRateSelector={ false }
					isCheckout={ false }
					className={ '' }
				/>
			</SlotFillProvider>
		);
		expect(
			screen.getByText( 'Free', { exact: true } )
		).toBeInTheDocument();
		expect( screen.queryByText( '0.00' ) ).not.toBeInTheDocument();

		rerender(
			<SlotFillProvider>
				<TotalsShipping
					currency={ {
						code: 'USD',
						symbol: '$',
						minorUnit: 2,
						decimalSeparator: '.',
						prefix: '',
						suffix: '',
						thousandSeparator: ', ',
					} }
					values={ {
						total_shipping: '5678',
						total_shipping_tax: '0',
					} }
					showCalculator={ true }
					showRateSelector={ false }
					isCheckout={ false }
					className={ '' }
				/>
			</SlotFillProvider>
		);

		expect( screen.queryByText( 'Free' ) ).not.toBeInTheDocument();
		expect( screen.getByText( '56.78' ) ).toBeInTheDocument();
	} );

	it( 'should show correct shipping calculator panel text if address is complete', () => {
		render(
			<SlotFillProvider>
				<TotalsShipping
					currency={ {
						code: 'USD',
						symbol: '$',
						minorUnit: 2,
						decimalSeparator: '.',
						prefix: '',
						suffix: '',
						thousandSeparator: ', ',
					} }
					values={ {
						total_shipping: '0',
						total_shipping_tax: '0',
					} }
					showCalculator={ true }
					showRateSelector={ true }
					isCheckout={ false }
					className={ '' }
				/>
			</SlotFillProvider>
		);
		expect(
			screen.getByRole( 'button', {
				name: /Delivers to W1T 4JG, London, United Kingdom \(UK\)/i,
			} )
		).toBeInTheDocument();
	} );

	it( 'should show calculator panel if address is incomplete', () => {
		baseContextHooks.useStoreCart.mockReturnValue( {
			cartItems: mockPreviewCart.items,
			cartTotals: [ mockPreviewCart.totals ],
			cartCoupons: mockPreviewCart.coupons,
			cartFees: mockPreviewCart.fees,
			cartNeedsShipping: mockPreviewCart.needs_shipping,
			shippingRates: mockPreviewCart.shipping_rates,
			shippingAddress: {
				...shippingAddress,
				city: '',
				country: '',
				postcode: '',
			},
			billingAddress: mockPreviewCart.billing_address,
			cartHasCalculatedShipping: mockPreviewCart.has_calculated_shipping,
			isLoadingRates: false,
		} );
		render(
			<SlotFillProvider>
				<TotalsShipping
					currency={ {
						code: 'USD',
						symbol: '$',
						minorUnit: 2,
						decimalSeparator: '.',
						prefix: '',
						suffix: '',
						thousandSeparator: ', ',
					} }
					values={ {
						total_shipping: '0',
						total_shipping_tax: '0',
					} }
					showCalculator={ true }
					showRateSelector={ true }
					isCheckout={ false }
					className={ '' }
				/>
			</SlotFillProvider>
		);
		expect(
			screen.getByText( 'Enter address to check delivery options' )
		).toBeInTheDocument();
	} );

	it( 'does not show the calculator button when only default rates are available and no address has been entered', () => {
		// Mock active shipping zones to have only one zone.
		setGetSettingImplementation( ( setting, ...rest ) => {
			if ( setting === 'activeShippingZones' ) {
				return [
					{
						id: 0,
						title: 'International',
						description: 'Locations outside all other zones',
					},
				];
			}
			const originalModule = jest.requireActual(
				'@woocommerce/settings'
			);
			return originalModule.getSetting( setting, ...rest );
		} );
		baseContextHooks.useStoreCart.mockReturnValue( {
			cartItems: mockPreviewCart.items,
			cartTotals: [ mockPreviewCart.totals ],
			cartCoupons: mockPreviewCart.coupons,
			cartFees: mockPreviewCart.fees,
			cartNeedsShipping: mockPreviewCart.needs_shipping,
			shippingRates: mockPreviewCart.shipping_rates,
			shippingAddress: {
				...shippingAddress,
				city: '',
				country: '',
				postcode: '',
			},
			billingAddress: mockPreviewCart.billing_address,
			cartHasCalculatedShipping: mockPreviewCart.has_calculated_shipping,
			isLoadingRates: false,
		} );
		render(
			<SlotFillProvider>
				<TotalsShipping
					currency={ {
						code: 'USD',
						symbol: '$',
						minorUnit: 2,
						decimalSeparator: '.',
						prefix: '',
						suffix: '',
						thousandSeparator: ', ',
					} }
					values={ {
						total_shipping: '0',
						total_shipping_tax: '0',
					} }
					showCalculator={ true }
					showRateSelector={ true }
					isCheckout={ false }
					className={ '' }
				/>
			</SlotFillProvider>
		);
		expect(
			screen.queryByText( 'Enter address to check delivery options' )
		).not.toBeInTheDocument();
	} );
	it( 'should show the calculator panel with address when default rates are available and has formatted address', () => {
		baseContextHooks.useStoreCart.mockReturnValue( {
			cartItems: mockPreviewCart.items,
			cartTotals: [ mockPreviewCart.totals ],
			cartCoupons: mockPreviewCart.coupons,
			cartFees: mockPreviewCart.fees,
			cartNeedsShipping: mockPreviewCart.needs_shipping,
			shippingRates: mockPreviewCart.shipping_rates,
			shippingAddress: {
				...shippingAddress,
				city: 'San Francisco',
				state: 'CA',
				country: 'US',
				postcode: '94107',
			},
			billingAddress: mockPreviewCart.billing_address,
			cartHasCalculatedShipping: mockPreviewCart.has_calculated_shipping,
			isLoadingRates: false,
		} );
		render(
			<SlotFillProvider>
				<TotalsShipping
					currency={ {
						code: 'USD',
						symbol: '$',
						minorUnit: 2,
						decimalSeparator: '.',
						prefix: '',
						suffix: '',
						thousandSeparator: ', ',
					} }
					values={ {
						total_shipping: '0',
						total_shipping_tax: '0',
					} }
					showCalculator={ true }
					showRateSelector={ true }
					isCheckout={ false }
					className={ '' }
				/>
			</SlotFillProvider>
		);
		expect(
			screen.getByRole( 'button', {
				name: /Delivers to 94107, San Francisco, CA/,
			} )
		).toBeInTheDocument();
	} );
} );
