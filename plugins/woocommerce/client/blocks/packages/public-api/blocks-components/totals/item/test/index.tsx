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

// The currency symbol sits in its own element, so the whole value is read at once.
const getValue = ( container: HTMLElement ) =>
	container.querySelector( '.wc-block-components-totals-item__value' )
		?.textContent;

describe( 'TotalsItem', () => {
	it( 'renders label and value correctly', () => {
		const { container } = render(
			<TotalsItem
				label="Subtotal"
				value={ 2599 }
				currency={ mockCurrency }
			/>
		);

		expect( screen.getByText( 'Subtotal' ) ).toBeInTheDocument();
		expect( getValue( container ) ).toBe( '$25.99' );
	} );

	it( 'renders value of 0 correctly', () => {
		const { container } = render(
			<TotalsItem
				label="Discount"
				value={ 0 }
				currency={ mockCurrency }
			/>
		);

		expect( screen.getByText( 'Discount' ) ).toBeInTheDocument();
		expect( getValue( container ) ).toBe( '$0.00' );
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
		const { container } = render(
			<TotalsItem
				label="Tax"
				value={ 599 }
				currency={ mockCurrency }
				description="Including VAT"
			/>
		);

		expect( screen.getByText( 'Tax' ) ).toBeInTheDocument();
		expect( getValue( container ) ).toBe( '$5.99' );
		expect( screen.getByText( 'Including VAT' ) ).toBeInTheDocument();
	} );

	it( 'shows skeleton when showSkeleton is true', () => {
		const { container } = render(
			<TotalsItem
				label="Loading"
				value={ 100 }
				currency={ mockCurrency }
				showSkeleton={ true }
			/>
		);

		expect( screen.getByLabelText( 'Loading price…' ) ).toBeInTheDocument();
		expect( getValue( container ) ).not.toBe( '$1.00' );
	} );

	it( 'does not show skeleton when showSkeleton is false', () => {
		const { container } = render(
			<TotalsItem
				label="Loaded"
				value={ 155 }
				currency={ mockCurrency }
				showSkeleton={ false }
			/>
		);

		expect( screen.getByText( 'Loaded' ) ).toBeInTheDocument();
		expect( getValue( container ) ).toBe( '$1.55' );
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

		const { container } = render(
			<TotalsItem label="Total" value={ 1000 } currency={ jpyCurrency } />
		);

		expect( screen.getByText( 'Total' ) ).toBeInTheDocument();
		expect( getValue( container ) ).toBe( '¥1,000' );
	} );

	it( 'renders without currency when not provided', () => {
		const { container } = render(
			<TotalsItem label="Amount" value={ 42 } />
		);

		expect( screen.getByText( 'Amount' ) ).toBeInTheDocument();
		// When no currency is provided, the value should still render
		expect( getValue( container ) ).toBe( '$0.42' );
	} );
} );
