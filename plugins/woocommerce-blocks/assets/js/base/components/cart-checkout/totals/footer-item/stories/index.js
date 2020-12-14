/**
 * External dependencies
 */
import { text } from '@storybook/addon-knobs';
import { currencyKnob } from '@woocommerce/knobs';

/**
 * Internal dependencies
 */
import TotalsFooterItem from '../';

export default {
	title:
		'WooCommerce Blocks/@base-components/cart-checkout/totals/TotalsFooterItem',
	component: TotalsFooterItem,
};

export const Default = () => {
	const currency = currencyKnob();
	const totalPrice = text( 'Total price', '1200' );
	const totalTax = text( 'Total tax', '200' );

	return (
		<TotalsFooterItem
			currency={ currency }
			values={ {
				total_price: totalPrice,
				total_tax: totalTax,
			} }
		/>
	);
};
