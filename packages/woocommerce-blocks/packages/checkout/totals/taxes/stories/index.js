/**
 * External dependencies
 */
import { text } from '@storybook/addon-knobs';
import { currencyKnob } from '@woocommerce/knobs';

/**
 * Internal dependencies
 */
import TotalsTaxes from '../';

export default {
	title: 'WooCommerce Blocks/@blocks-checkout/TotalsTaxes',
	component: TotalsTaxes,
};

export const Default = () => {
	const currency = currencyKnob();
	const totalTaxes = text( 'Total taxes', '1000' );

	return (
		<TotalsTaxes
			currency={ currency }
			values={ {
				total_tax: totalTaxes,
			} }
		/>
	);
};
