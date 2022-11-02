/**
 * External dependencies
 */
import type { Story, Meta } from '@storybook/react';
import { allSettings } from '@woocommerce/settings';
import { Currency } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import FooterItem, { TotalsFooterItemProps } from '..';

const NZD: Currency = {
	code: 'NZD',
	symbol: '$',
	thousandSeparator: ' ',
	decimalSeparator: '.',
	minorUnit: 2,
	prefix: '$',
	suffix: '',
};

export default {
	title: 'WooCommerce Blocks/@base-components/cart-checkout/totals/FooterItem',
	component: FooterItem,
	args: {
		currency: NZD,
		values: { total_price: '2500', total_tax: '550' },
	},
} as Meta< TotalsFooterItemProps >;

const Template: Story< TotalsFooterItemProps > = ( args ) => (
	<FooterItem { ...args } />
);

export const Default = Template.bind( {} );
Default.decorators = [
	( StoryComponent ) => {
		allSettings.displayCartPricesIncludingTax = false;

		return <StoryComponent />;
	},
];

export const IncludingTaxes = Template.bind( {} );
IncludingTaxes.decorators = [
	( StoryComponent ) => {
		allSettings.displayCartPricesIncludingTax = true;

		return <StoryComponent />;
	},
];
