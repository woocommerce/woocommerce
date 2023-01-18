/**
 * External dependencies
 */
import moment from 'moment';

/**
 * WooCommerce dependencies
 */
import { Card, CardHeader, __experimentalText as Text } from '@wordpress/components';
import { Chart } from '@woocommerce/components';
import Currency from '@woocommerce/currency';
import { CardBody } from '@wordpress/components';

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

export const GlobalPrices = ( { config }) => {
	const average =
		data.reduce( ( total, item ) => total + item.primary.value, 0 ) /
		data.length;
	return (
		<Card
			className="woocommerce-dashboard__chart-block woocommerce-analytics__card"
		>
			 <CardHeader>
			 	<Text size={ 16 } weight={ 600 } as="h2" color="#23282d">Global Apple Prices</Text>
      		</CardHeader>
			<CardBody>
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
			</CardBody>
		</Card>
	);
};
