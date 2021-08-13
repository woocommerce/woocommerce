/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { numberFormat } from '@woocommerce/number';
import CurrencyFactory from '@woocommerce/currency';
import { CURRENCY } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import { Leaderboard } from '../';
import mockData from '../data/top-selling-products-mock-data';

const { formatAmount, formatDecimal } = CurrencyFactory( CURRENCY );

describe( 'Leaderboard', () => {
	test( 'should render empty message when there are no rows', () => {
		const { container } = render(
			<Leaderboard
				id="products"
				title={ '' }
				headers={ [] }
				rows={ [] }
				totalRows={ 5 }
			/>
		);

		expect( container ).toMatchSnapshot();
	} );

	test( 'should render correct data in the table', () => {
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
		const { container } = render(
			<Leaderboard
				id="products"
				title={ '' }
				headers={ headers }
				rows={ rows }
				totalRows={ 5 }
			/>
		);

		expect( container ).toMatchSnapshot();
	} );
} );
