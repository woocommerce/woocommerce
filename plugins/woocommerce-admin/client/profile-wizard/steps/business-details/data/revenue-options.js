/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { CURRENCY } from '@woocommerce/wc-admin-settings';
import CurrencyFactory from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import { getCurrencyRegion } from '../../../../dashboard/utils';
import { getNumberRangeString } from './product-options';

const { formatAmount } = CurrencyFactory( CURRENCY );

// These are rough exchange rates from USD.  Precision is not paramount.
// The keys here should match the keys in `getCurrencyData`.
const exchangeRates = {
	US: 1,
	EU: 0.9,
	IN: 71.24,
	GB: 0.76,
	BR: 4.19,
	VN: 23172.5,
	ID: 14031.0,
	BD: 84.87,
	PK: 154.8,
	RU: 63.74,
	TR: 5.75,
	MX: 19.37,
	CA: 1.32,
};

const convertCurrency = ( value, country ) => {
	const region = getCurrencyRegion( country );

	if ( region === 'US' ) {
		return value;
	}

	const exchangeRate = exchangeRates[ region ] || exchangeRates.US;
	const digits = exchangeRate.toString().split( '.' )[ 0 ].length;
	const multiplier = Math.pow( 10, 2 + digits );

	return Math.round( ( value * exchangeRate ) / multiplier ) * multiplier;
};

export const getRevenueOptions = ( numberConfig, country ) => [
	{
		key: 'none',
		label: sprintf(
			/* translators: %s: $0 revenue amount */
			__( "%s (I'm just getting started)", 'woocommerce-admin' ),
			formatAmount( 0 )
		),
	},
	{
		key: 'up-to-2500',
		label: sprintf(
			/* translators: %s: A given revenue amount, e.g., $2500 */
			__( 'Up to %s', 'woocommerce-admin' ),
			formatAmount( convertCurrency( 2500, country ) )
		),
	},
	{
		key: '2500-10000',
		label: getNumberRangeString(
			numberConfig,
			convertCurrency( 2500, country ),
			convertCurrency( 10000, country ),
			( _, amount ) => formatAmount( amount )
		),
	},
	{
		key: '10000-50000',
		label: getNumberRangeString(
			numberConfig,
			convertCurrency( 10000, country ),
			convertCurrency( 50000, country ),
			( _, amount ) => formatAmount( amount )
		),
	},
	{
		key: '50000-250000',
		label: getNumberRangeString(
			numberConfig,
			convertCurrency( 50000, country ),
			convertCurrency( 250000, country ),
			( _, amount ) => formatAmount( amount )
		),
	},
	{
		key: 'more-than-250000',
		label: sprintf(
			/* translators: %s: A given revenue amount, e.g., $250000 */
			__( 'More than %s', 'woocommerce-admin' ),
			formatAmount( convertCurrency( 250000, country ) )
		),
	},
];
