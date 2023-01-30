/**
 * External dependencies
 */
import { number, select } from '@storybook/addon-knobs';

/**
 * Internal dependencies
 */
import ProductPrice from '../';

export default {
	title: 'WooCommerce Blocks/@base-components/ProductPrice',
	component: ProductPrice,
};

const getKnobs = () => {
	const align = select( 'Align', [ 'left', 'center', 'right' ], 'left' );
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
	const currency = select( 'Currency', currencies, currencies[ 0 ] );

	return { align, currency };
};

export const standard = () => {
	const { align, currency } = getKnobs();
	const price = number( 'Price', 4000 );

	return (
		<ProductPrice align={ align } currency={ currency } price={ price } />
	);
};

export const sale = () => {
	const { align, currency } = getKnobs();
	const price = number( 'Price', 3000 );
	const regularPrice = number( 'Regular price', 4000 );

	return (
		<ProductPrice
			align={ align }
			currency={ currency }
			price={ price }
			regularPrice={ regularPrice }
		/>
	);
};

export const range = () => {
	const { align, currency } = getKnobs();
	const minPrice = number( 'Min price', 3000 );
	const maxPrice = number( 'Max price', 5000 );

	return (
		<ProductPrice
			align={ align }
			currency={ currency }
			minPrice={ minPrice }
			maxPrice={ maxPrice }
		/>
	);
};
