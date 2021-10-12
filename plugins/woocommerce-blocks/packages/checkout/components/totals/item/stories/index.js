/**
 * External dependencies
 */
import { text } from '@storybook/addon-knobs';
import { currencyKnob } from '@woocommerce/knobs';

/**
 * Internal dependencies
 */
import TotalsItem from '../';

export default {
	title: 'WooCommerce Blocks/@blocks-checkout/TotalsItem',
	component: TotalsItem,
};

export const Default = () => {
	const currency = currencyKnob();
	const description = text(
		'Description',
		'This is the amount that will be charged to your card.'
	);
	const label = text( 'Label', 'Amount' );
	const value = text( 'Value', '1000' );

	return (
		<TotalsItem
			currency={ currency }
			description={ description }
			label={ label }
			value={ value }
		/>
	);
};
