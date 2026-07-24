/**
 * External dependencies
 */
import type { StoryFn, Meta } from '@storybook/react';
import { currencies, currencyControl } from '@woocommerce/storybook-controls';

/**
 * Internal dependencies
 */
import Taxes, { TotalsTaxesProps } from '..';

export default {
	title: 'External Components/Totals/Taxes',
	component: Taxes,
	argTypes: {
		currency: currencyControl,
		showRateAfterTaxName: {
			table: { disable: true },
		},
	},
	args: {
		values: {
			tax_lines: [
				{
					name: 'Expensive tax fee',
					price: '1000',
					rate: '500',
				},
			],
			total_tax: '2000',
		},
	},
} as Meta< TotalsTaxesProps >;

const Template: StoryFn< TotalsTaxesProps > = ( args ) => <Taxes { ...args } />;

export const Default: StoryFn< TotalsTaxesProps > = Template.bind( {} );
Default.args = {
	currency: currencies.USD,
};
