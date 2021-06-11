/**
 * External dependencies
 */
import moment from 'moment';

/**
 * WooCommerce dependencies
 */
import { Card, Chart } from '@woocommerce/components';
import Currency from '@woocommerce/currency';

const storeCurrency = Currency();
const data = [];

for ( let i = 1; i <= 20; i++ ) {
	const date = moment().subtract( i, 'days' );
	data.push( {
		date: date.format( 'YYYY-MM-DDT00:00:00' ),
		primary: {
			label: 'Global Apple Prices, last 20 days',
			labelDate: date.format( 'YYYY-MM-DD 00:00:00' ),
			value: Math.floor( Math.random() * 100 ),
		},
	} );
}

const GlobalPrices = () => {
	const average =
		data.reduce( ( total, item ) => total + item.primary.value, 0 ) /
		data.length;
	return (
		<Card
			className="woocommerce-dashboard__chart-block woocommerce-analytics__card"
			title="Global Apple Prices"
		>
			<Chart
				title="Global Apple Prices"
				interval="day"
				data={ data.reverse() }
				dateParser="%Y-%m-%dT%H:%M:%S"
				legendTotals={ { primary: average } }
				showHeaderControls={ false }
				valueType={ 'currency' }
				tooltipValueFormat={ storeCurrency.formatCurrency }
			/>
		</Card>
	);
};

export default GlobalPrices;
