/**
 * External dependencies
 */
import { number, select } from '@storybook/addon-knobs';
import { currencyKnob } from '@woocommerce/knobs';

/**
 * Internal dependencies
 */
import ProductPrice from '../';

export default {
	title: 'WooCommerce Blocks/@base-components/ProductPrice',
	component: ProductPrice,
};

export const standard = () => {
	const align = select( 'Align', [ 'left', 'center', 'right' ], 'left' );
	const currency = currencyKnob();
	const price = number( 'Price', 4000 );

	return (
		<ProductPrice align={ align } currency={ currency } price={ price } />
	);
};

export const sale = () => {
	const align = select( 'Align', [ 'left', 'center', 'right' ], 'left' );
	const currency = currencyKnob();
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
	const align = select( 'Align', [ 'left', 'center', 'right' ], 'left' );
	const currency = currencyKnob();
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
