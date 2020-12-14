/**
 * External dependencies
 */
import { select } from '@storybook/addon-knobs';

export const currencyKnob = () => {
	const currencies = [
		{
			label: 'USD',
			code: 'USD',
			symbol: '$',
			thousandSeparator: ',',
			decimalSeparator: '.',
			minorUnit: 2,
			prefix: '$',
			suffix: '',
		},
		{
			label: 'EUR',
			code: 'EUR',
			symbol: '€',
			thousandSeparator: '.',
			decimalSeparator: ',',
			minorUnit: 2,
			prefix: '',
			suffix: '€',
		},
	];
	return select( 'Currency', currencies, currencies[ 0 ] );
};
