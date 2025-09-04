/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import type { Currency } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import TotalsItem from '../index';

const mockCurrency: Currency = {
	code: 'USD',
	symbol: '$',
	prefix: '$',
	suffix: '',
	decimalSeparator: '.',
	thousandSeparator: ',',
	minorUnit: 2,
};

describe( 'TotalsItem', () => {
	it( 'renders label and value correctly', () => {
		render(
			<TotalsItem
				label="Subtotal"
				value={ 2599 }
				currency={ mockCurrency }
			/>
		);

		expect( screen.getByText( 'Subtotal' ) ).toBeInTheDocument();
		expect( screen.getByText( '$25.99' ) ).toBeInTheDocument();
	} );

	it( 'renders value of 0 correctly', () => {
		render(
			<TotalsItem
				label="Discount"
				value={ 0 }
				currency={ mockCurrency }
			/>
		);

		expect( screen.getByText( 'Discount' ) ).toBeInTheDocument();
		expect( screen.getByText( '$0.00' ) ).toBeInTheDocument();
	} );

	it( 'renders ReactNode value correctly', () => {
		const customValue = (
			<span data-testid="custom-value">Custom Value</span>
		);

		render( <TotalsItem label="Custom" value={ customValue } /> );

		expect( screen.getByText( 'Custom' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'custom-value' ) ).toBeInTheDocument();
	} );

	it( 'renders description when provided', () => {
		render(
			<TotalsItem
				label="Tax"
				value={ 599 }
				currency={ mockCurrency }
				description="Including VAT"
			/>
		);

		expect( screen.getByText( 'Tax' ) ).toBeInTheDocument();
		expect( screen.getByText( '$5.99' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Including VAT' ) ).toBeInTheDocument();
	} );

	it( 'shows skeleton when showSkeleton is true', () => {
		render(
			<TotalsItem
				label="Loading"
				value={ 100 }
				currency={ mockCurrency }
				showSkeleton={ true }
			/>
		);

		expect( screen.getByLabelText( 'Loading price…' ) ).toBeInTheDocument();
		expect( screen.queryByText( '$1.00' ) ).not.toBeInTheDocument();
	} );

	it( 'does not show skeleton when showSkeleton is false', () => {
		render(
			<TotalsItem
				label="Loaded"
				value={ 155 }
				currency={ mockCurrency }
				showSkeleton={ false }
			/>
		);

		expect( screen.getByText( 'Loaded' ) ).toBeInTheDocument();
		expect( screen.getByText( '$1.55' ) ).toBeInTheDocument();
		expect(
			screen.queryByLabelText( 'Loading price…' )
		).not.toBeInTheDocument();
	} );

	it( 'handles currency with different decimal places', () => {
		const jpyCurrency: Currency = {
			...mockCurrency,
			code: 'JPY',
			symbol: '¥',
			minorUnit: 0,
			prefix: '¥',
			suffix: '',
		};

		render(
			<TotalsItem label="Total" value={ 1000 } currency={ jpyCurrency } />
		);

		expect( screen.getByText( 'Total' ) ).toBeInTheDocument();
		expect( screen.getByText( '¥1,000' ) ).toBeInTheDocument();
	} );

	it( 'renders without currency when not provided', () => {
		render( <TotalsItem label="Amount" value={ 42 } /> );

		expect( screen.getByText( 'Amount' ) ).toBeInTheDocument();
		// When no currency is provided, the value should still render
		expect( screen.getByText( '$0.42' ) ).toBeInTheDocument();
	} );
} );
