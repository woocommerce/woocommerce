/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { allSettings } from '@woocommerce/settings';
import { CurrencyCode } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import TotalsFooterItem from '../index';

jest.mock( '@woocommerce/base-context', () => ( {
	...jest.requireActual( '@woocommerce/base-context' ),
	useStoreCart: () => ( {
		cartIsLoading: false,
	} ),
} ) );

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
