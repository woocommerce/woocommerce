/**
 * External dependencies
 */
import { text } from '@storybook/addon-knobs';
import { currencyKnob } from '@woocommerce/knobs';

/**
 * Internal dependencies
 */
import TotalsFees from '../';

export default {
	title: 'WooCommerce Blocks/@blocks-checkout/TotalsFees',
	component: TotalsFees,
};

export const Default = () => {
	const currency = currencyKnob();
	const totalFees = text( 'Total fee', '1000' );
	const totalFeesTax = text( 'Total fee tax', '200' );

	return (
		<TotalsFees
			currency={ currency }
			cartFees={ [
				{
					id: 'fee',
					name: 'Fee',
					totals: {
						total: totalFees,
						total_tax: totalFeesTax,
					},
				},
			] }
		/>
	);
};
