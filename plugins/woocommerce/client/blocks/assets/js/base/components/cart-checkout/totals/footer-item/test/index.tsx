/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { allSettings } from '@woocommerce/settings';
import { CurrencyCode } from '@woocommerce/types';
import * as baseContextHooks from '@woocommerce/base-context/hooks';
import * as wpData from '@wordpress/data';
import { previewCart as mockPreviewCart } from '@woocommerce/resource-previews';

/**
 * Internal dependencies
 */
import TotalsFooterItem from '../index';

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
		useOrderSummaryLoadingState: jest.fn( () => {
			return {
				isLoading: false,
			};
		} ),
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

describe( 'TotalsFooterItem', () => {
	beforeEach( () => {
		allSettings.taxesEnabled = true;
		allSettings.displayCartPricesIncludingTax = true;
	} );

	const currency = {
		code: 'GBP' as CurrencyCode,
		decimalSeparator: '.',
		minorUnit: 2,
		prefix: '£',
		suffix: '',
		symbol: '£',
		thousandSeparator: ',',
	};

	const values = {
		currency_code: 'GBP' as CurrencyCode,
		currency_decimal_separator: '.',
		currency_minor_unit: 2,
		currency_prefix: '£',
		currency_suffix: '',
		currency_symbol: '£',
		currency_thousand_separator: ',',
		tax_lines: [],
		total_discount: '0',
		total_discount_tax: '0',
		total_fees: '0',
		total_fees_tax: '0',
		total_items: '7100',
		total_items_tax: '0',
		total_price: '8500',
		total_shipping: '0',
		total_shipping_tax: '0',
		total_tax: '0',
	};

	it( 'Does not show the "including %s of tax" line if tax is 0', async () => {
		render( <TotalsFooterItem currency={ currency } values={ values } /> );

		// Check that the total price is displayed
		expect( screen.getByText( /£85\.00/ ) ).toBeInTheDocument();

		// Check that no tax information is displayed
		expect(
			screen.queryByText( /including.*tax/i )
		).not.toBeInTheDocument();
	} );

	it( 'Does not show the "including %s of tax" line if tax is disabled', async () => {
		allSettings.taxesEnabled = false;
		/* This shouldn't ever happen if taxes are disabled, but this is to test whether the taxesEnabled setting works */
		const valuesWithTax = {
			...values,
			total_tax: '100',
			total_items_tax: '100',
		};
		render(
			<TotalsFooterItem currency={ currency } values={ valuesWithTax } />
		);

		// Check that the total price is displayed
		expect( screen.getByText( /£85\.00/ ) ).toBeInTheDocument();

		// Check that no tax information is displayed when taxes are disabled
		expect(
			screen.queryByText( /including.*tax/i )
		).not.toBeInTheDocument();
	} );

	it( 'Shows the "including %s of tax" line if tax is greater than 0', async () => {
		const valuesWithTax = {
			...values,
			total_tax: '100',
			total_items_tax: '100',
		};
		render(
			<TotalsFooterItem currency={ currency } values={ valuesWithTax } />
		);

		// Check that the total price is displayed
		expect( screen.getByText( /£85\.00/ ) ).toBeInTheDocument();

		// Check that tax information is displayed
		const taxInfo = screen.getByText( /including.*tax/i );
		expect( taxInfo ).toBeInTheDocument();
		expect( taxInfo ).toHaveClass(
			'wc-block-components-totals-footer-item-tax'
		);
	} );

	it( 'Shows the "including %s TAX LABEL" line with single tax label', async () => {
		const valuesWithTax = {
			...values,
			total_tax: '100',
			total_items_tax: '100',
			tax_lines: [ { name: '10% VAT', price: '100', rate: '10.000' } ],
		};
		render(
			<TotalsFooterItem currency={ currency } values={ valuesWithTax } />
		);

		// Check that the total price is displayed
		expect( screen.getByText( /£85\.00/ ) ).toBeInTheDocument();

		// Check that tax information with label is displayed
		const taxInfo = screen.getByText( /including.*10% VAT/i );
		expect( taxInfo ).toBeInTheDocument();
		expect( taxInfo ).toHaveClass(
			'wc-block-components-totals-footer-item-tax'
		);
	} );

	it( 'Shows the "including %s TAX LABELS" line with multiple tax labels', async () => {
		const valuesWithTax = {
			...values,
			total_tax: '100',
			total_items_tax: '100',
			tax_lines: [
				{ name: '10% VAT', price: '50', rate: '10.000' },
				{ name: '5% VAT', price: '50', rate: '5.000' },
			],
		};
		render(
			<TotalsFooterItem currency={ currency } values={ valuesWithTax } />
		);

		// Check that the total price is displayed
		expect( screen.getByText( /£85\.00/ ) ).toBeInTheDocument();

		// Check that tax information with multiple labels is displayed
		const taxInfo = screen.getByText( /including.*10% VAT.*5% VAT/i );
		expect( taxInfo ).toBeInTheDocument();
		expect( taxInfo ).toHaveClass(
			'wc-block-components-totals-footer-item-tax'
		);
	} );
} );
