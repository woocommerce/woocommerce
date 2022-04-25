/**
 * External dependencies
 */
import { formatValue } from '@woocommerce/number';
import { __, sprintf, _x } from '@wordpress/i18n';

const formatNumber = ( numberConfig, value ) => {
	return formatValue( numberConfig, 'number', value );
};

export const getNumberRangeString = (
	numberConfig,
	min,
	max = false,
	formatAmount = formatNumber
) => {
	if ( ! max ) {
		return sprintf(
			_x( '%s+', 'store product count or revenue', 'woocommerce' ),
			formatAmount( numberConfig, min )
		);
	}

	return sprintf(
		_x(
			'%1$s - %2$s',
			'store product count or revenue range',
			'woocommerce'
		),
		formatAmount( numberConfig, min ),
		formatAmount( numberConfig, max )
	);
};

export const getProductCountOptions = ( numberConfig ) => [
	{
		key: '0',
		label: __( "I don't have any products yet.", 'woocommerce' ),
	},
	{
		key: '1-10',
		label: getNumberRangeString( numberConfig, 1, 10 ),
	},
	{
		key: '11-100',
		label: getNumberRangeString( numberConfig, 11, 100 ),
	},
	{
		key: '101-1000',
		label: getNumberRangeString( numberConfig, 101, 1000 ),
	},
	{
		key: '1000+',
		label: getNumberRangeString( numberConfig, 1000 ),
	},
];
