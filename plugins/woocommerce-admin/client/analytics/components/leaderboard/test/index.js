/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { CurrencyFactory, CurrencyContext } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import { Leaderboard } from '../';
import mockData from '../data/top-selling-products-mock-data';

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
			display: itemsSold.toString(),
			value: itemsSold,
			format: 'number',
		},
		{
			display: ordersCount.toString(),
			value: ordersCount.toString(),
			format: 'number',
		},
		{
			display: `<span class="woocommerce-Price-currencySymbol">${ netRevenue }</span>`,
			value: netRevenue,
			format: 'currency',
		},
	];
} );

describe( 'Leaderboard', () => {
	test( 'should render empty message when there are no rows', () => {
		render(
			<Leaderboard
				id="products"
				title={ '' }
				headers={ [] }
				rows={ [] }
				totalRows={ 5 }
			/>
		);

		expect(
			screen.getByText( 'No data recorded for the selected time period.' )
		).toBeInTheDocument();
	} );

	test( 'should render the headers', () => {
		render(
			<Leaderboard
				id="products"
				title={ '' }
				headers={ headers }
				rows={ rows }
				totalRows={ 5 }
			/>
		);

		expect( screen.getByText( 'Name' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Items sold' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Orders' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Net sales' ) ).toBeInTheDocument();
	} );

	test( 'should render formatted data in the table', () => {
		render(
			<Leaderboard
				id="products"
				title={ '' }
				headers={ headers }
				rows={ rows }
				totalRows={ 5 }
			/>
		);

		expect( screen.getAllByRole( 'row' ) ).toHaveLength( 6 );
		expect( screen.getByText( 'awesome shirt' ) ).toBeInTheDocument();
		expect( screen.getByText( '123,456,789' ) ).toBeInTheDocument();
		expect( screen.getByText( '54' ) ).toBeInTheDocument();
		expect( screen.getByText( '$9,876,543.22' ) ).toBeInTheDocument();
	} );

	test( 'should format data according to the currency context', () => {
		const currencySetting = {
			code: 'PLN',
			decimalSeparator: ',',
			precision: 3,
			priceFormat: '%1$s %2$s',
			symbol: 'zł',
			thousandSeparator: '.',
		};

		render(
			<CurrencyContext.Provider
				value={ new CurrencyFactory( currencySetting ) }
			>
				<Leaderboard
					id="products"
					title={ '' }
					headers={ headers }
					rows={ rows }
					totalRows={ 5 }
				/>
			</CurrencyContext.Provider>
		);

		expect( screen.getByText( 'awesome shirt' ) ).toBeInTheDocument();
		expect( screen.getByText( '123.456.789' ) ).toBeInTheDocument();
		expect( screen.getByText( '54' ) ).toBeInTheDocument();
		expect( screen.getByText( 'zł 9.876.543,215' ) ).toBeInTheDocument();
	} );

	test( `should not format data that is not specified in a format or doesn't conform to a number value`, () => {
		const columns = [
			{
				display: 'awesome shirt',
				value: '$123.456', // Not a pure numeric string
				format: 'currency',
			},
			{
				display: 'awesome pants',
				value: '123,456', // Not a pure numeric string
				format: 'number',
			},
			{
				display: 'awesome hat',
				value: '', // Not a number
				format: 'number',
			},
			{
				display: 'awesome sticker',
				value: 123,
				format: 'product', // Invalid format
			},
			{
				// Not specified format
				display: 'awesome button',
				value: 123,
			},
		];

		render(
			<Leaderboard
				id="products"
				title={ '' }
				headers={ columns.map( ( _, i ) => ( {
					label: i.toString(),
				} ) ) }
				rows={ [ columns ] }
				totalRows={ 5 }
			/>
		);

		expect( screen.getByText( 'awesome shirt' ) ).toBeInTheDocument();
		expect( screen.getByText( 'awesome pants' ) ).toBeInTheDocument();
		expect( screen.getByText( 'awesome hat' ) ).toBeInTheDocument();
		expect( screen.getByText( 'awesome sticker' ) ).toBeInTheDocument();
		expect( screen.getByText( 'awesome button' ) ).toBeInTheDocument();
	} );
} );
