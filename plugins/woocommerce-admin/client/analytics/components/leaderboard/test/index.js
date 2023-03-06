/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { numberFormat } from '@woocommerce/number';
import { CurrencyFactory } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import { Leaderboard } from '../';
import mockData from '../data/top-selling-products-mock-data';
import { CURRENCY } from '~/utils/admin-settings';

const { formatAmount, formatDecimal } = CurrencyFactory( CURRENCY );

const headers = [
	{
		label: 'Name',
	},
	{
		label: 'Items sold',
	},
	{
		label: 'Orders',
	},
	{
		label: 'Net sales',
	},
];

const rows = mockData.map( ( row ) => {
	const {
		name,
		items_sold: itemsSold,
		net_revenue: netRevenue,
		orders_count: ordersCount,
	} = row;
	return [
		{
			display: '<a href="#">' + name + '</a>',
			value: name,
		},
		{
			display: numberFormat( CURRENCY, itemsSold ),
			value: itemsSold,
		},
		{
			display: numberFormat( CURRENCY, ordersCount ),
			value: ordersCount,
		},
		{
			display: formatAmount( netRevenue ),
			value: formatDecimal( netRevenue ),
		},
	];
} );

describe( 'Leaderboard', () => {
	test( 'should render empty message when there are no rows', () => {
		const { queryByText } = render(
			<Leaderboard
				id="products"
				title={ '' }
				headers={ [] }
				rows={ [] }
				totalRows={ 5 }
			/>
		);

		expect(
			queryByText( 'No data recorded for the selected time period.' )
		).toBeInTheDocument();
	} );

	test( 'should render the headers', () => {
		const { queryByText } = render(
			<Leaderboard
				id="products"
				title={ '' }
				headers={ headers }
				rows={ rows }
				totalRows={ 0 }
			/>
		);

		expect( queryByText( 'Name' ) ).toBeInTheDocument();
		expect( queryByText( 'Items sold' ) ).toBeInTheDocument();
		expect( queryByText( 'Orders' ) ).toBeInTheDocument();
		expect( queryByText( 'Net sales' ) ).toBeInTheDocument();
	} );

	test( 'should render formatted data in the table', () => {
		const { container, queryByText } = render(
			<Leaderboard
				id="products"
				title={ '' }
				headers={ headers }
				rows={ rows }
				totalRows={ 5 }
			/>
		);

		const tableRows = container.querySelectorAll( 'tr' );

		expect( tableRows.length ).toBe( 6 );
		expect( queryByText( 'awesome shirt' ) ).toBeInTheDocument();
		expect( queryByText( '1,000.00' ) ).toBeInTheDocument();
		expect( queryByText( '54.00' ) ).toBeInTheDocument();
		expect( queryByText( '$999.99' ) ).toBeInTheDocument();
	} );
} );
