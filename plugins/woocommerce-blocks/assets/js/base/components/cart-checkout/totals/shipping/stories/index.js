/**
 * External dependencies
 */
import { boolean, text } from '@storybook/addon-knobs';
import { currencyKnob } from '@woocommerce/knobs';

/**
 * Internal dependencies
 */
import TotalsShipping from '../';

export default {
	title: 'WooCommerce Blocks/@blocks-checkout/TotalsShipping',
	component: TotalsShipping,
};

export const Default = () => {
	const currency = currencyKnob();
	const showCalculator = boolean( 'Show calculator', true );
	const showRateSelector = boolean( 'Show rate selector', true );
	const totalShipping = text( 'Total shipping', '1000' );
	const totalShippingTax = text( 'Total shipping tax', '200' );

	return (
		<TotalsShipping
			currency={ currency }
			showCalculator={ showCalculator }
			showRateSelector={ showRateSelector }
			values={ {
				total_shipping: totalShipping,
				total_shipping_tax: totalShippingTax,
			} }
		/>
	);
};
